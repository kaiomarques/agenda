@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<h1>Adicionar novo Processo Administrativo</h1>
<hr>
{!! Form::open([
    'route' => 'processosadms.store',
    'id' => 'processosadms'
]) !!}

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']) !!}
    {!! Form::text('periodo_apuracao', $periodo_apuracao_processos, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('area', 'Area:', ['class' => 'control-label']) !!}
    {!! Form::text('area', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('estabelecimento', 'Estabelecimento:', ['class' => 'control-label']) !!}
    {!! Form::text('estabelecimento', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:40%">
    {!! Form::label('cnpj', 'CNPJ:', ['class' => 'control-label']) !!}
    {!! Form::text('cnpj', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:40%">
    {!! Form::label('ie', 'Inscrição Estadual:', ['class' => 'control-label']) !!}
    {!! Form::text('ie', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
    </div>
</div>


<div class="form-group">
    <div style="width:40%">
    {!! Form::label('cidade', 'Cidade:', ['class' => 'control-label']) !!}
    {!! Form::text('cidade', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:40%">
    {!! Form::label('uf', 'UF:', ['class' => 'control-label']) !!}
    {!! Form::text('uf', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('nro_processo', 'Processo nro:', ['class' => 'control-label']) !!}
    {!! Form::text('nro_processo', '', ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        {!! Form::label('responsavel_financeiro', 'Responsavel Financeiro:', ['class' => 'control-label']) !!}
        {!! Form::select('resp_financeiro_id', $respFinanceiro, null, array('class' => 'form-control')) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('resp_acompanhamento', 'Responsavel Acompanhamento:', ['class' => 'control-label']) !!}
    {!! Form::text('resp_acompanhamento', '', ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        {!! Form::label('status', 'Status:', ['class' => 'control-label']) !!}
        {!! Form::select('status_id', $status, null, array('class' => 'form-control')) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        {!! Form::label('observacao', 'Observação:', ['class' => 'control-label']) !!}
        {!! Form::textarea('Observacao', '', array('class' => 'form-control', 'id'=>'textObservacao')) !!}
    </div>
</div>



{!! Form::hidden('estabelecimento_id', null, ['class' => 'form-control', 'id'=> 'estabelecimento_id']) !!}
{!! Form::submit('Cadastrar', ['class' => 'btn btn-default', 'id' => 'btnprocessos']) !!}

{!! Form::close() !!}
<hr/>

<script>
jQuery(function($){
    

    $('input[name="periodo_apuracao"]').mask("99/9999");

    $( "#area" ).change(function() { 
        $.ajax(
        {
            type: "GET",
            url: '{{ url('estabelecimento') }}/search_area',
            cache: false,
            async: false,
            dataType: "json",
            data:
            {
                'codigo_area':$(this).val()
            },
            success: function(d)
            {
                if (!d.success) {

                    alert('Código de Área não existe');
                    $("#estabelecimento").val('');
                    $("#estabelecimento_id").val('');
                    $("#cnpj").val('');
                    $("#ie").val('');
                    $("#cidade").val('');
                    $("#uf").val('');
                    $("#area").val('');
                    $("#area").focus();
                    return false;
                }       

                $("#estabelecimento").val(d.data.estabelecimento.razao_social);
                $("#estabelecimento_id").val(d.data.estabelecimento.id);
                $("#cnpj").val(printMask(d.data.estabelecimento.cnpj));
                $("#ie").val(d.data.estabelecimento.insc_estadual);
                $("#cidade").val(d.data.municipio.nome);
                $("#uf").val(d.data.municipio.uf);
            }
        });
    });       
});

function printMask(data) {
        return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}

</script>

@stop



