<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CatalogosController extends Controller
{
    public function getNegocios()
    {
        $negocios = DB::select("SELECT * from negocios cs where cs.estatus = 1");

        return $negocios;
    }

    public function getSucursales()
    {
        $rows = DB::select("SELECT * from sucursales cs where cs.estatus = 1");

        return $rows;
    }

    public function getRutas()
    {
        $rows = DB::connection('mysql_fb')->select("SELECT * FROM cat_rutas cr WHERE cr.`status` = 1
        UNION
        SELECT * FROM inroutem_lizer_pet.`cat_rutas` crb WHERE crb.status = 1
        ORDER BY ruta");

        return $rows;
    }

    public function getTiposVehiculo()
    {
        $rows = DB::select("SELECT * from cat_tipo_vehiculo cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getTiposCombustible()
    {
        $rows = DB::select("SELECT * from cat_tipos_combustible cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getTiposTransmision()
    {
        $rows = DB::select("SELECT * from cat_tipos_transmision cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getAnios()
    {
        $rows = DB::select("SELECT * from cat_anios cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getMarcas()
    {
        $rows = DB::select("SELECT * from cat_marcas cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getModelos(Request $request)
    {
        $rows = DB::select("SELECT * from cat_modelos cat where cat.estatus = 1 and cat.idmarca = '$request->idmarca' order by cat.nombre");

        return $rows;
    }

    public function getColores()
    {
        $rows = DB::select("SELECT * from cat_colores cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getDepartamentos()
    {
        $rows = DB::select("SELECT * from cat_departamentos cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getNivelGasolina()
    {
        $rows = DB::select("SELECT * from cat_nivel_gasolina cat where cat.estatus = 1 order by cat.niveldecimales");

        return $rows;
    }

    public function getCheckList($parte)
    {
        $rows = DB::select("SELECT * from cat_checklist cat where cat.parte = '$parte'");

        return $rows;
    }

    public function getPuestos()
    {
        $rows = DB::select("SELECT * from cat_puestos cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getEstadoCivil()
    {
        $rows = DB::select("SELECT * from cat_estado_civil cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getTiposLicencia()
    {
        $rows = DB::select("SELECT * from cat_tipos_licencia cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getProveedores()
    {
        $rows = DB::select("SELECT * from cat_proveedor cat where cat.estatus = 1 order by cat.razonsocial");

        return $rows;
    }

    public function getProveedorById(Request $request)
    {
        $rows = DB::select("SELECT * from cat_proveedor cat where cat.id = '$request->id'");

        return $rows;
    }

    public function getConceptosMantenimiento()
    {
        $rows = DB::select("SELECT * from cat_concepto_mantenimiento cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getCondiciones()
    {
        $rows = DB::select("SELECT * from cat_condiciones cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getTiposActivosFijos()
    {
        $rows = DB::select("SELECT * from cat_tipos_activos_fijos cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getEstatusActivosFijos()
    {
        $rows = DB::select("SELECT * from cat_estatus_activos_fijos cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getTiposCoberturaSeguro()
    {
        $rows = DB::select("SELECT * from cat_tipo_cobertura_seguro cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    public function getEstatusRh()
    {
        $rows = DB::select("SELECT * from cat_estatus_rh cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    // Alias usados por VehiculoForm (nombres alternativos)
    public function getMarcasVehiculo()
    {
        return $this->getMarcas();
    }

    public function getTiposVehiculoAlias()
    {
        return $this->getTiposVehiculo();
    }

    public function getTiposCombustibleAlias()
    {
        return $this->getTiposCombustible();
    }

    public function getTiposProveedor()
    {
        $rows = DB::select("SELECT * from cat_tipos_proveedor cat where cat.estatus = 1 order by cat.nombre");

        return $rows;
    }

    /*public function getActivosFijos(Request $request)
    {
        $rows = DB::select("SELECT *,
        fnGetDatoNombreCatalogoById('cat_tipos_activos_fijos', cat.`idtipoactivo`) AS tipoactivo,
        fnGetDatoNombreCatalogoById('cat_condiciones', cat.`idcondicion`) AS condicion
        FROM activos_fijos cat
        WHERE cat.idtipoactivo = IF('$request->idtipoactivo' != '', '$request->idtipoactivo', cat.idtipoactivo)
        AND cat.estatus = IF('$request->estatus' != '', '$request->estatus', cat.estatus)
        ORDER BY cat.modelo;");

        return $rows;
    }

    public function getActivoFijo($id)
    {
        $rows = collect(DB::select("SELECT *,
        fnGetDatoNombreCatalogoById('cat_tipos_activos_fijos', cat.`idtipoactivo`) AS tipoactivo,
        fnGetDatoNombreCatalogoById('cat_condiciones', cat.`idcondicion`) AS condicion
        FROM activos_fijos cat 
        WHERE cat.id = ?", [$id]))->first();

    return $rows;
    }

    public function guardarActivoFijo(Request $request, $id = null)
    {
        $rules = [
            'clave'    => [
                'required',
                'string',
                'max:50',
                Rule::unique('activos_fijos', 'clave')->ignore($id)
            ],
            'marca'   => 'required|string|max:255',
            'modelo'   => 'required|string|max:255',
            'idtipoactivo'  => 'required|numeric',
            'fechaadquisicion'    => 'required|date',
            'idcondicion'  => 'required|numeric',
            'precio'  => 'required|numeric',
            'estatus'    => 'required|string',
        ];

        $validated = $request->validate($rules);

        $validated['caracteristicas'] = $request->caracteristicas;
        $validated['serie'] = $request->serie;

        if ($id) {
            // 🔄 UPDATE
            DB::table('activos_fijos')
                ->where('id', $id)
                ->update([
                    ...$validated,
                    'updated_at' => now()
                ]);

            return response()->json([
                'message' => 'Activo fijo actualizado correctamente'
            ]);
        }

        // 🆕 CREATE
        $activoFijoId = DB::table('activos_fijos')->insertGetId([
            ...$validated,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Activo fijo creado correctamente',
            'id' => $activoFijoId
        ], 201);
    }*/
}
