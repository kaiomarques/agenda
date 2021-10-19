@extends('layouts.master')

@section('content')

<h2>{{ $estabelecimento->razao_social }}</h2>
<hr>
{!! Form::model($estabelecimento, [
    'method' => 'PATCH',
    'route' => ['estabelecimentos.update', $estabelecimento->id]
]) !!}

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']) !!}
    {!! Form::text('cnpj', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
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
    {!! Form::hidden('empresa_id') !!}
</div>
<div class="form-group">
    <div style="width:20%">
    {!! Form::label('cod_municipio', 'Municipio:', ['class' => 'control-label']) !!}
    <br/>
    {!! Form::select('cod_municipio', $municipios, null, ['class' => 'form-control']) !!}
    </div>
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
        {!! Form::date('data_cadastro', date('Y-m-d', strtotime($estabelecimento->data_cadastro)), ['class' => 'form-control','style' => 'width:200px']) !!}
</div>
<div class="form-group">
    {!! Form::label('ativo', 'Ativo?', ['class' => 'control-label']) !!}
    {!! Form::checkbox('ativo', 1, null,['class' => 'form-control','style' => 'width:30px']) !!}
</div>

{!! Form::submit('Update Estabelecimento', ['class' => 'btn btn-default']) !!}
<a href="{{ route('estabelecimentos.index') }}" class="btn btn-default">Voltar</a>

{!! Form::close() !!}
<br/>

<script>
jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
});
</script>

@stop