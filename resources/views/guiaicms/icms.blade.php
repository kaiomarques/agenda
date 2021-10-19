@extends('...layouts.master')
@section('content')
    <link href="{{ URL::to('/') }}/assets/bootstrap-tagsinput/bootstrap-tagsinput.css" rel="stylesheet">
    <script src="{{ URL::to('/') }}/assets/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>
{!! Form::open([
    'route' => 'guiaicms.planilha'
]) !!}
<?php if (@!empty($mensagem)) { ?>
    <div class="alert alert-success">
        <?php echo $mensagem; ?>
    </div>
<?php } ?>
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-12">
                <h2 class="sub-title">{!! Form::label('periodo_apuracao', 'Período de busca', ['class' => 'control-label'] )  !!} </h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                {!! Form::label('multiple_select_tributos[]', 'Estabelecimentos', ['class' => 'control-label'] )  !!}
                <select multiple="multiple" name="multiple_select_estabelecimentos[]" id="estabelecimentos" class="form-control s2_multi">
                <?php foreach($estabelecimentos as $aKey => $value) { 
                    $selected = false;
                    foreach($estabelecimentosselected as $key) {
                        if($aKey == $key) {
                            $selected = true;
                        }
                    }
                ?>
                    <option value="{{$aKey}}" @if($selected)selected="selected"@endif>{{$value}}</option>
                <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                {!! Form::label('multiple_select_tributos[]', 'UF', ['class' => 'control-label'] )  !!}
                {!!  Form::select('multiple_select_uf[]', $uf, $ufselected, ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-md-3">     
                {!! Form::label('inicio', 'Data Inicial Vencimento', ['class' => 'control-label']) !!}    
                {!! Form::date('inicio', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-3">         
                {!! Form::label('fim', 'Data Final Vencimento', ['class' => 'control-label']) !!}
                {!! Form::date('fim', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <div class="row">
             <div class="col-md-3">         
                {!! Form::label('inicio_leitura', 'Data Inicial Leitura', ['class' => 'control-label']) !!}
                {!! Form::date('inicio_leitura', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-3">         
                {!! Form::label('fim_leitura', 'Data Final Leitura', ['class' => 'control-label']) !!}
                {!! Form::date('fim_leitura', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <?php //if(!empty($enviar_zfic)){ ?>
        <div class="row">
            <div class="col-sm-12">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" value="1" name="enviar_zfic" id="enviar_zfic"> Enviar ZFIC ao Cliente
                    </label>
                </div>
                <label>Informe os e-mails: </label>
                <div class="form-group">
                    <input type="text" name="emails" id="emails" class="form-control emails" data-role="tagsinput" />
                </div>
            </div>
        </div>
        <?php //} ?>
        <div class="row">
            <br />
        </div>
        <div class="row">
            <div class="col-md-2">
                
                <table class="table table-bordered display" id="dataTables-example" style="width: 100%; height: 100%; font-size: 12px; display: none;">
                <thead>
                <tr style="display: none">
                    <th>CAB_CDRCIN</th>
                    <th>CAB_CODTBT</th>
                    <th>CAB_BUKRS</th>
                    <th>CAB_BARCOD</th>
                    <th>CAB_DTVENC</th>
                    <th>CAB_GSBER</th>
                    <th>CAB_CNPJE</th>
                    <th>CAB_COMPCM</th>
                    <th>CAB_COMENT</th>
                    <th>CAB_RGINST</th>
                    <th>CAB_NFENUM</th>
                    <th>CAB_SERIES</th>
                    <th>CAB_SUBSER</th>
                    <th>CAB_ACCESS_KEY</th>
                    <th>CAB_AUTHCOD</th>
                    <th>CAB_DATANF</th>
                    <th>CAB_FGTSID</th>
                    <th>CAB_AUFNR</th>
                    <th>RAT_KOSTL</th>
                    <th>RAT_GSBER</th>
                    <th>RAT_VALOR</th>
                    <th>RAT_VAL_ATU</th>
                    <th>RAT_VAL_MULTA</th>
                    <th>RAT_VAL_JUROS</th>
                    <th>RAT_VAL_OUTROS</th>
                    <th>RAT_VAL_ACRES</th>
                    <th>RAT_VAL_DESCONT</th>
                    <th>RAT_AUFNR</th>
                </tr>
                </thead>
                    <tbody>
                    <!-- cabeçafilho fixo? -->
                    <tr style="display: none">
                        <th>Codigo de Receita (Interno)</th>
                        <th>Codigo do Tributo</th>
                        <th>Empresa</th>
                        <th>Codigo de Barras</th>
                        <th>Data de vencimento</th>
                        <th>Divisao</th>
                        <th>CNPJ</th>
                        <th>Comentario para Comprovante</th>
                        <th>Comentarios</th>
                        <th>Registro da instalacao</th>
                        <th>Numero de documento de nove posicoes</th>
                        <th>Series NF/NFE</th>
                        <th>Subseries</th>
                        <th>Chave de acesso de 44 posicoes</th>
                        <th>Codigo de Autoriza</th>
                        <th>BTP - Data da Nota</th>
                        <th>Identifcacao processo FGTS</th>
                        <th>Ordem</th>
                        <th>Centro de custo</th>
                        <th>Divisao</th>
                        <th>Valor total</th>
                        <th>Valor atualizado</th>
                        <th>Valor multa</th>
                        <th>Valor Juros</th>
                        <th>Valor outros </th>
                        <th>Valor acrescimento</th>
                        <th>Valor desconto</th>
                        <th>Ordem</th>
                    </tr>
                    <?php
                        if (!empty($planilha)) {
                    ?>

                    Com Código de barras
                    

                    <?php
                          foreach ($planilha as $key => $value) {
                          	if($value['valido'] == 'S'){
                    ?>
                        <tr style="display: none">
                            <td><?php echo $value['uf'];?></td>
                            <td><?php echo $value['IMPOSTO'];?></td>
                            <td><?php if (substr($value['CNPJ'], 0,8) == 13574594) {
                                echo "1000";
                            } ?></td>
                          <td><?php echo $value['CODBARRAS'];?></td>
                            <?php
                            $valorData = $value['DATA_VENCTO'];
                            $data_vencimento = str_replace('-', '/', $valorData);
                            $value['DATA_VENCTO'] = date('d/m/Y', strtotime($data_vencimento));
                            ?>
                            <td><?php echo $value['DATA_VENCTO'];?></td>
                            <td><?php echo $value['codigo'];?></td>
                            <td></td>
                            <td></td>
                            <td><?php echo 'Pagto ICMS'.$value['codigo'].'/'.$value['centrocusto'];?></td>
                            <td></td>
                            <td>ICMS</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><?php echo $value['ORDEM_TAXA'];?></td>
                            <td><?php echo $value['centrocusto'];?></td>
                            <td><?php echo $value['codigo'];?></td>
                            <td><?php echo $value['VLR_TOTAL'];?></td>
                            <td></td>
                            <td><?php echo $value['MULTA_MORA_INFRA'];?></td>
                            <td><?php echo $value['JUROS_MORA'];?></td>
                            <td></td>
                            <td><?php echo $value['ACRESC_FINANC'];?></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php } } } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <br> 
                <table class="table table-bordered display" id="dataTables-example_2333" style="width: 100%; height: 100%; font-size: 12px; display: none;">
                <thead>
                    <tr style="display: none">
                        <th>CAB_CDRCIN</th>
                        <th>CAB_CODTBT</th>
                        <th>CAB_BUKRS</th>
                        <th>CAB_DTVENC</th>
                        <th>CAB_TPIDENT</th>
                        <th>CAB_IDENT</th>
                        <th>CAB_DTAPUR</th>
                        <th>CAB_NUMREF</th>
                        <th>CAB_TIPODARF</th>
                        <th>CAB_GSBER</th>
                        <th>CAB_CNPJE</th>
                        <th>CAB_COMPCM</th>
                        <th>CAB_COMENT</th>
                        <th>CAB_PRDCPT</th>
                        <th>CAB_INFADI</th>
                        <th>CAB_DARF11</th>
                        <th>CAB_DARJ22</th>
                        <th>CAB_GARE13</th>
                        <th>CAB_GARE14</th>
                        <th>CAB_GARE15</th>
                        <th>CAB_ANOBAS</th>
                        <th>CAB_RENAVA</th>
                        <th>CAB_INSEST</th>
                        <th>CAB_ESTADO</th>
                        <th>CAB_MUNICI</th>
                        <th>CAB_CPLACA</th>
                        <th>CAB_OPCPAG</th>
                        <th>CAB_OPCRET</th>
                        <th>CAB_NOMGPS</th>
                        <th>CAB_ENDGPS</th>
                        <th>CAB_NUMGPS</th>
                        <th>CAB_BAIGPS</th>
                        <th>CAB_CEPGPS</th>
                        <th>CAB_ESTGPS</th>
                        <th>CAB_MUNGPS</th>
                        <th>CAB_TELGPS</th>
                        <th>CAB_AUFNR</th>
                        <th>RAT_KOSTL</th>
                        <th>RAT_GSBER</th>
                        <th>RAT_VALOR</th>
                        <th>RAT_VAL_ATU</th>
                        <th>RAT_VAL_MULTA</th>
                        <th>RAT_VAL_JUROS</th>
                        <th>RAT_VAL_OUTROS</th>
                        <th>RAT_VAL_ACRES</th>
                        <th>RAT_VAL_DESCONT</th>
                        <th>RAT_AUFNR</th>
                    </tr>
                </thead>
                    <tbody>
                        <!-- cabeçafilho fixo? -->
                        <tr style="display: none">
                            <td>Código de Receita (Interno)</td>
                            <td>Codigo do Tributo</td>
                            <td>Empresa</td>
                            <td>Data de vencimento</td>
                            <td>Tipo de identificação</td>
                            <td>Identificação</td>
                            <td>Data Apuração</td>
                            <td>Numero Refencia</td>
                            <td>Tipo DARF</td>
                            <td>Divisão</td>
                            <td>CNPJ</td>
                            <td>Comentário para Comprovante</td>
                            <td>Comentários</td>
                            <td>Periodo de Competência/Referência/Apuração</td>
                            <td>Informações Adicionais</td>
                            <td>Data de Apuração</td>
                            <td>Percentual sobre Receita Bruta</td>
                            <td>Documento de Origem</td>
                            <td>Referência GARE Bradesco</td>
                            <td>Número do Parcelamento/AIIM/OEICM</td>
                            <td>Divida ativa / Nº Etiqueta</td>
                            <td>Ano Base</td>
                            <td>Inscrição Estadual</td>
                            <td>Estado (UF)</td>
                            <td>Município</td>
                            <td>Placa Veículo</td>
                            <td>Opção de Pagamento</td>
                            <td>Opção de Retirada</td>
                            <td>Nome (GPS)</td>
                            <td>Endereço (GPS)</td>
                            <td>Numero do Endereço (GPS)</td>
                            <td>Bairro (GPS)</td>
                            <td>CEP (GPS)</td>
                            <td>UF (GPS)</td>
                            <td>Município (GPS)</td>
                            <td>Telefone (GPS)</td>
                            <td>Ordem</td>
                            <td>Centro de custo</td>
                            <td>Divisao</td>
                            <td>Valor total</td>
                            <td>Valor atualizado</td>
                            <td>Valor multa</td>
                            <td>Valor Juros</td>
                            <td>Valor outros </td>
                            <td>Valor acrescimento</td>
                            <td>Valor desconto</td>
                            <td>Ordem</td>
                        </tr>
                    <?php
                        if (!empty($planilha_semcod)) { ?>
                    
                    Sem Código de barras
                    
                    <?php
                          foreach ($planilha_semcod as $key => $value) {
                            if($value['valido'] == 'S'){
                    ?>
                        <tr style="display: none">
                            <td>046-2</td>
                            <td><?php echo $value['IMPOSTO'];?></td>
                            <?php
                            $valorData = $value['DATA_VENCTO'];
                            $data_vencimento = str_replace('-', '/', $valorData);
                            $value['DATA_VENCTO'] = date('d/m/Y', strtotime($data_vencimento));
                            ?>
                            <td><?php if (substr($value['CNPJ'], 0,8) == 13574594) {
                                echo "1000";
                            } ?></td>
                            <td><?php echo $value['DATA_VENCTO'];?></td>
                            <td>1</td>
                            <td><?php echo $value['CNPJ'];?></td>
                            <td><?php echo str_replace('/', '', $value['REFERENCIA']);?></td>
                            <td></td>
                            <td></td>
                            <td><?php echo $value['codigo'];?></td>
                            <td><?php echo $value['CNPJ'];?></td>
                            <td></td>
                            <td>ICMS SP</td>
                            <td><?php echo str_replace('/', '', $value['REFERENCIA']);?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><?php echo $value['ORDEM_TAXA'];?></td>
                            <td><?php echo str_replace('/', '', $value['REFERENCIA']);?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><?php echo $value['IE'];?></td>
                            <td><?php echo $value['uf'];?></td>
                            <td><?php echo $value['codigo_sap'];?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><?php echo $value['centrocusto'];?></td>
                            <td><?php echo $value['codigo'];?></td>
                            <td><?php echo $value['VLR_TOTAL'];?></td>
                            <td></td>
                            <td><?php echo $value['MULTA_MORA_INFRA'];?></td>
                            <td><?php echo $value['JUROS_MORA'];?></td>
                            <td></td>
                            <td><?php echo $value['ACRESC_FINANC'];?></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php } } } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col-md-2">
                {!! Form::submit('Gerar', ['class' => 'btn btn-success-block']) !!}
                {!! Form::close() !!}
            </div>
        </div>
    </div>

<script type="text/javascript">
    
$('select').select2();

jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");
});

$(document).ready(function () {
	$('.emails').tagsinput();

    $('#dataTables-example').dataTable({
        language: {
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: '<B>rt',
        <?php
        if (!empty($planilha)) {
        ?>
        buttons: [
            {
                extend: 'csvHtml5',
                title: 'ZFIC_COMCODBARRAS_<?php echo $data_inicio; ?>_<?php echo $data_fim; ?>',
                fieldSeparator: ';',
                fieldBoundary: ''
            }
        ],
        "ordering": false
        <?php }?>
    });


    $('#dataTables-example_2333').dataTable({
        language: {
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: '<B>rt',
        <?php
        if (!empty($planilha_semcod)) {
        ?>
        buttons: [
            {
                extend: 'csvHtml5',
                title: 'ZFIC_SEMCODBARRAS_<?php echo $data_inicio; ?>_<?php echo $data_fim; ?>',
                fieldSeparator: ';',
                fieldBoundary:''
            }
        ],
        "ordering": false
        <?php }?>
    });
});

</script>
@stop
<footer>
    @include('layouts.footer')
</footer>