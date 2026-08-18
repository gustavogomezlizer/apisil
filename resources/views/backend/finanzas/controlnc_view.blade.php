@extends('layouts.app')

@section('title', 'FINANZAS')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

		<!-- Large Modal -->
		<div class="modal fade" id="largeModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="exampleModalLabel3">Nueva Nota de Credito</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>

					<div class="modal-body">
						<div class="row">
							<div class="col mb-6">
								<label for="nameLarge" class="form-label">Name</label>
								<input type="text" id="nameLarge" class="form-control" placeholder="Enter Name">
							</div>
						</div>
						<div class="row g-6">
							<div class="col mb-0">
								<label for="emailLarge" class="form-label">Email</label>
								<input type="email" id="emailLarge" class="form-control" placeholder="xxxx@xxx.xx">
							</div>
							<div class="col mb-0">
								<label for="dobLarge" class="form-label">DOB</label>
								<input type="date" id="dobLarge" class="form-control">
							</div>
						</div>
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
						<button type="button" class="btn btn-primary">Save changes</button>
					</div>
				</div>
			</div>
		</div>

        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">CONTROL NOTAS DE CREDITO</h5>			

            <div class="card-body">


				<!--<div class="row g-6">
					<div class="col-md-2">
						<label class="form-label" for="txtPeriodo">Periodo</label>
						<input type="text" id="txtPeriodo" class="form-control" placeholder="periodo" />
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
				</div>-->

				<div class="demo-inline-spacing" align="right">
					<button id="btnBuscar" type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#largeModal">
						<span class="tf-icons bx bx-message-square-add bx-18px me-2"></span>Nuevo
					</button>
				</div>

				<hr class="my-6 mx-n6" />

                <div class="table-responsive text-nowrap">
                    <table id="table_categorias" class="table table-sm table-bordered">

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

		cargarTablaProductos(periodo, negocio, sucursal);
	});	

	//cargarNegociosCombobox($('#cmbNegocio'));
	//cargarSucursalesCombobox($('#cmbSucursal'));

    function cargarTablaProductos(pPeriodo, pNegocio, pSucursal)
	{		
		/*$('#table_categorias').addClass('loadingtable');

		if(pSucursal==null) pSucursal = "0";*/

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
			//$('#divInfoCliente').removeClass('loadingtable');
            renderItemsTable();
	    })
	    .catch(function(err) {
            alert(err)
	    	//$('#divInfoCliente').removeClass('loadingtable');
	    })
	    .then(function() {
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