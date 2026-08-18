@extends('layouts.app')

@section('title', 'CONTROL UNIDADES')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
		
        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">REGISTRO KILOMETRAJE</h5>

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
								<th>MODELO</th>
                                <th>FECHA INICIO</th>
                                <th>KM INICIO</th>
								<th>NIVEL GAS. INICIO</th>
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
						<h5 class="modal-title" id="exampleModalLabel3">Nuevo registro de kilometraje</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>

					<div class="modal-body">
						<div class="row">
							<div class="col-md-4">
								<label for="cmbUnidad" class="form-label">*Unidad:</label>
								<input type="hidden" id="txtId" value="0"/>
								<select id="cmbUnidad" class="selectpicker form-select" data-style="btn-default" data-live-search="true">
								</select>
							</div>
						</div>	
						<div class="row">
							<div class="col-md-4">
								<label for="txtSucursal" class="form-label">Sucursal:</label>
								<input type="text" id="txtSucursal" class="form-control" readonly/>
							</div>

							<div class="col-md-4">
								<label for="txtAsignadoA" class="form-label">Asignado a:</label>
								<input type="text" id="txtAsignadoA" class="form-control" readonly/>
							</div>
							<div class="col-md-4">
								<label for="txtRuta" class="form-label">Ruta:</label>
								<input type="text" id="txtRuta" class="form-control" readonly/>
							</div>
						</div>
						<hr/>
						<div class="row">
							<div class="col-md-2">
								<label for="txtFechaInicio" class="form-label">Fecha Inicio:</label>
								<input type="date" id="txtFechaInicio" class="form-control" value="{{ date('Y-m-d') }}" />
							</div>
							<div class="col-md-2">
								<label for="txtHoraInicio" class="form-label">Hora Inicio:</label>
								<input type="time" id="txtHoraInicio" class="form-control" value="{{ date('H:i:s') }}" />
							</div>

							<div class="col-md-4">
								<label for="txtKmInicio" class="form-label">Km Inicial:</label>
								<input type="number" id="txtKmInicio" class="form-control" placeholder="Km Inicial"/>
							</div>
							<div class="col-md-4">
								<label for="cmbNivelGasolinaInicio" class="form-label">Nivel Gasolina:</label>
								<select id="cmbNivelGasolinaInicio" class="form-select">
								</select>
							</div>
						</div>
						<hr/>
						<div id="divregistrofinal" class="row">
							<div class="col-md-2">
								<label for="txtFechaFinal" class="form-label">Fecha Final:</label>
								<input type="date" id="txtFechaFinal" class="form-control" value="{{ date('Y-m-d') }}" />
							</div>
							<div class="col-md-2">
								<label for="txtHoraFinal" class="form-label">Hora Final:</label>
								<input type="time" id="txtHoraFinal" class="form-control" value="{{ date('H:i:s') }}" />
							</div>

							<div class="col-md-4">
								<label for="txtKmFinal" class="form-label">Km Final:</label>
								<input type="number" id="txtKmFinal" class="form-control" placeholder="Km Final"/>
							</div>
							<div class="col-md-4">
								<label for="cmbNivelGasolinaFinal" class="form-label">Nivel Gasolina:</label>
								<select id="cmbNivelGasolinaFinal" class="form-select">
								</select>
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

	cargarUnidadesCombobox($("#cmbUnidad"));
	cargarNivelGasolinaCombobox($("#cmbNivelGasolinaInicio"));
	cargarNivelGasolinaCombobox($("#cmbNivelGasolinaFinal"));

	cargarTabla();

	$("#cmbUnidad").on("change", function()
	{
		var idunidad = $(this).val();

		axios.get("/controlunidad/getAsignacionByIdUnidad/" + idunidad)
	    .then(function(res) 
		{
			var info = res["data"];
			$("#txtSucursal").val(info[0].sucursal).prop('readonly', true);
			$("#txtAsignadoA").val(info[0].empleado).prop('readonly', true);
			$("#txtRuta").val(info[0].ruta).prop('readonly', true);

			console.log(info)
	    })
	    .catch(function(err) 
		{
            alert(err)
	    });
	});

	$('#modal_nueva_unidad').on('show.bs.modal', function (event) 
	{
  		/*var button = $(event.relatedTarget);
  		var recipient = button.data('whatever');
  		var modal = $(this);*/
  		
		//modal.find('.modal-title').text('New message to ' + recipient)
  		//modal.find('.modal-body input').val(recipient)

		var id = $("#txtId").val();

		if(id == "0")
		{
			$("#divregistrofinal").hide();
		}
		else
		{
			$("#divregistrofinal").show();
		}
	})

	$('#modal_nueva_unidad').on('hidden.bs.modal', function (e) 
	{
  		$(this).find("input,textarea,select").val('').prop('readonly', false).end();
		$(this).find("select").val('0').prop('disabled', false).end();
    	$(this).find("input[type=checkbox], input[type=radio]").prop("checked", "").end();

		$(this).find("select[id='cmbUnidad']").selectpicker('val', "0");

		$(this).find(".modal-title").text("Nuevo registro de kilometraje");

		$("#txtId").val("0");

		$("#lblMensaje").hide();
	});

	$("#btnGuardar").on("click", function()
	{
		$("#lblMensaje").hide();

		var id = $("#txtId").val();
		var idunidad = $("#cmbUnidad").val().trim();
		var fechainicio = $("#txtFechaInicio").val().trim();
		var horainicio = $("#txtHoraInicio").val().trim();
		var kminicio = $("#txtKmInicio").val();
		var nivelgasolinainicio = $("#cmbNivelGasolinaInicio").val();
		var comentarios = $("#txtComentarios").val();

		var fechafinal = $("#txtFechaFinal").val().trim();
		var horafinal = $("#txtHoraFinal").val().trim();
		var kmfinal = $("#txtKmFinal").val();
		var nivelgasolinafinal = $("#cmbNivelGasolinaFinal").val();

		axios.post("/controlunidad/guardarRegistroKilometraje", 
		{
			id,
			idunidad,
			fechainicio,
			horainicio,
			kminicio,
			nivelgasolinainicio,

			fechafinal,
			horafinal,
			kmfinal,
			nivelgasolinafinal,

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
		modal.find(".modal-title").text("Finalizar registro kilometraje");
		modal.find("input[id='txtId']").val(row.id);
		modal.find("select[id='cmbUnidad']").selectpicker('val', row.numeroeconomico).attr('disabled', true).trigger("change");
		modal.find("input[id='txtFechaInicio']").val(row.fechainicio).prop('readonly', true);
		modal.find("input[id='txtHoraInicio']").val(row.horainicio).prop('readonly', true);
		modal.find("input[id='txtKmInicio']").val(row.kminicio).prop('readonly', true);
		modal.find("select[id='cmbNivelGasolinaInicio']").val(row.nivelgasolinainicio).prop('disabled', true);
		modal.find("textarea[id='txtComentarios']").val(row.comentarios);

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
			ajax: {url: "/controlunidad/getRegistroKilometraje", dataSrc:""},
			//ordering: false,
			columns: [
				{ data: 'numeroeconomico', width: "1%", className: "dt-left" },
				{ data: 'modelo', width: "1%", className: "dt-left" },
				{ data: 'fechainicio', width: "10%", className: "dt-left" },
				{ data: 'kminicio', width: "10%", className: "dt-right" },
				{ data: 'nivelgasolinainiciofraccion', width: "15%", className: "dt-right" },
				{ data: null, width: "2%", className: "dt-center" },
			],
			"columnDefs": [
				{
					"targets": 5,
					"data" : "id",
					"defaultContent": 									
					"<a href='#' title='Ver o editar' class='showrow'><i class='bx bx-edit'></i></a>"
				},
				{ targets: 3, render: $.fn.dataTable.render.number(',', '.', 0, '') }
			]
		});
	}

</script>
@endsection