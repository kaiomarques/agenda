@extends('...layouts.master')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">
   							 
	<!--span>Prezado usuário, selecione a atividade a qual se refere a entrega:</span><br/><br/-->
	<table class="table table-bordered display" id="entregas-table">
		<thead>
			<tr>
				<td colspan="10">
					<div class="form-group">

						<div class="col-xs-10  pull-left">

							<input style="width: 100px; position:relative; left:10px; text-align:center" class="codigo" placeholder="Código" type="text" id="src_codigo" maxlength="5" name="src_codigo" value="<?= $filter_codigo ?>">
							<input style="width: 145px; position:relative; left:10px; text-align:center" placeholder="CNPJ" type="text" id="src_cnpj" name="src_cnpj" value="<?= $filter_cnpj ?>">
							<input style="width: 145px; position:relative; left:10px; text-align:center" placeholder="TRIBUTO" type="text" id="src_tributo" name="src_tributo" value="<?= $filter_tributo ?>">
							<input style="width: 45px; position:relative; left:10px; text-align:center; text-transform: uppercase;" placeholder="UF" type="text" id="src_uf" name="src_uf" value="<?= $filter_uf ?>">
							<button id="adv_search" style="position:relative; left:10px;">BUSCAR</button>
							<!--<div id="spinner" class="pull-right" style="top:-5px;left:-50px">
								<img  title="Processando... " src={{asset('assets/img/loading_32.gif')}} alt="Logo">
								&nbsp;&nbsp;&nbsp;Carregando Lista...
							</div>-->	
							<div class="btn-group pull-left bt-download-principal hidden" role="group" aria-label="Button group with nested dropdown"  >
							  <div class="btn-group bt-download" role="group">
							    <button id="btnGroupDrop1" style="height: 26px" type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							      <div style="margin-top: -4%" id="div-text-download">Download CSV<div>
							    </button>
							    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1" >
							     &nbsp;&nbsp;&nbsp;&nbsp; <i class="fa fa-folder"></i><a  class="dropdown-item downloadcsv" href="#" target="_blank">   Download CSV</a>
							      <!--<i class="fa fa-folder"></i><a class="dropdown-item" href="#"> Download PDF</a>-->
							    </div>
							  </div>
							</div>

						</div>
						<div class="col-xs-3 selectContainer pull-right">

							<select class="form-control" id="src_status" name="src_status">

								<option <?= $filter_status=='T'?'selected':'' ?> value="T">Todas as entregas em aberto</option>
								<option <?= $filter_status=='A'?'selected':'' ?> value="A">Entregas em aprovação</option>
								<option <?= $filter_status=='E'?'selected':'' ?> value="E">Entregas não efetuadas</option>
							</select>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<th>ID</th>
				<th>DESCRIÇÃO</th>
				<th>TRIBUTO</th>
				<!--th>REF</th-->
				<th>P.A.</th>
				@if ( Auth::user()->hasRole('analyst'))
				<th>VENCIMENTO</th>
				@else
				<th>ENTREGA</th>
				@endif
				<th>F.P.</th>
				<th>CNPJ</th>
				<th>COD</th>
				<th>UF</th>
				<th>STATUS</th>
			</tr>
		</thead>
	</table>
<script>
var file;
	$(function() {

		$('.bt-download').children().prop('disabled',true);

	    $('#entregas-table').DataTable({
	        processing: true,
	        serverSide: true,
	        stateSave: true,
	        paginate:true,
	        paging: true,
	        filter:true,

	        ajax: {
				url: "{!! route('entregas.data') !!}",
				data: function (d) {
					d.codigo        = $('#src_codigo').val();
					d.cnpj          = $('#src_cnpj').val();
					d.uf            = $('#src_uf').val();
					d.status_filter = $('#src_status option:selected').val();
					d.tributo       = $('#src_tributo').val();
					
				}
			},
	        columnDefs: [{ "width": "22%", "targets": 1 },{ "width": "120px", "targets": 2 },{ "width": "150px", "targets": 6 }],
	        columns: [
	            {
					data: 'id',
					name: 'atividades.id'
				},
	            {
					data: 'descricao',
					name: 'atividades.descricao'
				},
	            {
					data: 'regra.tributo.nome',
					name: 'regra.tributo.nome',
					searchable: false,
					orderable: false
				},
	            // {
				// 	data: 'regra.ref',
				// 	name: 'regra.ref',
				// 	orderable: false
				// },
	            {
					data: 'periodo_apuracao',
					name: 'atividades.periodo_apuracao'
				},
	            @if (Auth::user()->hasRole('analyst'))
	            {
					data: 'limite',
					name: 'atividades.limite',
					render: function (data) {
	
						return data.substring(8,10)+'-'+data.substring(5,7)+'-'+data.substring(0,4);
					}
				},
	            @else
	            {
					data: 'data_entrega',
					name: 'atividades.data_entrega',
					render: function (data) {

						
						if (data=='0000-00-00 00:00:00') {
							return '-';
						} else {
							return data.substring(8,10)+'-'+data.substring(5,7)+'-'+data.substring(0,4);
						}
					}
				},
	            @endif
	            {
					data: 'id',
					name: 'atraso',
					searchable: false,
					orderable: false,
					render: function (data, type, row) {



						var date1 = new Date(row['limite']);
						var date2 = new Date(row['data_entrega']);
						var timeDiff = (date2.getTime() - date1.getTime());
						var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));
						var retval = "-";
						if (diffDays>1) {
							retval = diffDays+' dias';
						}
						return retval;
					}
	            },
	            {
					data: 'estemp.cnpj',
					name: 'estemp.cnpj',
					searchable: false,
					orderable: false,
					render: function (data) {
						console.log(data);
						if (data != undefined) {
							
							return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
						} else {
							return '-';
						}
					}
				},
	            {
					data: 'estemp.codigo',
					name: 'estemp.codigo',
					searchable: false,
					orderable: false,
					render: function (data) {
						if (data == undefined) {
							return '-';
						} else {
							return data;
						}
					}
				},
				{
					data: 'uf',
					name: 'uf',
					searchable: false,
					orderable: false,
					render: function (data) {
						if (data == undefined) {
							return '-';
						} else {
							return data;
						}
					}
				},
	            {
					data: 'id',
					name:'edit',
					searchable: false,
					orderable: false,
					render: function (data, type, row) {
						var url = '';
					

						switch(row['status']) {
							case 1:
								if (row['tipo_geracao'] == 'R') {
									url = '<a href="{{ route('upload.entrega', ':id_atividade') }}" style="margin-left:10px" class="btn btn-default btn-sm">Entregar</a>';
									url = url.replace(':id_atividade', data);
								} else {
									url = '<a href="{{ route('entrega.comentarios', ':id_atividade') }}" style="margin-left:10px" class="btn btn-info btn-sm"><i class="fa fa-comments-o" aria-hidden="true"></i> Comentário</a>';
									url = url.replace(':id_atividade', data);
								}
								break;

							case 2:
								@if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor'))
								url = '<a href="{{ route('entregas.show', ':id_atividade') }}" style="margin-left:10px" class="btn btn-danger btn-default btn-sm"><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> Em aprovação</a>';
								url = url.replace(':id_atividade', data);
								@else
								url = '<span style="margin-left:10px; cursor:not-allowed" class="btn btn-danger btn-default btn-sm"><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> Em aprovação</span>';
								@endif
								break;
							case 3:
								if (row['cliente_aprova'] == 'S') {
									url = '<a href="{{ route('entregas.show', ':id_atividade') }}" style="margin-left:10px" class="btn btn-danger btn-default btn-sm"><i class="fa fa-file-text-o" aria-hidden="true"></i> Em aprovação</a>';
									url = url.replace(':id_atividade', data);
									break;
								} else {
									url = '<a href="{{ route('entregas.show', ':id_atividade') }}" style="margin-left:10px" class="btn btn-success btn-default btn-sm"><i class="fa fa-file-text-o" aria-hidden="true"></i> Recibo</a>';
									url = url.replace(':id_atividade', data);
									break;
								}
						}

						  

						  if(file!="")
						  	file = row["file"];

						//$("#spinner").addClass("hidden");	
						$(".bt-download-principal").removeClass("hidden");


						return url;
						
					}
				}
	        ],

	        order: [[ 4, "asc" ]],
	        language: {
				"searchPlaceholder": "ID, P.A. ou descrição",
				"sSearch": "Pesquisar",	
				// "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json",
				// "processing": "<img src='/assets/img/loading_16.gif' />",
	        },
	        aLengthMenu: [
				[10, 25, 50, 100, 3000],
				[10, 25, 50, 100, 3000]
			],
			iDisplayLength: 10,
	        dom: 'l<"centerBtn"B>frtip',
	        buttons: [
	             'copyHtml5',
	             'excelHtml5',
	             //'csvHtml5',
	             'pdfHtml5'
	        ]
	    });

	    $('#adv_search').on('click', function(e) {
			
				
				$(".bt-download-principal").addClass("hidden");
				$("#spinner").removeClass("hidden");

				var val_cnpj    = $('#src_cnpj').val();
				var val_codigo  = $('#src_codigo').val();
				var val_uf      = $('#src_uf').val();
				var val_tributo = $('#src_tributo').val();
				var val_status  = $('#src_status option:selected').val();

				if (val_cnpj || val_codigo || val_status || val_uf || val_tributo) {
					var url = "{{ route('entregas.index') }}?vcn="+val_cnpj.replace(/[^0-9]/g,'')+"&vco="+val_codigo+"&vst="+val_status+"&vuf="+val_uf+"&vtb="+val_tributo;
					
				} else {
					var url = "{{ route('entregas.index') }}";
				}

				$("body").css("cursor", "progress");
					
				location.replace(url);
				//window.location.replace(url);
				

	    });

	    $('#src_status').change(function(){
	        //$("body").css("cursor", "progress");
	        //$('#adv_search').click();
	       
	    });


		//change csv created André 04/02/2020
			$.ajax({

						headers: {
					        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
					    },
		                cache:true,
		                type: 'GET',
		                url:"{!! route('entregas.csv') !!}",
		                data: {codigo:  $('#src_codigo').val(),cnpj: $('#src_cnpj').val(),uf:$('#src_uf').val(),status_filter:$('#src_status option:selected').val(),tributo:$('#src_tributo').val()},
		                dataType: "json",
		                success: function(data) {                 
		                    	
		                	file = data;


							//CASE LINUX
							file = file.replace('/var/www/html/agenda/public/' , '');
							file = file.replace('assetsdownloadcsv','assets/download/csv/')


							//CASE WINDOWS
							file = file.replace('F:\\wamp64\\www\\agenda\\public\\assets\\download\\csv\\' , '\\assets\\download\\csv\\');

							console.log(file,"carregando...");
							$('.bt-download').children().prop('disabled',false);
							$('#div-text-download').text('Download');


		                }
		    }); 


		//created André 31/01/2020
		$(".downloadcsv").on('click',function(){
			
			console.log(file,"carregadooooooo no botão...");
			//console.log(file);
			var asset = "{!! asset('f') !!}" ;
			asset = asset.replace('f' , file );
			
			$(this).attr("href",   file  ); // Set herf value
			

		}) 

	});

	jQuery(function($){
	    $('input[name="src_cnpj"]').mask("99.999.999/9999-99");
	});




	

</script>

@stop

