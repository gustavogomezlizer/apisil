<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class FlotillaReportesController extends Controller
{
    public function getReporteMantenimientos(Request $request)
    {
        $query = DB::table('flotilla_mantenimiento_preventivo as mp')
            ->join('activos_fijos as af', 'mp.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('cat_tipos_activos_fijos as taf', 'af.idtipoactivo', '=', 'taf.id')
            ->leftJoin('talleres as t', 'mp.idtaller', '=', 't.id')
            ->leftJoin('empleados as e', 'mp.idempleado_registra', '=', 'e.id')
            ->leftJoin('flotilla_unidad_mantenimiento as um', 'mp.idunidad_mantenimiento', '=', 'um.id')
            ->leftJoin('sucursales as suc', 'af.idsucursal', '=', 'suc.id')
            ->select(
                'mp.folio', 'mp.fecha_servicio', 'mp.km_servicio',
                'af.descripcion as unidad', 'afu.placas', 'afu.numeroeconomico',
                'taf.nombre as tipo_unidad',
                'suc.nombre as sucursal',
                'um.nombre_servicio as servicio',
                't.razonsocial as taller',
                DB::raw("CONCAT(e.nombres, ' ', e.apellidopaterno) as empleado"),
                'mp.costo_mano_obra', 'mp.costo_refacciones', 'mp.costo_total',
                'mp.observaciones'
            )
            ->addSelect(DB::raw("'Preventivo' as tipo_mantenimiento"));

        $this->aplicarFiltros($query, $request, 'preventivo');

        $correctivo = DB::table('flotilla_mantenimiento_correctivo as mc')
            ->join('activos_fijos as af', 'mc.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('cat_tipos_activos_fijos as taf', 'af.idtipoactivo', '=', 'taf.id')
            ->leftJoin('talleres as t', 'mc.idtaller', '=', 't.id')
            ->leftJoin('empleados as e', 'mc.idempleado_registra', '=', 'e.id')
            ->leftJoin('sucursales as suc', 'af.idsucursal', '=', 'suc.id')
            ->select(
                'mc.folio',
                DB::raw('mc.fecha_ingreso as fecha_servicio'),
                DB::raw('NULL as km_servicio'),
                'af.descripcion as unidad', 'afu.placas', 'afu.numeroeconomico',
                'taf.nombre as tipo_unidad',
                'suc.nombre as sucursal',
                DB::raw('mc.diagnostico as servicio'),
                't.razonsocial as taller',
                DB::raw("CONCAT(e.nombres, ' ', e.apellidopaterno) as empleado"),
                'mc.costo_mano_obra', 'mc.costo_refacciones', 'mc.costo_total',
                'mc.observaciones'
            )
            ->addSelect(DB::raw("'Correctivo' as tipo_mantenimiento"));

        $this->aplicarFiltros($correctivo, $request, 'correctivo');

        // Incluir correctivo si no se filtra por tipo
        $incluirCorrectivo = !$request->tipo_mantenimiento || $request->tipo_mantenimiento === 'correctivo';
        $incluirPreventivo = !$request->tipo_mantenimiento || $request->tipo_mantenimiento === 'preventivo';

        if ($incluirPreventivo && $incluirCorrectivo) {
            $finalQuery = $query->union($correctivo);
        } elseif ($incluirCorrectivo) {
            $finalQuery = $correctivo;
        } else {
            $finalQuery = $query;
        }

        $data = DB::table(DB::raw("({$finalQuery->toSql()}) as rep"))
            ->mergeBindings($finalQuery)
            ->orderBy('fecha_servicio', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($data);
    }

    public function exportarExcel(Request $request)
    {
        $datos = $this->obtenerDatosReporte($request);

        $headers = [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="reporte_flotilla_' . date('Ymd') . '.xlsx"',
        ];

        // Generar CSV simple (compatible sin paquete Excel extra)
        $csv  = "Folio,Fecha,Tipo,Unidad,Placas,No. Económico,Tipo Unidad,Servicio/Diagnóstico,Taller,Empleado,Mano de Obra,Refacciones,Total\n";
        foreach ($datos as $row) {
            $csv .= implode(',', [
                '"' . ($row->folio              ?? '') . '"',
                '"' . ($row->fecha_servicio     ?? '') . '"',
                '"' . ($row->tipo_mantenimiento ?? '') . '"',
                '"' . ($row->unidad             ?? '') . '"',
                '"' . ($row->placas             ?? '') . '"',
                '"' . ($row->numeroeconomico    ?? '') . '"',
                '"' . ($row->tipo_unidad         ?? '') . '"',
                '"' . ($row->servicio            ?? '') . '"',
                '"' . ($row->taller              ?? '') . '"',
                '"' . ($row->empleado            ?? '') . '"',
                $row->costo_mano_obra  ?? 0,
                $row->costo_refacciones?? 0,
                $row->costo_total      ?? 0,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_flotilla_' . date('Ymd') . '.csv"',
        ]);
    }

    public function exportarPdf(Request $request)
    {
        $datos    = $this->obtenerDatosReporte($request);
        $filtros  = $request->all();
        $total    = collect($datos)->sum('costo_total');

        $pdf = Pdf::loadView('flotilla.reporte_pdf', compact('datos', 'filtros', 'total'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('reporte_flotilla_' . date('Ymd') . '.pdf');
    }

    protected function obtenerDatosReporte(Request $request): array
    {
        $query = DB::table('flotilla_mantenimiento_preventivo as mp')
            ->join('activos_fijos as af', 'mp.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('cat_tipos_activos_fijos as taf', 'af.idtipoactivo', '=', 'taf.id')
            ->leftJoin('talleres as t', 'mp.idtaller', '=', 't.id')
            ->leftJoin('empleados as e', 'mp.idempleado_registra', '=', 'e.id')
            ->leftJoin('flotilla_unidad_mantenimiento as um', 'mp.idunidad_mantenimiento', '=', 'um.id')
            ->leftJoin('sucursales as suc', 'af.idsucursal', '=', 'suc.id')
            ->select(
                'mp.folio', DB::raw('mp.fecha_servicio'), 'af.descripcion as unidad',
                'afu.placas', 'afu.numeroeconomico', 'taf.nombre as tipo_unidad',
                'suc.nombre as sucursal',
                'um.nombre_servicio as servicio', 't.razonsocial as taller',
                DB::raw("CONCAT(e.nombres, ' ', e.apellidopaterno) as empleado"),
                'mp.costo_mano_obra', 'mp.costo_refacciones', 'mp.costo_total'
            )
            ->addSelect(DB::raw("'Preventivo' as tipo_mantenimiento"));

        $this->aplicarFiltros($query, $request, 'preventivo');

        if (!$request->tipo_mantenimiento || $request->tipo_mantenimiento === 'correctivo') {
            $correctivo = DB::table('flotilla_mantenimiento_correctivo as mc')
                ->join('activos_fijos as af', 'mc.idactivofijo', '=', 'af.id')
                ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
                ->leftJoin('cat_tipos_activos_fijos as taf', 'af.idtipoactivo', '=', 'taf.id')
                ->leftJoin('talleres as t', 'mc.idtaller', '=', 't.id')
                ->leftJoin('empleados as e', 'mc.idempleado_registra', '=', 'e.id')
                ->leftJoin('sucursales as suc', 'af.idsucursal', '=', 'suc.id')
                ->select(
                    'mc.folio', DB::raw('mc.fecha_ingreso as fecha_servicio'), 'af.descripcion as unidad',
                    'afu.placas', 'afu.numeroeconomico', 'taf.nombre as tipo_unidad',
                    'suc.nombre as sucursal',
                    DB::raw('mc.diagnostico as servicio'), 't.razonsocial as taller',
                    DB::raw("CONCAT(e.nombres, ' ', e.apellidopaterno) as empleado"),
                    'mc.costo_mano_obra', 'mc.costo_refacciones', 'mc.costo_total'
                )
                ->addSelect(DB::raw("'Correctivo' as tipo_mantenimiento"));

            $this->aplicarFiltros($correctivo, $request, 'correctivo');

            if (!$request->tipo_mantenimiento) {
                $query = $query->union($correctivo);
            } else {
                $query = $correctivo;
            }
        }

        return DB::table(DB::raw("({$query->toSql()}) as rep"))
            ->mergeBindings($query)
            ->orderBy('fecha_servicio', 'desc')
            ->get()
            ->toArray();
    }

    protected function aplicarFiltros($query, Request $request, string $tipo): void
    {
        $tabla = $tipo === 'preventivo' ? 'mp' : 'mc';
        $campoFecha = $tipo === 'preventivo' ? "{$tabla}.fecha_servicio" : "{$tabla}.fecha_ingreso";

        $query->when($request->fechade, fn($q) => $q->where($campoFecha, '>=', $request->fechade));
        $query->when($request->fechaa,  fn($q) => $q->where($campoFecha, '<=', $request->fechaa));
        $query->when($request->idactivofijo, fn($q) => $q->where("{$tabla}.idactivofijo", $request->idactivofijo));
        $query->when($request->idtaller, fn($q) => $q->where("{$tabla}.idtaller", $request->idtaller));
        $query->when($request->idempleado, fn($q) => $q->where("{$tabla}.idempleado_registra", $request->idempleado));
        $query->when($request->idtipoactivo, fn($q) => $q->where('af.idtipoactivo', $request->idtipoactivo));
        $query->when($request->idsucursal, fn($q) => $q->where('af.idsucursal', $request->idsucursal));

        $query->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());
    }
}
