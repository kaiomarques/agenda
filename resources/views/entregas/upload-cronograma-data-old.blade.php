@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

<div class="about-section">
   <div class="text-content">
        <h2>Cronograma Mensal</h2>
        <table width="70%" class="table table-bordered display">
            <thead>
                    <td colspan="2" style="width: 20%" align="center"><b>Atividade</b></td>
                    <td colspan="3" style="width: 35%" align="center"><b>No Prazo</b></td>
                    <td colspan="3" style="width: 35%" align="center"><b>Fora do Prazo</b></td>
            </thead>
            <tbody>
                <tr align="center">
                    <td>Tributo</td>
                    <td>UF</td>
                    <td>Não Entregue</td>
                    <td>Em Aprovação</td>
                    <td>Entregue</td>
                    <td>Não Entregue</td>
                    <td>Em Aprovação</td>
                    <td>Entregue</td>
                </tr>
            </tbody>
            <tfoot>
                <?php
                if (!empty($atividades)) { 
                    foreach ($atividades as $tributo => $atividade) {
                        foreach ($atividade as $uf => $statusAtividade) {
                            foreach ($statusAtividade as $statusID => $prazo) {
                ?> 
                <tr align="center">
                    <td><?php echo $tributo; ?></td>
                    <td><?php echo $uf; ?></td>
                   
                    <td onclick='openModal(<?php echo  ( isset($atividades[$tributo][$uf][1]['Prazo']) ? json_encode($atividades[$tributo][$uf][1]['Prazo']) : "" ) ?>)'>
                            <?php echo  ( isset($atividades[$tributo][$uf][1]['Prazo']) ? count($atividades[$tributo][$uf][1]['Prazo']) : "0" ) ?>
                    </td>

                    <td onclick='openModal(<?php echo  ( isset($atividades[$tributo][$uf][2]['Prazo']) ? json_encode($atividades[$tributo][$uf][2]['Prazo']) : "" ) ?>)'>
                            <?php echo  ( isset($atividades[$tributo][$uf][2]['Prazo']) ? count($atividades[$tributo][$uf][2]['Prazo']) : "0" ) ?>
                    </td>
                    
                    <td onclick='openModal(<?php echo  ( isset($atividades[$tributo][$uf][3]['Prazo']) ? json_encode($atividades[$tributo][$uf][3]['Prazo']) : "" ) ?>)'>
                            <?php echo  ( isset($atividades[$tributo][$uf][3]['Prazo']) ? count($atividades[$tributo][$uf][3]['Prazo']) : "0" ) ?>
                    </td>
                    
                    <td onclick='openModal(<?php echo  ( isset($atividades[$tributo][$uf][1]['PrazoEstourado']) ? json_encode($atividades[$tributo][$uf][1]['PrazoEstourado']) : "" ) ?>)'>
                            <?php echo  ( isset($atividades[$tributo][$uf][1]['PrazoEstourado']) ? count($atividades[$tributo][$uf][1]['PrazoEstourado']) : "0" ) ?>
                    </td>
                    
                    <td onclick='openModal(<?php echo  ( isset($atividades[$tributo][$uf][2]['PrazoEstourado']) ? json_encode($atividades[$tributo][$uf][2]['PrazoEstourado']) : "" ) ?>)'>
                            <?php echo  ( isset($atividades[$tributo][$uf][2]['PrazoEstourado']) ? count($atividades[$tributo][$uf][2]['PrazoEstourado']) : "0" ) ?>
                    </td>

                    <td onclick='openModal(<?php echo  ( isset($atividades[$tributo][$uf][3]['PrazoEstourado']) ? json_encode($atividades[$tributo][$uf][3]['PrazoEstourado']) : "" ) ?>)'>
                            <?php echo  ( isset($atividades[$tributo][$uf][3]['PrazoEstourado']) ? count($atividades[$tributo][$uf][3]['PrazoEstourado']) : "0" ) ?>
                    </td>

                </tr>
                <?php } } } }  else { ?>
                <tr align="center">
                    <td>-</td>
                    <td>-</td>
                    <td>0</td>
                    <td>0</td>
                    <td>0</td>
                    <td>0</td>
                    <td>0</td>
                    <td>0</td>
                </tr>
                <?php } ?>
            </tfoot>
        </table>
        <br/>
        <a href="{{ URL::previous() }}" class="btn btn-default">Voltar </a>
   </div>
</div>

<div class="modal fade" id="modalDetalhes" style="width: 100%;" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Detalhes de Atividades</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closemodal()" onclose="closemodal()">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="width: 100%; height: 100%;">
        <table class="table table-bordered display" id="tableDetalhes">
            <thead>
                <tr>
                    <td>ID</td>
                    <td>Descrição</td>
                    <td>Prazo</td>
                    <td>Analista</td>
                </tr>
            </thead>
            <tbody class="recebeHTML">
                
            </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="closemodal()">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">

$('#modalDetalhes').on('hidden', function () {
    closemodal();
})

function openModal(data)
{   
    if (data == null) {
        alert('Não existem atividades'); return false;
    }

    jQuery.each(data, function(index, item) {
        if (item.name == null) {
            item.name = 'Sem Responsável'
        }
        $("#tableDetalhes > tbody").append('<tr><td>'+item.id+'</td><td>'+item.descricao+'</td><td width="15%">'+dataFormatada(item.limite)+'</td><td>'+item.name+'</td></tr>');
    });

    $("#modalDetalhes").modal(); 
}

function dataFormatada(datetime){
    var data = new Date(datetime);
    var dia = data.getDate();
    if (dia.toString().length == 1)
      dia = "0"+dia;
    var mes = data.getMonth()+1;
    if (mes.toString().length == 1)
      mes = "0"+mes;
    var ano = data.getFullYear();  
    return dia+"/"+mes+"/"+ano;
}

function closemodal()
{
    $("#tableDetalhes tbody > tr").remove();

}
</script>

<?php

function Date_Converter($date) {

    # Separate Y-m-d from Date
    $date = explode("-", substr($date,0,10));
    # Rearrange Date into m/d/Y
    $date = $date[2] . "/" . $date[1] . "/" . $date[0];

    # Return
    return $date;

}

?>

@stop

