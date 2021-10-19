@extends('...layouts.master')
@section('content')

{!! Form::open([
	'route' => 'validador.validaDados'
]) !!}

<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
				<h4 class="sub-title">
					Validador
				</h4>
            </div>
        </div>	
		<div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
				<div class="bs-callout bs-callout-info">
					Selecione os tributos a serem criticados, o período de apuração e clique em Validar
				</div>
            </div>
            {{-- <div class="col-xs-12 col-sm-12 col-md-12"> --}}
				{{-- <div class="bs-callout bs-callout-info"> --}}
					<?php
					// if (date_default_timezone_get()) {
					// 	echo 'date_default_timezone_set: ' . date_default_timezone_get() . '<br />';
					// }

					// if (ini_get('date.timezone')) {
					// 	echo 'date.timezone: ' . ini_get('date.timezone'). '<br />';
					// }
					// echo date_default_timezone_get() . ' => ' . date('e') . ' => ' . date('T'). '<br /><br />';
					
					// date_default_timezone_set(config('configICMSVars.wamp.timezone_brt'));
					// if (date_default_timezone_get()) {
					// 	echo 'date_default_timezone_set: ' . date_default_timezone_get() . '<br />';
					// }

					// if (ini_get('date.timezone')) {
					// 	echo 'date.timezone: ' . ini_get('date.timezone'). '<br />';
					// }
					// echo date_default_timezone_get() . ' => ' . date('e') . ' => ' . date('T'). '<br /><br />';
					
					// date_default_timezone_set(config('configICMSVars.wamp.timezone_brst'));
					// if (date_default_timezone_get()) {
					// 	echo 'date_default_timezone_set: ' . date_default_timezone_get() . '<br />';
					// }

					// if (ini_get('date.timezone')) {
					// 	echo 'date.timezone: ' . ini_get('date.timezone'). '<br />';
					// }
					// echo date_default_timezone_get() . ' => ' . date('e') . ' => ' . date('T'). '<br /><br />';
					// ?>
				{{-- </div> --}}
            {{-- </div> --}}
        </div>
		<br />

		<div class="row">
            <div class="col-xs-12 col-sm-6 col-md-3">
            	{!! Form::label('tributo_id_a', 'Selecionar Tributo A', ['class' => 'control-label']) !!}
				{!! Form::select('tributo_id_a', $tributosA, null, ['class' => 'form-control', 'required' => 'required']) !!}
			</div>
			<div class="col-xs-12 col-sm-6 col-md-3">
				{!! Form::label('tributo_id_b', 'Selecionar Tributo B', ['class' => 'control-label']) !!}
				{!! Form::select('tributo_id_b', $tributosB, null, ['class' => 'form-control', 'required' => 'required']) !!}
			</div>
        </div>
		<div class="row">
			<div class="col-xs-12 col-sm-6 col-md-63">
				<div id="infoMessage" class="alert alert-danger" role="alert">Você deve selecionar tributos diferentes para realizar a validação <i class="fa fa-exclamation" aria-hidden="true"></i></div>
			</div>
		</div>
        <br />
		
        <div class="row">
			<div class="col-xs-12 col-sm-6 col-md-3">    
				{!! Form::label('periodo_apuracao', 'Periodo de apuração', ['class' => 'control-label']) !!}     
                {!! Form::text('periodo_apuracao', '', ['class' => 'form-control', 'required' => 'required']) !!}
            </div>
			<div class="col-xs-12 col-sm-6 col-md-3">
				<label for="Validar">&nbsp;</label>
				{!! Form::submit('Validar', ['class' => 'btn btn-success-block', 'id' => 'frmValidarSubmit']) !!}
				{!! Form::close() !!}
			</div>
		</div>
		<br>
		<div class="row">
			<div class="col-xs-12 col-sm-6 col-md-63">
				<div class="alert alert-info" role="alert" id="processMessage"><img src="/assets//img/loading_16.gif"><strong> Aguarde, </strong>realizando validações.</div>
				@if (isset($status))
				<div class="alert alert-success" role="alert" id="statusMessage"><i class="fa fa-check" aria-hidden="true"></i> <strong>{{ $status or " " }}</strong></div>
				@endif
				
				<div id="alert-status" class="alert alert-success alertMessage" style="display:none"></div>
			</div>
		</div>
		<br />
		
        <br />
        <br />
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12">
				<h4 class="sub-title">
					Listagem de críticas
				</h4>
			</div>
		</div>
		
		<br />
        <div class="row">
            <div id="resultado" class="col-xs-12 col-sm-12 col-md-12">
				<table id="tableCritica" class="table-default table-responsive">
					<thead>
						<th><small>Id</small></th>
						<th><small>Filial</small></th>
						<th><small>Estabelecimento</small></th>
						<th><small>Período Apuração</small></th>
						<th><small>Critica</small></th>
						<th><small>Data</small></th>
					</thead>
					<tfoot>
						<th><small>Id</small></th>
						<th><small>Filial</small></th>
						<th><small>Estabelecimento</small></th>
						<th><small>Período Apuração</small></th>
						<th><small>Critica</small></th>
						<th><small>Data</small></th>
					</tfoot>
					<tbody>
					@if (!empty($table))
						@foreach ($table as $key => $value)
						<tr>
							<td><small>{{ $value->criticasvalores_id }}</small></td>
							<td><small>{{ $value->codigo }}</small></td>
							<td><small>{{ $value->cnpj }}</small></td>
							<td><small>{{ $value->periodo_apuracao }}</small></td>
							<td><small>{{ $value->critica }}</small></td>
							{{-- <td>><small><strong>{{ formataData($value->data_critica) }}</strong></small></td> --}}
							<td><small><strong>{!! \Helper::dateFormatFromUTC($value->data_critica, config('configICMSVars.wamp.timezone_brt')) !!}</strong></small></td>
						</tr>
						@endforeach
					@endif
					</tbody>
				</table>
			</div>
        </div>
    </div>
<script type="text/javascript">
jQuery(function($){
	
	function alertaBootstrap(mensagem) {
		$('#alert-status').html(mensagem).fadeIn().delay(5000).fadeOut();	
	}
	
    $('input[name="periodo_apuracao"]').mask("99/9999");
	
	$("#frmValidarSubmit").click(function () {
		var data = $("input[name=periodo_apuracao]").val();
		
		var patternData = /^[0-9]{2}\/[0-9]{4}$/;
		if(!patternData.test(data)){
			alertaBootstrap("Digite a data no formato Mês/Ano");
			return false;
		}
		
		data = data.split('/');
		
		var data = new Date(data[1], data[0], '1');
		var data_inicial = new Date('2020','05','01');
		
		if(data < data_inicial) {
			
			alertaBootstrap("Não existem dados antes do período 05/2020. ");
			return false;
		}
	});
	
	$(document).ready(function (){
		$('#tableCritica').dataTable({
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
			// lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
		});        
		
		$("#tributo_id_b").change(function(){
			var selectedTributoA = $("#tributo_id_a").children("option:selected").val();
			var selectedTributoB = $(this).children("option:selected").val();
			if (selectedTributoA == selectedTributoB) {
				$("#infoMessage").slideDown({
					opacity: "show"
				},
				"slow"
				);
			} else {
				$("#infoMessage").slideUp({
					opacity: "show"
				},
				"slow"
				);
			}
		});
		
		$('form').submit(function() {
			$('#frmValidarSubmit').attr('disabled', true);
			$("#processMessage").slideDown({
				opacity: "show"
			},
			"slow"
			);
		});
		
		setTimeout(() => {
			$("#statusMessage").slideUp({
				opacity: "show"
			},
			"slow"
			);
		}, 30000);
	});
});
</script>
@stop
<footer>
    @include('layouts.footer')
</footer>