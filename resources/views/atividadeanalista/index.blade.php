@extends('...layouts.master')

@section('content')

    @include('partials.alerts.errors')

    @if(Session::has('alert'))
        <div class="alert alert-danger">
            {!! Session::get('alert') !!}
        </div>
    @endif

    <h1>Atividade do Analista</h1>
    <p class="lead">Segue a lista de todas as atividades cadastradas.</p>
    <hr>
    <table class="table table-bordered display" id="myTableAprovacao">
        <thead>
        <tr>
            <th>Empresa</th>
            <th>Analista</th>
            <th>Tributo</th>
            <th>UF</th>
            <th>Estabelecimento</th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @if (!empty($table))
            @foreach ($table as $key => $value)

                <tr>
                    <td><?php echo $value['razao_social']; ?></td>
                    <td><?php echo $value['name']; ?></td>
                    <td><?php echo $value['nome']; ?></td>
                    <td><?php echo $value['uf']; ?></td>
                    <td><?php echo $value['estabelecimento']; ?></td>
                    <td><a href="{{ route('atividadesanalista.editRLT', $value['id']) }}" class="btn btn-default btn-sm"><i class="fa fa-edit"></i></a>
                    <td><a href="{{ route('atividadesanalista.destroy', $value['id']) }}" class="btn btn-default btn-sm" onclick="return confirm('Realmente deseja excluir a atividade do analista?')" ><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
            @endforeach
        @endif
        </tbody>
    </table>

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

    </script>
@stop