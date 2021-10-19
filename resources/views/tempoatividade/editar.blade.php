@extends('layouts.master')

@section('content')

    @include('partials.alerts.errors')

    @if(Session::has('alert'))
        <div class="alert alert-danger">
            {!! Session::get('alert') !!}
        </div>

    @endif

    <?php if (@!empty($status)) { ?>
    <div class="alert alert-success">
        <?php echo $status; ?>
    </div>
    <?php } ?>

    <?php if (@!empty($error)) { ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
    <?php } ?>

    <h1>Tempo da atividade</h1>
    <hr>
    {!! Form::open([
        'route' => 'tempoatividade.edit'
    ]) !!}

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('Empresa_id', 'Empresas', ['class' => 'control-label'] )  !!}
            {!! Form::select('Empresa_id', $empresas, $dados['Empresa_id'], ['class' => 'form-control s2' , 'disabled']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('Tributo_id', 'Responsabilidade Tributos', ['class' => 'control-label'] )  !!}
            {!! Form::select('Tributo_id', $tributos, $dados['Tributo_id'], ['class' => 'form-control' , 'disabled']) !!}

        </div>
    </div>

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('UF', 'UF', ['class' => 'control-label'] )  !!}
            {!!  Form::select('UF', $ufs, $dados['UF'], ['class' => 'form-control s2' , 'disabled']) !!}
        </div>
    </div>
	
	<div class="form-group">
        <div style="width:50%">			
			{!! Form::label('Qtd_minutos', 'Quantidade de minutos', ['class' => 'control-label']) !!}
			{!! Form::text('Qtd_minutos',$dados['Qtd_minutos'], ['class' => 'form-control','style' => 'width:80px']) !!}
        </div>
    </div>

    {!! Form::hidden('id', $dados['id'], ['class' => 'form-control']) !!}
    {!! Form::submit('Salvar', ['class' => 'btn btn-default']) !!}
    <a href="{{route('tempoatividade.index')}}" class="btn btn-default">Voltar</a>
    {!! Form::close() !!}
    <hr/>




    <script type="text/javascript">
        $(document).ready(function (){
            $('#myTableAprovacao').dataTable({
                language: {
                    "searchPlaceholder": "Pesquisar registro específico",
                    "url": "//cdn.datatables.net/plug-ins/1.10.9/i18n/Portuguese-Brasil.json"
                },
                dom: "lrtip",
                processing: true,
                stateSave: true,
                lengthMenu: [[25, 50, 75, -1], [25, 50, 75, "100"]]
            });
        });


        $('select').select2();
        if (document.getElementById('regra_geral_NAO').checked) {
            document.getElementById('hidden_div').style.display = "block";
        }
        function myfunction() {
            cnpj = $('input[name="cnpj_exibe"]').val();
            $('input[name="cnpj"]').val(cnpj);
            $('input[name="cnpj_exibe"]').val('');
        }

        function confirma() {
            if (confirm("Você tem certeza que quer deletar o registro?") == true) {
                <?php if (!empty($date['id'])) { ?>
                    window.location="{{ route('atividadesanalistafilial.excluirFilial', $date['id']) }}";
                <?php } ?>
            }
        }

        jQuery(function($){
            $('input[name="cnpj_exibe"]').mask("99.999.999/9999-99");
        });

        function showDiv(){
            document.getElementById('hidden_div').style.display = "block";
        }

        function hideDiv(){
            document.getElementById('hidden_div').style.display = "none";
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