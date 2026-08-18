<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * 2026-08-07: Add fecha_ingreso + estatus to flotilla_mantenimiento_preventivo
 * to mirror the corrective maintenance form (estatus combo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flotilla_mantenimiento_preventivo', function (Blueprint $table) {
            if (!Schema::hasColumn('flotilla_mantenimiento_preventivo', 'fecha_ingreso')) {
                $table->date('fecha_ingreso')->nullable()->after('idempleado_registra');
            }
            if (!Schema::hasColumn('flotilla_mantenimiento_preventivo', 'estatus')) {
                $table->enum('estatus', ['pendiente', 'en_proceso', 'finalizado', 'cancelado'])
                      ->default('pendiente')
                      ->after('fecha_ingreso');
            }
        });

        // Backfill existing rows: fecha_ingreso defaults to fecha_servicio
        DB::table('flotilla_mantenimiento_preventivo')
            ->whereNull('fecha_ingreso')
            ->update(['fecha_ingreso' => DB::raw('fecha_servicio')]);
    }

    public function down(): void
    {
        Schema::table('flotilla_mantenimiento_preventivo', function (Blueprint $table) {
            foreach (['fecha_ingreso', 'estatus'] as $col) {
                if (Schema::hasColumn('flotilla_mantenimiento_preventivo', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
