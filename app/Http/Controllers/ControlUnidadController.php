<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\CatalogosController;
use App\Http\Controllers\RecursosHumanosController;
use \PDF;

class ControlUnidadController extends Controller
{
    public function unidades_view(): View
    {
        return view('backend.controlunidad.unidades_view');
    }

    public function asignacion_view(): View
    {
        $catalogos = new CatalogosController();
        $rh = new RecursosHumanosController();

        $sucursales = $catalogos->getSucursales();
        $empleados = $rh->getEmpleados();
        $rutas = $catalogos->getRutas();

        return view('backend.controlunidad.asignacion_view', array("sucursales" => $sucursales, "empleados" => $empleados, "rutas" => $rutas));
    }

    public function registrokm_view(): View
    {
        return view('backend.controlunidad.registrokm_view');
    }

    public function reportekm_view(): View
    {
        return view('backend.controlunidad.reportekm_view');
    }

    public function checklist_view(): View
    {
        return view('backend.controlunidad.checklist_view');
    }

    public function checklistnew_view(Request $request): View
    {
        $id = $request->id;

        $vista = $id == 0 ? "crear" : "ver";

        if($id == 0)
        {
            $catalogos = new CatalogosController();
            $info_checklist = array();
            $revisionfisicaexterior = $catalogos->getCheckList("REVISION FISICA EXTERIOR");
            $revisionfisicainterior = $catalogos->getCheckList("REVISION FISICA INTERIOR");
            $revisionmecanicabasica = $catalogos->getCheckList("REVISION MECANICA BASICA");
            $documentacion = $catalogos->getCheckList("DOCUMENTACION");
            $herramientasbasicas = $catalogos->getCheckList("HERRAMIENTAS BASICAS");
        }
        else
        {
            $info_checklist = $this->getRegistroCheckListById($id)[0];
            $revisionfisicaexterior = $this->getRegistrosCheckListDetalle($id, "REVISION FISICA EXTERIOR");
            $revisionfisicainterior = $this->getRegistrosCheckListDetalle($id, "REVISION FISICA INTERIOR");
            $revisionmecanicabasica = $this->getRegistrosCheckListDetalle($id, "REVISION MECANICA BASICA");
            $documentacion = $this->getRegistrosCheckListDetalle($id, "DOCUMENTACION");
            $herramientasbasicas = $this->getRegistrosCheckListDetalle($id, "HERRAMIENTAS BASICAS");
        }

        return view('backend.controlunidad.checklistnew_view',
            array(
                "info_checklist" => $info_checklist,
                "rfe" => $revisionfisicaexterior,
                "rfi" => $revisionfisicainterior,
                "rmb" => $revisionmecanicabasica,
                "documentacion" => $documentacion,
                "hb" => $herramientasbasicas,
                "vista" => $vista
            )
        );
    }

    public function ordenesservicio_view(): View
    {
        return view('backend.controlunidad.ordenesservicio_view');
    }

    public function ordenservicionew_view(Request $request): View
    {
        $id = $request->id;

        $vista = $id == 0 ? "crear" : "ver";

        if($id == 0)
        {
            return view('backend.controlunidad.ordenservicionew_view', 
                array(
                    "vista" => $vista,
                    "info" => array()
                )
            );
        }
        else
        {
            $info_orden = $this->getOrdenesServicioById($id);

            return view('backend.controlunidad.ordenservicionew_view', 
                array(
                    "vista" => $vista,
                    "info" => $info_orden
                )
            );
        }        
    }

    public function getUnidades()
    {
        $datos = DB::select("SELECT u.*,
        fnGetDatoNombreCatalogoById('cat_tipo_vehiculo', u.idtipovehiculo) AS tipovehiculo,
        fnGetDatoNombreCatalogoById('cat_anios', u.idanio) AS anio,
        fnGetDatoNombreCatalogoById('cat_marcas', u.idmarca) AS marca,
        fnGetDatoNombreCatalogoById('cat_modelos', u.idmodelo) AS modelo,
        fnGetDatoNombreCatalogoById('cat_colores', u.idcolor) AS color,
        avse.`idsucursal`, avse.`idempleado`, avse.`idruta`
        FROM unidades u
        LEFT JOIN asignacion_vehiculo_sucursal_empleado avse ON u.`id` = avse.`idvehiculo`
        WHERE u.estatus = 1 ORDER BY u.numeroeconomico");

        return $datos;
    }

    public function getAsignacionByIdUnidad(Request $request)
    {
        $datos = DB::select("SELECT u.*,
        fnGetDatoNombreCatalogoById('cat_tipo_vehiculo', u.idtipovehiculo) AS tipovehiculo,
        fnGetDatoNombreCatalogoById('cat_anios', u.idanio) AS anio,
        fnGetDatoNombreCatalogoById('cat_marcas', u.idmarca) AS marca,
        fnGetDatoNombreCatalogoById('cat_modelos', u.idmodelo) AS modelo,
        fnGetDatoNombreCatalogoById('cat_colores', u.idcolor) AS color,
        fnGetDatoNombreCatalogoById('sucursales', avse.`idsucursal`) AS sucursal,
        fnGetDatoNombreCatalogoById('empleados', avse.`idempleado`) AS empleado,
        IFNULL(fnGetDatoNombreCatalogoById('rutas', avse.`idruta`), 'SIN RUTA') AS ruta,
        fnGetDatoNombreCatalogoById('cat_departamentos', e.iddepartamento) AS departamento,
        fnGetDatoNombreCatalogoById('cat_puestos', e.idpuesto) AS puesto,
        avse.`idsucursal`, avse.`idempleado`, avse.`idruta`
        FROM unidades u
        LEFT JOIN asignacion_vehiculo_sucursal_empleado avse ON u.`id` = avse.`idvehiculo`
        LEFT JOIN empleados e ON avse.idempleado = e.id
        WHERE u.id = '$request->idvehiculo'");

        return $datos;
    }

    public function getRegistroKilometraje(Request $request)
    {
        $datos = DB::select("SELECT rk.*, 
        u.numeroeconomico, 
        fnGetDatoNombreCatalogoById('cat_modelos', u.idmodelo) AS modelo,
        fnGetDatoNombreCatalogoById('cat_nivel_gasolina', rk.nivelgasolinainicio) AS nivelgasolinainiciofraccion
        FROM registro_kilometraje rk
        INNER JOIN unidades u ON rk.idunidad = u.id
        WHERE rk.idusuarioregistrofinal = 0");

        return $datos;
    }

    public function getReporteRegistroKilometraje(Request $request)
    {
        $where_idunidad = "";

        if($request->idunidad != "0")
        {
            $where_idunidad = " AND rk.idunidad = '$request->idunidad' ";
        }

        $datos = DB::select("SELECT CONCAT_WS(' - ', u.`id`, fnGetDatoNombreCatalogoById('cat_modelos', u.`idmodelo`)) AS unidad, rk.idunidad, rk.`fechainicio`, rk.kminicio, rk.nivelgasolinainicio, rk.kmfinal, rk.nivelgasolinafinal,
        u.`capacidadtanque`,
        (rk.kmfinal - rk.kminicio) AS kmrecorridosruta,
        IFNULL((SELECT sub.kminicio FROM registro_kilometraje sub WHERE sub.idunidad = rk.`idunidad` AND sub.id > rk.id LIMIT 1), 0) AS kmpersonal,
        ((SELECT kmpersonal) - rk.`kmfinal`) AS kmrecorridospersonal_operacion,
        IF((SELECT kmrecorridospersonal_operacion) < 0, 0, (SELECT kmrecorridospersonal_operacion)) AS kmrecorridospersonal,
        ((SELECT kmrecorridospersonal) + (SELECT kmrecorridosruta)) AS kmrecorridostotal,
        ((rk.`nivelgasolinainicio` - rk.`nivelgasolinafinal`) * (SELECT capacidadtanque)) AS litrosconsumidosruta,
        (SELECT sub.nivelgasolinainicio FROM registro_kilometraje sub WHERE sub.idunidad = rk.`idunidad` AND sub.id > rk.id LIMIT 1) AS nivelgasolinapersonal,
        ((rk.`nivelgasolinafinal` - (SELECT nivelgasolinapersonal)) * (SELECT capacidadtanque)) AS litrosconsumidospersonal_operacion,
        IFNULL(IF((SELECT litrosconsumidospersonal_operacion) < 0, 0, (SELECT litrosconsumidospersonal_operacion)), 0) AS litrosconsumidospersonal,
        ((SELECT litrosconsumidosruta) + (SELECT litrosconsumidospersonal)) AS litrosconsumidostotal,
        fnGetDatoNombreCatalogoById('cat_nivel_gasolina', rk.nivelgasolinainicio) AS nivelgasolinainiciofraccion,
        fnGetDatoNombreCatalogoById('cat_nivel_gasolina', rk.nivelgasolinafinal) AS nivelgasolinafinalfraccion,
        fnGetDatoNombreCatalogoById('cat_nivel_gasolina', (SELECT nivelgasolinapersonal)) AS nivelgasolinapersonalfraccion
        FROM registro_kilometraje rk
        INNER JOIN unidades u ON rk.`idunidad` = u.`id`
        WHERE rk.fechainicio BETWEEN '$request->fechade' AND '$request->fechaa' $where_idunidad");

        return $datos;
    }

    public function getRegistrosCheckList(Request $request)
    {
        $datos = DB::select("SELECT rcl.*, 
        CONCAT_WS(' - ', u.`id`, fnGetDatoNombreCatalogoById('cat_modelos', u.`idmodelo`)) AS unidad,
        (SELECT COUNT(*) FROM registro_check_list_detalle rcld WHERE rcld.idregistro = rcl.id AND rcld.respuesta = 0) AS incosistencias
        FROM registro_check_list rcl
        INNER JOIN unidades u ON rcl.`idunidad` = u.`id`");

        return $datos;
    }

    public function getRegistroCheckListById($id)
    {
        $datos = DB::select("SELECT rcl.*, 
        CONCAT_WS(' - ', u.`id`, fnGetDatoNombreCatalogoById('cat_modelos', u.`idmodelo`)) AS unidad,
        fnGetDatoNombreCatalogoById('cat_marcas', u.`idmarca`) AS marca,
        fnGetDatoNombreCatalogoById('cat_tipo_vehiculo', u.`idtipovehiculo`) AS tipovehiculo,
        fnGetDatoNombreCatalogoById('cat_modelos', u.`idmodelo`) AS modelo,
        (SELECT COUNT(*) FROM registro_check_list_detalle rcld WHERE rcld.idregistro = rcl.id AND rcld.respuesta = 0) AS incosistencias
        FROM registro_check_list rcl
        INNER JOIN unidades u ON rcl.`idunidad` = u.`id` WHERE rcl.id = '$id'");

        return $datos;
    }

    public function getRegistrosCheckListDetalle($id, $parte)
    {
        $datos = DB::select("SELECT cat.`descripcion`, rcld.`respuesta`, rcld.`comentarios` 
        FROM cat_checklist cat
        LEFT JOIN registro_check_list_detalle rcld ON cat.`id` = rcld.`idchecklist`
        WHERE rcld.`idregistro` = '$id' AND cat.parte = '$parte'");

        return $datos;
    }

    public function getOrdenesServicio(Request $request)
    {
        $datos = DB::select("SELECT os.*,
        CONCAT_WS(' - ', u.`id`, fnGetDatoNombreCatalogoById('cat_modelos', u.`idmodelo`)) AS unidad,
        cp.`razonsocial` AS proveedor
        FROM ordenes_servicio os
        INNER JOIN unidades u ON os.`idunidad` = u.`id`
        INNER JOIN cat_proveedor cp ON os.`idproveedor` = cp.`id`");

        return $datos;
    }

    public function getOrdenesServicioById($idorden)
    {
        $datos = DB::select("SELECT os.*, osd.*,
        u.numeroeconomico, u.placas,
        fnGetDatoNombreCatalogoById('cat_marcas', u.idmarca) AS marca,
        fnGetDatoNombreCatalogoById('cat_modelos', u.idmodelo) AS modelo,
        fnGetDatoNombreCatalogoById('empleados', avse.`idempleado`) AS empleado,
        IFNULL(fnGetDatoNombreCatalogoById('rutas', avse.`idruta`), 'SIN RUTA') AS ruta,
        cp.`razonsocial` AS proveedor, cp.domicilio, cp.ciudad, cp.contacto, cp.telefono
        FROM ordenes_servicio os
        INNER JOIN ordenes_servicio_detalle osd ON os.`id` = osd.`idorden`
        INNER JOIN unidades u ON os.`idunidad` = u.`id`
        INNER JOIN cat_proveedor cp ON os.`idproveedor` = cp.`id`
        LEFT JOIN asignacion_vehiculo_sucursal_empleado avse ON u.`id` = avse.`idvehiculo`
        WHERE os.`id` = '$idorden'");

        return $datos;
    }

    public function getOrdenServicioPdf(Request $request)
    {
        $idorden = $request->id;

        $datos = DB::select("SELECT os.*, osd.*,
        u.numeroeconomico, u.placas,
        fnGetDatoNombreCatalogoById('cat_marcas', u.idmarca) AS marca,
        fnGetDatoNombreCatalogoById('cat_modelos', u.idmodelo) AS modelo,
        fnGetDatoNombreCatalogoById('empleados', avse.`idempleado`) AS empleado,
        IFNULL(fnGetDatoNombreCatalogoById('rutas', avse.`idruta`), 'SIN RUTA') AS ruta,
        cp.`razonsocial` AS proveedor, cp.domicilio, cp.ciudad, cp.contacto, cp.telefono
        FROM ordenes_servicio os
        INNER JOIN ordenes_servicio_detalle osd ON os.`id` = osd.`idorden`
        INNER JOIN unidades u ON os.`idunidad` = u.`id`
        INNER JOIN cat_proveedor cp ON os.`idproveedor` = cp.`id`
        LEFT JOIN asignacion_vehiculo_sucursal_empleado avse ON u.`id` = avse.`idvehiculo`
        WHERE os.`id` = '$idorden'");

        $pdf = PDF::loadView('backend.controlunidad.ordenservicio_pdf',
            array(
                "info" => $datos
            )
        );

        return $pdf->stream('report.pdf', array('Attachment' => 0));
        //return $pdf->download('invoice.pdf');        

        return $datos;
    }

    public function guardarAsignacion(Request $request)
    {
        $info_unidad = DB::select("SELECT * FROM asignacion_vehiculo_sucursal_empleado avse WHERE avse.idvehiculo = '$request->idvehiculo'");

        $datos["idvehiculo"] = $request->idvehiculo;
        $datos["idsucursal"] = $request->idsucursal;
        $datos["idempleado"] = $request->idempleado;
        $datos["idruta"] = $request->idruta;
        $datos["idusuario"] = GET_LOGIN_ID();
        $datos["fechaactualizacion"] = date("Y-m-d H:i:s");

        if(count($info_unidad) == 0)
        {
            $datos["fechaalta"] = date("Y-m-d H:i:s");            

            $id = DB::table("asignacion_vehiculo_sucursal_empleado")->insert($datos);
        }
        else
        {
            $id = $info_unidad[0]->id;
            DB::table("asignacion_vehiculo_sucursal_empleado")->where(array("id" => $id))->update($datos);
        }

        return $id;
    }

    public function guardar_unidad(Request $request)
    {
        if($request->numeroeconomico == "")
        {
            return "Favor de capturar un numero economico";
        }

        if($request->serie == "")
        {
            return "Favor de capturar una serie";
        }

        if($request->kmactual == "")
        {
            return "Favor de capturar un km actual";
        }

        $info_unidad = DB::select("SELECT * FROM unidades WHERE numeroeconomico = '$request->numeroeconomico' and estatus = 1");
        $id = $request->id;

        $datos = $request->input();
        unset($datos["id"]);

        if(count($info_unidad) > 0)
        {
            if($id == 0 || $info_unidad[0]->id != $id)
            {
                return "El numero economico ingresado ya existe";
            }
            else
            {
            }
        }

        $datos["idusuario"] = GET_LOGIN_ID();
        $datos["fechaactualizacion"] = date("Y-m-d H:i:s");

        if($request->id == 0)
        {
            $datos["fechaalta"] = date("Y-m-d H:i:s");            

            $id = DB::table("unidades")->insert($datos);
        }
        else
        {
            DB::table("unidades")->where(array("id" => $id))->update($datos);
        }

        return $id;
    }

    public function guardarRegistroKilometraje(Request $request)
    {
        $id = $request->id;
        $idunidad = $request->idunidad;

        if($idunidad == "0")
        {
            return "Favor de seleccionar una unidad";
        }
                
        $ultimokmregistrado = DB::select("SELECT IFNULL(MAX(rk.`kmfinal`), 0) AS ultimokmregistrado FROM registro_kilometraje rk WHERE idunidad = '$idunidad'")[0]->ultimokmregistrado;

        if($id == "0")
        {
            $registro_abierto = DB::select("SELECT * FROM registro_kilometraje rk WHERE idunidad = '$idunidad' and rk.idusuarioregistrofinal = 0");

            if(count($registro_abierto) > 0)
            {
                return "La unidad tiene un registro abierto. Favor de finalizar el registro";
            }
        }        

        if($request->fechainicio == "")
        {
            return "Favor de capturar una fecha de inicio";
        }

        if($request->horainicio == "")
        {
            return "Favor de capturar una hora de inicio";
        }

        if($request->kminicio == "")
        {
            return "Favor de capturar un kilometraje de inicio";
        }

        if($request->kminicio < $ultimokmregistrado)
        {
            return "El kilometraje registra no puede ser menor al ultimo registrado($ultimokmregistrado)";
        }

        if($request->nivelgasolinainicio == "0")
        {
            return "Favor de capturar un nivel de gasolina de inicio";
        }

        if($id > 0)
        {
            $ultimokmregistrado = DB::select("SELECT IFNULL(MAX(rk.`kminicio`), 0) AS ultimokmregistrado FROM registro_kilometraje rk WHERE idunidad = '$idunidad'")[0]->ultimokmregistrado;

            if($request->fechafinal == "")
            {
                return "Favor de capturar una fecha final";
            }

            if($request->horafinal == "")
            {
                return "Favor de capturar una hora final";
            }

            if($request->kmfinal == "")
            {
                return "Favor de capturar un kilometraje final";
            }

            if($request->kmfinal < $ultimokmregistrado)
            {
                return "El kilometraje registra no puede ser menor al ultimo registrado($ultimokmregistrado)";
            }

            if($request->nivelgasolinafinal == "0")
            {
                return "Favor de capturar un nivel de gasolina final";
            }
        }

        $datos = $request->input();
        unset($datos["id"]);

        if($id == 0)
        {
            $datos["fecharegistroinicio"] = date("Y-m-d H:i:s");
            $datos["idusuarioregistroinicio"] = GET_LOGIN_ID();
            
            $id = DB::table("registro_kilometraje")->insert($datos);
        }
        else
        {
            $datos["fecharegistrofinal"] = date("Y-m-d H:i:s");
            $datos["idusuarioregistrofinal"] = GET_LOGIN_ID();

            DB::table("registro_kilometraje")->where(array("id" => $id))->update($datos);
        }

        return $id;
    }

    public function guardarRegistroCheckList(Request $request)
    {
        $datos = $request->input();
        unset($datos["checklist"]);

        $datos["idusuario"] = GET_LOGIN_ID();
        $datos["fechahora"] = date("Y-m-d H:i:s");

        $id = DB::table("registro_check_list")->insertGetId($datos);

        if($id > 0)
        {
            foreach($request->checklist as $item)
            {
                $item["idregistro"] = $id;
                DB::table("registro_check_list_detalle")->insert($item);
            }
        }

        return $id;
    }

    public function guardarOrdenServicio(Request $request)
    {
        $datos = $request->input();
        unset($datos["items_conceptos"]);

        $datos["idusuario"] = GET_LOGIN_ID();
        $datos["fechahora"] = date("Y-m-d H:i:s");

        $id = DB::table("ordenes_servicio")->insertGetId($datos);

        if($id > 0)
        {
            foreach($request->items_conceptos as $item)
            {
                $item["idorden"] = $id;
                DB::table("ordenes_servicio_detalle")->insert($item);
            }
        }

        return $id;
    }
}