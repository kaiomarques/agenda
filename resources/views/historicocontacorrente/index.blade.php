@extends('...layouts.master')

@section('content')
<?php 
    $data = date('d/m/Y H:i:s');
?>
<h1>Histórico das movimentações</h1>
<p class="lead"><a href="{{ route('movtocontacorrentes.search') }}">Voltar</a></p>
<hr>

<table class="table table-bordered display" id="dataTables-example" style="width: 100%; height: 100%; font-size: 12px;">
    <thead>
    <tr>
        <th>Conta Corrente</th>
        <th>Alteração Realizada (Anterior => Alterado) </th>
        <th>Autor Alteração</th>
        <th>Data Alteração</th>
    </tr>
    </thead>
    <tbody>
    <?php
        if (!empty($dados)) {
          foreach ($dados as $key => $value) {  
    ?>
        <tr>
            <td><?php echo $value['Id_contacorrente']; ?></td>
            <td><?php echo $value['Alteracao_realizada']; ?></td>
            <td><?php echo $value['email']; ?></td>
            <td><?php echo $value['updated_at']; ?></td>
        </tr>
    <?php } } else { ?>
        <tr>
            <td>Nenhum Registro encontrado</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    <?php   } ?>
            
    </tbody>
</table>

<script>
$(document).ready(function () {
    $('#dataTables-example').dataTable({
        language: {                        
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: '<B>frtip'
    });     
});
</script>
@stop
