@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

<h1>Adicionar uma atividade de entrega</h1>
<hr>
{!! Form::open([
    'route' => 'atividades.store'
]) !!}

<div class="form-group">
    {!! Form::label('user_id', 'Analista:', ['class' => 'control-label']) !!}
    <br/>
    {!! Form::select('user_id', $usuarios, ['class' => 'form-control']) !!}
</div>
<div class="form-group">
    <div style="width:180px">
    {!! Form::label('cnpj', 'Empresa/Estab (cnpj):', ['class' => 'control-label']) !!}
    {!! Form::text('cnpj', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    {!! Form::label('uf', 'UF:', ['class' => 'control-label']) !!}
    <br/>
    {!! Form::select('uf', $ufs, ['class' => 'form-control'],['placeholder' => 'Seleciona...']) !!}
</div>
<div class="form-group">
    {!! Form::label('codigo', 'Municipio:', ['class' => 'control-label']) !!}
    <br/>
    {!! Form::select('codigo', $municipios, ['class' => 'form-control']) !!}
</div>
<div class="form-group">
    {!! Form::label('tributo_id', 'Tributo:', ['class' => 'control-label']) !!}
    <br/>
    {!! Form::select('tributo_id', $tributos, ['class' => 'form-control'],['placeholder' => 'Seleciona um tributo...']) !!}
</div>
<div class="form-group">
    {!! Form::label('regra_id', 'Regra:', ['class' => 'control-label']) !!}
    <br/>
    {!! Form::select('regra_id', $regras, ['class' => 'form-control']) !!}
</div>
<div class="form-group">
    <div style="width:90%">
    {!! Form::label('descricao', 'Descrição entrega:', ['class' => 'control-label']) !!}
    {!! Form::textarea('descricao', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    {!! Form::label('periodo_apuracao', 'Periodo Apuração', ['class' => 'control-label']) !!}
    {!! Form::text('periodo_apuracao',null, ['class' => 'form-control','style' => 'width:80px']) !!}
</div>
<div class="form-group">
    {!! Form::label('recibo', 'Recibo?', ['class' => 'control-label']) !!}
    {!! Form::checkbox('recibo',1,true, ['class' => 'form-control','style' => 'width:30px']) !!}
</div>
<div class="form-group">
    <table>
    <tr>
        <td>
        {!! Form::label('limite', 'Data entrega', ['class' => 'control-label']) !!}
        {!! Form::date('limite', null, ['class' => 'form-control','style' => 'width:200px']) !!}
        </td>
        <td style="padding:0px 10px 0px 10px;">
        {!! Form::label('inicio_aviso', 'Inicio aviso', ['class' => 'control-label']) !!}
        {!! Form::date('inicio_aviso', null, ['class' => 'form-control','style' => 'width:200px']) !!}
        </td>
    </tr>
    </table>
</div>
<div class="form-group">
    {!! Form::hidden('status', 1, ['class' => 'form-control']) !!}
    {!! Form::hidden('tipo_geracao', 'M', ['class' => 'form-control']) !!}
</div>
<br/>

{!! Form::submit('Cria nova atividade', ['class' => 'btn btn-default']) !!}

{!! Form::close() !!}

<br/>
<script>

jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
});

jQuery(document).ready(function($){
  	$('#tributo_id').change(function(){

			$.get("{{ url('/dropdown-regras')}}",

				{ option: $(this).val() },

				function(data) {
					var model = $('#regra_id');
					model.empty();

					$.each(data, function(index, element) {
			            model.append("<option value='"+ element.id +"'>" + element.nome_especifico + ' ' + element.ref + "</option>");
			        });
				});
	});
	$('#uf').change(function(){

            $.get("{{ url('/dropdown-municipios')}}",

                { option: $(this).val() },

                function(data) {
                    var model = $('#codigo');
                    model.empty();

                    $.each(data, function(index, element) {
                        model.append("<option value='"+ element.codigo +"'>" + element.nome + "</option>");
                    });
                });
    });
});

</script>

@stop



