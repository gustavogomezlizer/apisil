<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FlotillaBitacoraController extends Controller
{
    /**
     * Obtiene la bitácora/timeline de una unidad específica.
     */
    public function getBitacoraUnidad(Request $request, $idActivoFijo)
    {
        $query = DB::table('flotilla_bitacora as b')
            ->leftJoin('users as u', 'b.idusuario', '=', 'u.id')
            ->select(
                'b.*',
                'u.name as usuario_nombre'
            )
            ->where('b.idactivofijo', $idActivoFijo);

        $query->when($request->tipo_evento, fn($q) => $q->where('b.tipo_evento', $request->tipo_evento));
        $query->when($request->fechade, fn($q) => $q->where('b.fecha_evento', '>=', $request->fechade));
        $query->when($request->fechaa, fn($q) => $q->where('b.fecha_evento', '<=', $request->fechaa . ' 23:59:59'));

        $query->orderBy('b.fecha_evento', 'desc')->orderBy('b.id', 'desc');

        return $query->paginate($request->per_page ?? 20);
    }

    /**
     * Obtiene eventos de bitácora de toda la flotilla (para reportes).
     */
    public function getBitacoraGeneral(Request $request)
    {
        $query = DB::table('flotilla_bitacora as b')
            ->join('activos_fijos as af', 'b.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('users as u', 'b.idusuario', '=', 'u.id')
            ->select(
                'b.*',
                'af.descripcion as unidad',
                'afu.placas',
                'u.name as usuario_nombre'
            )
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $query->when($request->idactivofijo, fn($q) => $q->where('b.idactivofijo', $request->idactivofijo));
        $query->when($request->tipo_evento, fn($q) => $q->where('b.tipo_evento', $request->tipo_evento));
        $query->when($request->fechade, fn($q) => $q->where('b.fecha_evento', '>=', $request->fechade));
        $query->when($request->fechaa, fn($q) => $q->where('b.fecha_evento', '<=', $request->fechaa . ' 23:59:59'));
        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('b.descripcion', 'like', $s)
                    ->orWhere('af.descripcion', 'like', $s);
            });
        });

        $query->orderBy('b.fecha_evento', 'desc')->orderBy('b.id', 'desc');

        return $query->paginate($request->per_page ?? 20);
    }
}
