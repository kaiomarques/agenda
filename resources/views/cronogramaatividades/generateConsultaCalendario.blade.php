@extends('...layouts.master')
@section('content')

{!! Form::open([
    'route' => 'ConsultaPeriodoTabela'
]) !!}
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
                <h2>Consulta Cronograma Gerencial</h2>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-xs-4 col-sm-4 col-md-4">
            {!! Form::label('periodo_apuracao', 'Periodo Apuração', ['class' => 'control-label']) !!}
            {!! Form::text('periodo_apuracao', null, ['class' => 'form-control', 'placeholder'=>'MM/AAAA']) !!}
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            {!! Form::label('empresas', 'Empresas', ['class' => 'control-label'] ) !!}
            {!! Form::select('empresas_selected[]', $empresas, '', ['class' => 'form-control s2_multi', 'multiple' =>
            'multiple']) !!}
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-xs-4 col-sm-4 col-md-4">
            {!! Form::submit('Consulta Calendário', ['class' => 'btn btn-success-block']) !!}
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