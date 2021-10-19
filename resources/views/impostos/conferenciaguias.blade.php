@extends('...layouts.master')
@section('content')

    <div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-12">
                <h2 class="sub-title">{!! Form::label('periodo_apuracao', 'Período de busca', ['class' => 'control-label'] )  !!} </h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <div>
                        <label class="control-label" for="tributo">Tributo:</label><br />
                        <select name="tributo" id="tributo" class="form-control" required="required">
                            <option value="">Selecione</option>
													<?php
													foreach ($tributos as $t => $value) {
														$selected = '';
														if(isset($_GET['tributo']) && $_GET['tributo'] == $t){
														    $selected = 'selected';
                                                        }
														echo '<option value="' . $t . '" '.$selected.'>' . $value . '</option>';
													}
													?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <div>
                        <label class="control-label" for="uf">UF:</label><br />
                        <select name="uf" id="uf" class="form-control" required="required">
                        <?php
                        foreach ($uf as $t => $value) {
	                        $selected = '';
	                        if(isset($_GET['uf']) && $_GET['uf'] == $value){
		                        $selected = 'selected';
	                        }
                            echo '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
                        }
                        ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <div>
                        <label class="control-label" for="periodo_apuracao">Período de Apuração:</label><br />
                        <input type="month" id="periodo_apuracao" name="apuracao" class="form-control"
                               value="<?php echo isset($_GET['periodo'])? $_GET['periodo'] : ''; ?>" required="required">
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <br/>
                <input class="btn btn-success-block" type="submit" value="Filtrar" id="filtrar" style="margin-top: 0.5rem;">
            </div>
        </div>
        <hr>
        <div class="row" <?php echo empty($mostrartabela)? 'style="display: none;"' : ''; ?>>
            <div class="col-md-12" style="margin-bottom: 1rem;">
                <button type="button" class="btn btn-success" onclick="aprovarSelecionados()"> Aprovar Selecionados </button>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalConferencia"
                        onclick="getReprovarSelecionados()"> Reprovar Selecionados </button>
                <button type="button" class="btn btn-primary" onclick="downloadArquivos()"> Download Selecionados </button>
                <a href="#" id="downloadZip" download style="display: none;">Download</a>
            </div>
            <div class="col-md-12" style="margin-bottom: 1rem;">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="selecionar_todos" class="selecionar-todos" value="1"> Selecionar todos
                    </label>
                </div>
            </div>
            <table class="table table-bordered display" id="dataTables-example" style="width: 100%; font-size: 10px;">
                <thead>
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>Tributo</th>
                    <th>P.A</th>
                    <th>Referência</th>
                    <th>UF</th>
                    <th>CNPJ</th>
                    <th>I.E</th>
                    <th>Filial</th>
                    <th>Valor Guia</th>
                    <th>Vencto Guia</th>
                    <th>Data Importação</th>
                    <th>Analista</th>
                    <th>Download</th>
                    <th>Aprova?</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach($conferenciaGuias as $value):
                $explode = explode('_', str_replace(['.pdf','.PDF'], '', $value->nome_arquivo));
                $pasta = 'impostos/'.$pasta_empresa.'/'.$value->usuario_analista_id.'/'.$explode[2].'_'.$explode[3].'_'.$explode[4].'/';
                $arquivo = $value->nome_arquivo;
                ?>
                <tr id="del<?=$value->id?>">
                    <td><input type="checkbox" class="guias" name="guias[]" value="<?=$value->id?>" data-pasta="<?=$pasta?>" data-arquivo="<?=$arquivo?>"></td>
                    <td><?=$value->atividade_id?></td>
                    <td><?php
                            $tributo_nome = $value->tributo_nome;
                            if($value->tributo_nome != $explode[2]){
	                            $tributo_nome .= ' '. $explode[2];
                            }
                      echo $tributo_nome;
                      ?></td>
                    <td><?=substr($value->periodo_apuracao, 0, 2)?>/<?=substr($value->periodo_apuracao, 2)?></td>
                    <td><?=empty($value->guiaicms_referencia) ? '-' : $value->guiaicms_referencia?></td>
                    <td><?=$value->uf?></td>
                    <td><?php if (!empty($value->zfic)) {
												echo $value->guiaicms_cnpj;
											} else {
												echo $value->cnpj_estabelecimento;
											}
											?></td>
                    <td><?php if (!empty($value->zfic)) {
												echo $value->guiaicms_ie;
											} else {
												echo $value->ie_estabelecimento;
											}
											?></td>
                    <td><?=$value->codigo_estabelecimento?></td>
                    <td><?=empty($value->zfic) ? '-' : 'R$ ' . $value->guiaicms_vlr_total?></td>
                    <td><?=empty($value->zfic) ? '-' : date('d/m/Y', strtotime($value->guiaicms_data_vencto))?></td>
                    <td><?=date('d/m/Y', strtotime($value->data_importacao))?></td>
                    <td><?=$value->nome_usuario_analista?></td>
                    <td>
                        <a href="/<?=$pasta.$arquivo?>"
                           download="<?=$arquivo?>"
                           target="_blank" style="margin-left:10px"
                           class="btn btn-default btn-default btn-sm"><i
                                    class="fa fa-file-text-o" aria-hidden="true"></i> Download</a>
                    </td>
                    <td id="aprovarReprovar<?=$value->id?>">
                        <a href="javascript:void(0)" style="margin-left:10px;color:#ffffff;" class="btn btn-success btn-default btn-sm" onclick="aprovar('<?=$value->id?>')">
                            Aprovar
                        </a>
                        <a href="javascript:void(0)" style="margin-left:10px;color:#ffffff;" class="btn btn-danger btn-default btn-sm"
                           data-toggle="modal" data-target="#modalConferencia" onclick="getAtividadeId(<?=$value->id?>,<?=$value->atividade_id?>)">
                            Reprovar
                        </a>
                    </td>
                </tr>
								<?php endforeach; ?>
                </tbody>
            </table>
            <br>
        </div>
    </div>

    <div class="modal fade bs-example-modal-sm" id="modalConferencia" tabindex="-1" role="dialog" aria-labelledby="modalConferenciaLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConferenciaLabel">Reprovar: <span id="atividadeid" style="font-weight: bold;">123</span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            {!! Form::open([
                    'id' => 'formReprovar',
                    'enctype'=>'multipart/form-data'
                    ]) !!}
                            <input type="hidden" name="conferencia_tributo" id="conferencia_tributo" value="<?php echo isset($_GET['tributo'])? $_GET['tributo'] : ''; ?>" />
                            <input type="hidden" name="conferencia_uf" id="conferencia_uf" value="<?php echo isset($_GET['uf'])? $_GET['uf'] : ''; ?>" />
                            <input type="hidden" name="conferencia_periodo" id="conferencia_periodo" value="<?php echo isset($_GET['periodo'])? $_GET['periodo'].'-01' : ''; ?>" />
                            <input type="hidden" name="idconferenciaguias" id="idconferenciaguias" />
                            <div class="form-group">
                                <label for="observacao">Observação</label>
                                <textarea class="form-control" rows="3" name="observacao" id="observacao"></textarea>
                            </div>
                            <div class="alert alert-warning alert-dismissible" role="alert" id="alerta-mensagem" style="display: none;">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <strong>Atenção!</strong> <span id="mensagem"></span>
                            </div>
                            <button type="submit" class="btn btn-primary" id="btnReprovar"> Reprovar </button>
                            <div class="alert alert-info" role="alert" id="loadingEnviando" style="margin-top:2rem;display: none;">
                                <img src="/assets/img/loading.gif" style="width: 30px;" /><strong> Enviando e-mails...</strong>
                            </div>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
			$('#sidebar').toggleClass('active');
			$('#sidebarCollapse').toggleClass('auto-left');
			$('#content').toggleClass('auto-left');
			$('select').select2();
			$(document).ready(function () {
				$('#dataTables-example').dataTable({
                    "paging":   false,
                    "ordering": false,
                    "info":     false
                });
			});

			function aprovarSelecionados() {
				var selecionados = document.querySelectorAll('input:checked');

                var arr = [];
                for(i=0; i <= selecionados.length-1; i++){
	                if(selecionados[i].getAttribute('class') == 'guias'){
		                arr.push(selecionados[i].value);

                        $('#del'+selecionados[i].value).css('background-color','#daffeb');
                        $('#aprovarReprovar'+selecionados[i].value).html(`<img src="/assets/img/loading.gif" style="width: 20px;" />`);
	                }
                }
                var filtered = arr.filter(function (el) {
                    return el != null;
                });
                if(filtered.length === 0){
                    alert('Você precisa selecionar pelo menos uma guia.');
                    return false;
                }
                var id = filtered.join(',');
                aprovar(id, arr);
			}

			function aprovar(id, arr=[]){
				if(arr.length == 0){
                    arr.push(id);
                }
                var conferencia_tributo = $('#conferencia_tributo').val();
                var conferencia_uf = $('#conferencia_uf').val();
                var conferencia_periodo = $('#conferencia_periodo').val();
                $('#del'+id).css('background-color','#daffeb');
                $('#aprovarReprovar'+id).html(`<img src="/assets/img/loading.gif" style="width: 20px;" />`);
                $.ajax({
                    type: "POST",
                    headers:
                        {
                            'X-CSRF-Token': $('input[name="_token"]').val()
                        },
                    url: '{{ url('impostos') }}/aprovarguias',
                    dataType: "json",
                    data: {id: id, conferencia_tributo: conferencia_tributo, conferencia_uf: conferencia_uf, conferencia_periodo: conferencia_periodo},
                    success: function (data) {
                        // console.log(data)
                        if(data.success == true){
                            arr.forEach(function(value){
                                $('#del'+value).fadeOut('slow');
                            });
                        }
                        if(data.success == false){
                            $('#alerta-mensagem').show();
                            $('#mensagem').html(data.message)
                        }
                    },
                    error: function (request) {
                        console.log(request.responseText)
                    }
                });
            }

			$('#formReprovar').submit(function(){
				var formData = new FormData($(this)[0]);
				$('#btnReprovar').hide();
				$('#loadingEnviando').show();
				$.ajax({
					type: "POST",
					headers:
						{
							'X-CSRF-Token': $('input[name="_token"]').val()
						},
					url: '{{ url('impostos') }}/reprovarguias',
					dataType: "json",
					data: formData,
					processData: false,
					contentType: false,
					success: function (data) {
						console.log(data)
						if(data.success == true){
							data.arr.forEach(function(value){
								$('#del'+value).fadeOut('slow');
							});
							$('#modalConferencia').modal('hide');
                            $('#btnReprovar').show();
                            $('#loadingEnviando').hide();
						}
						if(data.success == false){
							$('#alerta-mensagem').show();
							$('#mensagem').html(data.message);
                            $('#btnReprovar').show();
                            $('#loadingEnviando').hide();
						}
					},
					error: function (request) {
						console.log(request.responseText)
					}
				});
				return false;
			});

			function getAtividadeId(idconferenciaguias,atividadeid){
				$('#atividadeid').text(atividadeid)
				$('#idconferenciaguias').val(idconferenciaguias)
                $('#observacao').val('');
			}

			function getReprovarSelecionados() {
                $('#observacao').val('');
				document.getElementById('atividadeid').innerText = 'Selecionados';
				var selecionados = document.querySelectorAll('input:checked');

				var arr = [];
				for(i=0; i <= selecionados.length-1; i++){
                    if(selecionados[i].getAttribute('class') == 'guias'){
					    arr.push(selecionados[i].value);
                    }
				}
				var filtered = arr.filter(function (el) {
					return el != null;
				});
				if(filtered.length === 0){
					alert('Você precisa selecionar pelo menos uma guia.');
                    setTimeout(function(){$('#modalConferencia').modal('hide')},500);
					return false;
				}
				document.getElementById('idconferenciaguias').value = filtered.join(',');
				$('#modalConferencia').modal('hide');
			}

			function downloadArquivos() {
				var selecionados = document.querySelectorAll('input:checked');

				var arr = [];
				for(i=0; i <= selecionados.length-1; i++){
                    if(selecionados[i].getAttribute('class') == 'guias'){
					    arr.push({pasta: selecionados[i].getAttribute('data-pasta'), arquivo: selecionados[i].getAttribute('data-arquivo')});
                    }
				}
				if(arr.length === 0){
					alert('Você precisa selecionar pelo menos uma guia.');
					return false;
				}
				var filtered = arr.filter(function (el) {
					return el.pasta != null;
				});
				// console.log(filtered);

				$.ajax({
					type: "POST",
					headers:
						{
							'X-CSRF-Token': $('input[name="_token"]').val()
						},
					url: '{{ url('impostos') }}/conferenciaguiasdownload',
					dataType: "json",
					data: {data: filtered},
					success: function (data) {
						// console.log(data)
                      if(data.success === true){
                      	$('#downloadZip').attr('href', data.arquivo);
                      	$('#downloadZip').attr('download', data.arquivo);
                      	document.getElementById('downloadZip').click();

                      	// colocar time
	                      setTimeout(function(){
                              $.get( "{{ url('impostos') }}/conferenciaguiasdownloaddelete/"+data.excluir, function( data ) {
                                  console.log(data)
                              });
                          }, 20000);
                      }
					},
					error: function (request) {
						console.log(request.responseText)
					}
				});
			}

			$('.selecionar-todos').change(function(){
				if($(this).is(":checked")){
					$('input:checkbox').prop("checked", true);
				}else{
					$('input:checkbox').prop("checked", false);
				}
			});

			$('#filtrar').click(function(){
				var trib = $('#tributo').val();
				var uf = $('#uf').val();
				var periodo = $('#periodo_apuracao').val();

                window.location.href = `/impostos/conferenciaguias?tributo=${trib}&uf=${uf}&periodo=${periodo}`;
				// console.log(`/impostos/conferenciaguias?tributo=${trib}&uf=${uf}&periodo=${periodo}`)
            });
    </script>
@stop