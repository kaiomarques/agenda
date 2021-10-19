@extends('...layouts.master')

@section('content')

<h1>Movimento - Conta Corrente</h1>
<p class="lead"> 
	<a href="{{ route('movtocontacorrentes.create') }}">Adicionar</a> - 
	<a href="{{ route('movtocontacorrentes.search') }}">Consultar</a> - 
	<a href="{{ route('movtocontacorrentes.import') }}">Importar</a>
</p>
<hr>

@stop
