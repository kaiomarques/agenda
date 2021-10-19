@extends('layouts.master')

@section('content')

<h2>{{ $user->name }}</h2>
<hr>
@if ( Auth::user()->hasRole('owner') || !$user->hasRole('owner'))
{!! Form::model($user, [
    'method' => 'PATCH',
    'route' => ['usuarios.update', $user->id]
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
    {!!  Form::select('multiple_select_tributos[]', $tributos, $user->tributos()->getRelatedIds()->toArray(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:50%">
    {!! Form::label('multiple_select_tributos[]', 'Empresas', ['class' => 'control-label'] )  !!}
    {!!  Form::select('multiple_select_empresas[]', $empresas, $user->empresas()->getRelatedIds()->toArray(), ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
        {!! Form::label('role_user', 'Tipo de Usuario:', ['class' => 'control-label']) !!}
        {!! Form::select('role_user', $roles, $user->role_id, ['class' => 'form-control']) !!}
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

{!! Form::submit('Atualiza Usuário', ['class' => 'btn btn-default']) !!}

{!! Form::close() !!}

<script type="text/javascript">
  $('select').select2();
</script>
@endif
<a href="{{ route('usuarios.index') }}" class="btn btn-default">Voltar</a>
@stop