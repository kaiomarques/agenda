@extends('...layouts.master')

@section('content')

<h1>Categorias</h1>
<p class="lead">Segue a lista de todas as categorias cadastradas.</p>
<hr>
<table class="table table-bordered display" id="categorias-table">
    <thead>
    <tr>
        <!--th>ID</th-->
        <th>NOME</th>
        <th>DESCRIÇÃO</th>
    </tr>
    </thead>
</table>
<script>
$(function() {

    $('#categorias-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{!! route('categorias.data') !!}",
        columns: [
            /*{data: 'id', name: 'id'},*/
            {data: 'nome', name: 'nome'},
            {data: 'descricao', name: 'descricao'}
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
