@extends('...layouts.master')

@section('content')

@include('partials.alerts.errors')

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
<br>
<div class="about-section">
	<div class="text-content-comments">
		<div class="span7 offset1">
			<h2>Entrega Recibo Atividade</h2>
			<h4>(REF. #{{ $atividade->id }})</h4>
			<h4>Tributo: {!! $atividade->regra->tributo->nome !!} - Descrição: {!! $atividade->regra->nome_especifico !!} {!! $atividade->descricao !!}</h4>
			<h4>Periodo Apuração: {!! $atividade->periodo_apuracao !!}</h4>
			<h4>Estabelecimento: {{ mask($atividade->estemp->cnpj,'##.###.###/####-##') }}</h4><br/>
			<small>Data limite para entrega: {{ Date_Converter($atividade->limite) }}</small><br/>
			<small>Data atual: {{ Date_Converter(date('Y-m-d H:m:s')) }}</small><br/>
			<br/>
		</div>
		<hr>
		<div>
			{!! Form::open([
				'route' => 'atividades.storeComentario'
			]) !!}

			{!! Form::label('obs', 'Comentario (max.120 caracteres)', ['class' => 'control-label']) !!}
			{!! Form::textarea('obs', null, ['style'=> 'width:550px; height:100px','class' => 'form-control']) !!}
			{!! Form::hidden('atividade_id', $atividade->id, ['class' => 'form-control']) !!}
			{!! Form::hidden('user_id', Auth::user()->id, ['class' => 'form-control']) !!}
			<br/>
			{!! Form::submit('Adicionar comentario', array('name'=>'bpo','class'=>'btn btn-default ')) !!}
			<div class="pull-left" style="padding: 0 15px 0 0">
				<a href="{{ route('entregas.index') }}" class="btn btn-default">Voltar</a>
			</div>

			{!! Form::close() !!}

			<br/>
		</div>
	</div>
</div>
<?php
function mask($val, $mask)
{
	$maskared = '';
	$k = 0;
	for($i = 0; $i<=strlen($mask)-1; $i++) {
		if($mask[$i] == '#') {
			if(isset($val[$k]))	$maskared .= $val[$k++];
		} else {
			if(isset($mask[$i])) $maskared .= $mask[$i];
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

