@extends('...layouts.master')

@section('content')

<h1>Cronograma de Atividades</h1>
<p class="lead">Segue a lista de todas as atividades em aberto. </p>
<p class="lead"><button href="#" id="excl_per" class="btn btn-default" data-toggle="modal" data-target="#myModal">Excluir por período/Empresa</button> <button href="#" id="excl_per" class="btn btn-default" data-toggle="modal" data-target="#myModalFill">Filtros</button> </p>
<hr>
<div class="table-default table-responsive">
{!! Form::open([
    'route' => 'cronogramaatividades.alterAnalista'
]) !!}

<div class="col-md-8">
    <div class="form-group">
        <div style="width:30%">
        {!! Form::label('Analista_id', 'Analista', ['class' => 'control-label'] )  !!}
        {!!  Form::select('Analista_id', $analistas, array(), ['class' => 'form-control s2']) !!}
        </div>
    </div>
    {!! Form::submit('Alterar', ['class' => 'btn btn-success']) !!}
</div>

<br /> 
<br /> 
<table class="table display" id="atividades-table">
    <thead>
    <tr>
        <th>Data Atividade</th>
        <th>Filial</th>
        <th>Atividade</th>
        <th>UF</th>
        <th>Tipo</th>
        <th>Analista</th>
        <th>Município</th>
        <th>CNPJ</th>
        <th>IE</th>
        <th></th>
        <th>Alterar Analista</th>

    </tr>
    </thead>
    <tbody>
        <?php if (!empty($tabela)) { 
            foreach ($tabela as $chave => $value) {
        ?>  
        <tr>
            <td><?php echo $value['data_atividade']; ?></td>
            <td><?php echo $value['codigo']; ?></td>
            <td><?php echo $value['descricao']; ?></td>
            <td><?php echo $value['uf']; ?></td>
            <td><?php echo tipo($value['Tipo']); ?></td>
            <td><?php echo $value['name']; ?></td>
            <td><?php echo $value['nome']; ?></td>
            <td><?php echo mask($value['cnpj'], "##.###.###/####-##"); ?></td>
            <td><?php echo $value['insc_estadual']; ?></td>
            <td><a class="btn btn-default btn-sm" onclick="mymodalAlt(<?php echo $value['id']; ?>, <?php echo $value['Id_usuario_analista']; ?> )" data-toggle="modal" data-target="#myModalAlt"><i class="fa fa-edit"></i></a><a class="btn btn-default btn-sm" onclick="confirmaDelete(<?php echo $value['id']; ?>)"><i class="fa fa-trash"></i></a></td>
            <td align="center"><input type="checkbox" name="alterar[]" value="<?php echo $value['id']; ?>"></td>
        </tr>
        <?php } }  ?>
    </tbody>
</table>
</div>

{!! Form::close() !!}


{!! Form::open([
    'route' => 'cronogramaatividades.excluir',
    'name' => 'formUnic'
]) !!}
{!!  Form::hidden('idAtividade', NULL , NULL, ['class' => 'form-control s2']) !!} 
{!! Form::close() !!}

<script>
    function confirmaDelete(id)
    {
        if (confirm('Você tem certeza que quer deletar esse registro?') == true) {
            $('input[name=idAtividade]').val(id);
            $('form[name=formUnic]').submit();
        }
    }

    $(document).ready(function (){
    $('#atividades-table').dataTable({
        language: {
        //"searchPlaceholder": "ID, P.A. ou descrição",
        "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: '<"centerBtn">frtip'
    });    
        
});

jQuery(function($){
    $('input[name="periodo_apuracao"]').mask("99/9999");
});

function mymodalAlt(id, id_analista){
    
    if (id > 0) {
        document.getElementById('periodo_alt').style.display = "none";
        document.getElementById('empresa_alt').style.display = "none";
        $('input[name=id_atividade]').val(id);
        $('#id_analista').val(id_analista);    
    } else {
        document.getElementById('periodo_alt').style.display = "block";
        document.getElementById('empresa_alt').style.display = "block";
        $('input[name=id_atividade]').val(0);
    }
}

</script>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Exclusão por Período/Empresa</h4>
      </div>
      <div class="modal-body">
        {!! Form::open([
            'route' => 'cronogramaatividades.excluir'
        ]) !!}
        <div class="form-group">
            <div style="width:50%">
                {!! Form::label('Período', 'Período apuração', ['class' => 'control-label'] )  !!}
                {!!  Form::text('periodo_apuracao', NULL , NULL, ['class' => 'form-control s2']) !!}            
            </div>
        </div>


        <div class="form-group">
            <div style="width:90%">
        {!! Form::label('Emp_id', 'Empresa', ['class' => 'control-label'] )  !!}
        {!!  Form::select('Emp_id', $empresas, array(), ['class' => 'form-control s2']) !!}
            </div>
        </div>

        {!! Form::submit('Remover', ['class' => 'btn btn-danger']) !!}
        {!! Form::close() !!}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Voltar</button>
      </div>
    </div>

  </div>
</div>



<!-- Modal -->
<div id="myModalAlt" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Alteração por Período/Empresa</h4>
      </div>
      <div class="modal-body">
        {!! Form::open([
            'route' => 'cronogramaatividades.alterar'
        ]) !!}
        <div class="form-group" id="periodo_alt" style="display:none;">
            <div style="width:50%">
                {!! Form::label('Período', 'Período apuração', ['class' => 'control-label'] )  !!}
                {!!  Form::text('periodo_apuracao', NULL , NULL, ['class' => 'form-control s2']) !!}            
            </div>
        </div>
        <div class="form-group" id="empresa_alt" style="display:none;"> 
            <div style="width:90%">
        {!! Form::label('Emp_id', 'Empresa', ['class' => 'control-label'] )  !!}
        {!!  Form::select('Emp_id', $empresas, array(), ['class' => 'form-control s2']) !!}
            </div>
        </div>

        <div class="form-group">
            <div style="width:50%">
        {!! Form::label('inicio_aviso', 'Novo Inicio Aviso', ['class' => 'control-label'] )  !!}<br>
        {!!  Form::date('inicio_aviso', NULL ,NULL, ['class' => 'form-control s2', 'id' => 'inicioAviso']) !!}
            </div>
        </div>

        <div class="form-group">
            <div style="width:50%">
        {!! Form::label('limite', 'Novo Término Aviso', ['class' => 'control-label'] )  !!}<br>
        {!!  Form::date('limite', NULL, NULL, ['class' => 'form-control s2', 'id' => 'limiteAviso']) !!}
            </div>
        </div>

        <div class="form-group">
            <div style="width:50%">
        {!! Form::label('data_atividade', 'Nova Data de atividade', ['class' => 'control-label'] )  !!}<br>
        {!!  Form::date('data_atividade', NULL, NULL, ['class' => 'form-control s2']) !!}
            </div>
        </div>

        <div class="form-group">
            <div style="width:50%">
        {!! Form::label('Id_usuario_analista', 'Analista', ['class' => 'control-label'] )  !!}
        {!!  Form::select('Id_usuario_analista', $analistas, array(), ['class' => 'form-control s2', 'id' => 'id_analista']) !!}
            </div>
        </div>

        {!!  Form::hidden('id_atividade', NULL, NULL, ['class' => 'form-control s2']) !!}
        {!! Form::submit('Alterar', ['class' => 'btn btn-success']) !!}
        {!! Form::close() !!}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Voltar</button>
      </div>
    </div>

  </div>
</div>

<div id="myModalFill" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Alteração por Período/Empresa</h4>
      </div>
      <div class="modal-body">
        {!! Form::open([
            'route' => 'cronogramaatividades.index',
            'method' => 'GET'
        ]) !!}
        <div class="form-group" id="periodo_alt">
            <div style="width:50%">
                {!! Form::label('Período', 'Período apuração', ['class' => 'control-label'] )  !!}
                {!!  Form::text('periodo_apuracao', NULL , NULL, ['class' => 'form-control s2']) !!}            
            </div>
        </div>
        <div class="form-group" id="empresa_alt"> 
            <div style="width:90%">
                <input type="checkbox" name="permite_empresa" value="true">            
        {!! Form::label('Emp_id', 'Empresa', ['class' => 'control-label'] )  !!}
        {!!  Form::select('Emp_id', $empresas, array(), ['class' => 'form-control s2']) !!}
            </div>
        </div>
        <div class="form-group" id="empresa_alt"> 
            <div style="width:90%">
                <input type="checkbox" name="permite_filial" value="true">
        {!! Form::label('Estabelecimento_id', 'Filial', ['class' => 'control-label'] )  !!}
        {!!  Form::select('Estabelecimento_id', $estabelecimentos, array(), ['class' => 'form-control s2']) !!}
            </div>
        </div>

        <div class="form-group">
            <div style="width:90%">
                <input type="checkbox" name="permite_analista" value="true">
                {!! Form::label('Analista_id', 'Analista', ['class' => 'control-label'] )  !!}
                {!!  Form::select('Analista_id', $analistas, array(), ['class' => 'form-control s2']) !!}
            </div>
        </div>

        <div class="form-group">
            <div style="width:90%">
                <input type="checkbox" name="permite_municipio" value="true">
                {!! Form::label('municipio_cod', 'Municipio', ['class' => 'control-label'] )  !!}
                {!!  Form::select('municipio_cod', $municipios, array(), ['class' => 'form-control s2']) !!}
            </div>
        </div>

        <div class="form-group">
            <div style="width:50%">
                {!! Form::label('ie_busca', 'Inscrição Estadual', ['class' => 'control-label'] )  !!}
                {!!  Form::text('ie_busca', NULL , NULL, ['class' => 'form-control s2']) !!}            
            </div>
        </div>
        <div class="form-group">
            <div style="width:50%">
                {!! Form::label('data_inicio', 'Data de busca (Coluna Início)', ['class' => 'control-label'] )  !!}
                {!!  Form::date('data_inicio', NULL , NULL, ['class' => 'form-control s2']) !!}            
            </div>
        </div>
        <div class="form-group">
            <div style="width:50%">
                {!! Form::label('data_termino', 'Data de Busca (Coluna Término)', ['class' => 'control-label'] )  !!}
                {!!  Form::date('data_termino', NULL , NULL, ['class' => 'form-control s2']) !!}            
            </div>
        </div>

        {!! Form::submit('Filtrar', ['class' => 'btn btn-success']) !!}
        {!! Form::close() !!}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Voltar</button>
      </div>
    </div>

  </div>
</div>


<?php
function mask($val, $mask)
    {
         $maskared = '';
         $k = 0;
         for($i = 0; $i<=strlen($mask)-1; $i++)
         {
         if($mask[$i] == '#')
         {
         if(isset($val[$k]))
         $maskared .= $val[$k++];
         }
         else
         {
         if(isset($mask[$i]))
         $maskared .= $mask[$i];
         }
         }
         return $maskared;
    }
function tipo($tipo)
    {
        switch ($tipo) {
            case 'E':
                return 'Estadual';
                break;
            
            case 'F':
                return 'Federal';
                break;

            case 'M';
                return 'Municipal';
                break;
        }
    }
?>
@stop