<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use \PDF;

class OrdenServicioController extends Controller
{
    public function getOrdenesServicio(Request $request)
    {
        $query = DB::table('ordenes_servicio as os')
            ->leftJoin('talleres as t', 'os.idtaller', '=', 't.id')
            ->leftJoin('activos_fijos as af', 'os.idunidad', '=', 'af.id')
            ->select(
                'os.*',
                't.razonsocial as taller',
                'af.descripcion as unidad',
                'os.autorizacion_estatus as autorizacionEstatus',
                'os.autorizacion_comentario as autorizacionComentario'
            );

        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';
            $q->where(function ($sub) use ($search) {
                $sub->where('os.ordenservicio', 'like', $search)
                    ->orWhere('os.descripcionunidad', 'like', $search)
                    ->orWhere('t.razonsocial', 'like', $search);
            });
        });

        $query->when($request->fechade, fn($q) =>
            $q->where('os.fechaingreso', '>=', $request->fechade)
        );
        $query->when($request->fechaa, fn($q) =>
            $q->where('os.fechaingreso', '<=', $request->fechaa)
        );
        $query->when($request->idunidad, fn($q) =>
            $q->where('os.idunidad', $request->idunidad)
        );
        $query->when($request->idtaller, fn($q) =>
            $q->where('os.idtaller', $request->idtaller)
        );
        $query->when($request->autorizacionestatus, fn($q) =>
            $q->where('os.autorizacion_estatus', $request->autorizacionestatus)
        );

        $query->orderBy('os.created_at', 'desc');

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getOrdenServicio($id)
    {
        $orden = DB::table('ordenes_servicio as os')
            ->leftJoin('talleres as t', 'os.idtaller', '=', 't.id')
            ->leftJoin('activos_fijos as af', 'os.idunidad', '=', 'af.id')
            ->select(
                'os.*',
                't.razonsocial as taller',
                'af.descripcion as unidad',
                'os.autorizacion_estatus as autorizacionEstatus',
                'os.autorizacion_comentario as autorizacionComentario'
            )
            ->where('os.id', $id)
            ->first();

        if (!$orden) {
            return response()->json(['message' => 'Orden de servicio no encontrada'], 404);
        }

        // Obtener movimientos / detalle
        $movimientos = DB::table('ordenes_servicio_detalle as d')
            ->leftJoin('cat_tipos_servicio as s', 'd.idservicio', '=', 's.id')
            ->select('d.*', 's.nombre as servicio')
            ->where('d.idorden', $id)
            ->get();

        $orden->movimientos = $movimientos;

        return response()->json($orden);
    }

    public function guardarOrdenServicio(Request $request, $id = null)
    {
        $rules = [
            'idunidad' => 'nullable|integer',
            'idtaller' => 'nullable|integer',
            'fechaingreso' => 'nullable|date',
            'fechaentrega' => 'nullable|date',
            'estatusorden' => 'nullable|string|max:50',
            'autorizacionestatus' => 'nullable|string|max:50',
            'autorizacioncomentario' => 'nullable|string',
            'movimientos' => 'nullable|array',
            'movimientos.*.idservicio' => 'nullable|integer',
            'movimientos.*.importe' => 'nullable|numeric',
            'movimientos.*.observaciones' => 'nullable|string',
        ];

        $request->validate($rules);

        $totalimporte = 0;

        $data = [
            'idunidad' => $request->idunidad,
            'idtaller' => $request->idtaller,
            'fechaingreso' => $request->fechaingreso,
            'fechaentrega' => $request->fechaentrega,
            'usuario' => $request->usuario,
            'sucursal' => $request->sucursal,
            'idsucursal' => $request->idsucursal,
            'descripcionunidad' => $request->descripcionunidad,
            'kilometrajeunidad' => $request->kilometrajeunidad,
            'estatusorden' => $request->estatusorden ?? 'INICIADO',
            'autorizacion_estatus' => $request->autorizacionestatus ?? 'pendiente',
            'autorizacion_comentario' => $request->autorizacioncomentario,
            'totalimporte' => $totalimporte,
            'updated_at' => now(),
        ];

        if ($id) {
            DB::table('ordenes_servicio')->where('id', $id)->update($data);

            // Reemplazar detalle
            DB::table('ordenes_servicio_detalle')->where('idorden', $id)->delete();
        } else {
            // Generar número de orden
            $consecutivo = DB::table('ordenes_servicio')->count() + 1;
            $data['ordenservicio'] = 'OS-' . str_pad($consecutivo, 5, '0', STR_PAD_LEFT);
            $data['idusuario'] = auth()->id();
            $data['created_at'] = now();
            $id = DB::table('ordenes_servicio')->insertGetId($data);
        }

        // Guardar detalle
        if ($request->detalles) {
            foreach ($request->detalles as $index => $mov) {
                DB::table('ordenes_servicio_detalle')->insert([
                    'idorden' => $id,
                    'numeromovimiento' => $mov['numeromovimiento'] ?? ($index + 1),
                    'idservicio' => $mov['idservicio'] ?? null,
                    'importe' => floatval($mov['importe'] ?? 0),
                    'observaciones' => $mov['observaciones'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalimporte += floatval($mov['importe'] ?? 0);
            }
        }

        // Actualizar total
        DB::table('ordenes_servicio')->where('id', $id)->update(['totalimporte' => $totalimporte]);

        return response()->json([
            'message' => $request->isMethod('put') ? 'Orden actualizada correctamente' : 'Orden creada correctamente',
            'id' => $id,
        ], $request->isMethod('post') ? 201 : 200);
    }

    public function eliminarOrdenServicio($id)
    {
        $orden = DB::table('ordenes_servicio')->where('id', $id)->first();

        if (!$orden) {
            return response()->json(['message' => 'Orden de servicio no encontrada'], 404);
        }

        DB::table('ordenes_servicio_detalle')->where('idorden', $id)->delete();
        DB::table('ordenes_servicio')->where('id', $id)->delete();

        return response()->json(['message' => 'Orden de servicio eliminada correctamente']);
    }

    public function getOrdenServicioPdf($id)
    {
        $orden = DB::table('ordenes_servicio as os')
            ->leftJoin('talleres as t', 'os.idtaller', '=', 't.id')
            ->leftJoin('activos_fijos as af', 'os.idunidad', '=', 'af.id')
            ->select(
                'os.*',
                't.razonsocial as taller',
                't.domicilio as taller_domicilio',
                't.contacto as taller_contacto',
                't.telefono as taller_telefono',
                'af.descripcion as unidad',
                'af.marca as unidad_marca',
                'af.serie as unidad_serie'
            )
            ->where('os.id', $id)
            ->first();

        if (!$orden) {
            return response()->json(['message' => 'Orden de servicio no encontrada'], 404);
        }

        $detalles = DB::table('ordenes_servicio_detalle as d')
            ->leftJoin('cat_tipos_servicio as s', 'd.idservicio', '=', 's.id')
            ->select('d.*', 's.nombre as servicio')
            ->where('d.idorden', $id)
            ->get();

        $pdf = PDF::loadView('backend.ordenesservicio.ordenservicio_pdf', [
            'orden' => $orden,
            'detalles' => $detalles,
        ]);

        return $pdf->stream('orden_servicio_' . $orden->ordenservicio . '.pdf', ['Attachment' => 0]);
    }
}
