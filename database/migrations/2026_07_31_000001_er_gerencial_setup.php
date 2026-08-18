<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * E.R. Gerencial setup:
 *   1. Composite indexes on sincronizador_bdd for BI query performance.
 *   2. Menu group "E.R. Gerencial" under Finanzas with Dashboard + ER items.
 *   3. Permissions for finance/report users.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. PERFORMANCE INDEXES ──────────────────────────────────────────
        Schema::table('sincronizador_bdd', function (Blueprint $table) {
            // Core ER query: filter by year+period, group by grupoer
            if (!$this->indexExists('sincronizador_bdd', 'idx_er_ejercicio_periodo_grupo')) {
                $table->index(['Ejercicio', 'Periodo', 'GrupoER'], 'idx_er_ejercicio_periodo_grupo');
            }
            // Drill-down by negocio
            if (!$this->indexExists('sincronizador_bdd', 'idx_er_negocio_periodo')) {
                $table->index(['Ejercicio', 'Periodo', 'idnegocio'], 'idx_er_negocio_periodo');
            }
            // Drill-down by sucursal
            if (!$this->indexExists('sincronizador_bdd', 'idx_er_sucursal_periodo')) {
                $table->index(['Ejercicio', 'Periodo', 'Sucursal'], 'idx_er_sucursal_periodo');
            }
            // Dashboard trend (monthly aggregation)
            if (!$this->indexExists('sincronizador_bdd', 'idx_er_importeer')) {
                $table->index(['Ejercicio', 'Periodo', 'GrupoER', 'Negocio'], 'idx_er_importeer');
            }
        });

        // ─── 2. MENU GROUP "E.R. GERENCIAL" under Finanzas (idmenu=5) ────────
        $idMenuFinanzas = 5;
        $now = now();

        // Check if already exists
        if (DB::table('submenu')->where('nombre', 'E.R. Gerencial')->where('idmenu', $idMenuFinanzas)->exists()) {
            return;
        }

        $idPadre = DB::table('submenu')->insertGetId([
            'idmenu'          => $idMenuFinanzas,
            'idsubmenu_padre' => null,
            'nombre'          => 'E.R. Gerencial',
            'link'            => null,
            'orden'           => 5,
            'estatus'         => 1,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $items = [
            ['nombre' => 'Dashboard Financiero', 'link' => '/finanzas/er/dashboard', 'orden' => 1],
            ['nombre' => 'Estado de Resultados', 'link' => '/reportes/estado-resultados', 'orden' => 2],
        ];

        foreach ($items as $item) {
            DB::table('submenu')->insert([
                'idmenu'          => $idMenuFinanzas,
                'idsubmenu_padre' => $idPadre,
                'nombre'          => $item['nombre'],
                'link'            => $item['link'],
                'orden'           => $item['orden'],
                'estatus'         => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // ─── 3. PERMISSIONS ─────────────────────────────────────────────────
        // Grant access to users who already have /reportes/estado-resultados or /finanzas/*
        $newPaths = ['/finanzas/er/dashboard', '/reportes/estado-resultados'];
        $existingPaths = ['/reportes/estado-resultados', '/finanzas/cumplimiento-objetivos'];

        $usersWithFinanzas = DB::table('user_permissions')
            ->whereIn('permission_path', $existingPaths)
            ->pluck('user_id')
            ->unique();

        foreach ($usersWithFinanzas as $userId) {
            foreach ($newPaths as $path) {
                DB::table('user_permissions')->insertOrIgnore([
                    'user_id'         => $userId,
                    'permission_path' => $path,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }

        // Also grant to superadmin (user_id=1 if exists)
        $superadmin = DB::table('users')->orderBy('id')->first();
        if ($superadmin) {
            foreach ($newPaths as $path) {
                DB::table('user_permissions')->insertOrIgnore([
                    'user_id'         => $superadmin->id,
                    'permission_path' => $path,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove indexes
        Schema::table('sincronizador_bdd', function (Blueprint $table) {
            foreach ([
                'idx_er_ejercicio_periodo_grupo',
                'idx_er_negocio_periodo',
                'idx_er_sucursal_periodo',
                'idx_er_importeer',
            ] as $idx) {
                if ($this->indexExists('sincronizador_bdd', $idx)) {
                    $table->dropIndex($idx);
                }
            }
        });

        // Remove menu items
        $padre = DB::table('submenu')->where('nombre', 'E.R. Gerencial')->first();
        if ($padre) {
            DB::table('submenu')->where('idsubmenu_padre', $padre->id)->delete();
            DB::table('submenu')->where('id', $padre->id)->delete();
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
