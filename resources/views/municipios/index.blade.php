@extends('...layouts.master')

@section('content')

<h1>Municipios</h1>
<p class="lead">Segue a lista de todos os municipios cadastrados.</p>
<hr>
<table class="table table-bordered display" id="municipios-table">
    <thead>
    <tr>
        <th>CODIGO</th>
        <th>NOME</th>
        <th>UF</th>
    </tr>
    </thead>
</table>
<script>
$(function() {

    $('#municipios-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{!! route('municipios.data') !!}",
        columns: [
            {data: 'codigo', name: 'codigo'},
            {data: 'nome', name: 'nome'},
            {data: 'uf', name: 'uf'}
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
