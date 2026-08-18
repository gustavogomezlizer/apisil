<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
    h1   { font-size: 14px; color: #1a56db; margin-bottom: 4px; }
    .sub { font-size: 9px; color: #666; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th    { background: #1a56db; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; }
    td    { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) td { background: #f9fafb; }
    .preventivo { color: #065f46; font-weight: bold; }
    .correctivo { color: #92400e; font-weight: bold; }
    .totals { margin-top: 10px; text-align: right; font-weight: bold; font-size: 11px; }
    .badge-prev { background:#d1fae5; color:#065f46; padding:2px 6px; border-radius:4px; }
    .badge-corr { background:#fef3c7; color:#92400e; padding:2px 6px; border-radius:4px; }
</style>
</head>
<body>
<h1>Reporte de Mantenimiento — Gestión Integral de Flotilla</h1>
<div class="sub">
    Generado: {{ date('d/m/Y H:i') }}
    @if(!empty($filtros['fechade'])) | Desde: {{ $filtros['fechade'] }} @endif
    @if(!empty($filtros['fechaa']))  | Hasta: {{ $filtros['fechaa'] }}  @endif
</div>

<table>
    <thead>
        <tr>
            <th>Folio</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Unidad</th>
            <th>Placas</th>
            <th>No. Econ.</th>
            <th>Tipo Unidad</th>
            <th>Servicio / Diagnóstico</th>
            <th>Taller</th>
            <th>Mano Obra</th>
            <th>Refacciones</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos as $row)
        <tr>
            <td>{{ $row->folio ?? '—' }}</td>
            <td>{{ $row->fecha_servicio ?? '—' }}</td>
            <td>
                @if($row->tipo_mantenimiento === 'Preventivo')
                    <span class="badge-prev">Preventivo</span>
                @else
                    <span class="badge-corr">Correctivo</span>
                @endif
            </td>
            <td>{{ $row->unidad ?? '—' }}</td>
            <td>{{ $row->placas ?? '—' }}</td>
            <td>{{ $row->numeroeconomico ?? '—' }}</td>
            <td>{{ $row->tipo_unidad ?? '—' }}</td>
            <td>{{ $row->servicio ?? '—' }}</td>
            <td>{{ $row->taller ?? '—' }}</td>
            <td style="text-align:right">${{ number_format($row->costo_mano_obra ?? 0, 2) }}</td>
            <td style="text-align:right">${{ number_format($row->costo_refacciones ?? 0, 2) }}</td>
            <td style="text-align:right"><strong>${{ number_format($row->costo_total ?? 0, 2) }}</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    TOTAL GENERAL: ${{ number_format($total, 2) }}
    &nbsp;&nbsp;|&nbsp;&nbsp;
    Registros: {{ count($datos) }}
</div>
</body>
</html>
