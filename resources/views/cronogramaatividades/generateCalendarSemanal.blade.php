@extends('...layouts.master')
@section('content')

{!! Form::open([
    'route' => 'semanal'
]) !!}
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-12">
                <h2 class="sub-title">{!! Form::label('semana_busca', 'Período (Semana) para Busca', ['class' => 'control-label'] )  !!} </h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2">
                {!! Form::label('semana_busca', 'Data Início', ['class' => 'control-label'] )  !!}         
                {!! Form::date('data_inicio', '', ['class' => 'form-control']) !!}
            </div>

            <div class="col-md-2">
                {!! Form::label('semana_busca', 'Data Fim', ['class' => 'control-label'] )  !!}         
                {!! Form::date('data_fim', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-6"></div>
            <div class="col-md-2">
                {!! Form::submit('Gerar Calendário', ['class' => 'btn btn-success-block']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>
<script type="text/javascript">
    
jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");
});

</script>
@stop
<footer>
    @include('layouts.footer')
</footer>