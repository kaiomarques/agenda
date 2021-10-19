@extends('...layouts.master')
@section('content')

<style>
	.form-control {
		height: 34px;
	}

	.form-group {
		margin-bottom: 10px;
	}
</style>

<div class="content-top">
	<div class="row">
		<div class="col-md-12">
			<h1 class="title">Gerar Lote de Pagamento (CSV) - ISS</h1>
			@if (!empty($msg))
			<div class="alert alert-success pull-right" style="margin-bottom: 0px;padding: 10px;margin-top: -10px;">
				{{ $msg }}
			</div>
			@endif
		</div>
	</div>
</div>

<div class="content">
	{!! Form::open([
		'route' => 'guiaiss.gerarLoteArquivoCSV'
	]) !!}
	<div class="row">
		<div class="col-md-12">
			<div class="form-group row">
				<div class="col-xs-12">
					<h4 class="sub-title">{!! Form::label('periodo_apuracao', 'Periodo de Busca', ['class' => 'control-label']) !!}</h4>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-6">
					{!! Form::label('multiple_select_estabelecimentos[]', 'Estabelecimentos', ['class' => 'control-label'] ) !!}
					<select multiple="multiple" name="multiple_select_estabelecimentos[]" id="estabelecimentos"
						class="form-control s2_multi">
						<?php foreach($estabelecimentos as $aKey => $value) { 
								$selected = false;
								foreach($estabelecimentosselected as $key) {
									if($aKey == $key) {
										$selected = true;
									}
								}
							?>
						<option value="{{$aKey}}" @if($selected)selected="selected" @endif>{{$value}}</option>
						<?php } ?>
					</select>
				</div>
				<div class="col-xs-6">
					{!! Form::label('multiple_select_municipios[]', 'Municípios', ['class' => 'control-label'] ) !!}
					{!! Form::select('multiple_select_municipios[]', $municipios, $municipiosselected, ['class' => 'form-control s2_multi', 'multiple'
					=> 'multiple']) !!}
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-6">
					{!! Form::label('vencimento_inicio', 'Data Inicial Vencimento', ['class' => 'control-label']) !!}
					{!! Form::date('vencimento_inicio', '', ['class' => 'form-control']) !!}
				</div>
				<div class="col-xs-6">
					{!! Form::label('vencimento_fim', 'Data Final Vencimento', ['class' => 'control-label']) !!}
					{!! Form::date('vencimento_fim', '', ['class' => 'form-control']) !!}
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-6">
					{!! Form::label('leitura_inicio', 'Data Inicial Leitura', ['class' => 'control-label']) !!}
					{!! Form::date('leitura_inicio', '', ['class' => 'form-control', 'required' => 'required']) !!}
				</div>
				<div class="col-xs-6">
					{!! Form::label('leitura_fim', 'Data Final Leitura', ['class' => 'control-label']) !!}
					{!! Form::date('leitura_fim', '', ['class' => 'form-control', 'required' => 'required']) !!}
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-6">
			<table class="table table-bordered display" id="dtLoteCSV" style="width: 100%; height: 100%; font-size: 12px; display: none;">
				<thead>
					<tr style="display: none">
						<th>CAB_CDRCIN</th>
						<th>CAB_CODTBT</th>
						<th>CAB_BUKRS</th>
						<th>CAB_BARCOD</th>
						<th>CAB_DTVENC</th>
						<th>CAB_GSBER</th>
						<th>CAB_CNPJE</th>
						<th>CAB_COMPCM</th>
						<th>CAB_COMENT</th>
						<th>CAB_RGINST</th>
						<th>CAB_NFENUM</th>
						<th>CAB_SERIES</th>
						<th>CAB_SUBSER</th>
						<th>CAB_ACCESS_KEY</th>
						<th>CAB_AUTHCOD</th>
						<th>CAB_DATANF</th>
						<th>CAB_FGTSID</th>
						<th>CAB_AUFNR</th>
						<th>RAT_KOSTL</th>
						<th>RAT_GSBER</th>
						<th>RAT_VALOR</th>
						<th>RAT_VAL_ATU</th>
						<th>RAT_VAL_MULTA</th>
						<th>RAT_VAL_JUROS</th>
						<th>RAT_VAL_OUTROS</th>
						<th>RAT_VAL_ACRES</th>
						<th>RAT_VAL_DESCONT</th>
						<th>RAT_AUFNR</th>
					</tr>
				</thead>
				<tbody>
					<tr style="display: none">
						<th>Codigo de Receita (Interno)</th>
						<th>Codigo do Tributo</th>
						<th>Empresa</th>
						<th>Codigo de Barras</th>
						<th>Data de vencimento</th>
						<th>Divisao</th>
						<th>CNPJ</th>
						<th>Comentario para Comprovante</th>
						<th>Comentarios</th>
						<th>Registro da instalacao</th>
						<th>Numero de documento de nove posicoes</th>
						<th>Series NF/NFE</th>
						<th>Subseries</th>
						<th>Chave de acesso de 44 posicoes</th>
						<th>Codigo de Autoriza</th>
						<th>BTP - Data da Nota</th>
						<th>Identifcacao processo FGTS</th>
						<th>Ordem</th>
						<th>Centro de custo</th>
						<th>Divisao</th>
						<th>Valor total</th>
						<th>Valor atualizado</th>
						<th>Valor multa</th>
						<th>Valor Juros</th>
						<th>Valor outros </th>
						<th>Valor acrescimento</th>
						<th>Valor desconto</th>
						<th>Ordem</th>
					</tr>
					@if (!empty($planilha))
						@foreach ($planilha as $item => $value)	
							<tr>
								<td>ISS</td>
								<td>ISS</td>
								<td>
									@if (substr($value['cnpj'], 0, 8) == 13574594)
										1000
									@endif 
								</td>
								<td>{{ $value['codigo_barras'] }}</td>
								<td>{{ date('d/m/Y', strtotime($value['vencimento'])) }}</td>
								<td>{{ $value['codigo'] }}</td>
								<td>{{ $value['cnpj'] }}</td>
								<td></td>
								<td>{{ 'Pagto ISS '.$value['codigo'].'/'.$value['centrocusto'] }}</td>
								<td></td>
								<td>ISS</td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td>{{ $value['centrocusto'] }}</td>
								<td>{{ $value['codigo'] }}</td>
								<td>{{ $value['valor_guia'] }}</td>
								<td></td>
								<td>{{ $value['valor_multa'] }}</td>
								<td>{{ $value['valor_juros'] }}</td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
						@endforeach
					@endif
				</tbody>
				<tfoot>
					<tr>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
				</tfoot>
			</table>
		</div>
		<div class="col-xs-6">
			{!! Form::submit('Gerar', ['class' => 'btn btn-success-block']) !!}
		</div>
	</div>
	{!! Form::close() !!}
</div>

<script type="text/javascript">
	$(document).ready(function () {
		// hide scrollbar in selectbox control
		$('select').select2();
		// gera arquivo
		$('#dtLoteCSV').dataTable({
        language: {                        
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: '<B>rt',
        @if (!empty($planilha))
        buttons: [
            {
                extend: 'csvHtml5',
				text: 'Download do arquivo CSV',
                title: 'guiaiss_<?php echo $data_inicio; ?>_<?php echo $data_fim; ?>',
                fieldSeparator: ';',
                fieldBoundary: ''
            }
        ],
        "ordering": false
        @endif
    }); 
		
	});
</script>

@stop
<footer>
	@include('layouts.footer')
</footer>
