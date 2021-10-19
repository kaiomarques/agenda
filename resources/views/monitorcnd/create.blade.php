@extends('layouts.master')

@section('content')
    @include('partials.alerts.errors')

    <h2>Criar CND</h2>
    <hr>
    {!! Form::open([
        'route' => 'monitorcnd.store',
        'enctype'=>'multipart/form-data'
    ]) !!}

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('select_estabelecimentos', 'Estabelecimento', ['class' => 'control-label'] )  !!}
            {!! Form::select('estemp_id', $estabelecimentos, '', ['class' => 'form-control', 'placeholder' => 'Selecione uma filial...', 'required' => 'required']) !!}
        </div>
    </div>
    <div id="cnpj" class="form-group" style="display:none">
        <div style="width:50%; margin-bottom:40px">
            <p>CNPJ: </p>
        </div>
    </div>
    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('tipocnd', 'Tipo CND', ['class' => 'control-label'] )  !!}
            {!! Form::select('tipocnd_id', $tipocnd, '', ['class' => 'form-control', 'required' => 'required', 'placeholder' => 'Selecione um tipo de CND...']) !!}
        </div>
    </div>
    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('select_classificacaocnd', 'Classificacao CND', ['class' => 'control-label'] )  !!}
            {!! Form::select('classificacaocnd_id', $classificacaocnd, '', ['class' => 'form-control', 'required' => 'required', 'placeholder' => 'Selecione uma classificação de CND...']) !!}
        </div>
    </div>
    <div class="form-group">
        <div style="width:30%">
            {!! Form::label('numerocnd', 'Número CND:', ['class' => 'control-label']) !!}
            {!! Form::text('numero_cnd', null, ['class' => 'form-control']) !!}
        </div>
    </div>
    <div class="form-group" style="width: 50%">
        {!! Form::label('validade', 'Validade:', ['class' => 'control-label']) !!}
        {!! Form::date('validade_cnd', null, ['class' => 'form-control', 'required' => 'required']) !!}
    </div>
    <div class="form-group">
        <div style="width:30%">
            {!! Form::label('arquivo', 'Arquivo:', ['class' => 'control-label']) !!}
            {!! Form::file('arquivo', null, ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="form-group">
        <div style="width:30%">
            <label for="descricao_solicitacao">Adicionar uma observação: </label>
            <textarea name="observacao" id="observacao" class="validate" style="height:80px;width:415px"></textarea>
            <input type="hidden" name="add_usuario_edit" value="usuario_edit" />
        </div>
    </div>

    {!! Form::submit('Criar CND', ['class' => 'btn btn-default']) !!}

    {!! Form::close() !!}
    <hr/>

    <script>

        $('select[name=estemp_id]').change(function() {
            filial_id = $(this).val();

            $.ajax({
                url: "monitorcnd/getCNPJ/"+filial_id,
                cache: false,
                type: "GET",
                success: function(response) {
                    $("#cnpj").show().html(response);
                },
                error: function(xhr) {

                }
            });

            $("#cnpj").show();
        });

    </script>

@stop