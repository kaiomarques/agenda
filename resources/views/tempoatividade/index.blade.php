@extends('...layouts.master')

@section('content')

    @include('partials.alerts.errors')

    @if(Session::has('alert'))
        <div class="alert alert-danger">
            {!! Session::get('alert') !!}
        </div>
    @endif

    <h1>Tempo das Atividades</h1>
    <p class="lead">Segue dos minutos cadastrados para cada atividade.</p>
    <hr>
    <table class="table table-bordered display" id="myTableAprovacao">
        <thead>
        <tr>
            <th>Empresa</th>
            <th>Tributo</th>
            <th>UF</th>
			<th>Minutos</th>
            <th></th>
			<th></th>
        </tr>
        </thead>
        <tbody>
        @if (!empty($table))
            @foreach ($table as $key => $value)

                <tr>
                    <td><?php echo $value['razao_social']; ?></td>
                    <td><?php echo $value['Tributo']; ?></td>
                    <td><?php echo $value['uf']; ?></td>
					<td><?php echo $value['Qtd_minutos']; ?></td>
                    <td><a href="{{ route('tempoatividade.editRLT', $value['id']) }}" class="btn btn-default btn-sm"><i class="fa fa-edit"></i></a>
					<td><a href="{{ route('tempoatividade.destroy', $value['id']) }}" class="btn btn-default btn-sm" onclick="return confirm('Realmente deseja excluir o Tempo de Atividade para essa Empresa/Tributo/UF?')" ><i class="fa fa-trash"></i></a>
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