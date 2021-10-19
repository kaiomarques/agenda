@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>   
@endif
<div class="row">
    <h1>Transmissão Sped Fiscal</h1>
    <p class="lead">Segue a lista de todas as consultas cadastradas.</p>
    <hr>      
</div>
    <div class="table-default table-responsive">            
    <form class="form-horizontal" role="form" action="{{ route('spedfiscal.transmitir') }}" method="POST">
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-6">
                    <p style="font-weight: bold;font-size: 15px;"><input style="width:20px;height:20px;margin: 1px" type="checkbox" id="checkall" title="All Files" onchange="selectAllFiles();"/> Selecionar Todos os Arquivos</p>                 
                </div>
                <div class="col-md-6"></div>
                <div class="col-md-6" align="right">
                    {!! csrf_field() !!}
                    <input style="margin: 1px" type="submit" class="btn btn-sucess">               
                </div>
                <div class="col-md-6"></div>
            </div>
        </div>
        <hr>
        <table class="table display" id="myTableAprovacao">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Descrição</th>
                    <th>Filial</th>
                    <th>CNPJ</th>
                    <th>Período</th>
                    <th>Transmissão</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @if (!empty($table))
                @foreach ($table as $key => $value)  
                <tr>
                    <td><?php echo $value->id; ?></td>
                    <td><?php echo $value->descricao; ?></td>
                    <td><?php echo $value->codigo; ?></td>
                    <td><?php echo mask($value->cnpj,'##.###.###/####-##'); ?></td>
                    <td><?php echo mask($value->periodo_apuracao, '##/####'); ?></td>
                    <td align="center" style="width: 10%" ><input style="width:20px;height:20px;" type="checkbox" class="checked" name="<?php echo $value->id; ?>"></td>
                    <td align="center" style="width: 10%"><a href="{{ route('download_sped', $value->id) }}"><img src="{{ URL::to('/') }}/assets/img/{{ $value->color }}-icon.png" title="Status {{ $value->color }}" /></a></td>
                </tr> 
                @endforeach
            @endif 
            </tbody>
        </table>                                            
    </form>
</div>
<script type="text/javascript">
    $(document).ready(function (){
        $('#myTableAprovacao').dataTable({
            language: {
            "searchPlaceholder": "Pesquisar registro específico",
            "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
            },
            dom: "lfrtip",
            processing: true,
            stateSave: true,
            lengthMenu: [[25, 50, 75, -1], [25, 50, 75, "100"]]
        });
    });

    setTimeout(function() {
      window.location.reload(1);
    }, 600000);

    function selectAllFiles() {
        if ($('#checkall').is(":checked") == true) {
            $('.checked').prop('checked', 'true');
        } else {
            $('.checked').prop('checked', false);
        } 
        
    }

</script>


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
?>
@stop