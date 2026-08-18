@extends('layouts.app')

@section('title', 'Finanzas')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">Presupuesto</h5>

            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table id="table_categorias" class="table-bordered">

                        <thead>
                            <tr>
                                <th><b>MES</b></th>
                                <th><b>VENTAS</b></th>
                                <th><b>COSTO DE VENTAS</b></th>
                                <th><b>GASTOS</b></th>
                                <th><b>OTROS INGRESOS</b></th>
                                <th><b>UTILIDAD OPERATIVA</b></th>
                            </tr>
                        </thead>

                        <tbody>
                            
                        </tbody>

                        <tfoot>
                            <tr>
                                <td>Total:</td>
                                <td align="right"><b id="lblTotalVentas">$0.00</b></td>
                                <td align="right"><b id="lblTotalCostos">$0.00</b></td>
                                <td align="right"><b id="lblTotalGastos">$0.00</b></td>
                                <td align="right"><b id="lblTotalOtrosIngresos">$0.00</b></td>
                                <td align="right"><b id="lblTotalUtilidadOperativa">$0.00</b></td>
                            </tr>
                        </tfoot>

                    </table>
                </div>
            </div>
        </div>
        <!--/ Bordered Table -->
    
    </div>
@endsection

<script src="https://unpkg.com/axios/dist/axios.min.js"></script>

<script>

	let dollarUS = Intl.NumberFormat("en-US", {
		style: "currency",
		currency: "USD",
		decimal: 2
	});

    var items = [];

    cargarTablaProductos("2024", "1", "1");

    function cargarTablaProductos(pAnio, pSucursal, pNegocio)
	{		
		/*$('#table_categorias').addClass('loadingtable');

		if(pSucursal==null) pSucursal = "0";*/

		items = [];

        axios.get("{{ route('presupuesto_listado') }}", {
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

    function changeVentas(id, e)
	{
		var index = items.findIndex((obj => obj.presupuesto_id == id));

		items[index].presupuesto_ventas = e.value;

		sumaTotales();
    }

	function changeCostos(id, e)
	{
		var index = items.findIndex((obj => obj.presupuesto_id == id));

		items[index].presupuesto_costos = e.value;

		sumaTotales();
    }

	function changeGastos(id, e)
	{
		var index = items.findIndex((obj => obj.presupuesto_id == id));

		items[index].presupuesto_gastos = e.value;

		sumaTotales();
    }

	function changeOtrosIngresos(id, e)
	{
		var index = items.findIndex((obj => obj.presupuesto_id == id));

		items[index].presupuesto_otrosingresos = e.value;

		sumaTotales();
    }

	function sumaTotales()
	{
		var ventas = 0, costos = 0, gastos = 0, otrosingresos = 0, totalutilidadoperativa = 0;

		items.forEach((obj) => {

			var utilidadoperativa = 0;

			ventas = ventas + parseFloat(obj.presupuesto_ventas);
			costos = costos + parseFloat(obj.presupuesto_costos);
			gastos = gastos + parseFloat(obj.presupuesto_gastos);
			otrosingresos = otrosingresos + parseFloat(obj.presupuesto_otrosingresos);

			utilidadoperativa = parseFloat(obj.presupuesto_ventas) + parseFloat(obj.presupuesto_otrosingresos) - parseFloat(obj.presupuesto_costos) - parseFloat(obj.presupuesto_gastos);

			totalutilidadoperativa = totalutilidadoperativa + utilidadoperativa;

			$("#utilidadoperativa" + obj.presupuesto_id).text(dollarUS.format(parseFloat(utilidadoperativa)));
		});

		$("#lblTotalVentas").text(dollarUS.format(parseFloat(ventas)));
		$("#lblTotalCostos").text(dollarUS.format(parseFloat(costos)));
		$("#lblTotalGastos").text(dollarUS.format(parseFloat(gastos)));
		$("#lblTotalOtrosIngresos").text(dollarUS.format(parseFloat(otrosingresos)));
		$("#lblTotalUtilidadOperativa").text(dollarUS.format(parseFloat(totalutilidadoperativa)));
	}

    function renderItemsTable()
	{
		let dollarUS = Intl.NumberFormat("en-US", {
			style: "currency",
			currency: "USD",
			decimal: 2
		});

		var cadena = "";
		var totalventas = 0, totalcostos = 0, totalgastos = 0, totalotrosingresos = 0, totalutilidadoperativa = 0;

		for(var x in items)
		{
			var presupuesto_ventas = dollarUS.format(parseFloat(items[x].presupuesto_ventas)).replace("$", "");
			var presupuesto_costos = dollarUS.format(parseFloat(items[x].presupuesto_costos)).replace("$", "");
			var presupuesto_gastos = dollarUS.format(parseFloat(items[x].presupuesto_gastos)).replace("$", "");
			var presupuesto_otrosingresos = dollarUS.format(parseFloat(items[x].presupuesto_otrosingresos)).replace("$", "");
			var presupuesto_utilidadoperativa = dollarUS.format(parseFloat(items[x].presupuesto_utilidadoperativa)).replace("$", "");

			cadena = cadena + "<tr>";
			cadena = cadena + "<td><b>" + items[x].presupuesto_mes + "</b></td>";
			cadena = cadena + "<td align='right'><input type='text' onchange='changeVentas(" + items[x].presupuesto_id + ", this)' value='" + presupuesto_ventas + "' /></td>";
			cadena = cadena + "<td align='right'><input type='text' onchange='changeCostos(" + items[x].presupuesto_id + ", this)' value='" + presupuesto_costos + "' /></td>";
			cadena = cadena + "<td align='right'><input type='text' onchange='changeGastos(" + items[x].presupuesto_id + ", this)' value='" + presupuesto_gastos + "' /></td>";
			cadena = cadena + "<td align='right'><input type='text' onchange='changeOtrosIngresos(" + items[x].presupuesto_id + ", this)' value='" + presupuesto_otrosingresos + "' /></td>";
			cadena = cadena + "<td align='right' id='utilidadoperativa" + items[x].presupuesto_id + "'>$" + presupuesto_utilidadoperativa + "</td>";
			cadena = cadena + "</tr>";

			totalventas = totalventas + parseFloat(items[x].presupuesto_ventas);
			totalcostos = totalcostos + parseFloat(items[x].presupuesto_costos);
			totalgastos = totalgastos + parseFloat(items[x].presupuesto_gastos);
			totalotrosingresos = totalotrosingresos + parseFloat(items[x].presupuesto_otrosingresos);
			totalutilidadoperativa = totalutilidadoperativa + parseFloat(items[x].presupuesto_utilidadoperativa);
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