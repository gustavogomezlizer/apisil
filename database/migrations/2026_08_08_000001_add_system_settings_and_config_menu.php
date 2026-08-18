<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de configuración del sistema + item "Configuración" en el menú de Sistemas
 * + permiso del super admin para ver la configuración.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ─── 1. Tabla system_settings ────────────────────────────────────
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // Valor por defecto: ocultar opciones de menú sin permiso (1 = sí)
        if (!DB::table('system_settings')->where('key', 'ocultar_menu_sin_permiso')->exists()) {
            DB::table('system_settings')->insert([
                'key'        => 'ocultar_menu_sin_permiso',
                'value'      => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ─── 2. Submenú "Configuración" junto a "Usuarios" ─────────────────
        // Se agrega al mismo grupo de menú donde vive "Usuarios", sin importar
        // cómo se llame el grupo (Sistemas / Acceso, según la base de datos).
        $usuarios = DB::table('submenu')
            ->where('link', '/sistemas/usuarios')
            ->first(['id', 'idmenu']);

        if ($usuarios) {
            $exists = DB::table('submenu')
                ->where('idmenu', $usuarios->idmenu)
                ->where('link', '/sistemas/configuracion')
                ->exists();

            if (!$exists) {
                $orden = DB::table('submenu')
                    ->where('idmenu', $usuarios->idmenu)
                    ->max('orden');

                DB::table('submenu')->insert([
                    'idmenu'          => $usuarios->idmenu,
                    'idsubmenu_padre' => null,
                    'nombre'          => 'Configuración',
                    'link'            => '/sistemas/configuracion',
                    'orden'           => ($orden ?? 0) + 1,
                    'estatus'         => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }

        // ─── 3. Permiso del super admin (usuario "admin") ────────────────
        $adminId = DB::table('users')->where('username', 'admin')->value('id');

        if ($adminId) {
            $hasPermission = DB::table('user_permissions')
                ->where('user_id', $adminId)
                ->where('permission_path', '/sistemas/configuracion')
                ->exists();

            if (!$hasPermission) {
                DB::table('user_permissions')->insert([
                    'user_id'         => $adminId,
                    'permission_path' => '/sistemas/configuracion',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('submenu')->where('link', '/sistemas/configuracion')->delete();
        DB::table('system_settings')->where('key', 'ocultar_menu_sin_permiso')->delete();
        DB::table('user_permissions')->where('permission_path', '/sistemas/configuracion')->delete();
    }
};
