<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ordenes_servicio
        Schema::create('ordenes_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('ordenservicio', 50)->nullable();
            $table->date('fechaingreso')->nullable();
            $table->date('fechaentrega')->nullable();
            $table->unsignedBigInteger('idunidad')->nullable();    // FK a activos_fijos
            $table->string('usuario', 150)->nullable();
            $table->string('sucursal', 150)->nullable();
            $table->unsignedBigInteger('idsucursal')->nullable();
            $table->string('descripcionunidad', 255)->nullable();
            $table->string('kilometrajeunidad', 50)->nullable();
            $table->unsignedBigInteger('idtaller')->nullable();    // FK a talleres
            $table->string('estatusorden', 50)->default('INICIADO');
            $table->string('autorizacion_estatus', 50)->default('pendiente');
            $table->text('autorizacion_comentario')->nullable();
            $table->decimal('totalimporte', 12, 2)->default(0);
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();
        });

        // ordenes_servicio_detalle
        Schema::create('ordenes_servicio_detalle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idorden');
            $table->unsignedBigInteger('idservicio')->nullable();  // FK a cat_tipos_servicio
            $table->decimal('importe', 12, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('idorden')->references('id')->on('ordenes_servicio')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_servicio_detalle');
        Schema::dropIfExists('ordenes_servicio');
    }
};
