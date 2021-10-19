@extends('layouts.master')

@section('content')

    @include('partials.alerts.errors')

    @if(Session::has('alert'))
        <div class="alert alert-danger">
            {!! Session::get('alert') !!}
        </div>

    @endif

    <?php if (@!empty($status)) { ?>
    <div class="alert alert-success">
        <?php echo $message; ?>
    </div>
    <?php } ?>

    <?php if (@!empty($error)) { ?>
    <div class="alert alert-danger">
        <?php echo $message; ?>
    </div>
    <?php } ?>

    <h1>Cadastro de Tempo de Atividade</h1>
    <hr>
    {!! Form::open([
        'route' => 'tempoatividade.store'
    ]) !!}

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('Empresa_id', 'Empresa', ['class' => 'control-label'] )  !!}
            {!!  Form::select('Empresa_id', $empresas, array(), ['class' => 'form-control s2']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('Tributo_id', 'Atividade Tributo', ['class' => 'control-label'] )  !!}
            {!!  Form::select('Tributo_id', $tributos, array(), ['class' => 'form-control s2_multi']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('UF', 'UF', ['class' => 'control-label'] )  !!}
            {!!  Form::select('UF', $ufs, array(), ['class' => 'form-control s2_multi']) !!}
        </div>
    </div>
	
	<div class="form-group">
        <div style="width:50%">			
			{!! Form::label('Qtd_minutos', 'Quantidade de minutos', ['class' => 'control-label']) !!}
			{!! Form::text('Qtd_minutos',null, ['class' => 'form-control','style' => 'width:80px']) !!}
        </div>
    </div>

    {!! Form::submit('Adicionar', ['class' => 'btn btn-default']) !!}
    {!! Form::close() !!}
    <hr/>


    <script type="text/javascript">
        $('select').select2();
    </script>
@stop