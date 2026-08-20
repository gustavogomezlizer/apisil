<?php

namespace App\Http\Controllers\Combustible;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CombustibleDashboardController extends Controller
{
    public function getDashboard(Request $request)
    {
        $mes  = $request->mes  ?? date('n');
        $anio = $request->anio ?? date('Y');

        return response()->json([
            'kpis'             => $this->getKpis($mes, $anio),
            'top_consumidores' => $this->getTopConsumidores($mes, $anio),
            'grafica_mensual'  => $this->getGraficaMensual($anio),
            'alertas_resumen'  => $this->getResumenAlertas(),
            'presupuesto_mes'  => $this->getEjecucionPresupuesto($mes, $anio),
        ]);
    }

    protected function getKpis(string $mes, string $anio): array
    {
        $base = DB::table('tickets_combustibles as c')
            ->join('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->whereYear('c.fechacarga', $anio)
            ->whereMonth('c.fechacarga', $mes)
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $totalLitros  = $base->sum('c.litros') ?? 0;
        $totalImporte = $base->sum('c.importe') ?? 0;
        $totalCargas  = $base->count();
        $totalUnidades= $base->distinct()->count('c.idvehiculo');

        // Rendimiento promedio (km/litro)
        $rendimiento = DB::table('tickets_combustibles as c')
            ->join('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->whereYear('c.fechacarga', $anio)
            ->whereMonth('c.fechacarga', $mes)
            ->where('c.litros', '>', 0)
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->avg('c.rendimiento') ?? 0;

        // Mes anterior para comparativo
        $mesAnterior = $mes == 1 ? 12 : $mes - 1;
        $anioAnterior = $mes == 1 ? $anio - 1 : $anio;
        $importeAnterior = DB::table('tickets_combustibles as c')
            ->join('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->whereYear('c.fechacarga', $anioAnterior)
            ->whereMonth('c.fechacarga', $mesAnterior)
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->sum('c.importe') ?? 0;

        $variacion = $importeAnterior > 0
            ? round((($totalImporte - $importeAnterior) / $importeAnterior) * 100, 1)
            : 0;

        return [
            'total_litros'     => round($totalLitros, 3),
            'total_importe'    => round($totalImporte, 2),
            'total_cargas'     => $totalCargas,
            'total_unidades'   => $totalUnidades,
            'rendimiento_prom' => round($rendimiento, 2),
            'variacion_mes'    => $variacion,
            'importe_anterior' => round($importeAnterior, 2),
        ];
    }

    protected function getTopConsumidores(string $mes, string $anio): array
    {
        return DB::table('tickets_combustibles as c')
            ->join('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->whereYear('c.fechacarga', $anio)
            ->whereMonth('c.fechacarga', $mes)
            ->select(
                'af.id',
                'af.descripcion as unidad',
                'afu.placas',
                DB::raw('SUM(c.litros) as total_litros'),
                DB::raw('SUM(c.importe) as total_importe'),
                DB::raw('COUNT(c.id) as total_cargas'),
                DB::raw('ROUND(AVG(c.rendimiento), 2) as rendimiento_prom')
            )
            ->groupBy('af.id', 'af.descripcion', 'afu.placas')
            ->orderByDesc('total_importe')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getGraficaMensual(string $anio): array
    {
        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = DB::table('tickets_combustibles as c')
                ->join('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
                ->whereYear('c.fechacarga', $anio)
                ->whereMonth('c.fechacarga', $m)
                ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
                ->selectRaw('SUM(c.litros) as litros, SUM(c.importe) as importe, COUNT(c.id) as cargas')
                ->first();

            $meses[] = [
                'mes'     => Carbon::create($anio, $m, 1)->locale('es')->monthName,
                'mes_num' => $m,
                'litros'  => round($row->litros ?? 0, 3),
                'importe' => round($row->importe ?? 0, 2),
                'cargas'  => $row->cargas ?? 0,
            ];
        }
        return $meses;
    }

    protected function getResumenAlertas(): array
    {
        $row = DB::table('comb_alertas as ca')
            ->join('activos_fijos as af', 'ca.idactivofijo', '=', 'af.id')
            ->where('ca.leida', 0)
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->selectRaw('COUNT(*) as total,
                SUM(CASE WHEN nivel="critica"     THEN 1 ELSE 0 END) as criticas,
                SUM(CASE WHEN nivel="advertencia" THEN 1 ELSE 0 END) as advertencias,
                SUM(CASE WHEN nivel="info"        THEN 1 ELSE 0 END) as informativas')
            ->first();

        return [
            'total'        => $row->total       ?? 0,
            'criticas'     => $row->criticas     ?? 0,
            'advertencias' => $row->advertencias ?? 0,
            'informativas' => $row->informativas ?? 0,
        ];
    }

    protected function getEjecucionPresupuesto(string $mes, string $anio): array
    {
        $presupuestos = DB::table('comb_presupuestos')
            ->where('activo', 1)
            ->where('periodo_anio', $anio)
            ->where(function ($q) use ($mes) {
                $q->where('periodo_mes', $mes)->orWhereNull('periodo_mes');
            })
            ->get();

        $resultado = [];
        foreach ($presupuestos as $p) {
            $gastado = DB::table('tickets_combustibles as c')
                ->join('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
                ->whereYear('c.fechacarga', $anio)
                ->whereMonth('c.fechacarga', $mes)
                ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
                ->when($p->idactivofijo, fn($q) => $q->where('c.idvehiculo', $p->idactivofijo))
                ->when($p->idsucursal,   fn($q) => $q->where('c.idsucursal', $p->idsucursal))
                ->sum('c.importe') ?? 0;

            $pct = $p->presupuesto_importe > 0
                ? round(($gastado / $p->presupuesto_importe) * 100, 1)
                : 0;

            $resultado[] = [
                'id'                  => $p->id,
                'tipo'                => $p->tipo,
                'descripcion'         => $p->descripcion,
                'presupuesto_importe' => $p->presupuesto_importe,
                'gastado'             => round($gastado, 2),
                'pct_ejecutado'       => $pct,
                'alerta'              => $pct >= $p->alerta_pct,
                'nivel_alerta'        => $pct >= 100 ? 'critica' : ($pct >= $p->alerta_pct ? 'advertencia' : 'ok'),
            ];
        }

        return $resultado;
    }
}
