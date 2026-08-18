<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ActivosFijosExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DB::table('activos_fijos as cat')
        ->leftJoin('activos_fijos_unidades as afu', 'cat.id', '=', 'afu.idactivofijo')
        ->leftJoin('activos_fijos_asignacion as afa', 'cat.id', '=', 'afa.idactivofijo');

        // SELECT
        $query->select(
            'cat.clave',
            DB::raw("fnGetDatoNombreCatalogoById('cat_tipos_activos_fijos', cat.idtipoactivo) AS tipoactivo"),
            'cat.marca', 'cat.descripcion', 'cat.serie', 'cat.anio AS modelo', 'cat.fechaadquisicion', 'cat.fechareemplazo', 'cat.precio',
            DB::raw("fnGetDatoNombreCatalogoById('sucursales', cat.idsucursal) AS sucursal"),
            'cat.pin', 'cat.estatus',
            'afu.numeroeconomico', 'afu.propietario', 'afu.placas', 'afu.accesorios', 'afu.entidadfederativa',
            DB::raw("fnGetDatoNombreCatalogoById('aseguradoras', afu.idaseguradora) AS aseguradora"),
            'afu.numeropoliza', 'afu.inciso', 'afu.cobertura', 'afu.fechavencimientopoliza', 'afu.costopoliza',
            'afu.combustibleasignado',
            'afu.clunes', 'afu.cmartes', 'afu.cmiercoles', 'afu.cjueves', 'afu.cviernes', 'afu.csabado', 'afu.cdomingo',
            //DB::raw("fnGetDatoNombreCatalogoById('empleados', afa.idempleadoasignado) AS empleadoasignado")
        );

        // FILTROS DINÁMICOS
        $query->when($this->filters['idtipoactivo'] ?? null, fn($q, $idtipoactivo) =>
            $q->where('cat.idtipoactivo', $idtipoactivo)
        );

        $query->when($this->filters['idsucursal'] ?? null, fn($q, $idsucursal) =>
            $q->where('cat.idsucursal', $idsucursal)
        );

        $query->when($this->filters['iddepartamento'] ?? null, fn($q, $iddepartamento) =>
            $q->where('afa.iddepartamento', $iddepartamento)
        );

        $query->when($this->filters['estatus'] ?? null, fn($q, $estatus) =>
            $q->where('cat.estatus', $estatus)
        );


        // SEARCH
        $query->when($this->filters['search'] ?? null, function ($q, $search) {
            $search = '%' . $search . '%';

            $q->where(function ($sub) use ($search) {
                $sub->where('cat.descripcion', 'like', $search)
                    ->orWhere('cat.clave', 'like', $search)
                    ->orWhere('afu.numeroeconomico', 'like', $search)
                    ->orWhereRaw("fnGetDatoNombreCatalogoById('empleados', afa.idempleadoasignado) LIKE ?", [$search]);
            });
        });


        // ORDEN
        $query->orderBy('cat.clave')
            ->orderBy('cat.descripcion');

        return $query->get([
            'clave',
            'tipoactivo',
            'marca',
            'descripcion',
            'serie',
            'modelo',
            'fechaaquisicion',
            'fechareemplazo',
            'precio',
            'sucursal',
            'pin',
            'estatus',
            'numeroeconomico', 
            'propietario', 
            'placas', 
            'accesorios', 
            'entidadfederativa',
            'aseguradora',
            'numeropoliza', 
            'inciso', 
            'cobertura', 
            'fechavencimientopoliza', 
            'costopoliza',
            'combustibleasignado',
            'clunes', 
            'cmartes', 
            'cmiercoles', 
            'cjueves', 
            'cviernes', 
            'csabado', 
            'cdomingo'
        ]);
    }

    public function headings(): array
    {
        return [
            'CLAVE',
            'TIPO ACTIVO',
            'MARCA',
            'DESCRIPCION',
            'SERIE',
            'MODELO',
            'FECHA ADQUISICION',
            'FECHA REEMPLAZO',
            'PRECIO',
            'SUCURSAL',
            'PIN',
            'ESTATUS',
            'NUMERO ECONOMICO',
            'PROPIETARIO',
            'PLACAS',
            'ACCESORIOS',
            'ENTIDAD FEDERATIVA',
            'ASEGURADORA',
            'NUMERO POLIZA',
            'INCISO',
            'COBERTURA',
            'FECHA VENCIMIENTO POLIZA',
            'COSTO POLIZA',
            'COMBUSTIBLE ASIGNADO',
            'LUNES',
            'MARTES',
            'MIERCOLES',
            'JUEVES',
            'VIERNES',
            'SABADO',
            'DOMINGO'
        ];
    }
}