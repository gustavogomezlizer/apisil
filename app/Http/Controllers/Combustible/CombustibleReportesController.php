<?php

namespace App\Http\Controllers\Combustible;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;

class CombustibleReportesController extends Controller
{
    public function getReporte(Request $request)
    {
        $query = $this->buildBaseQuery($request);
        return $query->paginate($request->per_page ?? 20);
    }

    public function exportarCsv(Request $request)
    {
        $data = $this->buildBaseQuery($request)->get();

        $csv = "Folio,Fecha,Semana,Unidad,Placas,No. Económico,Sucursal,Proveedor,Litros,Importe,Km Anterior,Km Carga,Consumo Km,Rendimiento (km/l),Responsable,Estatus\n";

        foreach ($data as $row) {
            $csv .= implode(',', [
                '"' . ($row->foliointerno    ?? '') . '"',
                '"' . ($row->fechacarga      ?? '') . '"',
                '"' . ($row->semana          ?? '') . '"',
                '"' . ($row->unidad          ?? '') . '"',
                '"' . ($row->placas          ?? '') . '"',
                '"' . ($row->numeroeconomico ?? '') . '"',
                '"' . ($row->sucursal_nombre ?? '') . '"',
                '"' . ($row->proveedor       ?? '') . '"',
                $row->litros   ?? 0,
                $row->importe  ?? 0,
                $row->ultimoodometro ?? 0,
                $row->odometrocarga  ?? 0,
                $row->consumo  ?? 0,
                $row->rendimiento ?? 0,
                '"' . ($row->responsable ?? '') . '"',
                '"' . ($row->estatus     ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="combustible_' . date('Ymd') . '.csv"',
        ]);
    }

    public function getReporteRendimiento(Request $request)
    {
        $query = DB::table('tickets_combustibles as c')
            ->join('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('cat_tipos_activos_fijos as taf', 'af.idtipoactivo', '=', 'taf.id')
            ->select(
                'af.id',
                'af.descripcion as unidad',
                'taf.nombre as tipo_unidad',
                'afu.placas', 'afu.numeroeconomico',
                DB::raw('COUNT(c.id) as total_cargas'),
                DB::raw('SUM(c.litros) as total_litros'),
                DB::raw('SUM(c.importe) as total_importe'),
                DB::raw('SUM(c.consumo) as total_km'),
                DB::raw('ROUND(SUM(c.consumo) / NULLIF(SUM(c.litros), 0), 2) as rendimiento_prom'),
                DB::raw('ROUND(SUM(c.importe) / NULLIF(SUM(c.consumo), 0), 2) as costo_km'),
                DB::raw('MIN(c.rendimiento) as rendimiento_min'),
                DB::raw('MAX(c.rendimiento) as rendimiento_max')
            )
            ->groupBy('af.id', 'af.descripcion', 'taf.nombre', 'afu.placas', 'afu.numeroeconomico')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $this->aplicarFiltrosComunes($query, $request);
        $query->orderByDesc('total_importe');

        return $query->paginate($request->per_page ?? 20);
    }

    public function getResumenSucursal(Request $request)
    {
        $query = DB::table('tickets_combustibles as c')
            ->join('sucursales as s', 'c.idsucursal', '=', 's.id')
            ->join('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->select(
                's.id as idsucursal',
                's.nombre as sucursal',
                DB::raw('COUNT(DISTINCT c.idvehiculo) as total_unidades'),
                DB::raw('COUNT(c.id) as total_cargas'),
                DB::raw('SUM(c.litros) as total_litros'),
                DB::raw('SUM(c.importe) as total_importe'),
                DB::raw('ROUND(SUM(c.consumo) / NULLIF(SUM(c.litros), 0), 2) as rendimiento_prom')
            )
            ->groupBy('s.id', 's.nombre')
            ->orderByDesc('total_importe')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $this->aplicarFiltrosComunes($query, $request);
        return response()->json($query->get());
    }

    protected function buildBaseQuery(Request $request)
    {
        $query = DB::table('tickets_combustibles as c')
            ->leftJoin('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('sucursales as s', 'c.idsucursal', '=', 's.id')
            ->leftJoin('talleres as t', 'c.idproveedor', '=', 't.id')
            ->select(
                'c.*',
                'af.descripcion as unidad',
                'afu.placas', 'afu.numeroeconomico',
                's.nombre as sucursal_nombre',
                't.razonsocial as proveedor'
            )
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $this->aplicarFiltrosComunes($query, $request);

        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('c.foliointerno', 'like', $s)
                    ->orWhere('af.descripcion', 'like', $s)
                    ->orWhere('afu.placas', 'like', $s);
            });
        });

        $query->orderByDesc('c.fechacarga')->orderByDesc('c.id');
        return $query;
    }

    protected function aplicarFiltrosComunes($query, Request $request): void
    {
        $query->when($request->fechade,     fn($q) => $q->where('c.fechacarga', '>=', $request->fechade));
        $query->when($request->fechaa,      fn($q) => $q->where('c.fechacarga', '<=', $request->fechaa));
        $query->when($request->idsucursal,  fn($q) => $q->where('c.idsucursal', $request->idsucursal));
        $query->when($request->idvehiculo,  fn($q) => $q->where('c.idvehiculo', $request->idvehiculo));
        $query->when($request->idproveedor, fn($q) => $q->where('c.idproveedor', $request->idproveedor));
        $query->when($request->estatus,     fn($q) => $q->where('c.estatus', $request->estatus));
        $query->when($request->semana,      fn($q) => $q->where('c.semana', $request->semana));
    }
}
