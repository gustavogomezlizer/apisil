<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class TipoServicioController extends Controller
{
    public function getTiposServicio(Request $request)
    {
        $query = DB::table('cat_tipos_servicio as t');

        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';
            $q->where('t.nombre', 'like', $search);
        });

        $query->where('t.estatus', 1)->orderBy('t.nombre');

        $perPage = $request->per_page ?? 10;

        if ($perPage == 500 || $request->all == 1) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    public function getTipoServicio($id)
    {
        $tipo = DB::table('cat_tipos_servicio')->where('id', $id)->first();

        if (!$tipo) {
            return response()->json(['message' => 'Tipo de servicio no encontrado'], 404);
        }

        return response()->json(['tiposervicio' => $tipo]);
    }

    public function guardarTipoServicio(Request $request, $id = null)
    {
        $rules = [
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:255',
            'estatus' => 'boolean',
        ];

        $validated = $request->validate($rules);
        $validated['activo'] = $validated['estatus'] ?? true;

        if ($id) {
            DB::table('cat_tipos_servicio')->where('id', $id)->update([
                ...$validated,
                'updated_at' => now(),
            ]);
            return response()->json(['message' => 'Tipo de servicio actualizado correctamente']);
        }

        $tipoId = DB::table('cat_tipos_servicio')->insertGetId([
            ...$validated,
            'estatus' => $validated['estatus'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Tipo de servicio creado correctamente',
            'id' => $tipoId,
        ], 201);
    }

    public function eliminarTipoServicio($id)
    {
        $tipo = DB::table('cat_tipos_servicio')->where('id', $id)->first();

        if (!$tipo) {
            return response()->json(['message' => 'Tipo de servicio no encontrado'], 404);
        }

        DB::table('cat_tipos_servicio')->where('id', $id)->update([
            'estatus' => 0,
            'activo' => 0,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Tipo de servicio eliminado correctamente']);
    }
}
