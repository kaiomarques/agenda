@extends('...layouts.master')

@section('content')

    <link href="{{ URL::to('/') }}/assets/css/jquery-upload/jquery.filer.css" rel="stylesheet">
    <link href="{{ URL::to('/') }}/assets/css/jquery-upload/themes/jquery.filer-dragdropbox-theme.css" rel="stylesheet">
    <script src="{{ URL::to('/') }}/assets/js/jquery-upload/jquery.filer.min.js"></script>

    <h1>Importar Guias</h1>
    <div class="row">
        <div class="col-md-6">
            <p class="lead">Importar guias de impostos.</p>
        </div>
    </div>
    <hr/>

    {{--<form id="importarguias" method="post" enctype="multipart/form-data">--}}
    {!! Form::open([
    'id' => 'importarguias',
    'enctype'=>'multipart/form-data'
    ]) !!}
    <div class="row">
        <div class="col-sm-2">
            <div class="form-group">
                <div>
                    <label class="control-label" for="tributo">Tributo:</label>
                    <select name="tributo" id="tributo" class="form-control">
                        <option value="">Selecione</option>
											<?php
											foreach ($tributos as $t => $value) {
												echo '<option value="' . $t . '">' . $value . '</option>';
											}
											?>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <div>
                    {!! Form::label('uf', 'UF:', ['class' => 'control-label']) !!}
                    {!! Form::select('uf', $uf, $uf, ['class' => 'form-control', 'required' => 'required']) !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div>
                    {!! Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']) !!}
                    <input type="month" id="periodo_apuracao" name="apuracao" class="form-control" required>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-5 col-lg-6">
            <div class="form-group">
                <div>
                    <label for="apuracao">Selecione os arquivos:</label>
                    {!! Form::file('guias[]', ['class' => 'form-control', 'multiple' => 'multiple', 'required' => 'required', 'id'=>'guias']); !!}
                </div>
            </div>
        </div>
        <div class="col-md-4">
			<div class="alert alert-info" role="alert" id="loadingValidando" style="display: none;">
				<img src="/assets/img/loading.gif" style="width: 30px;" /><strong> Validando arquivos...</strong>
			</div>
			<div class="alert alert-info" role="alert" id="loadingSalvando" style="margin-top:2rem;display: none;">
				<img src="/assets/img/loading.gif" style="width: 30px;" /><strong> Salvando arquivos...</strong>
			</div>
            <div class="form-group" id="btnEnviarArquivos" style="margin-top:2rem;display: none;">
                <button type="submit" class="btn btn-success">Enviar Arquivos</button>
            </div>
        </div>
    </div>
    {!! Form::close() !!}

    <script>
			jQuery(function ($) {
				$('#guias').filer({
					limit: 40,
					maxSize: 500,
					showThumbs: true,
					captions: {
						button: "Selecionar arquivos",
						feedback: "",
						feedback2: "",
						drop: "Arraste e solte o arquivo aqui para fazer o upload",
						removeConfirmation: "Tem certeza de que deseja remover este arquivo?"
					}
				});

				$('#importarguias').submit(function () {
					$('#btnEnviarArquivos').hide();
					$('#loadingSalvando').show();
					var formData = new FormData($(this)[0]);
					$.ajax({
						type: "POST",
						headers:
							{
								'X-CSRF-Token': $('input[name="_token"]').val()
							},
						url: '{{ url('impostos') }}/importarguias',
						dataType: "json",
						data: formData,
						processData: false,
						contentType: false,
						success: function (data) {
							console.log(data)
							if (data.success === true) {
								alert('Arquivos enviados com sucesso!');
								document.location.reload(true);
							}
							if (data.success === false) {
								$('#btnEnviarArquivos').show();
								$('#loadingSalvando').hide();

								var lista = document.querySelectorAll('.jFiler-item');
								var total = lista.length - 1;
								for (var i = 0; i <= total; i++) {
									if (data.message[lista[i].getAttribute('data-jfiler-index')]) {
										if (lista[i].getAttribute('data-jfiler-index') == data.message[lista[i].getAttribute('data-jfiler-index')].key) {
											lista[i].style.border = '2px solid #cb1515';
											lista[i].innerHTML += `<span style="color:#cb1515;width:100%;display:flex;"><strong>Erro: </strong> ${data.message[lista[i].getAttribute('data-jfiler-index')].message} </span>`;
										}
									}
								}
							}
						},
						error: function (request) {
							console.log(request.responseText)
						}
					});
					return false;
				});

				$('#guias').change(function () {
					$('#loadingValidando').show();
					var formData = new FormData($('#importarguias')[0]);
					$.ajax({
						type: "POST",
						headers:
							{
								'X-CSRF-Token': $('input[name="_token"]').val()
							},
						url: '{{ url('impostos') }}/validarguias',
						dataType: "json",
						data: formData,
						processData: false,
						contentType: false,
						success: function (data) {
							console.log(data)
							if (data.success === false) {
								var lista = document.querySelectorAll('.jFiler-item');
								var total = lista.length - 1;
								for (var i = 0; i <= total; i++) {
								  	if (data.message[lista[i].getAttribute('data-jfiler-index')]) {
										if (lista[i].getAttribute('data-jfiler-index') == data.message[lista[i].getAttribute('data-jfiler-index')].key) {
											lista[i].style.border = '2px solid #cb1515';
											lista[i].innerHTML += `<span style="color:#cb1515;width:100%;display:flex;"><strong>Erro: </strong> ${data.message[lista[i].getAttribute('data-jfiler-index')].message} </span>`;
										}
									}
								}
								$('#loadingValidando').hide();
								$('#btnEnviarArquivos').show();
							}
							if (data.success === true) {
								$('#btnEnviarArquivos').show();
								$('#loadingValidando').hide();
							}
						},
						error: function (request) {
							console.log(request.responseText)
						}
					});
					return false;
				});
			});
    </script>
@stop


<?php /*

 {!! Form::open([
    'route' => 'impostos.importar',
    'files' => 'true'
]) !!}  
<div class="row">
    <div class="col-sm-2">
        <div class="form-group">
            <div style="width:80%">
            {!! Form::label('TRIBUTO', 'Tributo:', ['class' => 'control-label']) !!}
            <select name="slt_tributo" id="slt_tributo" class="form-control" >
                <option value="" selected disabled hidden>Selecione</option>   
                <?php
                foreach($tributos as $t => $value){
                    echo '<option value="'.$t.'_'.$value.'">'.$value.'</option>';
                }
                ?>
            </select>
            </div>
        </div>
    </div>
    <div class="col-sm-2">
        <div class="form-group">
            <div style="width:80%">
                {!! Form::label('municipio', 'UF:', ['class' => 'control-label']) !!}
			    {!! Form::select('municipio', $uf, $uf, ['class' => 'form-control', 'required' => 'required']) !!}
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group">
            <div style="width:70%">
            <label for="apuracao">Período de Apuração:</label>
            <input type="month" id="apuracao" name="apuracao" class="form-control" required>    
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="form-group" style="display: none">
        <div style="width:30%">
        {!! Form::hidden('TRIBUTO_ID', 8, ['class' => 'form-control']) !!}
        </div>
    </div>
</div>
<div class="row"><BR></div>
<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <div style="width:70%">
            <label for="apuracao">Selecione os arquivos:</label>
            {!! Form::file('guias[]', ['class' => 'form-control', 'multiple' => 'multiple', 'required' => 'required']); !!} 
            </div>
        </div>
    </div>
</div>
<div class="row"><BR></div>
<div class="row">
    <div class="col-sm-4">
        <div class="form-group">
            <div style="width:0%">
            <!-- <input type="submit" class="btn btn-default" value="Importar"> -->
        {!! Form::submit('Importar', ['class' => 'btn btn-default']) !!}
            </div>
        </div>
    </div>
</div>
{!! Form::close() !!}
<hr/>

@stop
*/ ?>