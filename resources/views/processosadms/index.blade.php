@extends('...layouts.master')

@section('content')

<h1>Processos administrativos</h1>
<p class="lead"> 
	<a href="{{ route('processosadms.create') }}">Adicionar</a> - 
	<a href="{{ route('processosadms.search') }}">Consultar</a> - 
	<a href="{{ route('processosadms.import') }}">Importar</a>
</p>
<hr>

@stop
