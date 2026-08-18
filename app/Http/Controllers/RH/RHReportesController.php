<?php

namespace App\Http\Controllers\RH;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RHReportesController extends Controller
{
    public function getPlantillaLaboral(Request $request)
    {
        $query = DB::table('empleados as e')
            ->leftJoin('cat_departamentos as dep', 'e.iddepartamento', '=', 'dep.id')
            ->leftJoin('sucursales as s', 'e.idsucursal', '=', 's.id')
            ->leftJoin('rh_empleados_extra as ex', 'e.id', '=', 'ex.idempleado')
            ->select(
                'e.numeroempleado', 'e.nombrecompleto', 'e.puesto',
                'dep.nombre as departamento', 's.nombre as sucursal',
                'e.fechaingreso', 'e.correo', 'e.telefono',
                'ex.tipo_contrato', 'ex.fecha_fin_contrato', 'ex.turno',
                DB::raw("ROUND(DATEDIFF(CURDATE(), e.fechaingreso) / 365, 1) as antiguedad_anios"),
                'e.estatus as estatus_label'
            )
            ->whereIn('e.estatus', config('rh.active_statuses'));

        $this->aplicarFiltros($query, $request);
        $query->orderBy('s.nombre')->orderBy('dep.nombre')->orderBy('e.nombrecompleto');

        return $query->paginate($request->per_page ?? 30);
    }

    public function getHistorialMovimientos(Request $request)
    {
        $query = DB::table('rh_movimientos as m')
            ->join('empleados as e', 'm.idempleado', '=', 'e.id')
            ->leftJoin('sucursales as s', 'e.idsucursal', '=', 's.id')
            ->leftJoin('users as u', 'm.idusuario', '=', 'u.id')
            ->select(
                'm.folio', 'm.tipo_movimiento', 'm.fecha_efectiva',
                'e.numeroempleado', 'e.nombrecompleto', 'e.puesto',
                's.nombre as sucursal',
                'm.puesto_anterior', 'm.puesto_nuevo',
                'm.salario_anterior', 'm.salario_nuevo',
                'm.motivo', 'm.estatus',
                'u.name as usuario'
            );

        $query->when($request->tipo_movimiento, fn($q) => $q->where('m.tipo_movimiento', $request->tipo_movimiento));
        $query->when($request->fechade, fn($q) => $q->where('m.fecha_efectiva', '>=', $request->fechade));
        $query->when($request->fechaa,  fn($q) => $q->where('m.fecha_efectiva', '<=', $request->fechaa));
        $this->aplicarFiltros($query, $request);

        $query->orderByDesc('m.fecha_efectiva');
        return $query->paginate($request->per_page ?? 20);
    }

    public function getReporteAltasBajas(Request $request)
    {
        $anio = $request->anio ?? date('Y');
        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $altas = DB::table('rh_movimientos')
                ->where('tipo_movimiento', 'alta')
                ->whereYear('fecha_efectiva', $anio)
                ->whereMonth('fecha_efectiva', $m)
                ->count();
            $bajas = DB::table('rh_movimientos')
                ->where('tipo_movimiento', 'baja')
                ->whereYear('fecha_efectiva', $anio)
                ->whereMonth('fecha_efectiva', $m)
                ->count();
            $meses[] = ['mes_num' => $m, 'altas' => $altas, 'bajas' => $bajas, 'neto' => $altas - $bajas];
        }
        return response()->json($meses);
    }

    public function getReporteAntiguedad(Request $request)
    {
        $query = DB::table('empleados as e')
            ->leftJoin('sucursales as s', 'e.idsucursal', '=', 's.id')
            ->leftJoin('cat_departamentos as dep', 'e.iddepartamento', '=', 'dep.id')
            ->select(
                'e.numeroempleado', 'e.nombrecompleto', 'e.puesto', 'e.fechaingreso',
                's.nombre as sucursal', 'dep.nombre as departamento',
                DB::raw("DATEDIFF(CURDATE(), e.fechaingreso) as dias_antiguedad"),
                DB::raw("ROUND(DATEDIFF(CURDATE(), e.fechaingreso) / 365, 1) as anios_antiguedad"),
                DB::raw("CASE
                    WHEN DATEDIFF(CURDATE(), e.fechaingreso) < 365 THEN 'Menos de 1 año'
                    WHEN DATEDIFF(CURDATE(), e.fechaingreso) < 730 THEN '1-2 años'
                    WHEN DATEDIFF(CURDATE(), e.fechaingreso) < 1825 THEN '2-5 años'
                    WHEN DATEDIFF(CURDATE(), e.fechaingreso) < 3650 THEN '5-10 años'
                    ELSE 'Más de 10 años'
                END as rango_antiguedad")
            )
            ->whereIn('e.estatus', config('rh.active_statuses'))
            ->whereNotNull('e.fechaingreso');

        $this->aplicarFiltros($query, $request);
        $query->orderByDesc('dias_antiguedad');
        return $query->paginate($request->per_page ?? 20);
    }

    public function exportarCsvPlantilla(Request $request)
    {
        $data = $this->getPlantillaLaboral($request)->items();
        $csv = "No. Empleado,Nombre Completo,Puesto,Departamento,Sucursal,Fecha Ingreso,Antigüedad (años),Tipo Contrato,Turno,Correo,Teléfono\n";
        foreach ($data as $r) {
            $csv .= implode(',', [
                '"' . $r->numeroempleado  . '"',
                '"' . $r->nombrecompleto  . '"',
                '"' . ($r->puesto         ?? '') . '"',
                '"' . ($r->departamento   ?? '') . '"',
                '"' . ($r->sucursal       ?? '') . '"',
                '"' . ($r->fechaingreso   ?? '') . '"',
                $r->antiguedad_anios ?? '',
                '"' . ($r->tipo_contrato  ?? '') . '"',
                '"' . ($r->turno          ?? '') . '"',
                '"' . ($r->correo         ?? '') . '"',
                '"' . ($r->telefono       ?? '') . '"',
            ]) . "\n";
        }
        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_laboral_' . date('Ymd') . '.csv"',
        ]);
    }

    protected function aplicarFiltros($query, Request $request): void
    {
        $query->when($request->idsucursal,    fn($q) => $q->where('e.idsucursal', $request->idsucursal));
        $query->when($request->iddepartamento,fn($q) => $q->where('e.iddepartamento', $request->iddepartamento));
        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('e.nombrecompleto', 'like', $s)
                    ->orWhere('e.numeroempleado', 'like', $s)
                    ->orWhere('e.puesto', 'like', $s);
            });
        });
    }
}
