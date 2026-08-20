<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AnalisisMantenimientoExport implements FromCollection, WithHeadings
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = DB::table('ordenes_servicio as os')
            ->leftJoin('talleres as t', 'os.idtaller', '=', 't.id')
            ->leftJoin('activos_fijos as af', 'os.idunidad', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('ordenes_servicio_detalle as d', 'os.id', '=', 'd.idorden')
            ->leftJoin('cat_tipos_servicio as s', 'd.idservicio', '=', 's.id')
            ->leftJoin('sucursales as suc', 'os.idsucursal', '=', 'suc.id')
            ->select(
                'os.fechaingreso',
                'os.fechaentrega',
                'os.ordenservicio',
                'os.usuario',
                DB::raw('IFNULL(suc.nombre, os.sucursal) as sucursal'),
                'afu.numeroeconomico',
                'os.descripcionunidad',
                'os.kilometrajeunidad as kilometrounidad',
                't.razonsocial as proveedorservicio',
                'os.estatusorden as estadoordenservicio',
                'd.numeromovimiento',
                's.nombre as servicio',
                'd.importe',
                'd.observaciones'
            );

        $query->when($this->filters['fechade'] ?? null, fn($q) => $q->where('os.fechaingreso', '>=', $this->filters['fechade']));
        $query->when($this->filters['fechaa'] ?? null, fn($q) => $q->where('os.fechaingreso', '<=', $this->filters['fechaa']));
        $query->when($this->filters['idsucursal'] ?? null, fn($q) => $q->where('os.idsucursal', $this->filters['idsucursal']));
        $query->when($this->filters['idunidad'] ?? null, fn($q) => $q->where('os.idunidad', $this->filters['idunidad']));
        $query->when($this->filters['idservicio'] ?? null, fn($q) => $q->where('d.idservicio', $this->filters['idservicio']));
        $query->when($this->filters['idtaller'] ?? null, fn($q) => $q->where('os.idtaller', $this->filters['idtaller']));
        $query->when($this->filters['estatus'] ?? null, fn($q) => $q->where('os.estatusorden', $this->filters['estatus']));

        $query->orderBy('os.fechaingreso', 'desc')->orderBy('os.id', 'desc');

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Fecha Ingreso',
            'Fecha Entrega',
            'Orden de Servicio',
            'Usuario',
            'Sucursal',
            'No. Económico',
            'Descripción Unidad',
            'Kilometraje',
            'Proveedor',
            'Estado',
            'No. Movimiento',
            'Servicio',
            'Importe',
            'Observaciones',
        ];
    }
}
