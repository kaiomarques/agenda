<?php

// ter que criar essa gambiarra por causa das frescuras do Laravel é inadmissivel
// TODO: arrumar criando uma classe Model decente (sem Eloquent)

$array = [];

foreach ($tributos as $value => $text) {
	$array[$value] = $text;
}

asort($array);

?>
@extends('layouts.master')
@section('content')

    @include('partials.alerts.errors')

	<script src="{{ URL::to('/') }}/assets/js/hendl.js"></script>

    @if(Session::has('alert'))
        <div class="alert alert-danger">
            {!! Session::get('alert') !!}
        </div>
    @endif

    <?php if (@!empty($status)) { ?>
    <div class="alert alert-success">
        <?php echo $message; ?>
    </div>
    <?php } ?>

    <?php if (@!empty($error)) { ?>
    <div class="alert alert-danger">
        <?php echo $message; ?>
    </div>
    <?php } ?>

    <h1><?php printf('Edição de Justificativa %s', $model->id); ?></h1>
    <hr>
    {!! Form::open([
        'route' => 'justificativa.save'
    ]) !!}

    <div class="form-group">
        <div style="width:50%">
        	{!! Form::label('id_tributo', 'Tributo', ['class' => 'control-label'] )  !!}
        	{!!  Form::select('id_tributo', $array, $model->id_tributo, ['class' => 'form-control']) !!}
        </div>
    </div>

	<div class="form-group">
        <div style="width:50%">
			{!! Form::label('periodo_apuracao', 'Período de apuração', ['class' => 'control-label']) !!}
			{!! Form::text('periodo_apuracao', $model->periodo_apuracao, ['class' => 'form-control','style' => 'width:80px', 'maxlength' => 7]) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:180px">
            {!! Form::label('justificativa', 'Justificativa', ['class' => 'control-label']) !!}
            {!! Form::text('justificativa', $model->justificativa, ['class' => 'form-control', 'style' => 'width:480px', 'maxlength' => 60]) !!}
        </div>
    </div>

	{!! Form::hidden('id', $model->id, ['class' => 'form-control']) !!}
    {!! Form::submit('Salvar', ['class' => 'btn btn-default']) !!}
    <a href="{{route('justificativa.index')}}" class="btn btn-default">Voltar</a>
    {!! Form::close() !!}
    <hr/>
    <script type="text/javascript">
        $('select').select2();
        $(document).ready(function () {
			$("#periodo_apuracao").mask("99/9999");
		});
    </script>
@stop