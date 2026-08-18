<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlotillaCatRefaccionesSeeder extends Seeder
{
    public function run(): void
    {
        $refacciones = [
            // ── Aceites y lubricantes
            ['nombre' => 'Aceite de motor 5W-30',    'categoria' => 'aceites',      'unidad_medida' => 'litro',  'costo_promedio' => 85.00],
            ['nombre' => 'Aceite de motor 10W-40',   'categoria' => 'aceites',      'unidad_medida' => 'litro',  'costo_promedio' => 80.00],
            ['nombre' => 'Aceite de transmisión',    'categoria' => 'aceites',      'unidad_medida' => 'litro',  'costo_promedio' => 120.00],
            ['nombre' => 'Líquido de frenos DOT 4',  'categoria' => 'frenos',       'unidad_medida' => 'litro',  'costo_promedio' => 90.00],
            ['nombre' => 'Líquido refrigerante',     'categoria' => 'refrigeracion','unidad_medida' => 'litro',  'costo_promedio' => 75.00],
            ['nombre' => 'Aceite diferencial',       'categoria' => 'aceites',      'unidad_medida' => 'litro',  'costo_promedio' => 130.00],

            // ── Filtros
            ['nombre' => 'Filtro de aceite',         'categoria' => 'filtros',      'unidad_medida' => 'pieza',  'costo_promedio' => 120.00],
            ['nombre' => 'Filtro de aire',           'categoria' => 'filtros',      'unidad_medida' => 'pieza',  'costo_promedio' => 180.00],
            ['nombre' => 'Filtro de combustible',    'categoria' => 'filtros',      'unidad_medida' => 'pieza',  'costo_promedio' => 150.00],
            ['nombre' => 'Filtro de cabina/habitáculo','categoria'=> 'filtros',     'unidad_medida' => 'pieza',  'costo_promedio' => 200.00],

            // ── Frenos
            ['nombre' => 'Balatas delanteras',       'categoria' => 'frenos',       'unidad_medida' => 'juego',  'costo_promedio' => 350.00],
            ['nombre' => 'Balatas traseras',         'categoria' => 'frenos',       'unidad_medida' => 'juego',  'costo_promedio' => 280.00],
            ['nombre' => 'Disco de freno delantero', 'categoria' => 'frenos',       'unidad_medida' => 'pieza',  'costo_promedio' => 800.00],
            ['nombre' => 'Disco de freno trasero',   'categoria' => 'frenos',       'unidad_medida' => 'pieza',  'costo_promedio' => 700.00],

            // ── Neumáticos
            ['nombre' => 'Llanta',                   'categoria' => 'neumaticos',   'unidad_medida' => 'pieza',  'costo_promedio' => 1800.00],
            ['nombre' => 'Llanta de refacción',      'categoria' => 'neumaticos',   'unidad_medida' => 'pieza',  'costo_promedio' => 1800.00],
            ['nombre' => 'Válvula de llanta',        'categoria' => 'neumaticos',   'unidad_medida' => 'pieza',  'costo_promedio' => 25.00],

            // ── Eléctrico
            ['nombre' => 'Batería',                  'categoria' => 'electrico',    'unidad_medida' => 'pieza',  'costo_promedio' => 1200.00],
            ['nombre' => 'Bujías',                   'categoria' => 'electrico',    'unidad_medida' => 'juego',  'costo_promedio' => 400.00],
            ['nombre' => 'Cables de bujía',          'categoria' => 'electrico',    'unidad_medida' => 'juego',  'costo_promedio' => 350.00],
            ['nombre' => 'Alternador',               'categoria' => 'electrico',    'unidad_medida' => 'pieza',  'costo_promedio' => 2500.00],
            ['nombre' => 'Motor de arranque',        'categoria' => 'electrico',    'unidad_medida' => 'pieza',  'costo_promedio' => 2200.00],

            // ── Distribución y motor
            ['nombre' => 'Banda de distribución',   'categoria' => 'motor',        'unidad_medida' => 'pieza',  'costo_promedio' => 600.00],
            ['nombre' => 'Kit de distribución',     'categoria' => 'motor',        'unidad_medida' => 'juego',  'costo_promedio' => 1500.00],
            ['nombre' => 'Banda de accesorios',     'categoria' => 'motor',        'unidad_medida' => 'pieza',  'costo_promedio' => 250.00],
            ['nombre' => 'Bujía de encendido',      'categoria' => 'motor',        'unidad_medida' => 'pieza',  'costo_promedio' => 120.00],

            // ── Suspensión y dirección
            ['nombre' => 'Amortiguador delantero',  'categoria' => 'suspension',   'unidad_medida' => 'pieza',  'costo_promedio' => 1200.00],
            ['nombre' => 'Amortiguador trasero',    'categoria' => 'suspension',   'unidad_medida' => 'pieza',  'costo_promedio' => 1000.00],
            ['nombre' => 'Terminal de dirección',   'categoria' => 'suspension',   'unidad_medida' => 'pieza',  'costo_promedio' => 350.00],
            ['nombre' => 'Rótula',                  'categoria' => 'suspension',   'unidad_medida' => 'pieza',  'costo_promedio' => 400.00],
            ['nombre' => 'Caja de dirección',       'categoria' => 'suspension',   'unidad_medida' => 'pieza',  'costo_promedio' => 3500.00],

            // ── Refrigeración
            ['nombre' => 'Termostato',              'categoria' => 'refrigeracion','unidad_medida' => 'pieza',  'costo_promedio' => 250.00],
            ['nombre' => 'Bomba de agua',           'categoria' => 'refrigeracion','unidad_medida' => 'pieza',  'costo_promedio' => 800.00],
            ['nombre' => 'Radiador',                'categoria' => 'refrigeracion','unidad_medida' => 'pieza',  'costo_promedio' => 3000.00],
        ];

        $now = now();
        foreach ($refacciones as &$ref) {
            $ref['activo']     = 1;
            $ref['created_at'] = $now;
            $ref['updated_at'] = $now;
        }

        DB::table('flotilla_cat_refacciones')->insert($refacciones);
    }
}
