@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<h1>Adicionar nova Usuario</h1>
<hr>
{!! Form::open([
    'route' => 'usuarios.store'
]) !!}

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('name', 'Nome:', ['class' => 'control-label']) !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('email', 'E-Mail:', ['class' => 'control-label']) !!}
    {!! Form::text('email', null, ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('multiple_select_tributos[]', 'Responsabilidade Tributos', ['class' => 'control-label'] )  !!}
    {!!  Form::select('multiple_select_tributos[]', $tributos, array(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('multiple_select_tributos[]', 'Empresas', ['class' => 'control-label'] )  !!}
    {!!  Form::select('multiple_select_empresas[]', $empresas, array(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        {!! Form::label('role_user', 'Tipo de Usuario:', ['class' => 'control-label']) !!}
        {!! Form::select('role_user', $roles, array(), ['class' => 'form-control']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        Resetar Senha:
        {{ Form::label('Sim', 'SIM') }}
        {!! Form::radio('reset_sim', true, '', ['id' => 'Resetar_Senha_SIM']) !!}
        {{ Form::label('Nao', 'NAO') }}
        {!! Form::radio('reset_sim', false, true, ['id' => 'Resetar_Senha_NAO']) !!}
    </div>
</div>


{!! Form::submit('Cadastrar', ['class' => 'btn btn-default']) !!}

{!! Form::close() !!}
<hr/>


<script type="text/javascript">
  $('select').select2();
</script>
@stop