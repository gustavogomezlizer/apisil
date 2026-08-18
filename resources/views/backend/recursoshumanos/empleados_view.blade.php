@extends('layouts.app')

@section('title', 'RECURSOS HUMANOS')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
		
        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">EMPLEADOS</h5>			

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
                                <th>CODIGO EMPLEADO</th>
                                <th>NOMBRE</th>
                                <th>DEPARTAMENTO</th>
								<th>NEGOCIO</th>
								<th>SUCURSAL</th>
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
						<h5 class="modal-title" id="exampleModalLabel3">Nueva Empleado</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>

					<div class="modal-body">
						<div class="row">
							<div class="col-md-4">
								<label for="txtCodigoEmpleado" class="form-label">*Codigo de empleado:</label>
								<input type="hidden" id="txtId" value="0" />
								<input type="text" id="txtCodigoEmpleado" class="form-control" placeholder="Codigo de Empleado" />
							</div>
							<div class="col-md-8">
								<label for="txtNombre" class="form-label">*Nombre:</label>
								<input type="text" id="txtNombre" class="form-control" placeholder="Nombre" />
							</div>
						</div>

						<br/>
						<div class="row">
							<div class="col-md-3">
								<label for="cmbDepartamento" class="form-label">Departamento:</label>
								<select id="cmbDepartamento" class="form-select">
								</select>
							</div>
							<div class="col-md-3">
								<label for="cmbPuesto" class="form-label">Puesto:</label>
								<select id="cmbPuesto" class="form-select">
								</select>
							</div>
							<div class="col-md-3">
								<label for="cmbNegocio" class="form-label">Negocio:</label>
								<select id="cmbNegocio" class="selectpicker form-control" data-style="btn-default" data-icon-base="icon-base bx" data-tick-icon="bx-check text-primary" multiple>
								</select>
							</div>
							<div class="col-md-3">								
								<label for="cmbSucursal" class="form-label">Sucursal:</label>
								<select id="cmbSucursal" class="selectpicker form-select" data-style="btn-default" data-icon-base="icon-base bx" data-tick-icon="bx-check text-primary" multiple>
								</select>
							</div>
						</div>

						<br/>
						<div class="row">
							<div class="col-md-3">
								<label for="txtFechaIngreso" class="form-label">Fecha Ingreso:</label>
								<input type="date" id="txtFechaIngreso" class="form-control" placeholder="Fecha Ingreso" />
							</div>
							<div class="col-md-3">
								<label for="txtFechaNacimiento" class="form-label">Fecha Nacimiento:</label>
								<input type="date" id="txtFechaNacimiento" class="form-control" placeholder="Fecha Nacimiento" />
							</div>
							<div class="col-md-3">
								<label for="txtNss" class="form-label">N.S.S:</label>
								<input type="text" id="txtNss" class="form-control" placeholder="N.S.S" />
							</div>
							<div class="col-md-3">
								<label for="txtRfc" class="form-label">RFC:</label>
								<input type="text" id="txtRfc" class="form-control" placeholder="RFC" />
							</div>
						</div>

						<br/>
						<div class="row">
							<div class="col-md-3">
								<label for="txtCurp" class="form-label">CURP:</label>
								<input type="text" id="txtCurp" class="form-control" placeholder="CURP" />
							</div>
							<div class="col-md-5">
								<label for="txtCalle" class="form-label">Calle:</label>
								<input type="text" id="txtCalle" class="form-control" placeholder="Calle" />
							</div>
							<div class="col-md-2">
								<label for="txtNumExt" class="form-label"># Ext:</label>
								<input type="text" id="txtNumExt" class="form-control" placeholder="# Ext" />
							</div>
							<div class="col-md-2">
								<label for="txtNumInt" class="form-label"># Int:</label>
								<input type="text" id="txtNumInt" class="form-control" placeholder="# Int" />
							</div>
						</div>

						<br/>
						<div class="row">
							<div class="col-md-4">
								<label for="txtColonia" class="form-label">Colonia:</label>
								<input type="text" id="txtColonia" class="form-control" placeholder="Colonia" />
							</div>
							<div class="col-md-2">
								<label for="txtCp" class="form-label">CP:</label>
								<input type="text" id="txtCp" class="form-control" placeholder="CP" />
							</div>
							<div class="col-md-3">
								<label for="txtCiudad" class="form-label">Ciudad:</label>
								<input type="text" id="txtCiudad" class="form-control" placeholder="Ciudad" />
							</div>
							<div class="col-md-3">
								<label for="txtEstado" class="form-label">Estado:</label>
								<input type="text" id="txtEstado" class="form-control" placeholder="Estado" />
							</div>
						</div>

						<br/>
						<div class="row">
							<div class="col-md-3">
								<label for="txtTelefono" class="form-label">Telefono:</label>
								<input type="text" id="txtTelefono" class="form-control" placeholder="Telefono" />
							</div>
							<div class="col-md-3">
								<label for="cmbEstadoCivil" class="form-label">Estado Civil:</label>
								<select id="cmbEstadoCivil" class="form-select">
								</select>
							</div>
							<div class="col-md-4">
								<label for="txtLugarNacimiento" class="form-label">Lugar Nacimiento:</label>
								<input type="text" id="txtLugarNacimiento" class="form-control" placeholder="Lugar Nacimiento" />
							</div>
							<div class="col-md-2">
								<label for="txtTipoSangre" class="form-label">Tipo Sangre:</label>
								<input type="text" id="txtTipoSangre" class="form-control" placeholder="Tipo Sangre" />
							</div>
						</div>

						<br/>
						<div class="row">
							<div class="col-md-6">
								<label for="txtContactoEmergencia" class="form-label">Contacto de Emergencia:</label>
								<input type="text" id="txtContactoEmergencia" class="form-control" placeholder="Contacto de Emergencia" />
							</div>
							<div class="col-md-3">
								<label for="txtParentesco" class="form-label">Parentesco:</label>
								<input type="text" id="txtParentesco" class="form-control" placeholder="Parentesco" />
							</div>
							<div class="col-md-3">
								<label for="txtTelefonoContactoEmergencia" class="form-label">Telefono:</label>
								<input type="text" id="txtTelefonoContactoEmergencia" class="form-control" placeholder="Telefono" />
							</div>
						</div>

						<br/>
						<div class="row">
							<div class="col-md-4">
								<label for="cmbTipoLicencia" class="form-label">Tipo Licencia:</label>
								<select id="cmbTipoLicencia" class="form-select">
								</select>
							</div>
							<div class="col-md-2">
								<label for="txtFechaVencimientoLicencia" class="form-label">Vencimiento Licencia:</label>
								<input type="date" id="txtFechaVencimientoLicencia" class="form-control" placeholder="Vencimiento Licencia" />
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

	cargarNegociosCombobox($("#cmbNegocio"));
	cargarDepartamentosCombobox($("#cmbDepartamento"));
	cargarSucursalesCombobox($("#cmbSucursal"));
	cargarPuestosCombobox($("#cmbPuesto"));
	cargarEstadoCivilCombobox($("#cmbEstadoCivil"));
	cargarTiposLicenciaCombobox($("#cmbTipoLicencia"));

	cargarTabla()

	$("#cmbMarca").on("change", function()
	{
		cargarModelosCombobox($("#cmbModelo"), $(this).val());
	});

	$('#modal_nueva_unidad').on('show.bs.modal', function (event) 
	{
	})

	$('#modal_nueva_unidad').on('hidden.bs.modal', function (e) 
	{
  		$(this).find("input,textarea,select").val('').end();
		$(this).find("select").val('0').end();
    	$(this).find("input[type=checkbox], input[type=radio]").prop("checked", "").end();

		$("#cmbNegocio").selectpicker('deselectAll');
		$("#cmbSucursal").selectpicker('deselectAll');

		$("#lblMensaje").hide();
	});

	$("#btnGuardar").on("click", function()
	{
		$("#lblMensaje").hide();

		var id = $("#txtId").val();
		var codigoempleado = $("#txtCodigoEmpleado").val().trim();
		var nombre = $("#txtNombre").val().trim();
		var iddepartamento = $("#cmbDepartamento").val();
		var idpuesto = $("#cmbPuesto").val();
		var idnegocio = $("#cmbNegocio").val().toString();
		var idsucursal = $("#cmbSucursal").val().toString();
		var fechaingreso = $("#txtFechaIngreso").val();
		var fechanacimiento = $("#txtFechaNacimiento").val();
		var nss = $("#txtNss").val().trim();
		var rfc = $("#txtRfc").val().trim();
		var curp = $("#txtCurp").val().trim();
		var calle = $("#txtCalle").val().trim();
		var numext = $("#txtNumExt").val().trim();
		var numint = $("#txtNumInt").val().trim();
		var colonia = $("#txtColonia").val().trim();
		var cp = $("#txtCp").val().trim();
		var ciudad = $("#txtCiudad").val().trim();
		var estado = $("#txtEstado").val().trim();
		var telefono = $("#txtTelefono").val().trim();
		var idestadocivil = $("#cmbEstadoCivil").val();
		var lugarnacimiento = $("#txtLugarNacimiento").val();
		var tiposangre = $("#txtTipoSangre").val().trim();
		var contactoemergencia = $("#txtContactoEmergencia").val().trim();
		var parentesco = $("#txtParentesco").val().trim();
		var telefonocontactoemergencia = $("#txtTelefonoContactoEmergencia").val().trim();
		var idtipolicencia = $("#cmbTipoLicencia").val();
		var fechavencimientolicencia = $("#txtFechaVencimientoLicencia").val();

		axios.post("/recursoshumanos/guardarEmpleado", 
		{
			id,
			codigoempleado,
			nombre,
			iddepartamento,
			idpuesto,
			idnegocio,
			idsucursal,
			fechaingreso,
			fechanacimiento,
			nss,
			rfc,
			curp,
			calle,
			numext,
			numint,
			colonia,
			cp,
			ciudad,
			estado,
			telefono,
			idestadocivil,
			lugarnacimiento,
			tiposangre,
			contactoemergencia,
			parentesco,
			telefonocontactoemergencia,
			idtipolicencia,
			fechavencimientolicencia
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
		modal.find("input[id='txtCodigoEmpleado']").val(row.codigoempleado);
		modal.find("input[id='txtNombre']").val(row.nombre);
		modal.find("select[id='cmbDepartamento']").val(row.iddepartamento);
		modal.find("select[id='cmbPuesto']").val(row.idpuesto);
		modal.find("select[id='cmbNegocio']").selectpicker('val', (row.idnegocio == "" || row.idnegocio == null) ? '' : (row.idnegocio).split(","));
		modal.find("select[id='cmbSucursal']").selectpicker('val', (row.idsucursal == "" || row.idsucursal == null) ? '' : (row.idsucursal).split(","));
		modal.find("input[id='txtFechaIngreso']").val(row.fechaingreso);
		modal.find("input[id='txtFechaNacimiento']").val(row.fechanacimiento);
		modal.find("input[id='txtNss']").val(row.nss);
		modal.find("input[id='txtRfc']").val(row.rfc);
		modal.find("input[id='txtCurp']").val(row.curp);
		modal.find("input[id='txtCalle']").val(row.calle);
		modal.find("input[id='txtNumExt']").val(row.numext);
		modal.find("input[id='txtNumInt']").val(row.numint);
		modal.find("input[id='txtColonia']").val(row.colonia);
		modal.find("input[id='txtCp']").val(row.cp);
		modal.find("input[id='txtCiudad']").val(row.ciudad);
		modal.find("input[id='txtEstado']").val(row.estado);
		modal.find("input[id='txtTelefono']").val(row.telefono);
		modal.find("input[id='cmbEstadoCivil']").val(row.idestadocivil);
		modal.find("input[id='txtLugarNacimiento']").val(row.lugarnacimiento);
		modal.find("input[id='txtTipoSangre']").val(row.tiposangre);
		modal.find("input[id='txtContactoEmergencia']").val(row.contactoemergencia);
		modal.find("input[id='txtParentesco']").val(row.parentesco);
		modal.find("input[id='txtTelefonoContactoEmergencia']").val(row.telefonocontactoemergencia);
		modal.find("input[id='cmbTipoLicencia']").val(row.idtipolicencia);
		modal.find("input[id='txtFechaVencimientoLicencia']").val(row.fechavencimientolicencia);

		modal.modal('show');
	});

    function cargarTabla()
	{
		table = $('#table').DataTable( {
			destroy: true,
			pageLength: 50,
			autoWidth: true,
			ajax: {url: "/recursoshumanos/getEmpleados", dataSrc:""},
			//ordering: false,
			columns: [
				{ data: 'codigoempleado', width: "1%", className: "dt-left" },
				{ data: 'nombre', width: "30%", className: "dt-left" },
				{ data: 'departamento', width: "10%", className: "dt-left" },
				{ data: 'negocio', width: "15%", className: "dt-left" },
				{ data: 'sucursal', width: "10%", className: "dt-right" },
				{ data: null, width: "2%", className: "dt-center" },
			],
			"columnDefs": [
				{
					"targets": 5,
					"data" : "id",
					"defaultContent": 									
					"<a href='#' title='Ver o editar' class='showrow'><i class='bx bx-edit'></i></a>"
				}
			]
		});
	}

</script>
@endsection