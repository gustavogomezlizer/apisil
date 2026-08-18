<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class MenuService
{
    protected ?bool $submenuHasParentColumn = null;

    public function buildMenuForUser($userId = null): array
    {
        return [[
            'title' => 'Menu',
            'items' => $this->buildItems($userId)
        ]];
    }

    protected function buildItems($userId): Collection
    {
        // 1️⃣ Permisos del usuario
        $permissions = $this->getPermissionsForUser($userId);

        // Configuración global: si está activa, se ocultan las opciones de menú
        // sin permiso (incluyendo carpetas sin ningún acceso en todos los niveles).
        $hideInaccessible = $this->getSetting('ocultar_menu_sin_permiso') === '1';

        // 2️⃣ Dashboard fijo (siempre visible)
        $dashboard = collect([
            [
                'name'     => 'Dashboard',
                'path'     => '/',
                'iconName' => 'HomeIcon',
            ]
        ]);

        $menuItems = $this->getMenu()->map(function ($item) use ($permissions, $hideInaccessible) {

            // 2️⃣ Submenús permitidos (recursivo)
            $subitems = $this->buildSubMenuTreeWithPermissions($item->id, $permissions, null, $hideInaccessible);

            // 3️⃣ Permiso del menú padre
            $hasMenuPermission = in_array($item->controlador, $permissions);

            // 4️⃣ Si no tiene permiso ni en menú ni en submenús → no se muestra
            if (!$hasMenuPermission && $subitems->isEmpty()) {
                return null;
            }

            $menuItem = [
                'name'     => $item->nombre,
                'iconName' => $item->icono,
            ];

            if (!empty($item->descripcion)) {
                $menuItem['description'] = $item->descripcion;
            }

            // Solo incluye path si el usuario tiene permiso del padre.
            if ($hasMenuPermission && !empty($item->controlador)) {
                $menuItem['path'] = $item->controlador;
            }

            // 5️⃣ Solo agregar subItems si existen
            if ($subitems->isNotEmpty()) {
                $menuItem['subItems'] = $subitems;
            }

            return $menuItem;

        })->filter()->values(); // elimina nulls

        return $dashboard->merge($menuItems)->values();
    }

    public function getPermissionsForUser($userId): array
    {
        return DB::table('user_permissions')
            ->where('user_id', $userId)
            ->pluck('permission_path')
            ->toArray();
    }

    protected function getMenu(): Collection
    {
        return DB::table('menu')
            ->where('estatus', 1)
            ->orderBy('orden')
            ->get();
    }

    protected function getSubMenu($menuId, $userId = null): Collection
    {
        return DB::table('submenu')
            ->where('idmenu', $menuId)
            ->where('estatus', 1)
            ->when($this->hasSubmenuParentColumn(), function ($query) {
                $query->whereNull('idsubmenu_padre');
            })
            ->orderBy('orden')
            ->get();
    }

    protected function getSubMenuByParent($menuId, $parentId = null): Collection
    {
        // Si la columna de jerarquía no existe, solo soportamos 1 nivel de submenú.
        // Evita recursión infinita al pedir niveles hijos.
        if (!$this->hasSubmenuParentColumn() && $parentId !== null) {
            return collect();
        }

        $query = DB::table('submenu')
            ->where('idmenu', $menuId)
            ->where('estatus', 1);

        if ($this->hasSubmenuParentColumn()) {
            if ($parentId === null) {
                $query->whereNull('idsubmenu_padre');
            } else {
                $query->where('idsubmenu_padre', $parentId);
            }
        }

        return $query->orderBy('orden')->get();
    }

    protected function buildSubMenuTreeWithPermissions($menuId, array $permissions, $parentId = null, bool $hideInaccessible = false): Collection
    {
        $subMenus = $this->getSubMenuByParent($menuId, $parentId);

        return $subMenus->map(function ($subitem) use ($menuId, $permissions, $hideInaccessible) {
            $children = $this->buildSubMenuTreeWithPermissions($menuId, $permissions, $subitem->id, $hideInaccessible);
            $hasOwnPermission = !empty($subitem->link) && in_array($subitem->link, $permissions);

            // Nodo sin permiso propio ni hijos permitidos:
            //  - Con link → se requiere permiso para acceder, se oculta.
            //  - Sin link (carpeta/placeholder) → se muestra sin acción.
            //
            // Con `ocultar_menu_sin_permiso` activo también se ocultan las
            // carpetas sin ningún acceso (y por tanto sus niveles siguientes).
            if (!$hasOwnPermission && $children->isEmpty() && ($hideInaccessible || !empty($subitem->link))) {
                return null;
            }

            $node = [
                'name' => $subitem->nombre,
            ];

            if (!empty($subitem->descripcion)) {
                $node['description'] = $subitem->descripcion;
            }

            if ($hasOwnPermission) {
                $node['path'] = $subitem->link;
            }

            if ($children->isNotEmpty()) {
                $node['subItems'] = $children->values();
            }

            return $node;
        })->filter()->values();
    }

    protected function buildSubMenuTreeForOptions($menuId, $parentId = null): Collection
    {
        $subMenus = $this->getSubMenuByParent($menuId, $parentId);

        return $subMenus->map(function ($subitem) use ($menuId) {
            $children = $this->buildSubMenuTreeForOptions($menuId, $subitem->id);

            $node = [
                'name' => $subitem->nombre,
                'path' => $subitem->link,
            ];

            if (!empty($subitem->descripcion)) {
                $node['description'] = $subitem->descripcion;
            }

            if ($children->isNotEmpty()) {
                $node['subItems'] = $children->values();
            }

            return $node;
        })->values();
    }

    protected function hasSubmenuParentColumn(): bool
    {
        if ($this->submenuHasParentColumn !== null) {
            return $this->submenuHasParentColumn;
        }

        $this->submenuHasParentColumn = Schema::hasColumn('submenu', 'idsubmenu_padre');
        return $this->submenuHasParentColumn;
    }

    protected function getSetting(string $key, $default = null)
    {
        if (!Schema::hasTable('system_settings')) {
            return $default;
        }

        $row = DB::table('system_settings')->where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    public function getMenuOpciones($userId = null): array
    {
        $items = $this->getMenu()->map(function ($item) {
            $subitems = $this->buildSubMenuTreeForOptions($item->id);

            $menuItem = [
                'name'     => $item->nombre,
                'path'     => $item->controlador,
                'iconName' => $item->icono,
            ];

            if (!empty($item->descripcion)) {
                $menuItem['description'] = $item->descripcion;
            }

            if ($subitems->isNotEmpty()) {
                $menuItem['subItems'] = $subitems->values();
            }

            return $menuItem;
        })->values();

        return [
            'menu' => [[
                'title' => 'Menú',
                'items' => $items,
            ]]
        ];
    }
}