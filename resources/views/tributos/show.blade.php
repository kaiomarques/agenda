@extends('layouts.master')

@section('content')
<h1>TRIBUTO</h1>
<p class="lead">NOME: {{ $tributo->nome }}</p>
<p class="lead">DESCRIÇÃO: {{ $tributo->descricao }} </p>
<p class="lead">CATEGORIA: {{ $tributo->categoria->nome }} </p>
<hr>
<p>REGRAS ATIVAS:</p>
<?php foreach($regras as $regra): ?>
<a href="{{ url('regras').'/'.$regra->id }}">{{ $tributo->nome.' '.$regra->nome_especifico.' - REF. '.$regra->ref  }}</a>
<br />
<?php endforeach; ?>
<br/>

<hr>
<div class="row">
    <div class="col-md-6">
        <a href="{{ route('tributos.index') }}" class="btn btn-default">Voltar para lista de tributos</a>
    </div>
    <div class="col-md-6 text-right">
        {!! Form::open([
            'method' => 'DELETE',
            'route' => ['tributos.destroy', $tributo->id]
        ]) !!}
            {!! Form::submit('Cancelar este tributo?', ['class' => 'btn btn-default']) !!}
        {!! Form::close() !!}
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
