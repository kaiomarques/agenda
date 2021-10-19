<?php
use App\Services\ConciliacaoVendasService;

function lines() {

	$aux   = range(1, 4);
	$lines = [];

	foreach ($aux as $number) {
		$lines[$number] = $number;
	}

	return $lines;
}

$lines = lines();

?>
@extends('layouts.master')

@section('content')

    @include('partials.alerts.errors')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <script src="{{ URL::to('/') }}/assets/js/hendl.js"></script>

    <div id="alert-status" class="alert alert-success alertMessage" style="display:none"></div>

    <h1>Conciliação de Vendas - LINX X JDE</h1>
    <hr>
    <div class="row">
    	<div class="col-md-3">
		    <div class="form-group">
		    	<input type="hidden" name="id_empresa" id="id_empresa" value="{{$id_empresa}}" />
		        {!! Form::label('periodo_apuracao', 'Periodo Apuração', ['class' => 'control-label']) !!}
		        {!! Form::text('periodo_apuracao', null, ['class' => 'form-control','style' => 'width:90px', 'placeholder'=>'MM/AAAA', 'required']) !!}
		    </div>
    	</div>
    	<div class="col-md-4">
		    <div class="form-inline">
		    	<div style="height:28px;"></div>
		        <input type="button" id="btnRun" class="btn btn-success" value="Iniciar" />
		    </div>
    	</div>
    </div>
    <div class="row">
    	<div class="col-md-6">
		    <div class="form-group">
		    	{!! Form::label('linhaInicioJDE', 'O arquivo JDE começa a ser lido na linha:', ['class' => 'control-label']) !!}
		        {!! Form::select('linhaInicioJDE', $lines, ConciliacaoVendasService::getLinhaInicioJDE(), ['class' => 'form-control']) !!}
		    </div>
    	</div>
    	<div class="col-md-6">
		    <div class="form-group">
		    	{!! Form::label('linhaInicioLINX', 'O arquivo LINX começa a ser lido na linha:', ['class' => 'control-label']) !!}
		        {!! Form::select('linhaInicioLINX', $lines, ConciliacaoVendasService::getLinhaInicioLINX(), ['class' => 'form-control']) !!}
		    </div>
    	</div>
    </div>
<!--
    <br/>
	<h6 class="current_label"></h6>
	<div id="div_pbar0"></div>
	<div id="div_pbar1"></div>
	<div id="div_pbar2"></div>
    <div id="alert-danger" class="alert alert-danger alertMessage" style="display:none"></div>
-->
	<div id="div_response"></div>
    <script>

    	var global_id = null;
    	var _token    = $('meta[name="csrf-token"]').attr('content');
		var $ajax     = new hendl.Ajax(_token);
		var $pbar     = [];

    	$(document).ready(function () {

    		$('input[name="periodo_apuracao"]').mask("99/9999");

//     		$pbar[0] = hendl.JProgressBar({id : "pbar0", count : 0, color : "default", message : "Total de linhas encontradas"}).create("div_pbar0");
//     		$pbar[1] = hendl.JProgressBar({id : "pbar1", count : 1, color : "success", message : "Processados com sucesso"}).create("div_pbar1");
//     		$pbar[2] = hendl.JProgressBar({id : "pbar2", count : 2, color : "failed",  message : "Não processados"}).create("div_pbar2");

			$("#btnRun").on('click', function(evt) {

				evt.preventDefault();

				$("#div_response").html("");
// 				$pbar[0].init();
// 				$pbar[1].init();
// 				$pbar[2].init();

        		var $data, $objHtml, $percSuccess, $percFailed;

        		$data = {
        	    	"id_empresa"       : document.getElementById("id_empresa").value,
        	    	"periodo_apuracao" : document.getElementById("periodo_apuracao").value,
        	    	"linhaInicioJDE"   : document.getElementById("linhaInicioJDE").value,
        	    	"linhaInicioLINX"  : document.getElementById("linhaInicioLINX").value
        	    };

        		$objHtml = {
        	    	"context" : $("#div_response"),
        	    	"message" : "<img src=\"{{asset('assets/img/loading_16.gif')}}\">Conciliando arquivos, <strong> por favor, aguarde ...</strong>"
    			};

    			$ajax.call("POST", "{!! route('conciliacaovendas.validar') !!}", $data, function ($grid) {

//    				$pbar[0].update(data.progress.todos, data.progress.todos);
//     				$pbar[1].update(data.progress.success, data.progress.todos);
//     				$pbar[2].update(data.progress.failed, data.progress.todos);

        			$(document).ready(function() {

        				$("#grid-criticas").dataTable({
        					"ordering": true, // Disables control ordering (sorting) abilities
        					"processing": true, // Enables control processing indicator.
        					"responsive": true, // Enables and configure the Responsive extension for tables layout for different screen sizes
        					"searching": true, // Enables control search (filtering) abilities
        					"searchHighlight": true,
        					"columnDefs": [
        						{
//        						"targets": [ 18 ],
        						"searchable": false,
        						"orderable": false
        						},
        						{
//        						"targets": [ 0,7,9,11,13,14,15,16,17 ],
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
        								columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
        							}
        						},
        					],

        				});

        				$("#grid-criticas2").dataTable({
        					"ordering": true, // Disables control ordering (sorting) abilities
        					"processing": true, // Enables control processing indicator.
        					"responsive": true, // Enables and configure the Responsive extension for tables layout for different screen sizes
        					"searching": true, // Enables control search (filtering) abilities
        					"searchHighlight": true,
        					"columnDefs": [
        						{
//        						"targets": [ 18 ],
        						"searchable": false,
        						"orderable": false
        						},
        						{
//        						"targets": [ 0,7,9,11,13,14,15,16,17 ],
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
        								columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
        							}
        						},
        					],

        				});

        			});
        		}, $objHtml);
			});

        });
    </script>
@stop