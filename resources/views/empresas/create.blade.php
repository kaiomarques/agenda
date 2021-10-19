@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

<h1>Adicionar nova empresa</h1>
<hr>
{!! Form::open([
    'route' => 'empresas.store'
]) !!}

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('codigo', 'código empresa:', ['class' => 'control-label']) !!}
    {!! Form::text('codigo', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']) !!}
    {!! Form::text('cnpj', null, ['class' => 'form-control']) !!}
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
        {!! Form::label('insc_municipal', 'Inscrição Municipal:', ['class' => 'control-label']) !!}
        {!! Form::text('insc_municipal', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    <div style="width:20%">
        {!! Form::label('insc_estadual', 'Inscrição Estadual:', ['class' => 'control-label']) !!}
        {!! Form::text('insc_estadual', null, ['class' => 'form-control']) !!}
    </div>
</div>

{!! Form::submit('Cria nova empresa', ['class' => 'btn btn-default']) !!}

{!! Form::close() !!}
<hr/>

<script>
jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
});
</script>

@stop



