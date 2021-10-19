@extends('layouts.master')

@section('content')


<hr>
<div class="row">
    <div class="col-md-7">
        <p class="lead"><b>{{ $empresa->razao_social }}</b></p>
        <p class="lead">CODIGO: {{ $empresa->codigo }}</p>
        <p class="lead">CNPJ: {{ mask($empresa->cnpj,'##.###.###/####-##') }}</p>
        <p class="lead">LOCAL: {{ $empresa->municipio->nome }} ({{ $empresa->municipio->uf }}) | {{ $empresa->endereco }} {{ $empresa->num_endereco }}</p>
        <p class="lead">IE: {{ $empresa->insc_estadual?$empresa->insc_estadual:'não cadastrado' }}</p>
        <p class="lead">IM: {{ $empresa->insc_municipal?$empresa->insc_municipal:'não cadastrado' }}</p>
    </div>
    <div class="col-md-3  pull-right">
        <img style="max-height: 150px" src="{{ URL::to('/') }}/assets/logo/Logo-{{ $empresa->id }}.png" />
        <img style="width:250px" src="{{ URL::to('/') }}/assets/img/img_empresa.png" />
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-8">
        <p style="" class="lead">Atividades em aberto relacionadas.</p>
        @if (sizeof($atividades)>0)
        <div class="row">
            <div style="font-weight: bold" class="col-md-6">DESCRIÇÃO</div>
            <div style="font-weight: bold" class="col-md-2">PERIODO</div>
            <div style="font-weight: bold" class="col-md-2">ENTREGA</div>
            <div style="font-weight: bold" class="col-md-2"></div>
        </div>
        @endif
        @if (sizeof($atividades)==0)
        <div class="row">
            <div class="col-md-6">Nenhuma atividade relacionada em aberto.</div>
        </div>
        @endif
        @foreach ($atividades as $atividade)
        <div class="row">
            <div class="col-md-6">{{$atividade['descricao']}}</div>
            <div class="col-md-2">{{$atividade['periodo_apuracao']}}</div>
            <div class="col-md-2">{{Date_Converter($atividade['limite'])}}</div>
            <div class="col-md-2"><a href="{{ route('atividades.show', $atividade['id']) }}" style="margin-left:10px" class="btn btn-default btn-xs">Abrir</a></div>
        </div>
        @endforeach
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-10">
        <p style="" class="lead">Mapeamento tributos para esta empresa:</p>
        <div class="row">
        @foreach ($empresa->tributos as $tributo)
            <div class="col-md-3">
             {{ $tributo->nome }}
            </div>
        @endforeach
        </div>
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-12">
        <p style="" class="lead">Mapeamento usuarios para esta empresa:</p>
        <div class="row">
        @foreach ($empresa->users as $user)
            <div class="col-md-4">
             {{ $user->name }}
             @foreach ($user->roles as $role)
                ({{ $role->display_name }})
             @endforeach
            </div>
        @endforeach
        </div>
    </div>
</div>
<hr/>
<div class="panel panel-default">
        <div class="panel-heading">Painel Operacional para geração das atividades</div>
        <div style="padding:20px" class="panel-body">
            <div style="margin-bottom: 30px" class="row">
                <div class="col-xs-2 col-sm-2">
                    <label>Periodo Apuração: </label>
                    <input style="width: 80px; text-align: center" type="text" name="periodo" value="{{ date('mY') }}" />
                </div>
            </div>
            <div style="margin-left: 30px;" class="row">
                {{ Form::button('Gera todas as Atividades', array('id'=>'btn_geracao','class' => 'btn btn-default')) }}
            </div>
        </div>
        <div class="panel-footer clearfix">
            <div class="col-md-6">
                <a href="{{ route('empresas.index') }}" class="btn btn-default">Voltar</a>
                <a href="{{ route('empresas.edit', $empresa->id) }}" class="btn btn-default">Alterar Empresa</a>
            </div>
            <div class="col-md-6 text-right">
                {!! Form::open([
                    'method' => 'DELETE',
                    'route' => ['empresas.destroy', $empresa->id]
                ]) !!}
                    {!! Form::submit('Cancelar esta empresa?', ['class' => 'btn btn-default']) !!}
                {!! Form::close() !!}
            </div>
        </div>
</div>

<script>
    $(function () {

        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });

        $('#btn_geracao').click(function() {

            var periodo = $('input[name="periodo"]').val();
            periodo = periodo.replace('/','');

            var url = '{{ url('empresa') }}/:periodo/:id_emp/geracao';
            url = url.replace(':periodo', periodo);
            url = url.replace(':id_emp', {{ $empresa->id }});

            location.replace(url);
        });

        jQuery(function($){
            $('input[name="periodo"]').mask("99/9999");
        });
    });
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
function Date_Converter($date) {

    # Separate Y-m-d from Date
    $date = explode("-", substr($date,0,10));
    # Rearrange Date into m/d/Y
    $date = $date[2] . "/" . $date[1] . "/" . $date[0];

    # Return
    return $date;

}
?>
@stop






