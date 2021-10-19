@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')
<?php $dia = explode("/", $_SERVER['REQUEST_URI']); ?>
<div class="about-section">
   <div class="text-content">
        <h2>Cronograma Gerencial | Dia <?php echo date('d/m/Y' , strtotime($dia[2])); ?> </h2>
        <table width="70%" class="table table-bordered display">
            <thead>
                <tr align="center">
                    <td></td>
                    <td><b>Tributos</b></td>
                    <td><b>Atividades</b></td>
                </tr>
            </thead>
            <tfoot>
                <?php if (!empty($atividades)) {
                foreach ($atividades as $key => $value) { ?>
                <tr align="center">
                    <td>
                      <?php
                      $cor = '#c31212';
                      if(empty($value['atrasado_trib'])){
                        $cor = '#3788d8';
                      }
                      ?>
                      <i class="fa fa-circle" aria-hidden="true" style="color:<?php echo $cor; ?>"></i>
                    </td>
                    <td><?php echo $value['tributo']; ?></td>
                    <td>
                        <?php
                        $json = json_encode($value['atividades']);
                        ?>
                        <a href="javascript:void(0)" onclick='openModal(<?php echo $json; ?>)'>
                            <span class="glyphicon glyphicon-search" aria-hidden="true"></span> ::Detalhar
                        </a>
                    </td>
                </tr>
                <?php }} ?>
            </tfoot>
            <tfoot>
                <?php /*
                if (!empty($atividades)) { 
                    foreach ($atividades as $tributo => $atividade) {
                        foreach ($atividade as $uf => $statusAtividade) {
                            foreach ($statusAtividade as $statusID => $prazo) {
                ?> 
                <tr align="center">
                    <td><?php echo $tributo; ?></td>
                    <td><?php echo $uf; ?></td>
                    <td><?php echo  ( isset($atividades[$tributo][$uf][1]['Prazo']) ? (date('d/m/Y', strtotime($atividades[$tributo][$uf][1]['Prazo'][0]->limite))) : "-" ) ?></td>
                    <td onclick='openModal(<?php echo  ( isset($atividades[$tributo][$uf][1]['Prazo']) ? json_encode($atividades[$tributo][$uf][1]['Prazo']) : "" ) ?>)'
                        style="cursor: pointer;">
                    <?php echo  ( isset($atividades[$tributo][$uf][1]['Prazo']) ?
                            ('<span class="glyphicon glyphicon-search" aria-hidden="true"></span><span>::Detalhar</span>')
                            : "Sem Atividade" ) ?>
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
                <?php }*/ ?>
            </tfoot>
        </table>
        <br/>
        <!-- <a href="{{ URL::previous() }}" class="btn btn-default">Voltar </a> -->
        <button class="btn btn-default" onclick="goBack()">Voltar</button>

   </div>
</div>

<style type="text/css">
    @media (min-width: 992px) {
        .modal-dialog {
            width: 100%!important;
        }
    }
</style>
<div class="modal fade bs-example-modal-lg" id="modalDetalhes" style="width: 100%;" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
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
                    <td></td>
                    <td>ID Cronograma</td>
                    <td>ID Atividade</td>
                    <td>UF</td>
                    <td>Filial</td>
                    <td>Periodo</td>
                    <td>Data Atividade</td>
                    <td>Data Entrega</td>
                    <td>Data Aprovação</td>
                    <td>Analista</td>
                </tr>
            </thead>
            <tbody class="recebeHTML"></tbody>
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
	$('#tableDetalhes').DataTable().clear();
	$('#tableDetalhes').DataTable().destroy();

    if (data == null) {
        alert('Não existem atividades'); return false;
    }

    jQuery.each(data, function(index, item) {
	    let data_entrega = '-';0
	    if(item.data_entrega != null && item.data_entrega != '0000-00-00 00:00:00'){
            data_entrega = dataFormatada(item.data_entrega);
	    }
	    let data_atividade = '-';0
	    if(item.data_atividade != null && item.data_atividade != '0000-00-00 00:00:00'){
            data_atividade = dataFormatada(item.data_atividade);
	    }
        let data_aprovacao = '-';0
        if(item.data_aprovacao != null && item.data_aprovacao != '0000-00-00 00:00:00'){
            data_aprovacao = dataFormatada(item.data_aprovacao);
        }
	    let cor = '#3788d8';
        if(item.atrasado == 1){
            cor = '#c31212';
        }

        $("#tableDetalhes > tbody").append(`
        <tr>
            <td> <i class="fa fa-circle" aria-hidden="true" style="color:${cor}"></i> </td>
            <td>${item.id}</td>
            <td>${item.id_atividade}</td>
            <td>${item.uf}</td>
            <td>${item.codigo}</td>
            <td>${item.periodo_apuracao}</td>
            <td width="15%">${data_atividade}</td>
            <td width="15%">${data_entrega}</td>
            <td width="15%">${data_aprovacao}</td>
            <td>${item.usuario}</td>
        </tr>
        `);
    });

    $("#modalDetalhes").modal();
	if ($.fn.dataTable.isDataTable('#tableDetalhes')) {
		$('#tableDetalhes').DataTable().destroy();
	}
	$('#tableDetalhes').dataTable({
		language: {
			"searchPlaceholder": "Pesquisar registro específico",
			"url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
		},
		dom: "lfrtip",
		processing: true,
		stateSave: true,
		lengthMenu: [[25, 50, 75, -1], [25, 50, 75, "100"]]
	});
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
	$("#tableDetalhes > tbody").append(``);
	$('#tableDetalhes').DataTable().destroy();
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

<script>
    function goBack() {
        window.history.back();
    }
</script> 
@stop

