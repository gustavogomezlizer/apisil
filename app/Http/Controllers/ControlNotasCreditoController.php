<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class ControlNotasCreditoController extends Controller
{
    public function getNotasCredito(Request $request)
    {
        $query = DB::table('control_notas_credito as nc');

        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';
            $q->where(function ($sub) use ($search) {
                $sub->where('nc.nota_credito', 'like', $search)
                    ->orWhere('nc.numero_nc', 'like', $search)
                    ->orWhere('nc.descripcion', 'like', $search)
                    ->orWhere('nc.negocio', 'like', $search)
                    ->orWhere('nc.sucursal', 'like', $search);
            });
        });

        $query->when($request->idnegocio, fn($q) => $q->where('nc.idnegocio', $request->idnegocio));
        $query->when($request->idsucursal, fn($q) => $q->where('nc.idsucursal', $request->idsucursal));
        $query->when($request->anio, fn($q) => $q->where('nc.anio', $request->anio));
        $query->when($request->mes, fn($q) => $q->where('nc.mes', $request->mes));
        $query->when($request->fechade, fn($q) => $q->where('nc.fecha', '>=', $request->fechade));
        $query->when($request->fechaa, fn($q) => $q->where('nc.fecha', '<=', $request->fechaa));

        $query->orderBy('nc.fecha', 'desc');

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getNotaCredito($id)
    {
        $nc = DB::table('control_notas_credito')->where('id', $id)->first();

        if (!$nc) {
            return response()->json(['message' => 'Nota de crédito no encontrada'], 404);
        }

        return response()->json($nc);
    }

    public function guardarNotaCredito(Request $request, $id = null)
    {
        $rules = [
            'idnegocio' => 'nullable|integer',
            'idsucursal' => 'nullable|integer',
            'fecha' => 'nullable|date',
            'importe' => 'nullable|numeric',
        ];

        $request->validate($rules);

        $data = [
            'idnegocio' => $request->idnegocio,
            'negocio' => $request->negocio,
            'anio' => $request->anio,
            'mes' => $request->mes,
            'idsucursal' => $request->idsucursal,
            'sucursal' => $request->sucursal,
            'fecha' => $request->fecha,
            'id_nota_credito' => $request->id_nota_credito,
            'nota_credito' => $request->nota_credito,
            'numero_nc' => $request->numero_nc,
            'descripcion' => $request->descripcion,
            'importe' => floatval($request->importe ?? 0),
            'aplicado' => $request->aplicado,
            'observaciones' => $request->observaciones,
            'updated_at' => now(),
        ];

        if ($id) {
            DB::table('control_notas_credito')->where('id', $id)->update($data);
            return response()->json(['message' => 'Nota de crédito actualizada correctamente']);
        }

        $data['created_at'] = now();
        $newId = DB::table('control_notas_credito')->insertGetId($data);

        return response()->json([
            'message' => 'Nota de crédito creada correctamente',
            'id' => $newId,
        ], 201);
    }

    public function eliminarNotaCredito($id)
    {
        $nc = DB::table('control_notas_credito')->where('id', $id)->first();

        if (!$nc) {
            return response()->json(['message' => 'Nota de crédito no encontrada'], 404);
        }

        DB::table('control_notas_credito')->where('id', $id)->delete();

        return response()->json(['message' => 'Nota de crédito eliminada correctamente']);
    }
}
