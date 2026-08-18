<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Flotilla\FlotillaService;
use App\Services\Flotilla\AlertaService;

class FlotillaAlertasController extends Controller
{
    public function getAlertas(Request $request)
    {
        $query = DB::table('flotilla_alertas as a')
            ->join('activos_fijos as af', 'a.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->select(
                'a.*',
                'af.descripcion as unidad',
                'afu.placas',
                'afu.numeroeconomico'
            )
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $query->when($request->nivel, fn($q) => $q->where('a.nivel', $request->nivel));
        $query->when($request->tipo_alerta, fn($q) => $q->where('a.tipo_alerta', $request->tipo_alerta));
        $query->when(isset($request->leida), fn($q) => $q->where('a.leida', (int)$request->leida));
        $query->when($request->idactivofijo, fn($q) => $q->where('a.idactivofijo', $request->idactivofijo));
        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('a.mensaje', 'like', $s)
                    ->orWhere('af.descripcion', 'like', $s);
            });
        });

        // Ordenar: rojo primero, luego amarillo, luego las no leídas
        $query->orderByRaw("FIELD(a.nivel, 'rojo', 'amarillo', 'verde')")
              ->orderBy('a.leida')
              ->orderBy('a.fecha_generacion', 'desc');

        return $query->paginate($request->per_page ?? 20);
    }

    public function getResumenAlertas()
    {
        $resumen = DB::table('flotilla_alertas')
            ->where('leida', 0)
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN nivel = "rojo"    THEN 1 ELSE 0 END) as rojo'),
                DB::raw('SUM(CASE WHEN nivel = "amarillo" THEN 1 ELSE 0 END) as amarillo'),
                DB::raw('SUM(CASE WHEN tipo_alerta = "mantenimiento_preventivo" THEN 1 ELSE 0 END) as mantenimiento'),
                DB::raw('SUM(CASE WHEN tipo_alerta = "documento" THEN 1 ELSE 0 END) as documentos')
            )
            ->first();

        return response()->json($resumen);
    }

    public function marcarLeida($id)
    {
        DB::table('flotilla_alertas')
            ->where('id', $id)
            ->update(['leida' => 1, 'fecha_lectura' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'Alerta marcada como leída']);
    }

    public function marcarTodasLeidas(Request $request)
    {
        $query = DB::table('flotilla_alertas')->where('leida', 0);

        $query->when($request->nivel, fn($q) => $q->where('nivel', $request->nivel));
        $query->when($request->tipo_alerta, fn($q) => $q->where('tipo_alerta', $request->tipo_alerta));

        $query->update(['leida' => 1, 'fecha_lectura' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'Alertas marcadas como leídas']);
    }

    public function generarAlertas()
    {
        $flotillaService = new FlotillaService();
        $alertaService   = new AlertaService($flotillaService);
        $resultados      = $alertaService->generarAlertas();

        return response()->json([
            'message'    => 'Alertas generadas correctamente',
            'resultados' => $resultados,
        ]);
    }
}
