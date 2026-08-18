@extends('layouts.app')

@section('title', 'CONTROL UNIDADES')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
		
        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">ASIGNACION</h5>			

            <div class="card-body">

				<hr class="my-6 mx-n6" />

                <div class="table-responsive text-nowrap">
                    <table id="table" class="table table-striped table-sm table-hover">

                        <thead class="table-dark">
                            <tr>
                                <th>NUMERO ECONOMICO</th>
								<th>MODELO</th>
								<th>SUCURSAL</th>
								<th>EMPLEADO</th>
								<th>RUTA</th>
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

	var listasucursales = getSucursales();
	var listaempleados = getEmpleados();
	var listarutas = getRutas();
	
	cargarTabla();

	$('#table tbody').on( 'change', 'select.selectsucursales', function () {
		var row = table.row( $(this).parents('tr') ).data();

		var idsucursal = $(this).val();

		var selectempleados = $(this).parents('tr').children().children(".selectempleados");
		selectempleados.empty();
        selectempleados.append("<option value='0'>[SELECCIONE UN EMPLEADO]</option>");

		listaempleados.forEach(function (item) 
		{
			var sucursal = (item.idsucursal).split(",");

			if(sucursal.includes(idsucursal))
			{
				var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
				selectempleados.append(option);
			}
		});

		var selectrutas = $(this).parents('tr').children().children(".selectrutas");
		selectrutas.empty();
        selectrutas.append("<option value='0'>[SELECCIONE UNA RUTA]</option>");
		//selectrutas.append("<option value='9999'>SIN RUTA</option>");

		listarutas.forEach(function (item) 
		{
			var sucursal = (item.idsucursal).split(",");

			if(sucursal.includes(idsucursal) || item.id.toString() == "9999")
			{
				var option = "<option value='" + item.id + "'>" + item.nombre + "</option>";
				selectrutas.append(option);
			}
		});
	});

	/*$('#table tbody').on( 'change', 'select.selectempleados', function () {
		var row = table.row( $(this).parents('tr') ).data();
		
		var selectsucursales = $(this).parents('tr').children().children(".selectsucursales");
		var idvehiculo = row.id;
		var idsucursal = selectsucursales.val();
		var idempleado = $(this).val();
	});*/

	$('#table tbody').on( 'change', 'select.selectrutas', function () {
		var row = table.row( $(this).parents('tr') ).data();
		
		var selectsucursales = $(this).parents('tr').children().children(".selectsucursales");
		var selectempleados = $(this).parents('tr').children().children(".selectempleados");

		var idvehiculo = row.id;
		var idsucursal = selectsucursales.val();
		var idempleado = selectempleados.val();
		var idruta = $(this).val();

		guardarAsignacion(0, idvehiculo, idsucursal, idempleado, idruta);
	});

	function guardarAsignacion(pTipo, pIdVehiculo, pIdSucursal, pIdEmpleado, pIdRuta)
	{
		var idvehiculo = pIdVehiculo;
		var idsucursal = pIdSucursal;
		var idempleado = pIdEmpleado;
		var idruta = pIdRuta;

		axios.post("/controlunidad/guardarAsignacion", 
		{
			idvehiculo,
			idsucursal,
			idempleado,
			idruta
		})
	    .then(function(res) 
		{
			/*if(parseFloat(res.data) > 0)
			{
				$('#modal_nueva_unidad').modal('hide');
				cargarTabla();
			}
			else
			{
				$("#lblMensaje").html(res.data);
				$("#lblMensaje").show();
			}*/
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
	}

	function getSucursales()
	{
		var sucursales = [];

		<?php foreach($sucursales as $sucursal) { ?>
			var item = {};
			item["id"] = "<?php echo $sucursal->id; ?>";
			item["nombre"] = "<?php echo $sucursal->sucursal; ?>";

			sucursales.push(item);
		<?php } ?>

		return sucursales;
	}

	function getEmpleados()
	{
		var sucursales = [];

		<?php foreach($empleados as $sucursal) { ?>
			var item = {};
			item["id"] = "<?php echo $sucursal->id; ?>";
			item["nombre"] = "<?php echo $sucursal->nombre; ?>";
			item["idsucursal"] = "<?php echo $sucursal->idsucursal; ?>";

			sucursales.push(item);
		<?php } ?>

		return sucursales;
	}

	function getRutas()
	{
		var rutas = [];

		var item = {};
		item["id"] = "9999";
		item["nombre"] = "SIN RUTA";
		item["idsucursal"] = "0";
		rutas.push(item);

		<?php foreach($rutas as $ruta) { ?>
			var item = {};
			item["id"] = "<?php echo $ruta->id; ?>";
			item["nombre"] = "<?php echo $ruta->ruta; ?>";
			item["idsucursal"] = "<?php echo $ruta->sucursal; ?>";

			rutas.push(item);
		<?php } ?>

		return rutas;
	}

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
				{ data: 'modelo', width: "15%", className: "dt-left" },
				{ 
					render: function(data, type, row, meta) 
					{
						var select = "<select id='cmb1' class='selectsucursales form-select'>";
						select = select + "<option value='0'>[SELECCIONE UNA SUCURSAL]</option>";
						for(var i in listasucursales)
						{
							select = select + "<option value='" + listasucursales[i].id + "'" + (row.idsucursal == listasucursales[i].id ? "selected" : "") + ">" + listasucursales[i].nombre + "</option>";
						}
						select = select + "</select>";
						return (select)
					}, 
					width: "15%", 
					className: "dt-left"
				},
				{ 
					render: function(data, type, row, meta) 
					{
						var select = "<select class='selectempleados form-select'>";
						select = select + "<option value='0'>[SELECCIONE UN EMPLEADO]</option>";
						if(row.idsucursal != null)
						{
							var idsucursal = row.idsucursal.toString();

							listaempleados.forEach(function (item) 
							{
								var sucursal = (item.idsucursal).split(",");
								var idempleado = row.idempleado.toString();

								if(sucursal.includes(idsucursal))
								{
									select = select + "<option value='" + item.id + "'" + (idempleado == item.id ? "selected" : "") + ">" + item.nombre + "</option>";
								}
							});
						}
						select = select + "</select>";
						return (select)
					}, 
					width: "15%", 
					className: "dt-left"
				},
				{ 
					render: function(data, type, row, meta) 
					{
						var select = "<select class='selectrutas form-select'>";
						select = select + "<option value='0'>[SELECCIONE UNA RUTA]</option>";
						//select = select + "<option value='9999'>SIN RUTA</option>";
						if(row.idsucursal != null)
						{
							var idsucursal = row.idsucursal.toString();

							listarutas.forEach(function (item) 
							{
								var sucursal = (item.idsucursal).split(",");
								var idruta = row.idruta.toString();

								if(sucursal.includes(idsucursal) || item.id.toString() == "9999")
								{
									select = select + "<option value='" + item.id + "'" + (idruta == item.id ? "selected" : "") + ">" + item.nombre + "</option>";
								}
							});
						}
						select = select + "</select>";
						return (select)
					}, 
					width: "15%", 
					className: "dt-left"
				},
			],
			/*"columnDefs": [
				{
					"targets": 5,
					"data" : "id",
					"defaultContent": 									
					"<a href='#' title='Ver o editar' class='showrow'><i class='bx bx-edit'></i></a>"
				}
			]*/
		});
	}

</script>
@endsection