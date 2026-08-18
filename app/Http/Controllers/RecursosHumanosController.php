<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class RecursosHumanosController extends Controller
{
    public function empleados_view(): View
    {
        return view('backend.recursoshumanos.empleados_view');
    }

    public function getEmpleados()
    {
        $datos = DB::select("SELECT cat.*,
        fnGetDatoNombreCatalogoById('cat_departamentos', cat.iddepartamento) as departamento,
        fnGetDatoNombreCatalogoById('negocios', cat.idnegocio) as negocio,
        fnGetDatoNombreCatalogoById('sucursales', cat.idsucursal) as sucursal
        FROM empleados cat
        where cat.estatus = 1");

        return $datos;
    }

    public function getEmpleadosFlutter(Request $request)
    {
        $query = DB::table('empleados as cat');

        // SELECT
        $query->select(
            'cat.*',
            DB::raw("fnGetDatoNombreCatalogoById('cat_departamentos', cat.`iddepartamento`) AS departamento"),
            DB::raw("fnGetDatoNombreCatalogoById('sucursales', cat.`idsucursal`) AS sucursal")
        );

         // FILTROS DINÁMICOS
        $query->when($request->iddepartamento, fn($q) =>
            $q->where('cat.iddepartamento', $request->iddepartamento)
        );

        $query->when($request->idsucursal, fn($q) =>
            $q->where('cat.idsucursal', $request->idsucursal)
        );

        $query->when($request->estatus, fn($q) =>
            $q->where('cat.estatus', $request->estatus)
        );

        // SEARCH
        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';

            $q->where(function ($sub) use ($search) {
                $sub->where('cat.numeroempleado', 'like', $search)
                    ->orWhere('cat.nombrecompleto', 'like', $search)
                    ->orWhere('cat.puesto', 'like', $search)
                    ->orWhereRaw("fnGetDatoNombreCatalogoById('sucursales', cat.idsucursal) LIKE ?", [$search]);
            });
        });

        // ORDEN
        $query->orderBy('cat.numeroempleado');

        $datos = $query->get();

        return $datos;
    }

    public function getArchivosEmpleado()
    {
        $datos = DB::select("SELECT cat.*
        FROM archivos_empleados cat;");

        return $datos;
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

        $activos = implode("','", config('rh.active_statuses'));
        $info_unidad = DB::select("SELECT * FROM empleados WHERE codigoempleado = '$request->codigoempleado' and estatus in ('$activos')");
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
            'numeroempleado'    => [
                'required',
                'string',
                'max:50',
                Rule::unique('empleados', 'numeroempleado')->ignore($id)
            ],
            'nombres'           => 'required|string|max:255',
            'apellidopaterno'  => 'required|string|max:255',
            'apellidomaterno'  => 'required|string|max:255',
            'iddepartamento'      => 'required|integer',
            'idsucursal'          => 'required|integer',
        ];

        $validated = $request->validate($rules);

        $validated['nombrecompleto'] = $request->nombrecompleto;
        $validated['telefono'] = $request->telefono;
        $validated['puesto'] = $request->puesto;
        $validated['fechaingreso'] = $request->fechaingreso;
        $validated['fechanacimiento'] = $request->fechanacimiento;
        $validated['nss'] = $request->nss;
        $validated['rfc'] = $request->rfc;
        $validated['curp'] = $request->curp;
        $validated['calle'] = $request->calle;
        $validated['numeroext'] = $request->numeroext;
        $validated['numeroint'] = $request->numeroint;
        $validated['colonia'] = $request->colonia;
        $validated['cp'] = $request->cp;
        $validated['ciudad'] = $request->ciudad;
        $validated['estado'] = $request->estado;
        $validated['correo'] = $request->correo;
        $validated['idestadocivil'] = $request->idestadocivil;
        $validated['lugarnacimiento'] = $request->lugarnacimiento;
        $validated['tiposangre'] = $request->tiposangre;
        $validated['contactoemergencianombre'] = $request->contactoemergencianombre;
        $validated['contactoemergenciaparentesco'] = $request->contactoemergenciaparentesco;
        $validated['contactoemergenciatelefono'] = $request->contactoemergenciatelefono;
        $validated['estatus'] = $request->estatus;

        if ($id) {
            // 🔄 UPDATE
            DB::table('empleados')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now(),
                    'idusuario' => $request->header('x-user-id')
                ]);

            return response()->json([
                'message' => 'Empleado actualizado correctamente'
            ]);
        }

        // 🆕 CREATE
        $empleadoId = DB::table('empleados')->insertGetId([
            ...$validated,
            'created_at' => now(),
            'updated_at' => now(),
            'idusuario' => $request->header('x-user-id')
        ]);

        return response()->json([
            'message' => 'Empleado creado correctamente',
            'id' => $empleadoId
        ], 201);
    }

    public function guardarArchivosEmpleado(Request $request, $id)
    {
        try {

            $request->validate([
                'archivo' => 'required|file|max:10240', // 10MB
                'descripcion' => 'nullable|string|max:255',
                'fechade' => 'nullable|date',
                'fechaa' => 'nullable|date'
            ]);

            // Guardar archivo
            $path = $request->file('archivo')->store('archivos/empleados', 'public');

            // 🆕 CREATE
            $archivo = DB::table('archivos_empleados')->insertGetId([
                'idempleado' => $id,
                'descripcion' => $request->descripcion,
                'fechade' => $request->fechade,
                'fechaa' => $request->fechaa,
                'archivo' => $path,
                'created_at' => now(),
                'updated_at' => now(),
                'estatus' => 1
            ]);

            return response()->json([
                'message' => 'Archivo guardado correctamente',
                'id' => $archivo
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar archivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function eliminarActivoFijo($id)
    {
        $deleted = DB::table('activos_fijos')
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

    public function getDocumentosEmpleados(Request $request)
    {
        $query = DB::table('archivos_empleados as a')
            ->leftJoin('empleados as e', 'a.idempleado', '=', 'e.id')
            ->select('a.*', 'e.nombrecompleto as empleado', 'e.numeroempleado');

        $query->when($request->idempleado, fn($q) =>
            $q->where('a.idempleado', $request->idempleado)
        );
        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';
            $q->where(function ($sub) use ($search) {
                $sub->where('a.nombre', 'like', $search)
                    ->orWhere('e.nombrecompleto', 'like', $search);
            });
        });

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    public function getBajaEmpleado($id)
    {
        $empleado = DB::table('empleados')->where('id', $id)->first();

        if (!$empleado) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        return response()->json([
            'empleado' => $empleado,
            'fechabaja' => $empleado->fechabaja,
            'estatus' => $empleado->estatus,
        ]);
    }

    public function getMovimientosActivosFijos(Request $request)
    {
        $query = DB::table('activos_fijos_movimientos as m')
            ->leftJoin('activos_fijos as af', 'm.idactivofijo', '=', 'af.id')
            ->leftJoin('empleados as eo', 'm.idempleado_origen', '=', 'eo.id')
            ->leftJoin('empleados as ed', 'm.idempleado_destino', '=', 'ed.id')
            ->select(
                'm.*',
                'af.clave as id_lizer',
                'af.descripcion as descripcion',
                'eo.nombrecompleto as responsable_origen',
                'ed.nombrecompleto as responsable_destino'
            );

        $query->when($request->idLizer, function ($q) use ($request) {
            $q->where('af.clave', 'like', '%' . $request->idLizer . '%');
        });
        $query->when($request->tipoMovimiento, fn($q) =>
            $q->where('m.tipo_movimiento', $request->tipoMovimiento)
        );
        $query->when($request->fechaInicio, fn($q) =>
            $q->where('m.fecha_movimiento', '>=', $request->fechaInicio)
        );
        $query->when($request->fechaFinal, fn($q) =>
            $q->where('m.fecha_movimiento', '<=', $request->fechaFinal)
        );
        $query->when($request->responsable, function ($q) use ($request) {
            $search = '%' . $request->responsable . '%';
            $q->where(function ($sub) use ($search) {
                $sub->where('eo.nombrecompleto', 'like', $search)
                    ->orWhere('ed.nombrecompleto', 'like', $search);
            });
        });

        $query->orderBy('m.fecha_movimiento', 'desc');

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }
}