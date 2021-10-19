<meta name="_token" content="{!! csrf_token() !!}"/>
@extends('layouts.master')

@section('content')

@if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('supervisor'))

<div class="row">
    <div class="col-md-1 col-md-offset-1">
        <a href="{{ route('dashboard') }}" class="btn btn-default"><i class="fa fa-backward"></i> Voltar</a>
    </div>
    <div class="col-md-2">
        <div class="input-group spinner">
            <input type="text" class="form-control" value="{{substr($periodo,0,2)}}/{{substr($periodo,-4,4)}}">
            <div class="input-group-btn-vertical">
              <button class="btn btn-default" type="button"><i class="fa fa-caret-up"></i></button>
              <button class="btn btn-default" type="button"><i class="fa fa-caret-down"></i></button>
            </div>
        </div>
    </div>
    {!! Form::open([
        'route' => 'dashboard_tributo'
        ]) !!}
    {!! Form::hidden('periodo_apuracao', $periodo, ['class' => 'form-control']) !!}
    <div class="col-md-2 col-md-offset-4">
        {!! Form::select('tributo', $tributos, $tributo, ['style'=>'width:160px','class' => 'form-control']) !!}
    </div>
    <div class="col-md-2">
        {!! Form::button('<i class="fa fa-refresh"></i> Atualizar', array('id' => 'atualiza_btn', 'class'=>'btn btn-default', 'type'=>'submit')) !!}
    </div>
    {!! Form::close() !!}

</div>
<div id="container" style="float:left; width:100%; height:70%;">dashboard</div>
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Atividades selecionadas</h4>
      </div>
      <div class="modal-body">
        <p>Some text in the modal.</p>
      </div>
      <div class="modal-footer">
        <button id="sendmail_btn" type="button" class="btn btn-default"><i class="fa fa-envelope">Send Mail</i></button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>

<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/highcharts-more.js"></script>
<script>
$(function () {


setInterval(function(){ $( '#atualiza_btn' ).click() }, 300000);

<?php
    $array_entregue = array();
    $array_nentregue = array();
    $array_aprovacao = array();
    $array_entregue_vencidas = array();
    $array_nentregue_vencidas = array();
    $array_aprovacao_vencidas = array();

    foreach ($graph[$tributo] as $el) {
        $array_nentregue[] = isset($el['s1'])?$el['s1']:0;
        $array_aprovacao[] = isset($el['s2'])?$el['s2']:0;
        $array_entregue[] = isset($el['s3'])?$el['s3']:0;
        $array_nentregue_vencidas[] = isset($el['v1'])?$el['v1']:0;
        $array_aprovacao_vencidas[] = isset($el['v2'])?$el['v2']:0;
        $array_entregue_vencidas[] = isset($el['v3'])?$el['v3']:0;
    }
    $sum = array_map(function () {
            return array_sum(func_get_args());
        }, $array_entregue, $array_nentregue, $array_aprovacao,$array_entregue_vencidas,$array_nentregue_vencidas,$array_aprovacao_vencidas );


?>
    var graph_apuracao = '<?= substr($periodo,0,2).'-'.substr($periodo,-4,4) ?>';
    var graph_tributo = '<?= $tributo ?>';
    var graph_categories = [<?= "'" . implode("','", array_keys($graph[$tributo])) . "'" ?>];
    var graph_data = [[{{implode(',',$array_nentregue)}}],[{{implode(',',$array_nentregue_vencidas)}}],[{{implode(',',$array_aprovacao)}}],[{{implode(',',$array_aprovacao_vencidas)}}],[{{implode(',',$array_entregue)}}],[{{implode(',',$array_entregue_vencidas)}}],[{{implode(',',$sum)}}]];
    var graph_data_pie = [{{ array_sum($array_nentregue) }},{{ array_sum($array_nentregue_vencidas) }},{{ array_sum($array_aprovacao) }},{{ array_sum($array_aprovacao_vencidas) }},{{ array_sum($array_entregue) }},{{ array_sum($array_entregue_vencidas) }}];


    $('#container').highcharts({
        chart: {
                type: 'column'
        },
        title: {
            text: 'Entregas - (Apuração '+graph_apuracao+')'//+graph_tributo
        },
        xAxis: {
            categories: graph_categories
        },
        yAxis: {
                    title: {
                        text: 'Entregas'
                    }
        },
        labels: {
            items: [{
                html: 'Total entregas',
                style: {
                    left: '50px',
                    top: '18px',
                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'black'
                }
            }]
        },
        plotOptions: {
            column: {
                stacking: 'normal'
            },
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function () {
                            var serie = this.series.name;
                            var vencimento = this.category;

                            $.get("{{ url('/find-activities-detail')}}",

                                {   option_tributo: graph_tributo,
                                    option_periodo: graph_apuracao,
                                    option_data: vencimento,
                                    option_serie_id: this.series.index
                                    //option_serie_nome: this.series.name
                                },

                                function(data) {
                                   var html = '<table><tr>'
                                                +'<td style="padding:5px">ID</td>'
                                                +'<td style="padding:5px">ATIVIDADE</td>'
                                                +'<td style="padding:5px">FILIAL</td>'
                                             +'</tr>';
                                   $.each(data, function(index, element) {
                                        html += '<tr>';
                                            html += '<td style="padding:5px">';
                                                html +=  element.id;
                                            html += '</td>';
                                            html += '<td style="padding:5px">';
                                                html +=  element.descricao;
                                            html += '</td>';
                                            html += '<td style="padding:5px">';
                                                html +=  element.estemp.codigo;
                                            html += '</td>';
                                        html += '</tr>';
                                   });
                                   html += '</table>';

                                   $(".modal-title").html('Atividades com vencimento '+vencimento+' - '+serie+' - '+graph_apuracao);
                                   $(".modal-body").html(html);
                                   $("#myModal").modal();
                            });
                        }
                    }
                }
            }
        },
        series: [{
            //type: 'column',
            name: 'Não Entregue',
            data: graph_data[0],
            color: '#DDDDDD',
            stack: 'ne'
        }, {
            //type: 'column',
            name: 'Não Entregue (f.p.)',
            data: graph_data[1],
            color: '#FC6F6F',
            stack: 'ne'
        }, {
            //type: 'column',
            name: 'Em aprovação',
            data: graph_data[2],
            color: Highcharts.getOptions().colors[0],
            stack: 'ea'

        }, {
            //type: 'column',
            name: 'Em aprovação (f.p.)',
            data: graph_data[3],
            color: '#f2b44b',
            stack: 'ea'
        }, {
            //type: 'column',
            name: 'Entregue',
            data: graph_data[4],
            color: Highcharts.getOptions().colors[2],
            stack: 'en'
        }, {
             //type: 'column',
             name: 'Entregue (f.p.)',
             data: graph_data[5],
             color: '#F7F970',
             stack: 'en'
         },{
            type: 'spline',
            name: 'Total Entregas',
            data: graph_data[6],
            marker: {
                lineWidth: 2,
                lineColor: Highcharts.getOptions().colors[1],
                fillColor: 'white'
            }
        }, {
            type: 'pie',
            name: 'Total entregas',
            data: [{
                name: 'Não Entregue',
                y: graph_data_pie[0],
                color: '#DDDDDD'
            },{
                name: 'Não Entregue (f.p.)',
                y: graph_data_pie[1],
                color: '#FC6F6F'
            }, {
                name: 'Em aprovação',
                y: graph_data_pie[2],
                color: Highcharts.getOptions().colors[0]
            }, {
                name: 'Em aprovação (f.p.)',
                y: graph_data_pie[3],
                color: '#f2b44b'
            }, {
                name: 'Entregue',
                y: graph_data_pie[4],
                color: Highcharts.getOptions().colors[2]
            },{
                name: 'Entregue (f.p.)',
                y: graph_data_pie[5],
                color: '#F7F970'
            }],
            center: [75, 80],
            size: 100,
            showInLegend: false,
            dataLabels: {
                enabled: false
            }
        }]
    });


});

jQuery(document).ready(function($){

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

            $("body").css("cursor", "progress");
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

            $("body").css("cursor", "progress");
            $( "#atualiza_btn" ).click();
     });
});

$( "#atualiza_btn" ).click(function() {
 $("body").css("cursor", "progress");
});

$( "#sendmail_btn" ).click(function() {

    $.ajax({
      url: 'sendEmailExport',
      type: "post",
      data: {'html_head': $(".modal-title").html(),'html_body': $(".modal-body").html(),'user_id': {{ Auth::user()->id }} },
      success: function(data){
        $( "#sendmail_btn" ).html(data);
      }
    });
    $( "#sendmail_btn" ).html('Envio em andamento...');
    $( "#sendmail_btn" ).prop('disabled', true);
});

$.ajaxSetup({
   headers: { 'X-CSRF-Token' : $('meta[name=_token]').attr('content') }
});

</script>
@endif

@stop
<footer>
   @include('layouts.footer')
</footer>

