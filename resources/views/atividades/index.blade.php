@extends('...layouts.master')

@section('content')

<h1>Atividades</h1>
<p class="lead">Segue a lista de todas as atividades em aberto. <a href="#"></a></p>
<hr>
<table class="table table-bordered display" id="atividades-table">
    <thead>
    <tr>
        <td colspan="10">
            <input style="width: 145px; position:relative; left:10px; " placeholder="codigo" type="text" id="src_codigo" name="src_codigo" value="<?= $filter_codigo ?>">
            <input style="width: 145px; position:relative; left:10px; " placeholder="cnpj" type="text" id="src_cnpj" name="src_cnpj" value="<?= $filter_cnpj ?>">
            <button id="adv_search" style="position:relative; left:10px;">BUSCAR</button>
        </td>
    </tr>
    <tr>
        <th>ID</th>
        <th>DESCRIÇÃO</th>
        <th>TRIBUTO</th>
        <th>APURAÇÃO</th>
        <th>LIMITE</th>
        <th>CNPJ</th>
        <th>CODIGO</th>
        <th>STATUS</th>
        <th></th>

    </tr>
    </thead>
</table>
<script>
$(function() {

    $('#atividades-table').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: {
                url: "{!! route('atividades.data') !!}",
                data: function (d) {
                    d.codigo = $('#src_codigo').val();
                    d.cnpj = $('#src_cnpj').val();
                }
        },
        columnDefs: [{ "width": "22%", "targets": 1 },{ "width": "120px", "targets": 2 },{ "width": "150px", "targets": 5 }],
        columns: [
            {data: 'id',name:'id'},
            {data: 'descricao', name: 'descricao'},
            {data: 'regra.tributo.nome', name: 'regra.tributo.nome',searchable: false, orderable: false},
            {data: 'periodo_apuracao', name: 'periodo_apuracao'},
            {data: 'limite', name: 'limite', render: function ( data ) { return data.substring(8,10)+'-'+data.substring(5,7)+'-'+data.substring(0,4); } },
            {data: 'estemp.cnpj', name: 'estemp.cnpj',searchable: false, orderable: false, render: function (data) {return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14)} },
            {data: 'estemp.codigo', name: 'estemp.codigo',searchable: false, orderable: false },
            {data: 'status', name: 'status', render: function (data) {
                                                                           var retval= '';
                                                                           switch(data) {
                                                                               case 1:
                                                                                   retval = 'Pendente';
                                                                                   break;
                                                                               case 2:
                                                                                   retval = 'Em aprovação';
                                                                                   break;
                                                                               case 3:
                                                                                   retval = 'Concluida';
                                                                                   break;
                                                                               default:
                                                                                   retval = '-';
                                                                                   break;
                                                                           }
                                                                           return retval;
                                                                       }
            },
            {data: 'id', name:'edit', searchable: false, orderable: false, render: function (data) {

                        //var url = '<a href="{{ route('atividades.edit', ':id_edit') }}" class="btn btn-default btn-sm">Alterar</a>';
                        var url = '<a href="{{ route('atividades.show', ':id_show') }}" style="margin-left:10px" class="btn btn-default btn-sm">Mostrar</a>';
                        url = url.replace(':id_edit', data);
                        url = url.replace(':id_show', data);
                        return url;
            }}
        ],
        language: {
                            "searchPlaceholder": "ID, P.A. ou descrição"
                            //"url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
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

    $('#adv_search').on('click', function(e) {
                    var val_cnpj = $('#src_cnpj').val();
                    var val_codigo = $('#src_codigo').val();
                    if (val_cnpj || val_codigo) {
                        var url = "{{ route('atividades.index') }}?vcn="+val_cnpj.replace(/[^0-9]/g,'')+"&vco="+val_codigo.replace(/[^0-9]/g,'');
                    } else {
                        var url = "{{ route('atividades.index') }}";
                    }
                    $("body").css("cursor", "progress");
                    location.replace(url);
        });

});

jQuery(function($){
    $('input[name="src_cnpj"]').mask("99.999.999/9999-99");
    $('input[name="src_codigo"]').mask("9999");
});

</script>
@stop
