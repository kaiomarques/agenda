@extends('layouts.master')

@section('content')

<h1>{{ $user->name }}</h1>
E-MAIL:<p class="lead">{{ $user->email }}</p>

<hr/>
<div class="row">
    <div class="col-md-6">
        <a href="{{ route('usuarios.index') }}" class="btn btn-default">Voltar</a>
        <a href="{{ route('usuarios.edit', $user->id) }}" class="btn btn-default">Alterar Usuário</a>
        <a href="{{ route('usuarios.sendEmailReminder', $user->id) }}" class="btn btn-default">Test E-Mail Reminder</a>
    </div>
    @if ( Auth::user()->hasRole('owner'))
    <div class="col-md-6 text-right">
        {!! Form::open([
            'method' => 'DELETE',
            'route' => ['usuarios.destroy', $user->id]
        ]) !!}
            {!! Form::submit('Cancelar este usuário?', ['class' => 'btn btn-default']) !!}
        {!! Form::close() !!}
    </div>
    @endif
</div>
<script>
    $(function () {

        $('.btn').click(function() {
            $("body").css("cursor", "progress");
        });

    });
</script>
@stop
