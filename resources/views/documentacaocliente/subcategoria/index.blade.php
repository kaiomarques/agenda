@extends('...layouts.master')

@section('content')


<div class="content-top">
    <div class="row">
        <div class="col-md-12">
			<h1 class="title">Subcategoria Documentos</h1>
			<span class="pull-right">
			<a class="btn btn-sm btn-warning" id="subcategoryHeader" href="#" data-toggle="modal" data-target="#modalSubcategoria"
				title="Nova subcategoria"><i class="fa fa-plus"></i> Nova Subcategoria</a>
			</span>
        </div>
    </div>
</div>

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>   
@endif
{{-- {{dd($table)}} --}}

<div class="modal fade" id="modalSubcategoria" style="width: 100%;" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
		<div class="modal-header">
			<h3 class="modal-title" id="modalSubcategoriaLabel">Nova Subcategoria</h3>
			<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body" id="modalSubcategoriaBody">
			{!! Form::open([
				'route' => 'documentacao.subcategoria.adicionar',
				'enctype' => 'application/x-www-form-urlencoded',
				'id' => 'frmDocSubcat'
			]) !!}   
			<div class="form-group">
				<div style="width:100%, height:80%">
					{!! Form::hidden('subcategoria_id','', ['class' => 'form-control', 'id' => 'subcategoria_id']) !!}
					{!! Form::label('subcategoria_descricao', 'Descrição:', ['class' => 'control-label']) !!}
					{!! Form::text('subcategoria_descricao', null, ['class' => 'form-control', 'required' => 'required', 'id' => 'subcategoria_descricao']) !!}
				</div>
			</div>
			<br/>
			<div class="form-group">
				<div style="width:100%, height:80%">
					
					{!! Form::label('subcategoria_status', 'Status?:', ['class' => 'control-label']) !!}
					{!! Form::select('subcategoria_status', ['A' => 'Ativa', 'I' => 'Inativa'], 'A', ['class' => 'form-control', 'required' => 'required', 'id' => 'subcategoria_status']) !!}
				</div>
			</div>
			<br />
		</div>
		<div class="modal-footer">
			{!! Form::submit('Incluir', ['class'=>'btn btn-primary', 'id' => 'frmSubcatSalvarSubmit']) !!}
			{!! Form::close() !!}
			<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
		</div>
		</div>
	</div>
</div>

<div class="table-default table-responsive">
	<table class="table display" id="tableSubcategories">
		<thead>
			<tr>
				<th class="text-center">ID</th>
				<th>Descrição</th>
				<th class="text-center">Status</th>
				<th>Data de Criação</th>
				<th>Última Atualização</th>
				<th class="text-center">Ações</th>
			</tr>
		</thead>
		<tbody>
		@if (!empty($table))
			@foreach ($table as $key => $value)
			<tr>
				<td>{{ $value->subcategoria_id }}</td>
				<td>{{ $value->subcategoria_descricao }}</td>
				<td>{{ ($value->subcategoria_status == 'A') ? "Ativa" :  "Inativa" }}</td>
				<td>{{ formataDataToTMZ($value->created_at) }}</td>
				<td>{{ formataDataToTMZ($value->updated_at) }}</td>
				<td class="text-center" style="width: 75px;">
					<div class="form-inline" style="width: 75px;">
						<button class="btn btn-default btn-sm" onclick="subcategoriaEditar({{ $value->subcategoria_id }})" style="float: left; margin: 0 4px;"><i class="fa fa-edit"></i></button>
						{!! Form::open([
							'route' => ['documentacao.subcategoria.excluir', $value->subcategoria_id],
							'enctype' => 'application/x-www-form-urlencoded',
							'method' => 'DELETE',
							'id' => 'frmDocSubcatExc'.$value->subcategoria_id
						]) !!}
						{!! Form::hidden('id', $value->subcategoria_id) !!}
						{!! Form::button('<i class="fa fa-trash" aria-hidden="true"></i>', ['class' => 'btn btn-danger btn-sm', 'id' => 'frmSubcatExcluirSubmit', 'type' =>	'submit']) !!}
						{!! Form::close() !!}
					</div>
				</td>
			</tr>
			@endforeach
		@endif 
		</tbody>
		<tfoot>
			<tr>
				<th>ID</th>
				<th>Descrição</th>
				<th>Status</th>
				<th>Data de Criação</th>
				<th>Última Atualização</th>
				<th class="text-center">Ações</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>


$(document).ready(function (){
    $('#tableSubcategories').dataTable({
        language: {
        	"searchPlaceholder": "Pesquisar",
        	"url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: "lfrtip",
        processing: true,
        stateSave: true,
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
             'copyHtml5',
             'excelHtml5',
             'csvHtml5',
             'pdfHtml5'
         ],
         lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });        
	
	$('[data-toggle="popover"]').popover({ trigger: "hover" });
	
	$('#modalSubcategoria').on('hidden.bs.modal', function(e) {
		$(this).find('#frmDocSubcat').reset();
	});
	
});

function subcategoriaEditar(id)
{   
	let itemId  = id;
	let itemURL = 'editar/'+itemId;
	$.getJSON(itemURL)
	.done(function(result) {
		// console.log(result);
		if (result == '0') {
			swal("Oops!", "Ocorreu um erro durante a sua requisição. Tente novamente.", "error");
		} else {
			$('#modalSubcategoriaLabel').html('Editar Subcategoria');
			$('#frmDocSubcat').attr('action', 'editar/'+result.subcategoria_id);
			$('#subcategoria_id').val(result.subcategoria_id);
			$('#subcategoria_descricao').val(result.subcategoria_descricao);
			$('#subcategoria_status').val(result.subcategoria_status);
			$('#frmSubcatSalvarSubmit').val('Salvar');
			$('#frmSubcatSalvarSubmit').text('');
			$('#frmSubcatSalvarSubmit').append('<i class="fa fa-save"></i> Salvar');
			$('#frmSubcatSalvarSubmit').attr('aria-label', 'Salvar');
			$("#modalSubcategoria").modal(); 
		}
	});
}

</script>

@stop

