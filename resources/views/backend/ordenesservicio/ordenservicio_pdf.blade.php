<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            .page-break {
                page-break-after: always;
            }
        </style>
    </head>

    <body>
        <table border="0" width="100%">
            <tr>
                <td width="0%">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('/assets/img/logo/logo.jpg'))) }}" alt="Logo" height="40" width="100" />
                </td>
                <td width="100%">
                    <div align="center" style="margin-right:100px">
                        <h4>Comercializadora de Granos Lizer SA de CV</h4>
                        <p style="margin-top:-10px">Teléfono: (669) 980 6079</p>
                        <p style="margin-top:-10px">Mazatlán Sinaloa</p>
                    </div>
                </td>
            </tr>
        </table>

        <h3 align="center">Orden de Servicio</h3>

        <table width="100%">
            <tr>
                <td><b>Folio:</b></td>
                <td>{{ $orden->ordenservicio ?? '-' }}</td>
                <td align="right"><b>Fecha Ingreso:</b></td>
                <td>{{ $orden->fechaingreso ?? '-' }}</td>
            </tr>
            <tr>
                <td><b>Fecha Entrega:</b></td>
                <td>{{ $orden->fechaentrega ?? '-' }}</td>
                <td align="right"><b>Estatus:</b></td>
                <td>{{ $orden->estatusorden ?? '-' }}</td>
            </tr>
        </table>

        <br/>
        <div align="center" style="border: 0.1px solid black;background:#dedede;">Datos del Proveedor / Taller</div>
        <div style="border: 0.1px solid black;">
            <table border="0" width="100%">
                <tr>
                    <td><b>Nombre:</b></td>
                    <td>{{ $orden->taller ?? '-' }}</td>
                </tr>
                <tr>
                    <td><b>Domicilio:</b></td>
                    <td>{{ $orden->taller_domicilio ?? '-' }}</td>
                </tr>
                <tr>
                    <td><b>Contacto:</b></td>
                    <td>{{ $orden->taller_contacto ?? '-' }}</td>
                </tr>
                <tr>
                    <td><b>Teléfono:</b></td>
                    <td>{{ $orden->taller_telefono ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <br/>
        <div align="center" style="border: 0.1px solid black;background:#dedede;">Datos de la Unidad</div>
        <div style="border: 0.1px solid black;">
            <table border="0" width="100%">
                <tr>
                    <td><b>Unidad:</b></td>
                    <td>{{ $orden->numeroeconomico ?? '-' }} - {{ $orden->unidad ?? '-' }}</td>
                </tr>
                <tr>
                    <td><b>Marca:</b></td>
                    <td>{{ $orden->unidad_marca ?? '-' }}</td>
                </tr>
                <tr>
                    <td><b>Serie:</b></td>
                    <td>{{ $orden->unidad_serie ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <br/>
        <div align="center" style="border: 0.1px solid black;background:#dedede;">Datos Solicitante</div>
        <div style="border: 0.1px solid black;">
            <table border="0" width="100%">
                <tr>
                    <td><b>Solicita:</b></td>
                    <td>{{ $orden->usuario ?? '-' }}</td>
                </tr>
                <tr>
                    <td><b>Sucursal:</b></td>
                    <td>{{ $orden->sucursal ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <br/>
        <div align="center" style="border: 0.1px solid black;background:#dedede;">Detalle de Servicios</div>
        <table width="100%" border="1" style="border: 1px solid black;border-collapse: collapse;">
            <thead style="background:#dedede;">
                <tr>
                    <th align="left">Servicio</th>
                    <th align="right">Importe</th>
                    <th align="left">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detalles as $detalle)
                <tr>
                    <td align="left">{{ $detalle->servicio ?? '-' }}</td>
                    <td align="right">${{ number_format($detalle->importe ?? 0, 2) }}</td>
                    <td align="left">{{ $detalle->observaciones ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" align="center">Sin servicios registrados</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td align="right" colspan="1"><b>Total</b></td>
                    <td align="right"><b>${{ number_format($orden->totalimporte ?? 0, 2) }}</b></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <br/>
        <table width="100%">
            <tr>
                <td><b>Autorización:</b></td>
                <td>{{ $orden->autorizacion_estatus ?? '-' }}</td>
            </tr>
            <tr>
                <td><b>Comentario autorización:</b></td>
                <td>{{ $orden->autorizacion_comentario ?? '-' }}</td>
            </tr>
        </table>

        <br/><br/>
        <table width="100%">
            <tr>
                <td width="50%" align="center">
                    <hr style="width:80%; border:1px solid black;"/>
                    <p>Firma del responsable</p>
                </td>
                <td width="50%" align="center">
                    <hr style="width:80%; border:1px solid black;"/>
                    <p>Firma del proveedor</p>
                </td>
            </tr>
        </table>
    </body>
</html>
