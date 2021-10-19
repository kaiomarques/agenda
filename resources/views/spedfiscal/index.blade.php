@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>   
@endif

<h1>Consulta Status Sped Fiscal</h1>
<p class="lead">Segue a lista de todas as consultas cadastradas.</p>
<hr>
<div class="table-default table-responsive">
   <table class="table display" id="myTableAprovacao">   
        <thead>
            <tr>
                <th>ID</th>
                <th>Descrição</th>
                <th>Filial</th>
                <th>CNPJ</th>
                <th>Período</th>
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
                <td align="center" style="width: 10%"><a href="{{ route('download_sped', $value->id) }}"><img src="{{ URL::to('/') }}/assets/img/{{ $value->color }}-icon.png" title="Status {{ $value->color }}" /></a></td>
            </tr> 
            @endforeach
        @endif 
        </tbody>
    </table>                                            
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