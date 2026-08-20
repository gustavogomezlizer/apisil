<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ordenes_servicio_detalle', 'numeromovimiento')) {
            Schema::table('ordenes_servicio_detalle', function (Blueprint $table) {
                $table->unsignedInteger('numeromovimiento')->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ordenes_servicio_detalle', function (Blueprint $table) {
            $table->dropColumn('numeromovimiento');
        });
    }
};
