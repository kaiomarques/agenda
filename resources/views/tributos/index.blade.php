@extends('...layouts.master')

@section('content')

<h1>Tributos</h1>
<p class="lead">Abaixo a lista de todos os tributos cadastrados.</p>
<hr>
<table class="table table-bordered display" id="tributos-table">
    <thead>
    <tr>
        <!--th>ID</th-->
        <th>NOME</th>
        <th>DESCRIÇÃO</th>
        <th>CATEGORIA</th>
        <th>TIPO</th>
        <th>RECIBO</th>
        <th>ALERTA</th>
        <th></th>
    </tr>
    </thead>
</table>
<script>
$(function() {

    $('#tributos-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{!! route('tributos.data') !!}",
        columns: [
            /*{data: 'id', name: 'id'},*/
            {data: 'nome', name: 'nome'},
            {data: 'descricao', name: 'descricao'},
            {data: 'categoria.nome', name: 'categoria',searchable: false, orderable: false},
            {data: 'tipo', name:'tipo', render: function (data) {
                                                                    var retval= '';
                                                                    switch(data) {
                                                                        case 'F':
                                                                            retval = 'Federal';
                                                                            break;
                                                                        case 'E':
                                                                            retval = 'Estadual';
                                                                            break;
                                                                        case 'M':
                                                                            retval = 'Municipal';
                                                                            break;
                                                                        default:
                                                                            break;
                                                                    }
                                                                    return retval;
                                                                }
            },
            {data: 'recibo', name:'recibo', render: function (data) {
                                                                    var retval= 'Não';
                                                                    if(data==1) {
                                                                            retval = 'Sim';
                                                                    }
                                                                    return retval;
                                                                }
            },
            {data: 'alerta', name:'alerta', render: function (data) {
                                                                    var retval= 'Nenhuma';
                                                                    if(data>0) {
                                                                            retval = data+' dias';
                                                                    }
                                                                    return retval;
                                                                }
            },
            {data: 'id', name:'edit', searchable: false, orderable: false, render: function (data) {

                                                                 var url = '<a href="{{ route('tributos.show', ':id_show') }}" style="margin-left:10px" class="btn btn-default btn-sm">Mostrar</a>';
                                                                 url = url.replace(':id_show', data);
                                                                 return url;
            }}
        ],
        language: {
                    //"searchPlaceholder": "ID, P.A. ou descrição",
                    "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
             'copyHtml5',
             'excelHtml5',
             'csvHtml5',
             'pdfHtml5'
         ]

    });

});

</script>
@stop
