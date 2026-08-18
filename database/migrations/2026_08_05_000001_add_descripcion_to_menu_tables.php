<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega una descripción a cada opción del menú para mostrarla en las fichas
 * del panel de navegación (drill-down) del sidebar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu', function (Blueprint $table) {
            $table->string('descripcion', 255)->nullable()->after('nombre');
        });

        Schema::table('submenu', function (Blueprint $table) {
            $table->string('descripcion', 255)->nullable()->after('nombre');
        });

        $menuDescripciones = [
            1 => 'Administración de usuarios y asignación de permisos del sistema.',
            2 => 'Administración del personal: empleados, movimientos, expediente y reportes.',
            3 => 'Catálogos maestros del sistema: sucursales, aseguradoras, proveedores y más.',
            4 => 'Captura y consulta de la información operativa de la empresa.',
            5 => 'Información financiera: estado de resultados y notas de crédito.',
            6 => 'Reportes generales del sistema.',
            7 => 'Indicadores y tableros de control para la toma de decisiones de la dirección.',
            8 => 'Documentos oficiales y expedientes de la organización.',
        ];

        foreach ($menuDescripciones as $id => $descripcion) {
            DB::table('menu')->where('id', $id)->update(['descripcion' => $descripcion, 'updated_at' => now()]);
        }

        $submenuDescripciones = [
            1  => 'Alta, edición y asignación de permisos de los usuarios.',
            2  => 'Catálogo de empleados de la organización.',
            3  => 'Movimientos de altas, bajas y transferencias de activos fijos.',
            4  => 'Control de documentos de los empleados.',
            5  => 'Formatos oficiales de recursos humanos.',
            6  => 'Calendario de eventos y actividades.',
            7  => 'Catálogo de activos fijos: vehículos, equipos y más.',
            8  => 'Catálogo de aseguradoras.',
            9  => 'Catálogo de sucursales de la organización.',
            10 => 'Catálogo de negocios.',
            11 => 'Catálogo de adendum.',
            12 => 'Catálogo de tipos de servicio.',
            13 => 'Catálogo de proveedores y talleres.',
            14 => 'Consulta y administración de los vehículos de la flotilla.',
            15 => 'Asignación de activos a empleados o áreas.',
            16 => 'Registro y control de las órdenes de servicio.',
            22 => 'Seguimiento al cumplimiento de objetivos.',
            23 => 'Control de notas de crédito.',
            24 => 'Reporte del estado de resultados.',
            56 => 'Operaciones diarias: vehículos, mantenimiento, combustible y órdenes de servicio.',
            57 => 'Indicadores generales del desempeño de la flotilla.',
            58 => 'Registro y control del mantenimiento preventivo.',
            59 => 'Registro y control del mantenimiento correctivo.',
            60 => 'Plantillas de mantenimiento programado.',
            61 => 'Registro de lecturas de kilometraje de las unidades.',
            62 => 'Documentos de la flotilla.',
            63 => 'Alertas de mantenimiento y flotilla.',
            64 => 'Bitácora de eventos de la flotilla.',
            65 => 'Reportes de mantenimiento y flotilla.',
            66 => 'Configuración de las unidades de la flotilla.',
            67 => 'Control de combustible: cargas, presupuestos, rendimiento y costos.',
            68 => 'Indicadores del consumo de combustible.',
            69 => 'Registro de las cargas de combustible de los vehículos.',
            70 => 'Presupuestos de combustible.',
            71 => 'Reportes de consumo y costos de combustible.',
            72 => 'Alertas y validaciones de las cargas de combustible.',
            73 => 'Rendimiento por vehículo: kilómetros por litro.',
            74 => 'Costo por kilómetro por vehículo.',
            75 => 'Análisis de costos de mantenimiento y combustible.',
            76 => 'Gestión del personal: movimientos, expediente y alertas.',
            77 => 'Indicadores de recursos humanos.',
            78 => 'Movimientos del personal: altas, bajas e incidencias.',
            79 => 'Expediente digital de los empleados.',
            80 => 'Alertas de recursos humanos.',
            81 => 'Reportes de recursos humanos.',
            82 => 'Estado de resultados gerencial.',
            83 => 'Indicadores del área financiera.',
            84 => 'Estado de resultados detallado.',
            85 => 'Estados financieros de la organización.',
            86 => 'Indicadores de desarrollo de recursos humanos.',
            87 => 'Indicadores de desempeño de procesos.',
            88 => 'Indicadores de clientes y experiencias.',
            89 => 'Documentos de constitución de la empresa.',
            90 => 'Documentos fiscales y de cumplimiento.',
            91 => 'Comunicados oficiales de la organización.',
            92 => 'Expediente de recursos humanos.',
            93 => 'Información y documentos bancarios.',
            94 => 'Información de cuentas por cobrar.',
            95 => 'Información de inventarios y costos.',
            96 => 'Activos productivos de la organización.',
            97 => 'Información de cuentas por pagar.',
            98 => 'Administración del área de operaciones.',
            99 => 'Contabilidad del área de operaciones.',
            100 => 'Ventas del área de operaciones.',
            101 => 'Mantenimientos de la flotilla: preventivo, correctivo, plantillas y bitácoras.',
        ];

        foreach ($submenuDescripciones as $id => $descripcion) {
            DB::table('submenu')->where('id', $id)->update(['descripcion' => $descripcion, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('menu', function (Blueprint $table) {
            $table->dropColumn('descripcion');
        });

        Schema::table('submenu', function (Blueprint $table) {
            $table->dropColumn('descripcion');
        });
    }
};
