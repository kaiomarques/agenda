@extends('...layouts.master')
@section('content')

<?php if (!empty($mensagem)) { ?>
    <div class="alert alert-success">
        <?php echo $mensagem; ?>
    </div>
<?php } ?>
<br><br>

@stop
<footer>
    @include('layouts.footer')
</footer>