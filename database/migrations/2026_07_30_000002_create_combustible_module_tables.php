<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Presupuestos de combustible ──────────────────────────────────────
        Schema::create('comb_presupuestos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['unidad', 'sucursal', 'departamento', 'global'])->default('sucursal');
            $table->unsignedBigInteger('idactivofijo')->nullable();  // FK → activos_fijos
            $table->unsignedBigInteger('idsucursal')->nullable();
            $table->unsignedBigInteger('iddepartamento')->nullable();
            $table->enum('periodo_tipo', ['mensual', 'anual'])->default('mensual');
            $table->tinyInteger('periodo_mes')->nullable();           // 1-12
            $table->smallInteger('periodo_anio');
            $table->decimal('presupuesto_litros', 10, 3)->nullable();
            $table->decimal('presupuesto_importe', 12, 2);
            $table->tinyInteger('alerta_pct')->default(80);           // alerta al X% ejecutado
            $table->text('descripcion')->nullable();
            $table->tinyInteger('activo')->default(1);
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();

            $table->index(['tipo', 'periodo_anio', 'periodo_mes']);
        });

        // ─── Reglas de validación / alertas configurables ─────────────────────
        Schema::create('comb_reglas_validacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->enum('tipo', [
                'capacidad_tanque',
                'carga_doble',
                'km_regresion',
                'fuera_horario',
                'ticket_duplicado',
                'rendimiento_bajo',
                'incremento_excesivo',
                'custom',
            ])->default('custom');
            $table->enum('severidad', ['info', 'advertencia', 'critica'])->default('advertencia');
            $table->tinyInteger('activo')->default(1);
            $table->json('parametros')->nullable();  // configuración específica de la regla
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // ─── Alertas generadas por el módulo de combustible ───────────────────
        Schema::create('comb_alertas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idticket')->nullable();       // FK → tickets_combustibles
            $table->unsignedBigInteger('idactivofijo')->nullable();
            $table->unsignedBigInteger('idregla')->nullable();        // FK → comb_reglas_validacion
            $table->string('tipo_alerta', 80);
            $table->enum('nivel', ['info', 'advertencia', 'critica'])->default('advertencia');
            $table->string('mensaje', 500);
            $table->json('datos_extra')->nullable();
            $table->tinyInteger('leida')->default(0);
            $table->datetime('fecha_alerta');
            $table->timestamps();

            $table->index('idticket');
            $table->index('idactivofijo');
            $table->index(['nivel', 'leida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comb_alertas');
        Schema::dropIfExists('comb_reglas_validacion');
        Schema::dropIfExists('comb_presupuestos');
    }
};
