@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<h1>Regras de envio por lote</h1>
<hr>
{!! Form::open([
    'route' => 'regraslotes.store'
]) !!}

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('select_tributos', 'Empresas', ['class' => 'control-label'] )  !!}
    {!!  Form::select('select_empresas', $empresas, array(), ['class' => 'form-control s2']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('select_tributos', 'Responsabilidade Tributos', ['class' => 'control-label'] )  !!}
    {!!  Form::select('select_tributos', $tributos, array(), ['class' => 'form-control s2']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        Regra geral:
        {{ Form::label('Sim', 'SIM') }}
        {!! Form::radio('label_regra', true, true, ['id' => 'regra_geral_SIM']) !!}
        {{ Form::label('Nao', 'NAO') }}
        {!! Form::radio('label_regra', false, '', ['id' => 'regra_geral_NAO']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        Envio de e-mail por aprovação:
        {{ Form::label('Sim', 'SIM') }}
        {!! Form::radio('envioaprovacao', 'S', '', ['id' => 'envioaprovacao_SIM']) !!}
        {{ Form::label('Nao', 'NAO') }}
        {!! Form::radio('envioaprovacao', 'N', '', ['id' => 'envioaprovacao_NAO']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('email_1', 'E-Mail obrigatório:', ['class' => 'control-label']) !!}
    {!! Form::text('email_1', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('email_2', 'E-Mail opcional:', ['class' => 'control-label']) !!}
    {!! Form::text('email_2', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('email_3', 'E-Mail opcional:', ['class' => 'control-label']) !!}
    {!! Form::text('email_3', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div style="width: 50%">
    <div class="pull-right">
        <a href="{{ route('regraslotes.lote_consulta') }}" class="btn btn-default">Voltar</a>
    </div>
</div>

{!! Form::hidden('id', 0, ['class' => 'form-control']) !!}
{!! Form::hidden('envio_manual', FALSE, ['class' => 'form-control']) !!}        
{!! Form::hidden('add_cnpj', 0, ['class' => 'form-control']) !!}
{!! Form::submit('Cadastrar', ['class' => 'btn btn-default']) !!}
{!! Form::close() !!}
<hr/>


<script type="text/javascript">
  $('select').select2();
</script>
@stop