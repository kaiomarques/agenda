@extends('...layouts.master')

@section('content')

<h1>Feriados Nacionais</h1>
<p class="lead">Segue a lista de todos os feriados nacionais do ano <?= date('Y') ?></p>
<hr>
<table class="table table-bordered" id="feriados-table">
    <thead>
    <tr>
        <th>DATA</th>
        <th>DESCRIÇÃO</th>
    </tr>
    </thead>
    <tbody>
        <?php foreach ($feriados as $key=>$val): ?>
        <tr>
            <th><?= $val.'-'.date('Y') ?></th>
            <th><?= $key ?></th>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<hr>
<h1>Feriados Estaduais</h1>
<p class="lead">Segue a lista de todos os feriados estaduais do ano <?= date('Y') ?></p>
<hr>
<table class="table table-bordered display" id="feriados-table">
    <thead>
    <tr>
        <th>DATAS</th>
        <th>UF</th>
    </tr>
    </thead>
    <tbody>
        <?php foreach ($estaduais as $val):
        $feriados_estaduais_uf = explode(';',$val->datas);
        $retval = '';
        foreach ($feriados_estaduais_uf as $el) {
            $retval .= $el.'-'.date('Y').' | ';
        }
        ?>
        <tr>
            <th><?= $retval ?></th>
            <th><?= $val->uf ?></th>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
$(function() {

    $('#feriados-table').DataTable({
        columns: [
            {data: 'data', name: 'data'},
            {data: 'descricao', name: 'descricao'}
        ],
        aLengthMenu: [
                [10, -1],
                [10, "All"]
            ],
            iDisplayLength: -1,
        order: [[1,"asc"]],
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
