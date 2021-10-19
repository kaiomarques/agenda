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
<h1>Adicionar Novo Documento</h1>


@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<hr>
{!! Form::open([
    'route' => 'documentacao.adicionar',
    'enctype' => 'multipart/form-data'
]) !!}   

    <div class="form-group">
        <div style="width:50%, height:80%">
        {!! Form::label('categoria_descricao', 'Categoria do Documento:', ['class' => 'control-label']) !!}
        {!! Form::select('categoria_descricao', $categorias, array(), ['class' => 'form-control']) !!}
        </div>
	</div>
	
	<div class="form-group">
		<div style="width:50%, height:80%">
			{!! Form::label('subcategoria_descricao', 'Subcategoria do Documento:', ['class' => 'control-label']) !!}
			{!! Form::select('subcategoria_descricao', $subcategorias, array(), ['class' => 'form-control']) !!}
		</div>
	</div>
	
    <div class="form-group">
        <div style="width:100%, height:80%">
        {!! Form::label('descricao', 'Descrição:', ['class' => 'control-label']) !!}
        {!! Form::text('descricao', null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:50%,">
        {!! Form::label('observacao', 'Observação:', ['class' => 'control-label']) !!}
        {!! Form::textarea('observacao', null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group" style="width: 100%; height: 100%;">
        <div class="control-group">
            <div class="controls">
                <input type="file" name="image" class="form-control">
            </div>
        </div>
    </div>   
        {!! Form::hidden('versao', '1.0', ['class' => 'form-control']) !!}
        {!! Form::submit('Adicionar', ['class' => 'btn btn-default']) !!}
        <div style="width: 100%" class="container" align="right">
            {!! Form::label('versao', 'Versão: 1.0', ['class' => 'control-label']) !!}
        </div>
    </div>
</div>

{!! Form::close() !!}
<hr/>

@stop