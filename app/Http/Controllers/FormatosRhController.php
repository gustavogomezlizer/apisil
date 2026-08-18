<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormatosRhController extends Controller
{
    public function getFormatosRh(Request $request)
    {
        $query = DB::table('formatos_rh as f')
            ->leftJoin('cat_tipos_formato_rh as t', 'f.idtipoformato', '=', 't.id')
            ->select('f.*', 't.nombre as tipoformato');

        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';
            $q->where(function ($sub) use ($search) {
                $sub->where('f.nombre', 'like', $search)
                    ->orWhere('f.descripcion', 'like', $search);
            });
        });
        $query->when($request->idtipoformato, fn($q) => $q->where('f.idtipoformato', $request->idtipoformato));
        $query->where('f.estatus', 1)->orderBy('f.nombre');

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getFormatoRh($id)
    {
        $formato = DB::table('formatos_rh as f')
            ->leftJoin('cat_tipos_formato_rh as t', 'f.idtipoformato', '=', 't.id')
            ->select('f.*', 't.nombre as tipoformato')
            ->where('f.id', $id)
            ->first();

        if (!$formato) {
            return response()->json(['message' => 'Formato no encontrado'], 404);
        }

        return response()->json($formato);
    }

    public function guardarFormatoRh(Request $request, $id = null)
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'idtipoformato' => 'nullable|integer',
            'archivo' => 'nullable|file|max:20480',
        ];

        $request->validate($rules);

        $data = [
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'idtipoformato' => $request->idtipoformato,
            'estatus' => 1,
            'updated_at' => now(),
        ];

        // Manejar archivo subido
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $path = $file->store('formatos_rh', 'public');

            $data['ruta'] = $path;
            $data['tipo'] = $file->getMimeType();
            $data['extension'] = $file->getClientOriginalExtension();
            $data['tamano'] = $file->getSize();
        }

        if ($id) {
            DB::table('formatos_rh')->where('id', $id)->update($data);
            return response()->json(['message' => 'Formato actualizado correctamente']);
        }

        $data['created_at'] = now();
        $newId = DB::table('formatos_rh')->insertGetId($data);

        return response()->json([
            'message' => 'Formato creado correctamente',
            'id' => $newId,
        ], 201);
    }

    public function eliminarFormatoRh($id)
    {
        $formato = DB::table('formatos_rh')->where('id', $id)->first();

        if (!$formato) {
            return response()->json(['message' => 'Formato no encontrado'], 404);
        }

        // Eliminar archivo del storage si existe
        if ($formato->ruta) {
            Storage::disk('public')->delete($formato->ruta);
        }

        DB::table('formatos_rh')->where('id', $id)->update([
            'estatus' => 0,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Formato eliminado correctamente']);
    }

    public function getTiposFormatoRh()
    {
        $tipos = DB::table('cat_tipos_formato_rh')
            ->where('estatus', 1)
            ->orderBy('nombre')
            ->get();

        return $tipos;
    }
}
