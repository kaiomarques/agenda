@extends('...layouts.master')

@section('content')
@include('partials.alerts.errors')

{!! Form::model($user, [
    'method' => 'POST',
    'route' => 'atualizarsenha'
]) !!}

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif
<h1>Alterar Senha Padrão</h1>
<p class="lead"> 
   <div class="form-group">
    <div style="width:50%">
    {!! Form::label('senha', 'Senha:', ['class' => 'control-label']) !!}
    {!! Form::password('password', ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('senha', 'Confirmar Senha:', ['class' => 'control-label']) !!}
    {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
    </div>
</div>
    <input type="hidden" name="id" value="<?php echo $user->id; ?>" id="id"> 
    {!! Form::submit('Alterar Senha', ['class' => 'btn btn-default']) !!}
</div>
</p>
{!! Form::close() !!}
<hr/>
<hr>
@stop
