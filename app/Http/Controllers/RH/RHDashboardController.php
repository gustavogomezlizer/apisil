<?php

namespace App\Http\Controllers\RH;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RHDashboardController extends Controller
{
    public function getDashboard(Request $request)
    {
        $mes  = $request->mes  ?? date('n');
        $anio = $request->anio ?? date('Y');

        return response()->json([
            'kpis'                  => $this->getKpis($mes, $anio),
            'movimientos_recientes' => $this->getMovimientosRecientes(),
            'distribucion_sucursal' => $this->getDistribucionSucursal(),
            'distribucion_puesto'   => $this->getDistribucionPuesto(),
            'alertas_resumen'       => $this->getResumenAlertas(),
            'grafica_movimientos'   => $this->getGraficaMovimientos($anio),
            'contratos_por_vencer'  => $this->getContratosPorVencer(),
        ]);
    }

    protected function getKpis(string $mes, string $anio): array
    {
        $totalActivos  = DB::table('empleados')->whereIn('estatus', config('rh.active_statuses'))->count();
        $totalBajas    = DB::table('empleados')->where('estatus', 'Baja')->count();

        // Movimientos del mes
        $altas = DB::table('rh_movimientos')
            ->where('tipo_movimiento', 'alta')
            ->whereYear('fecha_efectiva', $anio)
            ->whereMonth('fecha_efectiva', $mes)
            ->count();

        $bajas = DB::table('rh_movimientos')
            ->where('tipo_movimiento', 'baja')
            ->whereYear('fecha_efectiva', $anio)
            ->whereMonth('fecha_efectiva', $mes)
            ->count();

        // Rotación = bajas / ((activos_inicio + activos_fin) / 2) * 100
        $rotacion = $totalActivos > 0 ? round(($bajas / $totalActivos) * 100, 2) : 0;

        // Antigüedad promedio en años
        $antiguedad = DB::table('empleados')
            ->whereIn('estatus', config('rh.active_statuses'))
            ->whereNotNull('fechaingreso')
            ->selectRaw('AVG(DATEDIFF(CURDATE(), fechaingreso) / 365) as promedio')
            ->value('promedio') ?? 0;

        // Edad promedio
        $edadPromedio = DB::table('empleados')
            ->whereIn('estatus', config('rh.active_statuses'))
            ->whereNotNull('fechanacimiento')
            ->selectRaw('AVG(DATEDIFF(CURDATE(), fechanacimiento) / 365) as promedio')
            ->value('promedio') ?? 0;

        // Incapacidades activas (con fecha_fin futura o nula)
        $incapacidades = DB::table('rh_movimientos')
            ->where('tipo_movimiento', 'incapacidad')
            ->where(function ($q) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', date('Y-m-d'));
            })
            ->count();

        // Vacantes (empleados dados de baja en el mes sin reposición)
        // Simplificado: bajas del mes
        $vacantes = $bajas;

        return [
            'total_activos'      => $totalActivos,
            'total_bajas'        => $totalBajas,
            'altas_mes'          => $altas,
            'bajas_mes'          => $bajas,
            'rotacion_pct'       => $rotacion,
            'antiguedad_promedio'=> round($antiguedad, 1),
            'edad_promedio'      => round($edadPromedio, 1),
            'incapacidades'      => $incapacidades,
            'vacantes'           => $vacantes,
        ];
    }

    protected function getMovimientosRecientes(): array
    {
        return DB::table('rh_movimientos as m')
            ->join('empleados as e', 'm.idempleado', '=', 'e.id')
            ->select(
                'm.id', 'm.folio', 'm.tipo_movimiento', 'm.fecha_efectiva', 'm.motivo',
                'e.nombrecompleto', 'e.numeroempleado'
            )
            ->orderByDesc('m.created_at')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getDistribucionSucursal(): array
    {
        return DB::table('empleados as e')
            ->leftJoin('sucursales as s', 'e.idsucursal', '=', 's.id')
            ->whereIn('e.estatus', config('rh.active_statuses'))
            ->select('s.nombre as sucursal', DB::raw('COUNT(e.id) as total'))
            ->groupBy('s.nombre')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    protected function getDistribucionPuesto(): array
    {
        return DB::table('empleados')
            ->whereIn('estatus', config('rh.active_statuses'))
            ->whereNotNull('puesto')
            ->select('puesto', DB::raw('COUNT(id) as total'))
            ->groupBy('puesto')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getResumenAlertas(): array
    {
        $row = DB::table('rh_alertas')
            ->where('leida', 0)
            ->selectRaw('COUNT(*) as total,
                COALESCE(SUM(CASE WHEN nivel="critica"     THEN 1 ELSE 0 END), 0) as criticas,
                COALESCE(SUM(CASE WHEN nivel="advertencia" THEN 1 ELSE 0 END), 0) as advertencias,
                COALESCE(SUM(CASE WHEN nivel="info"        THEN 1 ELSE 0 END), 0) as informativas')
            ->first();

        return $row ? (array) $row : [
            'total' => 0, 'criticas' => 0, 'advertencias' => 0, 'informativas' => 0,
        ];
    }

    protected function getGraficaMovimientos(string $anio): array
    {
        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = DB::table('rh_movimientos')
                ->whereYear('fecha_efectiva', $anio)
                ->whereMonth('fecha_efectiva', $m)
                ->selectRaw("
                    SUM(CASE WHEN tipo_movimiento='alta' THEN 1 ELSE 0 END) as altas,
                    SUM(CASE WHEN tipo_movimiento='baja' THEN 1 ELSE 0 END) as bajas,
                    COUNT(*) as total
                ")
                ->first();

            $meses[] = [
                'mes'    => Carbon::create($anio, $m, 1)->locale('es')->monthName,
                'mes_num'=> $m,
                'altas'  => $row->altas  ?? 0,
                'bajas'  => $row->bajas  ?? 0,
                'total'  => $row->total  ?? 0,
            ];
        }
        return $meses;
    }

    protected function getContratosPorVencer(): array
    {
        return DB::table('rh_empleados_extra as ex')
            ->join('empleados as e', 'ex.idempleado', '=', 'e.id')
            ->whereIn('e.estatus', config('rh.active_statuses'))
            ->whereNotNull('ex.fecha_fin_contrato')
            ->where('ex.fecha_fin_contrato', '>=', date('Y-m-d'))
            ->where('ex.fecha_fin_contrato', '<=', date('Y-m-d', strtotime('+60 days')))
            ->select(
                'e.id', 'e.nombrecompleto', 'e.numeroempleado',
                'ex.fecha_fin_contrato', 'ex.tipo_contrato',
                DB::raw('DATEDIFF(ex.fecha_fin_contrato, CURDATE()) as dias_restantes')
            )
            ->orderBy('ex.fecha_fin_contrato')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
