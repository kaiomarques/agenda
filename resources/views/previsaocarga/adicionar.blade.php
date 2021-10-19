<?php

// inicio
// ter que criar essa gambiarra por causa das frescuras do Laravel é inadmissivel

$array      = [];
$jsTributos = [];

foreach ($tributos as $value => $text) {
	$array[$value] = $text;
}

asort($array);

foreach ($array as $value => $text) {
	array_push($jsTributos, ['value' => $value, 'text' => $text]);
}

// fim

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

    <h1>Cadastro de Previsão de Carga</h1>
    <hr>
    {!! Form::open([
        'route' => 'previsaocarga.store'
    ]) !!}

    <div class="form-group">
        <div style="width:50%">
        	{!! Form::label('Tributo_id', 'Atividade Tributo', ['class' => 'control-label'] )  !!}
			<div id="div_tributo_id"></div>
        	<script type="text/javascript">

        	hendl.JCheckBoxGroup({
            	id              : "Tributo_id",
            	options         : <?= json_encode($jsTributos); ?>,
            	optionSelectAll : true
            }).create("div_tributo_id");

	       	</script>
        </div>
    </div>

	<div class="form-group">
        <div style="width:50%">
			{!! Form::label('periodo_apuracao', 'Período de apuração', ['class' => 'control-label']) !!}
			{!! Form::text('periodo_apuracao',null, ['class' => 'form-control','style' => 'width:80px']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:180px">
            {!! Form::label('Data_prev_carga', 'Data', ['class' => 'control-label']) !!}
            {!! Form::date('Data_prev_carga', '', ['class' => 'form-control']) !!}
        </div>
    </div>

    {!! Form::submit('Adicionar', ['class' => 'btn btn-default']) !!}
    {!! Form::close() !!}
    <hr/>


    <script type="text/javascript">
        $('select').select2();
    </script>
@stop