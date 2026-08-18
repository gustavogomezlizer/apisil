<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // negocios
        Schema::create('negocios', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('nombre', 150);
            $table->string('descripcion', 255)->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // sucursales (tabla local del sistema)
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 50)->unique();
            $table->string('nombre', 150);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedBigInteger('idnegocio')->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // aseguradoras
        Schema::create('aseguradoras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('descripcion', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 150)->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aseguradoras');
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('negocios');
    }
};
