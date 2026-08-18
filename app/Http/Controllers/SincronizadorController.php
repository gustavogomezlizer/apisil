<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SincronizadorController extends Controller
{
    public function saveVentas(Request $request)
    {
        //return $request->json;
        $tableguardar_pro = $request->tipo;
        $tablaguardar_tmp = $tableguardar_pro == "ventas" ? "tmp_ventas" : "tmp_devoluciones";        
        $columnaclavesucursal = $tableguardar_pro == "ventas" ? "clavemodulo" : "clavemodulodev";

        DB::table($tablaguardar_tmp)->insert(json_decode($request->json, true));

        $productos = DB::connection('mysql_fb')->select("SELECT p.*, cp.`clave` AS claveproveedor
        FROM cat_productos p
        INNER JOIN cat_proveedor cp ON cp.`id` = p.`proveedor`");

        $sucursales = DB::connection('mysql_fb')->select("SELECT * FROM cat_sucursales");

        $negocios = DB::select("SELECT * FROM negocios");

        $ventas = DB::select("SELECT * FROM $tablaguardar_tmp GROUP BY codigoproducto");

        foreach($ventas as $v)
        {
            $key_producto = array_search($v->codigoproducto, array_column($productos, 'codigo'));

            if($key_producto != "")
            {
                $claveproveedor = $productos[$key_producto]->claveproveedor;

                $key_negocio = array_search($claveproveedor, array_column($negocios, 'clave'));

                $idnegocio = $negocios[$key_negocio]->id;

                DB::table($tablaguardar_tmp)->where('codigoproducto', $v->codigoproducto)->update(
                    [
                        'idnegocio' => $idnegocio,
                        'clavenegocio' => $claveproveedor
                    ]
                );
            }
        }

        $ventas = DB::select("SELECT * FROM $tablaguardar_tmp GROUP BY codigoalmacen");

        foreach($ventas as $v)
        {
            $key_sucursal = array_search($v->codigoalmacen, array_column($sucursales, $columnaclavesucursal));

            if($key_sucursal != "")
            {
                DB::table($tablaguardar_tmp)->where('codigoalmacen', $v->codigoalmacen)->update(
                    [
                        'idsucursal' => $sucursales[$key_sucursal]->id
                    ]
                );
            }
        }

        $ventas = DB::select("SELECT *, SUM(total) AS totalventas, SUM(costo) AS totalcosto
        FROM $tablaguardar_tmp 
        GROUP BY fecha, idnegocio, idsucursal");

        foreach($ventas as $v)
        {
            DB::table($tableguardar_pro)->insert(
                array(
                       'periodo' => str_replace('.0', '', $v->periodo),
                       'fecha'   => $v->fecha,
                       'idnegocio'   => $v->idnegocio,
                       'idsucursal'   => $v->idsucursal,
                       'venta'   => $v->totalventas,
                       'costo'   => $v->totalcosto
                )
           );
        }

        DB::table($tablaguardar_tmp)->truncate();

        return "si";
    }
}
