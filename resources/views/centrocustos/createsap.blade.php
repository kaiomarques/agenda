@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

<h1>Cadastro de Código SAP</h1>

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
    'route' => 'codigosap.create'
]) !!}

<div class="col-md-8">
    <div class="form-group">
        <div style="width:10%">
        {!! Form::label('uf', 'UF:', ['class' => 'control-label']) !!}
        <input type="text" name="uf" class="form-control" value="<?php echo $response['uf']; ?>">
        </div>
    </div>
    <div class="form-group">
        {!! Form::submit('Buscar', ['class' => 'btn btn-default', 'id' => 'busca_id']) !!}
        {!! Form::submit('Atualizar', ['class' => 'btn btn-default', 'id' => 'create_id']) !!}
    </div>

    <div class="form-group">
        <div style="width:70%">
        <table class="table table-bordered display" id="myTableAprovacao">   
            <thead>
                <tr>
                    <th>UF</th>
                    <th>Município</th>
                    <th>Código SAP</th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($response['municipios']))
                    @foreach ($response['municipios'] as $chave => $data)  
                    <tr>
                       <td><?php echo $data->uf; ?></td>
                       <td><?php echo $data->nome; ?></td>
                       <td><input type="text" name="codigosap[<?php echo $data->codigo; ?>]" class="form-control" value="<?php echo $data->codigo_sap; ?>"></td>
                    </tr> 
                    @endforeach
                @endif
            </tbody>
        </table>
        
        </div>
    </div>
    {!! Form::hidden('create', 1, ['class' => 'form-control', 'id'=>'action_id']) !!}
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

$(document).ready(function (){
    $('#myTableAprovacao').dataTable({
        language: {
        "searchPlaceholder": "Município/Código SAP",
        "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: "frtip"
    });        
});
</script>

@stop



