@extends('layouts.master')

@section('content')


<hr>
<div class="row">
    <div class="col-md-5">
        <p class="lead">CODIGO: {{ $estabelecimento->codigo }}</p>
        <p class="lead">CNPJ: {{ mask($estabelecimento->cnpj,'##.###.###/####-##') }}</p>
        <p class="lead">LOCAL: {{ $estabelecimento->municipio->nome }} ({{ $estabelecimento->municipio->uf }})</p>
        <p class="lead">ENDEREÇO: {{ $estabelecimento->endereco }} {{ $estabelecimento->num_endereco }}</p>
        <p class="lead">IE: {{ $estabelecimento->insc_estadual?$estabelecimento->insc_estadual:'não cadastrado' }}</p>
        <p class="lead">IM: {{ $estabelecimento->insc_municipal?$estabelecimento->insc_municipal:'não cadastrado' }}</p>
        <p class="lead">STATUS: {!! $estabelecimento->ativo?'<span style="color:green">ativo</span>':'<span style="color:red">inativo</span>' !!}</p>
        <p class="lead">DATA CADASTRO: {{ Date_Converter($estabelecimento->data_cadastro) }}</p>
    </div>
    <div class="col-md-3  pull-right">
        <img style="max-height: 150px" src="{{ URL::to('/') }}/assets/logo/Logo-{{ $estabelecimento->empresa_id }}.png" />
        <img style="width:250px" src="{{ URL::to('/') }}/assets/img/img_estab.png" />
    </div>
</div>
<hr/>
<div class="row">
    <div class="col-md-8">
        @if (!$atividades->isEmpty())
        <p style="" class="lead">Atividades em aberto relacionadas:</p>
        <div class="row">
            <div style="font-weight: bold" class="col-md-5">DESCRIÇÃO</div>
            <div style="font-weight: bold" class="col-md-2">PERIODO</div>
            <div style="font-weight: bold" class="col-md-2">ENTREGA</div>
            <div style="font-weight: bold" class="col-md-2"></div>
        </div>
        @else
        <b>Nenhuma atividade em aberto.</b>
        @endif
        @foreach ($atividades as $atividade)
        <div class="row">
            <div class="col-md-5">{{$atividade['descricao']}}</div>
            <div class="col-md-2">{{$atividade['periodo_apuracao']}}</div>
            <div class="col-md-2">{{Date_Converter($atividade['limite'])}}</div>
            <div class="col-md-2"><a href="{{ route('atividades.show', $atividade['id']) }}" style="margin-left:10px" class="btn btn-default btn-xs">Abrir</a></div>
        </div>
        @endforeach
    </div>
</div>
<hr>
<div class="panel panel-default">
        <div class="panel-heading">Painel Operacional para geração das atividades por estabelecimento por periodo de apuração</div>
        <div style="padding:20px" class="panel-body">
            <div style="margin-bottom: 30px" class="row">
                <div style="margin-right: 30px; margin-top: 5px" class="col-xs-2 col-sm-2">
                    {!! Form::select('combo_tributo', $tributos, ['class' => 'form-control'],['placeholder' => 'TODOS OS TRIBUTOS']) !!}
                </div>
                <div class="col-xs-2 col-sm-2">
                    <label>From:</label>
                    <input style="width: 80px; text-align: center" type="text" name="periodo_ini" value="{{ date('mY') }}" />
                </div>
                <div class="col-xs-2 col-sm-2">
                    <label>To:</label>
                    <input style="width: 80px; text-align: center" type="text" name="periodo_fin" value="{{ date('mY') }}" />
                </div>
            </div>
            <div style="margin-left: 30px; margin-bottom: 30px" class="row">
                <div class="col-xs-2 col-sm-2">
                {{ Form::button('Gera Atividades', array('id'=>'btn_geracao','class' => 'btn btn-default')) }}
                </div>
                <div class="col-md-7">
                @if (sizeof($bloqueios)>0)
                <b>ATENÇÃO! Existem regras bloqueadas:</b>
                @endif
                @foreach ($bloqueios as $bl)
                    {{$bl->tributo->nome}}
                @endforeach
                </div>
            </div>

        </div>
        <div class="panel-footer clearfix">
            <div class="col-md-6">
                <a href="{{ route('estabelecimentos.index') }}" class="btn btn-default">Voltar</a>
                <a href="{{ route('estabelecimentos.edit', $estabelecimento->id) }}" class="btn btn-default">Alterar Estabelecimento</a>
            </div>
            <div class="col-md-6 text-right">
                {!! Form::open([
                    'method' => 'DELETE',
                    'route' => ['estabelecimentos.destroy', $estabelecimento->id]
                ]) !!}
                    {!! Form::submit('Cancelar este estabelecimento?', ['class' => 'btn btn-default']) !!}
                {!! Form::close() !!}
            </div>
        </div>
</div>
</hr>

<script>
    $(function () {

        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });

        $('#btn_geracao').click(function() {
            var p_ini = $('input[name="periodo_ini"]').val();
            var p_fin = $('input[name="periodo_fin"]').val();
            var id_tributo = $('select[name="combo_tributo"]').val();
            if (id_tributo=='') id_tributo=0;
            p_ini = p_ini.replace('/','');
            p_fin = p_fin.replace('/','');

            var url = '{{ url('estabelecimento') }}/:id_tributo/:id_estab/:p_ini/:p_fin/geracao';
            url = url.replace(':id_tributo', id_tributo);
            url = url.replace(':id_estab', {{ $estabelecimento->id }});
            url = url.replace(':p_ini', p_ini);
            url = url.replace(':p_fin', p_fin);


            location.replace(url);
        });

    });

    jQuery(function($){
        $('input[name="periodo_ini"]').mask("99/9999");
        $('input[name="periodo_fin"]').mask("99/9999");
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
