@extends('layouts.master')

@section('content')
@include('partials.alerts.errors')


<h2>{{ $empresa->razao_social }}</h2>
<hr>
{!! Form::model($empresa, [
    'method' => 'PATCH',
    'route' => ['empresas.update', $empresa->id]
]) !!}

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']) !!}
    {!! Form::text('cnpj', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('codigo', 'Código:', ['class' => 'control-label']) !!}
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
    <div style="width:50%">
        {!! Form::hidden ('cliente_aprova', 'N') !!}
        {!! Form::label('cliente_aprova', 'Aprovação de arquivo por cliente:', ['class' => 'control-label']) !!}
        {!! Form::checkbox('cliente_aprova', 'S', ($empresa->cliente_aprova=='S')?1:0, ['style' => 'margin: 4px 4px 0;']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('cod_municipio', 'Municipio:', ['class' => 'control-label']) !!}
    <br/>
    {!! Form::select('cod_municipio', $municipios, null, ['class' => 'form-control']) !!}
    </div>
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
<div class="form-group">
    <div style="width:50%">
    {!! Form::label('multiple_select_tributos[]', 'Configuração Tributos', ['class' => 'control-label'] )  !!}
    {!! Form::select('multiple_select_tributos[]', $tributos, $empresa->tributos()->getRelatedIds()->toArray(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
    </div>
</div>
<div class="form-group">
    <div style="width:50%">
    {!! Form::label('multiple_select_users[]', 'Acesso Usuarios', ['class' => 'control-label'] )  !!}
    {!! Form::select('multiple_select_users[]', $users, $empresa->users()->getRelatedIds()->toArray(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
    </div>
</div>

{!! Form::submit('Update Empresa', ['class' => 'btn btn-default']) !!}
<a href="{{ route('empresas.index') }}" class="btn btn-default">Voltar</a>

{!! Form::close() !!}
<hr/>

<script>
jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
});

$('select').select2();

</script>

@stop