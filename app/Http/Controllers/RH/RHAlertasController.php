<?php

namespace App\Http\Controllers\RH;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RHAlertasController extends Controller
{
    public function getAlertas(Request $request)
    {
        $query = DB::table('rh_alertas as a')
            ->leftJoin('empleados as e', 'a.idempleado', '=', 'e.id')
            ->select('a.*', 'e.nombrecompleto', 'e.numeroempleado')
            ->orderByRaw("FIELD(a.nivel, 'critica', 'advertencia', 'info')")
            ->orderByDesc('a.fecha_alerta');

        $query->when($request->nivel,       fn($q) => $q->where('a.nivel', $request->nivel));
        $query->when($request->tipo_alerta, fn($q) => $q->where('a.tipo_alerta', $request->tipo_alerta));
        $query->when(isset($request->leida), fn($q) => $q->where('a.leida', (int)$request->leida));

        return $query->paginate($request->per_page ?? 20);
    }

    public function getResumen(): array
    {
        $row = DB::table('rh_alertas')
            ->where('leida', 0)
            ->selectRaw('COUNT(*) as total,
                COALESCE(SUM(CASE WHEN nivel="critica"     THEN 1 ELSE 0 END), 0) as criticas,
                COALESCE(SUM(CASE WHEN nivel="advertencia" THEN 1 ELSE 0 END), 0) as advertencias,
                COALESCE(SUM(CASE WHEN nivel="info"        THEN 1 ELSE 0 END), 0) as informativas')
            ->first();

        return $row ? (array) $row : [
            'total' => 0, 'criticas' => 0, 'advertencias' => 0, 'informativas' => 0,
        ];
    }

    public function marcarLeida($id)
    {
        DB::table('rh_alertas')->where('id', $id)->update(['leida' => 1, 'fecha_lectura' => now(), 'updated_at' => now()]);
        return response()->json(['message' => 'Alerta marcada como leída']);
    }

    public function marcarTodasLeidas()
    {
        DB::table('rh_alertas')->where('leida', 0)->update(['leida' => 1, 'fecha_lectura' => now(), 'updated_at' => now()]);
        return response()->json(['message' => 'Todas las alertas marcadas como leídas']);
    }

    /**
     * Genera las alertas automáticas para todo el personal activo.
     */
    public function generarAlertas(): array
    {
        $generadas = 0;
        $hoy = Carbon::today();

        // ─── 1. Contratos por vencer (60 días) ───────────────────────────
        $contratos = DB::table('rh_empleados_extra as ex')
            ->join('empleados as e', 'ex.idempleado', '=', 'e.id')
            ->whereIn('e.estatus', config('rh.active_statuses'))
            ->whereNotNull('ex.fecha_fin_contrato')
            ->where('ex.fecha_fin_contrato', '>=', $hoy->format('Y-m-d'))
            ->where('ex.fecha_fin_contrato', '<=', $hoy->copy()->addDays(60)->format('Y-m-d'))
            ->select('ex.idempleado', 'ex.fecha_fin_contrato', 'e.nombrecompleto')
            ->get();

        foreach ($contratos as $c) {
            $dias = $hoy->diffInDays(Carbon::parse($c->fecha_fin_contrato), false);
            $nivel = $dias <= 15 ? 'critica' : ($dias <= 30 ? 'advertencia' : 'info');
            $this->upsertAlerta($c->idempleado, 'contrato_vencer', $nivel,
                "{$c->nombrecompleto}: contrato vence en {$dias} días (" . Carbon::parse($c->fecha_fin_contrato)->format('d/m/Y') . ")",
                $generadas
            );
        }

        // ─── 2. Documentos por vencer ─────────────────────────────────────
        $documentos = DB::table('rh_documentos as d')
            ->join('empleados as e', 'd.idempleado', '=', 'e.id')
            ->where('d.vigente', 1)
            ->whereIn('e.estatus', config('rh.active_statuses'))
            ->whereNotNull('d.fecha_vencimiento')
            ->where('d.fecha_vencimiento', '<=', $hoy->copy()->addDays(60)->format('Y-m-d'))
            ->select('d.idempleado', 'd.tipo_documento', 'd.fecha_vencimiento', 'e.nombrecompleto')
            ->get();

        foreach ($documentos as $doc) {
            $dias = $hoy->diffInDays(Carbon::parse($doc->fecha_vencimiento), false);
            $nivel = $dias <= 0 ? 'critica' : ($dias <= 15 ? 'advertencia' : 'info');
            $this->upsertAlerta($doc->idempleado, 'documento_vencer', $nivel,
                "{$doc->nombrecompleto}: {$doc->tipo_documento} " . ($dias <= 0 ? 'VENCIDO' : "vence en {$dias} días"),
                $generadas
            );
        }

        // ─── 3. Cumpleaños (próximos 7 días) ─────────────────────────────
        $cumpleanos = DB::table('empleados')
            ->whereIn('estatus', config('rh.active_statuses'))
            ->whereNotNull('fechanacimiento')
            ->whereRaw("DATE_FORMAT(fechanacimiento, '%m-%d') BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')")
            ->select('id', 'nombrecompleto', 'fechanacimiento')
            ->get();

        foreach ($cumpleanos as $emp) {
            $this->upsertAlerta($emp->id, 'cumpleanos', 'info',
                "🎂 Cumpleaños de {$emp->nombrecompleto}: " . Carbon::parse($emp->fechanacimiento)->format('d de F'),
                $generadas
            );
        }

        // ─── 4. Expedientes incompletos ────────────────────────────────────
        $tiposRequeridos = ['INE', 'CURP', 'RFC', 'NSS', 'contrato'];
        $empleadosActivos = DB::table('empleados')->whereIn('estatus', config('rh.active_statuses'))->pluck('id');

        foreach ($empleadosActivos as $idEmp) {
            $existentes = DB::table('rh_documentos')
                ->where('idempleado', $idEmp)
                ->where('vigente', 1)
                ->pluck('tipo_documento')
                ->toArray();

            $faltantes = array_diff($tiposRequeridos, $existentes);
            if (!empty($faltantes)) {
                $emp = DB::table('empleados')->where('id', $idEmp)->value('nombrecompleto');
                $this->upsertAlerta($idEmp, 'expediente_incompleto', 'advertencia',
                    "{$emp}: faltan documentos: " . implode(', ', $faltantes),
                    $generadas
                );
            }
        }

        return ['generadas' => $generadas];
    }

    protected function upsertAlerta(int $idempleado, string $tipo, string $nivel, string $mensaje, int &$count): void
    {
        $existe = DB::table('rh_alertas')
            ->where('idempleado', $idempleado)
            ->where('tipo_alerta', $tipo)
            ->where('leida', 0)
            ->first();

        if ($existe) {
            DB::table('rh_alertas')->where('id', $existe->id)->update(['nivel' => $nivel, 'mensaje' => $mensaje, 'fecha_alerta' => now()->format('Y-m-d'), 'updated_at' => now()]);
        } else {
            DB::table('rh_alertas')->insert([
                'idempleado'  => $idempleado,
                'tipo_alerta' => $tipo,
                'nivel'       => $nivel,
                'mensaje'     => $mensaje,
                'leida'       => 0,
                'fecha_alerta'=> now()->format('Y-m-d'),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $count++;
        }
    }
}
