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
            <table class="table table-bordered display" id="dataTables-example" style="width: 100%; font-size: 10px;">
                <thead>
                <tr>
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
                    <th>Conferência</th>
                    <th>Data Conf.</th>
                    <th>Conferente</th>
                    <th>Ação</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach($conferenciaGuias as $value):
                $explode = explode('_', str_replace(['.pdf','.PDF'], '', $value->nome_arquivo));
                $pasta = 'impostos/'.$value->usuario_analista_id.'/'.$explode[2].'_'.$explode[3].'_'.$explode[4].'/';
                $arquivo = $value->nome_arquivo;
                ?>
                <tr id="del<?=$value->id?>">
                    <td><?=$value->atividade_id?></td>
                    <td><?=$value->tributo_nome?></td>
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
                    <td id="reprovar<?=$value->id?>">
                        <?php if($value->statusconferencia_id == 1){ echo 'Aguardando'; } ?>
                        <?php if($value->statusconferencia_id == 2){ echo '<span style="color: #11b411; font-size: 1.2rem; font-weight: 700;">Aprovado</span>'; } ?>
                        <?php if($value->statusconferencia_id == 3){ echo '<span style="color:#e21313; font-size: 1.2rem; font-weight: 700;">Reprovado</span>'; } ?>
                    </td>
                    <td>
	                    <?php if(!empty($value->data_conferencia)){
	                    	$data = new DateTime($value->data_conferencia);
	                    	echo $data->format('d/m/Y');
	                    } ?>
                    </td>
                    <td><?=$value->nome_usuario_conferente?></td>
                    <td>
                        <?php if($value->statusconferencia_id == 2){ echo '<a href="javascript:void(0)" class="btn btn-danger"
                        style="text-transform: uppercase; font-size: 0.9rem; color: #ffffff; font-weight: 600;"
                        data-toggle="modal" data-target="#modalConferencia" onclick="getAtividadeId('.$value->id.','.$value->atividade_id.')">Reprovar</a>'; } ?>
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
                "pageLength": 10,
                "language": {
                    "lengthMenu": "Exibir _MENU_ registros por página",
                    "zeroRecords": "Nada encontrado - desculpe",
                    "info": "Mostrando página _PAGE_ de _PAGES_",
                    "infoEmpty": "Nenhum registro disponível",
                    "infoFiltered": "(filtrado de _MAX_ registros totais)",
	                "search": "Pesquisar",
	                "paginate": {
                        "previous": "Anterior",
                        "next": "Próximo"
                    }
                }
		    });

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
                                $('#reprovar'+value).html(`<span style="color:#e21313; font-size: 1.2rem; font-weight: 700;">Reprovado</span>`);
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
	    });

        $('#filtrar').click(function(){
            var trib = $('#tributo').val();
            var uf = $('#uf').val();
            var periodo = $('#periodo_apuracao').val();

            window.location.href = `/impostos/consultar?tributo=${trib}&uf=${uf}&periodo=${periodo}`;
        });

        function getAtividadeId(idconferenciaguias,atividadeid){
            $('#atividadeid').text(atividadeid)
            $('#idconferenciaguias').val(idconferenciaguias)
            $('#observacao').val('');
        }
    </script>
@stop