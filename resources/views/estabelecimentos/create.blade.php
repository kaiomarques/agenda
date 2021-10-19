@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

<h1>Adicionar novo estabelecimento</h1>
<hr>
{!! Form::open([
    'route' => 'estabelecimentos.store'
]) !!}
<div class="form-group">
    {!! Form::label('empresa_id', 'Empresa:', ['class' => 'control-label']) !!}
    <br/>
    {!! Form::select('empresa_id', $empresas, ['class' => 'form-control']) !!}
</div>
<div class="form-group">
    <div style="width:30%">
    {!! Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']) !!}
    {!! Form::text('cnpj', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    <div style="width:30%">
    {!! Form::label('codigo', 'Codigo:', ['class' => 'control-label']) !!}
    {!! Form::text('codigo', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    <div style="width:50%">
    {!! Form::label('razao_social', 'Razão Social:', ['class' => 'control-label']) !!}
    {!! Form::text('razao_social', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    <div style="width:40%">
    {!! Form::label('endereco', 'Endereço:', ['class' => 'control-label']) !!}
    {!! Form::text('endereco', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    <div style="width:20%">
    {!! Form::label('num_endereco', 'Numero/Complemento:', ['class' => 'control-label']) !!}
    {!! Form::text('num_endereco', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    {!! Form::label('cod_municipio', 'Municipio:', ['class' => 'control-label']) !!}
    <br/>
    {!! Form::select('cod_municipio', $municipios, ['class' => 'form-control']) !!}
</div>
<div class="form-group">
    <div style="width:20%">
        {!! Form::label('insc_estadual', 'Inscrição Estadual:', ['class' => 'control-label']) !!}
        {!! Form::text('insc_estadual', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    <div style="width:20%">
        {!! Form::label('insc_municipal', 'Inscrição Municipal:', ['class' => 'control-label']) !!}
        {!! Form::text('insc_municipal', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
        {!! Form::label('data_cadastro', 'Data Cadastro', ['class' => 'control-label']) !!}
        {!! Form::date('data_cadastro', null, ['class' => 'form-control','style' => 'width:200px']) !!}
</div>
<a href="{{ route('estabelecimentos.index') }}" class="btn btn-default">Voltar</a>
{!! Form::submit('Cria novo estabelecimento', ['class' => 'btn btn-default']) !!}

{!! Form::close() !!}

<br/>
<script>
jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");

});
$('select').on('change', function (e) {
   var optionSelected = $(this).find("option:selected");
   var valueSelected  = optionSelected.val();
   var textSelected   = optionSelected.text();
   $('#cnpj').attr('placeholder',textSelected.substring(0,8));
});
</script>

@stop



