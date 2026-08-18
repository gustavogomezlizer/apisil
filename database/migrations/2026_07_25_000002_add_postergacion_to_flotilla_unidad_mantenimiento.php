<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flotilla_unidad_mantenimiento', function (Blueprint $table) {
            // Campos para postergación de mantenimiento
            $table->decimal('postergado_km', 12, 2)->nullable()->after('estatus_alerta')
                ->comment('Km nuevo target cuando se pospone. Anula proximo_km temporalmente.');
            $table->date('postergado_fecha')->nullable()->after('postergado_km')
                ->comment('Fecha nueva target cuando se pospone. Anula proxima_fecha temporalmente.');
            $table->string('postergado_motivo', 300)->nullable()->after('postergado_fecha');
            $table->datetime('postergado_en')->nullable()->after('postergado_motivo');
        });
    }

    public function down(): void
    {
        Schema::table('flotilla_unidad_mantenimiento', function (Blueprint $table) {
            $table->dropColumn(['postergado_km', 'postergado_fecha', 'postergado_motivo', 'postergado_en']);
        });
    }
};
