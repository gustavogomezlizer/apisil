<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    // ─── Todas las rutas del sistema (deben coincidir con requiresPermission en Vue router) ───
    private array $allRoutes = [
        '/sistemas/usuarios',
        '/catalogos/empleados',
        '/catalogos/aseguradoras',
        '/catalogos/sucursales',
        '/catalogos/negocios',
        '/catalogos/adendum',
        '/catalogos/tipos-servicio',
        '/catalogos/talleres',
        '/catalogos/activos-fijos',
        '/catalogos/formatos-rh',
        '/catalogos/calendario',
        '/recursos-humanos/activos-fijos-movimientos',
        '/recursos-humanos/control-documentos',
        '/operaciones/control-activos/asignacion-activos',
        '/operaciones/ordenes-servicio',
        '/operaciones/vehiculos',
        '/operaciones/vehiculos/combustible',
        '/operaciones/vehiculos/mantenimientos',
        '/operaciones/vehiculos/rendimiento',
        '/operaciones/vehiculos/costo-km',
        '/operaciones/reportes/analisis-mantenimiento',
        '/finanzas/cumplimiento-objetivos',
        '/finanzas/control-notas-credito',
        '/reportes/estado-resultados',
        '/reportes/utilidad-operativa',
        // Permisos especiales de autorización
        '/operaciones/ordenes-servicio:authorize',
        '/operaciones/operaciones-usuarios-sucursal:authorize',
        // Configuración del sistema
        '/sistemas/configuracion',
    ];

    // ─── Acciones CRUD por ruta ───────────────────────────────────────────────
    private array $crudRoutes = [
        '/sistemas/usuarios',
        '/catalogos/empleados',
        '/catalogos/aseguradoras',
        '/catalogos/sucursales',
        '/catalogos/negocios',
        '/catalogos/adendum',
        '/catalogos/tipos-servicio',
        '/catalogos/talleres',
        '/catalogos/activos-fijos',
        '/catalogos/formatos-rh',
        '/operaciones/control-activos/asignacion-activos',
        '/operaciones/ordenes-servicio',
        '/operaciones/vehiculos',
        '/operaciones/vehiculos/combustible',
        '/finanzas/cumplimiento-objetivos',
        '/finanzas/control-notas-credito',
    ];

    public function run(): void
    {
        // ─── 1. Crear usuario super admin ─────────────────────────────────────
        $existingUser = DB::table('users')->where('username', 'admin')->first();

        if ($existingUser) {
            $userId = $existingUser->id;
            DB::table('users')->where('id', $userId)->update(['role' => 'sistemas']);
            $this->command->info("Usuario 'admin' ya existe (id: {$userId}). Actualizando permisos...");
        } else {
            $userId = DB::table('users')->insertGetId([
                'name'       => 'Super Administrador',
                'username'   => 'admin',
                'email'      => 'admin@sistema.local',
                'password'   => Hash::make('Admin123!'),
                'role'       => 'sistemas',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("Usuario 'admin' creado con id: {$userId}");
        }

        // ─── 2. Asignar todos los permisos ────────────────────────────────────
        DB::table('user_permissions')->where('user_id', $userId)->delete();

        $permissions = [];

        // Rutas base
        foreach ($this->allRoutes as $route) {
            $permissions[] = [
                'user_id'         => $userId,
                'permission_path' => $route,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        // Acciones CRUD por cada ruta con CRUD
        foreach ($this->crudRoutes as $route) {
            foreach (['create', 'edit', 'delete', 'permissions'] as $action) {
                $permissions[] = [
                    'user_id'         => $userId,
                    'permission_path' => "{$route}:{$action}",
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
        }

        DB::table('user_permissions')->insert($permissions);

        $total = count($permissions);
        $this->command->info("Se asignaron {$total} permisos al usuario 'admin'.");

        // ─── 3. Crear estructura del menú ─────────────────────────────────────
        $this->seedMenu();

        $this->command->info('');
        $this->command->info('════════════════════════════════════════════');
        $this->command->info('  Super Admin creado exitosamente');
        $this->command->info('  Usuario:    admin');
        $this->command->info('  Contraseña: Admin123!');
        $this->command->info('  CAMBIA LA CONTRASEÑA después del primer login');
        $this->command->info('════════════════════════════════════════════');
    }

    private function seedMenu(): void
    {
        // Limpiar menú existente
        DB::table('submenu')->delete();
        DB::table('menu')->delete();

        $menu = [
            [
                'nombre'      => 'Sistemas',
                'controlador' => null,
                'icono'       => 'GridIcon',
                'orden'       => 1,
                'subItems'    => [
                    ['nombre' => 'Usuarios',       'link' => '/sistemas/usuarios',        'orden' => 1],
                    ['nombre' => 'Configuración',  'link' => '/sistemas/configuracion',   'orden' => 2],
                ],
            ],
            [
                'nombre'      => 'Recursos Humanos',
                'controlador' => null,
                'icono'       => 'UserGroupIcon',
                'orden'       => 2,
                'subItems'    => [
                    ['nombre' => 'Empleados',                   'link' => '/catalogos/empleados',                         'orden' => 1],
                    ['nombre' => 'Movimientos Activos Fijos',   'link' => '/recursos-humanos/activos-fijos-movimientos',  'orden' => 2],
                    ['nombre' => 'Control de Documentos',       'link' => '/recursos-humanos/control-documentos',         'orden' => 3],
                    ['nombre' => 'Formatos RH',                 'link' => '/catalogos/formatos-rh',                       'orden' => 4],
                    ['nombre' => 'Calendario',                  'link' => '/catalogos/calendario',                        'orden' => 5],
                ],
            ],
            [
                'nombre'      => 'Catálogos',
                'controlador' => null,
                'icono'       => 'ListBulletIcon',
                'orden'       => 3,
                'subItems'    => [
                    ['nombre' => 'Activos Fijos',     'link' => '/catalogos/activos-fijos',   'orden' => 1],
                    ['nombre' => 'Aseguradoras',      'link' => '/catalogos/aseguradoras',    'orden' => 2],
                    ['nombre' => 'Sucursales',        'link' => '/catalogos/sucursales',      'orden' => 3],
                    ['nombre' => 'Negocios',          'link' => '/catalogos/negocios',        'orden' => 4],
                    ['nombre' => 'Adendum',           'link' => '/catalogos/adendum',         'orden' => 5],
                    ['nombre' => 'Tipos de Servicio', 'link' => '/catalogos/tipos-servicio',  'orden' => 6],
                    ['nombre' => 'Talleres',          'link' => '/catalogos/talleres',        'orden' => 7],
                ],
            ],
            [
                'nombre'      => 'Operaciones',
                'controlador' => null,
                'icono'       => 'TruckIcon',
                'orden'       => 4,
                'subItems'    => [
                    ['nombre' => 'Vehículos',            'link' => '/operaciones/vehiculos',                             'orden' => 1],
                    ['nombre' => 'Asignación de Activos','link' => '/operaciones/control-activos/asignacion-activos',    'orden' => 2],
                    ['nombre' => 'Órdenes de Servicio',  'link' => '/operaciones/ordenes-servicio',                      'orden' => 3],
                    ['nombre' => 'Combustible',          'link' => '/operaciones/vehiculos/combustible',                 'orden' => 4],
                    ['nombre' => 'Mantenimientos',       'link' => '/operaciones/vehiculos/mantenimientos',              'orden' => 5],
                    ['nombre' => 'Rendimiento',          'link' => '/operaciones/vehiculos/rendimiento',                 'orden' => 6],
                    ['nombre' => 'Costo por KM',         'link' => '/operaciones/vehiculos/costo-km',                   'orden' => 7],
                    ['nombre' => 'Análisis Mantenimiento','link' => '/operaciones/reportes/analisis-mantenimiento',      'orden' => 8],
                ],
            ],
            [
                'nombre'      => 'Finanzas',
                'controlador' => null,
                'icono'       => 'ChartBarIcon',
                'orden'       => 5,
                'subItems'    => [
                    ['nombre' => 'Cumplimiento Objetivos',   'link' => '/finanzas/cumplimiento-objetivos',   'orden' => 1],
                    ['nombre' => 'Control Notas de Crédito', 'link' => '/finanzas/control-notas-credito',    'orden' => 2],
                ],
            ],
            [
                'nombre'      => 'Reportes',
                'controlador' => null,
                'icono'       => 'DocumentChartBarIcon',
                'orden'       => 6,
                'subItems'    => [
                    ['nombre' => 'Estado de Resultados', 'link' => '/reportes/estado-resultados',  'orden' => 1],
                    ['nombre' => 'Utilidad Operativa',   'link' => '/reportes/utilidad-operativa', 'orden' => 2],
                ],
            ],
        ];

        foreach ($menu as $menuItem) {
            $subItems = $menuItem['subItems'] ?? [];
            unset($menuItem['subItems']);

            $menuItem['estatus']    = 1;
            $menuItem['created_at'] = now();
            $menuItem['updated_at'] = now();

            $menuId = DB::table('menu')->insertGetId($menuItem);

            foreach ($subItems as $sub) {
                DB::table('submenu')->insert([
                    'idmenu'     => $menuId,
                    'nombre'     => $sub['nombre'],
                    'link'       => $sub['link'],
                    'orden'      => $sub['orden'],
                    'estatus'    => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Menú de navegación creado correctamente.');
    }
}
