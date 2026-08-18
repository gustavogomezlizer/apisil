<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Catálogos base: departamentos, puestos, estado civil, condiciones, tipos activos, anios, etc.
return new class extends Migration
{
    public function up(): void
    {
        // cat_departamentos
        Schema::create('cat_departamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_puestos
        Schema::create('cat_puestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_estado_civil
        Schema::create('cat_estado_civil', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_condiciones
        Schema::create('cat_condiciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_tipos_activos_fijos
        Schema::create('cat_tipos_activos_fijos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_estatus_activos_fijos
        Schema::create('cat_estatus_activos_fijos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_anios
        Schema::create('cat_anios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 10);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_tipo_vehiculo
        Schema::create('cat_tipo_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_tipos_combustible
        Schema::create('cat_tipos_combustible', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_tipos_transmision
        Schema::create('cat_tipos_transmision', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_tipo_cobertura_seguro
        Schema::create('cat_tipo_cobertura_seguro', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_marcas
        Schema::create('cat_marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_modelos
        Schema::create('cat_modelos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idmarca')->nullable();
            $table->string('nombre', 150);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_colores
        Schema::create('cat_colores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_nivel_gasolina
        Schema::create('cat_nivel_gasolina', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->decimal('niveldecimales', 5, 2)->default(0);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_tipos_licencia
        Schema::create('cat_tipos_licencia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_checklist
        Schema::create('cat_checklist', function (Blueprint $table) {
            $table->id();
            $table->string('parte', 50);
            $table->string('nombre', 150);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // cat_concepto_mantenimiento
        Schema::create('cat_concepto_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_concepto_mantenimiento');
        Schema::dropIfExists('cat_checklist');
        Schema::dropIfExists('cat_tipos_licencia');
        Schema::dropIfExists('cat_nivel_gasolina');
        Schema::dropIfExists('cat_colores');
        Schema::dropIfExists('cat_modelos');
        Schema::dropIfExists('cat_marcas');
        Schema::dropIfExists('cat_tipo_cobertura_seguro');
        Schema::dropIfExists('cat_tipos_transmision');
        Schema::dropIfExists('cat_tipos_combustible');
        Schema::dropIfExists('cat_tipo_vehiculo');
        Schema::dropIfExists('cat_anios');
        Schema::dropIfExists('cat_estatus_activos_fijos');
        Schema::dropIfExists('cat_tipos_activos_fijos');
        Schema::dropIfExists('cat_condiciones');
        Schema::dropIfExists('cat_estado_civil');
        Schema::dropIfExists('cat_puestos');
        Schema::dropIfExists('cat_departamentos');
    }
};
