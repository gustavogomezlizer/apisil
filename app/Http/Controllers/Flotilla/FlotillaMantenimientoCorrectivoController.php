<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Flotilla\FlotillaService;

class FlotillaMantenimientoCorrectivoController extends Controller
{
    protected FlotillaService $flotillaService;

    public function __construct(FlotillaService $flotillaService)
    {
        $this->flotillaService = $flotillaService;
    }

    public function getMantenimientos(Request $request)
    {
        $query = DB::table('flotilla_mantenimiento_correctivo as mc')
            ->join('activos_fijos as af', 'mc.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('talleres as t', 'mc.idtaller', '=', 't.id')
            ->leftJoin('empleados as e', 'mc.idempleado_registra', '=', 'e.id')
            ->select(
                'mc.*',
                'af.descripcion as unidad',
                'afu.placas', 'afu.numeroeconomico',
                't.razonsocial as taller',
                DB::raw("CONCAT(e.nombres, ' ', e.apellidopaterno) as empleado")
            )
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('mc.folio', 'like', $s)
                    ->orWhere('mc.diagnostico', 'like', $s)
                    ->orWhere('af.descripcion', 'like', $s);
            });
        });

        $query->when($request->idactivofijo, fn($q) => $q->where('mc.idactivofijo', $request->idactivofijo));
        $query->when($request->idtaller,     fn($q) => $q->where('mc.idtaller', $request->idtaller));
        $query->when($request->estatus,      fn($q) => $q->where('mc.estatus', $request->estatus));
        $query->when($request->estatus_autorizacion, fn($q) => $q->where('mc.estatus_autorizacion', $request->estatus_autorizacion));
        $query->when($request->fechade,      fn($q) => $q->where('mc.fecha_ingreso', '>=', $request->fechade));
        $query->when($request->fechaa,       fn($q) => $q->where('mc.fecha_ingreso', '<=', $request->fechaa));

        $query->orderBy('mc.fecha_ingreso', 'desc')->orderBy('mc.id', 'desc');

        return $query->paginate($request->per_page ?? 15);
    }

    public function getMantenimiento($id)
    {
        $mant = DB::table('flotilla_mantenimiento_correctivo as mc')
            ->join('activos_fijos as af', 'mc.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('talleres as t', 'mc.idtaller', '=', 't.id')
            ->leftJoin('empleados as e', 'mc.idempleado_registra', '=', 'e.id')
            ->select(
                'mc.*',
                'af.descripcion as unidad',
                'afu.placas', 'afu.numeroeconomico',
                't.razonsocial as taller',
                DB::raw("CONCAT(e.nombres, ' ', e.apellidopaterno) as empleado")
            )
            ->where('mc.id', $id)
            ->first();

        if (!$mant) {
            return response()->json(['message' => 'Mantenimiento correctivo no encontrado'], 404);
        }

        $partidas = DB::table('flotilla_mantenimiento_partidas as p')
            ->leftJoin('flotilla_cat_refacciones as r', 'p.idcatalogo_refaccion', '=', 'r.id')
            ->select('p.*', 'r.nombre as refaccion_catalogo')
            ->where('p.tipo_mantenimiento', 'correctivo')
            ->where('p.idmantenimiento', $id)
            ->get();

        $servicios = DB::table('flotilla_mantenimiento_servicios as s')
            ->leftJoin('cat_tipos_servicio as ts', 's.idtipo_servicio', '=', 'ts.id')
            ->select('s.*', 'ts.nombre as nombre_servicio_catalogo')
            ->where('s.idmantenimiento', $id)
            ->where('s.tipo_mantenimiento', 'correctivo')
            ->orderBy('s.orden')
            ->get();

        $autorizador = $mant->idusuario_autoriza
            ? DB::table('users')->where('id', $mant->idusuario_autoriza)->value('name')
            : null;

        $archivos = DB::table('flotilla_archivos')
            ->where('entidad_tipo', 'correctivo')
            ->where('entidad_id', $id)
            ->get();

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
            'idactivofijo' => 'required|integer',
            'diagnostico'  => 'required|string|max:500',
            'descripcion'  => 'required|string',
            'fecha_ingreso'=> 'required|date',
            'estatus'      => 'required|in:pendiente,en_proceso,finalizado,cancelado',
        ]);

        if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($request->idactivofijo)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de flotilla.'], 422);
        }

        $datos = [
            'idactivofijo'        => $request->idactivofijo,
            'idtaller'            => $request->idtaller,
            'idempleado_registra' => $request->idempleado_registra,
            'fecha_ingreso'       => $request->fecha_ingreso,
            'fecha_entrega'       => $request->fecha_entrega,
            'diagnostico'         => $request->diagnostico,
            'descripcion'         => $request->descripcion,
            'costo_mano_obra'     => $request->costo_mano_obra ?? 0,
            'costo_refacciones'   => 0,
            'costo_total'         => $request->costo_mano_obra ?? 0,
            'tiempo_fuera_horas'  => $request->tiempo_fuera_horas,
            'estatus'             => $request->estatus,
            'observaciones'       => $request->observaciones,
            'idusuario'           => $request->idusuario,
            'updated_at'          => now(),
        ];

        if ($id) {
            DB::table('flotilla_mantenimiento_correctivo')->where('id', $id)->update($datos);
        } else {
            $datos['folio']      = $this->flotillaService->generarFolioCorrectivo();
            $datos['created_at'] = now();
            $id = DB::table('flotilla_mantenimiento_correctivo')->insertGetId($datos);
        }

        // Guardar partidas
        if ($request->has('partidas')) {
            DB::table('flotilla_mantenimiento_partidas')
                ->where('tipo_mantenimiento', 'correctivo')
                ->where('idmantenimiento', $id)
                ->delete();

            foreach ($request->partidas as $partida) {
                $ctotal = ($partida['cantidad'] ?? 1) * ($partida['costo_unitario'] ?? 0);
                DB::table('flotilla_mantenimiento_partidas')->insert([
                    'tipo_mantenimiento'   => 'correctivo',
                    'idmantenimiento'      => $id,
                    'idcatalogo_refaccion' => $partida['idcatalogo_refaccion'] ?? null,
                    'descripcion'          => $partida['descripcion'],
                    'cantidad'             => $partida['cantidad'] ?? 1,
                    'unidad_medida'        => $partida['unidad_medida'] ?? 'pieza',
                    'costo_unitario'       => $partida['costo_unitario'] ?? 0,
                    'costo_total'          => $ctotal,
                    'numero_serie'         => $partida['numero_serie'] ?? null,
                    'posicion'             => $partida['posicion'] ?? null,
                    'vida_util_km'         => $partida['vida_util_km'] ?? null,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }

            $this->flotillaService->recalcularCostosCorrectivo($id);
        }

        // Guardar servicios (Detalle de Servicios)
        if ($request->has('servicios')) {
            DB::table('flotilla_mantenimiento_servicios')
                ->where('tipo_mantenimiento', 'correctivo')
                ->where('idmantenimiento', $id)
                ->delete();

            foreach ($request->servicios as $idx => $srv) {
                DB::table('flotilla_mantenimiento_servicios')->insert([
                    'idmantenimiento'      => $id,
                    'tipo_mantenimiento'   => 'correctivo',
                    'idtipo_servicio'      => $srv['idtipo_servicio'] ?? null,
                    'descripcion_servicio' => $srv['descripcion_servicio'] ?? null,
                    'importe'             => $srv['importe'] ?? 0,
                    'observaciones'       => $srv['observaciones'] ?? null,
                    'orden'               => $idx + 1,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        }

        // Actualizar mano de obra si cambió
        if (!$request->has('partidas')) {
            $this->flotillaService->recalcularCostosCorrectivo($id);
        }

        // Registrar bitácora
        $this->flotillaService->registrarBitacora([
            'idactivofijo' => $request->idactivofijo,
            'tipo_evento'  => 'mant_correctivo',
            'entidad_tipo' => 'flotilla_mantenimiento_correctivo',
            'entidad_id'   => $id,
            'descripcion'  => 'Mantenimiento correctivo: ' . $request->diagnostico,
            'idusuario'    => $request->idusuario,
            'fecha_evento' => $request->fecha_ingreso . ' 00:00:00',
        ]);

        return response()->json(['message' => 'Mantenimiento correctivo guardado', 'id' => $id]);
    }

    public function actualizarEstatus(Request $request, $id)
    {
        $request->validate(['estatus' => 'required|in:pendiente,en_proceso,finalizado,cancelado']);

        $datos = ['estatus' => $request->estatus, 'updated_at' => now()];
        if ($request->estatus === 'finalizado' && $request->fecha_entrega) {
            $datos['fecha_entrega'] = $request->fecha_entrega;
        }

        DB::table('flotilla_mantenimiento_correctivo')->where('id', $id)->update($datos);

        return response()->json(['message' => 'Estatus actualizado']);
    }

    public function eliminarMantenimiento($id)
    {
        $mant = DB::table('flotilla_mantenimiento_correctivo')->where('id', $id)->first();

        if (!$mant) return response()->json(['message' => 'No encontrado'], 404);

        if (($mant->estatus_autorizacion ?? 'pendiente') === 'autorizado') {
            return response()->json(['message' => 'No se puede eliminar un mantenimiento ya autorizado.'], 422);
        }

        DB::table('flotilla_mantenimiento_correctivo')->where('id', $id)->delete();
        DB::table('flotilla_mantenimiento_partidas')
            ->where('tipo_mantenimiento', 'correctivo')
            ->where('idmantenimiento', $id)
            ->delete();
        DB::table('flotilla_mantenimiento_servicios')
            ->where('tipo_mantenimiento', 'correctivo')
            ->where('idmantenimiento', $id)
            ->delete();

        return response()->json(['message' => 'Mantenimiento correctivo eliminado']);
    }

    /**
     * PUT /flotilla/mantenimiento/correctivo/{id}/autorizacion
     */
    public function autorizarMantenimiento(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) return response()->json(['message' => 'No autenticado'], 401);

        $tienePermiso = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where('permission_path', '/flotilla/mantenimientos:autorizar')
            ->exists();

        if (!$tienePermiso) {
            return response()->json(['message' => 'No tienes permiso para autorizar mantenimientos.'], 403);
        }

        $mant = DB::table('flotilla_mantenimiento_correctivo')->where('id', $id)->first();
        if (!$mant) return response()->json(['message' => 'No encontrado'], 404);

        if (($mant->estatus_autorizacion ?? 'pendiente') === 'autorizado') {
            return response()->json(['message' => 'Este mantenimiento ya fue autorizado.'], 422);
        }

        $request->validate([
            'accion'         => 'required|in:autorizar,rechazar',
            'motivo_rechazo' => 'required_if:accion,rechazar|nullable|string|max:500',
        ]);

        DB::table('flotilla_mantenimiento_correctivo')
            ->where('id', $id)
            ->update([
                'estatus_autorizacion' => $request->accion === 'autorizar' ? 'autorizado' : 'rechazado',
                'idusuario_autoriza'   => $user->id,
                'fecha_autorizacion'   => now(),
                'motivo_rechazo'       => $request->accion === 'rechazar' ? $request->motivo_rechazo : null,
                'updated_at'           => now(),
            ]);

        return response()->json([
            'message' => $request->accion === 'autorizar' ? 'Mantenimiento autorizado.' : 'Mantenimiento rechazado.',
        ]);
    }
}
