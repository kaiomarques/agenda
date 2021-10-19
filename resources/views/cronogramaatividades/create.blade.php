@extends('layouts.master')

@section('content')

    @include('partials.alerts.errors')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

    <div id="alert-status" class="alert alert-success alertMessage" style="display:none"></div>

    <h1>Geração de um cronograma de uma atividade</h1>
    <hr>
    <div class="form-group">
        {!! Form::label('select_empresa', 'Empresas', ['class' => 'control-label'] )  !!}
        {!!  Form::select('select_empresa', $empresas, array(), ['class' => 'form-control s2_multi', 'id' => 'empresa']) !!}
    </div>

    <!--div class="form-group">
    {!! Form::label('multiple_select_estabelecimentos[]', 'Estabelecimentos', ['class' => 'control-label'] )  !!}
    {!!  Form::select('multiple_select_estabelecimentos[]', $estabelecimentos, array(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple', 'id' => 'multiple_estab']) !!}
            </div>

            <div class="form-group">
{!! Form::label('select_tributos', 'Tributo (estabelecimentos)', ['class' => 'control-label'] )  !!}
    {!!  Form::select('select_tributos[]', $tributos, array(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple', 'id' => 'select_tributo']) !!}
            </div-->

    <div class="form-group">
        {!! Form::label('periodo_apuracao', 'Periodo Apuração', ['class' => 'control-label']) !!}
        {!! Form::text('periodo_apuracao',null, ['class' => 'form-control','style' => 'width:90px', 'placeholder'=>'MM/AAAA', 'required']) !!}
    </div>

    <div class="form-inline">
        <input type="button" id="ButtonEmpresas" class="btn btn-default" value="Criar cronograma" />
        <div class="alert alert-primary" role="alert" id="processMessageCrono"><img
                src="{{asset('assets/img/loading_16.gif')}}"><strong> Aguarde, </strong> Cronograma de Atividades em processamento...
        </div>
    </div>
    <br/>

	<h6 class="current_label"></h6>
	<div class="progress bloco-progresso" style="width:100%;display:none;">
		<div class="progress-bar 1 progress-bar-striped active" role="progressbar"
			aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width:0">
		</div>
	</div>
	<div class="progress bloco-progresso" style="width:100%;display:none;">
		<div class="progress-bar 2 progress-bar-striped active" role="progressbar"
			aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width:0; background-color: #33b77e !important">
		</div>
	</div>
	<div class="progress bloco-progresso" style="width:100%;display:none;">
		<div class="progress-bar 3 progress-bar-striped active" role="progressbar"
			aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width:0; background-color: #b73333 !important">
		</div>
    </div>
    
    <div id="recreateSchedule" class="recreateSchedule">
        <div id="alert-warning" class="alert alert-warning alertMessage" style="display:none"></div>
        <div id="alert-success" class="alert alert-success alertMessage" style="display:none"></div>
        <p>Você deseja <strong>recriar este Cronograma</strong> para o periodo de apuração (<strong><span class="periodoApuracao"></span></strong>) informado? </p>
        <div id="alert-info" class="alert alert-info" style="display: block">
            <small><i class="fa fa-info-circle" aria-hidden="true"></i><strong> Observação: </strong>Se optar por recriar o cronograma, todos os dados do período (<strong><span class="periodoApuracao"></span></strong>) serão <strong>EXCLUÍDOS!</strong><p class="text-danger"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><strong> Esta é uma ação que não possui retorno, portanto, use com cuidado.</strong></p></small>
        </div>
        <div class="form-inline">
            <input type="button" id="btnRecreateSchedule" class="btn btn-warning" value="Recriar Cronograma" />
            <div class="alert alert-primary" role="alert" id="processMessage"><img src="{{asset('assets/img/loading_16.gif')}}"><strong> Aguarde, </strong>realizando limpeza do Cronograma...</div>
        </div>
    </div>
    <div id="alert-danger" class="alert alert-danger alertMessage" style="display:none"></div>
    <script>

        $(document).ready(function () {
			// insistent_call();
			
            jQuery(function($){
                $('input[name="periodo_apuracao"]').mask("99/9999");
                $('input[name="cnpj"]').mask("99.999.999/9999-99");
            });

			var global_id = null;
            var _token = $('meta[name="csrf-token"]').attr('content');

            function insistent_call() {
                dados = {'id': global_id};
                $.ajax({
                    headers: {
                        // 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        'X-CSRF-TOKEN': _token
                    },
                    cache:true,
                    type: 'GET',
                    url:"{!! route('cronogramaatividades.checarCronogramaEmProgresso') !!}",
                    dataType: "json",
					data: dados,
                    success: function(data) {
                        // console.log('dados insistentes: ', data);
                        if(typeof data !== 'undefined' && Object.keys(data).length > 0) {
							$(".bloco-progresso").show();
							id                  = data.id;
							periodo_apuracao    = data.periodo_apuracao;
							tipo_periodo        = data.tipo_periodo;
							qtd                 = data.qtd;
							emp_id              = data.emp_id;
							qtd_realizados      = data.qtd_realizados;
							qtd_mensal      	= data.qtd_mensal;
							qtd_priority      	= data.qtd_priority;
							created_at          = data.created_at;
							porcentagem_realizados	= data.porcentagem_realizados;
							porcentagem_mensal		= data.porcentagem_mensal;
							porcentagem_priority	= data.porcentagem_priority;
							nome_empresa		= data.nome_empresa;
							status				= data.status;
							global_id = id;
							label = "Empresa: <h3>"+nome_empresa+"</h3><br/>Período: "+periodo_apuracao+" | Data de geração: "+created_at;
          
							if(status != 1) {
                                $(".current_label").html("");
                                $('#recreateSchedule').hide();
                                $('#alert-warning').hide();
								$(".current_label").html(label);
								$(".current_label").show();
								$(".progress-bar.1").css('width', porcentagem_realizados + "%").html("Gerando itens no cronograma (" + porcentagem_realizados + "%)");
								$(".progress-bar.2").css('width', porcentagem_mensal + "%").html("Listando informações do mês (" + porcentagem_mensal + "%)");
								$(".progress-bar.3").css('width', porcentagem_priority + "%").html("Sequenciamento dos prazos das atividades (" + porcentagem_priority + "%)");
							} else {
								global_id = null;
                                clearInterval(funcao_insistent_call);
                                $(".current_label").hide();
                                $('#processMessageCrono').hide();
                                $('#processMessage').hide();
								$(".bloco-progresso").hide();
                                $('#recreateSchedule').hide();
                                $('#alert-warning').hide();
                                $('#alert-status').removeClass('alert-warning');
                                $('#alert-status').addClass('alert-success');
								$('#alert-status').html('Geração concluída para o período '+periodo_apuracao).fadeIn();
							}
						}
                    }
                });
            }

			$("#ButtonEmpresas").on('click', function(evt) {
                evt.preventDefault();
                if ($('#periodo_apuracao').val() == "") {
                    $('#alert-status').html();
                    $('#alert-status').removeClass('alert-success');
                    $('#alert-status').addClass('alert-warning');
                    $('#alert-status').html('O campo <strong>Período de Apuração</strong> é obrigatório!').slideDown().delay(4000).slideUp();
                    $('#alert-status').html();
                    $('#alert-danger').html();
                    $('#periodo_apuracao').css({'border-color':'#dc3545', 'color': '#dc3545'});
                } else {
                    $('#periodo_apuracao').css({'border-color':'#ccc', 'color': '#555'});
                    $('#alert-status').fadeOut();
                    $('#alert-danger').fadeOut();
                    dados = {'select_empresa': $('#empresa').val(), 'periodo_apuracao': $('#periodo_apuracao').val()};
                    // console.log('Dados form: ', dados);
                    $.ajax({
                        headers: {
                            // 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            'X-CSRF-TOKEN': _token
                        },
                        cache: true,
                        type: 'POST',
                        url:"{!! route('cronogramaatividades.storeEmpresa') !!}",
                        dataType: 'json',
                        data: dados,
                        beforeSend : function () {
                            $('#processMessageCrono').show();
                            funcao_insistent_call = setInterval(insistent_call, 2000);
                        },
                        success: function (data) {
                            // console.warn('data storeEmpresa: ', data);
                            if(data.sucesso === false) {
                                global_id = null;
                                clearInterval(funcao_insistent_call);
                                $(".current_label").hide();
                                $(".bloco-progresso").hide();
                                $('#alert-warning').html();
                                $('#alert-warning').html(data.mensagem).slideDown().delay(10000).slideUp();
                                $('#recreateSchedule').show();
                                $('.periodoApuracao').html(data.periodo);
                            }
                            $('#processMessageCrono').hide();
                        },
                        error: function (request, status, error) {
                            $('#alert-danger').html('<strong>Erro gerado durante a consulta: </strong><br>'+request.responseText).show();
                        }
                    });
                }
            });
            
            // Desvio para poder recriar o Cronograma de Atividades
			$("#btnRecreateSchedule").on('click', function(evt) {
                evt.preventDefault();
                if ($('#periodo_apuracao').val() == "") {
                    $('#alert-status').html();
                    $('#alert-status').removeClass('alert-success');
                    $('#alert-status').addClass('alert-warning');
                    $('#alert-status').html('O campo <strong>Período de Apuração</strong> é obrigatório!').slideDown().delay(4000).slideUp();
                    $('#alert-status').html();
                    $('#periodo_apuracao').css({'border-color':'#dc3545', 'color': '#dc3545'});
                } else {
                    $('#periodo_apuracao').css({'border-color':'#ccc', 'color': '#555'});
                    $('#alert-status').fadeOut();
                    dados = {'emp_id': $('#empresa').val(), 'periodo_apuracao': $('#periodo_apuracao').val()};
                    // console.warn('ZZZZZZ: ',dados)
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': _token
                        },
                        cache: true,
                        type: 'POST',
                        url:"{!! route('cronogramaatividades.clearActivitiesSchedule') !!}",
                        dataType: 'json',
                        data: dados,
                        beforeSend : function () {
                            $('#processMessage').show();
                        },
                        success: function (data) {
                            // console.warn('dataLimpeza: ', data);
                            if(data.success === true && data.errorType == 'info') {
                                $(".current_label").hide();
                                $('#processMessage').html();
                                $('#processMessage').removeClass('alert-primary');
                                $('#processMessage').addClass('alert-success');
                                $('#processMessage').html(data.message).slideDown().delay(5000).slideUp();
                                $('#recreateSchedule').show();
                                setTimeout(() => {
                                    $('#processMessageCrono').show();
                                    $("#ButtonEmpresas").click();
                                    $('#recreateSchedule').hide();
                                }, 3000);
                            } else {
                                $(".current_label").hide();
                                $('#processMessage').html();
                                $('#processMessage').removeClass('alert-primary');
                                $('#processMessage').addClass('alert-warning');
                                $('#processMessage').html(data.message).slideDown().delay(5000).slideUp();
                                $('#recreateSchedule').show();
                                $('#processMessageCrono').hide();
                                $('#processMessage').hide();
                            }
                        },
                        error: function (request, status, error) {
                            $('#alert-danger').html('<strong>Erro gerado durante a consulta: </strong><br>'+request.responseText).slideDown().delay(10000).slideUp();
                        }
                    });
                }
            });
        });

    </script>

@stop


