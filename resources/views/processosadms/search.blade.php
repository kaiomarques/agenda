@extends('...layouts.master')

@section('content')
<?php 
    $data = date('d/m/Y H:i:s');
?>
<h1>Processos Administrativos</h1>
<p class="lead">Segue consulta de cadastros realizados. <a href="{{ route('processosadms.create') }}">Adicionar outro?</a></p>
<hr>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Gráfico - Conta Corrente</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row">
            <div id="container" style="min-width: 550px; height: 400px; margin: 0 auto"></div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>



<div class="modal fade" id="myModalObservacao" style="width: 100%;" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Observações</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="observacaoHTML" style="width: 100%; height: 100%;">
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<table class="table table-bordered display" id="processosadms-table" style="width: 100%; height: 100%; font-size: 11px;">
    <thead>
    <tr>
        <td colspan="11">
            <input style="width: 145px; position:relative; left:10px; " placeholder="Período" type="text" id="src_periodo" name="src_periodo" value="<?= $filter_periodo ?>">
            <input style="width: 145px; position:relative; left:10px; " placeholder="CNPJ" type="text" id="src_cnpj" name="src_cnpj" value="<?= $filter_cnpj ?>">
            <input style="width: 145px; position:relative; left:10px; " placeholder="Area" type="text" id="src_area" name="src_area" value="<?= $filter_area ?>">
            <button id="adv_search" style="position:relative; left:10px;">BUSCAR</button>
            <button id="btn_grafico" style="position:relative; left:10px;">GRÁFICO</button>
        </td>
    </tr>
    <tr>
        <th>CÓDIGO</th>
        <th>PERÍODO</th>
        <th>CNPJ</th>
        <th>I.E</th>
        <th>CIDADE</th>
        <th>UF</th>
        <th>PRO NRO</th>
        <th>R.FINANCEIRO</th>
        <th>R.ACOMPANHAMENTO</th>
        <th>STATUS</th>
        <th>AÇÕES</th>
    </tr>
    </thead>
</table>

<?php 
    $categoria = array();
    $dataDif = array();
    $dataNDif = array();
    $dataDifString = '';
    $dataNDifString = '';
    if (count($graphs) > 0) {

        foreach ($graphs as $el) {
            $categoria[] = $el->uf;
            $dataDif[] = $el->Andamento;
            $dataNDif[] = $el->Baixada;
        }
    }

    $dataDifString = implode(",", $dataDif);
    $dataNDifString = implode(",", $dataNDif);
?>
<script>
function printMask(data) {
        return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}
function mascaraValor(valor) {
    valor = valor.toString().replace(/\D/g,"");
    valor = valor.toString().replace(/(\d)(\d{8})$/,"$1.$2");
    valor = valor.toString().replace(/(\d)(\d{5})$/,"$1.$2");
    valor = valor.toString().replace(/(\d)(\d{2})$/,"$1,$2");
    return valor                    
}

function dataHora()
{
    var now = new Date();
    return now.format("dd/MM/yyyy HH:mm:ss");
}

var graph_categories = [<?= "'" . implode("','", $categoria) . "'" ?>];

$(function() {

    Highcharts.chart('container', {
    chart: {
        type: 'column'
    },
    title: {
        text: 'Processos Administrativos Baixado/Andamento'
    },
    xAxis: {
        categories: graph_categories
    },
    yAxis: {
            min: 0,
            max: 100,
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
    legend: {
            align: 'right',
            x: 0,
            verticalAlign: 'top',
            y: 25,
            //floating: true,
            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
            borderColor: '#CCC',
            borderWidth: 1,
            shadow: false
        },
        tooltip: {
            headerFormat: '<b>{point.x}</b><br/>',
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
        },

    plotOptions: {
        format: '<b>{point.name}</b>: {point.percentage:.1f} %',
        column: {
            stacking: 'percent',
            dataLabels: {
                enabled: true,
                color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                style: {
                    textShadow: '0 0 5px black'
                }
            }
        },
    },
    series: [{
        name: 'BAIXADO',
        color: '#55BF3B',
        data: [<?php echo $dataNDifString; ?>]
    }, {
        name: 'EM ANDAMENTO',
        color: '#FC6F6F',
        data: [<?php echo $dataDifString; ?>]
    }]
});


    $('#processosadms-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: {
                url: "{!! route('processosadms.data') !!}",
                data: function (d) {
                    d.area = $('#src_area').val();
                    d.cnpj = $('#src_cnpj').val();
                    d.periodo = $('#src_periodo').val();
                }
        },
        columns: [
            {data: 'id', name: 'id'},
            {data: 'periodo_apuracao', name: 'periodo_apuracao'},
            {data: 'estabelecimentos.cnpj', name: 'estabelecimentos.cnpj',render: function ( data ) {
                                                      return printMask(data);
                                                    }},
            {data: 'estabelecimentos.insc_estadual', name: 'estabelecimentos.insc_estadual'},
            {data: 'estabelecimentos.municipio.nome', name: 'estabelecimentos.municipio.nome'},
            {data: 'estabelecimentos.municipio.uf', name: 'estabelecimentos.municipio.uf'},
            {data: 'nro_processo', name: 'nro_processo'},
            {data: 'respfinanceiro.descricao', name: 'respfinanceiro.descricao'},
            {data: 'resp_acompanhamento', name: 'resp_acompanhamento'},
            {data: 'statusprocadm.descricao', name: 'statusprocadm.descricao'},
            
            {data: 'IdProcessosAdms', name:'edit', searchable: false, orderable: false, render: function (data) {
                var url = ' <a href="javascript:void(0);" onclick="getObservacoes('+data+')" class="btn btn-info" title="Detalhes" alt="Detalhes"><i class="fa fa-file-text" aria-hidden="true"></i></a> ';
                url += ' <a href="{{ route('processosadms.edit', ':id_edit') }}" class="btn btn-primary" title="Editar" alt="Editar"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> ';
                url = url.replace(':id_edit', data);
                
                return url;
            }},
            {data: 'estabelecimentos.codigo', name: 'estabelecimentos.codigo', searchable: false},
            {data: 'observacoesGroupConcat', name: 'observacoesGroupConcat', searchable: false},
        ],
        "columnDefs": [
            { "width": "1%", "targets": 0 },
            { "width": "12%", "targets": 1 },
            { "width": "10%", "targets": 2 },
            { "width": "5%", "targets": 3 },
            { "width": "5%", "targets": 4 },
            { "width": "1%", "targets": 5 },
            { "width": "1%", "targets": 6 },
            { "width": "1%", "targets": 7 },
            { "width": "1%", "targets": 8 },
            { "width": "20%", "targets": 9 },
             { "width": "20%", "targets": 10 },
            { "width": "12%", "targets": 11, "visible":false, "title": "Codigo Filial"},
            { "width": "12%", "targets": 12, "visible":false, "title": "Observações"},
        ],
        language: {
            //"searchPlaceholder": "ID, P.A. ou descrição",
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
           
             {
                extend: 'excelHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 11, 9,12]
                }
             },
            
             {
                extend: 'csvHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 11, 9,12]
                }
             },

             {
                extend: 'copyHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                }
             },
             {
                extend: 'pdfHtml5',
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9]
                },
                customize: function(doc) {
                  doc.pageMargins = [ 180, 20, 120, 20 ];
               },
                message: '<?=$data;?>',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                title: 'Processos Administrativos',
                header: 'Processos Administrativos'
            }
        ],
        lengthMenu: [[10, 25, 100, -1], [10, 25, 100, "All"]]

    });
    
    
    $('#btn_grafico').on('click', function(e) {
        $('#myModal').modal();
    });

    $('#adv_search').on('click', function(e) {

        var val_cnpj        = $('#src_cnpj').val();
        var val_area        = $('#src_area').val();
        var val_periodo     = $('#src_periodo').val();

        if (val_cnpj || val_area || val_periodo) {
            var url = "{{ route('processosadms.search') }}?vcn="+val_cnpj.replace(/[^0-9]/g,'')+"&vco="+val_area+"&vcp="+val_periodo;
        } else {
            var url = "{{ route('processosadms.search') }}?clear=s";
        }
        $("body").css("cursor", "progress");
        location.replace(url);
    });

});

jQuery(function($){
    $('input[name="src_cnpj"]').mask("99.999.999/9999-99");
    $('input[name="src_periodo"]').mask("99/9999");
});

function getObservacoes(id)
{   
    $.ajax(
    {
        type: "GET",
        url: '{{ url('processosadms') }}/search_observacao',
        cache: false,
        async: false,
        dataType: "json",
        data:
        {
            'processosadm_id':id
        },
        success: function(d)
        {
            if (d.success) {
                var html = '';
                $.each( d.data.observacoes, function( index, value ){
                    html += '<div><b>'+value.nome+' - '+value.data+'<p><b>'+value.descricao+'</b></p></div><br>';
                });

                $("#observacaoHTML").html(html);
                $("#myModalObservacao").modal();

            } else {
                alert('Observação não encontrada');
            }
        }
    });      
}

</script>
@stop
