<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Flotilla\FlotillaService;

class FlotillaMantenimientoPreventivController extends Controller
{
    protected FlotillaService $flotillaService;

    public function __construct(FlotillaService $flotillaService)
    {
        $this->flotillaService = $flotillaService;
    }

    public function getMantenimientos(Request $request)
    {
        $query = DB::table('flotilla_mantenimiento_preventivo as mp')
            ->join('activos_fijos as af', 'mp.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('talleres as t', 'mp.idtaller', '=', 't.id')
            ->leftJoin('empleados as e', 'mp.idempleado_registra', '=', 'e.id')
            ->leftJoin('flotilla_unidad_mantenimiento as um', 'mp.idunidad_mantenimiento', '=', 'um.id')
            ->select(
                'mp.*',
                'af.descripcion as unidad',
                'afu.placas',
                'afu.numeroeconomico',
                't.razonsocial as taller',
                DB::raw("CONCAT(e.nombres, ' ', e.apellidopaterno) as empleado"),
                'um.nombre_servicio'
            )
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('mp.folio', 'like', $s)
                    ->orWhere('af.descripcion', 'like', $s)
                    ->orWhere('um.nombre_servicio', 'like', $s);
            });
        });

        $query->when($request->idactivofijo, fn($q) => $q->where('mp.idactivofijo', $request->idactivofijo));
        $query->when($request->idtaller,     fn($q) => $q->where('mp.idtaller', $request->idtaller));
        $query->when($request->estatus,      fn($q) => $q->where('mp.estatus', $request->estatus));
        $query->when($request->estatus_autorizacion, fn($q) => $q->where('mp.estatus_autorizacion', $request->estatus_autorizacion));
        $query->when($request->fechade,      fn($q) => $q->where('mp.fecha_servicio', '>=', $request->fechade));
        $query->when($request->fechaa,       fn($q) => $q->where('mp.fecha_servicio', '<=', $request->fechaa));

        $query->orderBy('mp.fecha_servicio', 'desc')->orderBy('mp.id', 'desc');

        return $query->paginate($request->per_page ?? 15);
    }

    public function getMantenimiento($id)
    {
        $mant = DB::table('flotilla_mantenimiento_preventivo as mp')
            ->join('activos_fijos as af', 'mp.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('talleres as t', 'mp.idtaller', '=', 't.id')
            ->leftJoin('empleados as e', 'mp.idempleado_registra', '=', 'e.id')
            ->leftJoin('flotilla_unidad_mantenimiento as um', 'mp.idunidad_mantenimiento', '=', 'um.id')
            ->select(
                'mp.*',
                'af.descripcion as unidad',
                'afu.placas', 'afu.numeroeconomico',
                't.razonsocial as taller',
                DB::raw("CONCAT(e.nombres, ' ', e.apellidopaterno) as empleado"),
                'um.nombre_servicio'
            )
            ->where('mp.id', $id)
            ->first();

        if (!$mant) {
            return response()->json(['message' => 'Mantenimiento no encontrado'], 404);
        }

        $partidas = DB::table('flotilla_mantenimiento_partidas as p')
            ->leftJoin('flotilla_cat_refacciones as r', 'p.idcatalogo_refaccion', '=', 'r.id')
            ->select('p.*', 'r.nombre as refaccion_catalogo')
            ->where('p.tipo_mantenimiento', 'preventivo')
            ->where('p.idmantenimiento', $id)
            ->get();

        $archivos = DB::table('flotilla_archivos')
            ->where('entidad_tipo', 'preventivo')
            ->where('entidad_id', $id)
            ->get();

        // Load service detail items
        $servicios = DB::table('flotilla_mantenimiento_servicios as s')
            ->leftJoin('cat_tipos_servicio as ts', 's.idtipo_servicio', '=', 'ts.id')
            ->select('s.*', 'ts.nombre as nombre_servicio_catalogo')
            ->where('s.idmantenimiento', $id)
            ->where('s.tipo_mantenimiento', 'preventivo')
            ->orderBy('s.orden')
            ->get();

        // Authorization user name
        $autorizador = null;
        if ($mant->idusuario_autoriza) {
            $autorizador = DB::table('users')->where('id', $mant->idusuario_autoriza)->value('name');
        }

        return response()->json([
            'mantenimiento' => $mant,
            'partidas'      => $partidas,
            'servicios'     => $servicios,
            'archivos'      => $archivos,
            'autorizador'   => $autorizador,
        ]);
    }

    public function guardarMantenimiento(Request $request, $id = null)
    {
        $request->validate([
            'idactivofijo'            => 'required|integer',
            'idunidad_mantenimiento'  => 'required|integer',
            'fecha_servicio'          => 'required|date',
            'km_servicio'             => 'required|numeric|min:0',
        ]);

        if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($request->idactivofijo)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de flotilla.'], 422);
        }

        $datos = [
            'idactivofijo'           => $request->idactivofijo,
            'idunidad_mantenimiento' => $request->idunidad_mantenimiento,
            'idtaller'               => $request->idtaller,
            'idempleado_registra'    => $request->idempleado_registra,
            'fecha_ingreso'          => $request->fecha_ingreso ?? $request->fecha_servicio,
            'estatus'                => $request->estatus ?? 'pendiente',
            'fecha_servicio'         => $request->fecha_servicio,
            'km_servicio'            => $request->km_servicio,
            'horas_servicio'         => $request->horas_servicio,
            'descripcion'            => $request->descripcion,
            'costo_mano_obra'        => $request->costo_mano_obra ?? 0,
            'costo_refacciones'      => 0, // se recalcula con partidas
            'costo_total'            => $request->costo_mano_obra ?? 0,
            'observaciones'          => $request->observaciones,
            'idusuario'              => $request->idusuario,
            'updated_at'             => now(),
        ];

        if ($id) {
            DB::table('flotilla_mantenimiento_preventivo')->where('id', $id)->update($datos);
        } else {
            $datos['folio']      = $this->flotillaService->generarFolioPreventivo();
            $datos['created_at'] = now();
            $id = DB::table('flotilla_mantenimiento_preventivo')->insertGetId($datos);
        }

        // Guardar partidas si vienen en el request
        if ($request->has('partidas')) {
            DB::table('flotilla_mantenimiento_partidas')
                ->where('tipo_mantenimiento', 'preventivo')
                ->where('idmantenimiento', $id)
                ->delete();

            foreach ($request->partidas as $partida) {
                $ctotal = ($partida['cantidad'] ?? 1) * ($partida['costo_unitario'] ?? 0);
                DB::table('flotilla_mantenimiento_partidas')->insert([
                    'tipo_mantenimiento'    => 'preventivo',
                    'idmantenimiento'       => $id,
                    'idcatalogo_refaccion'  => $partida['idcatalogo_refaccion'] ?? null,
                    'descripcion'           => $partida['descripcion'],
                    'cantidad'              => $partida['cantidad'] ?? 1,
                    'unidad_medida'         => $partida['unidad_medida'] ?? 'pieza',
                    'costo_unitario'        => $partida['costo_unitario'] ?? 0,
                    'costo_total'           => $ctotal,
                    'numero_serie'          => $partida['numero_serie'] ?? null,
                    'posicion'              => $partida['posicion'] ?? null,
                    'vida_util_km'          => $partida['vida_util_km'] ?? null,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }
        }

        // Guardar servicios (Detalle de Servicios)
        if ($request->has('servicios')) {
            DB::table('flotilla_mantenimiento_servicios')
                ->where('tipo_mantenimiento', 'preventivo')
                ->where('idmantenimiento', $id)
                ->delete();

            foreach ($request->servicios as $idx => $srv) {
                DB::table('flotilla_mantenimiento_servicios')->insert([
                    'idmantenimiento'       => $id,
                    'tipo_mantenimiento'    => 'preventivo',
                    'idtipo_servicio'       => $srv['idtipo_servicio'] ?? null,
                    'descripcion_servicio'  => $srv['descripcion_servicio'] ?? null,
                    'importe'              => $srv['importe'] ?? 0,
                    'observaciones'        => $srv['observaciones'] ?? null,
                    'orden'                => $idx + 1,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        }

        // Recalcular costos después de partidas y servicios
        if ($request->has('partidas') || $request->has('servicios')) {
            $this->flotillaService->recalcularCostosPreventivo($id);
        }
        $schedule = DB::table('flotilla_unidad_mantenimiento')
            ->where('id', $request->idunidad_mantenimiento)
            ->first();

        if ($schedule) {
            $proximo = $this->flotillaService->calcularProximoServicio(
                $schedule,
                (float)$request->km_servicio,
                $request->fecha_servicio
            );

            DB::table('flotilla_unidad_mantenimiento')
                ->where('id', $schedule->id)
                ->update([
                    'ultimo_km'     => $proximo['ultimo_km'],
                    'ultima_fecha'  => $proximo['ultima_fecha'],
                    'proximo_km'    => $proximo['proximo_km'],
                    'proxima_fecha' => $proximo['proxima_fecha'],
                    'estatus_alerta'=> 'verde',
                    'updated_at'    => now(),
                ]);
        }

        // Registrar en bitácora
        $this->flotillaService->registrarBitacora([
            'idactivofijo' => $request->idactivofijo,
            'tipo_evento'  => 'mant_preventivo',
            'entidad_tipo' => 'flotilla_mantenimiento_preventivo',
            'entidad_id'   => $id,
            'descripcion'  => 'Mantenimiento preventivo: ' . ($schedule->nombre_servicio ?? ''),
            'km_evento'    => $request->km_servicio,
            'idusuario'    => $request->idusuario,
            'fecha_evento' => $request->fecha_servicio . ' 00:00:00',
        ]);

        return response()->json(['message' => 'Mantenimiento guardado', 'id' => $id]);
    }

    public function eliminarMantenimiento($id)
    {
        $mant = DB::table('flotilla_mantenimiento_preventivo')->where('id', $id)->first();

        if (!$mant) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        if (($mant->estatus_autorizacion ?? 'pendiente') === 'autorizado') {
            return response()->json(['message' => 'No se puede eliminar un mantenimiento ya autorizado.'], 422);
        }

        DB::table('flotilla_mantenimiento_preventivo')->where('id', $id)->delete();
        DB::table('flotilla_mantenimiento_partidas')
            ->where('tipo_mantenimiento', 'preventivo')
            ->where('idmantenimiento', $id)
            ->delete();
        DB::table('flotilla_mantenimiento_servicios')
            ->where('tipo_mantenimiento', 'preventivo')
            ->where('idmantenimiento', $id)
            ->delete();

        return response()->json(['message' => 'Mantenimiento eliminado']);
    }

    /**
     * PUT /flotilla/mantenimiento/preventivo/{id}/autorizacion
     * Authorize or reject a preventive maintenance.
     * Requires permission: /flotilla/mantenimientos:autorizar
     */
    public function autorizarMantenimiento(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Check authorization permission
        $tienePermiso = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where('permission_path', '/flotilla/mantenimientos:autorizar')
            ->exists();

        if (!$tienePermiso) {
            return response()->json(['message' => 'No tienes permiso para autorizar mantenimientos.'], 403);
        }

        $mant = DB::table('flotilla_mantenimiento_preventivo')->where('id', $id)->first();
        if (!$mant) return response()->json(['message' => 'Mantenimiento no encontrado'], 404);

        if (($mant->estatus_autorizacion ?? 'pendiente') === 'autorizado') {
            return response()->json(['message' => 'Este mantenimiento ya fue autorizado.'], 422);
        }

        $request->validate([
            'accion'         => 'required|in:autorizar,rechazar',
            'motivo_rechazo' => 'required_if:accion,rechazar|nullable|string|max:500',
        ]);

        DB::table('flotilla_mantenimiento_preventivo')
            ->where('id', $id)
            ->update([
                'estatus_autorizacion' => $request->accion === 'autorizar' ? 'autorizado' : 'rechazado',
                'idusuario_autoriza'   => $user->id,
                'fecha_autorizacion'   => now(),
                'motivo_rechazo'       => $request->accion === 'rechazar' ? $request->motivo_rechazo : null,
                'updated_at'           => now(),
            ]);

        $msg = $request->accion === 'autorizar'
            ? 'Mantenimiento autorizado correctamente.'
            : 'Mantenimiento rechazado.';

        return response()->json(['message' => $msg]);
    }
}
