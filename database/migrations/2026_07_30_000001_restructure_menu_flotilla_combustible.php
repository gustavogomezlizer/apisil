<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reestructura el menú lateral:
 *   1. Crea "Gestión de Flotilla" como padre y mueve los items actuales como hijos.
 *   2. Crea "Control de Combustible" como padre con sus sub-items.
 *   3. Desactiva los items planos anteriores de Flotilla.
 *   4. Mueve Rendimiento y Costo KM bajo Control de Combustible.
 *   5. Otorga permisos del nuevo módulo al superadmin.
 */
return new class extends Migration
{
    public function up(): void
    {
        $idMenuOperaciones = 4; // idmenu de "Operaciones"
        $now = now();

        // ─── 1. DESACTIVAR items planos de Flotilla que estaban sueltos ───────
        DB::table('submenu')
            ->whereIn('id', [26, 27, 28, 29, 30, 31, 32, 33, 34, 35])
            ->update(['estatus' => 0, 'updated_at' => $now]);

        // Desactivar Rendimiento y Costo KM del nivel plano (los moveremos bajo Combustible)
        DB::table('submenu')
            ->whereIn('id', [18, 19, 20]) // Mantenimientos, Rendimiento, CostoKm originales
            ->update(['estatus' => 0, 'updated_at' => $now]);

        // ─── 2. CREAR PADRE: "Gestión de Flotilla" ────────────────────────────
        $idFlotilla = DB::table('submenu')->insertGetId([
            'idmenu'          => $idMenuOperaciones,
            'idsubmenu_padre' => null,
            'nombre'          => 'Gestión de Flotilla',
            'link'            => null,
            'orden'           => 9,
            'estatus'         => 1,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // Sub-items de Flotilla
        $flotillaItems = [
            ['nombre' => 'Dashboard',               'link' => '/flotilla/dashboard',                  'orden' => 1],
            ['nombre' => 'Mant. Preventivo',        'link' => '/flotilla/preventivo',                 'orden' => 2],
            ['nombre' => 'Mant. Correctivo',        'link' => '/flotilla/correctivo',                 'orden' => 3],
            ['nombre' => 'Plantillas',              'link' => '/flotilla/plantillas',                 'orden' => 4],
            ['nombre' => 'Lecturas de Km',          'link' => '/flotilla/lecturas-km',                'orden' => 5],
            ['nombre' => 'Documentos',              'link' => '/flotilla/documentos',                 'orden' => 6],
            ['nombre' => 'Alertas',                 'link' => '/flotilla/alertas',                    'orden' => 7],
            ['nombre' => 'Bitácora',                'link' => '/flotilla/bitacora',                   'orden' => 8],
            ['nombre' => 'Reportes',                'link' => '/flotilla/reportes',                   'orden' => 9],
            ['nombre' => 'Config. Unidades',        'link' => '/flotilla/unidades/configuracion',     'orden' => 10],
        ];

        foreach ($flotillaItems as $item) {
            DB::table('submenu')->insert([
                'idmenu'          => $idMenuOperaciones,
                'idsubmenu_padre' => $idFlotilla,
                'nombre'          => $item['nombre'],
                'link'            => $item['link'],
                'orden'           => $item['orden'],
                'estatus'         => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // ─── 3. CREAR PADRE: "Control de Combustible" ─────────────────────────
        $idCombustible = DB::table('submenu')->insertGetId([
            'idmenu'          => $idMenuOperaciones,
            'idsubmenu_padre' => null,
            'nombre'          => 'Control de Combustible',
            'link'            => null,
            'orden'           => 10,
            'estatus'         => 1,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // Sub-items de Combustible
        $combustibleItems = [
            ['nombre' => 'Dashboard',               'link' => '/combustible/dashboard',                       'orden' => 1],
            ['nombre' => 'Registro de Cargas',      'link' => '/operaciones/vehiculos/combustible',           'orden' => 2],
            ['nombre' => 'Presupuestos',            'link' => '/combustible/presupuestos',                    'orden' => 3],
            ['nombre' => 'Reportes',                'link' => '/combustible/reportes',                        'orden' => 4],
            ['nombre' => 'Alertas y Validaciones',  'link' => '/combustible/alertas',                         'orden' => 5],
            ['nombre' => 'Rendimiento',             'link' => '/operaciones/vehiculos/rendimiento',           'orden' => 6],
            ['nombre' => 'Costo por KM',            'link' => '/operaciones/vehiculos/costo-km',              'orden' => 7],
            ['nombre' => 'Análisis Mantenimiento',  'link' => '/operaciones/reportes/analisis-mantenimiento', 'orden' => 8],
        ];

        foreach ($combustibleItems as $item) {
            DB::table('submenu')->insert([
                'idmenu'          => $idMenuOperaciones,
                'idsubmenu_padre' => $idCombustible,
                'nombre'          => $item['nombre'],
                'link'            => $item['link'],
                'orden'           => $item['orden'],
                'estatus'         => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // ─── 4. REORDENAR los items de Operaciones que quedan al nivel raíz ───
        // Tickets Combustible (id=17) ya se mueve bajo el padre
        DB::table('submenu')->where('id', 17)->update(['estatus' => 0, 'updated_at' => $now]);
        // Análisis Mantenimiento (id=21) ya está como sub-item de combustible
        DB::table('submenu')->where('id', 21)->update(['estatus' => 0, 'updated_at' => $now]);

        // ─── 5. PERMISOS: otorgar al superadmin los nuevos paths ─────────────
        $newPaths = [
            '/combustible/dashboard',
            '/combustible/presupuestos',
            '/combustible/reportes',
            '/combustible/alertas',
            '/flotilla/dashboard',
            '/flotilla/preventivo',
            '/flotilla/correctivo',
            '/flotilla/plantillas',
            '/flotilla/lecturas-km',
            '/flotilla/documentos',
            '/flotilla/alertas',
            '/flotilla/bitacora',
            '/flotilla/reportes',
            '/flotilla/unidades/configuracion',
        ];

        // Obtener usuarios que ya tienen acceso a combustible o flotilla
        $usersWithCombustible = DB::table('user_permissions')
            ->where('permission_path', '/operaciones/vehiculos/combustible')
            ->pluck('user_id')
            ->unique();

        // Para cada usuario con acceso a combustible, agregar los nuevos permisos
        foreach ($usersWithCombustible as $userId) {
            foreach ($newPaths as $path) {
                $exists = DB::table('user_permissions')
                    ->where('user_id', $userId)
                    ->where('permission_path', $path)
                    ->exists();

                if (!$exists) {
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
        // Reactivar items anteriores
        DB::table('submenu')
            ->whereIn('id', [17, 18, 19, 20, 21, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35])
            ->update(['estatus' => 1, 'updated_at' => now()]);

        // Eliminar los nuevos padres e hijos (por rango de IDs)
        DB::table('submenu')
            ->where('nombre', 'Gestión de Flotilla')
            ->orWhere('nombre', 'Control de Combustible')
            ->orWhereIn('link', [
                '/combustible/dashboard', '/combustible/presupuestos',
                '/combustible/reportes', '/combustible/alertas',
            ])
            ->delete();
    }
};
