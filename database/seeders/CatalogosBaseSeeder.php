<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosBaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Departamentos ────────────────────────────────────────────────────
        $this->insertCatalogo('cat_departamentos', [
            'Administración', 'Operaciones', 'Logística', 'Recursos Humanos',
            'Finanzas', 'Sistemas', 'Mantenimiento', 'Ventas', 'Dirección General',
        ]);

        // ─── Puestos ──────────────────────────────────────────────────────────
        $this->insertCatalogo('cat_puestos', [
            'Director General', 'Gerente', 'Coordinador', 'Supervisor',
            'Chofer', 'Auxiliar Administrativo', 'Analista', 'Mecánico',
            'Ayudante General', 'Contador',
        ]);

        // ─── Estado Civil ─────────────────────────────────────────────────────
        $this->insertCatalogo('cat_estado_civil', [
            'Soltero(a)', 'Casado(a)', 'Divorciado(a)', 'Viudo(a)', 'Unión Libre',
        ]);

        // ─── Condiciones ──────────────────────────────────────────────────────
        $this->insertCatalogo('cat_condiciones', [
            'Nueva', 'Buena', 'Regular', 'Deteriorada', 'Fuera de servicio',
        ]);

        // ─── Tipos de Activos Fijos ───────────────────────────────────────────
        $this->insertCatalogo('cat_tipos_activos_fijos', [
            'Vehículo', 'Equipo de Cómputo', 'Mobiliario', 'Herramienta',
            'Equipo de Oficina', 'Inmueble', 'Otro',
        ]);

        // ─── Estatus de Activos Fijos ─────────────────────────────────────────
        $this->insertCatalogo('cat_estatus_activos_fijos', [
            'Activo', 'En Mantenimiento', 'Dado de Baja', 'En Resguardo', 'Robado/Siniestrado',
        ]);

        // ─── Años ─────────────────────────────────────────────────────────────
        $anios = [];
        for ($y = 2000; $y <= 2026; $y++) {
            $anios[] = (string)$y;
        }
        $this->insertCatalogo('cat_anios', $anios);

        // ─── Tipos de Vehículo ────────────────────────────────────────────────
        $this->insertCatalogo('cat_tipo_vehiculo', [
            'Automóvil', 'Camioneta Pickup', 'Camión', 'Motocicleta',
            'SUV', 'Van/Minivan', 'Tractocamión', 'Remolque',
        ]);

        // ─── Tipos de Combustible ─────────────────────────────────────────────
        $this->insertCatalogo('cat_tipos_combustible', [
            'Gasolina Magna', 'Gasolina Premium', 'Diésel', 'Gas Natural', 'Eléctrico',
        ]);

        // ─── Tipos de Transmisión ─────────────────────────────────────────────
        $this->insertCatalogo('cat_tipos_transmision', [
            'Manual', 'Automática', 'Semiautomática', 'CVT',
        ]);

        // ─── Tipos de Cobertura de Seguro ─────────────────────────────────────
        $this->insertCatalogo('cat_tipo_cobertura_seguro', [
            'Responsabilidad Civil', 'Daños Materiales', 'Robo Total',
            'Cobertura Amplia', 'Solo Legal',
        ]);

        // ─── Marcas de Vehículo ───────────────────────────────────────────────
        $this->insertCatalogo('cat_marcas', [
            'Chevrolet', 'Ford', 'Nissan', 'Toyota', 'Volkswagen',
            'Dodge', 'RAM', 'International', 'Kenworth', 'Freightliner',
            'Mercedes-Benz', 'Volvo', 'Hino',
        ]);

        // ─── Tipos de Proveedor ───────────────────────────────────────────────
        $this->insertCatalogos('cat_tipos_proveedor', [
            ['nombre' => 'Combustible',    'descripcion' => 'Estaciones de gasolina y diésel'],
            ['nombre' => 'Taller',         'descripcion' => 'Talleres mecánicos y de servicio'],
            ['nombre' => 'Refacciones',    'descripcion' => 'Proveedores de refacciones y partes'],
            ['nombre' => 'Llantas',        'descripcion' => 'Proveedores de llantas y servicios relacionados'],
            ['nombre' => 'Otros',          'descripcion' => 'Otros tipos de proveedor'],
        ]);

        // ─── Tipos de Formato RH ──────────────────────────────────────────────
        $this->insertCatalogos('cat_tipos_formato_rh', [
            ['nombre' => 'Contrato',       'descripcion' => 'Contratos de trabajo'],
            ['nombre' => 'Reglamento',     'descripcion' => 'Reglamentos internos'],
            ['nombre' => 'Evaluación',     'descripcion' => 'Formatos de evaluación de desempeño'],
            ['nombre' => 'Capacitación',   'descripcion' => 'Materiales de capacitación'],
            ['nombre' => 'Otros',          'descripcion' => 'Otros documentos RH'],
        ]);

        $this->command->info('Catálogos base insertados correctamente.');
    }

    private function insertCatalogo(string $tabla, array $nombres): void
    {
        $existingCount = DB::table($tabla)->count();
        if ($existingCount > 0) {
            $this->command->info("  '{$tabla}' ya tiene datos, omitiendo.");
            return;
        }

        $rows = array_map(fn($nombre) => [
            'nombre'     => $nombre,
            'estatus'    => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $nombres);

        DB::table($tabla)->insert($rows);
        $this->command->info("  '{$tabla}': " . count($rows) . " registros insertados.");
    }

    private function insertCatalogos(string $tabla, array $rows): void
    {
        $existingCount = DB::table($tabla)->count();
        if ($existingCount > 0) {
            $this->command->info("  '{$tabla}' ya tiene datos, omitiendo.");
            return;
        }

        $data = array_map(fn($row) => array_merge($row, [
            'estatus'    => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]), $rows);

        DB::table($tabla)->insert($data);
        $this->command->info("  '{$tabla}': " . count($data) . " registros insertados.");
    }
}
