<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Flotilla\FlotillaService;

class FlotillaPlantillaController extends Controller
{
    protected FlotillaService $flotillaService;

    public function __construct(FlotillaService $flotillaService)
    {
        $this->flotillaService = $flotillaService;
    }

    // ─── PLANTILLAS ──────────────────────────────────────────────────────────

    public function getPlantillas(Request $request)
    {
        $query = DB::table('flotilla_plantillas_mantenimiento as p')
            ->leftJoin('cat_tipos_activos_fijos as t', 'p.idtipoactivo', '=', 't.id')
            ->select('p.*', 't.nombre as tipo_activo')
            ->where('p.activo', 1)
            ->whereIn('p.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $query->when($request->search, function ($q) use ($request) {
            $q->where('p.nombre', 'like', '%' . $request->search . '%');
        });

        $query->when($request->idtipoactivo, fn($q) =>
            $q->where('p.idtipoactivo', $request->idtipoactivo)
        );

        $query->orderBy('t.nombre')->orderBy('p.nombre');

        if ($request->all == 1) {
            return $query->get();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    public function getPlantilla($id)
    {
        $plantilla = DB::table('flotilla_plantillas_mantenimiento as p')
            ->leftJoin('cat_tipos_activos_fijos as t', 'p.idtipoactivo', '=', 't.id')
            ->select('p.*', 't.nombre as tipo_activo')
            ->where('p.id', $id)
            ->first();

        if (!$plantilla) {
            return response()->json(['message' => 'Plantilla no encontrada'], 404);
        }

        $servicios = DB::table('flotilla_plantillas_servicio as s')
            ->leftJoin('cat_tipos_servicio as ts', 's.idtiposervicio', '=', 'ts.id')
            ->select('s.*', 'ts.nombre as tipo_servicio_nombre')
            ->where('s.idplantilla', $id)
            ->where('s.activo', 1)
            ->orderBy('s.orden')
            ->get();

        return response()->json(['plantilla' => $plantilla, 'servicios' => $servicios]);
    }

    public function guardarPlantilla(Request $request, $id = null)
    {
        $request->validate([
            'idtipoactivo' => 'required|integer',
            'nombre'       => 'required|string|max:150',
        ]);

        if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($request->idtipoactivo)) {
            return response()->json(['message' => 'Solo se permiten plantillas para activos de tipo Unidad.'], 422);
        }

        $datos = [
            'idtipoactivo' => $request->idtipoactivo,
            'nombre'       => $request->nombre,
            'descripcion'  => $request->descripcion,
            'activo'       => $request->has('activo') ? (int)$request->activo : 1,
            'updated_at'   => now(),
        ];

        if ($id) {
            DB::table('flotilla_plantillas_mantenimiento')->where('id', $id)->update($datos);
            return response()->json(['message' => 'Plantilla actualizada', 'id' => $id]);
        }

        $datos['created_at'] = now();
        $newId = DB::table('flotilla_plantillas_mantenimiento')->insertGetId($datos);
        return response()->json(['message' => 'Plantilla creada', 'id' => $newId], 201);
    }

    public function eliminarPlantilla($id)
    {
        DB::table('flotilla_plantillas_mantenimiento')->where('id', $id)->update(['activo' => 0, 'updated_at' => now()]);
        return response()->json(['message' => 'Plantilla eliminada']);
    }

    // ─── SERVICIOS DE PLANTILLA ───────────────────────────────────────────────

    public function getServiciosPlantilla($idPlantilla)
    {
        $servicios = DB::table('flotilla_plantillas_servicio as s')
            ->leftJoin('cat_tipos_servicio as ts', 's.idtiposervicio', '=', 'ts.id')
            ->select('s.*', 'ts.nombre as tipo_servicio_nombre')
            ->where('s.idplantilla', $idPlantilla)
            ->where('s.activo', 1)
            ->orderBy('s.orden')
            ->get();

        return response()->json($servicios);
    }

    public function guardarServicioPlantilla(Request $request, $idPlantilla, $id = null)
    {
        $request->validate([
            'nombre_servicio' => 'required|string|max:200',
            'tipo_control'    => 'required|in:km,tiempo,ambos,horas',
        ]);

        $datos = [
            'idplantilla'     => $idPlantilla,
            'idtiposervicio'  => $request->idtiposervicio,
            'nombre_servicio' => $request->nombre_servicio,
            'tipo_control'    => $request->tipo_control,
            'frecuencia_km'   => $request->frecuencia_km,
            'frecuencia_dias' => $request->frecuencia_dias,
            'frecuencia_horas'=> $request->frecuencia_horas,
            'orden'           => $request->orden ?? 0,
            'activo'          => 1,
            'updated_at'      => now(),
        ];

        if ($id) {
            DB::table('flotilla_plantillas_servicio')->where('id', $id)->update($datos);
            return response()->json(['message' => 'Servicio actualizado', 'id' => $id]);
        }

        $datos['created_at'] = now();
        $newId = DB::table('flotilla_plantillas_servicio')->insertGetId($datos);

        // ── Propagar el nuevo servicio a todas las unidades que ya usan esta plantilla
        $propagados = $this->flotillaService->propagarServicioAUnidades((int)$idPlantilla, $newId);

        return response()->json([
            'message'    => 'Servicio agregado',
            'id'         => $newId,
            'propagados' => $propagados,
            'propagados_msg' => $propagados > 0
                ? "El servicio fue propagado automáticamente a {$propagados} unidad(es) que ya tenían esta plantilla asignada."
                : null,
        ], 201);
    }

    public function eliminarServicioPlantilla($idPlantilla, $id)
    {
        DB::table('flotilla_plantillas_servicio')
            ->where('id', $id)
            ->where('idplantilla', $idPlantilla)
            ->update(['activo' => 0, 'updated_at' => now()]);

        return response()->json(['message' => 'Servicio eliminado de la plantilla']);
    }
}
