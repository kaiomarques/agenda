@extends('layouts.master')

@section('content')

{!! Form::open([
    'route' => 'dashboard_analista'
]) !!}

<div class="content-top">
    <div class="row">
        <div class="col-md-4">
            <h1 class="title">Entregas por UF e Municípios</h1>
        </div>
        <div class="col-md-8">
            <div class="refresh-option">
                {!! Form::hidden('periodo_apuracao', $periodo, ['class' => 'form-control']) !!}
            </div>
            <div class="period">
                
                <div class="input-group spinner">
                    <input type="text" class="form-control" value="{{substr($periodo,0,2)}}/{{substr($periodo,-4,4)}}">
                    <div class="input-group-btn-vertical">
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-up"></i></button>
                    <button class="btn btn-default" type="button"><i class="fa fa-caret-down"></i></button>
                    </div>
                </div>
                <span>{!! Form::label('codigo', 'Periodo apuração:', ['class' => 'control-label']) !!}</span>
            </div>
        </div>
    </div>
</div>

<div class="select-options">
    <div class="row">
        <div class="col-md-3">
            {!! Form::label('uf', 'UF:', ['class' => 'control-label']) !!}
            {!! Form::select('uf', $ufs, $graph['params']['p_uf'], ['class' => 'form-control','placeholder' => 'Selecionar UF']) !!}
        </div>
        <div id="codigo_input" class="col-md-4">
            {!! Form::label('codigo', 'Municipio:', ['class' => 'control-label']) !!}
            {!! Form::select('codigo', $municipios, NULL, ['class' => 'form-control']) !!}
        </div>
        <div class="col-md-3">
            {!! Form::label('tributo', 'Tributo:', ['class' => 'control-label']) !!}
            {!! Form::select('tributo', $tributos, $graph['params']['p_tributo'], ['class' => 'form-control','placeholder' => 'Todos']) !!}
        </div>
        <div class="col-md-2">
            {!! Form::label('&nbsp;', '&nbsp;', ['class' => 'control-label']) !!}

            {!! Form::button('Pesquisar', array('id' => 'atualiza_btn', 'class'=>'btn btn-default form-control', 'type'=>'submit')) !!}
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="header-grafh darkcyan">
                Status entregas 
            </div>
            <div id="container" style="height: 450px;">dashboard-analista</div>
        </div>
    </div>
</div>


{!! Form::close() !!}


<script>
$(function () {

    var tot_status_1 = {{ ($graph['status_1']) }};
    var tot_status_2 = {{ ($graph['status_2']) }};
    var tot_status_3 = {{ ($graph['status_3']) }};
    var tot = tot_status_1+tot_status_2+tot_status_3;

    $('#container').highcharts({
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
                        y: tot_status_1
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
//setInterval(function(){ $( '#atualiza_btn' ).click() }, 300000);



});

function retriveMunicipios(uf,cod) {

    $.get("{{ url('/dropdown-municipios')}}",

                { option: uf },

                   function(data) {
                       var model = $('#codigo');
                       model.empty();
                       model.append("<option value='0'>Todos</option>");
                       $.each(data, function(index, element) {
                           model.append("<option value='"+ element.codigo +"'>" + element.nome + "</option>");
                       });

                       $('#codigo').val(cod).attr("selected", "selected"); //Reload codigo
                   }

    ); //Reload last list

}

jQuery(document).ready(function($){

    $('#only-uf').click (function(){

      if ( $(this).is(':checked') ) {
        $('#codigo_input').hide();
      } else {
        $('#codigo_input').show();
      }
    });

    @if ($graph['params']['p_uf'])
        retriveMunicipios('{{$graph['params']['p_uf']}}','{{$graph['params']['p_codigo']}}');
    @endif
    @if ($graph['params']['p_onlyuf'])
        $('#codigo_input').hide();
    @endif
	$('#uf').change(function(){

        $.get("{{ url('/dropdown-municipios')}}",
            { option: $(this).val() },

            function(data) {
                var model = $('#codigo');
                model.empty();
                model.append("<option value='0'>Todos</option>");
                $.each(data, function(index, element) {
                    model.append("<option value='"+ element.codigo +"'>" + element.nome + "</option>");
             });
        });
    });


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
});
</script>


@stop
<footer>
   @include('layouts.footer')
</footer>

