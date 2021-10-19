<?php
use App\UI\Html;
?>
@extends('...layouts.master')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="{{ URL::to('/') }}/assets/js/hendl.js"></script>
<script type="text/javascript">

var _token = $('meta[name="csrf-token"]').attr('content');
var $ajax  = new hendl.Ajax(_token);

function doListagem() {

	var $objHtml, $filters, $aux;

	$objHtml = {
	   	"context" : $("#div_grid"),
	   	"message" : "<img src=\"{{asset('assets/img/loading_16.gif')}}\">Aguarde, carregando ...</strong>"
	};
	$filters = "action=list";

	if (document.getElementById("div_grid2")) { // grid gerado
		$aux      = $("#frm_filters").serialize();
		$filters  = $aux ? [$filters, $aux].join("&") : $filters;
		$filters += "&movtocontacorrentes-table_length=" + $("select[name='movtocontacorrentes-table_length']").val();
	}

	$ajax.call("POST", "{!! route('movtocontacorrentes.search') !!}", $filters, function ($html) {
		
		$(document).ready(function() {

			var $filter_limit = $("#filter_limit").val();
			var $numCols      = $("#numCols").val();

			$("#src_periodo").mask("99/9999");
			$("#src_cnpj").mask("99.999.999/9999-99");
			$("#movtocontacorrentes-table").dataTable({
				"ordering": true, // Disables control ordering (sorting) abilities
				"processing": true, // Enables control processing indicator.
				"responsive": true, // Enables and configure the Responsive extension for tables layout for different screen sizes
				"searching": true, // Enables control search (filtering) abilities
				"searchHighlight": true,
				"columnDefs": [
					{
//					"targets": [ 18 ],
					"searchable": false,
					"orderable": false
					},
					{
//					"targets": [ 0,7,9,11,13,14,15,16,17 ],
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
						extend: "excelHtml5",
						exportOptions: {
							columns: [$numCols]
						}
					},
				],

			});

			if ($filter_limit) {
				setTimeout(function () {
					$("select[name='movtocontacorrentes-table_length']").val($filter_limit);
				}, 1000);
			}

		});

	}, $objHtml);
}

function doSave($id) {

	var $objHtml = {
	   	"context" : $("#div_form"),
	   	"message" : "<img src=\"{{asset('assets/img/loading_16.gif')}}\">Aguarde, carregando ...</strong>"
	};

	$("#div_listagem").hide();
	$("#div_form").show();
	$ajax.call("GET", "{!! route('movtocontacorrentes.search') !!}", {action : "form", id : $id}, null, $objHtml);
}

function doBackListagem() {
	$("#div_form").hide();
	$("#div_listagem").show();
	doHideOptions();
}

function doHideOptions() {
	$("#div_frm_opt").hide();
	hendl.JAlert.hide();
}

</script>
<div class="row">
	<script>hendl.JAlert.create("div_jalert");</script>
</div>
<div id="div_frm_opt" style="display:none; background:#000000;">
	<div class="btn-group" role="group">
		<?= Html::button(['class' => 'btn btn-primary', 'width' => '200px', 'id' => 'btnBack', 'value' => 'Voltar para listagem', 'onclick' => "doHideOptions(); doBackListagem();"]); ?>
		<?= Html::button(['class' => 'btn btn-danger', 'width' => '200px', 'id' => 'btnEdit', 'value' => 'Continuar editando', 'onclick' => "doHideOptions(); doSave(document['frm_save']['id'].value);"]); ?>
		<?= Html::button(['class' => 'btn btn-warning', 'width' => '200px', 'id' => 'btnNew', 'value' => 'Novo cadastro', 'onclick' => "doHideOptions(); doSave(-1);"]); ?>
	</div>
</div>
<div id="div_form" style="display:none;"></div>
<div id="div_listagem">
	<h1>Conta Corrente</h1>
	<!--<p class="lead">Segue consulta de cadastros realizados. <a href="{{ route('movtocontacorrentes.create') }}">Adicionar outro?</a></p>-->
	<p class="lead">Segue consulta de cadastros realizados. <a href="javascript:doSave(0);">Adicionar outro?</a></p>
	<hr>
	<div id="div_grid"></div>
</div>
<script>
$(document).ready(function() {
	doListagem();
});
</script>
@stop