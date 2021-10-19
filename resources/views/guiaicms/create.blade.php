@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

<h1>Adicionar nova Guia</h1>

<?php if ($status == 'success' && !empty($msg)) { ?>
    <div class="alert alert-success">
        <?php echo $msg; ?>
    </div>
<?php } ?>

<?php if ($status == 'error' && !empty($msg)) { ?>
    <div class="alert alert-danger">
      <?php echo $msg; ?>
    </div>
<?php } ?>

<p class="lead">Limpar Formulário -> <a href="{!! route('guiaicms.cadastrar') !!}" class="btn btn-default">Novo Registro</a></p>
<hr>
{!! Form::open([
    'route' => 'guiaicms.create'
]) !!}

<div class="col-md-8">
    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('CNPJ', 'Cnpj:', ['class' => 'control-label']) !!}
        {!! Form::text('CNPJ', null, ['class' => 'form-control cnpj']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('IE', 'Inscrição Estadual:', ['class' => 'control-label']) !!}
        {!! Form::text('IE', null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('COD_RECEITA', 'Código da Receita:', ['class' => 'control-label']) !!}
        {!! Form::text('COD_RECEITA', null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('REFERENCIA', 'Referencia:', ['class' => 'control-label']) !!}
        {!! Form::text('REFERENCIA', null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('IMPOSTO', 'Imposto:', ['class' => 'control-label']) !!}
        {!! Form::text('IMPOSTO', null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('DATA_VENCTO', 'Data de Vencimento:', ['class' => 'control-label']) !!}
        {!! Form::date('DATA_VENCTO', null, ['class' => 'form-control']) !!}
        </div>
    </div>


    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('VLR_RECEITA', 'Valor Receita R$:', ['class' => 'control-label']) !!}
        {!! Form::text('VLR_RECEITA', '0,00', ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('JUROS_MORA', 'Juros Mora R$:', ['class' => 'control-label']) !!}
        {!! Form::text('JUROS_MORA', '0,00', ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('MULTA_MORA_INFRA', 'Multa Mora Infra R$:', ['class' => 'control-label']) !!}
        {!! Form::text('MULTA_MORA_INFRA', '0,00', ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('ACRESC_FINANC', 'Acréscimo Financeiro R$:', ['class' => 'control-label']) !!}
        {!! Form::text('ACRESC_FINANC', '0,00', ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('TAXA', 'Taxa R$:', ['class' => 'control-label']) !!}
        {!! Form::text('TAXA', '0,00', ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('VLR_TOTAL', 'Valor Total R$:', ['class' => 'control-label']) !!}
        {!! Form::text('VLR_TOTAL', '0,00', ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:70%">
        {!! Form::label('CODBARRAS', 'Código de Barras:', ['class' => 'control-label']) !!}
        {!! Form::text('CODBARRAS', null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:70%">
        {!! Form::label('ORDEM_TAXA', 'Ordem Taxa:', ['class' => 'control-label']) !!}
        {!! Form::text('ORDEM_TAXA', null, ['class' => 'form-control', 'maxlength' => 10]) !!}
        </div>
    </div>

    <div class="form-group" style="display: none">
        <div style="width:30%">
        {!! Form::hidden('TRIBUTO_ID', 8, ['class' => 'form-control']) !!}
        </div>
    </div>

    {!! Form::submit('Cadastrar', ['class' => 'btn btn-default']) !!}
</div>

{!! Form::close() !!}
<hr/>

<script>
jQuery(function($){
    $('input[name="REFERENCIA"]').mask("99/9999");
    $(".cnpj").mask("99.999.999/9999-99");
    $(".reais").maskMoney({symbol:'R$ ', allowZero:true,
            showSymbol:false, thousands:'.', decimal:',', symbolStay: false, defaultZero: true});
});

function printMask(data) {
        return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}
</script>

@stop



