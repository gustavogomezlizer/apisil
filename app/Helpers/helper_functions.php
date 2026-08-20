<?php

use App\Http\Controllers\UserController;

function APP_NOMBRE()
{
    return "LIZER ADMINISTRACION";
}

function APP_LOGO()
{
    return asset('assets/img/logo/logo.jpg');
}

function function_a()
{
    $menu = new UserController();
    return $menu->getMenu();
}

function getSubMenu($pId)
{
    $menu = new UserController();
    return $menu->getSubMenu($pId);
}

function GET_LOGIN_ID()
{
    return Auth::user()->id;
}

/**
 * IDs de los tipos de activo fijo cuyo nombre es "Unidad".
 * Los módulos de Flotilla y Combustible operan únicamente sobre este tipo.
 */
function ACTIVO_FIJO_TIPO_UNIDAD_IDS(): array
{
    static $ids = null;

    if ($ids === null) {
        $ids = DB::table('cat_tipos_activos_fijos')
            ->where('nombre', 'Unidad')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
    }

    return $ids;
}

/**
 * ID (único) del tipo de activo fijo "Unidad" (null si no existe).
 */
function ACTIVO_FIJO_TIPO_UNIDAD_ID(): ?int
{
    $ids = ACTIVO_FIJO_TIPO_UNIDAD_IDS();

    return $ids[0] ?? null;
}

/**
 * Indica si un activo fijo (por idtipoactivo) pertenece al tipo "Unidad".
 */
function ACTIVO_FIJO_ES_TIPO_UNIDAD($idTipoActivo): bool
{
    return in_array((int) $idTipoActivo, ACTIVO_FIJO_TIPO_UNIDAD_IDS(), true);
}

/**
 * Indica si un activo fijo (por su ID en activos_fijos) es de tipo "Unidad".
 * Resuelve el idtipoactivo internamente.
 */
function ACTIVO_FIJO_ES_UNIDAD_POR_ID($idActivoFijo): bool
{
    $idtipoactivo = DB::table('activos_fijos')
        ->where('id', $idActivoFijo)
        ->value('idtipoactivo');

    return $idtipoactivo && ACTIVO_FIJO_ES_TIPO_UNIDAD($idtipoactivo);
}