<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class CombustibleController extends Controller
{
    public function getCombustibles(Request $request)
    {
        $query = DB::table('tickets_combustibles as c')
            ->leftJoin('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->leftJoin('talleres as t', 'c.idproveedor', '=', 't.id')
            ->select(
                'c.*',
                'af.descripcion as descripcionunidad',
                't.razonsocial as proveedor'
            )
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $query->when($request->unidad, fn($q) =>
            $q->where('c.idvehiculo', $request->unidad)
        );
        $query->when($request->sucursal, fn($q) =>
            $q->where('c.idsucursal', $request->sucursal)
        );
        $query->when($request->fechaInicio, fn($q) =>
            $q->where('c.fechacarga', '>=', $request->fechaInicio)
        );
        $query->when($request->fechaFin, fn($q) =>
            $q->where('c.fechacarga', '<=', $request->fechaFin)
        );
        $query->when($request->estatus, fn($q) =>
            $q->where('c.estatus', $request->estatus)
        );
        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';
            $q->where(function ($sub) use ($search) {
                $sub->where('c.foliointerno', 'like', $search)
                    ->orWhere('c.folioproveedor', 'like', $search)
                    ->orWhere('c.numerounidad', 'like', $search);
            });
        });

        $query->orderBy('c.fechacarga', 'desc')->orderBy('c.id', 'desc');

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getCombustible($id)
    {
        $row = DB::table('tickets_combustibles')->where('id', $id)->first();

        if (!$row) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        return response()->json($row);
    }

    public function guardarCombustible(Request $request, $id = null)
    {
        $rules = [
            'vehicleId' => 'required',
            'fecha' => 'required|date',
            'litros' => 'required|numeric|min:0',
            'importe' => 'required|numeric|min:0',
            'odometroFinal' => 'required|numeric|min:0',
        ];

        $request->validate($rules);

        if (!ACTIVO_FIJO_ES_TIPO_UNIDAD($request->vehicleId)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de combustible.'], 422);
        }

        // Obtener odómetro anterior
        $ultimoodometro = $request->odometroFinal; // default
        $prevRecord = DB::table('tickets_combustibles')
            ->where('idvehiculo', $request->vehicleId)
            ->when($id, fn($q) => $q->where('id', '!=', $id))
            ->orderBy('fechacarga', 'desc')
            ->orderBy('id', 'desc')
            ->first();
        if ($prevRecord) {
            $ultimoodometro = $prevRecord->odometrocarga;
        }

        $consumo = max(floatval($request->odometroFinal) - floatval($ultimoodometro), 0);
        $litros = floatval($request->litros);
        $rendimiento = $litros > 0 ? round($consumo / $litros, 4) : 0;

        // Generar folio interno
        $foliointerno = $id ? null : 'COMB-' . str_pad(DB::table('tickets_combustibles')->count() + 1, 6, '0', STR_PAD_LEFT);

        $data = [
            'idvehiculo' => $request->vehicleId,
            'numerounidad' => $request->numerounidad ?? null,
            'idproveedor' => $request->idproveedor,
            'idnegocio' => $request->idnegocio,
            'idsucursal' => $request->idsucursal,
            'idrutas' => $request->idrutas ?? null,
            'folioproveedor' => $request->folioProveedor,
            'fechacarga' => $request->fecha,
            'semana' => $request->semana ?? null,
            'litros' => $litros,
            'importe' => floatval($request->importe),
            'ultimoodometro' => floatval($ultimoodometro),
            'odometrocarga' => floatval($request->odometroFinal),
            'consumo' => $consumo,
            'rendimiento' => $rendimiento,
            'kmsasignados' => $request->kmsAsignados ?? null,
            'consumos' => $request->consumos ?? null,
            'responsable' => $request->responsable ?? null,
            'observaciones' => $request->observaciones ?? null,
            'estatus' => 'Capturado',
            'updated_at' => now(),
        ];

        if ($id) {
            DB::table('tickets_combustibles')->where('id', $id)->update($data);
            return response()->json(['message' => 'Registro actualizado correctamente']);
        }

        $data['foliointerno'] = $foliointerno;
        $data['created_at'] = now();
        $newId = DB::table('tickets_combustibles')->insertGetId($data);

        return response()->json([
            'message' => 'Registro de combustible guardado correctamente',
            'id' => $newId,
        ], 201);
    }

    public function eliminarCombustible($id)
    {
        $row = DB::table('tickets_combustibles')->where('id', $id)->first();

        if (!$row) {
            return response()->json(['message' => 'Registro no encontrado'], 404);
        }

        DB::table('tickets_combustibles')->where('id', $id)->delete();

        return response()->json(['message' => 'Registro eliminado correctamente']);
    }

    public function getRendimiento(Request $request)
    {
        $periodicidad = strtolower((string) $request->input('periodicidad', 'mensual'));

        $periodoExpr = match ($periodicidad) {
            'diario'  => "DATE_FORMAT(c.fechacarga, '%Y-%m-%d')",
            'semanal' => "DATE_FORMAT(c.fechacarga, '%x-%v')",
            default   => "DATE_FORMAT(c.fechacarga, '%Y-%m')",
        };

        $query = DB::table('tickets_combustibles as c')
            ->select(
                'c.idvehiculo',
                'c.numerounidad',
                DB::raw("$periodoExpr as periodo"),
                DB::raw('SUM(c.consumo) as kilometros'),
                DB::raw('SUM(c.litros) as litros'),
                DB::raw('ROUND(SUM(c.consumo) / NULLIF(SUM(c.litros), 0), 2) as rendimiento_kml'),
                DB::raw('ROUND(SUM(c.importe) / NULLIF(SUM(c.consumo), 0), 2) as costo_por_km')
            )
            ->groupBy('c.idvehiculo', 'c.numerounidad', 'periodo')
            ->orderBy('periodo', 'desc')
            ->orderBy('c.numerounidad', 'asc')
            ->whereIn('c.idvehiculo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        // Filtros de la vista: cada uno se omite si viene vacío.
        $query->when($request->filled('numeroeconomico'), fn($q) => $q->where('c.numerounidad', 'like', '%' . $request->numeroeconomico . '%'));
        $query->when($request->filled('idsucursal'), fn($q) => $q->where('c.idsucursal', $request->idsucursal));
        $query->when($request->filled('fechade'), fn($q) => $q->where('c.fechacarga', '>=', $request->fechade));
        $query->when($request->filled('fechaa'), fn($q) => $q->where('c.fechacarga', '<=', $request->fechaa));
        $query->when($request->filled('sucursales'), function ($q) use ($request) {
            $sucursales = array_values(array_filter(array_map('trim', explode(',', $request->sucursales))));
            if (count($sucursales) > 0) {
                $q->whereIn('c.idsucursal', $sucursales);
            }
        });

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getCostoKm(Request $request)
    {
        $periodicidad = strtolower((string) $request->input('periodicidad', 'mensual'));

        $periodoExpr = match ($periodicidad) {
            'semanal' => "DATE_FORMAT(fechacarga, '%x-%v')",
            'anual'   => "DATE_FORMAT(fechacarga, '%Y')",
            default   => "DATE_FORMAT(fechacarga, '%Y-%m')",
        };

        // Combustible por unidad y periodo
        $combustible = DB::table('tickets_combustibles as c')
            ->select('c.idvehiculo', 'c.numerounidad')
            ->addSelect(DB::raw("$periodoExpr as periodo"))
            ->addSelect(DB::raw('SUM(c.importe) as costo_combustible'))
            ->addSelect(DB::raw('SUM(c.consumo) as kilometros'))
            ->groupBy('c.idvehiculo', 'c.numerounidad', 'periodo')
            ->whereIn('c.idvehiculo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        // Mantenimiento por unidad y periodo (ordenes_servicio)
        $mantenimientoExpr = str_replace('fechacarga', 'o.fechaingreso', $periodoExpr);
        $mantenimiento = DB::table('ordenes_servicio as o')
            ->select('o.idunidad as idvehiculo')
            ->addSelect(DB::raw("$mantenimientoExpr as periodo"))
            ->addSelect(DB::raw('SUM(o.totalimporte) as costo_mantenimiento'))
            ->groupBy('o.idunidad', 'periodo')
            ->whereIn('o.idunidad', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        // Filtros de la vista: cada uno se omite si viene vacío.
        $combustible->when($request->filled('numeroeconomico'), fn($q) => $q->where('c.numerounidad', 'like', '%' . $request->numeroeconomico . '%'));
        $combustible->when($request->filled('idsucursal'), fn($q) => $q->where('c.idsucursal', $request->idsucursal));
        $combustible->when($request->filled('sucursal'), function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('c.idsucursal', $request->sucursal)
                    ->orWhere('c.sucursal', 'like', '%' . $request->sucursal . '%');
            });
        });
        $combustible->when($request->filled('fechade'), fn($q) => $q->where('c.fechacarga', '>=', $request->fechade));
        $combustible->when($request->filled('fechaa'), fn($q) => $q->where('c.fechacarga', '<=', $request->fechaa));
        $combustible->when($request->filled('sucursales'), function ($q) use ($request) {
            $sucursales = array_values(array_filter(array_map('trim', explode(',', $request->sucursales))));
            if (count($sucursales) > 0) {
                $q->whereIn('c.idsucursal', $sucursales);
            }
        });

        $mantenimiento->when($request->filled('idsucursal'), fn($q) => $q->where('o.idsucursal', $request->idsucursal));
        $mantenimiento->when($request->filled('sucursal'), function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('o.idsucursal', $request->sucursal)
                    ->orWhere('o.sucursal', 'like', '%' . $request->sucursal . '%');
            });
        });
        $mantenimiento->when($request->filled('fechade'), fn($q) => $q->where('o.fechaingreso', '>=', $request->fechade));
        $mantenimiento->when($request->filled('fechaa'), fn($q) => $q->where('o.fechaingreso', '<=', $request->fechaa));
        $mantenimiento->when($request->filled('sucursales'), function ($q) use ($request) {
            $sucursales = array_values(array_filter(array_map('trim', explode(',', $request->sucursales))));
            if (count($sucursales) > 0) {
                $q->whereIn('o.idsucursal', $sucursales);
            }
        });

        $query = DB::query()
            ->fromSub($combustible, 'cb')
            ->leftJoinSub($mantenimiento, 'mt', function ($join) {
                $join->on('cb.idvehiculo', '=', 'mt.idvehiculo')
                    ->on('cb.periodo', '=', 'mt.periodo');
            })
            ->select(
                'cb.idvehiculo',
                'cb.numerounidad',
                'cb.periodo',
                'cb.costo_combustible',
                'cb.kilometros',
                DB::raw('COALESCE(mt.costo_mantenimiento, 0) as costo_mantenimiento'),
                DB::raw('(cb.costo_combustible + COALESCE(mt.costo_mantenimiento, 0)) as costo_total'),
                DB::raw('ROUND((cb.costo_combustible + COALESCE(mt.costo_mantenimiento, 0)) / NULLIF(cb.kilometros, 0), 2) as costo_por_km')
            )
            ->orderBy('cb.periodo', 'desc')
            ->orderBy('cb.numerounidad', 'asc');

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getMantenimientos(Request $request)
    {
        $query = DB::table('ordenes_servicio as os')
            ->leftJoin('talleres as t', 'os.idtaller', '=', 't.id')
            ->leftJoin('activos_fijos as af', 'os.idunidad', '=', 'af.id')
            ->select(
                'os.*',
                't.razonsocial as taller',
                'af.descripcion as unidad',
                'os.autorizacion_estatus as autorizacionEstatus'
            )
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';
            $q->where(function ($sub) use ($search) {
                $sub->where('os.ordenservicio', 'like', $search)
                    ->orWhere('os.descripcionunidad', 'like', $search);
            });
        });
        $query->when($request->fechade, fn($q) => $q->where('os.fechaingreso', '>=', $request->fechade));
        $query->when($request->fechaa, fn($q) => $q->where('os.fechaingreso', '<=', $request->fechaa));
        $query->when($request->idsucursal, fn($q) => $q->where('os.idsucursal', $request->idsucursal));

        $query->orderBy('os.created_at', 'desc');
        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getListaTickets(Request $request)
    {
        $query = DB::table('tickets_combustibles as c')
            ->leftJoin('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->leftJoin('talleres as t', 'c.idproveedor', '=', 't.id')
            ->select(
                'c.*',
                'af.descripcion as descripcionunidad',
                't.razonsocial as proveedor'
            )
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS());

        // Filtros de la vista: cada uno se omite si viene vacío.
        $query->when($request->filled('unidad'), fn($q) => $q->where('c.idvehiculo', $request->unidad));
        $query->when($request->filled('idsucursal'), fn($q) => $q->where('c.idsucursal', $request->idsucursal));
        $query->when($request->filled('fechade'), fn($q) => $q->where('c.fechacarga', '>=', $request->fechade));
        $query->when($request->filled('fechaa'), fn($q) => $q->where('c.fechacarga', '<=', $request->fechaa));
        $query->when($request->filled('estatus'), fn($q) => $q->where('c.estatus', $request->estatus));
        $query->when($request->filled('search'), function ($q) use ($request) {
            $search = '%' . $request->search . '%';
            $q->where(function ($sub) use ($search) {
                $sub->where('c.foliointerno', 'like', $search)
                    ->orWhere('c.folioproveedor', 'like', $search)
                    ->orWhere('c.numerounidad', 'like', $search);
            });
        });

        // Sucursales permitidas del usuario (separadas por coma); se omite si no llegan.
        $query->when($request->filled('sucursales'), function ($q) use ($request) {
            $sucursales = array_values(array_filter(array_map('trim', explode(',', $request->sucursales))));
            if (count($sucursales) > 0) {
                $q->whereIn('c.idsucursal', $sucursales);
            }
        });

        $query->orderBy('c.fechacarga', 'desc')->orderBy('c.id', 'desc');

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getCapturaTicket($id)
    {
        $row = DB::table('tickets_combustibles as c')
            ->leftJoin('activos_fijos as af', 'c.idvehiculo', '=', 'af.id')
            ->leftJoin('talleres as t', 'c.idproveedor', '=', 't.id')
            ->select(
                'c.*',
                'af.descripcion as descripcionunidad',
                'af.numeroeconomico',
                'af.marca',
                'af.serie',
                't.razonsocial as proveedor',
                't.domicilio as proveedor_domicilio',
                't.contacto as proveedor_contacto',
                't.telefono as proveedor_telefono'
            )
            ->where('c.id', $id)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Ticket no encontrado'], 404);
        }

        return response()->json($row);
    }

    public function guardarCapturaTicket(Request $request, $id = null)
    {
        if (!$request->filled('idunidad') || !ACTIVO_FIJO_ES_TIPO_UNIDAD($request->idunidad)) {
            return response()->json(['message' => 'Solo se permiten activos de tipo Unidad en el módulo de combustible.'], 422);
        }

        $data = [
            'idvehiculo' => $request->idunidad,
            'numerounidad' => $request->numerounidad ?? null,
            'idproveedor' => $request->idproveedor ?? null,
            'idnegocio' => $request->idnegocio ?? null,
            'idsucursal' => $request->idsucursal ?? null,
            'negocio' => $request->negocio ?? null,
            'sucursal' => $request->sucursal ?? null,
            'idrutas' => $request->idruta ?? null,
            'idempleado' => $request->idempleado ?? null,
            'idrutas' => $request->idrutas ?? null,
            'empleadoasignado' => $request->empleadoasignado ?? null,
            'kmsasignados' => $request->kmsasignados ?? null,
            'folioproveedor' => $request->folioproveedor ?? '-',
            'fechacarga' => $request->fechacarga,
            'semana' => $request->semana ?? null,
            'litros' => floatval($request->litros ?? 0),
            'costolitro' => floatval($request->costolitro ?? 0),
            'importe' => floatval($request->importe ?? 0),
            'ultimoodometro' => floatval($request->ultimoodometro ?? 0),
            'odometrocarga' => floatval($request->odometrocarga ?? 0),
            'consumo' => floatval($request->consumo ?? 0),
            'rendimiento' => floatval($request->rendimiento ?? 0),
            'observaciones' => $request->observaciones ?? null,
            'combustibleasignado' => floatval($request->combustibleasignado ?? 0),
            'estatus' => 'Capturado',
            'idusuario' => $request->header('x-user-id'),
            'updated_at' => now(),
        ];

        if ($id) {
            DB::table('tickets_combustibles')->where('id', $id)->update($data);
            return response()->json(['message' => 'Ticket actualizado correctamente']);
        }

        $consecutivo = DB::table('tickets_combustibles')->count() + 1;
        $data['foliointerno'] = 'COMB-' . str_pad($consecutivo, 6, '0', STR_PAD_LEFT);
        $data['created_at'] = now();
        $data['idusuario'] = $request->header('x-user-id');
        $newId = DB::table('tickets_combustibles')->insertGetId($data);

        return response()->json([
            'message' => 'Ticket registrado correctamente',
            'id' => $newId,
        ], 201);
    }

    public function eliminarCapturaTicket($id)
    {
        $row = DB::table('tickets_combustibles')->where('id', $id)->first();

        if (!$row) {
            return response()->json(['message' => 'Ticket no encontrado'], 404);
        }

        DB::table('tickets_combustibles ')->where('id', $id)->delete();

        return response()->json(['message' => 'Ticket eliminado correctamente']);
    }
}
