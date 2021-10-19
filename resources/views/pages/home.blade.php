@extends('layouts.master')

@section('content')


@if (Auth::guest())

    <p class="lead">Devido ao volume de estabelecimentos localizados em áreas diferentes, existe uma complexidade do controle de todas as entregas tributárias a ser efetuadas no ano fiscal.
    Por isto identificou-se a necessidade de construir uma ferramenta que ajude o time com o gerenciamento das datas de entrega para torná-lo mais eficiente e, ao mesmo tempo, minimizar o risco de erros ou atrasos.</p>
    <img src="{{ URL::to('/') }}/assets/img/agenda-fiscal.png" />

@elseif(Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('gbravo') || Auth::user()->hasRole('gcliente'))

<div class="content-top">
    <div class="row">
        <div class="col-md-6">
            <h1 class="title">Entregas Gerais</h1>
        </div>
        <div class="col-md-6">
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


<div id="caixas_container">
    @if (is_array($aprovacao) && sizeof($aprovacao)>0)
    <div class="caixa" id="limit_aprovacao">
            <div id="aprovacao" class="">
                <div class="header-box box-1">
                    Entregas em fase de aprovação
                    <div class="btn-group">
                        <button type="button" id="btn_close_aprovacao"><i class="fa fa-chevron-up" aria-hidden="true"></i>
</button>
                    </div>
                </div>
                <div class="tree">
                    <p class="open-box" id="btn_open_aprovacao" class="btn-xs">Visualizar</p>
                    <ul>
                        @foreach($aprovacao as $message_trib_key=>$message_trib_val)
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
                                                    <a href="{{ route('atividades.show', $message_estab_val->id) }}" style="margin-left:10px" class="btn btn-default btn-sm">Visualizar</a>
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

    @if (is_array($vencidas) && sizeof($vencidas)>0)
    <div class="caixa" id="limit_vencidas">
            <div id="vencidas">
                <div class="header-box box-2">
                    Entregas vencidas
                    <div class="btn-group">
                        <button type="button" id="btn_close_vencidas"><i class="fa fa-chevron-up" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="tree">
                    <p class="open-box" id="btn_open_vencidas" class="btn-xs">Visualizar</p>
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
                                                    <a href="{{ route('atividades.show', $message_estab_val->id) }}" style="margin-left:10px" class="btn btn-default btn-sm">Visualizar</a>
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

    @if (is_array($urgentes) && sizeof($urgentes)>0 && !Auth::user()->hasRole('gcliente') && !Auth::user()->hasRole('gbravo'))
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

    @if (is_array($messages) && sizeof($messages)>0 && !Auth::user()->hasRole('gcliente') && !Auth::user()->hasRole('gbravo'))
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
<div class="row">
    <div class="col-md-2">
        <div class="input-group spinner">
            <input type="text" class="form-control" value="{{substr($periodo,0,2)}}/{{substr($periodo,-4,4)}}">
            <div class="input-group-btn-vertical">
              <button class="btn btn-default" type="button"><i class="fa fa-caret-up"></i></button>
              <button class="btn btn-default" type="button"><i class="fa fa-caret-down"></i></button>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        {!! Form::open([
            'route' => 'dashboard_analista'
        ]) !!}
        {!! Form::hidden('periodo_apuracao', $periodo, ['class' => 'form-control']) !!}
        {!! Form::button('<i class="fa fa-pie-chart"></i> Dashboard Analista', array('type' => 'submit', 'id'=>'btn_dashboard_analista', 'class' => 'btn btn-default')) !!}
        {!! Form::close() !!}
    </div>
    <div class="col-md-2">
        {!! Form::open([
            'route' => 'home'
        ]) !!}
        {!! Form::hidden('periodo_apuracao', $periodo, ['class' => 'form-control']) !!}
        {!! Form::button('<i class="fa fa-refresh"></i> Atualizar', array('id' => 'btn_atualiza', 'class'=>'btn btn-default', 'type'=>'submit')) !!}
        {!! Form::close() !!}
    </div>
</div>
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
                <div class="tree">
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
                </div>
                <br/>
            </div>
        </div>
    @endif

    @if (is_array($urgentes) & sizeof($urgentes)>0)
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
        <div style="float:right" class="btn-group">
            <button type="button" id="btn_open_aprovacao" class="btn btn-warning btn-xs">Abrir</button>
            <button type="button" id="btn_close_aprovacao" class="btn btn-warning btn-xs">Fechar</button>
        </div>
        <div id="aprovacao" class="alert alert-warning">
            <b>Entregas em fase de aprovação</b>
            <hr/>
            <div class="tree">
                <ul>
                    @foreach($aprovacao as $message_trib_key=>$message_trib_val)
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

    <div class="grafh-content">
        <div class="card">
            <div class="header-grafh">
                Status geral das entregas
            </div>
            <div id="graph_container" style="height: 470px">dashboard</div>
        </div>
    </div>

<script>
$(function () {
//Dashboard Graph
    var tot_status_1 = {{ ($graph['status_1']) }};
    var tot_status_2 = {{ ($graph['status_2']) }};
    var tot_status_3 = {{ ($graph['status_3']) }};
    var tot = tot_status_1+tot_status_2+tot_status_3;

    $('#graph_container').highcharts({
        chart: {
                    plotBackgroundColor: null,
                    plotBorderWidth: null,
                    plotShadow: false,
                    type: 'pie'
                },
                title: {
                        text: ''

                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Entregas (efet./total): <b>{point.y} / {point.total}</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %',
                            style: {
                                color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                            }
                        }
                    }
                },
                series: [{
                    name: 'Percentual entregas',
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
//Dashboard Messages
    $("#btn_open_vencidas").click(function(){

        $("#limit_vencidas").animate({
            height: $("#vencidas").height()
        },{{is_array($vencidas) && sizeof($vencidas)*10}});

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
        },{{is_array($urgentes) && sizeof($urgentes)*10}});
    });

    $("#btn_close_urgentes").click(function(){

        $("#limit_urgentes").animate({
            height: 75
        },100);
    });

    $("#btn_open_aprovacao").click(function(){

            $("#limit_aprovacao").animate({
                height: $("#aprovacao").height()
            },{{is_array($aprovacao) && sizeof($aprovacao)*10}});

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
                },{{is_array($messages) && sizeof($messages)*10}});
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
              $('.spinner input').val('loading..');

              $('input[name="periodo_apuracao"]').val(mes+year);
              $( "#btn_atualiza" ).click();

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
              $('.spinner input').val('loading..');

              $('input[name="periodo_apuracao"]').val(mes+year);
              $( "#btn_atualiza" ).click();
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

