<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TallerController extends Controller
{
    public function getTalleres(Request $request)
    {
        $query = DB::table('talleres as t')

        ->leftJoin('sucursales as s', function ($join) {
            $join->on(DB::raw('FIND_IN_SET(s.id, t.sucursal)'), '>', DB::raw('0'));
        })
        ->leftJoin('cat_tipos_proveedor as tp', 't.idtipoproveedor', '=', 'tp.id')
        ->selectRaw('t.*, GROUP_CONCAT(s.nombre SEPARATOR ", ") AS sucursales_nombre, MAX(tp.nombre) AS tipoproveedor_nombre');

        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';

            $q->where(function ($sub) use ($search) {
                $sub->where('t.razonsocial', 'like', $search)
                ->orWhere('t.nombrecorto', 'like', $search)
                ->orWhere('t.contacto', 'like', $search)
                ->orWhere('s.nombre', 'like', $search);
            });

        });

        $query->when($request->idsucursal, fn($q) =>
            $q->whereRaw("FIND_IN_SET(?, t.sucursal)", [$request->idsucursal])
        );

        $query->when($request->idtipoproveedor, fn($q) =>
            $q->where('t.idtipoproveedor', $request->idtipoproveedor)
        );

        $query->where('t.estatus', 1)->groupBy('t.id')->orderBy('t.razonsocial');

        $perPage = $request->per_page ?? 10;

        if ($perPage == 500 || $request->all == 1) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    public function getTaller($id)
    {
        $taller = DB::table('talleres')->where('id', $id)->first();

        if (!$taller) {
            return response()->json(['message' => 'Taller no encontrado'], 404);
        }

        return response()->json(['taller' => $taller]);
    }

    public function guardarTaller(Request $request, $id = null)
    {
        $rules = [
            'razonsocial' => 'required|string|max:200',
            'nombrecorto' => 'nullable|string|max:100',
            'tiposervicio' => 'nullable|string|max:150',
            'tipoproveedor' => 'nullable|string|max:100',
            'idtipoproveedor' => 'nullable|integer',
            'contacto' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:30',
            'domicilio' => 'nullable|string',
            'estatus' => 'boolean',
        ];

        $validated = $request->validate($rules);

        // sucursal puede ser array o string
        /*$sucursal = $request->sucursal;
        if (is_array($sucursal)) {
            $sucursal = implode(',', $sucursal);
        }*/
        $validated['sucursal'] = $request->idsucursal;

        if ($id) {
            DB::table('talleres')->where('id', $id)->update([
                ...$validated,
                'updated_at' => now(),
            ]);
            return response()->json(['message' => 'Taller actualizado correctamente']);
        }

        $consecutivo = DB::table('talleres')->count() + 1;
        $clavelizer = 'PROV-' . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);

        $tallerId = DB::table('talleres')->insertGetId([
            ...$validated,
            'clavelizer' => $clavelizer,
            'estatus' => $validated['estatus'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Taller creado correctamente',
            'id' => $tallerId,
        ], 201);
    }

    public function eliminarTaller($id)
    {
        $taller = DB::table('talleres')->where('id', $id)->first();

        if (!$taller) {
            return response()->json(['message' => 'Taller no encontrado'], 404);
        }

        DB::table('talleres')->where('id', $id)->update([
            'estatus' => 0,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Taller eliminado correctamente']);
    }
}
