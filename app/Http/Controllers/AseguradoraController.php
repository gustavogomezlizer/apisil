<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AseguradoraController extends Controller
{

    public function getAseguradoras()
    {
        $rows = DB::select("SELECT * from aseguradoras c where c.estatus = 1");

        return $rows;
    }

    public function getAseguradora($id)
    {
        $aseguradora = DB::table('aseguradoras')
            ->where('id', $id)
            ->first();

        if (!$aseguradora) {
            return response()->json([
                'message' => 'Aseguradora no encontrada'
            ], 404);
        }

        return response()->json([
            'aseguradora' => $aseguradora
        ]);
    }

    public function guardarAseguradora(Request $request, $id = null)
    {
        $rules = [
            'nombre'   => 'required|string|max:150',
            'descripcion'  => 'string|max:255',
            'estatus'            => 'boolean',
        ];

        $validated = $request->validate($rules);

        if ($id) {
            // 🔄 UPDATE
            DB::table('aseguradoras')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now()
                ]);

            return response()->json([
                'message' => 'Aseguradora actualizada correctamente'
            ]);
        }

        // 🆕 CREATE
        $aseguradoraId = DB::table('aseguradoras')->insertGetId([
            ...$validated,
            'estatus' => $validated['estatus'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Aseguradora creada correctamente',
            'id' => $aseguradoraId
        ], 201);
    }
}
