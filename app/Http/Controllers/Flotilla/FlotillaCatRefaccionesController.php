<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FlotillaCatRefaccionesController extends Controller
{
    public function getCatRefacciones(Request $request)
    {
        $query = DB::table('flotilla_cat_refacciones')
            ->where('activo', 1);

        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('nombre', 'like', $s)
                    ->orWhere('categoria', 'like', $s)
                    ->orWhere('descripcion', 'like', $s);
            });
        });

        $query->when($request->categoria, fn($q) => $q->where('categoria', $request->categoria));

        $query->orderBy('categoria')->orderBy('nombre');

        if ($request->all == 1) {
            return $query->get();
        }

        return $query->paginate($request->per_page ?? 20);
    }

    public function getCatRefaccion($id)
    {
        $row = DB::table('flotilla_cat_refacciones')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Refacción no encontrada'], 404);
        }
        return response()->json($row);
    }

    public function guardarCatRefaccion(Request $request, $id = null)
    {
        $request->validate([
            'nombre'       => 'required|string|max:200',
            'unidad_medida'=> 'required|string|max:30',
        ]);

        $datos = [
            'nombre'         => $request->nombre,
            'descripcion'    => $request->descripcion,
            'categoria'      => $request->categoria,
            'unidad_medida'  => $request->unidad_medida,
            'costo_promedio' => $request->costo_promedio,
            'activo'         => 1,
            'updated_at'     => now(),
        ];

        if ($id) {
            DB::table('flotilla_cat_refacciones')->where('id', $id)->update($datos);
            return response()->json(['message' => 'Refacción actualizada', 'id' => $id]);
        }

        $datos['created_at'] = now();
        $newId = DB::table('flotilla_cat_refacciones')->insertGetId($datos);
        return response()->json(['message' => 'Refacción creada', 'id' => $newId], 201);
    }

    public function eliminarCatRefaccion($id)
    {
        DB::table('flotilla_cat_refacciones')->where('id', $id)->update(['activo' => 0, 'updated_at' => now()]);
        return response()->json(['message' => 'Refacción eliminada']);
    }

    public function getCategorias()
    {
        $categorias = DB::table('flotilla_cat_refacciones')
            ->where('activo', 1)
            ->whereNotNull('categoria')
            ->distinct()
            ->pluck('categoria')
            ->sort()
            ->values();

        return response()->json($categorias);
    }
}
