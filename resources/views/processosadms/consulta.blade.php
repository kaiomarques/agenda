@extends('...layouts.master')

@section('content')
<?php
    $first = 0;
    $last = sizeof($datas)-1;
?>
<div class="content-top">
    <div class="row">
        <div class="col-md-6">
            <h1 class="title">Consulta Processos ADMS</h1>
        </div>
        <div class="col-md-6">
        <div class="refresh-option">
        <form action="{{ route('consulta_procadm') }}" method="get" >
        <button type="submit" class="refresh-icon"><i class="fa fa-refresh"></i></button>
        
        </div>
            <div class="period">
                <div class="input-group spinner" id="spinner">
                    <input type="text" id="INPUTFIM" name="periodo_fim" class="form-control" value="{{substr($datas[$last], 0,2)}}/{{substr($datas[$last], -4,4)}}">
                    <div class="input-group-btn-vertical">
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-up"></i></button>
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-down"></i></button>
                    </div>
                </div>
                <span>Fim:</span>
                <div class="input-group spinner" id="spinner2">
                    <input type="text" id="INPUTINI" class="form-control" name="periodo_inicio" value="{{substr($datas[$first], 0,2)}}/{{substr($datas[$first], -4,4)}}">
                    <div class="input-group-btn-vertical">
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-up"></i></button>
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-down"></i></button>
                    </div>
                </div>
                <span>Inicio:</span>
            </div>
            </form>
        </div>
    </div>
</div>
    
<form action="{{ route('rlt_detalhado') }}" id="formRLT" method="get">
    <input type="hidden" name="periodo_inicio" value="{{$datas[$first]}}">
    <input type="hidden" name="periodo_fim" value="{{$datas[$last]}}">
</form>    
@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif
    <div class="row" onclick="rlt_detalhado()">
        <div id="container" style="min-width: 310px; height: 650px; margin: 0 auto"></div>
    </div>
</div>
<hr>
<?php
//Números das colunas
$baixados = '';
$total = '';
$em_andamento = '';

//Grafico em pizza
$baixadosTT = 0;
$totalTT = 0;
$em_andamentoTT = 0;

//media
$media = '';

//Recebe array do controller
$standing = json_decode(json_encode($standing),true);

foreach ($datas as $index => $periodo) {
    foreach ($standing[$periodo] as $dados) {
        $baixados .= $dados['baixados'].',';
        $em_andamento .= $dados['em_andamento'].',';
        $total .= $dados['total'].',';
        $baixadosTT += $dados['baixados'];  
        $totalTT += $dados['total'];
        $em_andamentoTT += $dados['em_andamento'];
        $media .= round(($dados['em_andamento']+$dados['baixados'])/2).',';
    }
}
//Prepara para exibir em gráfico
$media = substr($media ,0,-1);
$baixados = substr($baixados ,0,-1);
$em_andamento = substr($em_andamento ,0,-1);
$total = substr($total ,0,-1);
?>
<script type="text/javascript">

function rlt_detalhado(){
    document.getElementById("formRLT").submit();
}

Highcharts.chart('container', {
    title: {
        text: 'Processos Administrativos'
    },
    xAxis: {
        categories: [<?php echo($dataBusca); ?>]
    },
    labels: {
        items: [{
            html: 'Total Geral',
            style: {
                left: '30px',
                top: '60px',
                color: (Highcharts.theme && Highcharts.theme.textColor) || 'black'
            }
        }]
    },
    series: [{
        type: 'column',
        name: 'Baixados',
        data: [<?php echo $baixados; ?>]
    }, {
        type: 'column',
        name: 'Em Andamento',
        data: [<?php echo $em_andamento; ?>]
    }, {
        type: 'column',
        name: 'Total',
        data: [<?php echo $total; ?>]
    }, {
        type: 'spline',
        name: 'Média',
        data: [<?php echo $media; ?>],
        marker: {
            lineWidth: 2,
            lineColor: Highcharts.getOptions().colors[3],
            fillColor: 'white'
        }
    }, {
        type: 'pie',
        name: 'Totais gerais',
        data: [{
            name: 'Baixados',
            y: <?php echo $baixadosTT; ?>,
            color: Highcharts.getOptions().colors[0] // Jane's color
        }, {
            name: 'Em Andamento',
            y: <?php echo $em_andamentoTT; ?>,
            color: Highcharts.getOptions().colors[1] // John's color
        }, {
            name: 'Total',
            y: <?php echo $totalTT; ?>,
            color: Highcharts.getOptions().colors[2] // Joe's color
        }],
        center: [40, 00],
        size: 80,
        showInLegend: false,
        dataLabels: {
            enabled: false
        }
    }]
});
$('#spinner2 .btn:first-of-type').on('click', function() { //UP
            var value = $('#spinner2 input').val();
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
            $('#INPUTINI').val(mes+'/'+year);

      });

$('#spinner2 .btn:last-of-type').on('click', function() {  //DOWN
            var value = $('#spinner2 input').val();
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
            $('#INPUTINI').val(mes+'/'+year);
     });

$('#spinner .btn:first-of-type').on('click', function() { //UP
            var value = $('#spinner input').val();
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
            $('#INPUTFIM').val(mes+'/'+year);

      });

$('#spinner .btn:last-of-type').on('click', function() {  //DOWN
            var value = $('#spinner input').val();
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
            $('#INPUTFIM').val(mes+'/'+year);
     });

jQuery(function($){
    $('#INPUTINI').mask("99/9999");
    $('#INPUTFIM').mask("99/9999");
});

</script>

@stop
