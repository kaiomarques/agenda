@extends('...layouts.master')

@section('content')

<h1>Empresas</h1>
<p class="lead">Segue a lista de todas as empresas cadastradas. <a href="{{ route('empresas.create') }}">Adicionar outra?</a></p>
<hr>
<table class="table table-bordered display" id="empresas-table">
    <thead>
    <tr>
        <!--th>ID</th-->
        <th>CNPJ</th>
        <th>RAZÃO SOCIAL</th>
        <!--th>ENDEREÇO</th>
        <th>NUM</th-->
        <th>MUNICÍPIO</th>
        <th>UF</th>
        <th></th>

    </tr>
    </thead>
</table>
<script>
function printMask(data) {
        return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14);
}

$(function() {

    $('#empresas-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{!! route('empresas.data') !!}",
        columns: [
            /*{data: 'id', name: 'id'},*/
            {data: 'cnpj', name: 'cnpj',render: function ( data ) {
                                                      return printMask(data);
                                                    }},
            {data: 'razao_social', name: 'razao_social'},
            {data: 'municipio.nome', name: 'municipio.nome', orderable: false},
            {data: 'municipio.uf', name: 'municipio.uf', orderable: false},
            {data: 'id', name:'edit', searchable: false, orderable: false, render: function (data) {

                                                    var url = '<a href="{{ route('empresas.edit', ':id_edit') }}" class="btn btn-default btn-sm">Alterar</a>';
                                                    url += '<a href="{{ route('empresas.show', ':id_show') }}" style="margin-left:10px" class="btn btn-default btn-sm">Mostrar</a>';
                                                    url = url.replace(':id_edit', data);
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
