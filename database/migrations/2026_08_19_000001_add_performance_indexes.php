<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Índices en columnas filtradas frecuentemente en los listados principales
return new class extends Migration
{
    private array $indexes = [
        // empleados
        ['table' => 'empleados', 'columns' => ['iddepartamento']],
        ['table' => 'empleados', 'columns' => ['idsucursal']],
        ['table' => 'empleados', 'columns' => ['estatus']],
        ['table' => 'empleados', 'columns' => ['numeroempleado']],

        // activos_fijos
        ['table' => 'activos_fijos', 'columns' => ['idtipoactivo']],
        ['table' => 'activos_fijos', 'columns' => ['idsucursal']],
        ['table' => 'activos_fijos', 'columns' => ['estatus']],

        // activos_fijos_asignacion
        ['table' => 'activos_fijos_asignacion', 'columns' => ['idactivofijo']],
        ['table' => 'activos_fijos_asignacion', 'columns' => ['idempleadoasignado']],
        ['table' => 'activos_fijos_asignacion', 'columns' => ['idsucursal']],
        ['table' => 'activos_fijos_asignacion', 'columns' => ['fechaasignacion']],
        ['table' => 'activos_fijos_asignacion', 'columns' => ['estadoasignacion']],

        // ordenes_servicio
        ['table' => 'ordenes_servicio', 'columns' => ['fechaingreso']],
        ['table' => 'ordenes_servicio', 'columns' => ['idunidad']],
        ['table' => 'ordenes_servicio', 'columns' => ['idtaller']],
        ['table' => 'ordenes_servicio', 'columns' => ['autorizacion_estatus']],
        ['table' => 'ordenes_servicio', 'columns' => ['created_at']],

        // flotilla_mantenimiento_preventivo
        ['table' => 'flotilla_mantenimiento_preventivo', 'columns' => ['idactivofijo']],
        ['table' => 'flotilla_mantenimiento_preventivo', 'columns' => ['idtaller']],
        ['table' => 'flotilla_mantenimiento_preventivo', 'columns' => ['estatus']],
        ['table' => 'flotilla_mantenimiento_preventivo', 'columns' => ['estatus_autorizacion']],
        ['table' => 'flotilla_mantenimiento_preventivo', 'columns' => ['fecha_servicio']],

        // flotilla_mantenimiento_correctivo
        ['table' => 'flotilla_mantenimiento_correctivo', 'columns' => ['idactivofijo']],
        ['table' => 'flotilla_mantenimiento_correctivo', 'columns' => ['idtaller']],
        ['table' => 'flotilla_mantenimiento_correctivo', 'columns' => ['estatus']],
        ['table' => 'flotilla_mantenimiento_correctivo', 'columns' => ['estatus_autorizacion']],
        ['table' => 'flotilla_mantenimiento_correctivo', 'columns' => ['fecha_ingreso']],

        // tickets_combustibles
        ['table' => 'tickets_combustibles', 'columns' => ['idvehiculo']],
        ['table' => 'tickets_combustibles', 'columns' => ['fechacarga']],

        // personal_access_tokens — acelera lookup del token
        ['table' => 'personal_access_tokens', 'columns' => ['tokenable_type', 'tokenable_id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $def) {
            if (!Schema::hasTable($def['table'])) continue;

            $indexName = $def['table'] . '_' . implode('_', $def['columns']) . '_idx';

            // Verificar si el índice ya existe para no fallar en re-ejecución
            $exists = collect(DB::select("SHOW INDEX FROM `{$def['table']}`"))
                ->pluck('Key_name')
                ->contains($indexName);

            if ($exists) continue;

            Schema::table($def['table'], function (Blueprint $table) use ($def, $indexName) {
                $table->index($def['columns'], $indexName);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $def) {
            if (!Schema::hasTable($def['table'])) continue;

            $indexName = $def['table'] . '_' . implode('_', $def['columns']) . '_idx';

            Schema::table($def['table'], function (Blueprint $table) use ($def, $indexName) {
                try {
                    $table->dropIndex($indexName);
                } catch (\Exception) {
                    // silenciar si el índice no existía
                }
            });
        }
    }
};
