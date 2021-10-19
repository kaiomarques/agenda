@extends('...layouts.master')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="{{ URL::to('/') }}/assets/js/hendl.js"></script>
<script type="text/javascript">

var _token = $('meta[name="csrf-token"]').attr('content');
var $ajax  = new hendl.Ajax(_token);

function doDetalhes($uf, $id_motivo) {

	var $objHtml = {
	   	"context" : $("#div_form"),
	   	"message" : "<img src=\"{{asset('assets/img/loading_16.gif')}}\">Aguarde, carregando ...</strong>"
	};

	$("#div_listagem").hide();
	$("#div_form").show();

	$ajax.call("POST", "{!! route('movtocontacorrentes.consultagerencial') !!}", getFiltros("form", {uf : $uf, id_motivo : $id_motivo}), function ($resp) {

		$(document).ready(function() {

			var $numCols = $("#numCols2").val();

			$("#movtocontacorrentes-table2").dataTable({
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
				paging: false,
				buttons: [
					{
						extend: "excelHtml5",
						exportOptions: {
							columns: [$numCols]
						}
					},
				],

			});

		});

	}, $objHtml);
}

function getFiltros($action, $objParams) {

	var $filters, $aux;

	$filters = "action=" + $action;

	if (document.getElementById("div_grid2")) { // grid gerado
		$aux      = $("#frm_filters").serialize();
		$filters  = $aux ? [$filters, $aux].join("&") : $filters;
		$filters += "&movtocontacorrentes-table_length=" + $("select[name='movtocontacorrentes-table_length']").val();
	}

	if (hendl.php.is_object($objParams)) {

		for (var $attr in $objParams) {
			$filters += "&" + $attr + "=" + $objParams[$attr];
		}

	}

	return $filters;
}

function doListagem() {

	var $objHtml;

	$objHtml = {
	   	"context" : $("#div_grid"),
	   	"message" : "<img src=\"{{asset('assets/img/loading_16.gif')}}\">Aguarde, carregando ...</strong>"
	};

	$ajax.call("POST", "{!! route('movtocontacorrentes.consultagerencial') !!}", getFiltros("list"), function ($html) {

		$(document).ready(function() {

			var $filter_limit = $("#filter_limit").val();
			var $numCols      = $("#numCols").val();

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
				paging: false,
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

function doBackListagem() {
	$("#div_form").hide();
	$("#div_listagem").show();
}

</script>
<div id="div_form" style="display:none;"></div>
<div id="div_listagem">
	<h1>Consulta Gerencial</h1>
	<hr>
	<div id="div_grid"></div>
</div>
<script>
$(document).ready(function() {
	doListagem();
});
</script>
@stop