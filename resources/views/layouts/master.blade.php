<!doctype html>
<html lang="pt-br">
@include('layouts.head')
<body>

@include('layouts.nav')
	<script>
		$(document).ready(function (){
			setTimeout(() => {
				$('.alertMessage').slideUp();
			}, 10000);
		});
	</script>

    <div id="content">
        @if (session('status'))
            <div id="alert-status" class="alert alert-success alertMessage">{{ session('status') }}</div>
		@endif
		
		@if (session('error'))
			<div id="alert-danger" class="alert alert-danger alertMessage">{{ session('error') }}</div>
		@endif
		
		@if (session('warning'))
			<div id="alert-warning" class="alert alert-warning alertMessage">{{ session('warning') }}</div>
		@endif
		
		@if (session('info'))
			<div id="alert-info" class="alert alert-info alertMessage">{{ session('info') }}</div>
		@endif
		
		@if (session('success'))
			<div id="alert-success" class="alert alert-success alertMessage">{{ session('success') }}</div>
		@endif
		
		@if (session('danger'))
		<div id="alert-danger" class="alert alert-danger alertMessage">{{ session('danger') }}</div>
		@endif
		
        @yield('content')
    </div>
</body>
</html>