<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FinanzasController extends Controller
{
    public function presupuesto_view(): View
    {
        return view('backend.finanzas.presupuesto');
    }

    public function controlnc_view(): View
    {
        return view('backend.finanzas.controlnc_view');
    }

    public function presupuesto_gastos_view(): View
    {
        return view('backend.finanzas.presupuesto_gastos_view');
    }

    public function getListadoPresupuesto()
    {
        $presupuesto = DB::select("SELECT ccg.id AS presupuesto_id, ccg.`mes` AS presupuesto_mes, ccg.`periodo` AS mes_numero, gs.*,
        'mazatlan' AS sucursal,
        '1' AS sucursal_id,
        '1' AS negocio_id,
        '2024' AS periodo_seleccionado
        FROM cat_periodos ccg
        LEFT JOIN presupuesto_periodo gs ON ccg.`id` = gs.`idperiodo` AND gs.`idsucursal` = '1' AND gs.`anio` = '2024' AND gs.idnegocio = '1'"
        );

        foreach ($presupuesto as $key => $value)
		{
			$presupuesto_ventas = is_null($value->presupuesto_ventas) ? 0 : $value->presupuesto_ventas;
			$presupuesto_costos = is_null($value->presupuesto_costos) ? 0 : $value->presupuesto_costos;
			$presupuesto_gastos = is_null($value->presupuesto_gastos) ? 0 : $value->presupuesto_gastos;
			$presupuesto_otrosingresos = is_null($value->presupuesto_otrosingresos) ? 0 : $value->presupuesto_otrosingresos;
			$presupuesto_utilidadoperativa = ($presupuesto_ventas + $presupuesto_otrosingresos) - ($presupuesto_costos + $presupuesto_gastos);

			$presupuesto[$key]->presupuesto_ventas = $presupuesto_ventas;
			$presupuesto[$key]->presupuesto_costos = $presupuesto_costos;
			$presupuesto[$key]->presupuesto_gastos = $presupuesto_gastos;
			$presupuesto[$key]->presupuesto_otrosingresos = $presupuesto_otrosingresos;
			$presupuesto[$key]->presupuesto_utilidadoperativa = $presupuesto_utilidadoperativa;
		}

        return json_encode($presupuesto);
    }

    public function getListadoPresupuestoGastos($pPeriodo, $pIdsucursal)
    {
        $presupuesto = DB::select("SELECT ccg.`id` AS idconcepto, ccg.`descripcion`,
        '$pIdsucursal' AS idsucursal,
        '$pPeriodo' AS periodo, 
        IFNULL(pg.`presupuesto`, 0) AS presupuesto
        FROM presupuesto_gastos pg
        RIGHT JOIN cat_conceptos_gastos ccg ON ccg.`id` = pg.`idconcepto` AND pg.`periodo` = '$pPeriodo' AND pg.`idsucursal` = '$pIdsucursal'");

        $sucursales = app(CatalogosController::class)->getSucursales();

        foreach ($presupuesto as $key => $value)
        {
            $key_sucursal = array_search($value->idsucursal, array_column($sucursales, 'id'));

            $presupuesto[$key]->sucursal = $sucursales[$key_sucursal]->sucursal;
        }

        return $presupuesto;
    }

    public function savePresupuestoGastos(Request $request)
    {
        $items = $request->items;

        foreach($items as $dato)
        {
            $valid = DB::select("SELECT * FROM presupuesto_gastos WHERE periodo = '$dato[periodo]' AND idsucursal = '$dato[idsucursal]' AND idconcepto = '$dato[idconcepto]'");

            if(count($valid) > 0)
            {
                DB::table("presupuesto_gastos")
                ->where(
                    [
                        'periodo' => $dato["periodo"],
                        'idsucursal'   => $dato["idsucursal"],
                        'idconcepto'   => $dato["idconcepto"]
                    ]
                )->update(
                    [
                        'presupuesto'   => $dato["presupuesto"]
                    ]
                );
            }
            else
            {
                DB::table("presupuesto_gastos")->insert(
                    array(
                           'periodo' => $dato["periodo"],
                           'idsucursal'   => $dato["idsucursal"],
                           'idconcepto'   => $dato["idconcepto"],
                           'presupuesto'   => $dato["presupuesto"]
                    )
               );                
            }
        }

        return "si";
    }

    public function getAdendums()
    {
        $rows = DB::select("SELECT * 
        FROM adendum c 
        WHERE c.estatus = 1
        ORDER BY c.`idnegocio`, c.`anio`, c.`mes`;");

        return $rows;
    }

    public function getAdendum($id)
    {
        $adendum = DB::table('adendum')
            ->where('id', $id)
            ->first();

        if (!$adendum) {
            return response()->json([
                'message' => 'Adendum no encontrado'
            ], 404);
        }

        return response()->json([
            'adendum' => $adendum
        ]);
    }

    public function guardarAdendum(Request $request, $id = null)
    {
        $rules = [            
            'mes'   => 'required|string|max:100',
            'anio'   => 'required|string|max:10',
            'idnegocio'   => 'integer',
            'negocio'   => 'required|string|max:150',
            'rango'   => 'required|string|max:150',
            'porcentaje'  => 'required|numeric',
            'importe'  => 'required|numeric',
            'estatus'            => 'boolean',
        ];

        $validated = $request->validate($rules);

        if ($id) {
            // 🔄 UPDATE
            DB::table('adendum')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now()
                ]);

            return response()->json([
                'message' => 'Adendum actualizado correctamente'
            ]);
        }

        // 🆕 CREATE
        $adendumId = DB::table('adendum')->insertGetId([
            ...$validated,
            'estatus' => $validated['estatus'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Adendum creado correctamente',
            'id' => $adendumId
        ], 201);
    }

    public function getCumplimientoObjetivosConcentrado(Request $request)
    {
        if ($request->negocio == null) return null;

        $unidades = DB::select("SELECT 
        r.*,
        r.nombre AS sucursal,
        r.facturacion - r.rechazos AS total,
        0 AS apoyo,
        fn_obtener_porcentaje_adendum(r.idnegocio,r.anio,r.mes,tot.total_general, '%') AS porcentajeapoyo,        
        ((SELECT porcentajeapoyo)) AS coberturaapoyo,
        fn_obtener_porcentaje_adendum(r.idnegocio,r.anio,r.mes,tot.total_general, 'rango') AS rango,
        tot.total_general

        FROM (
        SELECT 
        t1.id,
        t1.sucursal as nombre,
        t2.idsucursal, t2.idnegocio, t2.anio, t2.mes, t2.negocio,
        COALESCE(SUM(t2.subtotalfactura - t2.descuentogpr), 0) AS facturacion,
        COALESCE(SUM(t2.importerechazo), 0) AS rechazos        
        FROM lizer_inroute_bees_demo.cat_sucursales t1
        LEFT JOIN fi_cumplmiento_objetivo_facturas t2 
        ON t1.id = t2.idsucursal
        AND t2.idnegocio = '$request->negocio'
        AND t2.anio = '$request->anio'
        AND t2.mes = '$request->mes'
        GROUP BY t1.id, t1.sucursal
        ) r

        CROSS JOIN (
        SELECT 
        COALESCE(SUM(t2.subtotalfactura - t2.descuentogpr), 0) - COALESCE(SUM(t2.importerechazo), 0) AS total_general
        FROM fi_cumplmiento_objetivo_facturas t2
        WHERE t2.idnegocio = '$request->negocio' AND t2.anio = '$request->anio' AND t2.mes = '$request->mes'
        ) tot;");

        return $unidades;
    }

    public function getCumplimientoObjetivoDetalle(Request $request)
    {
        $unidades = DB::table('fi_cumplmiento_objetivo_facturas AS u')
            ->selectRaw("u.*,
            (u.subtotalfactura - u.descuentogpr) AS subtotalfactura_menos_gpr,
            ((u.subtotalfactura - u.descuentogpr) + u.impuesto) AS totalfactura,
            (u.importerechazo + u.impuestorechazo) AS totalrechazo,
            ((SELECT subtotalfactura_menos_gpr) - u.importerechazo) AS subtotalfactura_apoyo_dist,
            (u.impuesto - u.impuestorechazo) AS impuestoapoyo,
            ((SELECT subtotalfactura_apoyo_dist) + (SELECT impuestoapoyo)) AS totalfactura_apoyo,
            u.estatus AS activo
            ")
            ->where('u.idnegocio', $request->negocio)
            ->where('u.idsucursal', $request->sucursal)
            ->where('u.anio', $request->anio)
            ->where('u.mes', $request->mes)
            ->get();

        return $unidades;
    }

    public function guardarCapturaFactura(Request $request, $id = null)
    {
        $rules = [
            'idnegocio'   => 'integer',
            'negocio'   => 'required|string',
            'idsucursal'   => 'integer',
            'sucursal'   => 'required|string',
            'anio'   => 'required|string',
            'mes'   => 'required|string',
            'fechaemision'   => 'required|date',
            'numerofactura'  => 'required|string',
            'subtotalfactura'  => 'required|numeric',
            'descuentogpr'  => 'required|numeric',
            'impuesto'  => 'required|numeric',
            'importerechazo'  => 'required|numeric',
            'impuestorechazo'  => 'required|numeric',
        ];

        $validated = $request->validate($rules);

        if ($id) {
            // 🔄 UPDATE
            DB::table('fi_cumplmiento_objetivo_facturas')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now(),
                    'idusuario' => '1'
                ]);

            return response()->json([
                'message' => 'Factura actualizada correctamente'
            ]);
        }

        // 🆕 CREATE
        $facturaId = DB::table('fi_cumplmiento_objetivo_facturas')->insertGetId([
            ...$validated,
            'estatus' => $validated['estatus'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
            'idusuario' => '1'
        ]);

        return response()->json([
            'message' => 'Factura creada correctamente',
            'id' => $facturaId
        ], 201);
    }

    public function eliminarCapturaFactura($id)
    {
        $deleted = DB::table('fi_cumplmiento_objetivo_facturas')
            ->where('id', $id)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'message' => 'Registro no encontrado'
            ], 404);
        }

        return response()->json([
            'message' => 'Registro eliminado correctamente'
        ], 200);
    }
}
