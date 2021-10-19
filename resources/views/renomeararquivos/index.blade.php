@extends('layouts.master')

@section('content')

    @include('partials.alerts.errors')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <script src="{{ URL::to('/') }}/assets/js/hendl.js"></script>

    <div id="alert-status" class="alert alert-success alertMessage" style="display:none"></div>

    <h1>Renomear Arquivos</h1>
    <hr>
    <div class="form-group">
        <input type="hidden" name="id_empresa" id="id_empresa" value="{{$id_empresa}}" />
    </div>

    <div class="form-group">
        {!! Form::label('select_imposto', 'Imposto', ['class' => 'control-label']) !!}
        {!! Form::select('select_imposto', $tipos_tributos, array(), ['class' => 'form-control s2_multi', 'id' => 'tipo_tributo']) !!}
    </div>

    <div class="form-inline">
        <input type="button" id="ButtonEmpresas" class="btn btn-default" value="Iniciar" />
    </div>
    <br/>
	<h6 class="current_label"></h6>
	<div id="div_pbar0"></div>
	<div id="div_pbar1"></div>
	<div id="div_pbar2"></div>
    <div id="alert-danger" class="alert alert-danger alertMessage" style="display:none"></div>
	<div id="div_response" style="overflow:auto; height:154px;"></div>
    <script>

    	var global_id = null;
    	var _token    = $('meta[name="csrf-token"]').attr('content');
		var $ajax     = new hendl.Ajax(_token);
		var $pbar     = [];

    	$(document).ready(function () {

    		$pbar[0] = hendl.JProgressBar({id : "pbar0", count : 0, color : "default", message : "Total de arquivos encontrados"}).create("div_pbar0");
    		$pbar[1] = hendl.JProgressBar({id : "pbar1", count : 1, color : "success", message : "Processados com sucesso"}).create("div_pbar1");
    		$pbar[2] = hendl.JProgressBar({id : "pbar2", count : 2, color : "failed",  message : "Não processados"}).create("div_pbar2");

			$("#ButtonEmpresas").on('click', function(evt) {

				evt.preventDefault();

				$("#div_response").html("");
				$pbar[0].init();
				$pbar[1].init();
				$pbar[2].init();

        		var $data, $objHtml, $percSuccess, $percFailed;

        		$data = {
        	    	"id_empresa"   : document.getElementById("id_empresa").value,
        	    	"tipo_tributo" : document.getElementById("tipo_tributo").value
        	    };

        		$objHtml = {
        	    	"context" : $("#div_response"),
        	    	"message" : "<img src=\"{{asset('assets/img/loading_16.gif')}}\">Renomeando arquivos, <strong> por favor, aguarde ...</strong>"
    			};

    			$ajax.call("POST", "{!! route('renomear_arquivos') !!}", $data, function (data) {

    				$pbar[0].update(data.progress.todos, data.progress.todos);
    				$pbar[1].update(data.progress.success, data.progress.todos);
    				$pbar[2].update(data.progress.failed, data.progress.todos);

        			$objHtml.context.html(["<pre>", data.message, "</pre>"].join(""));

        		}, $objHtml);
			});

        });

    </script>
@stop