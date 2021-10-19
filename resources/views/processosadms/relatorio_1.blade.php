@extends('...layouts.master')

@section('content')

@if (Session::has('message'))
   <div class="alert alert-info">{{ Session::get('message') }}</div>
@endif

<div class="content-top">
    <div class="row">
        <div class="col-md-6">
            <h1 class="title">Relatório Avançado</h1>
            <p class="lead"> 
                <a onclick="retorna()" style="font-size: 13px">Voltar</a>  
            </p>
        </div>

    </div>
</div>

        <!--span>Prezado usuário, selecione a atividade a qual se refere a entrega:</span><br/><br/-->
        <div class="table-default">
            <table class="table display" id="entregas-table">
                <thead>
                    <tr class="top-table">
                        <th>Período Apuração</th>
                        <th>Área</th>
                        <th>CNPJ</th>
                        <th>UF</th>
                        <th>Guia</th>
                        <th>Gia</th>
                        <th>Sped</th>
                        <th>Dipam</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if (!empty($relatorio)) {
                    $relatorio_fund = json_decode($relatorio,true);
                    if (is_array($relatorio_fund) && !empty($relatorio_fund)) {
                        foreach ($relatorio_fund as $key => $value) {
                ?>    
                    <tr>
                        <td><?php echo $value['periodo_apuracao']; ?></td>
                        <td><?php echo $value['codigo']; ?></td>
                        <td><?php echo $value['cnpj']; ?></td>
                        <td><?php echo $value['uf']; ?></td>
                        <td><?php echo $value['vlr_guia']; ?></td>
                        <td><?php echo $value['vlr_gia']; ?></td>
                        <td><?php echo $value['vlr_sped']; ?></td>
                        <td><?php echo $value['vlr_dipam']; ?></td>
                        <td><?php echo $value['status_id'] == 1 ? "Baixado" : "Andamento"  ?></td>
                        <td><a href="{{ route('movtocontacorrentes.edit', $value['id']) }}?view=true&periodo_inicio={{$dataExibe['periodo_inicio']}}&periodo_fim={{$dataExibe['periodo_fim']}}"><span class="glyphicon glyphicon-search"></span></a></td>
                    </tr>    
                <?php    } } } ?>
                </tbody>
            </table>
        </div>

        <form action="{{ route('consulta_conta_corrente') }}" id="formRetorno" method="get">
            <input type="hidden" name="dataExibe[periodo_fim]" value="{{$dataExibe['periodo_fim']}}">
            <input type="hidden" name="dataExibe[periodo_inicio]" value="{{$dataExibe['periodo_inicio']}}">
        </form>
<script>

function retorna(){
    $('#formRetorno').submit();
}

$(function() {
    $('#entregas-table').DataTable({
        processing: true,
        serverSide: false,
        stateSave: false,
        order: [[ 4, "asc" ]],
        language: {
                            "searchPlaceholder": "Busca rápida"
                            //"url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
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

