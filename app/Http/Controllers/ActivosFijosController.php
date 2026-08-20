<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Exports\ActivosFijosExport;
use Maatwebsite\Excel\Facades\Excel;

class ActivosFijosController extends Controller
{
    public function getActivosFijos(Request $request)
    {
        $query = DB::table('activos_fijos as cat')
            ->leftJoin('activos_fijos_unidades as afu', 'cat.id', '=', 'afu.idactivofijo')
            ->leftJoin('activos_fijos_asignacion as afa', 'cat.id', '=', 'afa.idactivofijo');


        // SELECT
        $query->select(
            'cat.*',
            'afu.numeroeconomico',
            DB::raw("fnGetDatoNombreCatalogoById('cat_tipos_activos_fijos', cat.idtipoactivo) AS tipoactivo"),
            DB::raw("fnGetDatoNombreCatalogoById('empleados', afa.idempleadoasignado) AS empleadoasignado")
        );


        // FILTROS DINÁMICOS
        $query->when($request->idtipoactivo, fn($q) =>
            $q->where('cat.idtipoactivo', $request->idtipoactivo)
        );

        $query->when($request->idsucursal, fn($q) =>
            $q->where('cat.idsucursal', $request->idsucursal)
        );

        $query->when($request->iddepartamento, fn($q) =>
            $q->where('afa.iddepartamento', $request->iddepartamento)
        );

        $query->when($request->estatus, fn($q) =>
            $q->where('cat.estatus', $request->estatus)
        );

        $query->when($request->numeroeconomico, fn($q) =>
            $q->where('afu.numeroeconomico', $request->numeroeconomico)
        );


        // SEARCH
        $query->when($request->search, function ($q) use ($request) {
            $search = '%' . $request->search . '%';

            $q->where(function ($sub) use ($search) {
                $sub->where('cat.descripcion', 'like', $search)
                    ->orWhere('cat.clave', 'like', $search)
                    ->orWhere('afu.numeroeconomico', 'like', $search)
                    ->orWhereRaw("fnGetDatoNombreCatalogoById('empleados', afa.idempleadoasignado) LIKE ?", [$search]);
            });
        });


        // ORDEN
        $query->orderByRaw('CAST(afu.numeroeconomico AS UNSIGNED)')
            ->orderBy('cat.clave');


        return $query->paginate($request->per_page ?? 10);
    }

    public function getActivoFijo($id)
    {
        $rows = collect(DB::select("SELECT af.*,
        afu.propietario, afu.`placas`, afu.accesorios, afu.entidadfederativa,
        afu.numeroeconomico, afu.`idaseguradora`,
        afu.`numeropoliza`, afu.`inciso`, afu.`cobertura`, afu.`fechavencimientopoliza`, afu.`costopoliza`, afu.`combustibleasignado`,
        afu.`clunes`, afu.`cmartes`, afu.`cmiercoles`, afu.`cjueves`, afu.`cviernes`, afu.`csabado`, afu.`cdomingo`, afu.ultimoodometro, afu.idrutas,
        emp.`nombrecompleto` AS empleadoasignado,
        emp.`id` AS idempleadoasignado,
        suc.`nombre` AS sucursal,
        (SELECT tc.`idproveedor` FROM tickets_combustibles tc WHERE tc.`idvehiculo` = af.id ORDER BY id DESC LIMIT 1) AS idproveedorultimo
        FROM activos_fijos af
        LEFT JOIN activos_fijos_unidades afu ON af.id = afu.idactivofijo
        LEFT JOIN activos_fijos_asignacion afa ON af.id = afa.idactivofijo
        LEFT JOIN empleados emp ON afa.idempleadoasignado = emp.id
        LEFT JOIN sucursales suc ON af.idsucursal = suc.id
        WHERE af.id = ?", [$id]))->first();

        return $rows;
    }

    public function getAsignacionesActivosFijos(Request $request)
    {
        $query = DB::table('activos_fijos_asignacion as afa')
            ->join('activos_fijos as af', 'afa.idactivofijo', '=', 'af.id')
            ->join('empleados as emp', 'afa.idempleadoasignado', '=', 'emp.id')
            ->leftJoin('cat_tipos_activos_fijos as ctaf', 'af.idtipoactivo', '=', 'ctaf.id')
            ->leftJoin('sucursales as suc', 'afa.idsucursal', '=', 'suc.id')
            ->leftJoin('cat_departamentos as dep', 'afa.iddepartamento', '=', 'dep.id')
            ->select([
                'afa.id',
                'af.descripcion as activo',
                'ctaf.nombre as tipoactivo',
                'emp.nombrecompleto as responsable',
                'suc.nombre as sucursal',
                'dep.nombre as departamento',
                'afa.fechaasignacion',
                'afa.estadoasignacion',
                'afa.tipoactivo as tipoasignacion',
            ]);

        $query->when($request->idactivofijo,    fn($q) => $q->where('afa.idactivofijo', $request->idactivofijo));
        $query->when($request->idempleado,       fn($q) => $q->where('afa.idempleadoasignado', $request->idempleado));
        $query->when($request->tipoactivo,       fn($q) => $q->where('af.idtipoactivo', $request->tipoactivo));
        $query->when($request->idsucursal,       fn($q) => $q->where('afa.idsucursal', $request->idsucursal));
        $query->when($request->estadoasignacion, fn($q) => $q->where('afa.estadoasignacion', $request->estadoasignacion));

        if ($request->filled('fechade') && $request->filled('fechaa')) {
            $query->whereBetween('afa.fechaasignacion', [$request->fechade, $request->fechaa]);
        }

        $query->orderBy('afa.fechaasignacion', 'desc');

        return $query->paginate($request->per_page ?? 10);
    }

    public function getAsignacionActivoFijo($id)
    {
        $rows = collect(DB::select("SELECT * FROM activos_fijos_asignacion afa WHERE afa.id = ?", [$id]))->first();

        return $rows;
    }

    public function getArchivosActivoFijo($id)
    {
        $datos = DB::select("SELECT cat.* FROM archivos_activos_fijos cat WHERE cat.idactivofijo = ?", [$id]);

        return $datos;
    }

    public function exportExcel(Request $request)
    {
        $filters = $request->only([
            'search',
            'idtipoactivo',
            'idsucursal',
            'iddepartamento',
            'estatus'
        ]);

        return Excel::download(
            new ActivosFijosExport($filters),
            'activos-fijos.xlsx'
        );
    }

    public function guardarActivoFijo(Request $request, $id = null)
    {
        $rules = [
            'numeroeconomico'    => [
                'required',
                'string',
                'max:50',
                Rule::unique('activos_fijos_unidad', 'numeroeconomico')->ignore($id)
            ],
            'idtipoactivo'  => 'required|numeric',
            'idnegocio'  => 'required|numeric',
            'idsucursal'  => 'required|numeric',
            'numeroeconomico'   => 'required|string',
            'anio'   => 'required|string|max:255',
            'marca'   => 'required|string|max:255',
            'descripcion'   => 'required|string|max:255',
            'serie'   => 'nullable|string|max:255',
            'fechaadquisicion'    => 'nullable|date',
            'fechareemplazo'    => 'nullable|date',
            'condiciones'  => 'sometimes',
            'pin'  => 'sometimes',
            'precio'  => 'nullable|numeric',
            'caracteristicas'  => 'nullable|string',
            'estatus'    => 'required|string',
        ];

        $validated = $request->validate($rules);

        $datosunidad = array();

        $idlizer = '';

        if(isset($request->datos_tecnicos))
        {
            $infosucursal = DB::table('sucursales')->where('id', $request->idsucursal)->first();
            $idlizer = $infosucursal->clave . '' . $validated['numeroeconomico'] . '' . $request->anio;

            $datosunidad['numeroeconomico'] = $request->numeroeconomico;
            $datosunidad['propietario'] = $request->datos_tecnicos['propietario'];
            $datosunidad['placas'] = $request->datos_tecnicos['placas'];
            $datosunidad['accesorios'] = $request->datos_tecnicos['accesorios'];
            $datosunidad['entidadfederativa'] = $request->datos_tecnicos['entidadfederativa'];
            //$datosunidad['numeromotor'] = $request->datos_tecnicos['numeromotor'];
            //$datosunidad['idtipovehiculo'] = $request->datos_tecnicos['idtipovehiculo'];
            //$datosunidad['idtipocombustible'] = $request->datos_tecnicos['idtipocombustible'];
            //$datosunidad['idtipotransmision'] = $request->datos_tecnicos['idtipotransmision'];
            //$datosunidad['capacidadtanque'] = $request->datos_tecnicos['capacidadtanque'];
            //$datosunidad['color'] = $request->datos_tecnicos['color'];
            $datosunidad['idaseguradora'] = $request->datos_tecnicos['idaseguradora'];
            $datosunidad['numeropoliza'] = $request->datos_tecnicos['numeropoliza'];
            $datosunidad['inciso'] = $request->datos_tecnicos['inciso'];
            $datosunidad['cobertura'] = $request->datos_tecnicos['cobertura'];
            $datosunidad['fechavencimientopoliza'] = $request->datos_tecnicos['fechavencimientopoliza'];
            $datosunidad['costopoliza'] = $request->datos_tecnicos['costopoliza'];
            $datosunidad['combustibleasignado'] = $request->datos_tecnicos['combustibleasignado'];
            $datosunidad['clunes'] = $request->datos_tecnicos['clunes'];
            $datosunidad['cmartes'] = $request->datos_tecnicos['cmartes'];
            $datosunidad['cmiercoles'] = $request->datos_tecnicos['cmiercoles'];
            $datosunidad['cjueves'] = $request->datos_tecnicos['cjueves'];
            $datosunidad['cviernes'] = $request->datos_tecnicos['cviernes'];
            $datosunidad['csabado'] = $request->datos_tecnicos['csabado'];
            $datosunidad['cdomingo'] = $request->datos_tecnicos['cdomingo'];
            $datosunidad['idrutas'] = $request->datos_tecnicos['idrutas'];
        }
        else
        {
            if($id)
            {
                $infoactivofijo = DB::table('activos_fijos')->where('id', $id)->first();
                $idlizer = $infoactivofijo->clave;
            }
            else
            {
                $infosucursal = DB::table('activos_fijos as a')
                ->where('a.idtipoactivo', 2)
                ->selectRaw("LPAD(COUNT(*) + 1, 3, '0') AS numero")
                ->first();

                $idlizer = "EC".$infosucursal->numero;   
            }
        }

        unset($validated['numeroeconomico']);

        if ($id) {
            // 🔄 UPDATE
            DB::table('activos_fijos')
            ->where('id', $id)
            ->update([
                ...$validated,
                'clave' => $idlizer,
                'updated_at' => now()
            ]);

            if(isset($request->datos_tecnicos))
            {
                DB::table('activos_fijos_unidades')
                ->where('idactivofijo', $id)
                ->update([
                    ...$datosunidad,
                    'updated_at' => now()
                ]);
            }

            return response()->json([
                'message' => 'Activo fijo actualizado correctamente'
            ]);
        }

        // 🆕 CREATE
        $activoFijoId = DB::table('activos_fijos')->insertGetId([
            ...$validated,
            'clave' => $idlizer,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if(isset($request->datos_tecnicos))
        {
            DB::table('activos_fijos_unidades')
            ->insertGetId([
                ...$datosunidad,
                'idactivofijo' => $activoFijoId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json([
            'message' => 'Activo fijo creado correctamente',
            'id' => $activoFijoId
        ], 201);
    }

    public function guardarAsignacionActivoFijo(Request $request, $id = null)
    {
        $rules = [
            'idactivofijo' => [
                'required', 'integer',
                Rule::unique('activos_fijos_asignacion', 'idactivofijo')
                    ->ignore($id)
                    ->where(function ($query) {
                        $query->where('estadoasignacion', 'Activa');
                    }),
            ],
            'tipoactivo' => 'required|integer',
            'idsucursal'   => 'required|integer',
            'iddepartamento'   => 'required|integer',
            'idempleadoasignado'   => 'required|integer',
            'fechaasignacion'   => 'required|date',
            'estadoasignacion'   => 'required|string'
        ];

        $validated = $request->validate($rules);

        $validated['observaciones'] = $request->observaciones;
        //$validated['fechade'] = $request->fechade;
        //$validated['fechaa'] = $request->fechaa;

        if ($id) {
            // 🔄 UPDATE
            DB::table('activos_fijos_asignacion')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now()
                ]);

            return response()->json([
                'message' => 'Activo fijo asignado correctamente'
            ]);
        }

        // 🆕 CREATE
        $activoFijoId = DB::table('activos_fijos_asignacion')->insertGetId([
            ...$validated,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Activo fijo asignado correctamente',
            'id' => $activoFijoId
        ], 201);
    }

    public function guardarArchivosActivoFijo(Request $request, $id)
    {
        try {

            $request->validate([
                'archivo' => 'required|file|max:10240', // 10MB
                'descripcion' => 'nullable|string|max:255',
                'fechade' => 'nullable|date',
                'fechaa' => 'nullable|date'
            ]);

            // Guardar archivo
            $path = $request->file('archivo')->store('archivos/activos_fijos', 'public');

            // 🆕 CREATE
            $archivo = DB::table('archivos_activos_fijos')->insertGetId([
                'idactivofijo' => $id,
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

    public function eliminarEmpleado($id)
    {
        $deleted = DB::table('empleados')
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
