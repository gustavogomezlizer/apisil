<?php

// Roles del sistema.
// 'dashboard_path' define a qué pantalla se redirige al iniciar sesión.
// 'permissions'  puede contener rutas de permiso concretas o '*' (todos los
// permisos del árbol de menú + acciones CRUD + autorizaciones especiales).

return [
    'recursos_humanos' => [
        'label'          => 'Recursos Humanos',
        'dashboard_path' => '/rh/dashboard',
        'permissions'    => [
            '/rh/dashboard',
            '/rh/movimientos',
            '/rh/expediente',
            '/rh/alertas',
            '/rh/reportes',
            '/catalogos/empleados',
            '/catalogos/empleados:create',
            '/catalogos/empleados:edit',
            '/catalogos/empleados:delete',
        ],
    ],

    'directivo' => [
        'label'          => 'Directivo',
        'dashboard_path' => '/finanzas/er/dashboard',
        'permissions'    => ['*'],
    ],

    'operaciones_combustible' => [
        'label'          => 'Operaciones Combustible',
        'dashboard_path' => '/combustible/dashboard',
        'permissions'    => [
            '/combustible/dashboard',
            '/combustible/presupuestos',
            '/combustible/reportes',
            '/combustible/alertas',
            '/operaciones/vehiculos/combustible',
            '/operaciones/vehiculos/combustible:create',
            '/operaciones/vehiculos/combustible:edit',
            '/operaciones/vehiculos/combustible:delete',
            '/operaciones/vehiculos/rendimiento',
            '/operaciones/vehiculos/costo-km',
            '/catalogos/sucursales',
        ],
    ],

    'operaciones_mantenimiento' => [
        'label'          => 'Operaciones Mantenimiento',
        'dashboard_path' => '/flotilla/dashboard',
        'permissions'    => [
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
            '/flotilla/mantenimientos:autorizar',
            '/operaciones/ordenes-servicio',
            '/operaciones/ordenes-servicio:create',
            '/operaciones/ordenes-servicio:edit',
            '/operaciones/ordenes-servicio:delete',
            '/operaciones/ordenes-servicio:authorize',
            '/operaciones/control-activos/asignacion-activos',
            '/operaciones/control-activos/asignacion-activos:create',
            '/operaciones/control-activos/asignacion-activos:edit',
            '/operaciones/control-activos/asignacion-activos:delete',
            '/operaciones/reportes/analisis-mantenimiento',
            '/catalogos/activos-fijos',
            '/catalogos/activos-fijos:create',
            '/catalogos/activos-fijos:edit',
            '/catalogos/activos-fijos:delete',
            '/catalogos/aseguradoras',
            '/catalogos/aseguradoras:create',
            '/catalogos/aseguradoras:edit',
            '/catalogos/aseguradoras:delete',
            '/catalogos/talleres',
            '/catalogos/talleres:create',
            '/catalogos/talleres:edit',
            '/catalogos/talleres:delete',
            '/catalogos/tipos-servicio',
            '/catalogos/tipos-servicio:create',
            '/catalogos/tipos-servicio:edit',
            '/catalogos/tipos-servicio:delete',
        ],
    ],

    'sistemas' => [
        'label'          => 'Sistemas',
        'dashboard_path' => '/sistemas/usuarios',
        'permissions'    => ['*', '/sistemas/configuracion'],
    ],
];
