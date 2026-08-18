<?php

namespace App\Http\Controllers\RH;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RHMovimientosController extends Controller
{
    public function getMovimientos(Request $request)
    {
        $query = DB::table('rh_movimientos as m')
            ->join('empleados as e', 'm.idempleado', '=', 'e.id')
            ->leftJoin('users as u', 'm.idusuario', '=', 'u.id')
            ->select(
                'm.*',
                'e.nombrecompleto', 'e.numeroempleado', 'e.puesto',
                'u.name as usuario'
            );

        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('m.folio', 'like', $s)
                    ->orWhere('e.nombrecompleto', 'like', $s)
                    ->orWhere('e.numeroempleado', 'like', $s);
            });
        });

        $query->when($request->tipo_movimiento, fn($q) => $q->where('m.tipo_movimiento', $request->tipo_movimiento));
        $query->when($request->idempleado,      fn($q) => $q->where('m.idempleado', $request->idempleado));
        $query->when($request->fechade,         fn($q) => $q->where('m.fecha_efectiva', '>=', $request->fechade));
        $query->when($request->fechaa,          fn($q) => $q->where('m.fecha_efectiva', '<=', $request->fechaa));
        $query->when($request->estatus,         fn($q) => $q->where('m.estatus', $request->estatus));

        $query->orderByDesc('m.fecha_efectiva')->orderByDesc('m.id');

        return $query->paginate($request->per_page ?? 20);
    }

    public function getMovimiento($id)
    {
        $mov = DB::table('rh_movimientos as m')
            ->join('empleados as e', 'm.idempleado', '=', 'e.id')
            ->leftJoin('users as u', 'm.idusuario', '=', 'u.id')
            ->select('m.*', 'e.nombrecompleto', 'e.numeroempleado', 'u.name as usuario')
            ->where('m.id', $id)
            ->first();

        if (!$mov) return response()->json(['message' => 'Movimiento no encontrado'], 404);
        return response()->json($mov);
    }

    public function getHistorialEmpleado(Request $request, $idempleado)
    {
        return DB::table('rh_movimientos as m')
            ->join('empleados as e', 'm.idempleado', '=', 'e.id')
            ->leftJoin('users as u', 'm.idusuario', '=', 'u.id')
            ->select('m.*', 'u.name as usuario')
            ->where('m.idempleado', $idempleado)
            ->when($request->tipo_movimiento, fn($q) => $q->where('m.tipo_movimiento', $request->tipo_movimiento))
            ->orderByDesc('m.fecha_efectiva')
            ->paginate($request->per_page ?? 20);
    }

    public function guardarMovimiento(Request $request, $id = null)
    {
        $request->validate([
            'idempleado'      => 'required|integer',
            'tipo_movimiento' => 'required|string',
            'fecha_efectiva'  => 'required|date',
        ]);

        // Tomar snapshot del empleado actual (para auditoría)
        $empleado = DB::table('empleados as e')
            ->leftJoin('rh_empleados_extra as ex', 'e.id', '=', 'ex.idempleado')
            ->select('e.*', 'ex.salario_mensual', 'ex.turno', 'ex.tipo_contrato')
            ->where('e.id', $request->idempleado)
            ->first();

        $datos = [
            'idempleado'           => $request->idempleado,
            'tipo_movimiento'      => $request->tipo_movimiento,
            'fecha_efectiva'       => $request->fecha_efectiva,
            // Snapshot "antes" (auto-capturado del empleado actual)
            'puesto_anterior'      => $empleado?->puesto,
            'idsucursal_anterior'  => $empleado?->idsucursal,
            'iddepartamento_anterior' => $empleado?->iddepartamento,
            'salario_anterior'     => $empleado?->salario_mensual,
            'turno_anterior'       => $empleado?->turno,
            // Valores nuevos (vienen del request)
            'puesto_nuevo'         => $request->puesto_nuevo,
            'idsucursal_nueva'     => $request->idsucursal_nueva,
            'iddepartamento_nuevo' => $request->iddepartamento_nuevo,
            'salario_nuevo'        => $request->salario_nuevo,
            'turno_nuevo'          => $request->turno_nuevo,
            // Detalles
            'motivo'               => $request->motivo,
            'descripcion'          => $request->descripcion,
            'tipo_baja'            => $request->tipo_baja,
            'fecha_inicio'         => $request->fecha_inicio,
            'fecha_fin'            => $request->fecha_fin,
            'dias_afectados'       => $request->dias_afectados,
            'archivo_evidencia'    => $request->archivo_evidencia,
            'estatus'              => $request->estatus ?? 'completado',
            'idusuario'            => $request->idusuario,
            'updated_at'           => now(),
        ];

        if ($id) {
            DB::table('rh_movimientos')->where('id', $id)->update($datos);
        } else {
            $anio   = date('Y');
            $count  = DB::table('rh_movimientos')->whereYear('created_at', $anio)->count();
            $datos['folio']      = 'MOV-' . $anio . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $datos['created_at'] = now();
            $id = DB::table('rh_movimientos')->insertGetId($datos);
        }

        // Aplicar cambios al empleado si el movimiento es de tipo cambio
        $this->aplicarMovimientoAEmpleado($request, $empleado);

        return response()->json(['message' => 'Movimiento registrado', 'id' => $id]);
    }

    protected function aplicarMovimientoAEmpleado(Request $request, $empleado): void
    {
        $tipo = $request->tipo_movimiento;

        // Actualizar empleados
        $cambiosEmpleado = [];
        if (in_array($tipo, ['baja', 'fin_contrato'])) {
            $cambiosEmpleado['estatus']    = 'Baja';
            $cambiosEmpleado['fechabaja']  = $request->fecha_efectiva;
        }
        if ($tipo === 'alta') {
            $cambiosEmpleado['estatus']       = 'Activo';
            $cambiosEmpleado['fechaingreso']  = $request->fecha_efectiva;
            $cambiosEmpleado['fechabaja']     = null;
        }
        if ($tipo === 'reingreso') {
            $cambiosEmpleado['estatus']       = 'Activo';
            $cambiosEmpleado['fechaingreso']  = $request->fecha_efectiva;
            $cambiosEmpleado['fechabaja']     = null;
        }
        if ($request->puesto_nuevo && in_array($tipo, ['cambio_puesto', 'promocion'])) {
            $cambiosEmpleado['puesto'] = $request->puesto_nuevo;
        }
        if ($request->idsucursal_nueva && in_array($tipo, ['cambio_sucursal', 'transferencia'])) {
            $cambiosEmpleado['idsucursal'] = $request->idsucursal_nueva;
        }
        if ($request->iddepartamento_nuevo && $tipo === 'cambio_departamento') {
            $cambiosEmpleado['iddepartamento'] = $request->iddepartamento_nuevo;
        }

        if (!empty($cambiosEmpleado)) {
            $cambiosEmpleado['updated_at'] = now();
            DB::table('empleados')->where('id', $request->idempleado)->update($cambiosEmpleado);
        }

        // Actualizar datos extendidos
        $cambiosExtra = [];
        if ($request->salario_nuevo && in_array($tipo, ['cambio_salario', 'promocion', 'alta', 'reingreso'])) {
            $cambiosExtra['salario_mensual'] = $request->salario_nuevo;
        }
        if ($request->turno_nuevo && $tipo === 'cambio_horario') {
            $cambiosExtra['turno'] = $request->turno_nuevo;
        }

        if (!empty($cambiosExtra)) {
            $cambiosExtra['updated_at'] = now();
            DB::table('rh_empleados_extra')
                ->where('idempleado', $request->idempleado)
                ->update($cambiosExtra);
        }
    }

    public function eliminarMovimiento($id)
    {
        DB::table('rh_movimientos')->where('id', $id)->delete();
        return response()->json(['message' => 'Movimiento eliminado']);
    }

    public function getTiposMovimiento()
    {
        return response()->json([
            ['value' => 'alta',                 'label' => '✅ Alta'],
            ['value' => 'baja',                 'label' => '❌ Baja'],
            ['value' => 'reingreso',            'label' => '🔄 Reingreso'],
            ['value' => 'cambio_puesto',        'label' => '👔 Cambio de Puesto'],
            ['value' => 'cambio_sucursal',      'label' => '🏢 Cambio de Sucursal'],
            ['value' => 'cambio_departamento',  'label' => '📂 Cambio de Departamento'],
            ['value' => 'cambio_salario',       'label' => '💰 Cambio Salarial'],
            ['value' => 'promocion',            'label' => '⬆️ Promoción'],
            ['value' => 'transferencia',        'label' => '🔀 Transferencia'],
            ['value' => 'incapacidad',          'label' => '🏥 Incapacidad'],
            ['value' => 'vacaciones',           'label' => '🏖️ Vacaciones'],
            ['value' => 'permiso',              'label' => '📋 Permiso'],
            ['value' => 'suspension',           'label' => '⛔ Suspensión'],
            ['value' => 'cambio_horario',       'label' => '🕐 Cambio de Horario'],
            ['value' => 'fin_contrato',         'label' => '📑 Fin de Contrato'],
            ['value' => 'renovacion_contrato',  'label' => '🔃 Renovación Contrato'],
            ['value' => 'otro',                 'label' => '📌 Otro'],
        ]);
    }
}
