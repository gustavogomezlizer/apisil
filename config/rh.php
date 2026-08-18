<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Estatus de empleados (Recursos Humanos)
    |--------------------------------------------------------------------------
    |
    | La columna empleados.estatus guarda el NOMBRE del estatus
    | (cat_estatus_rh.nombre). Aquí se define cuáles representan un
    | empleado activo, para que todas las consultas queden unificadas.
    |
    */

    'active_statuses' => ['Activo', 'Prueba', 'Reingreso'],
];
