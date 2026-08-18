<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NegocioController extends Controller
{

    public function getNegocios()
    {
        $rows = DB::select("SELECT * from negocios c where c.estatus = 1");

        return $rows;
    }

    public function getNegocio($id)
    {
        $negocio = DB::table('negocios')
            ->where('id', $id)
            ->first();

        if (!$negocio) {
            return response()->json([
                'message' => 'Negocio no encontrado'
            ], 404);
        }

        return response()->json([
            'negocio' => $negocio
        ]);
    }

    public function guardarNegocio(Request $request, $id = null)
    {
        $rules = [
            'clave'    => [
                'required',
                'string',
                'max:50',
                Rule::unique('negocios', 'clave')->ignore($id)
            ],
            'nombre'   => 'required|string|max:150',
            'descripcion'  => 'string|max:255',
            'estatus'            => 'boolean',
        ];

        $validated = $request->validate($rules);

        if ($id) {
            // 🔄 UPDATE
            DB::table('negocios')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now()
                ]);

            return response()->json([
                'message' => 'Negocio actualizado correctamente'
            ]);
        }

        // 🆕 CREATE
        $negocioId = DB::table('negocios')->insertGetId([
            ...$validated,
            'estatus' => $validated['estatus'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Negocio creado correctamente',
            'id' => $negocioId
        ], 201);
    }
}
