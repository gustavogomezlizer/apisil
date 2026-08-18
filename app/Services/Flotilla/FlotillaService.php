<?php

namespace App\Services\Flotilla;

use DB;
use Carbon\Carbon;

class FlotillaService
{
    /**
     * Obtiene el kilometraje actual de una unidad.
     * Toma el mayor entre el último registro de combustible y el último mantenimiento preventivo.
     */
    public function getKilometrajeActual(int $idActivoFijo): float
    {
        $kmCombustible = DB::table('tickets_combustibles')
            ->where('idvehiculo', $idActivoFijo)
            ->max('odometrocarga') ?? 0;

        $kmMantenimiento = DB::table('flotilla_mantenimiento_preventivo')
            ->where('idactivofijo', $idActivoFijo)
            ->max('km_servicio') ?? 0;

        // Incluir lecturas de kilometraje capturadas manualmente
        $kmLectura = DB::table('flotilla_lecturas_km')
            ->where('idactivofijo', $idActivoFijo)
            ->max('kilometraje') ?? 0;

        return (float) max($kmCombustible, $kmMantenimiento, $kmLectura);
    }

    /**
     * Calcula los valores del próximo servicio después de registrar un mantenimiento.
     * Retorna array con: ultimo_km, ultima_fecha, proximo_km, proxima_fecha
     */
    public function calcularProximoServicio(object $unidadMant, float $kmServicio, string $fechaServicio): array
    {
        $resultado = [
            'ultimo_km'    => $kmServicio,
            'ultima_fecha' => $fechaServicio,
            'proximo_km'   => null,
            'proxima_fecha'=> null,
        ];

        if ($unidadMant->frecuencia_km && in_array($unidadMant->tipo_control, ['km', 'ambos'])) {
            $resultado['proximo_km'] = $kmServicio + $unidadMant->frecuencia_km;
        }

        if ($unidadMant->frecuencia_dias && in_array($unidadMant->tipo_control, ['tiempo', 'ambos'])) {
            $resultado['proxima_fecha'] = Carbon::parse($fechaServicio)
                ->addDays($unidadMant->frecuencia_dias)
                ->format('Y-m-d');
        }

        return $resultado;
    }

    /**
     * Calcula el nivel de alerta para un servicio programado.
     * Respeta postergaciones: si hay km/fecha postergados, los usa como umbral.
     * Retorna: verde | amarillo | rojo
     */
    public function calcularNivelAlerta(object $unidadMant, float $kmActual): string
    {
        $esRojo     = false;
        $esAmarillo = false;

        // Si el servicio está postergado, usar los valores postergados como umbral
        $kmTarget    = $unidadMant->postergado_km    ?? $unidadMant->proximo_km;
        $fechaTarget = $unidadMant->postergado_fecha ?? $unidadMant->proxima_fecha;

        // Evaluar por KM
        if ($kmTarget && in_array($unidadMant->tipo_control, ['km', 'ambos'])) {
            $kmRestante = $kmTarget - $kmActual;
            if ($kmRestante <= 0) {
                $esRojo = true;
            } elseif ($kmRestante <= ($unidadMant->km_alerta_amarillo ?? 500)) {
                $esAmarillo = true;
            }
        }

        // Evaluar por fecha
        if ($fechaTarget && in_array($unidadMant->tipo_control, ['tiempo', 'ambos'])) {
            $diasRestantes = Carbon::now()->diffInDays(Carbon::parse($fechaTarget), false);
            if ($diasRestantes <= 0) {
                $esRojo = true;
            } elseif ($diasRestantes <= ($unidadMant->dias_alerta_amarillo ?? 15)) {
                $esAmarillo = true;
            }
        }

        if ($esRojo) return 'rojo';
        if ($esAmarillo) return 'amarillo';
        return 'verde';
    }

    /**
     * Genera el folio para mantenimiento preventivo: MNP-AÑO-NNNN
     */
    public function generarFolioPreventivo(): string
    {
        $anio = date('Y');
        $ultimo = DB::table('flotilla_mantenimiento_preventivo')
            ->whereYear('created_at', $anio)
            ->count();
        return 'MNP-' . $anio . '-' . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Genera el folio para mantenimiento correctivo: MNC-AÑO-NNNN
     */
    public function generarFolioCorrectivo(): string
    {
        $anio = date('Y');
        $ultimo = DB::table('flotilla_mantenimiento_correctivo')
            ->whereYear('created_at', $anio)
            ->count();
        return 'MNC-' . $anio . '-' . str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Registra un evento en la bitácora de flotilla.
     */
    public function registrarBitacora(array $datos): void
    {
        DB::table('flotilla_bitacora')->insert([
            'idactivofijo'  => $datos['idactivofijo'],
            'tipo_evento'   => $datos['tipo_evento'],
            'entidad_tipo'  => $datos['entidad_tipo'] ?? null,
            'entidad_id'    => $datos['entidad_id']   ?? null,
            'descripcion'   => $datos['descripcion'],
            'km_evento'     => $datos['km_evento']    ?? null,
            'idusuario'     => $datos['idusuario']    ?? null,
            'fecha_evento'  => $datos['fecha_evento'] ?? now(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Copia una plantilla específica a una unidad.
     * Requiere idPlantilla directamente.
     * Solo copia si no existe schedule previo (usa copiarPlantillaAUnidad para eso).
     * Retorna la cantidad de servicios copiados.
     */
    public function aplicarPlantillaAUnidad(int $idActivoFijo, int $idPlantilla): int
    {
        $plantilla = DB::table('flotilla_plantillas_mantenimiento')
            ->where('id', $idPlantilla)
            ->where('activo', 1)
            ->first();

        if (!$plantilla) return 0;

        $servicios = DB::table('flotilla_plantillas_servicio')
            ->where('idplantilla', $idPlantilla)
            ->where('activo', 1)
            ->orderBy('orden')
            ->get();

        $copiados = 0;
        foreach ($servicios as $servicio) {
            DB::table('flotilla_unidad_mantenimiento')->insert([
                'idactivofijo'         => $idActivoFijo,
                'idplantillaservicio'  => $servicio->id,
                'nombre_servicio'      => $servicio->nombre_servicio,
                'tipo_control'         => $servicio->tipo_control,
                'frecuencia_km'        => $servicio->frecuencia_km,
                'frecuencia_dias'      => $servicio->frecuencia_dias,
                'frecuencia_horas'     => $servicio->frecuencia_horas,
                'ultimo_km'            => 0,
                'ultima_fecha'         => null,
                'ultimas_horas'        => 0,
                'proximo_km'           => $servicio->frecuencia_km,
                'proxima_fecha'        => $servicio->frecuencia_dias
                    ? now()->addDays($servicio->frecuencia_dias)->format('Y-m-d')
                    : null,
                'estatus_alerta'       => 'verde',
                'km_alerta_amarillo'   => 500,
                'dias_alerta_amarillo' => 15,
                'activo'               => 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
            $copiados++;
        }

        return $copiados;
    }

    /**
     * Cambia la plantilla de una unidad ya inicializada.
     * Elimina todos los servicios derivados de plantilla (idplantillaservicio NOT NULL)
     * y aplica los de la nueva plantilla. Los servicios manuales se conservan.
     * Retorna la cantidad de servicios de la nueva plantilla aplicados.
     */
    public function cambiarPlantillaUnidad(int $idActivoFijo, int $idNuevaPlantilla): int
    {
        $nuevaPlantilla = DB::table('flotilla_plantillas_mantenimiento')
            ->where('id', $idNuevaPlantilla)
            ->where('activo', 1)
            ->first();

        if (!$nuevaPlantilla) return 0;

        // Eliminar todos los servicios que vienen de una plantilla (no manuales)
        DB::table('flotilla_unidad_mantenimiento')
            ->where('idactivofijo', $idActivoFijo)
            ->whereNotNull('idplantillaservicio')
            ->delete();

        // Aplicar los servicios de la nueva plantilla
        return $this->aplicarPlantillaAUnidad($idActivoFijo, $idNuevaPlantilla);
    }

    /**
     * Obtiene las plantillas disponibles para un tipo de activo.
     * Incluye la plantilla actualmente asignada a una unidad (si se proporciona).
     */
    public function getPlantillasParaTipo(int $idTipoActivo, ?int $idActivoFijo = null): array
    {
        $plantillas = DB::table('flotilla_plantillas_mantenimiento as pm')
            ->leftJoin('flotilla_plantillas_servicio as ps', function ($j) {
                $j->on('ps.idplantilla', '=', 'pm.id')->where('ps.activo', 1);
            })
            ->select('pm.id', 'pm.nombre', 'pm.descripcion', DB::raw('COUNT(ps.id) as total_servicios'))
            ->where('pm.idtipoactivo', $idTipoActivo)
            ->where('pm.activo', 1)
            ->groupBy('pm.id', 'pm.nombre', 'pm.descripcion')
            ->orderBy('pm.nombre')
            ->get()
            ->toArray();

        // Marcar cuál es la plantilla actual de la unidad (si aplica)
        $plantillaActualId = null;
        if ($idActivoFijo) {
            $plantillaActualId = DB::table('flotilla_unidad_mantenimiento as um')
                ->join('flotilla_plantillas_servicio as ps', 'um.idplantillaservicio', '=', 'ps.id')
                ->where('um.idactivofijo', $idActivoFijo)
                ->where('um.activo', 1)
                ->whereNotNull('um.idplantillaservicio')
                ->value('ps.idplantilla');
        }

        foreach ($plantillas as &$p) {
            $p->es_actual = ($p->id === $plantillaActualId);
        }

        return $plantillas;
    }

    /**
     * Copia la plantilla de un tipo de activo a una unidad específica.
     * Solo copia si no existe schedule previo para esa unidad.
     * Retorna la cantidad de servicios copiados (0 = sin plantilla o ya existía).
     * @deprecated Usar aplicarPlantillaAUnidad() con idPlantilla explícito.
     */
    public function copiarPlantillaAUnidad(int $idActivoFijo, int $idTipoActivo): int
    {
        $plantilla = DB::table('flotilla_plantillas_mantenimiento')
            ->where('idtipoactivo', $idTipoActivo)
            ->where('activo', 1)
            ->first();

        if (!$plantilla) return 0;

        $yaExiste = DB::table('flotilla_unidad_mantenimiento')
            ->where('idactivofijo', $idActivoFijo)
            ->exists();

        if ($yaExiste) return 0;

        return $this->aplicarPlantillaAUnidad($idActivoFijo, $plantilla->id);
    }

    /**
     * Propaga un nuevo servicio de plantilla a todas las unidades que ya tienen esa plantilla asignada.
     * Se llama automáticamente cuando se agrega un servicio a una plantilla existente.
     * Retorna cuántas unidades se actualizaron.
     */
    public function propagarServicioAUnidades(int $idPlantilla, int $idNuevoServicio): int
    {
        $servicio = DB::table('flotilla_plantillas_servicio')->where('id', $idNuevoServicio)->first();
        if (!$servicio) return 0;

        // Obtener las unidades que ya tienen al menos un servicio de esta plantilla
        $unidades = DB::table('flotilla_unidad_mantenimiento')
            ->whereIn('idplantillaservicio', function ($q) use ($idPlantilla) {
                $q->select('id')
                  ->from('flotilla_plantillas_servicio')
                  ->where('idplantilla', $idPlantilla);
            })
            ->distinct()
            ->pluck('idactivofijo');

        $propagados = 0;
        foreach ($unidades as $idActivoFijo) {
            // No duplicar si la unidad ya tiene este servicio
            $existe = DB::table('flotilla_unidad_mantenimiento')
                ->where('idactivofijo', $idActivoFijo)
                ->where('idplantillaservicio', $idNuevoServicio)
                ->exists();

            if (!$existe) {
                DB::table('flotilla_unidad_mantenimiento')->insert([
                    'idactivofijo'         => $idActivoFijo,
                    'idplantillaservicio'  => $idNuevoServicio,
                    'nombre_servicio'      => $servicio->nombre_servicio,
                    'tipo_control'         => $servicio->tipo_control,
                    'frecuencia_km'        => $servicio->frecuencia_km,
                    'frecuencia_dias'      => $servicio->frecuencia_dias,
                    'frecuencia_horas'     => $servicio->frecuencia_horas,
                    'ultimo_km'            => 0,
                    'ultima_fecha'         => null,
                    'ultimas_horas'        => 0,
                    'proximo_km'           => $servicio->frecuencia_km,
                    'proxima_fecha'        => $servicio->frecuencia_dias
                        ? now()->addDays($servicio->frecuencia_dias)->format('Y-m-d')
                        : null,
                    'estatus_alerta'       => 'verde',
                    'km_alerta_amarillo'   => 500,
                    'dias_alerta_amarillo' => 15,
                    'activo'               => 1,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
                $propagados++;
            }
        }

        return $propagados;
    }

    /**
     * Recalcula y actualiza los costos totales de un mantenimiento preventivo.
     */
    public function recalcularCostosPreventivo(int $idMantenimiento): void
    {
        $costoRefacciones = DB::table('flotilla_mantenimiento_partidas')
            ->where('tipo_mantenimiento', 'preventivo')
            ->where('idmantenimiento', $idMantenimiento)
            ->sum('costo_total');

        $costoServicios = DB::table('flotilla_mantenimiento_servicios')
            ->where('tipo_mantenimiento', 'preventivo')
            ->where('idmantenimiento', $idMantenimiento)
            ->sum('importe');

        $manoObra = DB::table('flotilla_mantenimiento_preventivo')
            ->where('id', $idMantenimiento)
            ->value('costo_mano_obra') ?? 0;

        DB::table('flotilla_mantenimiento_preventivo')
            ->where('id', $idMantenimiento)
            ->update([
                'costo_refacciones' => $costoRefacciones,
                'costo_total'       => $manoObra + $costoRefacciones + $costoServicios,
                'updated_at'        => now(),
            ]);
    }

    /**
     * Recalcula y actualiza los costos totales de un mantenimiento correctivo.
     */
    public function recalcularCostosCorrectivo(int $idMantenimiento): void
    {
        $costoRefacciones = DB::table('flotilla_mantenimiento_partidas')
            ->where('tipo_mantenimiento', 'correctivo')
            ->where('idmantenimiento', $idMantenimiento)
            ->sum('costo_total');

        $costoServicios = DB::table('flotilla_mantenimiento_servicios')
            ->where('tipo_mantenimiento', 'correctivo')
            ->where('idmantenimiento', $idMantenimiento)
            ->sum('importe');

        $manoObra = DB::table('flotilla_mantenimiento_correctivo')
            ->where('id', $idMantenimiento)
            ->value('costo_mano_obra') ?? 0;

        DB::table('flotilla_mantenimiento_correctivo')
            ->where('id', $idMantenimiento)
            ->update([
                'costo_refacciones' => $costoRefacciones,
                'costo_total'       => $manoObra + $costoRefacciones + $costoServicios,
                'updated_at'        => now(),
            ]);
    }
}
