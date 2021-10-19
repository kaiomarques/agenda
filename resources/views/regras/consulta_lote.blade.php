@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>   
@endif

<h1>Regra para envio e lote</h1>
<p class="lead">Segue a lista de todas as regras cadastradas.</p>
<hr>
   <table class="table table-bordered display" id="myTableAprovacao">
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Tributo</th>
                <th>Regra Geral</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @if (!empty($array))
            @foreach ($array as $key => $value)        
            <?php
            if ($value['regra_geral'] == 'S') {
                $value['regra_geral'] = 'SIM';
            }
            if ($value['regra_geral'] == 'N') {
                $value['regra_geral'] = 'NÃO';
            }
            ?>

            <tr>
               <td><?php echo $value['razao_social']; ?></td>
               <td><?php echo $value['nome']; ?></td>
               <td><?php echo $value['regra_geral']; ?></td>
               <td><a href="{{ route('regraslotes.edit_lote', $value['id']) }}" style="margin-left:10px" class="btn btn-default btn-sm"><i class="fa fa-edit"></i></a>
               <a href="{{ route('regraslotes.excluir', $value['id']) }}" id="excluiReg" onclick="return confirm('Tem certeza que deseja excluir Regra?')" style="margin-left:10px" class="btn btn-default btn-sm"><i class="fa fa-trash"></i></a></td>
            </tr> 
            @endforeach
        @endif
        </tbody>
    </table>                                            

<script type="text/javascript">
    $(document).ready(function (){

    $('#myTableAprovacao').dataTable({
        language: {
        "searchPlaceholder": "Empresa, tributo e regra geral. ",
                "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
        },
        dom: 'l<"centerBtn"B>frtip',
        buttons: [
            'copy', 'excel', 'pdf'
        ],
        processing: true,
        stateSave: true,
        order: [[ 0, 'asc' ], [ 1, 'asc' ]],
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });        
});

</script>
@stop