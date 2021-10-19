@extends('...layouts.master')

@section('content')

    @include('partials.alerts.errors')

    @if(Session::has('alert'))
        <div class="alert alert-danger">
            {!! Session::get('alert') !!}
        </div>
    @endif

    <h1>Previsões de Carga</h1>
    <p class="lead">Segue dos minutos cadastrados para cada atividade.</p>
    <hr>
    <table class="table table-bordered display" id="myTableAprovacao">
        <thead>
        <tr>
            <th>Tributo</th>
            <th>Periodo de apuração</th>
			<th>Previsão de carga</th>
            <th></th>
			<th></th>
        </tr>
        </thead>
        <tbody>
        @if (!empty($table))
            @foreach ($table as $key => $value)

                <tr>
                    <td><?php echo $value['Tributo_Nome']; ?></td>
                    <td><?php echo $value['periodo_apuracao']; ?></td>
                    <td>
                        <?php 
                            $Data_prev_carga = DateTime::createFromFormat('Y-m-d', $value['Data_prev_carga']);
                            echo date_format($Data_prev_carga, "d/m/Y"); 
                        ?>    
                    </td>
                    <td><a href="{{ route('previsaocarga.editRLT', $value['id']) }}" class="btn btn-default btn-sm"><i class="fa fa-edit"></i></a>
					<td><a href="{{ route('previsaocarga.destroy', $value['id']) }}" class="btn btn-default btn-sm" onclick="return confirm('Realmente deseja excluir o Previsão de Carga para esse Tributo e Período de Apuração?')" ><i class="fa fa-trash"></i></a>
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