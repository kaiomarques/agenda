@extends('layouts.master')

@section('content')

@include('partials.alerts.errors')

@if(Session::has('alert'))
    <div class="alert alert-danger">
         {!! Session::get('alert') !!}
    </div>
   
@endif

<h1>Adicionar Mensageria - Processos adminstrativos</h1>
<hr>
{!! Form::open([
    'route' => 'mensageriaprocadms.store'
]) !!}

<div class="form-group">
    <div style="width:30%">
        {!! Form::label('role_id', 'Tipo De Usuário:', ['class' => 'control-label']) !!}
        {!! Form::select('role_id', $roles, null, array('class' => 'form-control')) !!}
    </div>
</div>

<div class="form-group">
    <div style="width:30%">
    {!! Form::label('parametro_qt_dias', 'Dias:', ['class' => 'control-label']) !!}
    {!! Form::text('parametro_qt_dias', null, ['class' => 'form-control', 'id' => 'parametro_qt_dias']) !!}
    </div>
</div>

{!! Form::submit('Cadastrar', ['class' => 'btn btn-default']) !!}

{!! Form::close() !!}
<hr/>
<script>
jQuery(function($){
    $( "#role_id" ).change(function() { 
        if ($(this).val() == 0) {
            $("#parametro_qt_dias").val('');
            return false;
        }

        $.ajax(
        {
            type: "GET",
            url: '{{ url('mensageriaprocadms') }}/search_role',
            cache: false,
            async: false,
            dataType: "json",
            data:
            {
                'role_id':$(this).val()
            },
            success: function(d)
            {
                if (d.success) {
                   $("#parametro_qt_dias").val(d.data.parametro_qt_dias);
                   
                } else {
                    $("#parametro_qt_dias").val('');
                    
                }
            }
        });
    });
});
</script>
@stop



