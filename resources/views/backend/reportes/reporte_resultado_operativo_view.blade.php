@extends('layouts.app')

@section('title', 'REPORTES')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">	

        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">RESULTADO OPERATIVO</h5>			

            <div class="card-body">


				<div class="row g-6">
					<div class="col-md-3">
						<label class="form-label" for="txtPeriodo">Periodo</label>
						<input type="month" id="txtPeriodo" class="form-control" placeholder="MES de AÑO" />
					</div>
					<div class="col-md-4 mb-6">
						<label for="cmbNegocio" class="form-label">Negocio</label>
						<select id="cmbNegocio" class="selectpicker w-100" data-style="btn-default" multiple data-actions-box="true">
						</select>
					</div>
					<div class="col-md-4 mb-6">
						<label for="cmbSucursal" class="form-label">Sucursal</label>
						<select id="cmbSucursal" class="selectpicker w-100" data-style="btn-default" multiple data-actions-box="true">
						</select>
					</div>
				</div>

				<div class="demo-inline-spacing">
					<button id="btnBuscar" type="button" class="btn btn-primary">
						<span class="tf-icons bx bx-search bx-18px me-2"></span>Buscar
					</button>
				</div>

				<hr class="my-6 mx-n6" />

                <div id="div_table_datos" class="table-responsive text-nowrap">
                    <table id="table_categorias" class="table table-sm table-bordered table-striped table-hover">

                        <thead>
                            <tr>
                                <th><b>PERIODO</b></th>
                                <th><b>FECHA</b></th>
                                <th><b>NEGOCIO</b></th>
                                <th><b>SUCURSAL</b></th>
                                <th><b>VENTAS</b></th>
                                <th><b>DEV S/VENTA</b></th>
								<th><b>VENTAS NETAS</b></th>
								<th><b>COSTO DE VENTAS</b></th>
								<th><b>UTILIDAD BRUTA</b></th>
								<th><b>% UT BR S/VENTAS</b></th>
								<th><b>GASTOS</b></th>
								<th><b>UTILIDAD NETA</b></th>
								<th><b>% UT S/VENTAS</b></th>
                            </tr>
                        </thead>

                        <tbody>
                            
                        </tbody>

                        <tfoot>
                            <!--<tr>
                                <td>Total:</td>
                                <td align="right"><b id="lblTotalVentas">$0.00</b></td>
                                <td align="right"><b id="lblTotalCostos">$0.00</b></td>
                                <td align="right"><b id="lblTotalGastos">$0.00</b></td>
                                <td align="right"><b id="lblTotalOtrosIngresos">$0.00</b></td>
                                <td align="right"><b id="lblTotalUtilidadOperativa">$0.00</b></td>
                            </tr>-->
                        </tfoot>

                    </table>
                </div>
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

	$("#btnBuscar").on("click", function(){
		var periodo = $("#txtPeriodo").val();
		var negocio = $("#cmbNegocio").val().toString();
		var sucursal = $("#cmbSucursal").val().toString();

		periodo = periodo.replace("-", "");

		cargarTablaProductos(periodo, negocio, sucursal);
	});	

	cargarNegociosCombobox($('#cmbNegocio'));
	cargarSucursalesCombobox($('#cmbSucursal'));

    function cargarTablaProductos(pPeriodo, pNegocio, pSucursal)
	{
		if(pPeriodo == "")
		{ 
			alert("Favor de seleccionar un periodo"); 
			return;
		}

		if(pNegocio == "")
		{ 
			alert("Favor de seleccionar un negocio"); 
			return;
		}

		if(pSucursal == "")
		{ 
			alert("Favor de seleccionar una sucursal"); 
			return;
		}

		$('#div_table_datos').addClass('loadingtable');

		items = [];

        axios.get("/reportes/getVentas/" + pPeriodo + "/" + pNegocio + "/" + pSucursal, {
			responseType: 'json'
		})
	    .then(function(data) {
            var datos = data["data"];
			if(datos.length > 0)
			{
				items = datos;
			}
			else
			{
				
			}
            renderItemsTable();
	    })
	    .catch(function(err) {
            alert(err)
	    })
	    .then(function() {
			$('#div_table_datos').removeClass('loadingtable');
	    });
	}

    function renderItemsTable()
	{
		let dollarUS = Intl.NumberFormat("en-US", {
			style: "currency",
			currency: "USD",
			decimal: 2
		});

		var cadena = "";
		//var totalventas = 0, totalcostos = 0, totalgastos = 0, totalotrosingresos = 0, totalutilidadoperativa = 0;

		for(var x in items)
		{
			/*var presupuesto_ventas = dollarUS.format(parseFloat(items[x].presupuesto_ventas)).replace("$", "");
			var presupuesto_costos = dollarUS.format(parseFloat(items[x].presupuesto_costos)).replace("$", "");
			var presupuesto_gastos = dollarUS.format(parseFloat(items[x].presupuesto_gastos)).replace("$", "");
			var presupuesto_otrosingresos = dollarUS.format(parseFloat(items[x].presupuesto_otrosingresos)).replace("$", "");
			var presupuesto_utilidadoperativa = dollarUS.format(parseFloat(items[x].presupuesto_utilidadoperativa)).replace("$", "");*/

			cadena = cadena + "<tr>";
			cadena = cadena + "<td>" + items[x].periodo + "</td>";
			cadena = cadena + "<td>" + items[x].fecha + "</td>";
			cadena = cadena + "<td>" + items[x].negocio + "</td>";
			cadena = cadena + "<td>" + items[x].sucursal + "</td>";
			cadena = cadena + "<td align='right'>" + items[x].venta + "</td>";
			cadena = cadena + "<td align='right'>" + items[x].devolucion + "</td>";
			cadena = cadena + "<td align='right'>" + items[x].ventas_netas + "</td>";
			cadena = cadena + "<td align='right'>" + items[x].costo + "</td>";
			cadena = cadena + "<td align='right'>" + items[x].utilidad_bruta + "</td>";
			cadena = cadena + "<td align='right'>" + items[x].utilidad_bruta_porcentaje + "</td>";
			cadena = cadena + "<td align='right'>" + items[x].gastos + "</td>";
			cadena = cadena + "<td align='right'>" + items[x].utilidad_neta + "</td>";
			cadena = cadena + "<td align='right'>" + items[x].utilidad_neta_porcentaje + "</td>";
			cadena = cadena + "</tr>";

			/*totalventas = totalventas + parseFloat(items[x].presupuesto_ventas);
			totalcostos = totalcostos + parseFloat(items[x].presupuesto_costos);
			totalgastos = totalgastos + parseFloat(items[x].presupuesto_gastos);
			totalotrosingresos = totalotrosingresos + parseFloat(items[x].presupuesto_otrosingresos);
			totalutilidadoperativa = totalutilidadoperativa + parseFloat(items[x].presupuesto_utilidadoperativa);*/
		}

		/*$("#lblTotalVentas").text(dollarUS.format(parseFloat(totalventas)));
		$("#lblTotalCostos").text(dollarUS.format(parseFloat(totalcostos)));
		$("#lblTotalGastos").text(dollarUS.format(parseFloat(totalgastos)));
		$("#lblTotalOtrosIngresos").text(dollarUS.format(parseFloat(totalotrosingresos)));
		$("#lblTotalUtilidadOperativa").text(dollarUS.format(parseFloat(totalutilidadoperativa)));
		*/

        $("#table_categorias tbody").html(cadena);
	}

</script>
@endsection