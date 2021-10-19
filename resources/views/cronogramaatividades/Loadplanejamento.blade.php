@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<h1>Gerar Planejamento</h1>
<hr>
{!! Form::open([
    'route' => 'cronogramaatividades.planejamento'
]) !!}

<div class="form-group">
    <div style="width:50%">
        {!! Form::label('Emp_id', 'Empresas', ['class' => 'control-label'] )  !!}
        {!! Form::select('Emp_id', $empresas, null, ['class' => 'form-control s2']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
        {!! Form::label('Tributo_id', 'Responsabilidade Tributos', ['class' => 'control-label'] )  !!}
        {!! Form::select('Tributo_id', $tributos, null, ['class' => 'form-control s2_multi']) !!}

    </div>
</div>

<div class="form-group">
    <div style="width:50%">
        {!! Form::label('uf', 'UF', ['class' => 'control-label'] )  !!}
        {!!  Form::select('uf', $ufs, null, ['class' => 'form-control s2']) !!}
    </div>
</div>

    <div class="form-group">
        <div style="width:100px">
        {!! Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']) !!}
        {!! Form::text('periodo_apuracao', '', ['class' => 'form-control']) !!}
        </div>
    </div>
    {!! Form::submit('Gerar', ['class' => 'btn btn-default']) !!}


{!! Form::close() !!}
<hr/>

<script>
jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");      
});

</script>

@stop



