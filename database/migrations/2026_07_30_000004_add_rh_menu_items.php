<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega "Gestión de Personal" como grupo padre en el menú de Recursos Humanos
 * y agrega los sub-items del nuevo módulo RH.
 */
return new class extends Migration
{
    public function up(): void
    {
        $idMenuRH = 2; // "Recursos Humanos"
        $now = now();

        // ─── 1. Crear padre "Gestión de Personal" ────────────────────────
        $idPadreRH = DB::table('submenu')->insertGetId([
            'idmenu'          => $idMenuRH,
            'idsubmenu_padre' => null,
            'nombre'          => 'Gestión de Personal',
            'link'            => null,
            'orden'           => 10,
            'estatus'         => 1,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // ─── 2. Sub-items del módulo RH ────────────────────────────────────
        $rhItems = [
            ['nombre' => 'Dashboard',            'link' => '/rh/dashboard',   'orden' => 1],
            ['nombre' => 'Movimientos',           'link' => '/rh/movimientos', 'orden' => 2],
            ['nombre' => 'Expediente Digital',    'link' => '/rh/expediente',  'orden' => 3],
            ['nombre' => 'Alertas RH',            'link' => '/rh/alertas',     'orden' => 4],
            ['nombre' => 'Reportes RH',           'link' => '/rh/reportes',    'orden' => 5],
        ];

        foreach ($rhItems as $item) {
            DB::table('submenu')->insert([
                'idmenu'          => $idMenuRH,
                'idsubmenu_padre' => $idPadreRH,
                'nombre'          => $item['nombre'],
                'link'            => $item['link'],
                'orden'           => $item['orden'],
                'estatus'         => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // ─── 3. Permisos para usuarios con acceso a empleados ─────────────
        $newPaths = ['/rh/dashboard', '/rh/movimientos', '/rh/expediente', '/rh/alertas', '/rh/reportes'];
        $usersWithRH = DB::table('user_permissions')
            ->where('permission_path', '/catalogos/empleados')
            ->pluck('user_id')
            ->unique();

        foreach ($usersWithRH as $userId) {
            foreach ($newPaths as $path) {
                if (!DB::table('user_permissions')->where('user_id', $userId)->where('permission_path', $path)->exists()) {
                    DB::table('user_permissions')->insert([
                        'user_id'         => $userId,
                        'permission_path' => $path,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('submenu')->where('nombre', 'Gestión de Personal')->delete();
        DB::table('submenu')->whereIn('link', ['/rh/dashboard', '/rh/movimientos', '/rh/expediente', '/rh/alertas', '/rh/reportes'])->delete();
    }
};
