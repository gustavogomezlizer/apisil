@extends('layouts.app')

@section('title', 'CONTROL UNIDADES')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
		
        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">UNIDADES</h5>			

            <div class="card-body">

				<div class="demo-inline-spacing" align="right">
					<button id="btnBuscar" type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal_nueva_unidad">
						<span class="tf-icons bx bx-message-square-add bx-18px me-2"></span>Nuevo
					</button>
				</div>

				<hr class="my-6 mx-n6" />

                <div class="table-responsive text-nowrap">
                    <table id="table" class="table table-striped table-sm table-hover">

                        <thead class="table-dark">
                            <tr>
                                <th>NUMERO ECONOMICO</th>
                                <th>PLACAS</th>
                                <th>SERIE</th>
								<th>MODELO</th>
								<th>KM ACTUAL</th>
								<th>&nbsp;</th>
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

		<!-- INICIO MODAL NUEVA UNIDAD -->
		<div class="modal fade" id="modal_nueva_unidad" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-xl" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="exampleModalLabel3">Nueva Unidad</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>

					<div class="modal-body">
						<div class="row">
							<div class="col-md-4">
								<label for="txtNumeroEconomico" class="form-label">*Número Economico:</label>
								<input type="hidden" id="txtId" value="0" />
								<input type="text" id="txtNumeroEconomico" class="form-control" placeholder="Número Economico" />
							</div>
							<div class="col-md-4">
								<label for="txtSerie" class="form-label">*Serie:</label>
								<input type="text" id="txtSerie" class="form-control" placeholder="Serie" />
							</div>
							<div class="col-md-4">
								<label for="txtPlacas" class="form-label">Placas:</label>
								<input type="text" id="txtPlacas" class="form-control" placeholder="Placas" />
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<label for="cmbTipoVehiculo" class="form-label">Tipo Vehiculo:</label>
								<select id="cmbTipoVehiculo" class="form-select">
								</select>
							</div>
							<div class="col-md-4">
								<label for="cmbAnio" class="form-label">Año:</label>
								<select id="cmbAnio" class="form-select">
								</select>
							</div>
							<div class="col-md-4">
								<label for="cmbMarca" class="form-label">Marca:</label>
								<select id="cmbMarca" class="form-select">
								</select>
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<label for="cmbModelo" class="form-label">Modelo:</label>
								<select id="cmbModelo" class="form-select">
								</select>
							</div>
							<div class="col-md-4">
								<label for="cmbColor" class="form-label">Color:</label>
								<select id="cmbColor" class="form-select">
								</select>
							</div>
							<div class="col-md-4">
								<label for="txtKmActual" class="form-label">*KM Actual:</label>
								<input type="number" id="txtKmActual" class="form-control" placeholder="KM Actual" />
							</div>
						</div>
						<div class="row">
							<div class="col-md-4">
								<label for="txtCapacidadTanque" class="form-label">Capacidad Tanque:</label>
								<input type="number" id="txtCapacidadTanque" class="form-control" placeholder="Capacidad Tanque" />
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<label for="txtComentarios" class="form-label">Comentarios:</label>
								<textarea class="form-control" id="txtComentarios" rows="3"></textarea>
							</div>
						</div>

						<div class="row">
							<div class="col-md-12">
								<br/>
								<div id="lblMensaje" class="alert alert-danger" role="alert">Mensaje</div>
							</div>
						</div>
					
					</div>

					<div class="modal-footer">
						<button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cerrar</button>
						<button type="button" id="btnGuardar" class="btn btn-primary">Guardar</button>
					</div>
				</div>
			</div>
		</div>
		<!-- FIN NUEVA UNIDAD -->
    
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

	cargarTipoVehiculoCombobox($("#cmbTipoVehiculo"));
	cargarAniosCombobox($("#cmbAnio"));
	cargarMarcasCombobox($("#cmbMarca"));
	cargarModelosCombobox($("#cmbModelo"), 0);
	cargarColoresCombobox($("#cmbColor"));

	cargarTabla()

	$("#cmbMarca").on("change", function()
	{
		cargarModelosCombobox($("#cmbModelo"), $(this).val());
	});

	$('#modal_nueva_unidad').on('show.bs.modal', function (event) 
	{
  		/*var button = $(event.relatedTarget);
  		var recipient = button.data('whatever');
  		var modal = $(this);*/
  		
		//modal.find('.modal-title').text('New message to ' + recipient)
  		//modal.find('.modal-body input').val(recipient)
	})

	$('#modal_nueva_unidad').on('hidden.bs.modal', function (e) 
	{
  		$(this).find("input,textarea,select").val('').end();
		$(this).find("select").val('0').end();
    	$(this).find("input[type=checkbox], input[type=radio]").prop("checked", "").end();

		$("#lblMensaje").hide();
	});

	$("#btnGuardar").on("click", function()
	{
		$("#lblMensaje").hide();

		var id = $("#txtId").val();
		var numeroeconomico = $("#txtNumeroEconomico").val().trim();
		var serie = $("#txtSerie").val().trim();
		var placas = $("#txtPlacas").val().trim();
		var idtipovehiculo = $("#cmbTipoVehiculo").val();
		var idanio = $("#cmbAnio").val();
		var idmarca = $("#cmbMarca").val();
		var idmodelo = $("#cmbModelo").val();
		var idcolor = $("#cmbColor").val();
		var kmactual = $("#txtKmActual").val().trim();
		var capacidadtanque = $("#txtCapacidadTanque").val().trim();
		var comentarios = $("#txtComentarios").val();

		axios.post("/controlunidad/guardar_unidad", 
		{
			id,
			numeroeconomico,
			serie,
			placas,
			idtipovehiculo,
			idanio,
			idmarca,
			idmodelo,
			idcolor,
			kmactual,
			capacidadtanque,
			comentarios
		})
	    .then(function(res) 
		{
			if(parseFloat(res.data) > 0)
			{
				$('#modal_nueva_unidad').modal('hide');
				cargarTabla();
			}
			else
			{
				$("#lblMensaje").html(res.data);
				$("#lblMensaje").show();
			}
			//$("#btnBuscar").click();
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

	$('#table tbody').on( 'click', 'a.showrow', function () {
		var row = table.row( $(this).parents('tr') ).data();
		
		var modal = $('#modal_nueva_unidad');
		modal.find("input[id='txtId']").val(row.id);
		modal.find("input[id='txtNumeroEconomico']").val(row.numeroeconomico);
		modal.find("input[id='txtSerie']").val(row.serie);
		modal.find("input[id='txtPlacas']").val(row.placas);
		modal.find("select[id='cmbTipoVehiculo']").val(row.idtipovehiculo);
		modal.find("select[id='cmbAnio']").val(row.idanio);
		modal.find("select[id='cmbMarca']").val(row.idmarca);
		modal.find("select[id='cmbModelo']").val(row.idmodelo);
		modal.find("select[id='cmbColor']").val(row.idcolor);
		modal.find("input[id='txtKmActual']").val(row.kmactual);
		modal.find("input[id='txtCapacidadTanque']").val(row.capacidadtanque);
		modal.find("textarea[id='txtComentarios']").val(row.comentarios);

		cargarModelosCombobox($("#cmbModelo"), row.idmarca, row.idmodelo);

		modal.modal('show');
	});

	//cargarNegociosCombobox($('#cmbNegocio'));
	//cargarSucursalesCombobox($('#cmbSucursal'));

    function cargarTabla()
	{
		table = $('#table').DataTable( {
			destroy: true,
			pageLength: 50,
			autoWidth: true,
			ajax: {url: "/controlunidad/getUnidades", dataSrc:""},
			//ordering: false,
			columns: [
				{ data: 'numeroeconomico', width: "1%", className: "dt-left" },
				{ data: 'placas', width: "10%", className: "dt-left" },
				{ data: 'serie', width: "10%", className: "dt-left" },
				{ data: 'modelo', width: "15%", className: "dt-left" },
				{ data: 'kmactual', width: "5%", className: "dt-right" },
				{ data: null, width: "2%", className: "dt-center" },
			],
			"columnDefs": [
				{
					"targets": 5,
					"data" : "id",
					"defaultContent": 									
					"<a href='#' title='Ver o editar' class='showrow btn btn-icon btn-outline-primary'><i class='bx bx-edit'></i></a>"
				}
			]
		});
	}

</script>
@endsection