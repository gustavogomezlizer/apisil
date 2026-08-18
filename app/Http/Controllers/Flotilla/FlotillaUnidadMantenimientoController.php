<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Flotilla\FlotillaService;

class FlotillaUnidadMantenimientoController extends Controller
{
    protected FlotillaService $flotillaService;

    public function __construct(FlotillaService $flotillaService)
    {
        $this->flotillaService = $flotillaService;
    }

    /**
     * Estado de todas las unidades activas: schedule inicializado,
     * cuántos servicios, alertas, plantilla actualmente asignada, etc.
     */
    public function getEstadoUnidades(Request $request)
    {
        $query = DB::table('activos_fijos as af')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('cat_tipos_activos_fijos as taf', 'af.idtipoactivo', '=', 'taf.id')
            ->leftJoin('flotilla_unidad_mantenimiento as um', function ($join) {
                $join->on('af.id', '=', 'um.idactivofijo')->where('um.activo', 1);
            })
            ->select(
                'af.id',
                'af.descripcion as unidad',
                'af.idtipoactivo',
                'taf.nombre as tipo_unidad',
                'afu.placas',
                'afu.numeroeconomico',
                DB::raw('COUNT(DISTINCT um.id) as total_servicios'),
                DB::raw('SUM(CASE WHEN um.estatus_alerta = "rojo"     THEN 1 ELSE 0 END) as alertas_rojo'),
                DB::raw('SUM(CASE WHEN um.estatus_alerta = "amarillo" THEN 1 ELSE 0 END) as alertas_amarillo'),
                // Plantilla actualmente asignada (detectada desde los idplantillaservicio)
                DB::raw('(
                    SELECT pm.id
                    FROM flotilla_unidad_mantenimiento um2
                    JOIN flotilla_plantillas_servicio ps2 ON um2.idplantillaservicio = ps2.id
                    JOIN flotilla_plantillas_mantenimiento pm ON ps2.idplantilla = pm.id
                    WHERE um2.idactivofijo = af.id AND um2.activo = 1
                    LIMIT 1
                ) as plantilla_actual_id'),
                DB::raw('(
                    SELECT pm.nombre
                    FROM flotilla_unidad_mantenimiento um2
                    JOIN flotilla_plantillas_servicio ps2 ON um2.idplantillaservicio = ps2.id
                    JOIN flotilla_plantillas_mantenimiento pm ON ps2.idplantilla = pm.id
                    WHERE um2.idactivofijo = af.id AND um2.activo = 1
                    LIMIT 1
                ) as plantilla_actual_nombre'),
                // ¿Hay plantillas disponibles para el tipo?
                DB::raw('(
                    SELECT COUNT(*) FROM flotilla_plantillas_mantenimiento
                    WHERE idtipoactivo = af.idtipoactivo AND activo = 1
                ) as total_plantillas_disponibles')
            )
            ->where('af.estatus', 'Activo')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->groupBy('af.id', 'af.descripcion', 'af.idtipoactivo', 'taf.nombre', 'afu.placas', 'afu.numeroeconomico');

        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('af.descripcion', 'like', $s)
                    ->orWhere('afu.placas', 'like', $s)
                    ->orWhere('taf.nombre', 'like', $s);
            });
        });

        $query->when($request->estado, function ($q) use ($request) {
            if ($request->estado === 'inicializado') {
                $q->havingRaw('COUNT(DISTINCT um.id) > 0');
            } elseif ($request->estado === 'sin_inicializar') {
                $q->havingRaw('COUNT(DISTINCT um.id) = 0');
            }
        });

        $query->orderBy('taf.nombre')->orderBy('af.descripcion');

        return $query->paginate($request->per_page ?? 20);
    }

    /**
     * Resumen general de todas las unidades con su estado de alertas.
     */
    public function getResumenFlotilla(Request $request)
    {
        $query = DB::table('flotilla_unidad_mantenimiento as um')
            ->join('activos_fijos as af', 'um.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->select(
                'af.id as idactivofijo',
                'af.descripcion as unidad',
                'afu.placas',
                'afu.numeroeconomico',
                DB::raw('COUNT(um.id) as total_servicios'),
                DB::raw('SUM(CASE WHEN um.estatus_alerta = "rojo"     THEN 1 ELSE 0 END) as alertas_rojo'),
                DB::raw('SUM(CASE WHEN um.estatus_alerta = "amarillo" THEN 1 ELSE 0 END) as alertas_amarillo'),
                DB::raw('SUM(CASE WHEN um.estatus_alerta = "verde"    THEN 1 ELSE 0 END) as alertas_verde')
            )
            ->where('um.activo', 1)
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->groupBy('af.id', 'af.descripcion', 'afu.placas', 'afu.numeroeconomico');

        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('af.descripcion', 'like', $s)
                    ->orWhere('afu.placas', 'like', $s)
                    ->orWhere('afu.numeroeconomico', 'like', $s);
            });
        });

        $query->when($request->alerta, function ($q) use ($request) {
            $alerta = $request->alerta;
            $q->havingRaw('SUM(CASE WHEN um.estatus_alerta = ? THEN 1 ELSE 0 END) > 0', [$alerta]);
        });

        $query->orderBy('alertas_rojo', 'desc')->orderBy('alertas_amarillo', 'desc');

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * Obtiene el schedule completo de mantenimiento de una unidad con km actual.
     */
    public function getScheduleUnidad(Request $request, $idActivoFijo)
    {
        $kmActual = $this->flotillaService->getKilometrajeActual((int) $idActivoFijo);

        $servicios = DB::table('flotilla_unidad_mantenimiento as um')
            ->select('um.*')
            ->where('um.idactivofijo', $idActivoFijo)
            ->where('um.activo', 1)
            ->orderByRaw("FIELD(um.estatus_alerta, 'rojo', 'amarillo', 'verde')")
            ->orderBy('um.nombre_servicio')
            ->get()
            ->map(function ($item) use ($kmActual) {
                $item->km_actual   = $kmActual;
                $item->km_restante = $item->proximo_km ? round($item->proximo_km - $kmActual) : null;
                // Mostrar si está postergado
                $item->esta_postergado = !is_null($item->postergado_km) || !is_null($item->postergado_fecha);
                return $item;
            });

        return response()->json([
            'km_actual' => $kmActual,
            'servicios' => $servicios,
        ]);
    }

    /**
     * Devuelve las plantillas disponibles para el tipo de activo de una unidad,
     * marcando cuál está actualmente asignada.
     */
    public function getPlantillasDisponibles(Request $request, $idActivoFijo)
    {
        $unidad = DB::table('activos_fijos')->where('id', $idActivoFijo)->first();
        if (! $unidad) {
            return response()->json(['message' => 'Unidad no encontrada.'], 404);
        }

        $plantillas = $this->flotillaService->getPlantillasParaTipo(
            (int) $unidad->idtipoactivo,
            (int) $idActivoFijo
        );

        return response()->json([
            'idtipoactivo' => $unidad->idtipoactivo,
            'tipo_unidad'  => DB::table('cat_tipos_activos_fijos')->where('id', $unidad->idtipoactivo)->value('nombre'),
            'plantillas'   => $plantillas,
        ]);
    }

    /**
     * Inicializa el schedule de mantenimiento de una unidad con una plantilla específica.
     * Falla si la unidad ya tiene servicios de plantilla (usar cambiarPlantilla para eso).
     */
    public function inicializarMantenimiento(Request $request, $idActivoFijo)
    {
        $request->validate([
            'idplantilla' => 'required|integer',
        ]);

        if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($idActivoFijo)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de flotilla.'], 422);
        }

        // Verificar que la unidad no tenga ya servicios de plantilla
        $yaInicializado = DB::table('flotilla_unidad_mantenimiento')
            ->where('idactivofijo', $idActivoFijo)
            ->whereNotNull('idplantillaservicio')
            ->exists();

        if ($yaInicializado) {
            return response()->json([
                'message'  => 'Esta unidad ya tiene un schedule de plantilla inicializado.',
                'tip'      => 'Usa "Cambiar plantilla" para reemplazarlo.',
                'ya_tiene' => true,
            ], 422);
        }

        $copiados = $this->flotillaService->aplicarPlantillaAUnidad(
            (int) $idActivoFijo,
            (int) $request->idplantilla
        );

        if ($copiados === 0) {
            return response()->json([
                'message' => 'La plantilla no tiene servicios activos o no fue encontrada.',
            ], 422);
        }

        $plantilla = DB::table('flotilla_plantillas_mantenimiento')->where('id', $request->idplantilla)->first();

        return response()->json([
            'message'       => "Se inicializaron {$copiados} servicios desde la plantilla \"{$plantilla->nombre}\".",
            'copiados'      => $copiados,
            'plantilla'     => $plantilla->nombre,
            'idplantilla'   => $plantilla->id,
        ]);
    }

    /**
     * Cambia la plantilla de una unidad ya inicializada.
     * Elimina los servicios de plantilla anteriores y aplica la nueva.
     * Los servicios manuales (idplantillaservicio IS NULL) se conservan.
     */
    public function cambiarPlantilla(Request $request, $idActivoFijo)
    {
        $request->validate([
            'idplantilla' => 'required|integer',
        ]);

        if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($idActivoFijo)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de flotilla.'], 422);
        }

        $plantilla = DB::table('flotilla_plantillas_mantenimiento')
            ->where('id', $request->idplantilla)
            ->where('activo', 1)
            ->first();

        if (! $plantilla) {
            return response()->json(['message' => 'Plantilla no encontrada.'], 404);
        }

        $serviciosAplicados = $this->flotillaService->cambiarPlantillaUnidad(
            (int) $idActivoFijo,
            (int) $request->idplantilla
        );

        // Bitácora
        $unidad = DB::table('activos_fijos')->where('id', $idActivoFijo)->value('descripcion');
        $this->flotillaService->registrarBitacora([
            'idactivofijo' => $idActivoFijo,
            'tipo_evento'  => 'cambio_plantilla',
            'entidad_tipo' => 'flotilla_plantillas_mantenimiento',
            'entidad_id'   => $request->idplantilla,
            'descripcion'  => "Cambio de plantilla de mantenimiento a \"{$plantilla->nombre}\" ({$serviciosAplicados} servicios)",
            'idusuario'    => $request->idusuario ?? null,
            'fecha_evento' => now(),
        ]);

        return response()->json([
            'message'     => "Plantilla cambiada a \"{$plantilla->nombre}\". Se aplicaron {$serviciosAplicados} servicios.",
            'copiados'    => $serviciosAplicados,
            'plantilla'   => $plantilla->nombre,
            'idplantilla' => $plantilla->id,
        ]);
    }

    /**
     * Inicializa el schedule de múltiples unidades en lote con la misma plantilla.
     * Requiere idplantilla.
     */
    public function inicializarLote(Request $request)
    {
        $request->validate([
            'unidades'    => 'required|array|min:1',
            'unidades.*'  => 'integer',
            'idplantilla' => 'required|integer',
        ]);

        $plantilla = DB::table('flotilla_plantillas_mantenimiento')
            ->where('id', $request->idplantilla)->where('activo', 1)->first();

        if (! $plantilla) {
            return response()->json(['message' => 'Plantilla no encontrada.'], 404);
        }

        $resultados = [];
        foreach ($request->unidades as $idActivoFijo) {
            $unidad = DB::table('activos_fijos')->where('id', $idActivoFijo)->first();
            if (! $unidad) {
                $resultados[] = ['id' => $idActivoFijo, 'status' => 'error', 'msg' => 'Unidad no encontrada'];
                continue;
            }

            if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($unidad->idtipoactivo)) {
                $resultados[] = ['id' => $idActivoFijo, 'status' => 'error', 'msg' => 'No es un activo de tipo Unidad'];
                continue;
            }

            // Si ya tiene servicios de plantilla, omitir (usar cambiarPlantilla para eso)
            $yaInicializado = DB::table('flotilla_unidad_mantenimiento')
                ->where('idactivofijo', $idActivoFijo)
                ->whereNotNull('idplantillaservicio')
                ->exists();

            if ($yaInicializado) {
                $resultados[] = [
                    'id' => $idActivoFijo, 'unidad' => $unidad->descripcion,
                    'status' => 'omitido', 'servicios' => 0,
                    'msg' => 'Ya inicializada. Usa "Cambiar plantilla" para reemplazarla.',
                ];
                continue;
            }

            $copiados = $this->flotillaService->aplicarPlantillaAUnidad(
                (int) $idActivoFijo,
                (int) $request->idplantilla
            );

            $resultados[] = [
                'id'        => $idActivoFijo,
                'unidad'    => $unidad->descripcion,
                'status'    => $copiados > 0 ? 'ok' : 'omitido',
                'servicios' => $copiados,
                'msg'       => $copiados > 0
                    ? "{$copiados} servicios inicializados desde \"{$plantilla->nombre}\""
                    : 'La plantilla no tiene servicios',
            ];
        }

        $ok      = collect($resultados)->where('status', 'ok')->count();
        $omitido = collect($resultados)->where('status', 'omitido')->count();

        return response()->json([
            'message'    => "{$ok} unidad(es) inicializadas, {$omitido} omitida(s).",
            'resultados' => $resultados,
        ]);
    }

    /**
     * Agrega un servicio manual al schedule de la unidad.
     */
    public function agregarServicioManual(Request $request, $idActivoFijo)
    {
        $request->validate([
            'nombre_servicio' => 'required|string|max:200',
            'tipo_control'    => 'required|in:km,tiempo,ambos,horas',
        ]);

        if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($idActivoFijo)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de flotilla.'], 422);
        }

        $id = DB::table('flotilla_unidad_mantenimiento')->insertGetId([
            'idactivofijo'         => $idActivoFijo,
            'idplantillaservicio'  => null,
            'nombre_servicio'      => $request->nombre_servicio,
            'tipo_control'         => $request->tipo_control,
            'frecuencia_km'        => $request->frecuencia_km,
            'frecuencia_dias'      => $request->frecuencia_dias,
            'frecuencia_horas'     => $request->frecuencia_horas,
            'ultimo_km'            => 0,
            'ultima_fecha'         => null,
            'ultimas_horas'        => 0,
            'proximo_km'           => $request->proximo_km,
            'proxima_fecha'        => $request->proxima_fecha,
            'estatus_alerta'       => 'verde',
            'km_alerta_amarillo'   => $request->km_alerta_amarillo   ?? 500,
            'dias_alerta_amarillo' => $request->dias_alerta_amarillo ?? 15,
            'activo'               => 1,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return response()->json(['message' => 'Servicio agregado al schedule', 'id' => $id], 201);
    }

    /**
     * Actualiza los parámetros de un servicio del schedule.
     */
    public function actualizarServicio(Request $request, $idActivoFijo, $id)
    {
        $request->validate([
            'nombre_servicio' => 'required|string|max:200',
            'tipo_control'    => 'required|in:km,tiempo,ambos,horas',
        ]);

        $row = DB::table('flotilla_unidad_mantenimiento')
            ->where('id', $id)->where('idactivofijo', $idActivoFijo)->first();

        if (! $row) {
            return response()->json(['message' => 'Servicio no encontrado para esta unidad.'], 404);
        }

        DB::table('flotilla_unidad_mantenimiento')->where('id', $id)->update([
            'nombre_servicio'      => $request->nombre_servicio,
            'tipo_control'         => $request->tipo_control,
            'frecuencia_km'        => $request->frecuencia_km,
            'frecuencia_dias'      => $request->frecuencia_dias,
            'frecuencia_horas'     => $request->frecuencia_horas,
            'km_alerta_amarillo'   => $request->km_alerta_amarillo   ?? $row->km_alerta_amarillo,
            'dias_alerta_amarillo' => $request->dias_alerta_amarillo ?? $row->dias_alerta_amarillo,
            'updated_at'           => now(),
        ]);

        return response()->json(['message' => 'Servicio actualizado correctamente.']);
    }

    /**
     * Elimina (inactiva) un servicio del schedule.
     */
    public function eliminarServicio($idActivoFijo, $id)
    {
        $affected = DB::table('flotilla_unidad_mantenimiento')
            ->where('id', $id)->where('idactivofijo', $idActivoFijo)
            ->update(['activo' => 0, 'updated_at' => now()]);

        if (! $affected) {
            return response()->json(['message' => 'Servicio no encontrado para esta unidad.'], 404);
        }

        return response()->json(['message' => 'Servicio eliminado del schedule.']);
    }

    /**
     * Pospone un mantenimiento vencido o próximo a vencer.
     * El nuevo umbral (km o fecha) se calcula automáticamente o puede venir en el request.
     */
    public function posponerMantenimiento(Request $request, $idActivoFijo, $id)
    {
        $request->validate([
            'motivo' => 'required|string|max:300',
        ]);

        if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($idActivoFijo)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de flotilla.'], 422);
        }

        $row = DB::table('flotilla_unidad_mantenimiento')
            ->where('id', $id)->where('idactivofijo', $idActivoFijo)->first();

        if (! $row) {
            return response()->json(['message' => 'Servicio no encontrado para esta unidad.'], 404);
        }

        $kmActual = $this->flotillaService->getKilometrajeActual((int) $idActivoFijo);

        // Nuevo km: manual o calcula 50% de la frecuencia
        $postergadoKm = null;
        if ($request->km_nuevo) {
            $postergadoKm = (float) $request->km_nuevo;
        } elseif ($row->frecuencia_km && in_array($row->tipo_control, ['km', 'ambos'])) {
            $postergadoKm = $kmActual + round($row->frecuencia_km * 0.5);
        }

        // Nueva fecha: manual o calcula 50% de la frecuencia
        $postergadoFecha = null;
        if ($request->fecha_nueva) {
            $postergadoFecha = $request->fecha_nueva;
        } elseif ($row->frecuencia_dias && in_array($row->tipo_control, ['tiempo', 'ambos'])) {
            $postergadoFecha = Carbon::now()->addDays(round($row->frecuencia_dias * 0.5))->format('Y-m-d');
        }

        DB::table('flotilla_unidad_mantenimiento')->where('id', $id)->update([
            'postergado_km'    => $postergadoKm,
            'postergado_fecha' => $postergadoFecha,
            'postergado_motivo'=> $request->motivo,
            'postergado_en'    => now(),
            'estatus_alerta'   => 'amarillo',
            'updated_at'       => now(),
        ]);

        // Marcar alerta activa como leída
        DB::table('flotilla_alertas')
            ->where('entidad_tipo', 'flotilla_unidad_mantenimiento')
            ->where('entidad_id', $id)
            ->where('leida', 0)
            ->update(['leida' => 1, 'fecha_lectura' => now(), 'updated_at' => now()]);

        // Bitácora
        $desc = "Mantenimiento pospuesto: {$row->nombre_servicio}. Motivo: {$request->motivo}";
        if ($postergadoKm)    $desc .= " | Nuevo km: " . number_format($postergadoKm);
        if ($postergadoFecha) $desc .= " | Nueva fecha: {$postergadoFecha}";

        $this->flotillaService->registrarBitacora([
            'idactivofijo' => $idActivoFijo,
            'tipo_evento'  => 'postergacion',
            'entidad_tipo' => 'flotilla_unidad_mantenimiento',
            'entidad_id'   => $id,
            'descripcion'  => $desc,
            'km_evento'    => $kmActual,
            'idusuario'    => $request->idusuario ?? null,
            'fecha_evento' => now(),
        ]);

        return response()->json([
            'message'          => 'Mantenimiento pospuesto correctamente.',
            'postergado_km'    => $postergadoKm,
            'postergado_fecha' => $postergadoFecha,
        ]);
    }
}
