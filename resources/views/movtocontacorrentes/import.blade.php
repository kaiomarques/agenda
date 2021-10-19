<?php
use App\UI\Html;

?>
@extends('layouts.master')
@section('content')

<script src="{{ URL::to('/') }}/assets/js/hendl.js"></script>
<form name="form_import" id="form_import" method="post" action="importCommit" enctype="multipart/form-data" target="ifrm">
	{!! csrf_field() !!}
	<fieldset>
		<legend>Importação de arquivo em Conta Corrente</legend>
		<?= sprintf('<a href="%s">%s</a>', route('movtocontacorrentes.layout'), Html::getImage('assets/img/excel-icon.png', ['title' => 'Baixar o Layout Modelo'])); ?>
		<hr />
		<div class="form-group">
			<?= Html::label('Selecione um arquivo TXT com o layout acima'); ?>
			<input type="file" name="arquivo" />
		</div>
		<div class="row">
			<div class="col-md-12">
				<br />
				<div class="btn-group" role="group" aria-label="Basic example">
					<?= Html::button(['class' => 'btn btn-success', 'width' => '200px', 'id' => 'btnSave', 'value' => 'Importar', 'onclick' => "this.form.submit();"]); ?>
				</div>
			</div>
		</div>
	</fieldset>
</form>
<br />
<div class="row">
	<script>hendl.JAlert.create("jalert");</script>
</div>
<iframe name="ifrm" id="ifrm" style="width:100%; height:480px; border:1px solid #808080;"></iframe>
@stop