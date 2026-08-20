<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AnalisisMantenimientoExport;

class ReportesController extends Controller
{
    public function listaReporteUtilidadJson(Request $request): array
    {
        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_final'  => ['required', 'date'],
            'tipo'         => ['required', 'string'], // "preventa,devolucion" o "preventa"
            'negocio'      => ['required', 'string'], // "1,2,3"
            'sucursal'     => ['nullable', 'string'], // "0" o id
            'impuestos'    => ['nullable', 'in:0,1'],
        ]);

        $data = [
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_final'  => $validated['fecha_final'],
            'tipo'         => $validated['tipo'],
            'negocio'      => $validated['negocio'],
            'sucursal'     => $validated['sucursal'] ?? '0',
            'impuestos'    => $validated['impuestos'] ?? '0',
        ];

        $periodo = Carbon::parse($data['fecha_inicio'])->format('Ym');

        $empresa = function_exists('GETEMPRESA') ? GETEMPRESA() : null;
        $impuestoCol = ($empresa === '02271106') ? 'p.iva' : 'p.ieps';

        if (($data['sucursal'] ?? '0') === '0') {
            $sql = "
                SELECT
                    datos.nombre_sucursal,
                    datos.idsucursal,
                    SUM(venta_cimpuesto) AS venta_cimpuesto,
                    SUM(costo_cimpuesto) AS costo_cimpuesto,
                    SUM(venta_simpuesto) AS venta_simpuesto,
                    SUM(costo_simpuesto) AS costo_simpuesto,
                    (
                        SELECT SUM(gs.importe)
                        FROM gastos_sucursal gs
                        WHERE gs.periodo = ?
                          AND gs.idsucursal = datos.idsucursal
                          AND FIND_IN_SET(gs.idnegocio, ?)
                    ) AS gastos,
                    (
                        SELECT SUM(ig.importe)
                        FROM ingresos_sucursal ig
                        WHERE ig.periodo = ?
                          AND ig.idsucursal = datos.idsucursal
                          AND FIND_IN_SET(ig.idnegocio, ?)
                    ) AS otros_ingresos,
                    (
                        SELECT rutaslaboradas
                        FROM gastos_sucursal gs
                        WHERE gs.periodo = ?
                          AND gs.idsucursal = datos.idsucursal
                        LIMIT 1
                    ) AS rutaslaboradas,
                    (
                        SELECT cs.orden
                        FROM cat_sucursales cs
                        WHERE cs.id = datos.idsucursal
                    ) AS orden
                FROM (
                    SELECT
                        datos.*,
                        SUM(datos.cantidad_real * datos.precio) AS venta_cimpuesto,
                        SUM(datos.cantidad_real * datos.costo) AS costo_cimpuesto,
                        SUM(datos.cantidad_real * datos.precio_simpuesto) AS venta_simpuesto,
                        SUM(datos.cantidad_real * datos.costouni_simpuesto) AS costo_simpuesto
                    FROM (
                        SELECT
                            p.nombre_sucursal,
                            p.idsucursal,
                            p.iditem,
                            p.tipo,
                            IF(p.tipo='preventa', (p.cantidad_entregado - p.cantidad_rechazado), (p.cantidad_entregado - p.cantidad_rechazado) * -1) AS cantidad_real,
                            IF({$impuestoCol} = 0, 0, (({$impuestoCol}/100)+1)) AS ieps2,
                            p.precio,
                            IF({$impuestoCol} = 0, p.precio, (p.precio / (IF({$impuestoCol} = 0, 0, (({$impuestoCol}/100)+1))))) AS precio_simpuesto,
                            p.costo,
                            IF({$impuestoCol} = 0, p.costo, (p.costo / (IF({$impuestoCol} = 0, 0, (({$impuestoCol}/100)+1))))) AS costouni_simpuesto,
                            (SELECT cp.proveedor FROM cat_productos cp WHERE cp.id = p.iditem) AS proveedor
                        FROM vwInformacionGeneralPedidos p
                        WHERE p.status_principal = 1
                          AND p.status_detalle = 1
                          AND p.fecha BETWEEN ? AND ?
                          AND FIND_IN_SET(p.tipo, ?)
                    ) AS datos
                    WHERE FIND_IN_SET(datos.proveedor, ?)
                    GROUP BY datos.idsucursal, datos.tipo
                ) AS datos
                GROUP BY datos.idsucursal
                ORDER BY orden
            ";

            $bindings = [
                $periodo, $data['negocio'],
                $periodo, $data['negocio'],
                $periodo,
                $data['fecha_inicio'], $data['fecha_final'], $data['tipo'],
                $data['negocio'],
            ];

            $query = collect(DB::connection('mysql_fb')->select($sql, $bindings));
        } else {
            $sql = "
                SELECT
                    datos.ruta_nombre,
                    datos.idsucursal,
                    SUM(venta_cimpuesto) AS venta_cimpuesto,
                    SUM(costo_cimpuesto) AS costo_cimpuesto,
                    SUM(venta_simpuesto) AS venta_simpuesto,
                    SUM(costo_simpuesto) AS costo_simpuesto,
                    (
                        SELECT SUM(gs.importe)
                        FROM gastos_sucursal gs
                        WHERE gs.periodo = ?
                          AND gs.idsucursal = datos.idsucursal
                          AND FIND_IN_SET(gs.idnegocio, ?)
                    ) AS gastos,
                    (
                        SELECT SUM(ig.importe)
                        FROM ingresos_sucursal ig
                        WHERE ig.periodo = ?
                          AND ig.idsucursal = datos.idsucursal
                          AND FIND_IN_SET(ig.idnegocio, ?)
                    ) AS otros_ingresos,
                    (
                        SELECT rutaslaboradas
                        FROM gastos_sucursal gs
                        WHERE gs.periodo = ?
                          AND gs.idsucursal = datos.idsucursal
                        LIMIT 1
                    ) AS rutaslaboradas
                FROM (
                    SELECT
                        datos.*,
                        SUM(datos.cantidad_real * datos.precio) AS venta_cimpuesto,
                        SUM(datos.cantidad_real * datos.costo) AS costo_cimpuesto,
                        SUM(datos.cantidad_real * datos.precio_simpuesto) AS venta_simpuesto,
                        SUM(datos.cantidad_real * datos.costouni_simpuesto) AS costo_simpuesto
                    FROM (
                        SELECT
                            p.nombre_sucursal,
                            p.idsucursal,
                            p.iditem,
                            p.ruta,
                            p.ruta_nombre,
                            p.tipo,
                            IF(p.tipo='preventa', (p.cantidad_entregado - p.cantidad_rechazado), (p.cantidad_entregado - p.cantidad_rechazado) * -1) AS cantidad_real,
                            IF({$impuestoCol} = 0, 0, (({$impuestoCol}/100)+1)) AS ieps2,
                            p.precio,
                            IF({$impuestoCol} = 0, p.precio, (p.precio / (IF({$impuestoCol} = 0, 0, (({$impuestoCol}/100)+1))))) AS precio_simpuesto,
                            p.costo,
                            IF({$impuestoCol} = 0, p.costo, (p.costo / (IF({$impuestoCol} = 0, 0, (({$impuestoCol}/100)+1))))) AS costouni_simpuesto,
                            (SELECT cp.proveedor FROM cat_productos cp WHERE cp.id = p.iditem) AS proveedor
                        FROM vwInformacionGeneralPedidos p
                        WHERE p.status_principal = 1
                          AND p.status_detalle = 1
                          AND p.fecha BETWEEN ? AND ?
                          AND FIND_IN_SET(p.tipo, ?)
                    ) AS datos
                    WHERE FIND_IN_SET(datos.proveedor, ?)
                      AND datos.idsucursal = ?
                    GROUP BY datos.ruta, datos.tipo
                ) AS datos
                GROUP BY datos.ruta
            ";

            $bindings = [
                $periodo, $data['negocio'],
                $periodo, $data['negocio'],
                $periodo,
                $data['fecha_inicio'], $data['fecha_final'], $data['tipo'],
                $data['negocio'], $data['sucursal'],
            ];

            $query = collect(DB::connection('mysql_fb')->select($sql, $bindings));
        }

        $totalVenta = 0.0;

        $query = $query->map(function ($row) use ($data, &$totalVenta) {
            $gastos = (float)($row->gastos ?? 0);
            $otrosIngresos = (float)($row->otros_ingresos ?? 0);
            $rutasLaboradas = (float)($row->rutaslaboradas ?? 0);

            if (($data['sucursal'] ?? '0') !== '0') {
                $row->nombre_sucursal = $row->ruta_nombre ?? '';
                if ($rutasLaboradas > 0) {
                    $gastos /= $rutasLaboradas;
                    $otrosIngresos /= $rutasLaboradas;
                }
            }

            $usarImpuestos = (string)($data['impuestos'] ?? '0') === '1';
            $venta = (float)($usarImpuestos ? ($row->venta_cimpuesto ?? 0) : ($row->venta_simpuesto ?? 0));
            $costo = (float)($usarImpuestos ? ($row->costo_cimpuesto ?? 0) : ($row->costo_simpuesto ?? 0));

            $totalIngresos = $otrosIngresos + $venta;
            $utilidadBruta = $totalIngresos - $costo;
            $utilidadNeta = $utilidadBruta - $gastos;
            $importeMargen = $venta - $costo;
            $porcentajeMargen = $venta == 0 ? 0 : ($importeMargen / $venta) * 100;
            $porcentajeGastos = $venta == 0 ? 0 : ($gastos / $venta) * 100;

            $row->venta = $this->fmt($venta);
            $row->otrosingresos = $this->fmt($otrosIngresos);
            $row->totalingresos = $this->fmt($totalIngresos);
            $row->costo = $this->fmt($costo);
            $row->utilidad_bruta = $this->fmt($utilidadBruta);
            $row->gastos = $this->fmt($gastos);
            $row->porcentaje_gastos = $this->fmt($porcentajeGastos) . '%';
            $row->importe_margen = $this->fmt($importeMargen);
            $row->porcentaje_margen = $this->fmt($porcentajeMargen) . '%';
            $row->utilidad_neta = $this->fmt($utilidadNeta);

            $totalVenta += $venta;

            return $row;
        });

        if (($data['sucursal'] ?? '0') === '0') {
            $query = $query->map(function ($row) use ($totalVenta) {
                $venta = (float)str_replace(',', '', (string)$row->venta);
                $porcentajeParticipacion = $venta <= 0 || $totalVenta <= 0 ? 0 : ($venta / $totalVenta) * 100;
                $row->porcentaje_participacion = $this->fmt($porcentajeParticipacion) . '%';
                return $row;
            });
        } else {
            $query = $query->map(function ($row) use ($totalVenta) {
                $venta = (float)str_replace(',', '', (string)$row->venta);
                $costo = (float)str_replace(',', '', (string)$row->costo);
                $gastos = (float)str_replace(',', '', (string)$row->gastos);

                $otrosIngresosProrrateado = $venta <= 0 || $totalVenta <= 0
                    ? 0
                    : ($venta / $totalVenta) * (float)($row->otros_ingresos ?? 0);

                $porcentajeParticipacion = $venta <= 0 || $totalVenta <= 0
                    ? 0
                    : ($venta / $totalVenta) * 100;

                $totalIngresos = $otrosIngresosProrrateado + $venta;
                $utilidadBruta = $totalIngresos - $costo;
                $utilidadNeta = $utilidadBruta - $gastos;

                $row->otrosingresos = $this->fmt($otrosIngresosProrrateado);
                $row->totalingresos = $this->fmt($totalIngresos);
                $row->utilidad_bruta = $this->fmt($utilidadBruta);
                $row->utilidad_neta = $this->fmt($utilidadNeta);
                $row->porcentaje_participacion = $this->fmt($porcentajeParticipacion) . '%';

                return $row;
            });
        }

        return $query->toArray();
    }

    private function fmt(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    public function getAnalisisMantenimiento(Request $request)
    {
        $query = DB::table('ordenes_servicio as os')
            ->leftJoin('talleres as t', 'os.idtaller', '=', 't.id')
            ->leftJoin('activos_fijos as af', 'os.idunidad', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('ordenes_servicio_detalle as d', 'os.id', '=', 'd.idorden')
            ->leftJoin('cat_tipos_servicio as s', 'd.idservicio', '=', 's.id')
            ->select(
                'os.fechaingreso',
                'os.fechaentrega',
                'os.ordenservicio',
                'os.usuario',
                'os.descripcionunidad',
                'os.kilometrajeunidad',
                'os.estatusorden as estadoordenservicio',
                'd.importe',
                'd.observaciones',
                's.nombre as servicio',
                'd.numeromovimiento',
                't.razonsocial as proveedorservicio',
                'afu.numeroeconomico',
                DB::raw('IFNULL(suc.nombre, os.sucursal) as sucursal')
            )
            ->leftJoin('sucursales as suc', 'os.idsucursal', '=', 'suc.id');

        $query->when($request->fechade, fn($q) => $q->where('os.fechaingreso', '>=', $request->fechade));
        $query->when($request->fechaa, fn($q) => $q->where('os.fechaingreso', '<=', $request->fechaa));
        $query->when($request->idsucursal, fn($q) => $q->where('os.idsucursal', $request->idsucursal));
        $query->when($request->idunidad, fn($q) => $q->where('os.idunidad', $request->idunidad));
        $query->when($request->idservicio, fn($q) => $q->where('d.idservicio', $request->idservicio));
        $query->when($request->idtaller, fn($q) => $q->where('os.idtaller', $request->idtaller));
        $query->when($request->estatus, fn($q) => $q->where('os.estatusorden', $request->estatus));

        $query->orderBy('os.fechaingreso', 'desc')->orderBy('os.id', 'desc');

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getEstadoResultados(Request $request)
    {
        $validated = $request->validate([
            'ejercicio'   => ['required', 'integer'],
            'periodo_ini' => ['required', 'integer', 'min:1', 'max:12'],
            'periodo_fin' => ['required', 'integer', 'min:1', 'max:12'],
            'negocio'     => ['nullable', 'string'],
            'sucursal'    => ['nullable', 'string'],
        ]);

        $query = DB::table('sincronizador_bdd')
            ->select(
                'Concepto as concepto',
                'Negocio as negocio',
                'GrupoER as grupoer',
                DB::raw('SUM(Importe) as importe')
            )
            ->where('Ejercicio', $validated['ejercicio'])
            ->whereBetween('Periodo', [$validated['periodo_ini'], $validated['periodo_fin']]);

        if (!empty($validated['negocio'])) {
            $query->where('Negocio', $validated['negocio']);
        }
        if (!empty($validated['sucursal'])) {
            $query->where('Sucursal', $validated['sucursal']);
        }

        $rows = $query->groupBy('Concepto', 'Negocio', 'GrupoER')->get();

        //return $query->toRawSql();

        return response()->json($rows);
    }

    public function getEstadoResultadosDetalle(Request $request)
    {
        $validated = $request->validate([
            'ejercicio'   => ['required', 'integer'],
            'periodo_ini' => ['required', 'integer', 'min:1', 'max:12'],
            'periodo_fin' => ['required', 'integer', 'min:1', 'max:12'],
            'concepto'    => ['required', 'string'],
            'nivel'       => ['required', 'string', 'in:negocio,sucursal,ruta,records'],
            'grupoer'     => ['nullable', 'string'],
            'negocio'     => ['nullable', 'string'],
            'sucursal'    => ['nullable', 'string'],
            'ruta'        => ['nullable', 'string'],
        ]);

        $query = DB::table('sincronizador_bdd')
            ->select(
                DB::raw('DATE_FORMAT(Fecha, "%Y-%m-%d") as fecha'),
                'Negocio as negocio',
                'Sucursal as sucursal',
                'Ruta as ruta',
                'Concepto as concepto',
                'DetalleGasto as detallegasto',
                'Proveedor as proveedor',
                'Docto as docto',
                'Importe as importe'
            )
            ->where('Ejercicio', $validated['ejercicio'])
            ->whereBetween('Periodo', [$validated['periodo_ini'], $validated['periodo_fin']]);

        $conceptoLower = strtolower($validated['concepto']);
        $query->whereRaw('LOWER(Concepto) = ?', [$conceptoLower]);

        if (!empty($validated['grupoer'])) {
            $query->whereRaw('CAST(GrupoER AS UNSIGNED) = ?', [$validated['grupoer']]);
        }
        if (!empty($validated['negocio'])) {
            $query->where('Negocio', $validated['negocio']);
        }
        if (!empty($validated['sucursal'])) {
            $query->where('Sucursal', $validated['sucursal']);
        }
        
        if (!empty($validated['ruta'])) 
        {
            if($validated['ruta'] != "—")
            {
                $query->where('Ruta', $validated['ruta']);
            }
        }

        $rows = $query->orderBy('Fecha', 'desc')->get();

        //return $query->toRawSql();

        return response()->json(['data' => $rows]);
    }

    public function exportAnalisisMantenimientoExcel(Request $request)
    {
        $filters = $request->only([
            'fechade', 'fechaa', 'idsucursal', 'idunidad',
            'idservicio', 'idtaller', 'estatus'
        ]);

        return Excel::download(
            new AnalisisMantenimientoExport($filters),
            'reporte-analisis-mantenimiento.xlsx'
        );
    }
}