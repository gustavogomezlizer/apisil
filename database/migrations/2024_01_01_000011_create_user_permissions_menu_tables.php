<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de permisos personalizados por usuario (paths de rutas + acciones)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('permission_path', 255);   // ej: /sistemas/usuarios  o  /sistemas/usuarios:edit
            $table->timestamps();

            $table->unique(['user_id', 'permission_path']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });

        // Menú principal de navegación
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('controlador', 255)->nullable();  // path base (puede ser vacío para grupos)
            $table->string('icono', 100)->nullable();
            $table->integer('orden')->default(0);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();
        });

        // Submenú (ítems dentro de cada menú principal)
        Schema::create('submenu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idmenu');
            $table->unsignedBigInteger('idsubmenu_padre')->nullable();  // para submenú anidado
            $table->string('nombre', 150);
            $table->string('link', 255)->nullable();    // path de la ruta Vue (coincide con requiresPermission)
            $table->integer('orden')->default(0);
            $table->tinyInteger('estatus')->default(1);
            $table->timestamps();

            $table->foreign('idmenu')->references('id')->on('menu')->onDelete('cascade');
            $table->index('idmenu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submenu');
        Schema::dropIfExists('menu');
        Schema::dropIfExists('user_permissions');
    }
};
