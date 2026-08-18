@extends('layouts.app')

@section('title', 'CONTROL UNIDADES')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
		
        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">CHECK LIST DE REVICION DE UNIDAD</h5>			

            <div class="card-body">

				<hr class="my-6 mx-n6" />

				@if($vista =='crear')
				<div class="row">
					<div class="col-md-12">
						<label for="cmbUnidad" class="form-label">Unidad:</label>
						<select id="cmbUnidad" class="selectpicker form-select" data-style="btn-default" data-live-search="true">
						</select><br/>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<label for="txtFechaElaboracion" class="form-label">Fecha elaboracion:</label>
						<input type="date" id="txtFechaElaboracion" class="form-control" value="{{ date('Y-m-d') }}" />
					</div>
					<div class="col-md-12">
						<label for="txtHoraElaboracion" class="form-label">Hora elaboracion:</label>
						<input type="time" id="txtHoraElaboracion" class="form-control" value="{{ date('H:i:s') }}" />
					</div>
				</div>
				@endif

				<div class="row">
					<div class="col-md-12">
						<br/>
						<table width="100%" class="table table-bordered table-sm">
							@if($vista =='ver')
							<tr>
								<td>Unidad:</td>
								<td>{{$info_checklist->unidad}}</td>
							</tr>
							<tr>
								<td>Fecha Elaboracón:</td>
								<td>{{$info_checklist->fechaelaboracion}}</td>
							</tr>
							<tr>
								<td>Hora Elaboración:</td>
								<td>{{$info_checklist->horaelaboracion}}</td>
							</tr>
							@endif
							<tr>
								<td>Marca:</td>
								<td id="lblMarca">{{$vista=="ver" ? $info_checklist->marca : ""}}</td>
							</tr>
							<tr>
								<td>Tipo:</td>
								<td id="lblTipo">{{$vista=="ver" ? $info_checklist->tipovehiculo : ""}}</td>
							</tr>
							<tr>
								<td>Modelo:</td>
								<td id="lblModelo">{{$vista=="ver" ? $info_checklist->modelo : ""}}</td>
							</tr>
							<tr>
								<td>Placas:</td>
								<td id="lblPlacas">{{$vista=="ver" ? $info_checklist->placas : ""}}</td>
							</tr>
							<tr>
								<td>Sucursal:</td>
								<td id="lblSucursal">{{$vista=="ver" ? $info_checklist->sucursal : ""}}</td>
							</tr>
							<tr>
								<td>Nombre:</td>
								<td id="lblNombre">{{$vista=="ver" ? $info_checklist->nombre : ""}}</td>
							</tr>
							<tr>
								<td>Puesto:</td>
								<td id="lblPuesto">{{$vista=="ver" ? $info_checklist->puesto : ""}}</td>
							</tr>
							<tr>
								<td>Departamento:</td>
								<td id="lblDepartamento">{{$vista=="ver" ? $info_checklist->departamento : ""}}</td>
							</tr>
						</table>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<br/>
						<label for="txtComentarios" class="form-label">Observaciones:</label>
						<textarea class="form-control" id="txtComentarios" rows="3">{{$vista=='ver' ? $info_checklist->observaciones : ''}}</textarea>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<br/>
						<table width="100%" class="cl table table-bordered table-sm">
							<thead>
								<tr style="background:#ffd5b7;">
									<th colspan="2">REVISIÓN FÍSICA EXTERIOR</th>
									<th>Observaciones</th>
								</tr>
							</thead>

							<tbody>
								@foreach ($rfe as $i)
								<tr>
									<td>{{$i->descripcion}}</td>
									<td align="center">
										@if($vista =='crear')
											<input class="form-check-input" style="transform: scale(2.0);" type="checkbox" id="check{{$i->id}}" />
										@else
											@if($i->respuesta == 1)
												<i class='bx  bx-check'  style='color:#07a21d'></i>
											@else
												<i class='bx  bx-x'  style='color:#f41717'></i> 
											@endif
										@endif
									</td>
									<td>
										@if($vista =='crear')
											<textarea class="form-control" id="txtComentarios{{$i->id}}" rows="1"></textarea>
										@else
											{{$i->comentarios}}
										@endif
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
					<div class="col-md-12">
						<br/>
						<table width="100%" class="cl table table-bordered table-sm">
							<tr style="background:#ffd5b7;">
								<th colspan="2">REVISIÓN FÍSICA INTERIOR</th>
								<th>Observaciones</th>
							</tr>

							@foreach ($rfi as $i)
							<tr>
								<td>{{$i->descripcion}}</td>
								<td align="center">
									@if($vista =='crear')
										<input class="form-check-input" style="transform: scale(2.0);" type="checkbox" id="check{{$i->id}}" />
									@else
										@if($i->respuesta == 1)
											<i class='bx  bx-check'  style='color:#07a21d'></i>
										@else
											<i class='bx  bx-x'  style='color:#f41717'></i> 
										@endif
									@endif
								</td>
								<td>
									@if($vista =='crear')
										<textarea class="form-control" id="txtComentarios{{$i->id}}" rows="1"></textarea>
									@else
										{{$i->comentarios}}
									@endif
								</td>
							</tr>
							@endforeach
						</table>
					</div>
					<div class="col-md-12">
						<br/>
						<table width="100%" class="cl table table-bordered table-sm">
							<tr style="background:#ffd5b7;">
								<th colspan="2">REVISIÓN MECANICA BASICA</th>
								<th>Observaciones</th>
							</tr>

							@foreach ($rmb as $i)
							<tr>
								<td>{{$i->descripcion}}</td>
								<td align="center">
									@if($vista =='crear')
										<input class="form-check-input" style="transform: scale(2.0);" type="checkbox" id="check{{$i->id}}" />
									@else
										@if($i->respuesta == 1)
											<i class='bx  bx-check'  style='color:#07a21d'></i>
										@else
											<i class='bx  bx-x'  style='color:#f41717'></i> 
										@endif
									@endif
								</td>
								<td>
									@if($vista =='crear')
										<textarea class="form-control" id="txtComentarios{{$i->id}}" rows="1"></textarea>
									@else
										{{$i->comentarios}}
									@endif
								</td>
							</tr>
							@endforeach
						</table>
					</div>
					<div class="col-md-12">
						<br/>
						<table width="100%" class="cl table table-bordered table-sm">
							<tr style="background:#ffd5b7;">
								<th colspan="2">DOCUMENTACION</th>
								<th>Observaciones</th>
							</tr>

							@foreach ($documentacion as $i)
							<tr>
								<td>{{$i->descripcion}}</td>
								<td align="center">
									@if($vista =='crear')
										<input class="form-check-input" style="transform: scale(2.0);" type="checkbox" id="check{{$i->id}}" />
									@else
										@if($i->respuesta == 1)
											<i class='bx  bx-check'  style='color:#07a21d'></i>
										@else
											<i class='bx  bx-x'  style='color:#f41717'></i> 
										@endif
									@endif
								</td>
								<td>
									@if($vista =='crear')
										<textarea class="form-control" id="txtComentarios{{$i->id}}" rows="1"></textarea>
									@else
										{{$i->comentarios}}
									@endif
								</td>
							</tr>
							@endforeach
						</table>
					</div>
					<div class="col-md-12">
						<br/>
						<table width="100%" class="cl table table-bordered table-sm">
							<tr style="background:#ffd5b7;">
								<th colspan="2">HERRAMIENTAS BASICAS</th>
								<th>Observaciones</th>
							</tr>

							@foreach ($hb as $i)
							<tr>
								<td>{{$i->descripcion}}</td>
								<td align="center">
									@if($vista =='crear')
										<input class="form-check-input" style="transform: scale(2.0);" type="checkbox" id="check{{$i->id}}" />
									@else
										@if($i->respuesta == 1)
											<i class='bx  bx-check'  style='color:#07a21d'></i>
										@else
											<i class='bx  bx-x'  style='color:#f41717'></i> 
										@endif
									@endif
								</td>
								<td>
									@if($vista =='crear')
										<textarea class="form-control" id="txtComentarios{{$i->id}}" rows="1"></textarea>
									@else
										{{$i->comentarios}}
									@endif
								</td>
							</tr>
							@endforeach
						</table>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<br/>
						<label for="txtNotas" class="form-label">Notas:</label>
						<textarea class="form-control" id="txtNotas" rows="3">{{$vista=='ver' ? $info_checklist->notas : ''}}</textarea>
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

	cargarUnidadesCombobox($("#cmbUnidad"));

	$("#cmbUnidad").on("change", function()
	{
		var idunidad = $(this).val();

		axios.get("/controlunidad/getAsignacionByIdUnidad/" + idunidad)
	    .then(function(res) 
		{
			var info = res["data"][0];
			$("#lblMarca").text(info.marca);
			$("#lblTipo").text(info.tipovehiculo);
			$("#lblModelo").text(info.modelo);
			$("#lblPlacas").text(info.placas);
			$("#lblSucursal").text(info.sucursal);
			$("#lblNombre").text(info.empleado);
			$("#lblPuesto").text(info.puesto);
			$("#lblDepartamento").text(info.departamento);
	    })
	    .catch(function(err) 
		{
            alert(err)
	    });
	});

	$("#btnGuardar").on("click", function()
	{
		var idunidad = $("#cmbUnidad").val();
		var fechaelaboracion = $("#txtFechaElaboracion").val();
		var horaelaboracion = $("#txtHoraElaboracion").val();
		var placas = $("#lblPlacas").text();
		var sucursal = $("#lblSucursal").text();
		var nombre = $("#lblNombre").text();
		var puesto = $("#lblPuesto").text();
		var departamento = $("#lblDepartamento").text();
		var observaciones = $("#txtComentarios").val();
		var notas = $("#txtNotas").val();

		if(idunidad == 0)
		{
			alert("Favor de seleccionar una unidad");
			return;
		}

		var checklist = [];

		$('.cl > tbody  > tr').each(function(index, tr) {
			var check = $(tr).find("input[type='checkbox']");
			var comentarios = $(tr).find("textarea");
			var id = check.attr('id');

			if(id!=undefined)
			{
				var item = {};
				item["idchecklist"] = id.replace(/[^0-9]/g, '');
				item["respuesta"] = check.is(":checked") ? 1 : 0;
				item["comentarios"] = comentarios.val();

				checklist.push(item);
			}
		});

		axios.post("/controlunidad/guardarRegistroCheckList", 
		{
			idunidad,
			fechaelaboracion,
			horaelaboracion,
			placas,
			sucursal,
			nombre,
			puesto,
			departamento,
			observaciones,
			notas,
			checklist
		})
	    .then(function(res) 
		{
			if(parseFloat(res.data) > 0)
			{
				location.href = "{{ route('checklist_view') }}";
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

	function seleccionar(id)
	{
	}

</script>
@endsection