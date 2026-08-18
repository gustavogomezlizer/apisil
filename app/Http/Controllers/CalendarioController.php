<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
    public function getEventos(Request $request)
    {
        $query = DB::table('calendario')
            ->where('estatus', 1);

        $query->when($request->fecha_inicio, fn($q) =>
            $q->where('fecha_fin', '>=', $request->fecha_inicio)
        );
        $query->when($request->fecha_fin, fn($q) =>
            $q->where('fecha_inicio', '<=', $request->fecha_fin)
        );
        $query->when($request->tipo, fn($q) =>
            $q->where('tipo', $request->tipo)
        );

        return $query->orderBy('fecha_inicio')->get();
    }

    public function getEvento($id)
    {
        $evento = DB::table('calendario')->where('id', $id)->first();

        if (!$evento) {
            return response()->json(['message' => 'Evento no encontrado'], 404);
        }

        return response()->json($evento);
    }

    public function guardarEvento(Request $request, $id = null)
    {
        $rules = [
            'titulo' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date',
            'tipo' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'todo_dia' => 'boolean',
        ];

        $validated = $request->validate($rules);

        if ($id) {
            DB::table('calendario')->where('id', $id)->update([
                ...$validated,
                'updated_at' => now(),
            ]);
            return response()->json(['message' => 'Evento actualizado correctamente']);
        }

        $newId = DB::table('calendario')->insertGetId([
            ...$validated,
            'estatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Evento creado correctamente',
            'id' => $newId,
        ], 201);
    }

    public function eliminarEvento($id)
    {
        $evento = DB::table('calendario')->where('id', $id)->first();

        if (!$evento) {
            return response()->json(['message' => 'Evento no encontrado'], 404);
        }

        DB::table('calendario')->where('id', $id)->update([
            'estatus' => 0,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Evento eliminado correctamente']);
    }
}
