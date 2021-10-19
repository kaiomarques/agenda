<?php
    $request = substr($_SERVER['REQUEST_URI'], -10);
?>
@extends('layouts.master')


@section('content')


@if (Auth::guest())

    <p class="lead">Devido ao volume de estabelecimentos localizados em áreas diferentes, existe uma complexidade do controle de todas as entregas tributárias a ser efetuadas no ano fiscal.
    Por isto identificou-se a necessidade de construir uma ferramenta que ajude o time com o gerenciamento das datas de entrega para torná-lo mais eficiente e, ao mesmo tempo, minimizar o risco de erros ou atrasos.</p>
    <img src="{{ URL::to('/') }}/assets/img/agenda-fiscal.png" />

@elseif(Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('gbravo') || Auth::user()->hasRole('gcliente') || uth::user()->hasRole('analyst'))



<div class="content-top">
    <div class="row">
        <div class="col-md-6">
            <h1 class="title">Aprovação</h1>
        </div>
        <div class="col-md-6">
            <div class="refresh-option">
                {!! Form::open([
                        'route' => 'aprovacao'
                    ]) !!}
                {!! Form::hidden('periodo_apuracao', $periodo, ['class' => 'form-control']) !!}    
                {!! Form::button('<i class="fa fa-refresh"></i>', array('id' => 'atualiza_btn', 'class'=>'refresh-icon', 'type'=>'submit')) !!}
                
            </div>
            <div class="period">
                <div class="input-group spinner">
                    <input type="text" class="form-control" value="{{substr($periodo,0,2)}}/{{substr($periodo,-4,4)}}">
                    <div class="input-group-btn-vertical">
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-up"></i></button>
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-down"></i></button>
                    </div>
                </div>
                <span>Período:</span>
            </div>
        </div>
    </div>

</div>

@if ($request == '/aprovacao')
<div id="caixas_container-aprovacao">
    
    <div class="caixa-aprovacao" style="height: 80%;" id="limit_aprovacao">
            <div id="aprovacao">
                <div class="header-box box-2">
                    Entregas em fase de aprovação
                    <div class="btn-group">
                        <button type="button" id="btn_close_aprovacao"><i class="fa fa-chevron-up" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="tree">
                    <table class="table table display" style=" font-size: 9px; height: 85%" id="myTableAprovacao">   
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Data</th>
                                <th>Área</th>
                                <th>CNPJ</th>
                                <th>Atividade</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                           @if (is_array($aprovacao) && sizeof($aprovacao)>0 ) 
                            @foreach ($aprovacao as $key => $value)
                            <?php
                                $timestamp = strtotime($value['data_entrega']);
                                $value['data_entrega'] = date("d/m/Y", $timestamp);
                                $antes  = substr($value['periodo_apuracao'], 0, 2);
                                $depois = substr($value['periodo_apuracao'], 1 + 1);
                                $value['periodo_apuracao'] = $antes . "/" . $depois;
                            ?>

                            <tr>
                                <td><?php echo $value['periodo_apuracao']; ?></td>
                                <td><?php echo $value['data_entrega']; ?></td>
                                <td><?php echo $value['area']; ?></td>
                                <td><?php echo mask($value['cnpj'], '##.###.###/####-##'); ?></td>
                                <td><?php echo $value['descricao']; ?></td>
                                <td><a href="{{ route('atividades.show', $value['id']) }}" style="margin-left:10px" class="btn btn-default btn-sm"><i class="fa fa-search"></i></a></td>
                            </tr> 
                            @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
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
            </div>
    </div>
    @endif

    @if (is_array($urgentes) && (sizeof($urgentes)>0 && !Auth::user()->hasRole('gcliente') && !Auth::user()->hasRole('gbravo') && $request != '/aprovacao'))
    <div class="caixa" id="limit_urgentes">
        <div style="float:right" class="btn-group">
            <button type="button" id="btn_open_urgentes" class="btn btn-danger btn-xs">Abrir</button>
            <button type="button" id="btn_close_urgentes" class="btn btn-danger btn-xs">Fechar</button>
        </div>
        <div id="urgentes" class="alert alert-danger">
                <b>Pendências Urgentes</b> (última semana!)
                <hr/>
                <div class="tree">
                    <ul>
                        @foreach($urgentes as $message_trib_key=>$message_trib_val)
                        <li>
                            <span><i class="icon-folder-open"></i> {{ $message_trib_key }}</span>
                            <ul>
                                @foreach($message_trib_val as $message_limit_key => $message_limit_val)
                                <li>
                                    <span><i class="icon-folder-open"></i> {{ $message_limit_key }}</span>
                                    <ul>
                                        @foreach($message_limit_val as $message_estab_val)
                                        <li>
                                            <span><i class="icon-leaf"></i>
                                                {{ mask($message_estab_val->estemp->cnpj,'##.###.###/####-##') }}
                                            </span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </li>
                                @endforeach
                            </ul>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <br/>
        </div>
    </div>
    @endif

    @if (is_array($messages) && (sizeof($messages)>0 && !Auth::user()->hasRole('gcliente') && !Auth::user()->hasRole('gbravo')  && $request != '/aprovacao'))
    <div class="caixa" id="limit_vencimento">
            <div style="float:right" class="btn-group">
                <button type="button" id="btn_open_vencimento" class="btn btn-info btn-xs">Abrir</button>
                <button type="button" id="btn_close_vencimento" class="btn btn-info btn-xs">Fechar</button>
            </div>
            <div id="vencimento" class="alert alert-info">
                <b>Entregas em vencimento</b>
                <hr/>
                <div class="tree">
                    <ul>
                        @foreach($messages as $message_trib_key=>$message_trib_val)
                        <li>
                            <span><i class="icon-folder-open"></i> {{ $message_trib_key }}</span>
                            <ul>
                                @foreach($message_trib_val as $message_limit_key => $message_limit_val)
                                <li>
                                    <span><i class="icon-folder-open"></i> {{ $message_limit_key }}</span>
                                    <ul>
                                        @foreach($message_limit_val as $message_estab_val)
                                        <li>
                                            <span><i class="icon-leaf"></i>
                                                {{ mask($message_estab_val->estemp->cnpj,'##.###.###/####-##') }}
                                            </span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </li>
                                @endforeach
                            </ul>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <br/>
            </div>
    </div>
    @endif
</div>
    
        
    
@else

<div style="float:right; width:40%; padding-left: 120px; font-size:small">
    @if (is_array($vencidas) && sizeof($vencidas)>0)
        <div class="caixa" id="limit_vencidas">
            <div style="float:right" class="btn-group">
                <button type="button" id="btn_open_vencidas" class="btn btn-danger btn-xs">Abrir</button>
                <button type="button" id="btn_close_vencidas" class="btn btn-danger btn-xs">Fechar</button>
            </div>
            <div id="vencidas" style="background-color:black; color:white;" class="alert alert-danger">
                <b>Entregas Vencidas</b> (Máxima prioridade!!!)
                <hr/>
                    <ul>
                        @foreach($vencidas as $message_trib_key=>$message_trib_val)
                            <li>
                                <span><i class="icon-folder-open"></i> {{ $message_trib_key }}</span>
                                <ul>
                                    @foreach($message_trib_val as $message_limit_key => $message_limit_val)
                                    <li>
                                        <span><i class="icon-folder-open"></i> {{ $message_limit_key }}</span>
                                        <ul>
                                            @foreach($message_limit_val as $message_estab_val)
                                            <li>
                                                <span><i class="icon-leaf"></i>
                                                    {{ mask($message_estab_val->estemp->cnpj,'##.###.###/####-##') }}
                                                    <a href="{{ route('upload.entrega', $message_estab_val->id) }}" style="margin-left:10px" class="btn btn-default btn-xs">Entregar</a>
                                                </span>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                <br/>
            </div>
        
    @endif

    @if (is_array($urgentes) && sizeof($urgentes)>0)
    <div class="caixa" id="limit_urgentes">
        <div style="float:right" class="btn-group">
            <button type="button" id="btn_open_urgentes" class="btn btn-danger btn-xs">Abrir</button>
            <button type="button" id="btn_close_urgentes" class="btn btn-danger btn-xs">Fechar</button>
        </div>
        <div id="urgentes" class="alert alert-danger">
                <b>Pendências Urgentes</b> (última semana!)
                <hr/>
                <div class="tree">
                    <ul>
                        @foreach($urgentes as $message_trib_key=>$message_trib_val)
                        <li>
                            <span><i class="icon-folder-open"></i> {{ $message_trib_key }}</span>
                            <ul>
                                @foreach($message_trib_val as $message_limit_key => $message_limit_val)
                                <li>
                                    <span><i class="icon-folder-open"></i> {{ $message_limit_key }}</span>
                                    <ul>
                                        @foreach($message_limit_val as $message_estab_val)
                                        <li>
                                            <span><i class="icon-leaf"></i>
                                                {{ mask($message_estab_val->estemp->cnpj,'##.###.###/####-##') }}
                                                <a href="{{ route('upload.entrega', $message_estab_val->id) }}" style="margin-left:10px" class="btn btn-default btn-xs">Entregar</a>
                                            </span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </li>
                                @endforeach
                            </ul>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <br/>
        </div>
    </div>
    @endif

    @if (is_array($aprovacao) && sizeof($aprovacao)>0)
    <div class="caixa" id="limit_aprovacao">
        <div id="aprovacao" class="alert alert-warning">
            <b>Entregas em fase de aprovação</b>
            <hr/>
            <div class="tree">
                <ul>
                    
                </ul>
            </div>
            <br/>
        </div>
    </div>
    @endif

    @if (is_array($messages) && sizeof($messages)>0)
    <div class="caixa" id="limit_vencimento">
            <div style="float:right" class="btn-group">
                <button type="button" id="btn_open_vencimento" class="btn btn-info btn-xs">Abrir</button>
                <button type="button" id="btn_close_vencimento" class="btn btn-info btn-xs">Fechar</button>
            </div>
            <div id="vencimento" class="alert alert-info">
                <b>Entregas em vencimento</b>
                <hr/>
                <div class="tree">
                    <ul>
                        @foreach($messages as $message_trib_key=>$message_trib_val)
                        <li>
                            <span><i class="icon-folder-open"></i> {{ $message_trib_key }}</span>
                            <ul>
                                @foreach($message_trib_val as $message_limit_key => $message_limit_val)
                                <li>
                                    <span><i class="icon-folder-open"></i> {{ $message_limit_key }}</span>
                                    <ul>
                                        @foreach($message_limit_val as $message_estab_val)
                                        <li>
                                            <span><i class="icon-leaf"></i>
                                                {{ mask($message_estab_val->estemp->cnpj,'##.###.###/####-##') }}
                                            </span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </li>
                                @endforeach
                            </ul>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <br/>
            </div>
    </div>
    @endif
</div>
@endif

@if (!Auth::guest())

    <div class="grafh-content-aprovacao">
        <div class="card">
            <div class="header-grafh">
                Status geral das entregas
            </div>
            <div id="graph_container" style="height: 33.6%; width: 100%">dashboard</div>
            <div id="container_uf" style="height: 40%; width: 100%">dashboard</div>
        </div>
    </div>

<?php 
    $categoria = array();
    $dataDif = array();
    $dataNDif = array();
    $dataStatus1 = array();
    $dataStatus2 = array();
    $dataStatus3 = array();
    $dataDifString = '';
    $dataNDifString = '';
    if (count($graph_uf) > 0) {

        foreach ($graph_uf as $el) {
            $categoria[] = $el->UF;
            $dataStatus1[] = $el->Status1;
            $dataStatus2[] = $el->Status2;
            $dataStatus3[] = $el->Status3;
        }
    }

    $dataDifStringStatus1 = implode(",", $dataStatus1);
    $dataDifStringStatus2 = implode(",", $dataStatus2);
    $dataDifStringStatus3 = implode(",", $dataStatus3);
?>

<script type="text/javascript">
    $(document).ready(function (){
    $('#myTableAprovacao').dataTable({
        language: {                        
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        "bFilter": false,
        "bInfo" : false,
        "lengthChange": false,
        "pageLength": 10
    });        
});

</script>



<script>
$(function () {
//Dashboard Graph
    var tot_status_1 = {{ ($graph['status_1']) }};
    var tot_status_2 = {{ ($graph['status_2']) }};
    var tot_status_3 = {{ ($graph['status_3']) }};
    var tot = tot_status_1+tot_status_2+tot_status_3;

    var graph_categories = [<?= "'" . implode("','", $categoria) . "'" ?>];
    
    Highcharts.chart('graph_container', {
    chart: {
        type: 'pie',
        options3d: {
            enabled: true,
            alpha: 45,
            beta: 0
        }
    },
    title: {
        text: ''
    },
    tooltip: {
        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
    },
    plotOptions: {
        pie: {
            allowPointSelect: true,
            cursor: 'pointer',
            depth: 35,
            dataLabels: {
                enabled: true,
                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
            }
        }
    },
    series: [{
        name: '',
        colorByPoint: true,
        data: [{
            name: 'Não efetuada',
            y: tot_status_1, 
            color: '#5268ff'
        }, {
            name: 'Em aprovação',
            y: tot_status_2,
            sliced: true,
            selected: true
        }, {
            name: 'Aprovada',
            y: tot_status_3
        }]
    }]
});

Highcharts.chart('container_uf', {
    chart: {
        type: 'bar'
    },
    title: {
        text: 'Status por UF'
    },
    xAxis: {
        categories: graph_categories
    },
    yAxis: {
        min: 0,
        title: {
            text: 'Aprovação - UF'
        }
    },
    legend: {
        reversed: true
    },
    plotOptions: {
        series: {
            stacking: 'percent'
        }
    },
    series: [{
        name: 'Não Efetuado',
        data: [<?php echo $dataDifStringStatus1; ?>]
    }, {
        name: 'Em Aprovação',
        data: [<?php echo $dataDifStringStatus2; ?>]
    }, {
        name: 'Aprovado',
        data: [<?php echo $dataDifStringStatus3; ?>]
    }]
});
//Dashboard Messages
    $("#btn_open_vencidas").click(function(){

        $("#limit_vencidas").animate({
            height: $("#vencidas").height()
        },{{($vencidas)?(sizeof($vencidas)*10):0}});

        $("#btn_open_vencidas").css("display", "none");

        $("#btn_close_vencidas").css("display", "block");
    
    });

    $("#btn_close_vencidas").click(function(){

        $("#limit_vencidas").animate({
            height: 94
        },100);

        $("#btn_open_vencidas").css("display", "block");

        $("#btn_close_vencidas").css("display", "none");

    });

    $("#btn_open_urgentes").click(function(){

        $("#limit_urgentes").animate({
            height: $("#urgentes").height()
        },{{($urgentes)?(sizeof($urgentes)*10):0}});
    });

    $("#btn_close_urgentes").click(function(){

        $("#limit_urgentes").animate({
            height: 75
        },100);
    });

    $("#btn_open_aprovacao").click(function(){

            $("#limit_aprovacao").animate({
                height: $("#aprovacao").height()
            },{{($aprovacao)?(sizeof($aprovacao)*10):0}});

            $("#btn_open_aprovacao").css("display", "none");

             $("#btn_close_aprovacao").css("display", "block");
        });

    $("#btn_close_aprovacao").click(function(){

        $("#limit_aprovacao").animate({
            height: 94
        },100);

        $("#btn_open_aprovacao").css("display", "block");

        $("#btn_close_aprovacao").css("display", "none");

    });

    $("#btn_open_vencimento").click(function(){

                $("#limit_vencimento").animate({
                    height: $("#vencimento").height()
                },{{($messages)?(sizeof($messages)*10):0}});
            });

    $("#btn_close_vencimento").click(function(){

        $("#limit_vencimento").animate({
            height: 75
        },100);
    });
});
//Spinner
(function ($) {
       $('.spinner .btn:first-of-type').on('click', function() { //UP
              var value = $('.spinner input').val();

              var mes = parseInt(value.substr(0,2));
              var year = parseInt(value.substr(3,4));
              mes += 1;
              if (mes>12) {
                  mes = 1;
                  year += 1;
              } else if (mes<10) {
                  mes = '0'+mes;
              }
              year = ''+year;
              $('.spinner input').val(mes+'/'+year);

              $('input[name="periodo_apuracao"]').val(mes+year);
              $( "#atualiza_btn" ).click();

        });

       $('.spinner .btn:last-of-type').on('click', function() {  //DOWN
              var value = $('.spinner input').val();

              var mes = parseInt(value.substr(0,2));
              var year = parseInt(value.substr(3,4));
              mes -= 1;
              if (mes<1) {
                  mes = 12;
                  year -= 1;
              } else if (mes<10) {
                  mes = '0'+mes;
              }
              year = ''+year;
              $('.spinner input').val(mes+'/'+year);

              $('input[name="periodo_apuracao"]').val(mes+year);
              $( "#atualiza_btn" ).click();
       });
})(jQuery);

//Loading
$( "#btn_dashboard" ).click(function() {
 $("body").css("cursor", "progress");
});

$( "#btn_dashboard_analista" ).click(function() {
 $("body").css("cursor", "progress");
});

$( "#btn_atualiza" ).click(function() {
 $("body").css("cursor", "progress");
});

//BS Tree
$(function () {
    $('.tree li:has(ul)').addClass('parent_li').find(' > span').attr('title', 'Collapse this branch');
    $('.tree li.parent_li > span').on('click', function (e) {
        var children = $(this).parent('li.parent_li').find(' > ul > li');
        if (children.is(":visible")) {
            children.hide('fast');
            $(this).attr('title', 'Expand this branch').find(' > i').addClass('icon-plus-sign').removeClass('icon-minus-sign');
        } else {
            children.show('fast');
            $(this).attr('title', 'Collapse this branch').find(' > i').addClass('icon-minus-sign').removeClass('icon-plus-sign');
        }
        e.stopPropagation();
    });
});
</script>
@endif

@stop
<footer>
    @if (!Auth::guest())
        @include('layouts.footer-left')
    @else
        @include('layouts.footer')
    @endif
</footer>

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

