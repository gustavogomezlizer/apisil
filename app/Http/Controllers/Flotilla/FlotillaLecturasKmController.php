<?php

namespace App\Http\Controllers\Flotilla;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Flotilla\FlotillaService;

class FlotillaLecturasKmController extends Controller
{
    protected FlotillaService $flotillaService;

    public function __construct(FlotillaService $flotillaService)
    {
        $this->flotillaService = $flotillaService;
    }

    /**
     * Obtiene todas las unidades activas con su kilometraje actual para la captura masiva.
     */
    public function getCapturaMasiva(Request $request)
    {
        $query = DB::table('activos_fijos as af')
            ->leftJoin('activos_fijos_unidades as afu', 'af.id', '=', 'afu.idactivofijo')
            ->leftJoin('cat_tipos_activos_fijos as taf', 'af.idtipoactivo', '=', 'taf.id')
            ->leftJoin('activos_fijos_asignacion as asa', function ($join) {
                $join->on('af.id', '=', 'asa.idactivofijo')
                     ->where('asa.estadoasignacion', '=', 'Activa');
            })
            ->leftJoin('empleados as e', 'asa.idempleadoasignado', '=', 'e.id')
            ->select(
                'af.id',
                'af.descripcion as unidad',
                'af.marca',
                'af.modelo',
                'af.idtipoactivo',
                'taf.nombre as tipo_unidad',
                'afu.placas',
                'afu.numeroeconomico',
                DB::raw("CONCAT(COALESCE(e.nombres,''), ' ', COALESCE(e.apellidopaterno,'')) as operador"),
                'asa.idempleadoasignado as idempleado'
            )
            ->where('af.estatus', 'Activo')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->groupBy(
                'af.id', 'af.descripcion', 'af.marca', 'af.modelo', 'af.idtipoactivo',
                'taf.nombre', 'afu.placas', 'afu.numeroeconomico',
                'e.nombres', 'e.apellidopaterno', 'asa.idempleadoasignado'
            );

        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('af.descripcion', 'like', $s)
                    ->orWhere('afu.placas', 'like', $s)
                    ->orWhere('afu.numeroeconomico', 'like', $s);
            });
        });

        $query->when($request->idtipoactivo, fn($q) => $q->where('af.idtipoactivo', $request->idtipoactivo));
        $query->when($request->idempleado,   fn($q) => $q->where('asa.idempleadoasignado', $request->idempleado));

        $query->orderBy('taf.nombre')->orderBy('af.descripcion');

        $unidades = $query->get();

        // Agregar km actual y última lectura a cada unidad
        $hoy = date('Y-m-d');
        foreach ($unidades as $u) {
            $u->km_actual = $this->flotillaService->getKilometrajeActual($u->id);

            // Última lectura específica de captura semanal
            $ultima = DB::table('flotilla_lecturas_km')
                ->where('idactivofijo', $u->id)
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->first();

            $u->ultima_fecha         = $ultima?->fecha;
            $u->ultima_lectura       = $ultima?->kilometraje;
            $u->ultimo_origen        = $ultima?->origen;

            // Estado: ¿ya tiene lectura esta semana?
            $semana = DB::table('flotilla_lecturas_km')
                ->where('idactivofijo', $u->id)
                ->where('fecha', '>=', date('Y-m-d', strtotime('monday this week')))
                ->where('fecha', '<=', $hoy)
                ->exists();

            $u->capturado_semana = $semana;
            $u->nuevo_km         = null; // Campo editable en frontend
            $u->observaciones    = '';
        }

        return response()->json($unidades);
    }

    /**
     * Guarda múltiples lecturas de km en una sola transacción.
     */
    public function guardarLecturasKm(Request $request)
    {
        $request->validate([
            'lecturas'              => 'required|array|min:1',
            'lecturas.*.idactivofijo' => 'required|integer',
            'lecturas.*.kilometraje'  => 'required|numeric|min:0',
            'lecturas.*.fecha'        => 'required|date',
        ]);

        $errores  = [];
        $guardados = 0;

        DB::beginTransaction();
        try {
            foreach ($request->lecturas as $lectura) {
                $idActivoFijo = $lectura['idactivofijo'];
                $nuevoKm      = (float) $lectura['kilometraje'];
                $kmActual     = $this->flotillaService->getKilometrajeActual($idActivoFijo);

                // Validación: no permitir km menor al actual
                if ($nuevoKm < $kmActual) {
                    $errores[] = [
                        'idactivofijo' => $idActivoFijo,
                        'mensaje'      => "El km {$nuevoKm} es menor al actual registrado ({$kmActual}).",
                    ];
                    continue;
                }

                DB::table('flotilla_lecturas_km')->insert([
                    'idactivofijo'  => $idActivoFijo,
                    'fecha'         => $lectura['fecha'],
                    'kilometraje'   => $nuevoKm,
                    'origen'        => $lectura['origen']      ?? 'captura_semanal',
                    'idempleado'    => $lectura['idempleado']  ?? null,
                    'idusuario'     => $lectura['idusuario']   ?? null,
                    'observaciones' => $lectura['observaciones'] ?? null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                // Actualizar odómetro de la unidad
                DB::table('activos_fijos_unidades')
                    ->where('idactivofijo', $idActivoFijo)
                    ->update(['ultimoodometro' => $nuevoKm]);

                // Registrar en bitácora de flotilla
                $this->flotillaService->registrarBitacora([
                    'idactivofijo' => $idActivoFijo,
                    'tipo_evento'  => 'lectura_km',
                    'entidad_tipo' => 'flotilla_lecturas_km',
                    'entidad_id'   => null,
                    'descripcion'  => "Lectura de kilometraje: {$nuevoKm} km (origen: captura semanal)",
                    'km_evento'    => $nuevoKm,
                    'idusuario'    => $lectura['idusuario'] ?? null,
                    'fecha_evento' => $lectura['fecha'] . ' 00:00:00',
                ]);

                $guardados++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al guardar lecturas: ' . $e->getMessage()], 500);
        }

        $mensaje = "Se guardaron {$guardados} lecturas correctamente.";
        if (count($errores)) {
            $mensaje .= ' ' . count($errores) . ' lectura(s) con error.';
        }

        return response()->json([
            'message'  => $mensaje,
            'guardados'=> $guardados,
            'errores'  => $errores,
        ]);
    }

    /**
     * Historial de lecturas de una unidad.
     */
    public function getHistorialUnidad(Request $request, $idActivoFijo)
    {
        $query = DB::table('flotilla_lecturas_km as lk')
            ->leftJoin('empleados as e', 'lk.idempleado', '=', 'e.id')
            ->leftJoin('users as u', 'lk.idusuario', '=', 'u.id')
            ->select(
                'lk.*',
                DB::raw("CONCAT(COALESCE(e.nombres,''), ' ', COALESCE(e.apellidopaterno,'')) as empleado"),
                'u.name as usuario'
            )
            ->where('lk.idactivofijo', $idActivoFijo);

        $query->when($request->origen, fn($q) => $q->where('lk.origen', $request->origen));
        $query->when($request->fechade, fn($q) => $q->where('lk.fecha', '>=', $request->fechade));
        $query->when($request->fechaa,  fn($q) => $q->where('lk.fecha', '<=', $request->fechaa));

        $query->orderBy('lk.fecha', 'desc')->orderBy('lk.id', 'desc');

        return $query->paginate($request->per_page ?? 20);
    }

    /**
     * Dashboard / indicadores del módulo.
     */
    public function getDashboard()
    {
        $hoy        = date('Y-m-d');
        $inicioSem  = date('Y-m-d', strtotime('monday this week'));
        $hace7dias  = date('Y-m-d', strtotime('-7 days'));

        $totalUnidades = DB::table('activos_fijos')
            ->where('estatus', 'Activo')
            ->whereIn('idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->count();

        $capturadas = DB::table('flotilla_lecturas_km')
            ->selectRaw('COUNT(DISTINCT idactivofijo) as total')
            ->where('fecha', '>=', $inicioSem)
            ->where('fecha', '<=', $hoy)
            ->value('total');

        $pendientes = $totalUnidades - ($capturadas ?? 0);

        $ultimaFecha = DB::table('flotilla_lecturas_km')
            ->where('origen', 'captura_semanal')
            ->max('fecha');

        $sinLectura7dias = DB::table('activos_fijos as af')
            ->where('af.estatus', 'Activo')
            ->whereIn('af.idtipoactivo', ACTIVO_FIJO_TIPO_UNIDAD_IDS())
            ->whereNotExists(function ($q) use ($hace7dias, $hoy) {
                $q->select(DB::raw(1))
                  ->from('flotilla_lecturas_km')
                  ->whereColumn('flotilla_lecturas_km.idactivofijo', 'af.id')
                  ->where('fecha', '>=', $hace7dias)
                  ->where('fecha', '<=', $hoy);
            })
            ->count();

        return response()->json([
            'total_unidades'    => $totalUnidades,
            'capturadas_semana' => $capturadas ?? 0,
            'pendientes'        => max(0, $pendientes),
            'ultima_fecha'      => $ultimaFecha,
            'sin_lectura_7dias' => $sinLectura7dias,
        ]);
    }
}
