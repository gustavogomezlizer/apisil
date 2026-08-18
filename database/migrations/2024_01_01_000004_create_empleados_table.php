<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('numeroempleado', 50)->unique();
            $table->string('nombres', 255);
            $table->string('apellidopaterno', 255)->nullable();
            $table->string('apellidomaterno', 255)->nullable();
            $table->string('nombrecompleto', 255)->nullable();
            $table->unsignedBigInteger('iddepartamento')->nullable();
            $table->unsignedBigInteger('idsucursal')->nullable();
            $table->unsignedBigInteger('idnegocio')->nullable();
            $table->string('puesto', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 150)->nullable();
            $table->date('fechaingreso')->nullable();
            $table->date('fechanacimiento')->nullable();
            $table->date('fechabaja')->nullable();
            $table->string('nss', 50)->nullable();
            $table->string('rfc', 20)->nullable();
            $table->string('curp', 30)->nullable();
            $table->string('calle', 200)->nullable();
            $table->string('numeroext', 20)->nullable();
            $table->string('numeroint', 20)->nullable();
            $table->string('colonia', 150)->nullable();
            $table->string('cp', 10)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('estado', 100)->nullable();
            $table->unsignedBigInteger('idestadocivil')->nullable();
            $table->string('lugarnacimiento', 150)->nullable();
            $table->string('tiposangre', 10)->nullable();
            $table->string('contactoemergencianombre', 200)->nullable();
            $table->string('contactoemergenciaparentesco', 100)->nullable();
            $table->string('contactoemergenciatelefono', 30)->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamp('fechaactualizacion')->nullable();
            $table->timestamps();
        });

        Schema::create('archivos_empleados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idempleado');
            $table->string('nombre', 255);
            $table->string('ruta', 500)->nullable();
            $table->string('tipo', 50)->nullable();
            $table->string('extension', 20)->nullable();
            $table->bigInteger('tamano')->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();

            $table->foreign('idempleado')->references('id')->on('empleados')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos_empleados');
        Schema::dropIfExists('empleados');
    }
};
