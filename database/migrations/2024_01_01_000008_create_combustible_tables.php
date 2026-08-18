<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // combustible - cargas de combustible
        Schema::create('combustible', function (Blueprint $table) {
            $table->id();
            $table->string('foliointerno', 50)->nullable();
            $table->string('folioproveedor', 100)->nullable();
            $table->unsignedBigInteger('idvehiculo')->nullable();   // FK a activos_fijos
            $table->string('numerounidad', 50)->nullable();
            $table->unsignedBigInteger('idproveedor')->nullable();  // FK a talleres
            $table->unsignedBigInteger('idnegocio')->nullable();
            $table->unsignedBigInteger('idsucursal')->nullable();
            $table->string('idrutas', 100)->nullable();
            $table->date('fechacarga')->nullable();
            $table->string('semana', 10)->nullable();
            $table->decimal('litros', 10, 3)->default(0);
            $table->decimal('importe', 12, 2)->default(0);
            $table->decimal('ultimoodometro', 12, 2)->default(0);   // odómetro anterior
            $table->decimal('odometrocarga', 12, 2)->default(0);    // odómetro al cargar
            $table->decimal('consumo', 12, 2)->nullable();
            $table->decimal('rendimiento', 10, 4)->nullable();
            $table->string('kmsasignados', 50)->nullable();
            $table->string('consumos', 50)->nullable();
            $table->string('responsable', 150)->nullable();
            $table->string('estatus', 50)->default('Capturado');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();
        });

        // usuarios_sucursal_combustible - configuracion de sucursales permitidas por usuario para combustible
        Schema::create('usuarios_sucursal_combustible', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idusuario');
            $table->unsignedBigInteger('idsucursal');
            $table->timestamps();

            $table->unique(['idusuario', 'idsucursal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_sucursal_combustible');
        Schema::dropIfExists('combustible');
    }
};
