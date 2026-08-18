<?php

namespace App\Services\Flotilla;

use DB;
use Carbon\Carbon;

class AlertaService
{
    protected FlotillaService $flotillaService;

    public function __construct(FlotillaService $flotillaService)
    {
        $this->flotillaService = $flotillaService;
    }

    /**
     * Genera todas las alertas del día para mantenimiento y documentos.
     * Actualiza los niveles en sus tablas origen y crea/actualiza alertas.
     */
    public function generarAlertas(): array
    {
        $resultados = [
            'mantenimiento' => $this->generarAlertasMantenimiento(),
            'documentos'    => $this->generarAlertasDocumentos(),
        ];
        $resultados['total'] = $resultados['mantenimiento'] + $resultados['documentos'];

        return $resultados;
    }

    protected function generarAlertasMantenimiento(): int
    {
        $count    = 0;
        $schedules = DB::table('flotilla_unidad_mantenimiento')
            ->where('activo', 1)
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->where(function ($q) {
                $q->whereNotNull('proximo_km')
                  ->orWhereNotNull('proxima_fecha');
            })
            ->get();

        foreach ($schedules as $schedule) {
            $kmActual = $this->flotillaService->getKilometrajeActual($schedule->idactivofijo);
            $nivel    = $this->flotillaService->calcularNivelAlerta($schedule, $kmActual);

            // Actualizar semáforo en el schedule
            DB::table('flotilla_unidad_mantenimiento')
                ->where('id', $schedule->id)
                ->update(['estatus_alerta' => $nivel, 'updated_at' => now()]);

            if (in_array($nivel, ['amarillo', 'rojo'])) {
                $mensaje = $this->construirMensajeMantenimiento($schedule, $kmActual, $nivel);
                $this->upsertAlerta(
                    $schedule->idactivofijo,
                    'mantenimiento_preventivo',
                    'flotilla_unidad_mantenimiento',
                    $schedule->id,
                    $nivel,
                    $mensaje,
                    $count
                );
            }
        }

        return $count;
    }

    protected function generarAlertasDocumentos(): int
    {
        $count = 0;
        $hoy   = Carbon::today();

        $documentos = DB::table('flotilla_documentos_unidad')
            ->where('activo', 1)
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->whereNotNull('fecha_vencimiento')
            ->get();

        foreach ($documentos as $doc) {
            $fechaVenc      = Carbon::parse($doc->fecha_vencimiento);
            $diasRestantes  = $hoy->diffInDays($fechaVenc, false);
            $diasAlerta     = $doc->dias_alerta_amarillo ?? 30;

            $nivel = 'verde';
            if ($diasRestantes <= 0) {
                $nivel = 'rojo';
            } elseif ($diasRestantes <= $diasAlerta) {
                $nivel = 'amarillo';
            }

            DB::table('flotilla_documentos_unidad')
                ->where('id', $doc->id)
                ->update(['estatus_alerta' => $nivel, 'updated_at' => now()]);

            if (in_array($nivel, ['amarillo', 'rojo'])) {
                $tipoNombre = ucwords(str_replace('_', ' ', $doc->nombre_custom ?? $doc->tipo_documento));
                $mensaje = $nivel === 'rojo'
                    ? "{$tipoNombre}: VENCIDO el {$fechaVenc->format('d/m/Y')}"
                    : "{$tipoNombre}: vence el {$fechaVenc->format('d/m/Y')} ({$diasRestantes} días restantes)";

                $this->upsertAlerta(
                    $doc->idactivofijo,
                    'documento',
                    'flotilla_documentos_unidad',
                    $doc->id,
                    $nivel,
                    $mensaje,
                    $count
                );
            }
        }

        return $count;
    }

    protected function upsertAlerta(
        int $idActivoFijo,
        string $tipoAlerta,
        string $entidadTipo,
        int $entidadId,
        string $nivel,
        string $mensaje,
        int &$count
    ): void {
        $existente = DB::table('flotilla_alertas')
            ->where('idactivofijo',  $idActivoFijo)
            ->where('tipo_alerta',   $tipoAlerta)
            ->where('entidad_tipo',  $entidadTipo)
            ->where('entidad_id',    $entidadId)
            ->where('leida',         0)
            ->first();

        if ($existente) {
            DB::table('flotilla_alertas')
                ->where('id', $existente->id)
                ->update([
                    'nivel'            => $nivel,
                    'mensaje'          => $mensaje,
                    'fecha_generacion' => now(),
                    'updated_at'       => now(),
                ]);
        } else {
            DB::table('flotilla_alertas')->insert([
                'idactivofijo'     => $idActivoFijo,
                'tipo_alerta'      => $tipoAlerta,
                'entidad_tipo'     => $entidadTipo,
                'entidad_id'       => $entidadId,
                'nivel'            => $nivel,
                'mensaje'          => $mensaje,
                'leida'            => 0,
                'fecha_generacion' => now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
            $count++;
        }
    }

    protected function construirMensajeMantenimiento(object $schedule, float $kmActual, string $nivel): string
    {
        $partes = [];

        if ($schedule->proximo_km && in_array($schedule->tipo_control, ['km', 'ambos'])) {
            $kmRestante = (int) ($schedule->proximo_km - $kmActual);
            $partes[]   = $kmRestante <= 0
                ? 'VENCIDO por ' . abs($kmRestante) . ' km'
                : 'faltan ' . number_format($kmRestante) . ' km';
        }

        if ($schedule->proxima_fecha && in_array($schedule->tipo_control, ['tiempo', 'ambos'])) {
            $dias   = (int) Carbon::now()->diffInDays(Carbon::parse($schedule->proxima_fecha), false);
            $partes[] = $dias <= 0
                ? 'VENCIDO por ' . abs($dias) . ' días'
                : 'faltan ' . $dias . ' días';
        }

        return $schedule->nombre_servicio . ': ' . implode(', ', $partes);
    }
}
