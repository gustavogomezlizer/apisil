<?php

namespace App\Http\Controllers\RH;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class RHDocumentosController extends Controller
{
    public function getDocumentosEmpleado(Request $request, $idempleado)
    {
        $docs = DB::table('rh_documentos')
            ->where('idempleado', $idempleado)
            ->where('vigente', 1)
            ->orderBy('tipo_documento')
            ->orderByDesc('version')
            ->get();

        // Agregar días restantes para los que tienen vencimiento
        $docs->transform(function ($d) {
            if ($d->fecha_vencimiento) {
                $d->dias_restantes = Carbon::today()->diffInDays(Carbon::parse($d->fecha_vencimiento), false);
            } else {
                $d->dias_restantes = null;
            }
            return $d;
        });

        return response()->json($docs);
    }

    public function getDocumentosFlotilla(Request $request)
    {
        $query = DB::table('rh_documentos as d')
            ->join('empleados as e', 'd.idempleado', '=', 'e.id')
            ->select('d.*', 'e.nombrecompleto', 'e.numeroempleado')
            ->where('d.vigente', 1)
            ->whereIn('e.estatus', config('rh.active_statuses'));

        $query->when($request->tipo_documento, fn($q) => $q->where('d.tipo_documento', $request->tipo_documento));
        $query->when($request->alerta,         fn($q) => $q->where('d.estatus_alerta', $request->alerta));
        $query->when($request->idempleado,     fn($q) => $q->where('d.idempleado', $request->idempleado));
        $query->when($request->search, function ($q) use ($request) {
            $s = '%' . $request->search . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('e.nombrecompleto', 'like', $s)
                    ->orWhere('d.tipo_documento', 'like', $s)
                    ->orWhere('d.numero_documento', 'like', $s);
            });
        });

        $query->orderBy('d.estatus_alerta')->orderBy('d.fecha_vencimiento');
        return $query->paginate($request->per_page ?? 20);
    }

    public function guardarDocumento(Request $request, $id = null)
    {
        $request->validate([
            'idempleado'    => 'required|integer',
            'tipo_documento'=> 'required|string|max:80',
        ]);

        $ruta = null;
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $ruta = $file->store('rh/documentos', 'public');
        }

        $datos = [
            'idempleado'          => $request->idempleado,
            'tipo_documento'      => $request->tipo_documento,
            'nombre_custom'       => $request->nombre_custom,
            'nombre_archivo'      => $request->nombre_archivo ?? ($ruta ? basename($ruta) : null),
            'numero_documento'    => $request->numero_documento,
            'fecha_emision'       => $request->fecha_emision,
            'fecha_vencimiento'   => $request->fecha_vencimiento,
            'tipo_mime'           => $request->hasFile('archivo') ? $request->file('archivo')->getMimeType() : null,
            'extension'           => $request->hasFile('archivo') ? $request->file('archivo')->extension() : null,
            'tamano'              => $request->hasFile('archivo') ? $request->file('archivo')->getSize() : null,
            'observaciones'       => $request->observaciones,
            'dias_alerta_amarillo'=> $request->dias_alerta_amarillo ?? 30,
            'estatus_alerta'      => $request->fecha_vencimiento ? 'verde' : 'sin_fecha',
            'vigente'             => 1,
            'idusuario'           => $request->idusuario,
            'updated_at'          => now(),
        ];

        if ($ruta) {
            $datos['ruta'] = $ruta;
        }

        if ($id) {
            // Si hay nuevo archivo, incrementar versión y marcar anterior como no vigente
            if ($ruta) {
                DB::table('rh_documentos')
                    ->where('idempleado', $request->idempleado)
                    ->where('tipo_documento', $request->tipo_documento)
                    ->where('vigente', 1)
                    ->update(['vigente' => 0, 'updated_at' => now()]);
                $datos['version'] = DB::table('rh_documentos')
                    ->where('idempleado', $request->idempleado)
                    ->where('tipo_documento', $request->tipo_documento)
                    ->max('version') + 1;
                $datos['created_at'] = now();
                $newId = DB::table('rh_documentos')->insertGetId($datos);
                return response()->json(['message' => 'Documento actualizado (nueva versión)', 'id' => $newId]);
            }
            DB::table('rh_documentos')->where('id', $id)->update($datos);
            return response()->json(['message' => 'Documento actualizado', 'id' => $id]);
        }

        $datos['version']    = 1;
        $datos['created_at'] = now();
        $newId = DB::table('rh_documentos')->insertGetId($datos);
        return response()->json(['message' => 'Documento guardado', 'id' => $newId], 201);
    }

    public function eliminarDocumento($id)
    {
        DB::table('rh_documentos')->where('id', $id)->update(['vigente' => 0, 'updated_at' => now()]);
        return response()->json(['message' => 'Documento eliminado']);
    }

    public function actualizarAlertas(): void
    {
        $hoy = Carbon::today();
        $docs = DB::table('rh_documentos')
            ->where('vigente', 1)
            ->whereNotNull('fecha_vencimiento')
            ->get();

        foreach ($docs as $doc) {
            $fechaVenc = Carbon::parse($doc->fecha_vencimiento);
            $dias      = $hoy->diffInDays($fechaVenc, false);
            $alerta    = match(true) {
                $dias <= 0  => 'rojo',
                $dias <= ($doc->dias_alerta_amarillo ?? 30) => 'amarillo',
                default     => 'verde',
            };
            DB::table('rh_documentos')->where('id', $doc->id)->update(['estatus_alerta' => $alerta, 'updated_at' => now()]);
        }
    }

    public function getResumenExpediente($idempleado): array
    {
        $tiposRequeridos = ['INE', 'CURP', 'RFC', 'NSS', 'acta_nacimiento', 'comprobante_domicilio', 'contrato', 'foto'];
        $existentes = DB::table('rh_documentos')
            ->where('idempleado', $idempleado)
            ->where('vigente', 1)
            ->pluck('tipo_documento')
            ->toArray();

        $completados = count(array_intersect($tiposRequeridos, $existentes));
        $pct = round(($completados / count($tiposRequeridos)) * 100);

        return [
            'tipos_requeridos' => $tiposRequeridos,
            'existentes'       => $existentes,
            'completados'      => $completados,
            'total_requeridos' => count($tiposRequeridos),
            'pct_completado'   => $pct,
        ];
    }
}
