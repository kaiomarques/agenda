@extends('...layouts.master')

@section('content')
<div class="content-top">
    <div class="row">
        <div class="col-md-12">
            <h1 class="title">Agrupamento de empresas</h1>
            <p class="lead">Criação de Grupos de empresas.</p>
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
            <div>
            {!! Form::open([
                'route' => 'grupoempresas.store'
            ]) !!}
                <div class="form-group">
                    <div style="width:75%">
                    {!! Form::label('Nome_grupo', 'Nome do Grupo:', ['class' => 'control-label']) !!}
                    {!! Form::text('Nome_grupo', $Nome_grupo, ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="form-group">
                    <div style="width:75%">
                    {!! Form::label('id_empresa', 'Empresas', ['class' => 'control-label'] )  !!}
                    {!!  Form::select('id_empresa', $empresas, array(), ['class' => 'form-control s2']) !!}
                    </div>
                </div>

                <div class="form-group">
                    <div style="width:75%">
                        Apresentar Logo:
                        {{ Form::label('Sim', 'SIM') }}
                        {!! Form::radio('Logo_grupo', 'S', 'S', ['id' => 'Logo_grupo_SIM']) !!}
                        {{ Form::label('Nao', 'NAO') }}
                        {!! Form::radio('Logo_grupo', 'N', '', ['id' => 'Logo_grupo_NAO']) !!}
                    </div>
                </div>
                <table style="width: 100%" class="table table-bordered display">   
                    <thead>
                        <tr>
                            <th>Razão Social</th>
                            <th>CNPJ</th>
                            <th>Exibir Logo</th>
                            <th width="10px"></th>
                        </tr>
                    </thead>
                    <tbody>

                    @if (!empty($dadosEmpresa))
                        @foreach ($dadosEmpresa as $key => $value)        
                        <tr>
                           <td><?php echo mask($value['cnpj'],'##.###.###/####-##'); ?></td>
                           <td><?php echo $value['razao_social']; ?></td>
                           <td><?php echo status($value['Logo_grupo']); ?></td>
                           <td><a href="{{ route('grupoempresas.destroy', $value['id']) }}" class="btn btn-default btn-sm"><i class="fa fa-trash"></i></a></td>
                        </tr> 
                        @endforeach
                    @else
                        <tr>
                           <td>XXXXXXXXXXXXXXXXXX</td>
                           <td>99.999.999/9999-99</td>
                           <td>S/N</td>
                           <td><a class="btn btn-default btn-sm"><i class="fa fa-trash"></i></a></td>
                        </tr>
                    @endif
                    </tbody>
                </table>    
                <br>
            {!! Form::submit('Adicionar', ['name'=>'Button','class' => 'btn btn-default']) !!}
            <?php if (@$view) { ?>
            <a href="{{ route('grupoempresas') }}" class="btn btn-default">Voltar </a>
            <?php } ?>
            {!! Form::close() !!}
            <br/>
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