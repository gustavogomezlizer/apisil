@extends('layouts.app')

@section('title', 'CONTROL UNIDADES')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
		
        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">REPORTE KILOMETRAJES</h5>

            <div class="card-body">

				<hr class="my-6 mx-n6" />

				<div class="row">
					<div class="col-md-3">
						<label for="cmbUnidad" class="form-label">*Unidad:</label>
						<input type="hidden" id="txtId" value="0"/>
						<select id="cmbUnidad" class="selectpicker form-select" data-style="btn-default" data-live-search="true">
						</select>
					</div>
					<div class="col-md-2">
						<label for="txtFechaFinal" class="form-label">De:</label>
						<input type="date" id="txtFechaInicio" class="form-control" value="{{ date('Y-m-d') }}" />
					</div>
					<div class="col-md-2">
						<label for="txtFechaFinal" class="form-label">A:</label>
						<input type="date" id="txtFechaFinal" class="form-control" value="{{ date('Y-m-d') }}" />
					</div>

					<div class="col-md-2">
						<br/>
						<button type="button" id="btnBuscar" class="btn btn-primary">Buscar</button>
					</div>
				</div>

                <div class="table-responsive text-nowrap">
                    <table id="table" class="table table-striped table-sm table-hover">

                        <thead class="table-dark">
							<tr>
                                <th>FECHA</th>
								<th>UNIDAD</th>
                                <th class="text-center" colspan="3">KM REGISTRADOS</th>
								<th class="text-center" colspan="3">KM RECORRIDOS</th>
								<th class="text-center" colspan="3">NIVEL GAS. REGISTRADOS</th>
								<th class="text-center" colspan="3">LITROS CONSUMIDOS</th>
                            </tr>
                            <tr>
                                <th>FECHA</th>
								<th>UNIDAD</th>

                                <th>INICIO</th>
								<th>FINAL</th>
								<th>PERSONAL</th>

								<th>EN RUTA</th>
								<th>PERSONAL</th>
								<th>TOTAL</th>

								<th>INICIO</th>
								<th>FINAL</th>
								<th>PERSONAL</th>

								<th>EN RUTA</th>
								<th>PERSONAL</th>
								<th>TOTAL</th>
                            </tr>
                        </thead>

                        <tbody>
                            
                        </tbody>

                        <tfoot>
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

	$("#lblMensaje").hide();

	var table = $('#table').DataTable();

	cargarUnidadesCombobox($("#cmbUnidad"));	

	$("#btnBuscar").on("click", function()
	{
		cargarTabla();
	});

	/*$("#cmbUnidad").on("change", function()
	{
		cargarTabla();
	});*/

    function cargarTabla()
	{
		var idunidad = $("#cmbUnidad").val();
		var fechade = $("#txtFechaInicio").val();
		var fechaa = $("#txtFechaFinal").val();

		table = $('#table').DataTable( {
			destroy: true,
			pageLength: 50,
			autoWidth: true,
			ajax: {url: "/controlunidad/getReporteRegistroKilometraje/" + idunidad + "/" + fechade + "/" + fechaa, dataSrc:""},
			ordering: false,
			columns: [
				{ data: 'fechainicio', width: "1%", className: "dt-left" },
				{ data: 'unidad', width: "1%", className: "dt-left" },
				{ data: 'kminicio', width: "10%", className: "dt-right" },
				{ data: 'kmfinal', width: "10%", className: "dt-right" },
				{ data: 'kmpersonal', width: "10%", className: "dt-right" },
				{ data: 'kmrecorridosruta', width: "10%", className: "dt-right" },
				{ data: 'kmrecorridospersonal', width: "10%", className: "dt-right" },
				{ data: 'kmrecorridostotal', width: "10%", className: "dt-right" },
				{ data: 'nivelgasolinainiciofraccion', width: "15%", className: "dt-right" },
				{ data: 'nivelgasolinafinalfraccion', width: "15%", className: "dt-right" },
				{ data: 'nivelgasolinapersonalfraccion', width: "15%", className: "dt-right" },
				{ data: 'litrosconsumidosruta', width: "10%", className: "dt-right" },
				{ data: 'litrosconsumidospersonal', width: "10%", className: "dt-right" },
				{ data: 'litrosconsumidostotal', width: "10%", className: "dt-right" },
			],
			"columnDefs": [
				{ targets: [2,3,4,5,6,7], render: $.fn.dataTable.render.number(',', '.', 0, '') }
			]
		});
	}

</script>
@endsection