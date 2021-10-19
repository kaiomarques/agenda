<?php
use App\UI\Html;

/**
 * @var int $AuthEmpresaId (id da empresa selecionada)
 * @var stdClass[] $resultset
 * @var int $numCols2
 * @var int $id
 * */

?>
<form>
	<fieldset>
		<legend>
			<?= "Detalhes de Conta Corrente"; ?>
		</legend>
	</fieldset>
</form>
<div id="div_grid_detalhes">
	<div class="panel with-nav-tabs panel-defaultr">
		<div class="panel-heading">
			<ul class="nav nav-tabs">
				<li class="active"><a href="#tab1primary" data-toggle="tab"><?= "Detalhes de Conta Corrente"; ?></a></li>
			</ul>
		</div>
		<div class="panel-body">
			<div class="tab-content">
				<div id="tab1primary" class="tab-pane fade in active">
					<div class="table-default table-responsive">
						<table class="table table-bordered display" id="movtocontacorrentes-table2" style="width: 100%; height: 100%; font-size: 12px;">
						    <thead>
						    <tr>
						        <td colspan="7"></td>
						    </tr>
						    <tr>
						        <th>Filial</th>
						        <th>CNPJ</th>
						        <th>IE</th>
						        <th>Municipio</th>
						        <th>UF</th>
						        <th>Ambito</th>
						        <th>Obrigação</th>
						        <th>Resumo Pendência</th>
						        <th>Processo</th>
								<th>Qtde NFS</th>
						        <th>Valor Principal</th>
						        <th>Multa/Juros/Correção</th>
						        <th>Data Inclusão Pendência</th>
						        <th>Data Consulta</th>
						        <th>Responsável Financeiro</th>
						        <th>Responsável Acompanhamento</th>
						        <th>Prazo Resposta</th>
						        <th>Status</th>
						        <th>OBS</th>
						    </tr>
						    </thead>
						    <tbody>
						    <?php

						    $numCols2 = 0;

						    foreach ($resultset as $linha) {

						    	$columns = [
						    		$linha->codigo,
						    		$linha->cnpj,
						    		$linha->insc_estadual,
						    		$linha->cidade,
						    		$linha->uf,
						    		$linha->ambito,
						    		$linha->obrigacao,
						    		$linha->resumo_pendencia,
						    		$linha->processo,
						    		$linha->qtde_nf,
						    		$linha->valor_principal,
						    		$linha->juros,
						    		$linha->data_inclusao_reg,
						    		$linha->data_consulta,
						    		($linha->responsavel_financeiro     ? $linha->responsavel_financeiro : $linha->resp_financeiro_id),
						    		($linha->responsavel_acompanhamento ? $linha->responsavel_acompanhamento : $linha->resp_acompanhar_id),
						    		$linha->prazo,
						    		($linha->status ? $linha->status : $linha->status_id),
						    		$linha->observacao
						    	];
						    	$numCols2 = count($columns);

						    	printf('<tr><td>%s</td></tr>', implode('</td><td nowrap>', $columns));
						    }

						    ?>
						    </tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<br />
		<div class="btn-group" role="group" aria-label="Basic example">
			<?= Html::button(['class' => 'btn btn-primary', 'width' => '200px', 'id' => 'btnBack', 'value' => '<< Voltar', 'onclick' => "doBackListagem();"]); ?>
		</div>
	</div>
</div>
<?= Html::hidden('numCols2', implode(', ', range(0, ($numCols2 - 1)))); ?>