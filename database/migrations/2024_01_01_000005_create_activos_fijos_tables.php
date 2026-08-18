<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // activos_fijos - tabla principal
        Schema::create('activos_fijos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->nullable();
            $table->unsignedBigInteger('idtipoactivo');
            $table->string('descripcion', 255);
            $table->string('marca', 150)->nullable();
            $table->string('modelo', 150)->nullable();
            $table->string('anio', 10)->nullable();
            $table->string('serie', 100)->nullable();
            $table->unsignedBigInteger('idsucursal')->nullable();
            $table->unsignedBigInteger('idnegocio')->nullable();
            $table->unsignedBigInteger('idcondicion')->nullable();
            $table->date('fechaadquisicion')->nullable();
            $table->date('fechareemplazo')->nullable();
            $table->decimal('precio', 12, 2)->nullable();
            $table->text('caracteristicas')->nullable();
            $table->text('condiciones')->nullable();
            $table->string('pin', 100)->nullable();
            $table->string('estatus', 30)->default('Activo');
            $table->timestamps();
        });

        // activos_fijos_unidades - datos técnicos del vehículo/unidad
        Schema::create('activos_fijos_unidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idactivofijo');
            $table->string('numeroeconomico', 50)->nullable();
            $table->string('propietario', 150)->nullable();
            $table->string('placas', 30)->nullable();
            $table->text('accesorios')->nullable();
            $table->string('entidadfederativa', 100)->nullable();
            $table->unsignedBigInteger('idaseguradora')->nullable();
            $table->string('numeropoliza', 100)->nullable();
            $table->string('inciso', 100)->nullable();
            $table->string('cobertura', 150)->nullable();
            $table->date('fechavencimientopoliza')->nullable();
            $table->decimal('costopoliza', 12, 2)->nullable();
            $table->decimal('combustibleasignado', 10, 2)->nullable()->default(0);
            // dias de combustible
            $table->decimal('clunes', 10, 2)->nullable()->default(0);
            $table->decimal('cmartes', 10, 2)->nullable()->default(0);
            $table->decimal('cmiercoles', 10, 2)->nullable()->default(0);
            $table->decimal('cjueves', 10, 2)->nullable()->default(0);
            $table->decimal('cviernes', 10, 2)->nullable()->default(0);
            $table->decimal('csabado', 10, 2)->nullable()->default(0);
            $table->decimal('cdomingo', 10, 2)->nullable()->default(0);
            $table->timestamps();

            $table->foreign('idactivofijo')->references('id')->on('activos_fijos')->onDelete('cascade');
        });

        // activos_fijos_asignacion
        Schema::create('activos_fijos_asignacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idactivofijo');
            $table->unsignedBigInteger('tipoactivo')->nullable();
            $table->unsignedBigInteger('idsucursal')->nullable();
            $table->unsignedBigInteger('iddepartamento')->nullable();
            $table->unsignedBigInteger('idempleadoasignado');
            $table->date('fechaasignacion');
            $table->string('estadoasignacion', 50)->default('Activa');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('idactivofijo')->references('id')->on('activos_fijos')->onDelete('cascade');
        });

        // archivos_activos_fijos
        Schema::create('archivos_activos_fijos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idactivofijo');
            $table->string('nombre', 255);
            $table->string('ruta', 500)->nullable();
            $table->string('tipo', 50)->nullable();
            $table->string('extension', 20)->nullable();
            $table->bigInteger('tamano')->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();

            $table->foreign('idactivofijo')->references('id')->on('activos_fijos')->onDelete('cascade');
        });

        // activos_fijos_movimientos
        Schema::create('activos_fijos_movimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idactivofijo');
            $table->string('tipo_movimiento', 50); // alta, asignacion, reasignacion, transferencia, mantenimiento, baja
            $table->string('descripcion', 255)->nullable();
            $table->unsignedBigInteger('idempleado_origen')->nullable();
            $table->unsignedBigInteger('idempleado_destino')->nullable();
            $table->unsignedBigInteger('iddepartamento_origen')->nullable();
            $table->unsignedBigInteger('iddepartamento_destino')->nullable();
            $table->string('ubicacion_origen', 200)->nullable();
            $table->string('ubicacion_destino', 200)->nullable();
            $table->date('fecha_movimiento');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();

            $table->foreign('idactivofijo')->references('id')->on('activos_fijos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activos_fijos_movimientos');
        Schema::dropIfExists('archivos_activos_fijos');
        Schema::dropIfExists('activos_fijos_asignacion');
        Schema::dropIfExists('activos_fijos_unidades');
        Schema::dropIfExists('activos_fijos');
    }
};
