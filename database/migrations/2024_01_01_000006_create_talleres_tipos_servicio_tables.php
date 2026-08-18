<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // cat_tipos_proveedor
        Schema::create('cat_tipos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // talleres (proveedores de servicio)
        Schema::create('talleres', function (Blueprint $table) {
            $table->id();
            $table->string('clavelizer', 50)->nullable();
            $table->string('nombrecorto', 100)->nullable();
            $table->string('razonsocial', 200);
            $table->string('tiposervicio', 150)->nullable();
            $table->string('tipoproveedor', 100)->nullable();
            $table->unsignedBigInteger('idtipoproveedor')->nullable();
            $table->string('contacto', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('sucursal', 255)->nullable();    // JSON/CSV de IDs de sucursales
            $table->text('domicilio')->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_tipos_servicio
        Schema::create('cat_tipos_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('descripcion', 255)->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->tinyInteger('activo')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_tipos_servicio');
        Schema::dropIfExists('talleres');
        Schema::dropIfExists('cat_tipos_proveedor');
    }
};
