@extends('...layouts.master')
@section('content')

{!! Form::open([
    'route' => 'ConsultaCronograma'
]) !!}
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="bs-callout bs-callout-info">
                <p>Informe a <strong>Data Início</strong> e a <strong>Data Fim</strong> ou apenas informe o
                    <strong>Período de Apuração</strong>.</p>
                <p><small>Obs.: Se as 3 opções forem informadas, apenas o <strong>Período de Apuração</strong> será
                        considerado.</small></p>
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-xs-4 col-sm-4 col-md-4">
            {!! Form::label('semana_busca', 'Data Início', ['class' => 'control-label'] ) !!}
            {!! Form::date('data_inicio', '', ['class' => 'form-control']) !!}
        </div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            {!! Form::label('semana_busca', 'Data Fim', ['class' => 'control-label'] ) !!}
            {!! Form::date('data_fim', '', ['class' => 'form-control']) !!}
        </div>
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
        <div class="col-xs-12 col-sm-12 col-md-12">
            {!! Form::label('empresas', 'Analistas', ['class' => 'control-label'] ) !!}
            {!! Form::select('analista_selected[]', $analistas, '', ['class' => 'form-control s2_multi', 'multiple' =>
            'multiple']) !!}
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            {!! Form::label('empresas', 'Filiais', ['class' => 'control-label'] ) !!}
            {!! Form::select('estabelecimento_selected[]', $estabelecimentos, '', ['class' => 'form-control s2_multi',
            'multiple' => 'multiple']) !!}
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            {!! Form::label('empresas', 'Tributos', ['class' => 'control-label'] ) !!}
            {!! Form::select('tributos_selected[]', $tributos, '', ['class' => 'form-control s2_multi', 'multiple' =>
            'multiple']) !!}
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            {!! Form::label('empresas', 'Status', ['class' => 'control-label'] ) !!}
            {!! Form::select('status_selected[]', $status, '', ['class' => 'form-control s2_multi', 'multiple' =>
            'multiple']) !!}
        </div>
        <div class="col-xs-8 col-sm-8 col-md-8">&nbsp;</div>
        <div class="col-xs-4 col-sm-4 col-md-4">
            {!! Form::submit('Consultar', ['class' => 'btn btn-success-block']) !!}
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