@extends('layouts.master')

@section('content')


<hr>
<div class="row">
    <div class="col-md-7">
        <p class="lead"><b>{{ $empresa->razao_social }}</b></p>
        <p class="lead">CODIGO: {{ $empresa->codigo }}</p>
        <p class="lead">CNPJ: {{ mask($empresa->cnpj,'##.###.###/####-##') }}</p>
        <p class="lead">LOCAL: {{ $empresa->municipio->nome }} ({{ $empresa->municipio->uf }}) | {{ $empresa->endereco }} {{ $empresa->num_endereco }}</p>
        <p class="lead">IE: {{ $empresa->insc_estadual?$empresa->insc_estadual:'não cadastrado' }}</p>
        <p class="lead">IM: {{ $empresa->insc_municipal?$empresa->insc_municipal:'não cadastrado' }}</p>
    </div>
    <div class="col-md-3  pull-right">
        <img style="max-height: 150px" src="{{ URL::to('/') }}/assets/logo/Logo-{{ $empresa->id }}.png" />
        <img style="width:250px" src="{{ URL::to('/') }}/assets/img/img_empresa.png" />
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-8">
        <p style="" class="lead">Atividades em aberto relacionadas.</p>
        @if (sizeof($atividades)>0)
        <div class="row">
            <div style="font-weight: bold" class="col-md-6">DESCRIÇÃO</div>
            <div style="font-weight: bold" class="col-md-2">PERIODO</div>
            <div style="font-weight: bold" class="col-md-2">ENTREGA</div>
            <div style="font-weight: bold" class="col-md-2"></div>
        </div>
        @endif
        @if (sizeof($atividades)==0)
        <div class="row">
            <div class="col-md-6">Nenhuma atividade relacionada em aberto.</div>
        </div>
        @endif
        @foreach ($atividades as $atividade)
        <div class="row">
            <div class="col-md-6">{{$atividade['descricao']}}</div>
            <div class="col-md-2">{{$atividade['periodo_apuracao']}}</div>
            <div class="col-md-2">{{Date_Converter($atividade['limite'])}}</div>
            <div class="col-md-2"><a href="{{ route('atividades.show', $atividade['id']) }}" style="margin-left:10px" class="btn btn-default btn-xs">Abrir</a></div>
        </div>
        @endforeach
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-10">
        <p style="" class="lead">Mapeamento tributos para esta empresa:</p>
        <div class="row">
        @foreach ($empresa->tributos as $tributo)
            <div class="col-md-3">
             {{ $tributo->nome }}
            </div>
        @endforeach
        </div>
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-12">
        <p style="" class="lead">Mapeamento usuarios para esta empresa:</p>
        <div class="row">
        @foreach ($empresa->users as $user)
            <div class="col-md-4">
             {{ $user->name }}
             @foreach ($user->roles as $role)
                ({{ $role->display_name }})
             @endforeach
            </div>
        @endforeach
        </div>
    </div>
</div>
<hr/>
<div class="panel panel-default">
        <div class="panel-heading">Painel Operacional para geração das atividades</div>
        <div style="padding:20px" class="panel-body">
            <div class="row">
                <div class="col-lg-6 col-md-12 col-xs-12">
                    <div id="geral">
                        <div style="margin-bottom: 30px" class="row">
                            <div class="col-xs-2 col-sm-2">
                                <label>Periodo Apuração: </label>
                                <input style="width: 80px; text-align: center" type="text" name="periodo" value="{{ date('mY') }}" />
                            </div>
                        </div>
                        <div style="margin-left: 30px;" class="row">
                            {{ Form::button('Gera todas as Atividades', array('class' => 'btn btn-default btn_geracao')) }}
                        </div>
                    </div>
                    <div id="tributo" style="display:none">
                        <div class="row" id="geralForm">
                            <div class="col-xs-3">
                                <label class="form-label">Período:</label>
                                <input type="text" id="periodo_busca" class="form-control"  value="{{ date('m/Y') }}" placeholder="03/2020" maxlength="7">
                            </div>
                            <div class="col-xs-3">
                                <label class="form-label">Tributos:</label>
                                {!! Form::select('combo_tributo', $tributos, null ,['class' => 'form-control ', 'id' => 'tributos', 'placeholder' => 'Todos os tributos']) !!}
                            </div>
                            
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                {{ Form::button('Gera todas as Atividades', array('class' => 'btn btn-info btn-block btn_geracao', 'style' => 'margin-top:10px')) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6  col-md-12 col-xs-12 pull-right">
                    <div class="row" style="margin-top: 10px">
                        <div class="col-xs-6">
                            <button class="btn btn-success btn-block" onclick="$('#geral').show();$('#tributo').hide();">Tributos gerais</button>
                        </div>
                        <div class="col-xs-6">
                            <button class="btn btn-danger btn-block" onclick="$('#geral').hide();$('#tributo').show();">Gerar por tributo</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-footer clearfix">
            <div class="col-md-6">
                <a href="{{ route('empresas.index') }}" class="btn btn-default">Voltar</a>
                <a href="{{ route('empresas.edit', $empresa->id) }}" class="btn btn-default">Alterar Empresa</a>
            </div>
            <div class="col-md-6 text-right">
                {!! Form::open([
                    'method' => 'DELETE',
                    'route' => ['empresas.destroy', $empresa->id]
                ]) !!}
                    {!! Form::submit('Cancelar esta empresa?', ['class' => 'btn btn-default']) !!}
                {!! Form::close() !!}
            </div>
        </div>
</div>

<script>
    var tipo = '';
    var ref = 0;
    $('#tributos').on('change', function(){
        var t = $('#tributos').val();
        $('#estadoForm').remove();
        $('#estadoMunicipio').remove();
        ref = 0;
        tipo = '';

        $.ajax({
            type: "GET",
            url:  "{{route('empresas.ajax')}}",
            data: 'action=1&idTributo='+t,
            dataType: "JSON",
            beforeSend: function(){$("body").css("cursor", "progress")},
            success: function(retorno) {
                $("body").css("cursor", "default")
                if(retorno == 'E' || retorno == 'M'){
                    tipo = retorno;
                    $('#geralForm').append('<div class="col-xs-3" id="estadoForm"><label for="tributoEstado" class="form-label">Estado:</label>\
                        <select class="form-control" id="tributoEstado">\
                            <option value="0">Escolha</option>\
                            <option>AC</option>\
                            <option>AL</option>\
                            <option>AP</option>\
                            <option>AM</option>\
                            <option>BA</option>\
                            <option>CE</option>\
                            <option>DF</option>\
                            <option>ES</option>\
                            <option>GO</option>\
                            <option>MA</option>\
                            <option>MT</option>\
                            <option>MS</option>\
                            <option>MG</option>\
                            <option>PB</option>\
                            <option>PR</option>\
                            <option>PE</option>\
                            <option>PI</option>\
                            <option>RJ</option>\
                            <option>RN</option>\
                            <option>RS</option>\
                            <option>RO</option>\
                            <option>RR</option>\
                            <option>SC</option>\
                            <option>SP</option>\
                            <option>SE</option>\
                            <option>TO</option>\
                        </select></div>');
                }
                $('#tributoEstado').on('change', function(){
                    ref = 0;
                    $('#estadoMunicipio').remove();
                    if(tipo == "M" && $('#tributoEstado').val() != ""){
                        var estado = $('#tributoEstado').val();
                        $('#geralForm').append('<div class="col-xs-3" id="estadoMunicipio"><label for="tributoMunicipio" class="form-label">Município:</label>\
                        <select class="form-control" id="tributoMunicipio"><option value="0">Escolha</option></select></div>');
                        $.ajax({
                            type: "GET",
                            url:  "{{route('empresas.ajax')}}",
                            data: 'action=2&estado='+estado,
                            dataType: "JSON",
                            beforeSend: function(){$("body").css("cursor", "progress")},
                            success: function(retorno) {
                                $("body").css("cursor", "default")
                                $.each(retorno, function(key,value){
                                    $('#tributoMunicipio').append('<option value="'+value['codigo']+'">'+value['nome']+'</option>"');
                                })
                                $('#tributoMunicipio').on('change', function(){
                                   ref = $('#tributoMunicipio').val();
                                });

                            }

                            
                        })
                    }else{
                        if(tipo == "E" && $('#tributoEstado').val() != ""){
                            ref = $('#tributoEstado').val();
                        }
                    }
                })
            }
        });
    });

    

    $(function () {
        $('#periodo_busca').datepicker({
            format: "mm/yyyy",
            startView: "months", 
            minViewMode: "months"
        });

        $('.btn_geracao').click(function() {
            if($( "#tributo" ).is( ":visible" )){
                var periodo = $('#periodo_busca').val();
                periodo = periodo.replace('/','');
                var tributo = $.isNumeric($('#tributos').val())? $('#tributos').val() : 0 ;
                if((tipo == "M" || tipo == "E") && ref == 0){
                    alert('Escolha um estado ou cidade!')
                }else{
                    if(tipo != "M" && tipo != "E"){
                        ref = 0;
                    }

                    var url = '{{ url('empresa') }}/:periodo/:id_emp/:tributo/:ref/geracao';
                    url = url.replace(':periodo', periodo);
                    url = url.replace(':id_emp', {{ $empresa->id }});
                    url = url.replace(':tributo', tributo);
                    url = url.replace(':ref', ref);
                    location.replace(url);
                }
            }else{
                var periodo = $('input[name="periodo"]').val();
                periodo = periodo.replace('/','');

                var url = '{{ url('empresa') }}/:periodo/:id_emp/geracao';
                url = url.replace(':periodo', periodo);
                url = url.replace(':id_emp', {{ $empresa->id }});

                location.replace(url);
            }
            
        });

        jQuery(function($){
            $('input[name="periodo"]').mask("99/9999");
        });
    });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<?php
function mask($val, $mask)
{
     $maskared = '';
     $k = 0;
     for($i = 0; $i<=strlen($mask)-1; $i++)
     {
     if($mask[$i] == '#')
     {
     if(isset($val[$k]))
     $maskared .= $val[$k++];
     }
     else
     {
     if(isset($mask[$i]))
     $maskared .= $mask[$i];
     }
     }
     return $maskared;
}
function Date_Converter($date) {

    # Separate Y-m-d from Date
    $date = explode("-", substr($date,0,10));
    # Rearrange Date into m/d/Y
    $date = $date[2] . "/" . $date[1] . "/" . $date[0];

    # Return
    return $date;

}
?>
@stop






