<?php
use App\UI\Html;
use App\Utils\Database;
use App\Utils\Estados;

/**
 * @var stdClass[] $resultset
 * @var int $filter_limit
 * @var string[] $selected_filiais
 * @var string[] $selected_estados
 * @var string[] $selected_status
 * @var string $filter_periodo
 * @var string $filter_cnpj
 * @var int $numCols
 * @var int $AuthEmpresaId (id da empresa selecionada)
 * */

$filters = (object) [
	'estados' => Estados::getAll(),
	'filiais' => Database::fetchPairs("SELECT * FROM estabelecimentos WHERE empresa_id = '{$AuthEmpresaId}' AND ativo = '1' ORDER BY codigo ASC", 'id', ['codigo', 'cnpj'], ' - '),
	'status'  => Database::fetchPairs("SELECT * FROM statusprocadms ORDER BY descricao ASC", 'id', 'descricao')
];

?>
<div id="div_grid2">
	<div class="panel with-nav-tabs panel-defaultr">
		<div class="panel-heading">
			<ul class="nav nav-tabs">
				<li class="active"><a href="#tab1primary" data-toggle="tab">Conta Corrente</a></li>
			</ul>
		</div>
		<div class="panel-body">
			<div class="tab-content">
				<div id="tab1primary" class="tab-pane fade in active">
					<div class="table-default table-responsive">
						<table class="table table-bordered display" id="movtocontacorrentes-table" style="width: 100%; height: 100%; font-size: 12px;">
						    <thead>
						    <tr>
						        <td colspan="19">
						        	<form id="frm_filters">
							            <input style="width: 145px;" placeholder="Período" type="text" id="src_periodo" name="src_periodo" value="<?= $filter_periodo ?>">
							            <input style="width: 145px;" placeholder="CNPJ" type="text" id="src_cnpj" name="src_cnpj" value="<?= $filter_cnpj ?>">
							            <br /><br />
							            <?= Html::JComboBoxMultiple(['options' => $filters->filiais, 'id' => 'filiais', 'width' => '145px', 'selectAll' => true, 'placeholder' => 'Filial', 'selected' => $selected_filiais]); ?>
							            <?= Html::JComboBoxMultiple(['options' => $filters->estados, 'id' => 'estados', 'width' => '145px', 'selectAll' => true, 'placeholder' => 'UF', 'selected' => $selected_estados]); ?>
							            <?= Html::JComboBoxMultiple(['options' => $filters->status,  'id' => 'status',  'width' => '145px', 'selectAll' => true, 'placeholder' => 'Status', 'selected' => $selected_status]); ?>
							            <br /><br />
							            <button id="adv_search" onclick="doListagem();">BUSCAR</button>
									</form>
						        </td>
						    </tr>
						    <tr>
						        <th>Filial</th>
						        <th>CNPJ</th>
						        <th>IE</th>
						        <th>Municipio</th>
						        <th>UF</th>
						        <th>Ambito</th>
						        <th>Obrigação</th>
						        <th>Período</th>
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

						    $numCols = 0;

						    foreach ($resultset as $linha) {

						    	$columns = [
						    		$linha->codigo,
						    		$linha->cnpj,
						    		$linha->insc_estadual,
						    		$linha->cidade,
						    		$linha->uf,
						    		$linha->ambito,
						    		$linha->obrigacao,
						    		$linha->periodo,
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
						    	$numCols = count($columns);

						    	printf('<tr title="clique para editar" style="cursor:pointer;" onclick="doSave(%s);"><td>%s</td></tr>', $linha->id, implode('</td><td nowrap>', $columns));
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
<?= Html::hidden('numCols', implode(', ', range(0, ($numCols - 1)))); ?>
<?= Html::hidden('filter_limit', $filter_limit); ?>