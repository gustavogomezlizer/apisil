<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperacionesController extends Controller
{
    public function getUnidades()
    {
        $unidades = DB::table('unidades AS u')
            ->selectRaw("u.*, 
            fnGetDatoNombreCatalogoById('cat_tipo_vehiculo', u.idtipovehiculo) AS tipo_vehiculo,
            CONCAT_WS(' ', fnGetDatoNombreCatalogoById('cat_marcas', u.idmarca), fnGetDatoNombreCatalogoById('cat_modelos', u.idmodelo), fnGetDatoNombreCatalogoById('cat_anios', u.idanio)) AS descripcion,
            u.estatus AS activo
            ")
            ->where('u.estatus', 1)
            ->get();

        return $unidades;
    }

    public function getEmpleado($id)
    {
        $empleado = DB::table('empleados')
            ->where('id', $id)
            ->first();

        if (!$empleado) {
            return response()->json([
                'message' => 'Empleado no encontrado'
            ], 404);
        }

        return response()->json([
            'empleado' => $empleado
        ]);
    }

    public function guardarEmpleado(Request $request)
    {
        if($request->codigoempleado == "")
        {
            return "Favor de capturar un codigo de empleado";
        }

        if($request->nombre == "")
        {
            return "Favor de capturar un nombre";
        }

        $info_unidad = DB::select("SELECT * FROM empleados WHERE codigoempleado = '$request->codigoempleado' and estatus = 1");
        $id = $request->id;

        $datos = $request->input();
        unset($datos["id"]);

        if(count($info_unidad) > 0)
        {
            if($id == 0 || $info_unidad[0]->id != $id)
            {
                return "El codigo de empleado ingresado ya existe";
            }
            else
            {
            }
        }

        $datos["idusuario"] = GET_LOGIN_ID();
        $datos["fechaactualizacion"] = date("Y-m-d H:i:s");

        //print_r($datos);die();

        if($request->id == 0)
        {
            $datos["fechaalta"] = date("Y-m-d H:i:s");            

            $id = DB::table("empleados")->insert($datos);
        }
        else
        {
            DB::table("empleados")->where(array("id" => $id))->update($datos);
        }

        return $id;
    }

    public function guardarEmpleadoVue(Request $request, $id = null)
    {
        $rules = [
            'codigoempleado'    => [
                'required',
                'string',
                'max:50',
                Rule::unique('empleados', 'codigoempleado')->ignore($id)
            ],
            'nombres'           => 'required|string|max:150',
            'apellido_paterno'  => 'required|string|max:150',
            'apellido_materno'  => 'nullable|string|max:150',
            'nombrecompleto'  => 'nullable|string|max:255',
            'iddepartamento'      => 'required|integer',
            'idpuesto'            => 'required|integer',
            'idsucursal'          => 'required|integer',
            'idestadocivil'       => 'required|integer',
            'fecha_ingreso'     => 'required|date',
            'fecha_nacimiento'  => 'required|date',
            'nss'               => 'nullable|string|max:20',
            'rfc'               => 'nullable|string|max:13',
            'curp'              => 'nullable|string|max:18',
            'calle'             => 'nullable|string|max:150',
            'numero_ext'        => 'nullable|string|max:10',
            'numero_int'        => 'nullable|string|max:10',
            'cp'                => 'nullable|string|max:10',
            'ciudad'            => 'nullable|string|max:100',
            'estado'            => 'nullable|string|max:100',
            'telefono'          => 'nullable|string|max:20',
            'correo'            => 'nullable|email|max:150',
            'estatus'            => 'boolean',
        ];

        $validated = $request->validate($rules);

        if ($id) {
            // 🔄 UPDATE
            DB::table('empleados')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now()
                ]);

            return response()->json([
                'message' => 'Empleado actualizado correctamente'
            ]);
        }

        // 🆕 CREATE
        $empleadoId = DB::table('empleados')->insertGetId([
            ...$validated,
            'estatus' => $validated['estatus'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Empleado creado correctamente',
            'id' => $empleadoId
        ], 201);
    }
}