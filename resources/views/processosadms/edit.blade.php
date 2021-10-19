@extends('layouts.master')

@section('content')
@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif


<div class="modal fade" id="myModalObservacao" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Observações</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="observacaoHTML" style="width: 100%; height: 100%;">
        <?php foreach($observacoes as $observacao) { ?>
            <div><p><b><?php echo $observacao['nome']." - ".$observacao['data']."<p>". $observacao['descricao']; ?></b></p></div><br>
        <?php } ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<hr>
{!! Form::model($processosadms, [
    'method' => 'PATCH',
    'route' => ['processosadms.update', $processosadms->id]
]) !!}

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('codigo', 'Código:', ['class' => 'control-label']) !!}
    <b><?php echo $processosadms->id; ?></b>
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']) !!}
    {!! Form::text('periodo_apuracao', $processosadms->periodo_apuracao, ['class' => 'form-control']) !!}
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
    {!! Form::text('nro_processo', $processosadms->nro_processo, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        {!! Form::label('responsavel_financeiro', 'Responsavel Financeiro:', ['class' => 'control-label']) !!}
        {!! Form::select('resp_financeiro_id', $respFinanceiro, $processosadms->resp_financeiro_id, array('class' => 'form-control')) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('resp_acompanhamento', 'Responsavel Acompanhamento:', ['class' => 'control-label']) !!}
    {!! Form::text('resp_acompanhamento', $processosadms->resp_acompanhamento, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        {!! Form::label('status', 'Status:', ['class' => 'control-label']) !!}
        {!! Form::select('status_id', $status, $processosadms->status_id, array('class' => 'form-control')) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        {!! Form::label('observacao', 'Observação:', ['class' => 'control-label']) !!}
        {!! Form::textarea('Observacao', '', null, array('class' => 'form-control')) !!}
    </div>
</div>


{!! Form::hidden('estabelecimento_id', null, ['class' => 'form-control', 'id'=> 'estabelecimento_id']) !!}

<?php if (@!$_GET['view']) { ?>
    {!! Form::submit('Update Processo Administrativo', ['class' => 'btn btn-default']) !!}
    <a href="{{ route('processosadms.delete', $processosadms->id) }}" class="btn btn-default" onclick="return confirm('Tem certeza que deseja excluir o registro?')">Excluir</a>
    <a href="javascript:void(0);" class="btn btn-default" onclick="getObservacao();">Observações</a>
    <a href="{{ route('processosadms.search') }}" class="btn btn-default">Voltar</a>
    
<?php } else { 
        if (!empty($_GET['periodo_inicio']) && !empty($_GET['periodo_fim'])) { ?>
            <a href="{{ route('rlt_detalhado') }}?periodo_inicio=<?php echo $_GET['periodo_inicio']; ?>&periodo_fim=<?php echo $_GET['periodo_fim']; ?>" class="btn btn-default">Voltar</a>
        <?php } else { ?>
            <a href="{{ route('rlt_detalhado') }}" class="btn btn-default">Voltar</a>

<?php } }  ?>

{!! Form::close() !!}
<hr/>
<script>
buscar_estabelecimento(0, $("#estabelecimento_id").val());
jQuery(function($){

    $( "#area" ).change(function() { 
        buscar_estabelecimento($(this).val(), 0);
    });          
});

function getObservacao()
{
    $("#myModalObservacao").modal();
}

function buscar_estabelecimento(codigo, id){
    $.ajax(
    {
        type: "GET",
        url: '{{ url('estabelecimento') }}/search_area',
        cache: false,
        async: false,
        dataType: "json",
        data:
        {
            'codigo_area':codigo,
            'estabelecimento_id':id 
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
            $("#area").val(d.data.estabelecimento.codigo);
        }
    });
}

function printMask(data) {
        return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}
</script>
@stop