@extends('...layouts.master')

@section('content')
<div class="content-top">
    <div class="row">
        <div class="col-md-12">
            <h1 class="title">Início </h1><span class="current-page">/ Selecionar empresa</span>
        </div>
    </div>
</div>

<form action="home" method="get">
    
<div class="main">
	<div class="row">
		@if (isset($error))
		<div id="alert-danger" class="alert alert-danger">{{ $error }}</div>
		@endif
	</div>
    <div class="row">
        <div class="col-md-12">
            <h2 class="sub-title">{!! Form::label('multiple_select_tributos[]', 'Selecionar empresa', ['class' => 'control-label'] )  !!}</h2>
        </div>
    </div>
    <div class="row">
        <div class="col-md-10">
            {!!  Form::select('empresa_selecionada', $empresas, array(), ['class' => 'form-control']) !!}
        </div>
        <div class="col-md-2">
            {!! Form::submit('Selecionar', ['class' => 'btn btn-success-block']) !!}
        </div>
    </div>
</div>
</form>

@stop
