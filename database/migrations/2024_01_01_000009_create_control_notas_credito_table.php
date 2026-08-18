<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // control_notas_credito
        Schema::create('control_notas_credito', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idnegocio')->nullable();
            $table->string('negocio', 150)->nullable();
            $table->string('anio', 10)->nullable();
            $table->string('mes', 10)->nullable();
            $table->unsignedBigInteger('idsucursal')->nullable();
            $table->string('sucursal', 150)->nullable();
            $table->date('fecha')->nullable();
            $table->string('id_nota_credito', 100)->nullable();
            $table->string('nota_credito', 150)->nullable();
            $table->string('numero_nc', 100)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->decimal('importe', 12, 2)->default(0);
            $table->string('aplicado', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_notas_credito');
    }
};
