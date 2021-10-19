@extends('layouts.master')

@section('content')
<div class="detailBox-comments">
    <div class="titleBox">
      <label>Comentários sobre a atividade</label>
    </div>
    <div class="commentBox">
        <p class="taskDescription">Nesta caixa são armazenados os comentários efetuados pelos usuários sobre esta atividade.</p>
    </div>
    <div class="actionBox">
        <ul class="commentList">
            @foreach($atividade->comentarios as $el)
            <li>
                <div>
                  <p class="commenterName">{{$el->user->name}}</p><p class="commentText">{{ $el->obs }}</p> <span class="date sub-text"> ({{ formataDataToTMZ($el->created_at) }})</span>
                </div>
            </li>
            @endforeach
        </ul>

    </div>
</div>
<div style="float:left; width:60%">
    <h2>ENTREGA</h2><small>(REF. #{{ $atividade->id }})</small>
    <p class="lead">Emp/Est: <b>{{ $atividade->estemp->codigo }}</b> - {{ mask($atividade->estemp->cnpj,'##.###.###/####-##') }}</p>
    <p class="lead">Período de Apuração: <b>{{ mask($atividade->periodo_apuracao,'##/####') }}</p>
    <p class="lead">IE: {{ $atividade->estemp->insc_estadual }}</p>
    <p class="lead">Descrição: <b>{{ $atividade->descricao }}</b></p>
    <p class="lead">Status: <b>{{ status_label($atividade->status) }}</b></p>
    @if ($atividade->status >1)
    <p class="lead">Data entrega: <b>{{ date("d/m/Y", strtotime($atividade->data_entrega)) }}</b>
        @if ($atividade->data_entrega > $atividade->limite)
        <small style="color:red">entrega em atraso (data limite prefixada {{ date("d/m/Y", strtotime($atividade->limite)) }})</small>
        @endif
    </p>
    <p class="lead">Usuário entregador:  <b>{{ $atividade->entregador->name }}</b></p>
        @if ($atividade->status >2)
            <p class="lead">Data aprovação: {{ date("d/m/Y", strtotime($atividade->data_aprovacao)) }}</p>
            <p class="lead">Usuário aprovador: <b>{{ $atividade->aprovador->name }}</b></p>
        @endif
    @else
    <p class="lead">Data limite entrega: {{ date("d/m/Y", strtotime($atividade->limite)) }}</p>
    @endif

    @if ($atividade->status_cliente == 'A' && $atividade->clienteaprovador != null)
        <p class="lead">Data aprovação do cliente: {{ date("d/m/Y", strtotime($atividade->data_aprovacao_cliente)) }}</p>
        <p class="lead">Cliente aprovador: <b>{{ $atividade->clienteaprovador->name }}</b></p>
    @elseif ($atividade->status_cliente == 'R' && $atividade->status == 2 && $atividade->clienteaprovador != null)
        <p class="lead">Data reprovação do cliente: {{ date("d/m/Y", strtotime($atividade->data_aprovacao_cliente)) }}</p>
        <p class="lead">Cliente reprovador: <b>{{ $atividade->clienteaprovador->name }}</b></p>
    @endif
    <hr>
    @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('manager') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('analyst')  || Auth::user()->hasRole('gcliente'))
        @if ($atividade->status > 1)
            @if ($atividade->arquivo_entrega == '-' )
                <p style="font-weight:bold">Atividade entregue sem documentação.</p>
            @else
                <div class="row">
                    <div class="col-xs-2 col-md-2"><a href="{{URL::to('download/'.$atividade->id)}}"><img title="Entrega {{$atividade->data_aprovacao}}" src={{asset('assets/img/zip-icon.png')}} alt="Logo"></a></div>
                    @foreach($atividade->retificacoes as $el)
                    <div class="col-xs-2 col-md-2"><a href="{{URL::to('download/'.$el->id)}}"><img title="Retificação {{$el->data_aprovacao}}" src={{asset('assets/img/ret-icon.png')}} alt="Logo"></a></div>
                    @endforeach
                </div>
            @endif

        @endif
    @endif
    <br/>
    <div>
                {!! Form::open([
                    'route' => 'atividades.storeComentario'
                ]) !!}

                    {!! Form::label('obs', 'Comentario (max.120 caracteres)', ['class' => 'control-label']) !!}
                    {!! Form::textarea('obs', null, ['style'=> 'width:550px; height:100px','class' => 'form-control']) !!}
                    {!! Form::hidden('atividade_id', $atividade->id, ['class' => 'form-control']) !!}
                    {!! Form::hidden('user_id', Auth::user()->id, ['class' => 'form-control']) !!}
                    <br/>
                    {!! Form::submit('Adiciona comentario', ['name'=>'com','class' => 'btn btn-default']) !!}
                    {!! Form::close() !!}

                {!! Form::close() !!}

                <br/>
    </div><hr/>
    <div class="panel panel-default">
            <div class="panel-heading">Painel Operacional</div>
            <div style="padding:20px" class="panel-body">
                <div class="row">
                    @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor'))
                        @if ($atividade->status == 2 && $atividade->entregador->id != Auth::user()->id)
                        <div class="col-md-4">
                            <a href="{{ route('atividades.aprovar', $atividade->id) }}" class="btn-success btn btn-default">Aprovar entrega atividade</a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('atividades.reprovar', $atividade->id) }}" class="btn-danger btn btn-default">Reprovar entrega atividade</a>
                        </div>
                        @endif
                        @if ($atividade->status == 3 && false)
                        <div class="col-md-3">
                            <a href="{{ route('atividades.retificar', $atividade->id) }}" class="btn btn-default">Retificar entrega?</a>
                        </div>
                        @endif
                    @endif


                    @if ( Auth::user()->hasRole('gcliente') && session('Empresa')->cliente_aprova == 'S' &&
                        $atividade->status == 3 && ($atividade->status_cliente == 'P' || $atividade->status_cliente == 'R') )
                        <div class="col-md-4">
                            <a href="{{ route('atividades.aprovar_cliente', $atividade->id) }}" class="btn-success btn btn-default">Aprovar entrega atividade</a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('atividades.reprovar_cliente', $atividade->id) }}" class="btn-danger btn btn-default">Reprovar entrega atividade</a>
                        </div>
                    @endif


                    @if ( Auth::user()->hasRole('owner') || Auth::user()->hasRole('admin'))
                    <div class="col-md-3">
                        @if ($atividade->status == 1)

                                {!! Form::open([
                                    'method' => 'DELETE',
                                    'route' => ['atividades.destroy', $atividade->id]
                                ]) !!}
                                    {!! Form::submit('Cancelar esta atividade?', ['class' => 'btn btn-default']) !!}
                                {!! Form::close() !!}

                        @elseif($atividade->status == 3)

                                {!! Form::open([
                                    'method' => 'GET',
                                    'route' => ['atividades.cancelar', $atividade->id]
                                ]) !!}
                                    {!! Form::submit('Cancelar entrega?', ['class' => 'btn btn-default']) !!}
                                {!! Form::close() !!}
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            <div class="panel-footer clearfix">
                <div class="pull-right">
                    <a href="{{ route('entregas.index') }}" class="btn btn-default">Voltar</a>
                </div>
            </div>
    </div>

</div>
<script>
    $(function () {

        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });

    });
</script>
@stop

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

    function status_label($status) {
        $retval = 'indefinido';
        switch ($status) {
            case 1:
                $retval = 'A entregar';
                break;
            case 2:
                $retval = 'Em aprovação';
                break;
            case 3:
                $retval = 'Aprovada';
                break;
            default:
                break;
        }
        return $retval;
    }
?>

