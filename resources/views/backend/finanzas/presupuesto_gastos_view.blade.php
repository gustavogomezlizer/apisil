@extends('layouts.app')

@section('title', 'Finanzas')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">Presupuesto Gastos</h5>

            <div class="card-body">

				<div class="row g-6">
					<div class="col-md-3">
						<label class="form-label" for="txtPeriodo">Periodo</label>
						<input type="month" id="txtPeriodo" class="form-control" placeholder="MES de AÑO" />
					</div>
					<div class="col-md-4 mb-6">
						<label for="cmbSucursal" class="form-label">Sucursal</label>
						<select id="cmbSucursal" class="selectpicker w-100" data-style="btn-default">
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
                    <table id="table_categorias" class="table table-bordered table-sm table-striped">

                        <thead>
                            <tr>
								<th><b>SUCURSAL</b></th>
                                <th><b>PERIODO</b></th>
                                <th><b>CONCEPTO</b></th>
                                <th><b>PRESUPUESTO</b></th>
                            </tr>
                        </thead>

                        <tbody>
                            
                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan=3 align="right">Total:</td>
                                <td align="right"><b id="lblTotal">$0.00</b></td>
                            </tr>
                        </tfoot>

                    </table>
                </div>

				<div align="right" class="demo-inline-spacing">
					<button id="btnGuardar" type="button" class="btn btn-success">
						<span class="tf-icons bx bx-save bx-18px me-2"></span>Guardar
					</button>
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

    var items = [];

	cargarSucursalesCombobox($('#cmbSucursal'));

	$("#btnBuscar").on("click", function()
	{
		var periodo = $("#txtPeriodo").val();
		var idsucursal = $("#cmbSucursal").val();

		periodo = periodo.replace("-", "");

		cargarDatosTabla(periodo, idsucursal);
	});

	$("#btnGuardar").on("click", function()
	{
		$('#div_table_datos').addClass('loadingtable');

		axios.post("/finanzas/savePresupuestoGastos", {
			items,
		})
	    .then(function(data) {
			$("#btnBuscar").click();
	    })
	    .catch(function(err) {
            alert(err)
	    })
		.then(() => {
			$('#div_table_datos').removeClass('loadingtable');
		});
	});

    function cargarDatosTabla(pPeriodo, pSucursal)
	{
		if(pPeriodo == "")
		{
			alert("Favor de seleccionar un periodo"); 
			return;
		}

		$('#div_table_datos').addClass('loadingtable');

		items = [];

        axios.get("/finanzas/getListadoPresupuestoGastos/" + pPeriodo + "/" + pSucursal, {
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
            alert(err);
	    })
		.then(() => {
			$('#div_table_datos').removeClass('loadingtable');
		});
	}

	function changePresupuesto(id, e)
	{
		var index = items.findIndex((obj => obj.idconcepto == id));

		items[index].presupuesto = e.value;

		sumaTotales();
    }

	function sumaTotales()
	{
		var total = 0;

		items.forEach((obj) => {

			var utilidadoperativa = 0;

			total = total + parseFloat(obj.presupuesto);
		});

		$("#lblTotal").text(dollarUS.format(parseFloat(total)));
	}

    function renderItemsTable()
	{
		let dollarUS = Intl.NumberFormat("en-US", {
			style: "currency",
			currency: "USD",
			decimal: 2
		});

		var cadena = "";
		var totalpresupuesto = 0;

		for(var x in items)
		{
			var presupuesto_ventas = dollarUS.format(parseFloat(items[x].presupuesto_ventas)).replace("$", "");
			var presupuesto_costos = dollarUS.format(parseFloat(items[x].presupuesto_costos)).replace("$", "");
			var presupuesto_gastos = dollarUS.format(parseFloat(items[x].presupuesto_gastos)).replace("$", "");
			var presupuesto_otrosingresos = dollarUS.format(parseFloat(items[x].presupuesto_otrosingresos)).replace("$", "");
			var presupuesto_utilidadoperativa = dollarUS.format(parseFloat(items[x].presupuesto_utilidadoperativa)).replace("$", "");

			cadena = cadena + "<tr>";
			cadena = cadena + "<td>" + items[x].sucursal + "</td>";
			cadena = cadena + "<td>" + items[x].periodo + "</td>";
			cadena = cadena + "<td>" + items[x].descripcion + "</td>";
			cadena = cadena + "<td align='right'><input type='text' onchange='changePresupuesto(" + items[x].idconcepto + ", this)' value='" + items[x].presupuesto + "' /></td>";
			cadena = cadena + "</tr>";

			totalpresupuesto = totalpresupuesto + parseFloat(items[x].presupuesto);
		}

		$("#lblTotal").text(dollarUS.format(parseFloat(totalpresupuesto)));

        $("#table_categorias tbody").html(cadena);
	}

</script>
@endsection