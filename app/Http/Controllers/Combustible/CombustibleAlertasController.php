<?php

namespace App\Http\Controllers\Combustible;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CombustibleAlertasController extends Controller
{
    // ─── REGLAS DE VALIDACIÓN ─────────────────────────────────────────────────

    public function getReglas(Request $request)
    {
        return DB::table('comb_reglas_validacion')
            ->orderBy('severidad')->orderBy('nombre')
            ->paginate($request->per_page ?? 20);
    }

    public function guardarRegla(Request $request, $id = null)
    {
        $request->validate([
            'nombre'    => 'required|string|max:150',
            'tipo'      => 'required',
            'severidad' => 'required|in:info,advertencia,critica',
        ]);

        $datos = [
            'nombre'      => $request->nombre,
            'tipo'        => $request->tipo,
            'severidad'   => $request->severidad,
            'activo'      => $request->has('activo') ? (int)$request->activo : 1,
            'parametros'  => $request->parametros ? json_encode($request->parametros) : null,
            'descripcion' => $request->descripcion,
            'updated_at'  => now(),
        ];

        if ($id) {
            DB::table('comb_reglas_validacion')->where('id', $id)->update($datos);
            return response()->json(['message' => 'Regla actualizada', 'id' => $id]);
        }

        $datos['created_at'] = now();
        $newId = DB::table('comb_reglas_validacion')->insertGetId($datos);
        return response()->json(['message' => 'Regla creada', 'id' => $newId], 201);
    }

    public function eliminarRegla($id)
    {
        DB::table('comb_reglas_validacion')->where('id', $id)->update(['activo' => 0, 'updated_at' => now()]);
        return response()->json(['message' => 'Regla desactivada']);
    }

    // ─── ALERTAS GENERADAS ────────────────────────────────────────────────────

    public function getAlertas(Request $request)
    {
        $query = DB::table('comb_alertas as a')
            ->leftJoin('activos_fijos as af', 'a.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->select('a.*', 'af.descripcion as unidad', 'afu.placas')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->orderByRaw("FIELD(a.nivel, 'critica', 'advertencia', 'info')")
            ->orderByDesc('a.fecha_alerta');

        $query->when($request->nivel,       fn($q) => $q->where('a.nivel', $request->nivel));
        $query->when($request->tipo_alerta, fn($q) => $q->where('a.tipo_alerta', $request->tipo_alerta));
        $query->when(isset($request->leida), fn($q) => $q->where('a.leida', (int)$request->leida));

        return $query->paginate($request->per_page ?? 20);
    }

    public function marcarLeida($id)
    {
        DB::table('comb_alertas')->where('id', $id)->update(['leida' => 1, 'updated_at' => now()]);
        return response()->json(['message' => 'Alerta marcada como leída']);
    }

    public function marcarTodasLeidas()
    {
        DB::table('comb_alertas')->where('leida', 0)->update(['leida' => 1, 'updated_at' => now()]);
        return response()->json(['message' => 'Todas las alertas marcadas como leídas']);
    }

    /**
     * Valida un ticket contra las reglas activas y genera alertas automáticamente.
     */
    public function validarTicket(Request $request, $idTicket)
    {
        $ticket = DB::table('tickets_combustibles')->where('id', $idTicket)->first();
        if (!$ticket) return response()->json(['message' => 'Ticket no encontrado'], 404);

        if (!ACTIVO_FIJO_ES_UNIDAD_POR_ID($ticket->idvehiculo)) {
            return response()->json(['message' => 'El ticket no corresponde a una unidad de tipo Unidad.'], 422);
        }

        $alertas  = [];
        $reglas   = DB::table('comb_reglas_validacion')->where('activo', 1)->get();

        foreach ($reglas as $regla) {
            $params = $regla->parametros ? json_decode($regla->parametros, true) : [];
            $alerta = null;

            switch ($regla->tipo) {
                case 'km_regresion':
                    if ($ticket->odometrocarga < $ticket->ultimoodometro) {
                        $alerta = [
                            'mensaje' => "Km carga ({$ticket->odometrocarga}) es menor al anterior ({$ticket->ultimoodometro}).",
                            'nivel'   => 'critica',
                        ];
                    }
                    break;

                case 'carga_doble':
                    $horasLimite = $params['horas'] ?? 4;
                    $anterior = DB::table('tickets_combustibles')
                        ->where('idvehiculo', $ticket->idvehiculo)
                        ->where('id', '!=', $idTicket)
                        ->where('fechacarga', $ticket->fechacarga)
                        ->exists();
                    if ($anterior) {
                        $alerta = [
                            'mensaje' => "Posible doble carga: ya existe un registro para esta unidad en la misma fecha.",
                            'nivel'   => 'advertencia',
                        ];
                    }
                    break;

                case 'rendimiento_bajo':
                    $minRendimiento = $params['minimo'] ?? 3.0;
                    if ($ticket->rendimiento > 0 && $ticket->rendimiento < $minRendimiento) {
                        $alerta = [
                            'mensaje' => "Rendimiento bajo: {$ticket->rendimiento} km/l (mínimo esperado: {$minRendimiento}).",
                            'nivel'   => 'advertencia',
                        ];
                    }
                    break;

                case 'incremento_excesivo':
                    $pctMax = $params['pct'] ?? 50;
                    $promedioLitros = DB::table('tickets_combustibles')
                        ->where('idvehiculo', $ticket->idvehiculo)
                        ->where('id', '!=', $idTicket)
                        ->orderByDesc('fechacarga')
                        ->limit(5)
                        ->avg('litros') ?? 0;
                    if ($promedioLitros > 0) {
                        $pctIncremento = (($ticket->litros - $promedioLitros) / $promedioLitros) * 100;
                        if ($pctIncremento > $pctMax) {
                            $alerta = [
                                'mensaje' => "Litros ({$ticket->litros}) son " . round($pctIncremento) . "% mayores al promedio reciente ({$promedioLitros}).",
                                'nivel'   => 'advertencia',
                            ];
                        }
                    }
                    break;
            }

            if ($alerta) {
                // Evitar duplicados
                $existe = DB::table('comb_alertas')
                    ->where('idticket', $idTicket)
                    ->where('tipo_alerta', $regla->tipo)
                    ->exists();

                if (!$existe) {
                    DB::table('comb_alertas')->insert([
                        'idticket'     => $idTicket,
                        'idactivofijo' => $ticket->idvehiculo,
                        'idregla'      => $regla->id,
                        'tipo_alerta'  => $regla->tipo,
                        'nivel'        => $alerta['nivel'],
                        'mensaje'      => $alerta['mensaje'],
                        'leida'        => 0,
                        'fecha_alerta' => now(),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                    $alertas[] = $alerta;
                }
            }
        }

        return response()->json([
            'alertas_generadas' => count($alertas),
            'alertas'           => $alertas,
        ]);
    }
}
