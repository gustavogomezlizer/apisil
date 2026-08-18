<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * 2026-08-04 changes:
 *   1. Authorization fields on preventive + corrective maintenance tables
 *   2. New flotilla_mantenimiento_servicios table (service detail)
 *   3. Menu restructuring: Gestión Directiva + Expedientes + rename Operaciones→Capturas
 *   4. New permission paths for authorization
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. AUTHORIZATION FIELDS ─────────────────────────────────────────

        Schema::table('flotilla_mantenimiento_preventivo', function (Blueprint $table) {
            if (!Schema::hasColumn('flotilla_mantenimiento_preventivo', 'estatus_autorizacion')) {
                $table->enum('estatus_autorizacion', ['pendiente', 'autorizado', 'rechazado'])
                      ->default('pendiente')
                      ->after('observaciones');
                $table->unsignedBigInteger('idusuario_autoriza')->nullable()->after('estatus_autorizacion');
                $table->dateTime('fecha_autorizacion')->nullable()->after('idusuario_autoriza');
                $table->text('motivo_rechazo')->nullable()->after('fecha_autorizacion');
            }
        });

        Schema::table('flotilla_mantenimiento_correctivo', function (Blueprint $table) {
            if (!Schema::hasColumn('flotilla_mantenimiento_correctivo', 'estatus_autorizacion')) {
                $table->enum('estatus_autorizacion', ['pendiente', 'autorizado', 'rechazado'])
                      ->default('pendiente')
                      ->after('observaciones');
                $table->unsignedBigInteger('idusuario_autoriza')->nullable()->after('estatus_autorizacion');
                $table->dateTime('fecha_autorizacion')->nullable()->after('idusuario_autoriza');
                $table->text('motivo_rechazo')->nullable()->after('fecha_autorizacion');
            }
        });

        // ─── 2. SERVICE DETAIL TABLE ─────────────────────────────────────────

        if (!Schema::hasTable('flotilla_mantenimiento_servicios')) {
            Schema::create('flotilla_mantenimiento_servicios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('idmantenimiento');
                $table->enum('tipo_mantenimiento', ['preventivo', 'correctivo']);
                $table->unsignedBigInteger('idtipo_servicio')->nullable(); // cat_tipos_servicio
                $table->string('descripcion_servicio', 255)->nullable();  // manual text
                $table->decimal('importe', 12, 2)->default(0);
                $table->text('observaciones')->nullable();
                $table->tinyInteger('orden')->default(0);
                $table->timestamps();

                $table->index(['idmantenimiento', 'tipo_mantenimiento'], 'idx_mant_srv');
            });
        }

        // ─── 3. MENU RESTRUCTURING ────────────────────────────────────────────

        $now = now();

        // 3a. Rename existing menu entries
        DB::table('menu')->where('id', 1)->update(['nombre' => 'Acceso', 'orden' => 10, 'updated_at' => $now]);
        DB::table('menu')->where('id', 4)->update(['nombre' => 'Capturas', 'icono' => 'PencilSquareIcon', 'orden' => 4, 'updated_at' => $now]);
        // Deactivate Reportes section (Estado de Resultados moved to Finanzas → E.R. Gerencial)
        DB::table('menu')->where('id', 6)->update(['estatus' => 0, 'updated_at' => $now]);

        // 3b. Create "Gestión Directiva" menu (dashboards for executives)
        if (!DB::table('menu')->where('nombre', 'Gestión Directiva')->exists()) {
            $idGD = DB::table('menu')->insertGetId([
                'nombre'     => 'Gestión Directiva',
                'icono'      => 'PresentationChartLineIcon',
                'orden'      => 1,
                'estatus'    => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $gdItems = [
                ['nombre' => 'EEFF - Finanzas',       'link' => '/finanzas/er/dashboard', 'orden' => 1],
                ['nombre' => 'RRHH - Desarrollo',      'link' => '/rh/dashboard',          'orden' => 2],
                ['nombre' => 'Procesos - Desempeño',   'link' => '/flotilla/dashboard',    'orden' => 3],
                ['nombre' => 'Clientes - Experiencias','link' => null,                     'orden' => 4],
            ];
            foreach ($gdItems as $item) {
                DB::table('submenu')->insert([
                    'idmenu'          => $idGD,
                    'idsubmenu_padre' => null,
                    'nombre'          => $item['nombre'],
                    'link'            => $item['link'],
                    'orden'           => $item['orden'],
                    'estatus'         => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }

        // 3c. Create "Expedientes" menu
        if (!DB::table('menu')->where('nombre', 'Expedientes')->exists()) {
            $idExp = DB::table('menu')->insertGetId([
                'nombre'     => 'Expedientes',
                'icono'      => 'FolderOpenIcon',
                'orden'      => 2,
                'estatus'    => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $expItems = [
                ['nombre' => 'Constitución',                    'link' => null,                          'orden' => 1],
                ['nombre' => 'Impuestos y Otros Cumplimientos', 'link' => null,                          'orden' => 2],
                ['nombre' => 'Comunicados Oficiales',           'link' => null,                          'orden' => 3],
                ['nombre' => 'Recursos Humanos',                'link' => '/rh/expediente',              'orden' => 4],
                ['nombre' => 'Bancos',                          'link' => null,                          'orden' => 6],
                ['nombre' => 'CxC',                             'link' => null,                          'orden' => 7],
                ['nombre' => 'Inventarios y Costos',            'link' => null,                          'orden' => 8],
                ['nombre' => 'Activos Productivos',             'link' => '/catalogos/activos-fijos',    'orden' => 9],
                ['nombre' => 'CxP',                             'link' => null,                          'orden' => 10],
            ];
            foreach ($expItems as $item) {
                DB::table('submenu')->insert([
                    'idmenu'          => $idExp,
                    'idsubmenu_padre' => null,
                    'nombre'          => $item['nombre'],
                    'link'            => $item['link'],
                    'orden'           => $item['orden'],
                    'estatus'         => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }

        // 3d. Update Capturas (ID:4 = former Operaciones) - add placeholder sections
        // The existing Flotilla + Combustible subgroups remain as-is
        $capturasPlaceholders = [
            ['nombre' => 'Administración',  'orden' => 1],
            ['nombre' => 'Contabilidad',    'orden' => 2],
            ['nombre' => 'Ventas',          'orden' => 99],
        ];
        foreach ($capturasPlaceholders as $item) {
            if (!DB::table('submenu')->where('idmenu', 4)->where('nombre', $item['nombre'])->exists()) {
                DB::table('submenu')->insert([
                    'idmenu'          => 4,
                    'idsubmenu_padre' => null,
                    'nombre'          => $item['nombre'],
                    'link'            => null,
                    'orden'           => $item['orden'],
                    'estatus'         => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }

        // ─── 4. AUTHORIZATION PERMISSIONS ────────────────────────────────────

        $authPaths = [
            '/flotilla/mantenimientos:autorizar',
        ];

        // Grant to superadmin (first user)
        $superAdmin = DB::table('users')->orderBy('id')->first();
        if ($superAdmin) {
            foreach ($authPaths as $path) {
                DB::table('user_permissions')->insertOrIgnore([
                    'user_id'         => $superAdmin->id,
                    'permission_path' => $path,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }

        // Also grant to users who already have flotilla admin access
        $flotillaAdmins = DB::table('user_permissions')
            ->where('permission_path', '/flotilla/dashboard')
            ->pluck('user_id');
        foreach ($flotillaAdmins as $userId) {
            foreach ($authPaths as $path) {
                DB::table('user_permissions')->insertOrIgnore([
                    'user_id'         => $userId,
                    'permission_path' => $path,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove authorization columns
        Schema::table('flotilla_mantenimiento_preventivo', function (Blueprint $table) {
            foreach (['estatus_autorizacion', 'idusuario_autoriza', 'fecha_autorizacion', 'motivo_rechazo'] as $col) {
                if (Schema::hasColumn('flotilla_mantenimiento_preventivo', $col)) $table->dropColumn($col);
            }
        });
        Schema::table('flotilla_mantenimiento_correctivo', function (Blueprint $table) {
            foreach (['estatus_autorizacion', 'idusuario_autoriza', 'fecha_autorizacion', 'motivo_rechazo'] as $col) {
                if (Schema::hasColumn('flotilla_mantenimiento_correctivo', $col)) $table->dropColumn($col);
            }
        });

        Schema::dropIfExists('flotilla_mantenimiento_servicios');

        // Reverse menu changes
        DB::table('menu')->where('nombre', 'Gestión Directiva')->delete();
        DB::table('menu')->where('nombre', 'Expedientes')->delete();
        DB::table('menu')->where('id', 4)->update(['nombre' => 'Operaciones', 'icono' => 'TruckIcon']);
        DB::table('menu')->where('id', 6)->update(['estatus' => 1]);
    }
};
