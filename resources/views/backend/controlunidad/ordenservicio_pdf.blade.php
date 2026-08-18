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
                <td width="0%"><img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('/assets/img/logo/logo.jpg'))) }}" alt="{{ public_path() }}" height="40" width="100" /></td>
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
                <td><b>Fecha:</b></td>
                <td>{{$info[0]->fecha}}</td>
                <td align="right"><b>Folio:</b></td>
                <td>{{$info[0]->fecha}}</td>
            </tr>
        </table>

        <br/>
        <div align="center" style="border: 0.1px solid black;background:#dedede;">Datos del Proveedor</div>
        <div style="border: 0.1px solid black;">
            <table border="0" width="100%">
                <tr>
                    <td><b>Nombre:</b></td>
                    <td>{{$info[0]->proveedor}}</td>
                </tr>
                <tr>
                    <td><b>Domicilio:</b></td>
                    <td>{{$info[0]->domicilio}}</td>
                </tr>
                <tr>
                    <td><b>Ciudad:</b></td>
                    <td>{{$info[0]->ciudad}}</td>
                </tr>
                <tr>
                    <td><b>Contacto:</b></td>
                    <td>{{$info[0]->contacto}}</td>
                </tr>
                <tr>
                    <td><b>Telefono:</b></td>
                    <td>{{$info[0]->telefono}}</td>
                </tr>
            </table>
        </div>

        <br/>
        <div align="center" style="border: 0.1px solid black;background:#dedede;">Datos de la Unidad</div>
        <div style="border: 0.1px solid black;">
            <table border="0" width="100%">
                <tr>
                    <td><b>No. Unidad:</b></td>
                    <td>{{$info[0]->numeroeconomico}}</td>
                </tr>
                <tr>
                    <td><b>Ruta:</b></td>
                    <td>{{$info[0]->ruta}}</td>
                </tr>
                <tr>
                    <td><b>Operador:</b></td>
                    <td>{{$info[0]->empleado}}</td>
                </tr>
                <tr>
                    <td><b>Marca/Modelo:</b></td>
                    <td>{{$info[0]->marca.'/'.$info[0]->modelo}}</td>
                </tr>
                <tr>
                    <td><b>Placas:</b></td>
                    <td>{{$info[0]->placas}}</td>
                </tr>
            </table>
        </div>

        <br/>
        <table>
            <tr>
                <td><b>Servicio a Realizar:</b></td>
                <td>{{$info[0]->serviciorealizar}}</td>
            </tr>
        </table>
        <br/>

        <table width="100%" border="1" style="border: 1px solid black;border-collapse: collapse;">
            <thead style="background:#dedede;">
                <tr>
                    <th align="right">Cantidad</th>
                    <th align="left">Concepto</th>
                    <th align="right">Precio Unitario</th>
                    <th align="right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($info as $item)
                <tr>
                    <td align="right">{{$item->cantidad}}</td>
                    <td align="left">{{$item->concepto}}</td>
                    <td align="right">${{number_format($item->preciounitario, 2)}}</td>
                    <td align="right">${{number_format($item->importe, 2)}}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td align="right" colspan="3">Descuento</td>
                    <td align="right">${{number_format($info[0]->descuento, 2)}}</td>
                </tr>
                <tr>
                    <td align="right" colspan="3">Subtotal</td>
                    <td align="right">${{number_format($info[0]->subtotal, 2)}}</td>
                </tr>
                <tr>
                    <td align="right" colspan="3">IVA</td>
                    <td align="right">${{number_format($info[0]->iva, 2)}}</td>
                </tr>
                <tr>
                    <td align="right" colspan="3">Total</td>
                    <td align="right">${{number_format($info[0]->total, 2)}}</td>
                </tr>
            </tfoot>
        </table>

        <br/>
        <table>
            <tr>
                <td><b>Comentarios:</b></td>
            </tr>
            <tr>
                <td>{{$info[0]->comentarios}}</td>
            </tr>
        </table>
        <br/>
    </body>
</html>