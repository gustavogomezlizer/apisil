<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flotilla_lecturas_km', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idactivofijo');          // FK -> activos_fijos.id
            $table->date('fecha');
            $table->decimal('kilometraje', 12, 2);
            $table->enum('origen', [
                'combustible',
                'servicio',
                'captura_semanal',
                'importacion',
                'gps',
            ])->default('captura_semanal');
            $table->unsignedBigInteger('idempleado')->nullable(); // operador / responsable
            $table->unsignedBigInteger('idusuario')->nullable();  // usuario que capturó
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('idactivofijo');
            $table->index('fecha');
            $table->index(['idactivofijo', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flotilla_lecturas_km');
    }
};
