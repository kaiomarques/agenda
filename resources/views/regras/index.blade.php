@extends('...layouts.master')

@section('content')

<h1>Regras</h1>
<p class="lead">Segue a lista de todas as regras cadastradas.</p>
<hr>
<table class="table table-bordered display" id="regras-table">
    <thead>
		<tr>
			<th>TRIBUTO</th>
			<th>REF</th>
			<th>REGRA</th>
			<th>FREQ</th>
			<th>LEGISLAÇÃO</th>
			<th>OBSERVAÇÕES</th>
			<th>AFDS</th>
			<th></th>
			<th></th>
		</tr>
    </thead>
</table>

<script>
$(function() {
    $('#regras-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: "{!! route('regras.data') !!}",
        columnDefs: [
			{ 
				"width": "150px",
				"targets": 0
			},
			{
				"width": "110px",
				"targets": 7
			}
		],
        columns: [
            {
				data: 'tributo.nome',
				name:'tributo.nome',
				render: function (data, type, row) {
					if(row['nome_especifico']) {
						return data+' ('+row['nome_especifico']+')';
					} else {
						return data;
					}
				}
			},
            {
				data: 'ref',
				name: 'ref',
				orderable: false
			},
            {
				data: 'regra_entrega',
				name: 'regra_entrega',
				orderable: false
			},
            {
				data: 'freq_entrega',
				name: 'freq_entrega',
				orderable: false,
				render: function (data) {
					var retval= '';
					switch(data) {
						case 'M':
							retval = 'Mensal';
							break;
						case 'T':
							retval = 'Trimestral';
							break;
						case 'A':
							retval = 'Anual';
							break;
						default:
							break;
					}
					return retval;
				}
			},
			{
				data: 'legislacao',
				name: 'legislacao',
				orderable: false,
				render: function (data) {
					var retval= data;
					if (data.indexOf("http")==0) {
						retval = '<a href="'+data+'" target="_blank">Link web</a>';
					}
					return retval;
				}
			},
            {
				data: 'obs',
				name: 'obs',
				orderable: false
			},
            {
				data: 'afds',
				name: 'afds',
				orderable: false,
				render: function (data) {
					var retval = "SIM";
					if (data==0) {
						retval = "NÃO";
					}
					return retval;
				}
			},
            {
				data: 'id',
				name:'edit',
				searchable: false,
				orderable: false,
				render: function (data) {
					var url = '<a href="{{ route('regras.edit', ':id_edit') }}" class="btn btn-default btn-sm">Alterar</a>';
					url += '<a href="{{ route('regras.show', ':id_show') }}" style="margin-left:10px" class="btn btn-default btn-sm">Mostrar</a>';
					url = url.replace(':id_edit', data);
					url = url.replace(':id_show', data);
					return url;
            	}
			},
            {
				data: 'id',
				name:'ativo',
				searchable: false,
				orderable: false,
				render: function (data, type, row) {
					var url = '';
					if(row['ativo']==1) {
						url += '<i title="regra ativa" class="fa fa-toggle-on"></i>';
					} else {
						url += '<i title="regra não ativa" class="fa fa-toggle-off"></i>';
					}

					return url;
				}
			}
        ],
        language: {
            // "searchPlaceholder": "ID, P.A. ou descrição",
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5'
        ],
        order: [[ 0, 'asc' ], [ 1, 'asc' ]],
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });
});

</script>
@stop
