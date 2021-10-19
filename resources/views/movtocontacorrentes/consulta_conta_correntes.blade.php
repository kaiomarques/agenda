@extends('...layouts.master')

@section('content')

<div class="content-top">
    <div class="row">
        <div class="col-md-4">
            <h1 class="title">Consulta conta corrente</h1>
        </div>
         <div class="col-md-8">
        <div class="refresh-option">
        <form action="{{ route('consulta_conta_corrente') }}" method="get">
        <button type="submit" class="refresh-icon"><i class="fa fa-refresh"></i></button>
        
        </div>
            <div class="period">
                <div class="input-group spinner">
                    <input type="text" id="periodo_fim" name="dataExibe[periodo_fim]" class="form-control" value="{{$dataExibe['periodo_fim']}}">
                    <div class="input-group-btn-vertical">
                    <button class="btn btn-default" id="up1" type="button"><i class="fa fa-caret-up"></i></button>
                    <button class="btn btn-default" id="down1" type="button"><i class="fa fa-caret-down"></i></button>
                    </div>
                </div>
                <span>Fim:</span>
                <div class="input-group spinner">
                    <input type="text" id="periodo_inicio" class="form-control" name="dataExibe[periodo_inicio]" value="{{$dataExibe['periodo_inicio']}}">
                    <div class="input-group-btn-vertical">
                    <button class="btn btn-default" id="up2" type="button"><i class="fa fa-caret-up"></i></button>
                    <button class="btn btn-default" id="down2" type="button"><i class="fa fa-caret-down"></i></button>
                    </div>
                </div>
                <span>Inicio:</span>
            </div>
            </form>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="header-grafh blue">
                Conta Corrente Por Divergência em Andamento
            </div>
            <div id="graph_container_1" style="height: 250px" onclick="hrefCont1()">Entrada</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="header-grafh red">
                Conta Corrente Por UF
            </div>
            <div id="graph_container_2" style="height: 250px" onclick="hrefCont1()">Saida</div>
        </div>
    </div>
</div>

<div class="row mgT30">
    <div class="col-md-12">
        <div class="card">
            <div class="header-grafh">
                Conta Corrente Por Período
            </div>
            <div id="graph_container_3" style="height: 240px" onclick="hrefCont1()">Consolidado</div>
        </div>
    </div>
</div>
<form method="get" id="form1" action="{{ route('consulta_conta_corrente_rlt_1') }}">
    <input type="hidden" name="dataExibe[periodo_fim]" value="{{$dataExibe['periodo_fim']}}">
    <input type="hidden" name="dataExibe[periodo_inicio]" value="{{$dataExibe['periodo_inicio']}}">
</form>


<!-- Primeiro gráfico -->
<?php
$guiaxsped = '';
$giaxsped ='';
$guiaxgia ='';
$guiaxdipam = '';
$giaxdipam ='';
$spedxdipam ='';
if (!empty($graph1)) {
    $dadosGraph1 = json_decode($graph1, true);
    foreach ($dadosGraph1 as $var => $dadosArray) {
        $dadosGrafico[$dadosArray['periodo_apuracao']] = $dadosArray;
    }    
    if (!empty($dadosGraph1)) {
        foreach ($dadosGrafico as $periodo_apuracao => $dados) {

            $guiaxsped .= $dados['GUIASPED'].',';
            $giaxsped .=$dados['GIASPED'].',';
            $guiaxgia .=$dados['GUIAGIA'].',';
            $guiaxdipam .= $dados['GUIADIPAM'].',';
            $giaxdipam .=$dados['GIADIPAM'].',';
            $spedxdipam .=$dados['SPEDDIPAM'].',';
        }
        $guiaxsped = substr($guiaxsped,0,-1);
        $giaxsped = substr($giaxsped,0,-1);
        $guiaxgia = substr($guiaxgia,0,-1);
        $guiaxdipam = substr($guiaxdipam,0,-1);
        $giaxdipam = substr($giaxdipam,0,-1);
        $spedxdipam = substr($spedxdipam,0,-1);   
    }
}
?>
<!-- fim primeiro gráfico -->


<!-- Segundo gráfico -->
<?php if (!empty($graph2)) {
        $dadosGraph2 = json_decode($graph2,true); ?>
    <table id="datatable31" style="display:none">
    <thead>
        <tr>
            <th></th>
            <th>Em Andamento</th>
            <th>Baixados</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($dadosGraph2 as $key => $value) { ?>
        <tr>
            <th><?php echo $value['uf']; ?></th>
            <td><?php echo $value['NaoBaixado']; ?></td>
            <td><?php echo $value['Baixado']; ?></td>
        </tr>
    <?php } ?> 
    </tbody>
    </table>
<?php } ?>
<!-- Fim do segundo gráfico -->


<!-- Terceiro gráfico -->
<?php

    $baixado3 = '';
    $naobaixado3 = '';
    $periodo3 = '';
    $valores = '';
    if (!empty($graph3)) {
        $dadosGraph3 = json_decode(json_encode($graph3),true);
        foreach ($dadosGraph3 as $key => $value) {
            $valores[$value['periodo_apuracao']] = $value;
        }

        foreach ($dadosGraph3 as $var3 => $dadosArray3) {
            $baixado3 .= $dadosArray3['Baixado'].',';
            $naobaixado3 .= $dadosArray3['NaoBaixado'].',';
            $periodo3 .= $dadosArray3['periodo_apuracao'].',';
        }

    $baixado3 = substr($baixado3,0,-1);
    $naobaixado3 = substr($naobaixado3,0,-1);
    }
?>
<!-- Fim do terceiro Gráfico -->
<script src="https://code.highcharts.com/modules/data.js"></script>
<script>

var graph_categories3 = 0;
var graph_categories = 0;
<?php if (!empty($valores)) { ?>
var graph_categories3 = [<?= "'" . implode("','", array_keys($valores)) . "'" ?>];
<?php } ?>

<?php if (!empty($dadosGrafico)) { ?> 
var graph_categories = [<?= "'" . implode("','", array_keys($dadosGrafico)) . "'" ?>];
<?php } ?>

$('#graph_container_1').highcharts({
    chart: {
        type: 'bar'
    },
    title: {
        text: ''
    },
    xAxis: {
        categories:graph_categories
    },
    yAxis: {
        min: 0,
        title: {
            text: ''
        }
    },
    legend: {
        reversed: true
    },
    plotOptions: {
        series: {
            stacking: 'normal'
        }
    },

    series: [{
        name: 'GUIA x SPED',
        data: [<?php if (!empty($guiaxsped)) { echo $guiaxsped; } else { echo '0'; } ?>]
    }, {
        name: 'GIA x SPED',
        data: [<?php if (!empty($giaxsped)) { echo $giaxsped; } else { echo '0'; } ?>]
    }, {
        name: 'GUIA x GIA',
        data: [<?php if (!empty($guiaxgia)) { echo $guiaxgia; } else { echo '0'; } ?>]
    }, {
        name: 'GUIA x DIPAM',
        data: [<?php if (!empty($guiaxdipam)) { echo $guiaxdipam; } else { echo '0'; } ?>]
    }, {
        name: 'GIA x DIPAM',
        data: [<?php if (!empty($giaxdipam)) { echo $giaxdipam; } else { echo '0'; } ?>]
    }, {
        name: 'SPED x DIPAM',
        data: [<?php if (!empty($spedxdipam)) { echo $spedxdipam; } else { echo '0'; } ?>]
    }]
});

$('#graph_container_2').highcharts({
    data: {
        table: 'datatable31'
    },
    chart: {
        type: 'column'
    },
    title: {
        text: ''
    },
    yAxis: {
        allowDecimals: false,
        title: {
            text: 'Units'
        }
    },
    tooltip: {
        formatter: function () {
            return '<b>' + this.series.name + '</b><br/>' +
                this.point.y + ' ' + this.point.name.toLowerCase();
        }
    }
});

$('#graph_container_3').highcharts({
    title: {
        text: ''
    },

    subtitle: {
        text: ''
    },

    yAxis: {
        title: {
            text: ''
        }
    },
    xAxis: {
            categories: graph_categories3
        },
    legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'middle'
    },

    plotOptions: {
        series: {
            label: {
                connectorAllowed: false
            },
        }
    },

    series: [{
        name: 'Em Andamento',
        data: [<?php echo $naobaixado3; ?>]
    }, {
        name: 'Baixado',
        data: [<?php echo $baixado3; ?>]
    }], 

    responsive: {
        rules: [{
            condition: {
                maxWidth: 500
            },
            chartOptions: {
                legend: {
                    layout: 'horizontal',
                    align: 'center',
                    verticalAlign: 'bottom'
                }
            }
        }]
    }
});



$('#up1').on('click', function() { //UP
            var value = $('#periodo_fim').val();

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
            if (year.length == 2) {
            year = '20'+year;
            }
            
            $('#periodo_fim').val(mes+'/'+year);
      });

 $('#down1').on('click', function() {  //DOWN
        var value = $('#periodo_fim').val();

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
        if (year.length == 2) {
            year = '20'+year;
        }

        $('#periodo_fim').val(mes+'/'+year);
 });


 $('#up2').on('click', function() { //UP
        var value = $('#periodo_inicio').val();

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
        if (year.length == 2) {
            year = '20'+year;
        }

        $('#periodo_inicio').val(mes+'/'+year);
  });

 $('#down2').on('click', function() {  //DOWN
        var value = $('#periodo_inicio').val();

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
        if (year.length == 2) {
            year = '20'+year;
        }

        $('#periodo_inicio').val(mes+'/'+year);
 });


 function hrefCont1(){
    $('#form1').submit();
 }
</script>
@stop
