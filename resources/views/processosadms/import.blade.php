@extends('layouts.master')

@section('content')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<hr>
{!! Form::open([
    'route' => 'processosadms.action_import',
    'files'=>true,
    'id'=>'form_import', 
    'name' => 'form_import'
]) !!}


<div class="form-group">
    <div style="width:30%">
    {!! Form::label('import_csv', 'Enviar arquivo CSV para realização de import:', ['class' => 'control-label']) !!}
    {!! Form::file('file_csv', null, ['class' => 'form-control']) !!}
    </div>
</div>


{!! Form::button('Importar CSV', ['class' => 'btn btn-default', 'onClick' => "submit_form();"]) !!}
<a href="{{ route('processosadms.index') }}" class="btn btn-default">Voltar</a>

{!! Form::close() !!}
<hr>
@if(Session::has('warning2'))
	<label>{!! Session::get('warning2') !!}</label>
	<div class="alert alert-warning">
		<strong>Totais:</strong> <br>
		Linhas processadas: <strong>{!! Session::get('totalLines') !!}</strong> <br>
		Registros encontrados: <strong>{!! Session::get('totalRegisters') !!}</strong> <br>
		Registros importados: <strong>{!! Session::get('totalRegistersImported') !!}</strong> <br>
		Registros não importados: <strong>{!! Session::get('totalRegistersNotImported') !!}</strong> <br><br>
		<strong>Mensagens de erro identificadas em:</strong> <br>
		@foreach (Session::get('errorMsgArr') as $key => $value)
			{{$value}} <br>
		@endforeach
	</div>
@elseif(Session::has('info2'))
	<label>{!! Session::get('info2') !!}</label>
	<div class="alert alert-info">
		<strong>Totais:</strong> <br>
		Linhas processadas: <strong>{!! Session::get('totalLines') !!}</strong> <br>
		Registros encontrados: <strong>{!! Session::get('totalRegisters') !!}</strong> <br>
		Registros importados: <strong>{!! Session::get('totalRegistersImported') !!}</strong> <br>
		Registros não importados: <strong>{!! Session::get('totalRegistersNotImported') !!}</strong> <br><br>
	</div>
@endif
<hr>
<script>
    function submit_form()
    {
        var formData = new FormData($(document.forms[0])[0]);       
        $.ajax({
            type: 'POST',
            headers:
            {
                'X-CSRF-Token': $('input[name="_token"]').val()
            },
            url: 'action_valid_import',
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(d)
            {
                if (d.success) {
                    
                    if (d.dataApuracaoDiferente) {

                        if (confirm('Existem registros com períodos de apuração diferentes, deseja continuar com a importação?')) {
                            form_import.submit();
                        } else {
                            location.replace('/movtocontacorrentes/import');
                        }

                    } else {
                        form_import.submit();
                    }

                } else {
                    alert(d.mensagem);
                }
            }
        });
    }


</script>
@stop