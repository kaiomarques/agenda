@extends('layouts.master')
<style type="text/css">
	.icon-atrasado:before{
		font-family:'FontAwesome';
		content:"\f111"
	}
	.fc-title{
		color: #2b2b2b;
		font-weight: 500;
	}
	.fc-sat{
		background-color: #FFFFFF !important;
	}
	.fc-event {
		position: relative;
		display: block;
		font-size: .85em;
		line-height: 1.3;
		border-radius: 3px;
		border: 1px solid transparent!important;
		background-color: transparent!important;
		font-weight: 400;
	}
</style>
<script type="text/javascript">
	window.onload = initPage;
	function initPage(){
		var all = document.getElementsByClassName("fc-time");
		for (var i=0; i<all.length; i++) {
			all[i].innerHTML = '<i class="fa fa-circle" aria-hidden="true"></i>';
		}
		more();
	}
	function more(){
		$('.fc-more').click(function(){
			initPage();
		});
	}
</script>
@section('content')
    {!! $calendar->calendar() !!}
    {!! $calendar->script() !!}
@stop
<footer>
    @include('layouts.footer')
</footer>