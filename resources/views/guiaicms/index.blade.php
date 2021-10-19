@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

<div class="content-top">
    <div class="row">
        <div class="col-md-4">
            <h1 class="title">Guias Cadastradas</h1>
        </div>
    </div>
</div>
    
<div class="table-default table-responsive">
   <table class="table display" id="myTableAprovacao" style="font-size: 13px">   
        <thead>

            <tr>
                <td colspan="10">
                    <input style="width: 145px; position:relative; left:10px; " placeholder="Data Inicial" type="date" id="src_inicio" name="src_inicio" value="<?= $src_inicio ?>">
                    <input style="width: 145px; position:relative; left:10px; " placeholder="Data Final" type="date" id="src_fim" name="src_fim" value="<?= $src_fim ?>" >
                    <button id="adv_search" style="position:relative; left:10px;">BUSCAR</button>
                </td>
            </tr>
            <tr>
                <th style="width: 20%">CNPJ </th>
                <th style="width: 10%">REFERENCIA </th>
                <th style="width: 15%">DATA VENCIMENTO </th>
                <th style="width: 10%">VALOR TOTAL </th>
                <th style="width: 5%">UF </th>
                <th style="width: 10%">FILIAL</th>
                <th style="width: 5%"></th>
                <th style="width: 5%"></th>
            </tr>
        </thead>
        <tbody>
         
        </tbody>
    </table>  
</div>                                          

<script type="text/javascript">
$(document).ready(function (){
    $('#myTableAprovacao').dataTable({
        language: {
        "searchPlaceholder": "Pesquisar por GUIA",
        "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        processing: true,
        serverSide: true,
        stateSave: true,
        ajax: {
                url: "{!! route('guiaicms.anyData') !!}",
                data: function (d) {
                    d.src_inicio = $('#src_inicio').val();
                    d.src_fim = $('#src_fim').val();
                }
        },

        columns: [
            {data: 'CNPJ',name:'CNPJ', render: function (data) {return data.substring(0,2)+'.'+data.substring(2,5)+'.'+data.substring(5,8)+'/'+data.substring(8,12)+'-'+data.substring(12,14)}},
            {data: 'REFERENCIA', name: 'REFERENCIA'},
            {data: 'DATA_VENCTO', name: 'DATA_VENCTO', render: function ( data ) {    if (data=='0000-00-00 00:00:00') return '-';
                                                                                        else return data.substring(8,10)+'/'+data.substring(5,7)+'/'+data.substring(0,4); } },
            {data: 'VLR_TOTAL', name: 'VLR_TOTAL', render: function ( data ) {    
                value = parseFloat(data).toFixed(2);
                var numero = value.split('.');
                numero[0] = "R$ " + numero[0].split(/(?=(?:...)*$)/).join('.');
                return numero.join(','); } },

            {data: 'UF', name: 'UF'},
            {data: 'codigo', name: 'codigo'},
            {data: 'ID', name:'edit', searchable: false, orderable: false, render: function (data) {
                        var url = '<a href="{{ route('guiaicms.editar', ':id_edit') }}" class="btn btn-default btn-sm">Alterar</a>';
                        url = url.replace(':id_edit', data);
                        return url;
            }},
            {data: 'ID', name:'delete', searchable: false, orderable: false, render: function (data) {
                        var url = '<a href="{{ route('guiaicms.excluir', ':id_excluir') }}" class="btn btn-default btn-sm">Excluir</a>';
                        url = url.replace(':id_excluir', data);
                        return url;
            }}
        ],
        lengthMenu: [[25, 50, 75, -1], [25, 50, 75, "100"]]
    });    


    $('#adv_search').on('click', function(e) {
                var src_inicio = $('#src_inicio').val();
                var src_fim = $('#src_fim').val();
                if (src_inicio || src_fim) {
                    var url = "{{ route('guiaicms.listar') }}?src_inicio="+src_inicio+"&src_fim="+src_fim;
                } else {
                    var url = "{{ route('guiaicms.listar') }}";
                }
                $("body").css("cursor", "progress");
                location.replace(url);
    });
});     

</script>

<?php
function printMaskCnpj($data) {
    return substr($data, 0,2).'.'.substr($data, 2,3).'.'.substr($data,5,3).'/'.substr($data, 8,4).'-'.substr($data, 12,2);
    return substr($data, 0,2).'.'.substr($data, 2,3).'.'.substr($data,5,8).'/'.substr($data, 8,12).'-'.substr($data, 12,14);
}
?>
@stop