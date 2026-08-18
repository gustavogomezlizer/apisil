<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleService
{
    public function getRoleDefinition(string $role): ?array
    {
        $definition = config("roles.{$role}");

        return is_array($definition) ? $definition : null;
    }

    public function dashboardPathForRole(?string $role): ?string
    {
        if (!$role) {
            return null;
        }

        return $this->getRoleDefinition($role)['dashboard_path'] ?? null;
    }

    /**
     * Expande la lista de permisos de un rol. El valor '*' se reemplaza por
     * todos los permisos disponibles del árbol de menú.
     */
    public function permissionsForRole(string $role): array
    {
        $definition = $this->getRoleDefinition($role);

        if (!$definition) {
            return [];
        }

        $permissions = [];

        foreach ($definition['permissions'] as $item) {
            if ($item === '*') {
                $permissions = array_merge($permissions, $this->allPermissions());
            } else {
                $permissions[] = $item;
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Limpia los permisos del usuario y le otorga los del rol indicado.
     */
    public function applyRoleToUser(int $userId, string $role): void
    {
        $permissions = $this->permissionsForRole($role);

        if (empty($permissions)) {
            return;
        }

        DB::transaction(function () use ($userId, $permissions) {
            DB::table('user_permissions')->where('user_id', $userId)->delete();

            $rows = array_map(fn ($path) => [
                'user_id'         => $userId,
                'permission_path' => $path,
                'created_at'      => now(),
                'updated_at'      => now(),
            ], $permissions);

            DB::table('user_permissions')->insert($rows);
        });
    }

    /**
     * Todos los enlaces base del árbol de menú (submenu con estatus activo).
     */
    public function allBaseLinks(): array
    {
        if (!Schema::hasTable('submenu')) {
            return [];
        }

        return DB::table('submenu')
            ->where('estatus', 1)
            ->whereNotNull('link')
            ->where('link', '!=', '')
            ->pluck('link')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Permisos completos: todos los enlaces base + acciones CRUD + permisos
     * especiales de autorización y pestañas.
     */
    public function allPermissions(): array
    {
        $bases = collect($this->allBaseLinks())
            ->reject(fn ($link) => $link === '/sistemas/configuracion')
            ->values()
            ->all();

        $permissions = $bases;

        foreach ($bases as $base) {
            $permissions[] = "{$base}:create";
            $permissions[] = "{$base}:edit";
            $permissions[] = "{$base}:delete";
        }

        $permissions = array_merge($permissions, [
            '/sistemas/usuarios:permissions',
            '/sistemas/usuarios:permissions:tab:menu',
            '/sistemas/usuarios:permissions:tab:actions',
            '/sistemas/usuarios:permissions:tab:authorizations',
            '/sistemas/usuarios:permissions:tab:special',
            '/operaciones/ordenes-servicio:authorize',
            '/operaciones/operaciones-usuarios-sucursal:authorize',
            '/flotilla/mantenimientos:autorizar',
        ]);

        return array_values(array_unique($permissions));
    }
}
