@extends('...layouts.master')
@section('content')

{!! Form::open([
    'route' => 'guiaicms.criticas'
]) !!}
<?php if (@!empty($mensagem)) { ?>
    <div class="alert alert-success">
        <?php echo $mensagem; ?>
    </div>
<?php } 
    $display = 'none';
    if (!empty($dados)) {
        $display = 'show';
    }
?>
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-12">
                <h2 class="sub-title">{!! Form::label('periodo_apuracao', 'Período de busca', ['class' => 'control-label'] )  !!} </h2>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-2">     
                {!! Form::label('inicio', 'Data Inicial', ['class' => 'control-label']) !!}    
                {!! Form::date('inicio', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-2">         
            {!! Form::label('fim', 'Data Final', ['class' => 'control-label']) !!}
                {!! Form::date('fim', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="row">
            <div class="col-md-2">
                    {!! Form::submit('Gerar', ['class' => 'btn btn-success-block']) !!}
                    {!! Form::close() !!}
            </div>
        </div>
        <br>
        <div class="row">
            
                
                <table class="table table-bordered display" id="dataTables-example" style="display: <?php echo $display; ?>">
                <thead>
                <tr style="display: <?php echo $display; ?>">
                    <th>Data Crítica</th>
                    <th>Filial</th>
                    <th>Tributo</th>
                    <th>Critica / Alerta</th>
                    <th>Arquivo</th>
                    <th>Importado</th>
                </tr>
                </thead>
                    <tbody style="display: <?php echo $display; ?>">
                    <?php
                        if (!empty($dados)) {
                    ?>
                        
                    <?php
                          foreach ($dados as $key => $value) {  
                    ?>
                        <tr style="display: <?php echo $display; ?>">
                            <td><?php echo $value['Data_critica']; ?></td>
                            <td><?php echo $value['codigo']; ?></td>
                            <td><?php echo $value['nome']; ?></td>
                            <td><?php echo $value['critica']; ?></td>
                            <td><?php echo $value['arquivo']; ?></td>
                            <td><?php echo $value['importado']; ?></td>
                        </tr>
                    <?php } 

                    } ?>
                            
                    </tbody>
                </table>
                
            
        </div>
    </div>

<script type="text/javascript">
    
$('select').select2();

jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");
});

$(document).ready(function () {
    $('#dataTables-example').dataTable({
        language: {                        
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: '<B>rt',
        name: 'oii',
        <?php
        if (!empty($planilha)) {
        ?>
        buttons: [
            {
                extend: 'csvHtml5',
                title: 'ZFIC_COMCODBARRAS_<?php echo $data_inicio; ?>_<?php echo $data_fim; ?>'
            }
        ]
        <?php }?>
    });     


    $('#dataTables-example_2').dataTable({
        language: {                        
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: '<B>rt',
        name: 'oii',
        <?php
        if (!empty($planilha_semcod)) {
        ?>
        buttons: [
            {
                extend: 'csvHtml5',
                title: 'ZFIC_SEMCODBARRAS_<?php echo $data_inicio; ?>_<?php echo $data_fim; ?>'
            }
        ]
        <?php }?>
    });     
});

</script>
@stop
<footer>
    @include('layouts.footer')
</footer>