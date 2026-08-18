@extends('layouts.app')

@section('title', 'CONTROL UNIDADES')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
		
        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">{{$vista == "crear" ? "NUEVA ORDEN DE SERVICIO" : "ORDEN DE SERVICIO"}}</h5>			

            <div class="card-body">

				<hr class="my-6 mx-n6" />

				<div class="row">
					<div class="col-md-3">
						<label for="txtSerie" class="form-label">Fecha:</label>
						<input type="date" id="txtFecha" class="form-control" value="{{$vista == 'crear' ? date('Y-m-d') : $info[0]->fecha}}" />
					</div>							
				</div>

				<hr class="my-6 mx-n6" />

				<div class="row">

					<div class="col-md-6">

						@if($vista == "crear")
						<div class="col-md-12">
							<label for="cmbProveedor" class="form-label">Proveedor:</label>
							<select id="cmbProveedor" class="selectpicker form-control" data-style="" data-live-search="true">
							</select>
						</div>
						@endif

						<div class="col-md-12">
							<table width="100%" border="1" class="table table-sm">
								<thead class="table-dark">
									<tr>
										<td align="center" colspan="2">Datos del Proveedor</td>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>Nombre:</td>
										<td id="lblNombreProveedor">{{$vista=="crear" ? "" : $info[0]->proveedor}}</td>
									</tr>
									<tr>
										<td>Domicilio:</td>
										<td id="lblDomicilioProveedor">{{$vista=="crear" ? "" : $info[0]->domicilio}}</td>
									</tr>
									<tr>
										<td>Ciudad:</td>
										<td id="lblCiudadProveedor">{{$vista=="crear" ? "" : $info[0]->ciudad}}</td>
									</tr>
									<tr>
										<td>Contacto:</td>
										<td id="lblContactoProveedor">{{$vista=="crear" ? "" : $info[0]->contacto}}</td>
									</tr>
									<tr>
										<td>Telefono:</td>
										<td id="lblTelefonoProveedor">{{$vista=="crear" ? "" : $info[0]->telefono}}</td>
									</tr>
									<tr>
										<td>Condiciones:</td>
										<td>&nbsp;</td>
									</tr>
									<tr>
										<td>Dias de Crédito:</td>
										<td>&nbsp;</td>
									</tr>
								</tbody>
							</table>
						</div>
						
					</div>

					<div class="col-md-6">

						@if($vista == "crear")
						<div class="col-md-12">
							<label for="cmbUnidad" class="form-label">Unidad:</label>
							<select id="cmbUnidad" class="selectpicker form-control" data-style="" data-live-search="true">
							</select>
						</div>
						@endif

						<div class="col-md-12">
							<table width="100%" border="1" class="table table-sm">
								<thead class="table-dark">
									<tr>
										<th class="text-center" colspan="2">Datos de la Unidad</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>No. Unidad:</td>
										<td id="lblNumeroUnidad">{{$vista=="crear" ? "" : $info[0]->numeroeconomico}}</td>
									</tr>
									<tr>
										<td>Ruta:</td>
										<td id="lblRuta">{{$vista=="crear" ? "" : $info[0]->ruta}}</td>
									</tr>
									<tr>
										<td>Operador:</td>
										<td id="lblOperador">{{$vista=="crear" ? "" : $info[0]->empleado}}</td>
									</tr>
									<tr>
										<td>Marca/Modelo:</td>
										<td id="lblMarcaModelo">{{$vista=="crear" ? "" : $info[0]->marca.'/'.$info[0]->modelo}}</td>
									</tr>
									<tr>
										<td>Placas:</td>
										<td id="lblPlacas">{{$vista=="crear" ? "" : $info[0]->placas}}</td>
									</tr>
									<tr>
										<td>Km:</td>
										<td>&nbsp;</td>
									</tr>
								</tbody>
							</table>
						</div>						

					</div>
					
					
				</div>

				<hr class="my-6 mx-n6" />

				<div class="row">
					<div class="col-md-12">
						<label for="txtServicioRealizar" class="form-label">Servicio a Realizar:</label>
						<input type="text" id="txtServicioRealizar" class="form-control" placeholder="Servicio a Realizar" value="{{$vista=='crear' ? '' : $info[0]->serviciorealizar}}" />
					</div>
				</div>

				<hr class="my-6 mx-n6" />

				<div class="row">
					@if($vista =='crear')
					<div class="col-md-8">
						<label for="cmbConcepto" class="form-label">Concepto:</label>
						<select id="cmbConcepto" class="selectpicker form-control" data-style="" data-live-search="true">
						</select>
					</div>
					<div class="col-md-4">						
						<br/>
						<button id="btnAgregarConcepto" type="button" class="btn btn-icon btn-primary">
							<span class="icon-base bx bx-plus icon-md"></span>
						</button>						
					</div>
					@endif
					<div class="col-md-12">
						<table id="tablaconceptos" width="100%" border="1" class="table table-sm">
							<thead class="table-dark">
								<tr>
									<th style="text-align:right">Cantidad</th>
									<th>Concepto</th>
									<th style="text-align:right">Precio Unitario</th>
									<th style="text-align:right">Importe</th>
									<th style="text-align:center">&nbsp;</th>
								</tr>
							</thead>
							<tbody>
								@foreach($info as $item)
								<tr>
									<td align="right" width="5%">{{$item->cantidad}}</td>
									<td width="60%">{{$item->concepto}}</td>
									<td align="right" width="20%">${{number_format($item->preciounitario, 2)}}</td>
									<td align="right" width="15%">${{number_format($item->importe, 2)}}</td>
									<td>&nbsp;</td>
								</tr>
								@endforeach
							</tbody>
							<tfoot>
								<tr>
									<td align="right" colspan="3">Descuento</td>
									<td id="lblDescuento" align="right">{{$vista=="crear" ? "0" : "$".number_format($info[0]->descuento, 2)}}</td>
								</tr>
								<tr>
									<td align="right" colspan="3">Subtotal</td>
									<td id="lblSubtotal" align="right">{{$vista=="crear" ? "0" : "$".number_format($info[0]->subtotal, 2)}}</td>
								</tr>
								<tr>
									<td align="right" colspan="3">IVA</td>
									<td id="lblIva" align="right">{{$vista=="crear" ? "0" : "$".number_format($info[0]->iva, 2)}}</td>
								</tr>
								<tr>
									<td align="right" colspan="3">Total</td>
									<td id="lblTotal" align="right">{{$vista=="crear" ? "0" : "$".number_format($info[0]->total, 2)}}</td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<label for="txtComentarios" class="form-label">Comentarios:</label>
						<textarea class="form-control" id="txtComentarios" rows="3">{{$vista=='crear' ? '' : $info[0]->comentarios}}</textarea>
					</div>
				</div>

				@if($vista =='crear')
				<div class="row">
					<div class="col-md-12" align="center">
						<br/>
						<button type="button" id="btnGuardar" class="btn btn-primary">Guardar</button>
					</div>
				</div>
				@endif

            </div>
        </div>
        <!--/ Bordered Table -->
    
    </div>
@endsection

@section('scripts')
<script>

	let dollarUS = Intl.NumberFormat("en-US", {
		style: "currency",
		currency: "USD",
		decimal: 2
	});

	$("#lblMensaje").hide();

	cargarProveedoresCombobox($("#cmbProveedor"));
	cargarUnidadesCombobox($("#cmbUnidad"));
	cargarConceptosMentenimientoCombobox($("#cmbConcepto"));

	$("#cmbProveedor").on("change", function()
	{
		var id = $(this).val();

		$("#lblNombreProveedor").text("");
		$("#lblDomicilioProveedor").text("");
		$("#lblCiudadProveedor").text("");
		$("#lblContactoProveedor").text("");
		$("#lblTelefonoProveedor").text("");

		if(id==0)
		{
			return;	
		}

		axios.get("/catalogos/getProveedorById/" + id)
	    .then(function(res) 
		{
			var info = res["data"][0];
			$("#lblNombreProveedor").text(info.razonsocial);
			$("#lblDomicilioProveedor").text(info.domicilio);
			$("#lblCiudadProveedor").text(info.ciudad);
			$("#lblContactoProveedor").text(info.contacto);
			$("#lblTelefonoProveedor").text(info.telefono);
	    })
	    .catch(function(err) 
		{
            alert(err)
	    });
	})

	$("#cmbUnidad").on("change", function()
	{
		var idunidad = $(this).val();

		$("#lblNumeroUnidad").text("");
		$("#lblRuta").text("");
		$("#lblOperador").text("");
		$("#lblMarcaModelo").text("");
		$("#lblPlacas").text("");

		if(idunidad==0)
		{
			return;	
		}

		axios.get("/controlunidad/getAsignacionByIdUnidad/" + idunidad)
	    .then(function(res) 
		{
			var info = res["data"][0];
			$("#lblNumeroUnidad").text(info.numeroeconomico);
			$("#lblRuta").text(info.ruta);
			$("#lblOperador").text(info.empleado);
			$("#lblMarcaModelo").text(info.marca + " " + info.modelo);
			$("#lblPlacas").text(info.placas);
	    })
	    .catch(function(err) 
		{
            alert(err)
	    });
	});

	$("#btnAgregarConcepto").on("click", function()
	{
		addConcepto();
	});

	$("#btnGuardar").on("click", function()
	{
		var idunidad = $("#cmbUnidad").val();
		var idproveedor = $("#cmbProveedor").val();
		var fecha = $("#txtFecha").val();
		var serviciorealizar = $("#txtServicioRealizar").val();
		var comentarios = $("#txtComentarios").val();
		var descuento = $("#lblDescuento").text();
		var subtotal = $("#lblSubtotal").text();
		var iva = $("#lblIva").text();
		var total = $("#lblTotal").text();

		descuento = descuento.replace("$", "").replace(",", "");
		subtotal = subtotal.replace("$", "").replace(",", "");
		iva = iva.replace("$", "").replace(",", "");
		total = total.replace("$", "").replace(",", "");

		if(idunidad == 0)
		{
			alert("Favor de seleccionar una unidad");
			return;
		}

		if(idproveedor == 0)
		{
			alert("Favor de seleccionar un proveedor");
			return;
		}

		if(items_conceptos.length == 0)
		{
			alert("Favor de agregar conceptos de servicios a realizar");
			return;
		}

		axios.post("/controlunidad/guardarOrdenServicio", 
		{
			fecha,
			idunidad,
			idproveedor,
			serviciorealizar,
			comentarios,
			descuento,
			subtotal,
			iva,
			total,
			items_conceptos
		})
	    .then(function(res) 
		{
			if(parseFloat(res.data) > 0)
			{
				location.href = "{{ route('ordenesservicio_view') }}";
			}
	    })
	    .catch(function(err) 
		{
            alert(err)
	    })
		.then(() => 
		{
			//$('#div_table_datos').removeClass('loadingtable');
		});

	});

	var items_conceptos = [];

	function cambiarValores(e, index)
	{
		var valor = $(e).val();
		valor = valor == "" ? 0 : valor;

		if($(e).hasClass("cantidad"))
		{
			items_conceptos[index]["cantidad"] = valor;
		}
		else if($(e).hasClass("preciounitario"))
		{
			items_conceptos[index]["preciounitario"] = valor;
		}

		calcularTotales();
	}

	function calcularTotales()
	{
		var total = 0;

		for(var i in items_conceptos)
		{
			var t = parseFloat(items_conceptos[i]["preciounitario"]) * parseFloat(items_conceptos[i]["cantidad"]);

			items_conceptos[i]["importe"] = t;
			$("#lblImporte" + i).text(dollarUS.format(t));

			total = total + t;
		}

		$("#lblTotal").text(dollarUS.format(total));
	}

	function fillTabla()
	{
		var fila = "";

		for(var i in items_conceptos)
		{
			fila = fila + "<tr>";
			fila = fila + "<td width='5%' align='right'><input type='number' class='cantidad form-control' value='" + items_conceptos[i]["cantidad"] + "' onkeyup='cambiarValores(this,"+i+")' /></td>";
			fila = fila + "<td width='55%'>" + items_conceptos[i]["concepto"] + "</td>";
			fila = fila + "<td width='20%' align='right'><input type='number' class='preciounitario form-control' value='" + items_conceptos[i]["importe"] + "' onkeyup='cambiarValores(this,"+i+")' /></td>";
			fila = fila + "<td id='lblImporte" + i + "' width='15%' align='right'>" + dollarUS.format(items_conceptos[i]["importe"]) + "</td>";
			fila = fila + "<td width='5%'><button title='Eliminar' class='eliminar btn btn-icon btn-outline-danger' onclick='eliminar(" + i + ")'><i class='bx bx-x'></i></button></td>";
		}

		$("#tablaconceptos tbody").html(fila);

		calcularTotales();
	}

	function addConcepto()
	{
		var idconcepto = $("#cmbConcepto").val();
		var concepto = $("#cmbConcepto option:selected").text();

		if(idconcepto == 0)
		{
			return;	
		}

		var item = {};
		item["cantidad"] = 1;
		item["idconcepto"] = idconcepto;
		item["concepto"] = concepto;
		item["preciounitario"] = 0;
		item["importe"] = 0;

		items_conceptos.push(item);

		fillTabla();
	}

	function eliminar(i)
	{
		items_conceptos.splice(i, 1);
		fillTabla();
	}

</script>
@endsection