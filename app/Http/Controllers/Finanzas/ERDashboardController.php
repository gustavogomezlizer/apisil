<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * ERDashboardController
 *
 * Provides executive-level financial dashboard data sourced entirely
 * from sincronizador_bdd. All aggregations are dynamic — new negocios,
 * sucursales, rutas, and conceptos are picked up automatically.
 *
 * Endpoints:
 *   GET /api/er/dashboard   → KPIs + trend + charts data
 *   GET /api/er/filtros     → Dynamic filter options (años, negocios, sucursales)
 */
class ERDashboardController extends Controller
{
    // ─── Base query with common filters ──────────────────────────────────────

    private function baseQuery(array $f): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('sincronizador_bdd')
            ->where('Ejercicio', $f['ejercicio'])
            ->whereBetween('Periodo', [$f['periodo_ini'], $f['periodo_fin']]);

        if (!empty($f['negocio']))  $q->where('Negocio', $f['negocio']);
        if (!empty($f['sucursal'])) $q->where('Sucursal', $f['sucursal']);

        return $q;
    }

    // ─── Internal KPI calculation ─────────────────────────────────────────────

    private function calcularKpis(array $f): array
    {
        $rows = $this->baseQuery($f)
            ->select('GrupoER', DB::raw('SUM(ImporteER) as total'))
            ->whereNotNull('GrupoER')
            ->groupBy('GrupoER')
            ->get()
            ->keyBy('GrupoER');

        $ingresos = (float)($rows[1]->total ?? 0);
        $costos   = (float)($rows[2]->total ?? 0);   // already negative (ImporteER = Importe × Factor)
        $gastos   = (float)($rows[3]->total ?? 0);   // already negative

        $utilBruta     = $ingresos + $costos;      // costos is negative, net effect is subtraction
        $utilOperativa = $utilBruta + $gastos;     // gastos is negative

        $margenBruto     = $ingresos != 0 ? round(($utilBruta / $ingresos) * 100, 2) : 0;
        $margenOperativo = $ingresos != 0 ? round(($utilOperativa / $ingresos) * 100, 2) : 0;
        $rotacionCosto   = $ingresos != 0 ? round((abs($costos) / $ingresos) * 100, 2) : 0;
        $rotacionGastos  = $ingresos != 0 ? round((abs($gastos) / $ingresos) * 100, 2) : 0;

        return [
            'ingresos'           => round($ingresos, 2),
            'costos'             => round(abs($costos), 2),
            'gastos_operativos'  => round(abs($gastos), 2),
            'utilidad_bruta'     => round($utilBruta, 2),
            'utilidad_operativa' => round($utilOperativa, 2),
            'margen_bruto_pct'   => $margenBruto,
            'margen_operativo_pct'=> $margenOperativo,
            'rotacion_costo_pct' => $rotacionCosto,
            'rotacion_gastos_pct'=> $rotacionGastos,
        ];
    }

    // ─── Monthly trend data ──────────────────────────────────────────────────

    private function getTendenciaMensual(array $f): array
    {
        $rows = DB::table('sincronizador_bdd')
            ->select('Periodo', 'GrupoER', DB::raw('SUM(ImporteER) as total'))
            ->where('Ejercicio', $f['ejercicio'])
            ->whereBetween('Periodo', [1, 12])
            ->whereNotNull('GrupoER')
            ->when(!empty($f['negocio']),  fn($q) => $q->where('Negocio',  $f['negocio']))
            ->when(!empty($f['sucursal']), fn($q) => $q->where('Sucursal', $f['sucursal']))
            ->groupBy('Periodo', 'GrupoER')
            ->orderBy('Periodo')
            ->get();

        $meses = [];
        foreach ($rows as $row) {
            $p = (int)$row->Periodo;
            if (!isset($meses[$p])) {
                $meses[$p] = ['periodo' => $p, 'ingresos' => 0, 'costos' => 0, 'gastos' => 0, 'utilidad' => 0];
            }
            $g = (int)$row->GrupoER;
            if ($g === 1) $meses[$p]['ingresos'] = round((float)$row->total, 2);
            if ($g === 2) $meses[$p]['costos']   = round(abs((float)$row->total), 2);
            if ($g === 3) $meses[$p]['gastos']   = round(abs((float)$row->total), 2);
        }

        // Calculate utilidad bruta per month
        foreach ($meses as &$m) {
            $m['utilidad'] = round($m['ingresos'] - $m['costos'], 2);
        }

        return array_values($meses);
    }

    // ─── Participation by Negocio ────────────────────────────────────────────

    private function getPorNegocio(array $f): array
    {
        return $this->baseQuery($f)
            ->select('Negocio as negocio', DB::raw('SUM(ImporteER) as total'))
            ->whereNotNull('Negocio')
            ->where('Negocio', '!=', '')
            ->where('idnegocio', '>', 0)
            ->where('GrupoER', 1)
            ->groupBy('Negocio')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => ['negocio' => $r->negocio, 'total' => round((float)$r->total, 2)])
            ->toArray();
    }

    // ─── Top Sucursales by Ingreso ───────────────────────────────────────────

    private function getTopSucursales(array $f): array
    {
        return $this->baseQuery($f)
            ->select('Sucursal as sucursal', DB::raw('SUM(ImporteER) as total'))
            ->whereNotNull('Sucursal')
            ->where('Sucursal', '!=', '')
            ->where('GrupoER', 1)
            ->groupBy('Sucursal')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['sucursal' => $r->sucursal, 'total' => round((float)$r->total, 2)])
            ->toArray();
    }

    // ─── ER structure (waterfall data) ───────────────────────────────────────

    private function getEstructuraER(array $f): array
    {
        return $this->baseQuery($f)
            ->select(
                'GrupoER as grupoer',
                'Concepto as concepto',
                DB::raw('SUM(ImporteER) as total'),
                DB::raw('COUNT(*) as registros')
            )
            ->whereNotNull('GrupoER')
            ->groupBy('GrupoER', 'Concepto')
            ->orderBy('GrupoER')
            ->orderBy('Concepto')
            ->get()
            ->map(fn($r) => [
                'grupoer'   => (int)$r->grupoer,
                'concepto'  => $r->concepto,
                'total'     => round((float)$r->total, 2),
                'registros' => (int)$r->registros,
            ])
            ->toArray();
    }

    // ─── Comparativo por negocio (Utilidad Operativa) ────────────────────────

    private function getComparativoNegocios(array $f): array
    {
        $rows = $this->baseQuery($f)
            ->select(
                'Negocio as negocio',
                'GrupoER',
                DB::raw('SUM(ImporteER) as total')
            )
            ->whereNotNull('Negocio')
            ->where('Negocio', '!=', '')
            ->where('idnegocio', '>', 0)
            ->whereNotNull('GrupoER')
            ->groupBy('Negocio', 'GrupoER')
            ->get();

        // Aggregate by negocio
        $negocios = [];
        foreach ($rows as $row) {
            $neg = $row->negocio;
            if (!isset($negocios[$neg])) {
                $negocios[$neg] = ['negocio' => $neg, 'ingresos' => 0, 'costos' => 0, 'gastos' => 0];
            }
            $g = (int)$row->GrupoER;
            if ($g === 1) $negocios[$neg]['ingresos'] += (float)$row->total;
            if ($g === 2) $negocios[$neg]['costos']   += abs((float)$row->total);
            if ($g === 3) $negocios[$neg]['gastos']   += abs((float)$row->total);
        }

        return array_values(array_map(function ($n) {
            $utilBruta     = $n['ingresos'] - $n['costos'];
            $utilOperativa = $utilBruta - $n['gastos'];
            $margen        = $n['ingresos'] != 0 ? round(($utilOperativa / $n['ingresos']) * 100, 2) : 0;
            return array_merge($n, [
                'ingresos'           => round($n['ingresos'], 2),
                'costos'             => round($n['costos'], 2),
                'gastos'             => round($n['gastos'], 2),
                'utilidad_bruta'     => round($utilBruta, 2),
                'utilidad_operativa' => round($utilOperativa, 2),
                'margen_pct'         => $margen,
            ]);
        }, $negocios));
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  PUBLIC ENDPOINTS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * GET /api/er/dashboard
     * Executive dashboard: KPIs + comparativo + trends + charts data
     */
    public function getDashboard(Request $request)
    {
        $validated = $request->validate([
            'ejercicio'   => 'required|integer|min:2000|max:2100',
            'periodo_ini' => 'required|integer|min:1|max:12',
            'periodo_fin' => 'required|integer|min:1|max:12',
            'negocio'     => 'nullable|string|max:100',
            'sucursal'    => 'nullable|string|max:100',
        ]);

        $cacheKey = 'er_dashboard_' . md5(json_encode($validated));

        $data = Cache::remember($cacheKey, 90, function () use ($validated) {
            // Current period KPIs
            $kpis = $this->calcularKpis($validated);

            // Same period, prior year
            $filtroPA  = array_merge($validated, ['ejercicio' => $validated['ejercicio'] - 1]);
            $kpisPA    = $this->calcularKpis($filtroPA);

            // YoY variation
            $variacion = $this->calcVariacion($kpis, $kpisPA);

            return [
                'kpis'               => $kpis,
                'kpis_periodo_ant'   => $kpisPA,
                'variacion_yoy'      => $variacion,
                'tendencia_mensual'  => $this->getTendenciaMensual($validated),
                'por_negocio'        => $this->getPorNegocio($validated),
                'top_sucursales'     => $this->getTopSucursales($validated),
                'estructura_er'      => $this->getEstructuraER($validated),
                'comparativo_negocios' => $this->getComparativoNegocios($validated),
                'periodo'            => [
                    'ejercicio'   => $validated['ejercicio'],
                    'periodo_ini' => $validated['periodo_ini'],
                    'periodo_fin' => $validated['periodo_fin'],
                    'negocio'     => $validated['negocio'] ?? null,
                    'sucursal'    => $validated['sucursal'] ?? null,
                ],
            ];
        });

        return response()->json($data);
    }

    /**
     * GET /api/er/filtros
     * Returns dynamic filter options (year, negocios, sucursales) from actual data.
     */
    public function getFiltros(Request $request)
    {
        $cacheKey = 'er_filtros_' . date('YmdH');

        return Cache::remember($cacheKey, 60, function () {
            $ejercicios = DB::table('sincronizador_bdd')
                ->selectRaw('DISTINCT Ejercicio as value, CAST(Ejercicio AS CHAR) as label')
                ->orderBy('Ejercicio', 'desc')
                ->get();

            $negocios = DB::table('sincronizador_bdd')
                ->selectRaw('DISTINCT Negocio as value, Negocio as label')
                ->whereNotNull('Negocio')
                ->where('Negocio', '!=', '')
                ->where('idnegocio', '>', 0)
                ->orderBy('Negocio')
                ->get();

            $sucursales = DB::table('sincronizador_bdd')
                ->selectRaw('DISTINCT Sucursal as value, Sucursal as label')
                ->whereNotNull('Sucursal')
                ->where('Sucursal', '!=', '')
                ->orderBy('Sucursal')
                ->get();

            return response()->json([
                'ejercicios' => $ejercicios,
                'negocios'   => $negocios,
                'sucursales' => $sucursales,
            ]);
        });
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function calcVariacion(array $actual, array $anterior): array
    {
        $campos = ['ingresos', 'costos', 'gastos_operativos', 'utilidad_bruta', 'utilidad_operativa'];
        $result = [];
        foreach ($campos as $campo) {
            $a = $actual[$campo] ?? 0;
            $b = $anterior[$campo] ?? 0;
            $diff    = $a - $b;
            $pct     = $b != 0 ? round(($diff / abs($b)) * 100, 2) : null;
            $result[$campo] = ['diferencia' => round($diff, 2), 'pct' => $pct, 'crece' => $diff >= 0];
        }
        return $result;
    }
}
