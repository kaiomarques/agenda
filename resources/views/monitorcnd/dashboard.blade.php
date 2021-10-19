@extends('layouts.master')

@section('content')

    @if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('gbravo')  || Auth::user()->hasRole('gcliente'))

        <form action="{{ route('monitorcnd.dashboardRLT') }}" method="GET" id="relatorioConsulta">
            <input type="hidden" name="classificacaoCND" id="classificacaoCND" value="">
            <input type="hidden" name="periodo" id="periodo" value="">
        </form>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="header-grafh darkcyan">
                        Monitoramento CND - Estadual
                    </div>
                    <div id="container" style="height: 500px;">Dashboard</div>
                </div>
            </div>
        </div>

        <script>
            var graph_categories = [<?= "'Vencidos','Vence esse mês','Vence próximo mês', 'Sem certidão'" ?>];
            var graph_data = [[{{implode(',',$dados[1])}}],[{{implode(',',$dados[2])}}],[{{implode(',',$dados[3])}}],[{{implode(',',$dados[4])}}]];

            $(function () {

                setInterval(function(){ $( '#atualiza_btn' ).click() }, 300000);

                $.fn.bootstrapSwitch.defaults.onText = 'P.A.';
                $.fn.bootstrapSwitch.defaults.offText = 'D.E.';
                $("[name='pa-checkbox']").bootstrapSwitch();

                $('#container').highcharts({
                    chart: {
                        type: 'bar'
                    },
                    title: {
                        text: ''
                    },
                    xAxis: {
                        categories: graph_categories
                    },
                    yAxis: {
                        min: 0,
                        max: <?php echo $maior; ?>,
                        title: {
                            text: 'Total (%) entregas'
                        },
                        stackLabels: {
                            enabled: true,
                            style: {
                                fontWeight: 'bold',
                                color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                            }
                        }
                    },
                    tooltip: {
                        valueSuffix: ''
                    },
                    plotOptions: {
                        bar: {
                            dataLabels: {
                                enabled: true
                            }
                        }
                    },
                    legend: {
                        layout: 'vertical',
                        align: 'right',
                        verticalAlign: 'top',
                        x: -40,
                        y: 80,
                        floating: true,
                        borderWidth: 1,
                        backgroundColor:
                            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
                        shadow: true
                    },
                    plotOptions: {
                        series: {
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function () {
                                        $("body").css("cursor", "progress");
                                        var periodo = this.category;
                                        var classificacaocnd = this.series.name;
                                        mes = this.category;
                                        $("#classificacaoCND").val(classificacaocnd);
                                        $("#periodo").val(periodo);
                                        $("#relatorioConsulta").submit();
                                    }
                                }
                            }
                        }
                    },
                    series: [{
                        name: 'Positiva',
                        data: graph_data[0],
                        color: '#DDDDDD'
                    }, {
                        name: 'Positiva com efeito negativa',
                        data: graph_data[1],
                        color: Highcharts.getOptions().colors[0]
                    }, {
                        name: 'Negativa',
                        data: graph_data[2],
                        color: Highcharts.getOptions().colors[1]
                    }, {
                        name: 'Sem certidão',
                        data: graph_data[3],
                        color: Highcharts.getOptions().colors[2]
                    }]
                });
            });



/*            (function ($) {
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
            })(jQuery);*/

/*            $('input[type=radio][name=tipo_tributos]').on('change', function() {
                $("body").css("cursor", "progress");
                $( "#atualiza_btn" ).click();
            });*/

/*            $('input[name="pa-checkbox"]').on('switchChange.bootstrapSwitch', function(event, state) {

                $('input[name="switch_periodo"]').val(state?1:0);
                $("body").css("cursor", "progress");
                $( "#atualiza_btn" ).click();
            });*/

            //Loading
/*            $( "#btn_dashboard_analista" ).click(function() {
                $("body").css("cursor", "progress");
            });

            $( "#atualiza_btn" ).click(function() {
                $("body").css("cursor", "progress");
            });*/
        </script>

    @endif

@stop
<footer>
    @include('layouts.footer')
</footer>

