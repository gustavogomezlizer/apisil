<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class FlotillaDashboardController extends Controller
{
    public function getDashboard(Request $request)
    {
        $mes  = $request->mes  ?? date('m');
        $anio = $request->anio ?? date('Y');

        return response()->json([
            'alertas'            => $this->getResumenAlertas(),
            'costos_mes'         => $this->getCostosMes($mes, $anio),
            'costos_anio'        => $this->getCostosAnio($anio),
            'top_unidades'       => $this->getTopUnidadesCostosas($anio),
            'top_proveedores'    => $this->getTopProveedores($anio),
            'unidades_detenidas' => $this->getUnidadesDetenidas(),
            'grafica_mensual'    => $this->getGraficaMensual($anio),
            'costos_por_tipo'    => $this->getCostosPorTipoMantenimiento($mes, $anio),
            'pendientes_autorizacion' => $this->getPendientesAutorizacion(),
        ]);
    }

    protected function getResumenAlertas(): array
    {
        $row = DB::table('flotilla_alertas')
            ->where('leida', 0)
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN nivel = "rojo"    THEN 1 ELSE 0 END) as rojo'),
                DB::raw('SUM(CASE WHEN nivel = "amarillo" THEN 1 ELSE 0 END) as amarillo'),
                DB::raw('SUM(CASE WHEN nivel = "verde"   THEN 1 ELSE 0 END) as verde')
            )
            ->first();

        $serviciosVencidos = DB::table('flotilla_unidad_mantenimiento')
            ->where('activo', 1)
            ->where('estatus_alerta', 'rojo')
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->count();

        $proximosServicios = DB::table('flotilla_unidad_mantenimiento')
            ->where('activo', 1)
            ->where('estatus_alerta', 'amarillo')
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->count();

        return [
            'total_no_leidas'    => $row->total,
            'nivel_rojo'         => $row->rojo,
            'nivel_amarillo'     => $row->amarillo,
            'nivel_verde'        => $row->verde,
            'servicios_vencidos' => $serviciosVencidos,
            'proximos_servicios' => $proximosServicios,
        ];
    }

    protected function getCostosMes(string $mes, string $anio): array
    {
        $preventivo = DB::table('flotilla_mantenimiento_preventivo')
            ->whereYear('fecha_servicio', $anio)
            ->whereMonth('fecha_servicio', $mes)
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->sum('costo_total') ?? 0;

        $correctivo = DB::table('flotilla_mantenimiento_correctivo')
            ->whereYear('fecha_ingreso', $anio)
            ->whereMonth('fecha_ingreso', $mes)
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->sum('costo_total') ?? 0;

        $ordenes = DB::table('ordenes_servicio')
            ->where('autorizacion_estatus', '!=', 'no-autorizado')
            ->whereYear('fechaingreso', $anio)
            ->whereMonth('fechaingreso', $mes)
            ->whereIn('idunidad', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->sum('totalimporte') ?? 0;

        return [
            'preventivo' => round($preventivo, 2),
            'correctivo' => round($correctivo, 2),
            'ordenes'    => round($ordenes, 2),
            'total'      => round($preventivo + $correctivo + $ordenes, 2),
        ];
    }

    protected function getCostosAnio(string $anio): array
    {
        $preventivo = DB::table('flotilla_mantenimiento_preventivo')
            ->whereYear('fecha_servicio', $anio)
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->sum('costo_total') ?? 0;

        $correctivo = DB::table('flotilla_mantenimiento_correctivo')
            ->whereYear('fecha_ingreso', $anio)
            ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->sum('costo_total') ?? 0;

        $ordenes = DB::table('ordenes_servicio')
            ->where('autorizacion_estatus', '!=', 'no-autorizado')
            ->whereYear('fechaingreso', $anio)
            ->whereIn('idunidad', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->sum('totalimporte') ?? 0;

        return [
            'preventivo' => round($preventivo, 2),
            'correctivo' => round($correctivo, 2),
            'ordenes'    => round($ordenes, 2),
            'total'      => round($preventivo + $correctivo + $ordenes, 2),
        ];
    }

    protected function getTopUnidadesCostosas(string $anio): array
    {
        $preventivo = DB::table('flotilla_mantenimiento_preventivo as mp')
            ->join('activos_fijos as af', 'mp.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->whereYear('mp.fecha_servicio', $anio)
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select('af.id', 'af.descripcion', 'afu.placas', DB::raw('SUM(mp.costo_total) as total'))
            ->groupBy('af.id', 'af.descripcion', 'afu.placas');

        $correctivo = DB::table('flotilla_mantenimiento_correctivo as mc')
            ->join('activos_fijos as af', 'mc.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->whereYear('mc.fecha_ingreso', $anio)
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select('af.id', 'af.descripcion', 'afu.placas', DB::raw('SUM(mc.costo_total) as total'))
            ->groupBy('af.id', 'af.descripcion', 'afu.placas');

        $ordenes = DB::table('ordenes_servicio as os')
            ->join('activos_fijos as af', 'os.idunidad', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->where('os.autorizacion_estatus', '!=', 'no-autorizado')
            ->whereYear('os.fechaingreso', $anio)
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select('af.id', 'af.descripcion', 'afu.placas', DB::raw('SUM(os.totalimporte) as total'))
            ->groupBy('af.id', 'af.descripcion', 'afu.placas');

        // Unir preventivo + correctivo + órdenes por unidad
        $combinado = DB::table(
            DB::raw("({$preventivo->toSql()} UNION ALL {$correctivo->toSql()} UNION ALL {$ordenes->toSql()}) as sub")
        )
        ->mergeBindings($preventivo)
        ->mergeBindings($correctivo)
        ->mergeBindings($ordenes)
        ->select('id', 'descripcion', 'placas', DB::raw('SUM(total) as costo_total'))
        ->groupBy('id', 'descripcion', 'placas')
        ->orderByDesc('costo_total')
        ->limit(10)
        ->get();

        return $combinado->toArray();
    }

    protected function getTopProveedores(string $anio): array
    {
        $preventivo = DB::table('flotilla_mantenimiento_preventivo as mp')
            ->join('talleres as t', 'mp.idtaller', '=', 't.id')
            ->whereYear('mp.fecha_servicio', $anio)
            ->whereNotNull('mp.idtaller')
            ->select('t.id', 't.razonsocial', DB::raw('SUM(mp.costo_total) as total'))
            ->groupBy('t.id', 't.razonsocial');

        $correctivo = DB::table('flotilla_mantenimiento_correctivo as mc')
            ->join('talleres as t', 'mc.idtaller', '=', 't.id')
            ->whereYear('mc.fecha_ingreso', $anio)
            ->whereNotNull('mc.idtaller')
            ->select('t.id', 't.razonsocial', DB::raw('SUM(mc.costo_total) as total'))
            ->groupBy('t.id', 't.razonsocial');

        $combinado = DB::table(
            DB::raw("({$preventivo->toSql()} UNION ALL {$correctivo->toSql()}) as sub")
        )
        ->mergeBindings($preventivo)
        ->mergeBindings($correctivo)
        ->select('id', 'razonsocial', DB::raw('SUM(total) as costo_total'))
        ->groupBy('id', 'razonsocial')
        ->orderByDesc('costo_total')
        ->limit(10)
        ->get();

        return $combinado->toArray();
    }

    protected function getUnidadesDetenidas(): array
    {
        return DB::table('flotilla_mantenimiento_correctivo as mc')
            ->join('activos_fijos as af', 'mc.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->whereIn('mc.estatus', ['pendiente', 'en_proceso'])
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select(
                'af.id', 'af.descripcion as unidad',
                'afu.placas',
                'mc.id as idcorrectivo',
                'mc.folio', 'mc.fecha_ingreso',
                'mc.diagnostico', 'mc.estatus'
            )
            ->orderBy('mc.fecha_ingreso')
            ->get()
            ->toArray();
    }

    protected function getGraficaMensual(string $anio): array
    {
        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $prev = DB::table('flotilla_mantenimiento_preventivo')
                ->whereYear('fecha_servicio', $anio)
                ->whereMonth('fecha_servicio', $m)
                ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
                ->sum('costo_total') ?? 0;

            $corr = DB::table('flotilla_mantenimiento_correctivo')
                ->whereYear('fecha_ingreso', $anio)
                ->whereMonth('fecha_ingreso', $m)
                ->whereIn('idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
                ->sum('costo_total') ?? 0;

            $ord = DB::table('ordenes_servicio')
                ->where('autorizacion_estatus', '!=', 'no-autorizado')
                ->whereYear('fechaingreso', $anio)
                ->whereMonth('fechaingreso', $m)
                ->whereIn('idunidad', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
                ->sum('totalimporte') ?? 0;

            $meses[] = [
                'mes'        => Carbon::create($anio, $m, 1)->locale('es')->monthName,
                'mes_num'    => $m,
                'preventivo' => round($prev, 2),
                'correctivo' => round($corr, 2),
                'ordenes'    => round($ord, 2),
                'total'      => round($prev + $corr + $ord, 2),
            ];
        }
        return $meses;
    }

    protected function getCostosPorTipoMantenimiento(string $mes, string $anio): array
    {
        $serv = DB::table('flotilla_mantenimiento_preventivo as mp')
            ->join('flotilla_unidad_mantenimiento as um', 'mp.idunidad_mantenimiento', '=', 'um.id')
            ->whereYear('mp.fecha_servicio', $anio)
            ->whereMonth('mp.fecha_servicio', $mes)
            ->whereIn('um.idactivofijo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select('um.nombre_servicio', DB::raw('SUM(mp.costo_total) as total'), DB::raw('COUNT(*) as cantidad'))
            ->groupBy('um.nombre_servicio')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return $serv->toArray();
    }

    /**
     * Registros con autorización pendiente: mantenimientos preventivo/correctivo
     * y órdenes de servicio. Se unifican para la tarjeta del dashboard.
     */
    protected function getPendientesAutorizacion(): array
    {
        $prev = DB::table('flotilla_mantenimiento_preventivo as mp')
            ->leftJoin('activos_fijos as af', 'mp.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->where('mp.estatus_autorizacion', 'pendiente')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select(
                'mp.id',
                'mp.folio',
                'af.descripcion as unidad',
                'afu.placas',
                DB::raw('COALESCE(mp.fecha_ingreso, mp.fecha_servicio) as fecha'),
                'mp.costo_total',
                'mp.estatus_autorizacion'
            )
            ->orderByDesc('mp.fecha_servicio')
            ->limit(30)
            ->get();

        $corr = DB::table('flotilla_mantenimiento_correctivo as mc')
            ->leftJoin('activos_fijos as af', 'mc.idactivofijo', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->where('mc.estatus_autorizacion', 'pendiente')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select(
                'mc.id',
                'mc.folio',
                'af.descripcion as unidad',
                'afu.placas',
                'mc.fecha_ingreso as fecha',
                'mc.costo_total',
                'mc.estatus_autorizacion'
            )
            ->orderByDesc('mc.fecha_ingreso')
            ->limit(30)
            ->get();

        $ord = DB::table('ordenes_servicio as os')
            ->leftJoin('activos_fijos as af', 'os.idunidad', '=', 'af.id')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->where('os.autorizacion_estatus', 'pendiente')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->select(
                'os.id',
                'os.ordenservicio as folio',
                'af.descripcion as unidad',
                'afu.placas',
                'os.fechaingreso as fecha',
                'os.totalimporte as costo_total',
                'os.autorizacion_estatus'
            )
            ->orderByDesc('os.fechaingreso')
            ->limit(30)
            ->get();

        $items = [];

        foreach ($prev as $p) {
            $items[] = [
                'tipo'   => 'preventivo',
                'id'     => $p->id,
                'folio'  => $p->folio,
                'unidad' => $p->unidad ?? '—',
                'placas' => $p->placas ?? '',
                'fecha'  => $p->fecha,
                'monto'  => round((float) $p->costo_total, 2),
                'estatus'=> $p->estatus_autorizacion,
            ];
        }

        foreach ($corr as $c) {
            $items[] = [
                'tipo'   => 'correctivo',
                'id'     => $c->id,
                'folio'  => $c->folio,
                'unidad' => $c->unidad ?? '—',
                'placas' => $c->placas ?? '',
                'fecha'  => $c->fecha,
                'monto'  => round((float) $c->costo_total, 2),
                'estatus'=> $c->estatus_autorizacion,
            ];
        }

        foreach ($ord as $o) {
            $items[] = [
                'tipo'   => 'orden',
                'id'     => $o->id,
                'folio'  => $o->folio ?? "OS-{$o->id}",
                'unidad' => $o->unidad ?? '—',
                'placas' => $o->placas ?? '',
                'fecha'  => $o->fecha,
                'monto'  => round((float) $o->costo_total, 2),
                'estatus'=> $o->autorizacion_estatus,
            ];
        }

        // Ordenar de más reciente a más antiguo
        usort($items, function ($a, $b) {
            return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
        });

        return ['total' => count($items), 'items' => array_slice($items, 0, 20)];
    }

    /**
     * POST /flotilla/dashboard/autorizar
     * Autoriza/rechaza un pendiente (preventivo, correctivo u orden de servicio).
     */
    public function autorizarPendiente(Request $request)
    {
        $user = auth()->user();
        if (!$user) return response()->json(['message' => 'No autenticado'], 401);

        $request->validate([
            'tipo'      => 'required|in:preventivo,correctivo,orden',
            'id'        => 'required|integer',
            'accion'    => 'required|in:autorizar,rechazar',
            'comentario'=> 'nullable|string|max:500',
        ]);

        $tipo = $request->tipo;

        $permissionPath = $tipo === 'orden'
            ? '/operaciones/ordenes-servicio:authorize'
            : '/flotilla/mantenimientos:autorizar';

        $tienePermiso = DB::table('user_permissions')
            ->where('user_id', $user->id)
            ->where('permission_path', $permissionPath)
            ->exists();

        if (!$tienePermiso) {
            return response()->json(['message' => 'No tienes permiso para autorizar este registro.'], 403);
        }

        if ($tipo === 'orden') {
            $orden = DB::table('ordenes_servicio')->where('id', $request->id)->first();
            if (!$orden) return response()->json(['message' => 'Orden de servicio no encontrada'], 404);
            if ($orden->autorizacion_estatus === 'autorizado') {
                return response()->json(['message' => 'La orden ya fue autorizada.'], 422);
            }

            DB::table('ordenes_servicio')->where('id', $request->id)->update([
                'autorizacion_estatus'    => $request->accion === 'autorizar' ? 'autorizado' : 'no-autorizado',
                'autorizacion_comentario' => $request->comentario,
                'updated_at'              => now(),
            ]);

            return response()->json([
                'message' => $request->accion === 'autorizar' ? 'Orden de servicio autorizada.' : 'Orden de servicio no autorizada.',
            ]);
        }

        $tabla = $tipo === 'preventivo'
            ? 'flotilla_mantenimiento_preventivo'
            : 'flotilla_mantenimiento_correctivo';

        $mant = DB::table($tabla)->where('id', $request->id)->first();
        if (!$mant) return response()->json(['message' => 'Mantenimiento no encontrado'], 404);

        if (($mant->estatus_autorizacion ?? 'pendiente') === 'autorizado') {
            return response()->json(['message' => 'Este mantenimiento ya fue autorizado.'], 422);
        }

        if ($request->accion === 'rechazar' && !trim((string) $request->comentario)) {
            return response()->json(['message' => 'Escribe el motivo del rechazo.'], 422);
        }

        DB::table($tabla)->where('id', $request->id)->update([
            'estatus_autorizacion' => $request->accion === 'autorizar' ? 'autorizado' : 'rechazado',
            'idusuario_autoriza'   => $user->id,
            'fecha_autorizacion'   => now(),
            'motivo_rechazo'       => $request->accion === 'rechazar' ? $request->comentario : null,
            'updated_at'           => now(),
        ]);

        return response()->json([
            'message' => $request->accion === 'autorizar' ? 'Mantenimiento autorizado.' : 'Mantenimiento rechazado.',
        ]);
    }
}
