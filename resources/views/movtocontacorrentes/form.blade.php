<?php
use App\UI\Html;
use App\Utils\Database;
use App\Utils\Models\ContaCorrente;

/**
 * @var int $AuthEmpresaId (id da empresa selecionada)
 * @var int $id (id da contacorrente p/ alteração)
 * @var \App\Utils\Model $userInc
 * @var \App\Utils\Model $userAlt
 * @var \App\Utils\Models\ContaCorrente $model
 * */

$model        = new ContaCorrente($id);
$motivos      = Database::fetchPairs("SELECT * FROM motivocontacorrente WHERE status = 'A' ORDER BY descricao ASC", 'id', 'descricao');
$riscos       = Database::fetchPairs("SELECT * FROM riscocontacorrente WHERE status = 'A' ORDER BY descricao ASC", 'id', 'descricao');
$estabs       = Database::fetchPairs("SELECT * FROM estabelecimentos WHERE empresa_id = '{$AuthEmpresaId}' AND ativo = '1' ORDER BY codigo ASC", 'id', ['codigo', 'cnpj'], ' - ');
$status       = Database::fetchPairs("SELECT * FROM statusprocadms  ORDER BY descricao ASC", 'id', 'descricao');
$responsaveis = Database::fetchPairs("SELECT * FROM respfinanceiros WHERE descricao IN ('Cliente', 'Bravo') ORDER BY descricao ASC", 'id', ['id', 'descricao'], ' - ');
$ac           = $model->exists() ? 'U' : 'I';

if ($ac == 'U') {
// 	$userInc = $model->hasParent('Users', 'usuario_inclusao');
// 	$userAlt = $model->hasParent('Users', 'usuario_alteracao');
	$motivoRiscoNullable = 'selecione...';
	$users    = Database::fetchPairs("SELECT * FROM users WHERE id IN ('{$model->usuario_inclusao}', '{$model->usuario_alteracao}') ORDER BY name ASC", 'id', ['id', 'name'], ' - ');
}
else {
	$motivoRiscoNullable = null;
}

$doUpload = (empty($model->arquivo));

?>
<form name="frm_save" id="frm_save" action="commit" method="post" enctype="multipart/form-data" target="ifrm">
	{!! csrf_field() !!}
	<fieldset>
		<legend>
			<?php echo ($model->exists() ? "Edição de Conta Corrente {$id}" : 'Adicionar nova Conta Corrente'); ?>
		</legend>
		<?php

		if ($ac == 'U' && $model->arquivo) {
			printf('<a href="%s?id=%s">', route('movtocontacorrentes.pdf'), $model->id);
			echo Html::getImage('assets/img/pdf-icon.png', ['title' => 'Baixar o PDF comprovante']);
			echo '</a>';
		}

		?>
		<div class="row">
			<div class="col-md-6"><?= Html::JComboBox(['options' => $estabs, 'id' => 'estabelecimento_id', 'label' => 'Estabelecimento', 'selected' => $model->estabelecimento_id]); ?></div>
			<div class="col-md-6"><?= Html::JComboBox(['options' => $status, 'id' => 'status_id', 'label' => 'Status', 'selected' => $model->status_id]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-6"><?= Html::JComboBox(['options' => $motivos, 'id' => 'motivo_id', 'label' => 'Motivo', 'selected' => $model->motivo_id, 'nullable' => $motivoRiscoNullable]); ?></div>
			<div class="col-md-6"><?= Html::JComboBox(['options' => $riscos,  'id' => 'risco_id',  'label' => 'Risco',  'selected' => $model->risco_id,  'nullable' => $motivoRiscoNullable]); ?></div>
		</div>

		<!-- TODO: add 6 campos novos abaixo -->

		<div class="row">
			<div class="col-md-12"><?= Html::JComboBox(['options' => ContaCorrente::getAmbitos(), 'id' => 'ambito', 'label' => 'Âmbito', 'selected' => $model->ambito]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-12"><?= Html::input(['id' => 'obrigacao', 'value' => $model->obrigacao, 'label' => 'Obrigação', 'disabled' => false, 'maxlength' => 250]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-12"><?= Html::textarea(['id' => 'resumo_pendencia', 'value' => stripslashes($model->resumo_pendencia), 'label' => 'Resumo da Pendência', 'disabled' => false]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-6"><?= Html::JComboBox(['options' => $responsaveis, 'id' => 'resp_financeiro_id', 'label' => 'Responsável Financeiro', 'selected' => $model->resp_financeiro_id, 'nullable' => null]); ?></div>
			<div class="col-md-6"><?= Html::JComboBox(['options' => $responsaveis, 'id' => 'resp_acompanhar_id', 'label' => 'Responsável pelo acompanhamento', 'selected' => $model->resp_acompanhar_id, 'nullable' => null]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-12"><?= Html::input(['maxlength' => 100, 'id' => 'responsavel_bravo', 'value' => $model->responsavel_bravo, 'label' => 'Responsavel Bravo', 'disabled' => false]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-6"><?= Html::input(['id' => 'qtde_nf', 'type' => 'number', 'value' => $model->qtde_nf, 'label' => 'Quantidade de Notas Fiscais', 'disabled' => false]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-6"><?= Html::input_money(['id' => 'valor_principal', 'value' => $model->valor_principal, 'label' => 'Valor Principal', 'disabled' => false]); ?></div>
			<div class="col-md-6"><?= Html::input_money(['id' => 'valor_debito', 'value' => $model->valor_debito, 'label' => 'Valor Debito', 'disabled' => false]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-6"><?= Html::input_money(['id' => 'juros', 'value' => $model->juros, 'label' => 'Juros/Multa/Correções', 'disabled' => false]); ?></div>
			<div class="col-md-6"><?= Html::input_data(['id'  => 'prazo', 'value' => $model->prazo, 'label' => 'Prazo Resposta', 'disabled' => false]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-6"><?= Html::input(['id' => 'acao', 'value' => $model->acao, 'label' => 'Ação', 'disabled' => false]); ?></div>
			<div class="col-md-6"><?= Html::input(['id' => 'processo', 'value' => $model->processo, 'label' => 'Processo', 'disabled' => false]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-6"><?= Html::input_periodo(['id' => 'periodo', 'value' => $model->periodo, 'label' => 'Competência', 'disabled' => false]); ?></div>
			<div class="col-md-6"><?= Html::input_data(['id' => 'data_consulta', 'value' => $model->data_consulta, 'label' => 'Data Consulta', 'disabled' => false]); ?></div>
		</div>

		<?php if ($ac == 'U') { ?>

		<div class="row">
			<div class="col-md-6"><?= Html::input(['id' => 'usuario_inclusao' , 'value' => $users[$model->usuario_inclusao], 'label'  => 'Usuário Inclusão', 'disabled' => true]); ?></div>
			<div class="col-md-6"><?= Html::input(['id' => 'usuario_alteracao', 'value' => (isset($users[$model->usuario_alteracao]) ? $users[$model->usuario_alteracao] : ''), 'label' => 'Usuário Alteração', 'disabled' => true]); ?></div>
		</div>
		<div class="row">
			<div class="col-md-6"><?= Html::input_data(['id' => 'data_inclusao_reg' , 'value' => $model->data_inclusao_reg, 'label' => 'Data de Inclusão Registro', 'disabled' => true]); ?></div>
			<div class="col-md-6"><?= Html::input_data(['id' => 'data_alteracao_reg', 'value' => $model->data_alteracao_reg, 'label' => 'Data de Alteração Registro', 'disabled' => true]); ?></div>
		</div>

		<?php } ?>

		<div class="row">
			<div class="col-md-12"><?= Html::textarea(['id' => 'observacao', 'value' => stripslashes($model->observacao), 'label' => 'Observação', 'disabled' => false]); ?></div>
		</div>

		<?php if ($doUpload) { ?>

		<div class="row">
			<div class="col-md-12"><?= Html::input(['type' => 'file', 'id' => 'arquivo', 'label' => 'Comprovante PDF']); ?></div>
		</div>

		<?php } ?>

		<div class="row">
			<div class="col-md-12">
				<br />
				<div class="btn-group" role="group" aria-label="Basic example">
					<?= Html::button(['class' => 'btn btn-primary', 'width' => '200px', 'id' => 'btnBack', 'value' => '<< Voltar', 'onclick' => "doBackListagem();"]); ?>
					<?= Html::button(['class' => 'btn btn-success', 'width' => '200px', 'id' => 'btnSave', 'value' => 'Salvar', 'onclick' => "document.getElementById('ifrm').style.display = ''; this.form.submit();"]); ?>
					<?= Html::hidden('id', $id); ?>
				</div>
			</div>
		</div>
	</fieldset>
</form>
<iframe name="ifrm" id="ifrm" style="width:100%; height:480px; border:1px solid #808080; display:none;"></iframe>