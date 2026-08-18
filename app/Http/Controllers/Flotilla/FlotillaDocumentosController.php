<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class FlotillaDocumentosController extends Controller
{
    public function getDocumentosUnidad(Request $request, $idActivoFijo)
    {
        $query = DB::table('flotilla_documentos_unidad')
            ->where('idactivofijo', $idActivoFijo)
            ->where('activo', 1)
            ->orderBy('tipo_documento')
            ->orderBy('fecha_vencimiento');

        return response()->json($query->get());
    }

    public function getDocumentosFlotilla(Request $request)
    {
        $query = DB::table('flotilla_documentos_unidad as d')
            ->join('activos_fijos as af', 'd.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->select(
                'd.*',
                'af.descripcion as unidad',
                'afu.placas',
                'afu.numeroeconomico'
            )
            ->where('d.activo', 1)
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('af.descripcion', 'like', $s)
                    ->orWhere('d.tipo_documento', 'like', $s)
                    ->orWhere('d.numero_documento', 'like', $s);
            });
        });

        $query->when($request->tipo_documento, fn($q) => $q->where('d.tipo_documento', $request->tipo_documento));
        $query->when($request->alerta, fn($q) => $q->where('d.estatus_alerta', $request->alerta));
        $query->when($request->idactivofijo, fn($q) => $q->where('d.idactivofijo', $request->idactivofijo));
        $query->when($request->vence_antes, fn($q) => $q->where('d.fecha_vencimiento', '<=', $request->vence_antes));

        $query->orderBy('d.estatus_alerta')->orderBy('d.fecha_vencimiento');

        return $query->paginate($request->per_page ?? 15);
    }

    public function getDocumento($id)
    {
        $doc = DB::table('flotilla_documentos_unidad as d')
            ->join('activos_fijos as af', 'd.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->select('d.*', 'af.descripcion as unidad', 'afu.placas')
            ->where('d.id', $id)
            ->first();

        if (!$doc) {
            return response()->json(['message' => 'Documento no encontrado'], 404);
        }

        return response()->json($doc);
    }

    public function guardarDocumento(Request $request, $id = null)
    {
        $request->validate([
            'idactivofijo'  => 'required|integer',
            'tipo_documento'=> 'required|string|max:80',
        ]);

        if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($request->idactivofijo)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de flotilla.'], 422);
        }

        $archivo = null;
        if ($request->hasFile('archivo')) {
            $file    = $request->file('archivo');
            $archivo = $file->store('flotilla/documentos', 'public');
        }

        $datos = [
            'idactivofijo'         => $request->idactivofijo,
            'tipo_documento'       => $request->tipo_documento,
            'nombre_custom'        => $request->nombre_custom,
            'numero_documento'     => $request->numero_documento,
            'fecha_emision'        => $request->fecha_emision,
            'fecha_vencimiento'    => $request->fecha_vencimiento,
            'costo'                => $request->costo,
            'descripcion'          => $request->descripcion,
            'dias_alerta_amarillo' => $request->dias_alerta_amarillo ?? 30,
            'estatus_alerta'       => $request->fecha_vencimiento ? 'verde' : 'sin_fecha',
            'activo'               => 1,
            'updated_at'           => now(),
        ];

        if ($archivo) {
            $datos['archivo'] = $archivo;
        }

        if ($id) {
            DB::table('flotilla_documentos_unidad')->where('id', $id)->update($datos);
            return response()->json(['message' => 'Documento actualizado', 'id' => $id]);
        }

        $datos['created_at'] = now();
        $newId = DB::table('flotilla_documentos_unidad')->insertGetId($datos);
        return response()->json(['message' => 'Documento guardado', 'id' => $newId], 201);
    }

    public function eliminarDocumento($id)
    {
        DB::table('flotilla_documentos_unidad')
            ->where('id', $id)
            ->update(['activo' => 0, 'updated_at' => now()]);

        return response()->json(['message' => 'Documento eliminado']);
    }
}
