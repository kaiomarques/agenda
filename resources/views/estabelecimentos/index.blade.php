@extends('...layouts.master')

@section('content')

<h1>Estabelecimentos</h1>
<p class="lead">Segue a lista de todos os estabelecimentos cadastrados. <a href="{{ route('estabelecimentos.create') }}">Adicionar outro?</a></p>
<hr>
<table class="table table-bordered display" id="estabelecimentos-table">
    <thead>
    <tr>
        <th>CÓDIGO</th>
        <th>CNPJ</th>
        <!--th>RAZÃO SOCIAL</th-->
        <!--th>ENDEREÇO</th-->
        <!--th>NUM</th-->
        <th>INS.ESTADUAL</th>
        <th>CADASTRO</th>
        <th>MUNICÍPIO</th>
        <th>UF</th>
        <th></th>
        <th></th>
    </tr>
    </thead>
</table>
<script>

$(function() {

    $('#estabelecimentos-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{!! route('estabelecimentos.data') !!}",
        columns: [
            {data: 'codigo', name: 'codigo'},
            {data: 'cnpj', name: 'cnpj',render: function ( data ) {
                                                      return printMaskCnpj(data);
                                                    }},
            //{data: 'razao_social', name: 'razao_social'},
            //{data: 'endereco', name: 'endereco'},
            //{data: 'num_endereco', name: 'num_endereco'},
            {data: 'insc_estadual', name: 'insc_estadual'},
            {data: 'data_cadastro', name: 'data_cadastro', render: function ( data ) { return data.substring(8,10)+'-'+data.substring(5,7)+'-'+data.substring(0,4); } },
            {data: 'municipio.nome', name: 'municipio.nome', orderable: false, render: function (data, type, row) {

                                                    var retval = '<i style="margin-right:20px" title="Codigo: '+row['cod_municipio']+'" class="fa fa-university"></i>'+data;

                                                    return retval;
            }},
            {data: 'municipio.uf', name: 'municipio.uf', orderable: false},
            {data: 'id', name:'edit', searchable: false, orderable: false, render: function (data) {

                                                    var url = '<a href="{{ route('estabelecimentos.edit', ':id_edit') }}" class="btn btn-default btn-sm">Alterar</a>';
                                                    url += '<a href="{{ route('estabelecimentos.show', ':id_show') }}" style="margin-left:10px" class="btn btn-default btn-sm">Mostrar</a>';
                                                    url = url.replace(':id_edit', data);
                                                    url = url.replace(':id_show', data);
                                                    return url;
            }},
            {data: 'id', name:'ativo', searchable: false, orderable: false, render: function (data, type, row) {

                                                    var url = '';
                                                    if(row['ativo']==1) {
                                                        url += '<i title="estabelecimento ativo" class="fa fa-toggle-on"></i>';
                                                    } else {
                                                        url += '<i title="estabelecimento não ativo" class="fa fa-toggle-off"></i>';
                                                    }

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
         ],
         lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]

    });

});

</script>

@stop
