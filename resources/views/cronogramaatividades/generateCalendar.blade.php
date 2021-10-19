@extends('...layouts.master')
@section('content')

{!! Form::open([
    'route' => 'mensal'
]) !!}
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-12">
                <h2 class="sub-title">{!! Form::label('periodo_apuracao', 'Periodo de apuração', ['class' => 'control-label'] )  !!} </h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2">         
                {!! Form::text('periodo_apuracao', '', ['class' => 'form-control']) !!}
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