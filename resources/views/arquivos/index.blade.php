@extends('...layouts.master')

@section('content')

@if (Session::has('message'))
<div class="alert alert-info">{{ Session::get('message') }}</div>
@endif

<div class="content-top">
	<div class="row">
		<div class="col-md-4">
			<h1 class="title">Arquivos</h1>
		</div>
	</div>
</div>

<div class="modal fade" id="myModalUpload" style="width: 100%;" tabindex="-1" role="dialog"
	aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Upload de Comprovante</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="observacaoHTML" style="width: 100%; height: 100%;">
				{!! Form::open(array('url'=>'arquivos/upload','method'=>'POST', 'files'=>true)) !!}
				<div class="control-group">
					<div class="controls">
						{!! Form::hidden('atividade_id', '', ['class' => 'form-control', 'id'=>'atividade_id']) !!}
						{!! Form::file('image', array('class'=>'btn btn-default ')) !!}
					</div>
				</div>
				<div id="success"> </div>
				<br />

			</div>
			<div class="modal-footer">
				{!! Form::submit('Salvar', array('class'=>'btn btn-default ')) !!}
				{!! Form::close() !!}
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>

<!--span>Prezado usuário, selecione a atividade a qual se refere a entrega:</span><br/><br/-->
<div class="table-default table-responsive">
	<table class="table display" id="entregas-table">
		<thead>
			<tr class="search-table">
				<td colspan="12">

					<input style="width: 100px; position:relative; left:10px; text-align:center" class="codigo"
						placeholder="Código" type="text" id="src_codigo" maxlength="5" name="src_codigo"
						value="<?= $filter_codigo ?>">
					<input style="width: 145px; position:relative; left:10px; text-align:center" placeholder="CNPJ"
						type="text" id="src_cnpj" name="src_cnpj" value="<?= $filter_cnpj ?>">
					<input style="width: 145px; position:relative; left:10px; text-align:center" placeholder="Tributo"
						type="text" id="src_tributo" name="src_tributo" value="<?= $filter_tributo ?>">
					<input
						style="width: 45px; position:relative; left:10px; text-align:center; margin-right: 15px; text-transform: uppercase;"
						placeholder="uf" type="text" id="src_uf" name="src_uf" value="<?= $filter_uf ?>">
					<button id="adv_search">Buscar</button>
				</td>
			</tr>
			<tr class="top-table">
				<th>Id</th>
				<th>Descrição</th>
				<th>Tributo</th>
				<!--th>REF</th-->
				<th>P.A.</th>
				<!--th>DATA LIMITE</th-->
				<th>Entrega</th>
				<th>F.P.</th>
				<th>CNPJ</th>
				<th>COD</th>
				<th>UF</th>
				<th>DET.</th>
				<th>Arquivo</th>
				<th>Upload C.P</th>
				<th>C.Pagto</th>
			</tr>
		</thead>
	</table>
</div>
<script>
	$(function() {
    $('#entregas-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        responsive: true,
        ajax: {
			url: "{!! route('arquivos.data') !!}",
			data: function (d) {
				d.codigo = $('#src_codigo').val();
				d.cnpj = $('#src_cnpj').val();
				d.tributo = $('#src_tributo').val();
				d.uf = $('#src_uf').val();
				d.status_filter = $('#src_status option:selected').val();
			}
		},
        columnDefs: [{ "width": "22%", "targets": 1 },{ "width": "120px", "targets": 2 },{ "width": "200px", "targets": 6 }],
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
            // {
			// 	data: 'limite',
			// 	name: 'limite',
			// 	render: function (data) {
			// 		return data.substring(8,10)+'-'+data.substring(5,7)+'-'+data.substring(0,4);
			// 	}
			// },
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
					return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14)
				}
			},
            {
				data: 'estemp.codigo',
				name: 'estemp.codigo',
				searchable: false,
				orderable: false
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
				name:'detalhe',
				searchable: false,
				orderable: false,
				render: function (data, type, row) {
					var url = '';
					url =  '<a href="{{ route('arquivos.show', ':id_atividade') }}" style="margin-left:10px" class="btn btn-success btn-default btn-sm">Detalhe</a>';
					url = url.replace(':id_atividade', data);
					return url;
	            }
			},
            {
				data: 'id',
				name:'detalhe',
				searchable: false,
				orderable: false,
				render: function (data, type, row) {
					var url = '';
					url = '<a href="{{ url('download/') }}/'+data+'" style="margin-left:10px" class="btn btn-success btn-default btn-sm"><i class="fa fa-btn fa-cloud-download"></i></a>';
					return url;
				}
			},
            {
				data: 'id',
				name:'id',
				searchable: false,
				orderable: false,
				render: function (data, type, row) {
                	var url = '<a href="javascript:void(0);" onclick="fileUpload('+data+')" class="btn btn-default btn-sm">Upload</a>';
                	return url;
				}
			},
            {
				data: 'id',
				name:'detalhe',
				searchable: false,
				orderable: false,
				render: function (data, type, row) {
	                var url = '';
    	            url = '<a href="{{ url('download_comprovante/') }}/'+data+'" style="margin-left:10px" class="btn btn-success btn-default btn-sm"><i class="fa fa-btn fa-cloud-download"></i></a>';
        	        return url;
	            }
			},
        ],
        order: [[ 4, "asc" ]],
        language: {
			"searchPlaceholder": "ID, P.A. ou descrição",
			"url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        aLengthMenu: [
			[10, 25, 50, 100, -1],
			[10, 25, 50, 100, "All"]
		],
		iDisplayLength: 10,
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
             'copyHtml5',
             'excelHtml5',
             'csvHtml5',
             'pdfHtml5'
        ]
    });

    $('#adv_search').on('click', function(e) {
		var val_cnpj = $('#src_cnpj').val();
		var val_codigo = $('#src_codigo').val();
		var val_tributo = $('#src_tributo').val();
		var val_uf = $('#src_uf').val();
		if (val_cnpj || val_codigo || val_tributo || val_uf) {
			var url = "{{ route('arquivos.index') }}?vcn="+val_cnpj.replace(/[^0-9]/g,'')+"&vco="+val_codigo+"&vct="+val_tributo+"&vuf="+val_uf;
		} else {
			var url = "{{ route('arquivos.index') }}";
		}
		$("body").css("cursor", "progress");
		location.replace(url);
    });

});
jQuery(function($){
    $('input[name="src_cnpj"]').mask("99.999.999/9999-99");
});


function fileUpload(id)
{   
    $("#atividade_id").val(id);
    $("#myModalUpload").modal(); 
}

</script>

@stop