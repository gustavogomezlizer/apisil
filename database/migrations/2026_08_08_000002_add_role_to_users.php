<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 50)->nullable()->index();
            });
        }

        // Roles iniciales para los usuarios existentes
        $roles = [
            'admin'           => 'sistemas',
            'jesus.lizarraga' => 'directivo',
            'rh'              => 'recursos_humanos',
            'operaciones'     => 'operaciones_mantenimiento',
        ];

        foreach ($roles as $username => $role) {
            DB::table('users')->where('username', $username)->update(['role' => $role]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
