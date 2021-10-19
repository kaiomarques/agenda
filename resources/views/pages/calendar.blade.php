@extends('layouts.master')

@section('content')

    {!! $calendar->calendar() !!}
    {!! $calendar->script() !!}

@stop
<footer>
    @include('layouts.footer')
</footer>