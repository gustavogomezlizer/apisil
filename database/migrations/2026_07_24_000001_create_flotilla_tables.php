<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. CATÁLOGO DE REFACCIONES / PARTES ─────────────────────────────
        Schema::create('flotilla_cat_refacciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->string('descripcion', 300)->nullable();
            $table->string('categoria', 80)->nullable(); // neumaticos, electrico, filtros, frenos, aceites, etc.
            $table->string('unidad_medida', 30)->default('pieza');
            $table->decimal('costo_promedio', 12, 2)->nullable();
            $table->tinyInteger('activo')->default(1);
            $table->timestamps();
        });

        // ─── 2. PLANTILLAS DE MANTENIMIENTO (por tipo de unidad) ─────────────
        Schema::create('flotilla_plantillas_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idtipoactivo'); // FK -> cat_tipos_activos_fijos.id
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->tinyInteger('activo')->default(1);
            $table->timestamps();

            $table->index('idtipoactivo');
        });

        // ─── 3. SERVICIOS DENTRO DE UNA PLANTILLA ────────────────────────────
        Schema::create('flotilla_plantillas_servicio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idplantilla');
            $table->unsignedBigInteger('idtiposervicio')->nullable(); // FK -> cat_tipos_servicio.id
            $table->string('nombre_servicio', 200);
            $table->enum('tipo_control', ['km', 'tiempo', 'ambos', 'horas'])->default('ambos');
            $table->unsignedInteger('frecuencia_km')->nullable();
            $table->unsignedInteger('frecuencia_dias')->nullable();
            $table->unsignedInteger('frecuencia_horas')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->tinyInteger('activo')->default(1);
            $table->timestamps();

            $table->foreign('idplantilla')
                ->references('id')->on('flotilla_plantillas_mantenimiento')
                ->onDelete('cascade');
            $table->index('idplantilla');
        });

        // ─── 4. SCHEDULE DE MANTENIMIENTO POR UNIDAD ─────────────────────────
        // Copia de la plantilla asignada a cada unidad; personalizable por unidad
        Schema::create('flotilla_unidad_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idactivofijo');    // FK -> activos_fijos.id
            $table->unsignedBigInteger('idplantillaservicio')->nullable(); // origen (plantilla)
            $table->string('nombre_servicio', 200);
            $table->enum('tipo_control', ['km', 'tiempo', 'ambos', 'horas'])->default('ambos');
            $table->unsignedInteger('frecuencia_km')->nullable();
            $table->unsignedInteger('frecuencia_dias')->nullable();
            $table->unsignedInteger('frecuencia_horas')->nullable();
            $table->decimal('ultimo_km', 12, 2)->default(0);
            $table->date('ultima_fecha')->nullable();
            $table->decimal('ultimas_horas', 10, 2)->default(0);
            $table->decimal('proximo_km', 12, 2)->nullable();
            $table->date('proxima_fecha')->nullable();
            $table->enum('estatus_alerta', ['verde', 'amarillo', 'rojo'])->default('verde');
            $table->unsignedInteger('km_alerta_amarillo')->default(500);
            $table->unsignedInteger('dias_alerta_amarillo')->default(15);
            $table->tinyInteger('activo')->default(1);
            $table->timestamps();

            $table->index('idactivofijo');
            $table->index('estatus_alerta');
        });

        // ─── 5. MANTENIMIENTO PREVENTIVO EJECUTADO ───────────────────────────
        Schema::create('flotilla_mantenimiento_preventivo', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->nullable();
            $table->unsignedBigInteger('idactivofijo');
            $table->unsignedBigInteger('idunidad_mantenimiento'); // FK -> flotilla_unidad_mantenimiento
            $table->unsignedBigInteger('idtaller')->nullable();   // FK -> talleres.id
            $table->unsignedBigInteger('idempleado_registra')->nullable(); // FK -> empleados.id
            $table->date('fecha_servicio');
            $table->decimal('km_servicio', 12, 2)->default(0);
            $table->decimal('horas_servicio', 10, 2)->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('costo_mano_obra', 12, 2)->default(0);
            $table->decimal('costo_refacciones', 12, 2)->default(0);
            $table->decimal('costo_total', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();

            $table->foreign('idunidad_mantenimiento')
                ->references('id')->on('flotilla_unidad_mantenimiento')
                ->onDelete('restrict');
            $table->index('idactivofijo');
            $table->index('fecha_servicio');
        });

        // ─── 6. MANTENIMIENTO CORRECTIVO ─────────────────────────────────────
        Schema::create('flotilla_mantenimiento_correctivo', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->nullable();
            $table->unsignedBigInteger('idactivofijo');
            $table->unsignedBigInteger('idtaller')->nullable();
            $table->unsignedBigInteger('idempleado_registra')->nullable();
            $table->date('fecha_ingreso');
            $table->date('fecha_entrega')->nullable();
            $table->string('diagnostico', 500);
            $table->text('descripcion');
            $table->decimal('costo_mano_obra', 12, 2)->default(0);
            $table->decimal('costo_refacciones', 12, 2)->default(0);
            $table->decimal('costo_total', 12, 2)->default(0);
            $table->unsignedInteger('tiempo_fuera_horas')->nullable();
            $table->enum('estatus', ['pendiente', 'en_proceso', 'finalizado', 'cancelado'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();

            $table->index('idactivofijo');
            $table->index('estatus');
            $table->index('fecha_ingreso');
        });

        // ─── 7. PARTIDAS DE MANTENIMIENTO (refacciones/partes - preventivo y correctivo) ──
        Schema::create('flotilla_mantenimiento_partidas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_mantenimiento', ['preventivo', 'correctivo']);
            $table->unsignedBigInteger('idmantenimiento');
            $table->unsignedBigInteger('idcatalogo_refaccion')->nullable(); // FK -> flotilla_cat_refacciones
            $table->string('descripcion', 300);
            $table->decimal('cantidad', 10, 3)->default(1);
            $table->string('unidad_medida', 30)->default('pieza');
            $table->decimal('costo_unitario', 12, 2)->default(0);
            $table->decimal('costo_total', 12, 2)->default(0);
            $table->string('numero_serie', 100)->nullable();     // para partes serializadas (llantas, baterías)
            $table->string('posicion', 50)->nullable();          // delantera_izq, trasera_der, etc.
            $table->unsignedInteger('vida_util_km')->nullable(); // si aplica (llantas, bandas)
            $table->timestamps();

            $table->index(['tipo_mantenimiento', 'idmantenimiento'], 'idx_partidas_tipo_id');
        });

        // ─── 8. DOCUMENTOS DE UNIDAD ─────────────────────────────────────────
        Schema::create('flotilla_documentos_unidad', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idactivofijo');
            $table->string('tipo_documento', 80); // seguro, tarjeta_circulacion, verificacion, tenencia, garantia, otro
            $table->string('nombre_custom', 150)->nullable(); // para tipo = "otro"
            $table->string('numero_documento', 100)->nullable();
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('costo', 12, 2)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('archivo', 500)->nullable();
            $table->enum('estatus_alerta', ['verde', 'amarillo', 'rojo', 'sin_fecha'])->default('sin_fecha');
            $table->unsignedInteger('dias_alerta_amarillo')->default(30);
            $table->tinyInteger('activo')->default(1);
            $table->timestamps();

            $table->index('idactivofijo');
            $table->index('tipo_documento');
            $table->index('fecha_vencimiento');
        });

        // ─── 9. ARCHIVOS GENÉRICOS DEL MÓDULO ────────────────────────────────
        Schema::create('flotilla_archivos', function (Blueprint $table) {
            $table->id();
            $table->enum('entidad_tipo', ['preventivo', 'correctivo', 'documento']);
            $table->unsignedBigInteger('entidad_id');
            $table->string('nombre', 255);
            $table->string('nombre_original', 255)->nullable();
            $table->string('ruta', 500);
            $table->string('tipo_mime', 100)->nullable();
            $table->string('extension', 20)->nullable();
            $table->bigInteger('tamano')->nullable();
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();

            $table->index(['entidad_tipo', 'entidad_id']);
        });

        // ─── 10. ALERTAS ──────────────────────────────────────────────────────
        Schema::create('flotilla_alertas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idactivofijo');
            $table->string('tipo_alerta', 80); // mantenimiento_preventivo, documento
            $table->string('entidad_tipo', 50)->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->enum('nivel', ['verde', 'amarillo', 'rojo']);
            $table->string('mensaje', 500);
            $table->json('datos_extra')->nullable();
            $table->tinyInteger('leida')->default(0);
            $table->datetime('fecha_generacion');
            $table->datetime('fecha_lectura')->nullable();
            $table->timestamps();

            $table->index('idactivofijo');
            $table->index(['nivel', 'leida']);
            $table->index('tipo_alerta');
        });

        // ─── 11. BITÁCORA / TIMELINE CRONOLÓGICO ─────────────────────────────
        Schema::create('flotilla_bitacora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idactivofijo');
            $table->string('tipo_evento', 80);
            // mant_preventivo, mant_correctivo, combustible, asignacion, documento, lectura_km, observacion
            $table->string('entidad_tipo', 50)->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('descripcion', 500);
            $table->decimal('km_evento', 12, 2)->nullable();
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->datetime('fecha_evento');
            $table->timestamps();

            $table->index('idactivofijo');
            $table->index('fecha_evento');
            $table->index('tipo_evento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flotilla_bitacora');
        Schema::dropIfExists('flotilla_alertas');
        Schema::dropIfExists('flotilla_archivos');
        Schema::dropIfExists('flotilla_documentos_unidad');
        Schema::dropIfExists('flotilla_mantenimiento_partidas');
        Schema::dropIfExists('flotilla_mantenimiento_correctivo');
        Schema::dropIfExists('flotilla_mantenimiento_preventivo');
        Schema::dropIfExists('flotilla_unidad_mantenimiento');
        Schema::dropIfExists('flotilla_plantillas_servicio');
        Schema::dropIfExists('flotilla_plantillas_mantenimiento');
        Schema::dropIfExists('flotilla_cat_refacciones');
    }
};
