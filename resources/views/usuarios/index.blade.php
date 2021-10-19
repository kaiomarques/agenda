@extends('...layouts.master')

@section('content')

<h1>Usuários</h1>
<p class="lead">Segue a lista de todos os usuários cadastrados.</p>
<hr>
<table class="table table-bordered display" id="usuarios-table">
    <thead>
    <tr>
        <th>NOME</th>
        <th>E-MAIL</th>
        <th>PERFIL</th>
        <th></th>
    </tr>
    </thead>
</table>
<script>
$(function() {

    $('#usuarios-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: "{!! route('usuarios.data') !!}",
        columns: [
            {data: 'name', name: 'name'},
            {data: 'email', name: 'email'},
            {data: 'roles[0].display_name', name: 'roles.display_name'},
            
            {data: 'id', name:'edit', searchable: false, orderable: false, render: function (data) {

                var url = '<a href="{{ route('usuarios.show', ':id_show') }}" class="btn btn-default btn-sm">Mostrar</a>';
                @if ( Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin'))
                url += '<a href="{{ route('usuarios.edit', ':id_edit') }}" style="margin-left:10px" class="btn btn-default btn-sm">Alterar</a>';
                url = url.replace(':id_edit', data);
                @endif;
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
             {
                extend: 'copyHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2]
                }
             },
             {
                extend: 'excelHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2]
                }
             },
             {
                extend: 'csvHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2]
                }
             },
             {
                extend: 'pdfHtml5',
                exportOptions: {
                   columns: [ 0, 1, 2]
                }
             },
         ]

    });

});

</script>
@stop
