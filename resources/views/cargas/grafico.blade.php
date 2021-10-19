@extends('...layouts.master')

@section('content')

<div class="content-top">
    <div class="row">
        <div class="col-md-4">
            <h1 class="title">Status das Integrações</h1>
        </div>
    </div>
</div>

<div class="row" style="margin-top: -10px;">
    <div class="col-md-6">
        <div class="card">
            <div class="header-grafh blue">
                Status Cargas (Entrada)
            </div>
            <div id="graph_container_1" style="height: 235px">Entrada</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="header-grafh red">
                Status Cargas (Saída)
            </div>
            <div id="graph_container_2" style="height: 235px">Saida</div>
        </div>
    </div>
</div>

<div class="row" style="margin-top: 10px;">
    <div class="col-md-12">
        <div class="card">
            <div class="header-grafh">
                Status Cargas (Consolidado)
            </div>
            <div id="graph_container_3" style="height: 235px">Consolidado</div>
        </div>
    </div>
</div>

<script>

var cargas_entrada = {{ $graph_data['E'] }};
var cargas_saida = {{ $graph_data['S'] }};
var cargas_completa = {{ $graph_data['C'] }};
var cargas_totais = {{ $graph_data['T'] }};

$('#graph_container_1').highcharts({
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
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Cargas (efet./total): <b>{point.y} / {point.total}</b>'
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
            name: 'Percentual',
            colorByPoint: true,
            data: [{
                name: 'Não efetuada',
                y: cargas_totais-cargas_entrada,
                color: '#DF5353' // red
            }, {
                name: 'Efetuada',
                y: cargas_entrada,
                color: Highcharts.getOptions().colors[2]
            }]
        }]
});

$('#graph_container_2').highcharts({
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
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Cargas (efet./total): <b>{point.y} / {point.total}</b>'
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
            name: 'Percentual',
            colorByPoint: true,
            data: [{
                name: 'Não efetuada',
                y: cargas_totais-cargas_saida,
                color: '#DF5353' // red
            }, {
                name: 'Efetuada',
                y: cargas_saida,
                color: Highcharts.getOptions().colors[2]
            }]
        }]
});

$('#graph_container_3').highcharts({
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
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>Cargas (efet./total): <b>{point.y} / {point.total}</b>'
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
            name: 'Percentual',
            colorByPoint: true,
            data: [{
                name: 'Não efetuada',
                y: cargas_totais-cargas_completa,
                color: '#DF5353' // red
            }, {
                name: 'Efetuada',
                y: cargas_completa,
                color: Highcharts.getOptions().colors[2]
            }]
        }]
});
</script>
@stop
