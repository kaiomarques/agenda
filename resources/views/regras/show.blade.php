@extends('layouts.master')

@section('content')

<div class="panel panel-primary">
	<div class="panel-heading">
		<h2 class="panel-title">REGRA</h2>
	</div>
	<div class="panel-body">
		<div class="table-responsive text-nowrap">
			<table class="table table-sm table-striped table-hover">
				<thead class="black white-text">
					<tr>
						<th>TRIBUTO</th>	
						<th>NOME ESPECÍFICO</th>	
						<th>REFERÊNCIA</th>	
						<th>ADIANTAMENTO ENTREGA NO FIM DE SEMANA?</th>	
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>{{ $tributo->nome }}</td>
						<td>{{ $regra->nome_especifico }}</td>
						<td>{{ $regra->ref }}</td>
						@if ($regra->afds)
						<td class="text-success"><i class="fa fa-check-square-o" aria-hidden="true"></i> SIM</td>
						@else
						<td class="text-danger"><i class="fa fa-square-o" aria-hidden="true"></i> NÃO</td>
						@endif
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<div class="panel-footer">
		<div class="list-group">
			<p class="list-group-item active">PRÓXIMA(S) ENTREGA(S) PREVISTA(S):</p>
			@foreach($entregas as $entrega)
				<p class="list-group-item"><strong>{{ substr($entrega['data'],0,10) }}</strong>{{' ('.$entrega['desc'].')'}}</p>
			@endforeach
		</div>
	</div>
</div>

@if($empresas)
<div class="panel panel-info">
	<div class="panel-heading">
		<h2 class="panel-title">EMPRESAS</h2>
	</div>
	<div class="panel-body">
		<div class="table-responsive text-nowrap">
			<table class="table borderless table-sm table-striped table-hover">
				<thead class="black white-text">
					<tr>
						<th width="33%">CNPJ</th>
						<th width="33%">CÓDIGO</th>
						<th width="33%">LOCALIDADE</th>
					</tr>	
				</thead>
				<tbody>
					@foreach($empresas as $empresa)
						<tr>
							<td><a href="{{ route('empresas.show', $empresa->id) }}"><button type="button" class="btn btn-info btn-sm m-0">{{mask($empresa->cnpj,'##.###.###/####-##')}}&nbsp;&nbsp;&nbsp; <i class="fa fa-external-link" aria-hidden="true"></i></button></a></td>
							<td>{{ $empresa->codigo }}</td>
							<td>{{ $empresa->nome.' ('.$empresa->uf.') ' }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
</div>
@endif

@if($estabs)
<div class="panel panel-info">
	<div class="panel-heading">
		<h2 class="panel-title">ESTABELECIMENTOS</h2>
	</div>
	<div class="panel-body">
		<div class="table-responsive text-nowrap">
			<table class="table borderless table-sm table-striped table-hover">
				<thead class="black white-text">
					<tr>
						<th width="20%">CNPJ</th>
						<th width="15%">CÓDIGO</th>
						<th width="20%">LOCALIDADE</th>
						<th width="15%">STATUS</th>
						<th width="30%">
							<div id='ruleAction'>
								<label class="checkContainer">TODOS?
									<input type="checkbox" id="checkboxAll">
									<span class="checkmark" id="spancheckboxAll"></span>
								</label>
								<div class="btn-group btn-ruleAction">
									<button class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true"
										aria-expanded="false">
										AÇÃO? <span class="caret"></span>
									</button>
									<ul class="dropdown-menu">
										<li><a href="#" class="enableAllRules"><i class="fa fa-check" aria-hidden="true"></i> <strong>ATIVAR</strong> selecionados?</a></li>
										<li><a href="#" class="disableAllRules"><i class="fa fa-ban" aria-hidden="true"></i> <strong>INATIVAR</strong> selecionados?</a></li>
									</ul>
								</div>
							</div>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php $cont = 0 ?>
					@foreach($estabs as $estab)
						@if(!in_array($estab->id, $blacklist))
						<tr>
							<td>
								<a href="{{ route('estabelecimentos.show', $estab->id) }}">
									<button type="button" class="btn btn-info btn-sm m-0">{{mask($estab->cnpj,'##.###.###/####-##')}}&nbsp;&nbsp;&nbsp;<i class="fa fa-external-link" aria-hidden="true"></i></button>
								</a>
							</td>
							<td>{{ $estab->codigo }}</td>
							<td>{{ $estab->nome.' ('.$estab->uf.') ' }}</td>
							<td class="text-left">
								<a href="{{ route('regras.setBlacklist', array($regra->id, $estab->id, 1)) }}" style="color:green;"><i class="fa fa-check" aria-hidden="true"></i> ATIVO</a>
							</td>
							<td class="text-center">
								<label class="checkContainer">
									<input type="checkbox" data-id="1" data-estab_id="{{$estab->id}}" class="checkBoxClass" id="{{$regra->id}}" checked="checked">
									<span class="checkmark" id="{{$regra->id}}-{{$estab->id}}"></span>
								</label> {{++$cont}} ({{$regra->id}}-{{$estab->id}})
							</td>
						</tr>
						@endif
					@endforeach
					@foreach($estabs as $estab)
						@if(in_array($estab->id, $blacklist))
						<tr>
							<td>
								<a href="{{ route('estabelecimentos.show', $estab->id) }}">
									<button type="button" class="btn btn-info btn-sm m-0">{{mask($estab->cnpj,'##.###.###/####-##')}}&nbsp;&nbsp;&nbsp;<i class="fa fa-external-link" aria-hidden="true"></i></button>
								</a>
							</td>
							<td>{{ $estab->codigo }}</td>
							<td>{{ $estab->nome.' ('.$estab->uf.') ' }}</td>
							<td class="text-left">
								<a href="{{ route('regras.setBlacklist', array($regra->id, $estab->id, 0)) }}" style="color:red;"><i class="fa fa-ban" aria-hidden="true"></i> INATIVO</a>
							</td>
							<td class="text-center">
								<label class="checkContainer">
									<input type="checkbox" data-id="0" data-estab_id="{{$estab->id}}" class="checkBoxClass" id="{{$regra->id}}">
									<span class="checkmark" id="{{$regra->id}}-{{$estab->id}}"></span>
								</label> {{++$cont}} ({{$regra->id}}-{{$estab->id}})
							</td>
						</tr>
						@endif
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
</div>
@endif

<hr>
<div class="row">
    <div class="col-md-6">
        <a href="{{ route('regras.index') }}" class="btn btn-default">Voltar para todas as regras</a>
        <a href="{{ route('calendario') }}" class="btn btn-default">Voltar para Calendario</a>
    </div>
    <div class="col-md-6 text-right">
        {!! Form::open([
            'method' => 'DELETE',
            'route' => ['regras.destroy', $regra->id]
        ]) !!}
            {!! Form::submit('Cancelar esta regra?', ['class' => 'btn btn-default']) !!}
        {!! Form::close() !!}
    </div>
</div>
<script>
    $(function () {
        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });
    });
	$(document).ready(function(){
		if ($('.checkBoxClass:checked').length == $('.checkBoxClass').length) {
			$("#checkboxAll").prop("checked", true);//do something
		}
		
		$('#checkboxAll').click(function(){
			$(".checkBoxClass").prop('checked', $(this).prop('checked'));
			// console.warn("ALL: ",$('#checkboxAll').prop("checked"),$(this).attr("id") );
		});
		
		$(".checkBoxClass").change(function(){
			// console.warn("OLE: ",$(this).prop("checked"),$(this).attr("id") );
			if (!$(this).prop("checked")){
				$("#checkboxAll").prop("checked",false);
			}
			
			if ($('.checkBoxClass:checked').length == $('.checkBoxClass').length) {
				$("#checkboxAll").prop("checked", true);//do something
			}
		});
		
		$('.enableAllRules').click(function(event){
			event.preventDefault();
			
			var estabs = [];
			var id = '';
			var dataId = '';
			var url = '';
			
			$.each($('.checkBoxClass:checked'), function(i, el){
				regraId = el.id;
				estabId = el.dataset.estab_id;
				dataId = el.dataset.id;
				
				estabs.push(estabId);
				// console.warn('regraId: ', regraId, 'estabs: ', estabs, 'dataId: ', dataId);
			});
			
			var url = 'ruleEnableDisable/'+regraId+'/'+estabs+'/'+dataId;
			if (dataId == 0) {
				$.ajax({
					url: url,
					success: function(response){
						Swal.fire({
							type: 'success',
							title: 'Regras atualizadas com sucesso!',
							showConfirmButton: true,
							allowOutsideClick: false,
							showCancelButton: false,
							showCloseButton: false,
							allowEscapeKey: false
						}).then((result) => {
							if (result.value === true) {
								window.location.reload();
							}
						})					
					},
					error: function(jqXHR, textStatus, errorThrown) { 
						console.log(JSON.stringify(jqXHR));
						console.dir("AJAX error: " + textStatus + ' : ' + errorThrown);
					}
				});
			}
			// console.warn('regra: ', regraId, 'estabsArray: ', estabs, 'url: ', url);
		});
		
		$('.disableAllRules').click(function(event){	
			event.preventDefault();
			
			var estabs = [];
			var id = '';
			var dataId = '';
			var url = '';
			
			$.each($('.checkBoxClass:checked'), function(i, el){
				regraId = el.id;
				estabId = el.dataset.estab_id;
				dataId = el.dataset.id;
				
				estabs.push(estabId);
				// console.warn('regraId: ', regraId, 'estabs: ', estabs, 'dataId: ', dataId);
			});
			
			var url = 'ruleEnableDisable/'+regraId+'/'+estabs+'/'+dataId;
			if (dataId == 1) {
				$.ajax({
					url: url,
					success: function(response){
						Swal.fire({
							type: 'success',
							title: 'Regras atualizadas com sucesso!',
							showConfirmButton: true,
							allowOutsideClick: false,
							showCancelButton: false,
							showCloseButton: false,
							allowEscapeKey: false
						}).then((result) => {
							if (result.value === true) {
								window.location.reload();
							}
						})					
					},
					error: function(jqXHR, textStatus, errorThrown) { 
						console.log(JSON.stringify(jqXHR));
						console.dir("AJAX error: " + textStatus + ' : ' + errorThrown);
					}
				});
			}
			// console.warn('regra: ', regraId, 'estabsArray: ', estabs, 'url: ', url);
		});
	});
</script>
@stop

<?php

function mask($val, $mask) {
	$maskared = '';
	$k = 0;
	for($i = 0; $i<=strlen($mask)-1; $i++) {
		if($mask[$i] == '#') {
			if(isset($val[$k]))	$maskared .= $val[$k++];
		} else {
			if(isset($mask[$i])) $maskared .= $mask[$i];
		}
	}
	return $maskared;
}

?>