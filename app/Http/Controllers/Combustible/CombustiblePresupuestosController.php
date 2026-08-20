<?php

namespace App\Http\Controllers\Combustible;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CombustiblePresupuestosController extends Controller
{
    public function getPresupuestos(Request $request)
    {
        $query = DB::table('comb_presupuestos as p')
            ->leftJoin('activos_fijos as af', 'p.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('sucursales as suc', 'p.idsucursal', '=', 'suc.id')
            ->select(
                'p.*',
                'af.descripcion as unidad',
                'afu.placas',
                'suc.nombre as sucursal_nombre'
            )
            ->where('p.activo', 1)
            ->where(function ($q) {
                $q->whereNull('p.idactivofijo')
                  ->orWhereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());
            });

        $query->when($request->tipo,         fn($q) => $q->where('p.tipo', $request->tipo));
        $query->when($request->idsucursal,   fn($q) => $q->where('p.idsucursal', $request->idsucursal));
        $query->when($request->periodo_anio, fn($q) => $q->where('p.periodo_anio', $request->periodo_anio));
        $query->when($request->periodo_mes,  fn($q) => $q->where('p.periodo_mes', $request->periodo_mes));

        $query->orderBy('p.periodo_anio', 'desc')
              ->orderBy('p.periodo_mes', 'desc')
              ->orderBy('p.tipo');

        // Calcular ejecución para cada presupuesto
        $result = $query->paginate($request->per_page ?? 20);
        $result->getCollection()->transform(function ($p) {
            $gastado = DB::table('tickets_combustibles as c')
                ->join('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
                ->whereYear('c.fechacarga', $p->periodo_anio)
                ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
                ->when($p->periodo_mes, fn($q) => $q->whereMonth('c.fechacarga', $p->periodo_mes))
                ->when($p->idactivofijo, fn($q) => $q->where('c.idvehiculo', $p->idactivofijo))
                ->when($p->idsucursal,   fn($q) => $q->where('c.idsucursal', $p->idsucursal))
                ->sum('c.importe') ?? 0;

            $p->gastado      = round($gastado, 2);
            $p->pct_ejecutado = $p->presupuesto_importe > 0
                ? round(($gastado / $p->presupuesto_importe) * 100, 1)
                : 0;
            $p->nivel_alerta = $p->pct_ejecutado >= 100 ? 'critica'
                : ($p->pct_ejecutado >= $p->alerta_pct ? 'advertencia' : 'ok');

            return $p;
        });

        return response()->json($result);
    }

    public function getPresupuesto($id)
    {
        $p = DB::table('comb_presupuestos')->where('id', $id)->first();
        if (!$p) return response()->json(['message' => 'Presupuesto no encontrado'], 404);
        return response()->json($p);
    }

    public function guardarPresupuesto(Request $request, $id = null)
    {
        $request->validate([
            'tipo'                => 'required|in:unidad,sucursal,departamento,global',
            'periodo_tipo'        => 'required|in:mensual,anual',
            'periodo_anio'        => 'required|integer|min:2020|max:2100',
            'presupuesto_importe' => 'required|numeric|min:0',
        ]);

        if ($request->tipo === 'unidad' && !ACTIVO_FIJO_ES_UNIDAD_POR_ID($request->idactivofijo)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de combustible.'], 422);
        }

        $datos = [
            'tipo'                => $request->tipo,
            'idactivofijo'        => $request->idactivofijo,
            'idsucursal'          => $request->idsucursal,
            'iddepartamento'      => $request->iddepartamento,
            'periodo_tipo'        => $request->periodo_tipo,
            'periodo_mes'         => $request->periodo_mes,
            'periodo_anio'        => $request->periodo_anio,
            'presupuesto_litros'  => $request->presupuesto_litros,
            'presupuesto_importe' => $request->presupuesto_importe,
            'alerta_pct'          => $request->alerta_pct ?? 80,
            'descripcion'         => $request->descripcion,
            'idusuario'           => $request->idusuario,
            'activo'              => 1,
            'updated_at'          => now(),
        ];

        if ($id) {
            DB::table('comb_presupuestos')->where('id', $id)->update($datos);
            return response()->json(['message' => 'Presupuesto actualizado', 'id' => $id]);
        }

        $datos['created_at'] = now();
        $newId = DB::table('comb_presupuestos')->insertGetId($datos);
        return response()->json(['message' => 'Presupuesto creado', 'id' => $newId], 201);
    }

    public function eliminarPresupuesto($id)
    {
        DB::table('comb_presupuestos')->where('id', $id)->update(['activo' => 0, 'updated_at' => now()]);
        return response()->json(['message' => 'Presupuesto eliminado']);
    }
}
