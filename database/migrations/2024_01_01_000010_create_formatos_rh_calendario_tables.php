<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // cat_tipos_formato_rh
        Schema::create('cat_tipos_formato_rh', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('descripcion', 255)->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // formatos_rh - archivos/documentos de recursos humanos
        Schema::create('formatos_rh', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('descripcion', 500)->nullable();
            $table->unsignedBigInteger('idtipoformato')->nullable();
            $table->string('ruta', 500)->nullable();
            $table->string('tipo', 50)->nullable();
            $table->string('extension', 20)->nullable();
            $table->bigInteger('tamano')->nullable();
            $table->tinyInteger('estatus')->default(1);
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();
        });

        // calendario - días festivos / eventos
        Schema::create('calendario', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('tipo', 50)->nullable();  // festivo, evento, etc.
            $table->string('color', 20)->nullable();
            $table->tinyInteger('todo_dia')->default(1);
            $table->tinyInteger('estatus')->default(1);
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario');
        Schema::dropIfExists('formatos_rh');
        Schema::dropIfExists('cat_tipos_formato_rh');
    }
};
