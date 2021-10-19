@extends('...layouts.master')

@section('content')

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="content-top">
        <div class="row">
            <div class="col-md-12">
                <h1 class="title">Documentos CND (<?php echo $_GET['classificacaoCND']; ?>) - <?php echo $_GET['periodo']; ?></h1>
            </div>
        </div>
    </div>
    <div class="table-default table-responsive">
        <table class="table display" id="monitorcnd-table">
            <thead>
            <tr class="top-table">
                <th>ID</th>
                <th>Area</th>
                <th>CNPJ</th>
                <th>UF</th>
                <th>I.E.</th>
                <th>Tipo de CND</th>
                <th>Classificação da CND</th>
                <th>Número da CND</th>
                <th>Validade</th>
                <th>Anexo</th>
            </tr>
            </thead>
        </table>
    </div>

    <a href="{{ route('monitorcnd.graficos') }}" class="btn btn-default">Voltar</a>

    <script>
        var file;
        $(function() {

            $('#monitorcnd-table').DataTable({
                stateSave: true,
                responsive: true,
                searching: true,
                ajax: {
                    url: "{!! route('monitorcnd.dashboardData', ["classificacaoCND" => $classificacaoCND, "periodo" => $periodo]) !!}"
                },
                columns: [
                    {
                        data: 'id',
                        name: 'DocumentoCND.id'
                    },
                    {
                        data: 'codigo',
                        name: 'estabelecimentos.codigo'
                    },
                    {
                        data: 'cnpj',
                        name: 'estabelecimentos.cnpj'
                    },
                    {
                        data: 'uf',
                        name: 'estabelecimentos.uf'
                    },
                    {
                        data: 'insc_estadual',
                        name: 'estabelecimentos.insc_estadual'
                    },
                    {
                        data: 'tipocnd_descricao',
                        name: 'tipocnd_descricao'
                    },
                    {
                        data: 'classificacaocnd_descricao',
                        name: 'classificacaocnd_descricao'
                    },
                    {
                        data: 'numero_cnd',
                        name: 'DocumentoCND.numero_cnd'
                    },
                    {
                        data: 'validade_cnd',
                        name: 'DocumentoCND.validade_cnd'
                    },
                    {
                        data: 'arquivo_cnd',
                        name: 'DocumentoCND.arquivo_cnd',
                        render: function (data, type, row) {
                            if(data != null) {
                                url = "<a href='\\"+data+"' class=\"btn btn-info\" role=\"button\" target=\"_blank\">Link do Anexo</a>";
                            } else {
                                url = "Sem anexo.";
                            }
                            return url;
                        }
                    }
                ],

/*                order: [[ 4, "asc" ]],*/
                language: {
                    "searchPlaceholder": "ID, AREA ou CNPJ",
                    "sSearch": "Pesquisar",
                    // "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json",
                    // "processing": "<img src='/assets/img/loading_16.gif' />",
                },
/*                aLengthMenu: [
                    [10, 25, 50, 100, 3000],
                    [10, 25, 50, 100, 3000]
                ],
                iDisplayLength: 10,*/
                dom: 'l<"centerBtn"B>frtip',
                buttons: [
                    //'copyHtml5',
                    //'excelHtml5',
                    //'csvHtml5',
                    //'pdfHtml5'
                ]
            });

            $('#adv_search').on('click', function(e) {
                $(".bt-download-principal").addClass("hidden");
                $("#spinner").removeClass("hidden");

                var val_cnpj    = $('#src_cnpj').val();
                var val_codigo  = $('#src_codigo').val();
                var val_uf      = $('#src_uf').val();
                var val_tributo = $('#src_tributo').val();
                var val_status  = $('#src_status option:selected').val();

                if (val_cnpj || val_codigo || val_status || val_uf || val_tributo) {
                    var url = "{{ route('entregas.index') }}?vcn="+val_cnpj.replace(/[^0-9]/g,'')+"&vco="+val_codigo+"&vst="+val_status+"&vuf="+val_uf+"&vtb="+val_tributo;

                } else {
                    var url = "{{ route('entregas.index') }}";
                }

                $("body").css("cursor", "progress");

                location.replace(url);
                //window.location.replace(url);


            });

            $('#src_status').change(function(){
                //$("body").css("cursor", "progress");
                //$('#adv_search').click();

            });


            //change csv created André 04/02/2020
            $.ajax({

                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                cache:true,
                type: 'GET',
                url:"{!! route('entregas.csv') !!}",
                data: {codigo:  $('#src_codigo').val(),cnpj: $('#src_cnpj').val(),uf:$('#src_uf').val(),status_filter:$('#src_status option:selected').val(),tributo:$('#src_tributo').val()},
                dataType: "json",
                success: function(data) {
                    file = data;

                    //CASE LINUX
                    file = file.replace('/var/www/html/agenda/public/' , '');
                    file = file.replace('assetsdownloadcsv','assets/download/csv/')

                    //CASE WINDOWS
                    file = file.replace('F:\\wamp64\\www\\agenda\\public\\assets\\download\\csv\\' , '\\assets\\download\\csv\\');

                    console.log(file,"carregando...");
                    $('.bt-download').children().prop('disabled',false);
                    $('#div-text-download').text('Download');
                }
            });


            //created André 31/01/2020
            $(".downloadcsv").on('click',function(){

                console.log(file,"carregadooooooo no botão...");
                //console.log(file);
                var asset = "{!! asset('f') !!}" ;
                asset = asset.replace('f' , file );

                $(this).attr("href",   file  ); // Set herf value


            })

        });

        jQuery(function($){
            $('input[name="src_cnpj"]').mask("99.999.999/9999-99");
        });
    </script>
@stop

