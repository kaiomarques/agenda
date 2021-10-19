@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

<h1>Atualizar Guia</h1>

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

<hr>
{!! Form::open([
    'route' => ['guiaicms.editar', $icms['ID']]
]) !!}

<?php
$icms['DATA_VENCTO'] = substr($icms['DATA_VENCTO'], 0,10);
if (strlen($icms['REFERENCIA']) == 6) {
    $icms['REFERENCIA'] = '0'.$icms['REFERENCIA'];
}
?>
<div class="col-md-8">
    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('CNPJ', 'Cnpj:', ['class' => 'control-label']) !!}
        {!! Form::text('CNPJ', $icms['CNPJ'], ['class' => 'form-control cnpj']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('IE', 'Inscrição Estadual:', ['class' => 'control-label']) !!}
        {!! Form::text('IE', $icms['IE'], ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('COD_RECEITA', 'Código da Receita:', ['class' => 'control-label']) !!}
        {!! Form::text('COD_RECEITA', $icms['COD_RECEITA'], ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('REFERENCIA', 'Referencia:', ['class' => 'control-label']) !!}
        {!! Form::text('REFERENCIA', $icms['REFERENCIA'], ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('IMPOSTO', 'Imposto:', ['class' => 'control-label']) !!}
        {!! Form::text('IMPOSTO', $icms['IMPOSTO'], ['class' => 'form-control']) !!}
        </div>
    </div>


    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('DATA_VENCTO', 'Data de Vencimento:', ['class' => 'control-label']) !!}
        {!! Form::date('DATA_VENCTO', $icms['DATA_VENCTO'], ['class' => 'form-control']) !!}
        </div>
    </div>


    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('VLR_RECEITA', 'Valor Receita R$:', ['class' => 'control-label']) !!}
        {!! Form::text('VLR_RECEITA', $icms['VLR_RECEITA'], ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('JUROS_MORA', 'Juros Mora R$:', ['class' => 'control-label']) !!}
        {!! Form::text('JUROS_MORA', $icms['JUROS_MORA'], ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('MULTA_MORA_INFRA', 'Multa Mora Infra R$:', ['class' => 'control-label']) !!}
        {!! Form::text('MULTA_MORA_INFRA', $icms['MULTA_MORA_INFRA'], ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('ACRESC_FINANC', 'Acréscimo Financeiro R$:', ['class' => 'control-label']) !!}
        {!! Form::text('ACRESC_FINANC', $icms['ACRESC_FINANC'], ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('TAXA', 'Taxa R$:', ['class' => 'control-label']) !!}
        {!! Form::text('TAXA', $icms['TAXA'], ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('VLR_TOTAL', 'Valor Total R$:', ['class' => 'control-label']) !!}
        {!! Form::text('VLR_TOTAL', $icms['VLR_TOTAL'], ['class' => 'form-control reais']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:70%">
        {!! Form::label('CODBARRAS', 'Código de Barras:', ['class' => 'control-label']) !!}
        {!! Form::text('CODBARRAS', $icms['CODBARRAS'], ['class' => 'form-control']) !!}
        </div>
    </div>

	<div class="form-group">
        <div style="width:70%">
        {!! Form::label('ORDEM_TAXA', 'Ordem Taxa:', ['class' => 'control-label']) !!}
        {!! Form::text('ORDEM_TAXA', $icms['ORDEM_TAXA'], ['class' => 'form-control', 'maxlength' => 10]) !!}
        </div>
    </div>

    <div class="form-group" style="display: none">
        <div style="width:30%">
        {!! Form::hidden('TRIBUTO_ID', 8, ['class' => 'form-control']) !!}
        </div>
    </div>
    '<a href="{{ route('guiaicms.listar') }}" class="btn btn-default">Voltar</a>
    {!! Form::submit('Atualizar', ['class' => 'btn btn-default']) !!}
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



