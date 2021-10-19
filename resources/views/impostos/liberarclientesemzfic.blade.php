@extends('...layouts.master')

@section('content')

<link href="{{ URL::to('/') }}/assets/bootstrap-tagsinput/bootstrap-tagsinput.css" rel="stylesheet">
<script src="{{ URL::to('/') }}/assets/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
    <div class="row">
        <div class="col-md-12">
            <h1 class="sub-title">Liberar para Cliente sem Zfic</h1>
        </div>
    </div>

    {!! Form::open([
    'id' => 'enviarclientesemzfic',
    'enctype'=>'multipart/form-data'
    ]) !!}
    <div class="row">
        <div class="col-sm-2">
            <div class="form-group">
                <div>
                    <label class="control-label" for="tributo">Tributo:</label>
                    <select name="tributo" id="tributo" class="form-control" required="required">
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
        <div class="col-sm-3">
            <div class="form-group">
                <div>
                    {!! Form::label('periodo_apuracao', 'Período de Apuração:', ['class' => 'control-label']) !!}
                    <input type="month" id="periodo_apuracao" name="apuracao" class="form-control" required="required">
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-4">
                <label>Informe os e-mails: </label>
            <div class="form-group">
                <input type="text" name="emails" id="emails" class="form-control emails" data-role="tagsinput" required="required" />
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <button class="btn btn-success" style="margin-top:2rem;" type="submit" id="btnEnviar"> Enviar </button>

                <div class="alert alert-info" role="alert" id="loadingEnviando" style="margin-top:2rem;display: none;">
                    <img src="/assets/img/loading.gif" style="width: 30px;" /><strong> Enviando e-mails...</strong>
                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}

</div>
<script>
	jQuery(function ($) {
        $('.emails').tagsinput();

        $('#enviarclientesemzfic').submit(function(){
        	var data = new FormData($(this)[0]);
        	$('#btnEnviar').hide();
        	$('#loadingEnviando').show();
	        $.ajax({
		        type: "POST",
		        headers:
			        {
				        'X-CSRF-Token': $('input[name="_token"]').val()
			        },
		        url: '{{ url('impostos') }}/enviarclientesemzfic',
		        dataType: "json",
		        data: data,
		        processData: false,
		        contentType: false,
		        success: function (data) {
			        // console.log(data)
			        if (data.success === true) {
                        // colocar time
                        $.get("{{ url('impostos') }}/rodaratividadesjob", function( data ) {
                        	console.log('rodaratividadesjob: ' + data);
                        });
                        setTimeout(function(){
                            $('#btnEnviar').show();
                            $('#loadingEnviando').hide();
	                        alert('E-mails enviado com sucesso!');
	                        document.location.reload(true);
                        }, 20000);
			        }
			        if (data.success === false) {
                        $('#btnEnviar').show();
                        $('#loadingEnviando').hide();
			        }
		        },
		        error: function (request) {
			        console.log(request.responseText)
                    $('#btnEnviar').show();
                    $('#loadingEnviando').hide();
		        }
	        });
        	return false;
        });
    });
</script>
@stop