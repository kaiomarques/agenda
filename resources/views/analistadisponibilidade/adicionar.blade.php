@extends('layouts.master')

@section('content')

    @include('partials.alerts.errors')

    @if(Session::has('alert'))
        <div class="alert alert-danger">
            {!! Session::get('alert') !!}
        </div>

    @endif

    <?php if (@!empty($status)) { ?>
    <div class="alert alert-success">
        <?php echo $message; ?>
    </div>
    <?php } ?>

    <?php if (@!empty($error)) { ?>
    <div class="alert alert-danger">
        <?php echo $message; ?>
    </div>
    <?php } ?>

    <h1>Cadastro de Disponibilidade do Analista</h1>
    <hr>
    {!! Form::open([
        'route' => 'analistadisponibilidade.store'
    ]) !!}

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('empresa_id', 'Empresa', ['class' => 'control-label'] )  !!}
            {!!  Form::select('empresa_id', $empresas, array(), ['class' => 'form-control s2']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('id_usuarioanalista', 'Analista', ['class' => 'control-label'] )  !!}
            {!!  Form::select('id_usuarioanalista', $analistas, array(), ['class' => 'form-control s2_multi']) !!}
        </div>
    </div>
	
	<div class="form-group">
        <div style="width:50%">			
			{!! Form::label('periodo_apuracao', 'Período de apuração', ['class' => 'control-label']) !!}
			{!! Form::text('periodo_apuracao',null, ['class' => 'form-control','style' => 'width:80px']) !!}
        </div>
    </div>
    <div class="row" style="margin-bottom:15px">
        <div class="col-md-2">     
            {!! Form::label('data_ini_disp', 'Data Inicial', ['class' => 'control-label']) !!}    
            {!! Form::date('data_ini_disp', '', ['class' => 'form-control']) !!}
        </div>
        <div class="col-md-2">         
            {!! Form::label('data_fim_disp', 'Data Final', ['class' => 'control-label']) !!}
            {!! Form::date('data_fim_disp', '', ['class' => 'form-control']) !!}
        </div>
    </div>
 	<div class="form-group">
        <div style="width:50%">			
			{!! Form::label('qtd_min_disp_dia', 'Quantidade de minutos', ['class' => 'control-label']) !!}
			{!! Form::text('qtd_min_disp_dia',null, ['class' => 'form-control','style' => 'width:80px']) !!}
        </div>
    </div>

    <br>
    {!! Form::submit('Adicionar', ['class' => 'btn btn-default enviar']) !!}
    {!! Form::close() !!}
    <hr/>


    <script type="text/javascript">
        $('select').select2();
			
        jQuery(function($){
            $('input[name="periodo_apuracao"]').mask("99/9999");
            $('input[name="cnpj"]').mask("99.999.999/9999-99");
        });

        $("#empresa_id, #id_usuarioanalista, #periodo_apuracao").change(function() {
            if($("#empresa_id").val() != '' && $("#id_usuarioanalista").val() != '' && $("#periodo_apuracao").val() != '' ) {
                $.get(
                    "{{ url('/analista_disponibilidade_ajax')}}",
                    { 
                        empresa_id: $("#empresa_id").val(),
                        id_usuarioanalista: $("#id_usuarioanalista").val(),
                        periodo_apuracao: $("#periodo_apuracao").val()
                    },
                    function(data) {
                        if(Object.keys(data).length > 0) {
                            $("#data_ini_disp").val(data.data_ini_disp);
                            $("#data_fim_disp").val(data.data_fim_disp);
                            $("#qtd_min_disp_dia").val(data.qtd_min_disp_dia);

                            $(".enviar").prop('disabled', true);
                        } else {
                            $("#data_ini_disp").val('');
                            $("#data_fim_disp").val('');
                            $("#qtd_min_disp_dia").val('');

                            $(".enviar").prop('disabled', false);
                        }
                    }
                );
            }
        });


    </script>
@stop