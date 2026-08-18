<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SucursalController extends Controller
{

    public function getSucursales()
    {
        $rows = DB::select("SELECT * from sucursales cs where cs.estatus = 1");

        return $rows;
    }

    public function getSucursal($id)
    {
        $sucursal = DB::table('sucursales')
            ->where('id', $id)
            ->first();

        if (!$sucursal) {
            return response()->json([
                'message' => 'Sucursal no encontrada'
            ], 404);
        }

        return response()->json([
            'sucursal' => $sucursal
        ]);
    }

    public function guardarSucursal(Request $request, $id = null)
    {
        $rules = [
            'clave'    => [
                'required',
                'string',
                'max:50',
                Rule::unique('sucursales', 'clave')->ignore($id)
            ],
            'nombre'   => 'required|string|max:150',
            'descripcion'  => 'string|max:255',
            'estatus'            => 'boolean',
        ];

        $validated = $request->validate($rules);

        if ($id) {
            // 🔄 UPDATE
            DB::table('sucursales')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now()
                ]);

            return response()->json([
                'message' => 'Sucursal actualizada correctamente'
            ]);
        }

        // 🆕 CREATE
        $sucursalId = DB::table('sucursales')->insertGetId([
            ...$validated,
            'estatus' => $validated['estatus'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Sucursal creada correctamente',
            'id' => $sucursalId
        ], 201);
    }
}
