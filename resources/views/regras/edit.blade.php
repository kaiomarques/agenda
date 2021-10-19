@extends('layouts.master')

@section('content')
@include('partials.alerts.errors')

<h2>{{ $regra->tributo->nome.' ('.$regra->ref.')' }}</h2>
<hr>
{!! Form::model($regra, [
    'method' => 'PATCH',
    'route' => ['regras.update', $regra->id]
]) !!}
 <div class="form-group">
    {!! Form::label('tributo_id', 'Tributo:', ['class' => 'control-label']) !!}
    {!! Form::select('tributo_id', $tributos, null, ['style' => 'width:250px', 'class' => 'form-control']) !!}
</div>
<div class="row">
    <div class="col-md-3">
    {!! Form::label('nome_especifico', 'Nome Especifico:', ['class' => 'control-label']) !!}
    {!! Form::text('nome_especifico', null, ['class' => 'form-control']) !!}
    </div>
    <div class="col-md-3">
    {!! Form::label('ref', 'Referência:', ['class' => 'control-label']) !!}
    {!! Form::text('ref', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
<div class="row">
    <div class="col-md-3">
    {!! Form::label('regra_entrega', 'Regra entrega:', ['class' => 'control-label']) !!}
    {!! Form::text('regra_entrega', null, ['class' => 'form-control']) !!}
    </div>
    <div class="col-md-3">
    {!! Form::label('freq_entrega', 'Frequência (M/A):', ['class' => 'control-label']) !!}
    {!! Form::text('freq_entrega', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
<div class="row">
    <div class="col-lg-10">
    {!! Form::label('legislacao', 'Legislacao:', ['class' => 'control-label']) !!}
    {!! Form::text('legislacao', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
<div class="row">
    <div class="col-lg-10">
    {!! Form::label('obs', 'Observações:', ['class' => 'control-label']) !!}
    {!! Form::text('obs', null, ['class' => 'form-control']) !!}
    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
<div class="row">
    <div class="col-md-3">
    {!! Form::label('afds', 'Adiantamento FDS?', ['class' => 'control-label']) !!}
    {!! Form::checkbox('afds', 1, null,['class' => 'form-control','style' => 'width:30px']) !!}
    </div>
    <div class="col-md-3">
    {!! Form::label('ativo', 'Ativo?', ['class' => 'control-label']) !!}
    {!! Form::checkbox('ativo', 1, null,['class' => 'form-control','style' => 'width:30px']) !!}
    </div>
</div>
<div class="form-group">
    &nbsp;
</div>
{!! Form::submit('Update Regra', ['class' => 'btn btn-default']) !!}
<a href="{{ route('regras.index') }}" class="btn btn-default">Voltar</a>

{!! Form::close() !!}

@stop