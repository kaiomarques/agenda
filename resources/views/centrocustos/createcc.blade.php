@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

<h1>Cadastro de Centro de Custos</h1>

<?php if ($status && !empty($msg)) { ?>
    <div class="alert alert-success">
        <?php echo $msg; ?>
    </div>
<?php } ?>

<?php if (!$status && !empty($msg)) { ?>
    <div class="alert alert-danger">
      <?php echo $msg; ?>
    </div>
<?php } ?>

<hr>
{!! Form::open([
    'route' => 'centrocustos.create'
]) !!}

<div class="col-md-8">
    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('codigo', 'Código Filial:', ['class' => 'control-label']) !!}
        <input type="text" name="codigo" class="form-control" value="<?php echo $response['codigo']; ?>">
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('centrocusto', 'Centro Custos:', ['class' => 'control-label']) !!}
        <input type="text" name="centrocusto" class="form-control" value="<?php echo $response['centrocusto']; ?>">
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('descricao', 'Descrição Centro Custos:', ['class' => 'control-label']) !!}
        <input type="text" name="descricao" class="form-control" value="<?php echo $response['descricao']; ?>">
        </div>
    </div>
    {!! Form::hidden('create', 1, ['class' => 'form-control', 'id'=>'action_id']) !!}

    {!! Form::submit('Buscar', ['class' => 'btn btn-default', 'id' => 'busca_id']) !!}
    {!! Form::submit('Atualizar', ['class' => 'btn btn-default', 'id' => 'create_id']) !!}
</div>

{!! Form::close() !!}
<hr/>

<script>
jQuery(function($){
    $( "#busca_id" ).click(function() {
      $( "#action_id" ).val(0);
    });

    $( "#create_id" ).click(function() {
      $( "#action_id" ).val(1);
    });
});
</script>

@stop



