<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Datos laborales extendidos (complemento de la tabla empleados) ─
        Schema::create('rh_empleados_extra', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idempleado')->unique(); // 1:1 con empleados
            // Datos laborales
            $table->decimal('salario_mensual', 12, 2)->nullable();
            $table->decimal('salario_diario',  10, 4)->nullable();
            $table->string('tipo_contrato', 80)->nullable();    // indefinido, temporal, por_obra, etc.
            $table->date('fecha_fin_contrato')->nullable();
            $table->date('fecha_fin_periodo_prueba')->nullable();
            $table->unsignedBigInteger('idsupervisor')->nullable(); // FK -> empleados.id
            $table->string('centro_costos', 100)->nullable();
            $table->string('turno', 80)->nullable();            // mañana, tarde, noche, mixto
            $table->string('horario', 200)->nullable();
            $table->string('tipo_empleado', 80)->nullable();    // operativo, administrativo, directivo
            // Control
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('idsupervisor');
        });

        // ─── Historial de movimientos de personal ─────────────────────────
        Schema::create('rh_movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->nullable()->unique();
            $table->unsignedBigInteger('idempleado');
            $table->enum('tipo_movimiento', [
                'alta', 'baja', 'reingreso',
                'cambio_puesto', 'cambio_sucursal', 'cambio_departamento', 'cambio_salario',
                'promocion', 'transferencia',
                'incapacidad', 'vacaciones', 'permiso', 'suspension',
                'cambio_horario', 'fin_contrato', 'renovacion_contrato', 'otro',
            ]);
            $table->date('fecha_efectiva');

            // Snapshot "antes" — para auditoría completa
            $table->string('puesto_anterior', 150)->nullable();
            $table->unsignedBigInteger('idsucursal_anterior')->nullable();
            $table->unsignedBigInteger('iddepartamento_anterior')->nullable();
            $table->decimal('salario_anterior', 12, 2)->nullable();
            $table->string('turno_anterior', 80)->nullable();

            // Snapshot "después"
            $table->string('puesto_nuevo', 150)->nullable();
            $table->unsignedBigInteger('idsucursal_nueva')->nullable();
            $table->unsignedBigInteger('iddepartamento_nuevo')->nullable();
            $table->decimal('salario_nuevo', 12, 2)->nullable();
            $table->string('turno_nuevo', 80)->nullable();

            // Detalles del movimiento
            $table->string('motivo', 500)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('tipo_baja', 100)->nullable();       // voluntaria, despido, fin_contrato
            $table->date('fecha_inicio')->nullable();           // para incapacidades, vacaciones, etc.
            $table->date('fecha_fin')->nullable();
            $table->integer('dias_afectados')->nullable();

            // Archivos de evidencia
            $table->string('archivo_evidencia', 500)->nullable();

            // Flujo
            $table->enum('estatus', ['pendiente', 'aprobado', 'rechazado', 'completado'])->default('completado');
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->datetime('fecha_aprobacion')->nullable();
            $table->text('comentario_aprobacion')->nullable();

            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();

            $table->index('idempleado');
            $table->index('tipo_movimiento');
            $table->index('fecha_efectiva');
            $table->index(['idempleado', 'tipo_movimiento']);
        });

        // ─── Expediente digital / documentos del empleado ─────────────────
        Schema::create('rh_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idempleado');
            $table->string('tipo_documento', 80);
            // INE, CURP, RFC, NSS, acta_nacimiento, comprobante_domicilio,
            // contrato, foto, constancia, certificado, evaluacion, carta, otro
            $table->string('nombre_custom', 150)->nullable();   // cuando tipo = "otro"
            $table->string('nombre_archivo', 255)->nullable();
            $table->string('numero_documento', 100)->nullable(); // folio / número del documento
            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('ruta', 500)->nullable();
            $table->string('tipo_mime', 100)->nullable();
            $table->string('extension', 20)->nullable();
            $table->bigInteger('tamano')->nullable();
            $table->tinyInteger('version')->default(1);
            $table->tinyInteger('vigente')->default(1);         // 0 = versión anterior
            $table->enum('estatus_alerta', ['verde', 'amarillo', 'rojo', 'sin_fecha'])->default('sin_fecha');
            $table->unsignedInteger('dias_alerta_amarillo')->default(30);
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();

            $table->index('idempleado');
            $table->index('tipo_documento');
            $table->index('fecha_vencimiento');
            $table->index(['idempleado', 'tipo_documento']);
        });

        // ─── Alertas del módulo de Recursos Humanos ───────────────────────
        Schema::create('rh_alertas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idempleado')->nullable();
            $table->string('tipo_alerta', 80);
            // contrato_vencer, documento_vencer, cumpleanos, aniversario,
            // periodo_prueba, expediente_incompleto, evaluacion_pendiente
            $table->string('entidad_tipo', 50)->nullable();     // tabla origen
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->enum('nivel', ['info', 'advertencia', 'critica'])->default('advertencia');
            $table->string('mensaje', 500);
            $table->json('datos_extra')->nullable();
            $table->tinyInteger('leida')->default(0);
            $table->date('fecha_alerta');
            $table->datetime('fecha_lectura')->nullable();
            $table->timestamps();

            $table->index('idempleado');
            $table->index(['nivel', 'leida']);
            $table->index('fecha_alerta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_alertas');
        Schema::dropIfExists('rh_documentos');
        Schema::dropIfExists('rh_movimientos');
        Schema::dropIfExists('rh_empleados_extra');
    }
};
