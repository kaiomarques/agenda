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
 * @var int $numCols
 * @var int $AuthEmpresaId (id da empresa selecionada)
 * */

$filters = (object) [
	'estados' => Estados::getAll(),
	'filiais' => Database::fetchPairs("SELECT * FROM estabelecimentos WHERE empresa_id = '{$AuthEmpresaId}' AND ativo = '1' ORDER BY codigo ASC", 'id', ['codigo', 'cnpj'], ' - '),
//	'status'  => Database::fetchPairs("SELECT * FROM statusprocadms ORDER BY descricao ASC", 'id', 'descricao')
];

?>
<div id="div_grid2">
	<div class="panel with-nav-tabs panel-defaultr">
		<div class="panel-heading">
			<ul class="nav nav-tabs">
				<li class="active"><a href="#tab1primary" data-toggle="tab">Consulta Gerencial</a></li>
			</ul>
		</div>
		<div class="panel-body">
			<div class="tab-content">
				<div id="tab1primary" class="tab-pane fade in active">
					<div class="table-default table-responsive">
						<table class="table table-bordered display" id="movtocontacorrentes-table" style="width: 100%; height: 100%; font-size: 12px;">
						    <thead>
						    <tr>
						        <td colspan="9">
						        	<form id="frm_filters">
							            <br /><br />
							            <?= Html::JComboBoxMultiple(['options' => $filters->filiais, 'id' => 'filiais', 'width' => '145px', 'selectAll' => true, 'placeholder' => 'Filial', 'selected' => $selected_filiais]); ?>
							            <?= Html::JComboBoxMultiple(['options' => $filters->estados, 'id' => 'estados', 'width' => '145px', 'selectAll' => true, 'placeholder' => 'UF', 'selected' => $selected_estados]); ?>
							            <?php /*Html::JComboBoxMultiple(['options' => $filters->status,  'id' => 'status',  'width' => '145px', 'selectAll' => true, 'placeholder' => 'Status', 'selected' => $selected_status]);*/ ?>
							            <br /><br />
							            <button id="adv_search" onclick="doListagem();">BUSCAR</button>
									</form>
						        </td>
						    </tr>
						    <tr>
						        <th>UF</th>
						        <th>Descrição</th>
						        <th>QTDE</th>
						        <th>Valor</th>
						    </tr>
						    </thead>
						    <tbody>
						    <?php

						    $numCols = 0;

						    foreach ($resultset as $linha) {
						    	$columns = [
						    		$linha->uf,
						    		$linha->motivo,
						    		$linha->qtde,
						    		$linha->valor_debito,
						    	];
						    	$numCols = count($columns);

						    	printf("<tr onclick=\"doDetalhes('%s', '%s');\" style=\"cursor:pointer;\"><td>%s</td></tr>", $linha->uf, $linha->motivo_id, implode('</td><td nowrap>', $columns));
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