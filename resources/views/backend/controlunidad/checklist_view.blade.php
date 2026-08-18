@extends('layouts.app')

@section('title', 'CONTROL UNIDADES')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">
		
        <!-- Bordered Table -->
        <div class="card">
            <h5 class="card-header">CHECK LIST DE UNIDADES</h5>

            <div class="card-body">

				<div class="demo-inline-spacing" align="right">
					<a href="{{ route('checklistnew_view', ['id' => 0]) }}" type="button" class="btn btn-success">
						<span class="tf-icons bx bx-message-square-add bx-18px me-2"></span>Nuevo
					</a>
				</div>

				<hr class="my-6 mx-n6" />

				<div class="table-responsive text-nowrap">
                    <table id="table" class="table table-striped table-sm table-hover">

                        <thead class="table-dark">
							<tr>
                                <th>&nbsp;</th>
                                <th colspan="2">ELABORACION</th>
								<th>&nbsp;</th>
								<th>&nbsp;</th>
                            </tr>
                            <tr>
                                <th>UNIDAD</th>
                                <th>FECHA</th>
								<th>HORA</th>
								<th>INCONSISTENCIAS</th>
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

	$('#table tbody').on( 'click', 'a.showrow', function () {
		var row = table.row( $(this).parents('tr') ).data();

		var url = "{{ route('checklistnew_view', ':id') }}";
		url = url.replace(':id', row.id);
		
		location.href = url;
	});

	cargarTabla();

	function cargarTabla()
	{
		table = $('#table').DataTable( {
			destroy: true,
			pageLength: 50,
			autoWidth: true,
			ajax: {url: "/controlunidad/getRegistrosCheckList", dataSrc:""},
			ordering: false,
			columns: [
				{ data: 'unidad', width: "5%", className: "dt-left" },
				{ data: 'fechaelaboracion', width: "10%", className: "dt-left" },
				{ data: 'horaelaboracion', width: "10%", className: "dt-left" },
				{ data: 'incosistencias', width: "15%", className: "dt-right" },
				{ data: null, width: "15%", className: "dt-center" },
			],
			"columnDefs": [
				{
					"targets": 4,
					"data" : "id",
					"defaultContent": 									
					"<a href='#' title='Ver' class='showrow btn btn-icon btn-outline-primary'><i class='bx bx-show'></i></a>"
				}
			]
		});
	}

</script>
@endsection