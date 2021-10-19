@extends('...layouts.master')
@section('content')

<div class="content-top">
	<div class="row">
		<div class="col-md-4">
			<h1 class="title">Consultar Atividades</h1>
			<p class="lead"><a href="{{ route('cronogramaatividades.GerarConsulta') }}">Voltar</a></p>
		</div>
	</div>
</div>
<?php
$data = date('d/m/Y H:i:s');
?>
<div class="table-default table-responsive">
	<table class="table table-hover table-sm display" id="dataTables-CronogramaAtividades">
		<thead>
			<tr class="top-table thead-light">
				<th alt="Período de Apuração" title="Período de Apuração">P.Apuração</th>
				<th alt="Data da Atividade" title="Data da Atividade">Dt.Atividade</th>
				<th>Filial</th>
				<th>CNPJ</th>
				<th alt="Inscrição Estadual" title="Inscrição Estadual">IE</th>
				<th alt="Inscrição Municipal" title="Inscrição Municipal">CCM</th>
				<th>Município</th>
				<th alt="Código do IBGE" title="Código do IBGE">Cod.IBGE</th>
				<th>UF</th>
				<th alt="Prioridade de Apuração" title="Prioridade de Apuração">Prio.Apuração</th>
				<th>Tributo</th>
				<th>Regra</th>
				<th>Atividade</th>
				<th alt="Data Previsão de Carga" title="Data Previsão de Carga">Dt.Prev.Carga</th>
				<th alt="Data Limite da Atividade" title="Data Limite da Atividade">Dt.Limite</th>
				<th alt="Tempo de Atividade (min)" title="Tempo de Atividade (min)">Tempo.Atvd</th>
				<th alt="Tempo de Atividade Excedido (min)" title="Tempo de Atividade Excedido (min)">Tempo.Exc.Atvd</th>
				<th alt="Mensagem de Atividade Excedido (min)" title="Mensagem de Atividade Excedido (min)">Msg.Exc.Atvd</th>
				<th>Obs</th>
				<th>Analista</th>
				<th>Status</th>
			</tr>
		</thead>
		<tbody>
			<?php
        if (!empty($dados)) {
			$iconExclamation = '<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-exclamation-circle-fill" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>';
			$iconCheck = '<svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-check2-circle" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M15.354 2.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L8 9.293l6.646-6.647a.5.5 0 0 1 .708 0z"/><path fill-rule="evenodd" d="M8 2.5A5.5 5.5 0 1 0 13.5 8a.5.5 0 0 1 1 0 6.5 6.5 0 1 1-3.25-5.63.5.5 0 1 1-.5.865A5.472 5.472 0 0 0 8 2.5z"/></svg>';
            foreach ($dados as $key => $value) {
              $cor[1] = 'red';
              $cor[2] = 'yellow';
              $cor[3] = 'green';
        ?>
			<tr>
				<td><?php echo $value->PeriodoApuracao; ?></td>
				<td><?php echo $value->DataAtividade; ?></td>
				<td><?php echo $value->EstabelecimentoCodigo; ?></td>
				<td><?php echo mask($value->CNPJ, '##.###.###/####-##'); ?></td>
				<td><?php echo $value->InscEstadual; ?></td>
				<td><?php echo $value->InscMunicipal; ?></td>
				<td><?php echo $value->Municipio; ?></td>
				<td><?php echo $value->CodigoIBGE; ?></td>
				<td><?php echo $value->UF; ?></td>
				<td><?php echo $value->PrioridadeApuracao; ?></td>
				<td><?php echo $value->NomeTributo; ?></td>
				<td><?php echo $value->Regra; ?></td>
				<td><?php echo $value->Atividade; ?></td>
				<td><?php echo $value->DataPrevisaoCarga; ?></td>
				<td><?php echo $value->DataLimite; ?></td>
				<td><?php echo $value->TempoAtividade; ?></td>
				<td><?php echo $value->TempoAtividadeExcedido; ?></td>
				<td><?php echo $value->TempoAtividadeExcedidoMensagem; ?></td>
				<?php
				if ($value->UsuarioAnalistaId > 0 && !empty($value->TempoAtividadeExcedidoMensagem)) {
					// echo "<td><a href='#' data-toggle='tooltip' data-placement='auto left' title='{$value->TempoAtividadeExcedidoMensagem}'><span class='iconCrono'>{$icon}</span></a>{$value->UsuarioAnalista}</td>";
					echo '<td><span id="'.$value->CronogramaAtividadeId.'" data-id="'.$value->CronogramaAtividadeId.'" class="iconCrono iconExclamation" onclick="exibeMsg(this)" data-msg="'.$value->TempoAtividadeExcedidoMensagem.'">'.$iconExclamation.'</span></td>';
				} else {
					echo '<td><span id="'.$value->CronogramaAtividadeId.'" class="iconCrono iconCheck">&nbsp;</span></td>';
				}
				?>
				<td><?php echo $value->UsuarioAnalista; ?></td>
				<td	style="background-color: <?php echo $cor[$value->AtividadeStatus]; ?>; <?php echo ($cor[$value->AtividadeStatus] == 'red' || $cor[$value->AtividadeStatus] == 'green') ? 'color: white' : 'color: black';  ?> ">
					<?php echo status($value->AtividadeStatus); ?>
				</td>
			</tr>
			<?php } } ?>
		</tbody>
	</table>
</div>

<script>
function exibeMsg(d) {
	var texto = d.getAttribute("data-msg");
	Swal.fire(
		'Cronograma',
		texto,
		'exclamation'
	)
}
$(document).ready(function(){
  	$('[data-toggle="tooltip"]').tooltip();
});

$('#dataTables-CronogramaAtividades').dataTable({
	"ordering": true, // Disables control ordering (sorting) abilities
	"processing": true, // Enables control processing indicator.
	"responsive": true, // Enables and configure the Responsive extension for table's layout for different screen sizes
	"searching": true, // Enables control search (filtering) abilities
	"searchHighlight": true,
	"columnDefs": [
		{
		"targets": [ 18 ],
		"searchable": false,
		"orderable": false
		},
		{
		"targets": [ 0,7,9,11,13,14,15,16,17 ],
		"visible": false
		}
	],
	language: {
		"url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
	},
	dom: '<"leftBtn"B>    lfrtip',
	paging: true,
	buttons: [
		{
		extend: 'excelHtml5',
		exportOptions: {
			columns: [ 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,19,20 ]
		}
		},
	],
	
});

var table = $('#dataTables-CronogramaAtividades').DataTable();
var data = table.columns( [0, 1] ).data();

$(function() {
	if ($('#sidebar').hasClass('active')) {
		//
	} else {
		$('#sidebar').addClass('active');
		$('#sidebarCollapse').addClass('auto-left');
		$('#content').addClass('auto-left');
	}
});
</script>

<?php
function mask($val, $mask)
{
    $maskared = '';
    $k = 0;
    for($i = 0; $i<=strlen($mask)-1; $i++) {
        if($mask[$i] == '#') {
            if(isset($val[$k]))
                $maskared .= $val[$k++];
        } else {
            if(isset($mask[$i]))
                $maskared .= $mask[$i];
        }
    }
    return $maskared;
}

function status($status)
{
    if ($status == 1) {
        return "Entrega não efetuada";
    }

    if ($status == 2) {
        return "Entrega em aprovação";
    }

    if ($status == 3) {
      return "Entrega efetuada";
    }
}

?>
@stop
<footer>
	@include('layouts.footer')
</footer>