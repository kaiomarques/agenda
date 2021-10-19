@extends('layouts.master')

@section('content')

<div class="content-top">
    <div class="row">
        <div class="col-md-12">
            <h1 class="title">Atividade - (REF #{{ $atividade->id }})</h1>
        </div>
    </div>
</div>

<div class="text-content">
    <div class="row">
        <div class="col-md-7">
        <p class="lead"><b>Emp/Est: </b>{{ $atividade->estemp->codigo }}- {{ mask($atividade->estemp->cnpj,'##.###.###/####-##') }}</p>
            <p class="lead"><b>IE: </b>{{ $atividade->estemp->insc_estadual }}</p>
            <p class="lead"><b>Descrição: </b>{{ $atividade->descricao }}</p>
            <p class="lead"><b>Status: </b>{{ status_label($atividade->status) }}
            @if ($atividade->limite < date('Y-m-d'))
            <span style="color:red"> VENCIDA!</span>
            @endif
            </p>
            @if ($atividade->status >1)
            <p class="lead">Data entrega: <b>{{ substr($atividade->data_entrega,0,10) }}</b></p>
                @if ($atividade->status >2)
                    <p class="lead"><b>Data aprovação: </b>{{ substr($atividade->data_aprovacao,0,10) }}</p>
                @endif
            @else
            <p class="lead"><b>Data limite entrega: </b>{{ substr($atividade->limite,0,10) }}</p>
            @endif

            @if ($atividade->obs != null)
            <p class="lead">Comentários: {{ $atividade->obs }}</p>
            @endif

          
            @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor') || Auth::user()->hasRole('analyst'))
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

                                {!! Form::label('obs', 'Comentários (Máx. 120 caracteres)', ['class' => 'control-label lead-bold']) !!}
                                {!! Form::textarea('obs', null, ['style'=> 'height:120px','class' => 'form-control']) !!}
                                {!! Form::hidden('atividade_id', $atividade->id, ['class' => 'form-control']) !!}
                                {!! Form::hidden('user_id', Auth::user()->id, ['class' => 'form-control']) !!}
                                <br/>
                                {!! Form::submit('Adicionar comentário', ['name'=>'com','class' => 'btn btn-default btn-black']) !!}
                                {!! Form::close() !!}

                            {!! Form::close() !!}

                            <br/>
                </div>
           
            <div class="panel panel-default">
                    <div class="panel-heading">Painel Operacional</div>
                    <div style="padding:20px" class="panel-body">
                        <div class="row">
                            @if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor'))

                                @if ($atividade->status == 2 && $atividade->entregador->id != Auth::user()->id)
                                <div class="col-md-3">
                                    <a href="{{ route('atividades.aprovar', $atividade->id) }}" class="btn-success btn btn-default">Aprovar entrega atividade</a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('atividades.reprovar', $atividade->id) }}" class="btn-danger btn btn-default">Reprovar entrega atividade</a>
                                </div>
                                @endif
                                @if ($atividade->status == 3 && !Auth::user()->hasRole('supervisor'))
                                <div class="col-md-3">
                                    <a href="{{ route('atividades.retificar', $atividade->id) }}" class="btn btn-default">Retificar entrega?</a>
                                </div>
                                @endif

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
                            <?php if($aprovacao) { ?>
                        <a href="{{ route('aprovacao') }}" class="btn btn-default">Voltar</a>
                    <?php }else{ ?>
                        <a href="{{ route('atividades.index') }}" class="btn btn-default">Voltar</a>
                    <?php } ?>
                        </div>
                    </div>
            </div>

        </div>

        <div class="col-md-5">
            <div class="detailBox">
                <div class="titleBox">
                  <label class="lead-bold">Comentários sobre a atividade</label>
                </div>
                <div class="commentBox">
                    <p class="taskDescription">Nesta caixa são armazenados os comentários efetuados pelos usuários sobre esta atividade.</p>
                </div>
                <div class="actionBox">
                    <ul class="commentList">
                        @foreach($atividade->comentarios as $el)
                        <li>
                            <div>
                              <p class="commenterName">{{$el->user->name}}</p><p class="commentText">{{ $el->obs }}</p> <span class="date sub-text"> ({{ $el->created_at }})</span>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
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