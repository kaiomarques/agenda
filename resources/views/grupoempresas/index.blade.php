@extends('...layouts.master')
@section('content')

<div class="content-top">
    <div class="row">
        <div class="col-md-12">
            <h1 class="title">Grupo de Empresas</h1>
            <p class="lead">Listagem de todos os grupos.</p>
        </div>
    </div>
</div>
<div class="text-content">
<p class="lead">Agrupamento de empresas.</p>
    <div class="row">
        <div class="col-md-7">
        <?php if (!empty($status)) { ?>
            <div class="alert alert-success">
                <?php echo $status; ?>
            </div>  
        <?php } ?>
        <?php if (!empty($error)) { ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>  
        <?php } ?>
          <div class="table-default table-responsive">
            <table class="table display" id="TableConsultaGrupo">
                <thead>
                <tr>
                    <td align="center"><b>Nome do Grupo</b></td>
                    <td align="center"><b>Editar/Excluir</b></td>
                </tr>
                </thead>
                <tbody>
                    @if (!empty($Relatorio))
                        @foreach ($Relatorio as $key => $value)        
                        <tr>
                           <td align="center"><?php echo $value['Nome_grupo']; ?></td>
                           <td  align="center">
                            <a href="{{ route('grupoempresas.anyData', $value['Nome_grupo']) }}?view=true" class="btn btn-default btn-sm">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="{{ route('grupoempresas.destroyRLT', $value['Nome_grupo']) }}" style="margin-left: 10px" class="btn btn-default btn-sm">
                                <i class="fa fa-trash"></i>
                            </a>
                           </td>
                        </tr> 
                        @endforeach
                    @endif
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
function confirma(id) {
    if (confirm("Você tem certeza que quer deletar o registro?") == true) {
        return true;       
    }
    return false;
}

jQuery(function($){
    $('input[name="cnpj"]').mask("99.999.999/9999-99");
});

$(document).ready( function () {
    $('#TableConsultaGrupo').DataTable({
        language: {                        
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        "bSort": false,
        paging: true
    });
});
</script>
@stop

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

    function status($val)
    {
         if ($val == 'S') {
             $maskared = 'SIM';
         } 

         if ($val == 'N') {
             $maskared = 'NÃO';
         }
         return $maskared;
    }
?>