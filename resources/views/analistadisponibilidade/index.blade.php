@extends('...layouts.master')

@section('content')

    @include('partials.alerts.errors')

    @if(Session::has('alert'))
        <div class="alert alert-danger">
            {!! Session::get('alert') !!}
        </div>
    @endif

    <h1>Disponibilidade dos analistas</h1>
    <p class="lead">Segue dos minutos cadastrados para cada atividade.</p>
    <hr>
    <table class="table table-bordered display" id="myTableAprovacao">
        <thead>
        <tr>
            <th>Empresa</th>
            <th>Analista</th>
            <th>Período apuração</th>
            <th>Data inicial</th>
            <th>Data final</th>
            <th>Minutos por dia</th>
            <th></th>
			<th></th>
        </tr>
        </thead>
        <tbody>
        @if (!empty($table))
            @foreach ($table as $key => $value)

                <tr>
                    <td><?php echo $value['razao_social']; ?></td>
                    <td><?php echo $value['usuario_analista']; ?></td>
                    <td><?php echo $value['periodo_apuracao']; ?></td>
                    <td>
                        <?php 
                            $data_ini_disp = DateTime::createFromFormat('Y-m-d', $value['data_ini_disp']);
                            echo date_format($data_ini_disp, "d/m/Y"); 
                        ?>
                    </td>
                    <td>
                        <?php 
                            $data_fim_disp = DateTime::createFromFormat('Y-m-d', $value['data_fim_disp']);
                            echo date_format($data_fim_disp, "d/m/Y"); 
                        ?>
                    </td>
                    <td><?php echo $value['qtd_min_disp_dia']; ?></td>
                    <td><a href="{{ route('analistadisponibilidade.editRLT', $value['id']) }}" class="btn btn-default btn-sm"><i class="fa fa-edit"></i></a>
					<td><a href="{{ route('analistadisponibilidade.destroy', $value['id']) }}" class="btn btn-default btn-sm" onclick="return confirm('Realmente deseja excluir o Limite de Disponibilidade para essa Analista nessa Empresa/Período?')" ><i class="fa fa-trash"></i></a>
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