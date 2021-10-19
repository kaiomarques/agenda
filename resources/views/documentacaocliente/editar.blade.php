@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')
<style type="text/css">
    #container {
        width: 100%;
        text-align: center;
    }

    .box {
        float: left;
        width: 100px; height: 70px;
        margin: 10px 20px;
    }
</style>
<h1>Editar Documento</h1>


@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
@endif

<hr>
{!! Form::open([
    'route' => ['documentacao.editar', $request->id],
    'enctype' => 'multipart/form-data'
]) !!}

<div class="col-md-8">
    <div class="form-group">
		<div class="form-group">
			<div style="width:50%, height:80%">
				{!! Form::label('categoria_descricao', 'Categoria do Documento:', ['class' => 'control-label']) !!}
				{!! Form::select('categoria_descricao', $categorias, $request->categoria_id, ['class' => 'form-control']) !!}
			</div>
		</div>
		
		<div class="form-group">
			<div style="width:50%, height:80%">
				{!! Form::label('subcategoria_descricao', 'Subcategoria do Documento:', ['class' => 'control-label']) !!}
				{!! Form::select('subcategoria_descricao', $subcategorias, $request->subcategoria_id, ['class' => 'form-control']) !!}
			</div>
		</div>
		
        <div style="width:100%, height:80%">
        {!! Form::label('descricao', 'Descrição:', ['class' => 'control-label']) !!}
        {!! Form::text('descricao', $request->descricao, ['class' => 'form-control']) !!}
        </div>
    </div>
    <div class="form-group">
        <div style="width:50%,">
        {!! Form::label('observacao', 'Observação:', ['class' => 'control-label']) !!}
        {!! Form::textarea('observacao', $request->observacao, ['class' => 'form-control']) !!}
        </div>
    </div>
    
    @if (!empty($request->arquivo))
        {!! Form::label('versao', 'Arquivo Atual: '.$request->arquivo.'', ['class' => 'control-label']) !!} <br/>
    @else
        {!! Form::label('versao', 'Arquivo Atual: -', ['class' => 'control-label']) !!} <br/>
    @endif
    <div class="form-group" style="width: 100%; height: 100%;">
        <div class="control-group">
            <div class="controls">
                <input type="file" name="image" class="form-control">
            </div>
        </div>
    </div>  
    <a href="{{ route('documentacao.consultar') }}" class="btn btn-default">Voltar</a>
    {!! Form::hidden('versao', $request->versao, ['class' => 'form-control']) !!}
    {!! Form::submit('Atualizar', ['class' => 'btn btn-default']) !!}
    <div style="width: 100%" class="container" align="right">
        {!! Form::label('versao', 'Versão: '.$request->versao.'.0', ['class' => 'control-label']) !!}
    </div>
</div>

{!! Form::close() !!}
<hr/>

@stop