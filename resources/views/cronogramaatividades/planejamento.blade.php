@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>   
@endif

<h1>Planejamento</h1>
<p class="lead">Planejamento de atividades.</p>
<a href="{!! route('cronogramaatividades.Loadplanejamento') !!}" class="btn btn-default">Voltar</a>
<hr>
   <table class="table table-bordered display" id="myTableAprovacao">   
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Tributo</th>
                <th>SLA</th>
                <th>Período</th>
                <th>Carga</th>
                <th>UF</th>
                <th>Qtd Estabelecimento</th>
                <th>Tempo Estabelecimento</th>
                <th>Tempo Total</th>
                <th>Qtd Dias</th>
                <th>Tempo geração</th>
                <th>Qtd Analistas</th>
                <th>Inicio</th>
                <th>Termino</th>
                <th>Analistas</th>

            </tr>
            </thead>
            <tbody>
                <?php if (!empty($dados)) { 
                    foreach ($dados as $chave => $value) {
                ?>  
                <tr>
                    <td><?php echo substr($value->cnpj, 0,8); ?></td>
                    <td><?php echo $value->Tributo_nome; ?></td>
                    <td><?php echo $value->DATA_SLA; ?></td>
                    <td><?php echo $value->periodo_apuracao; ?></td>
                    <td><?php echo $value->carga; ?></td>
                    <td><?php echo $value->uf; ?></td>
                    <td><?php echo $value->Qtde_estab; ?></td>
                    <td><?php echo $value->Tempo_estab; ?></td>
                    <td><?php echo $value->Tempo_total; ?></td>
                    <td><?php echo $value->Qtd_dias; ?></td>
                    <td><?php echo $value->Tempo_geracao; ?></td>
                    <td><?php echo $value->Qtd_analistas; ?></td>
                    <td><?php echo $value->Inicio; ?></td>
                    <td><?php echo $value->Termino; ?></td>
                    <td align="center"><p onclick="openModal('<?php echo $value->names; ?>', '<?php echo $value->id; ?>')"><button class="btn btn-default btn-sm"><i class="fa fa-search"></i></button></a></p></td>
                </tr>
                <?php } }  ?>
            </tbody>
    </table>                                            


<div class="modal fade" id="modalDetalhes" style="width: 100%;" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Alteração em lote de Analistas do Cronograma Mensal</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closemodal()" onclose="closemodal()">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" style="width: 100%; height: 100%;">
        {!! Form::open([
        'route' => 'cronograma.analistas'
        ]) !!}
        <div class="row">
            <div class="col-sm-6">
                {!! Form::label('Id_usuario_analista', 'Analista', ['class' => 'control-label'] )  !!}
                {!!  Form::select('Id_usuario_analista', $usuarios, array(), ['class' => 'form-control']) !!}
            </div>
            <br />
            <div class="col-sm-4"><input type="submit" class="form-control btn btn-success" value="Alteração em Lote"></div>
        </div>
            <input type="hidden" name="id_cronogramamensal" value="" id="id_cronogramamensal">

        {!! Form::close() !!}
        <hr />
        <h5> Analistas do Cronograma mensal</h5>
        <p class="analistasListagem"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="closemodal()">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">

jQuery(function($){
    $('#sidebarCollapse').click();
});

function openModal(data, id)
{   
    $("#id_cronogramamensal").val(id);
    $(".analistasListagem").html(data);
    $("#modalDetalhes").modal(); 
}

$(document).ready(function (){
    $('#myTableAprovacao').dataTable({
        language: {
        "searchPlaceholder": "Pesquisar registro específico",
        "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: "lfrtip",
        processing: true,
        stateSave: true
    });        
});

</script>

@stop