@extends('layouts.master')

@section('content')
    @include('partials.alerts.errors')

        <h2>Editar</h2>
    <hr>

    {!! Form::open([
        'route' => ['monitorcnd.edit', $documentocnd->id],
        'enctype'=>'multipart/form-data'
    ]) !!}

    <?php //var_dump($estabelecimentos);die; ?>

    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('select_estabelecimentos', 'Estabelecimento', ['class' => 'control-label'] )  !!}
            {!! Form::select('estemp_id', $estabelecimentos, $documentocnd->Estemp_id, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
        </div>
    </div>
    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('tipocnd', 'Tipo CND', ['class' => 'control-label'] )  !!}
            {!! Form::select('tipocnd_id', $tipocnd, $documentocnd->tipocnd_id, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
        </div>
    </div>
    <div class="form-group">
        <div style="width:50%">
            {!! Form::label('select_classificacaocnd', 'Cclassificacao CND', ['class' => 'control-label'] )  !!}
            {!! Form::select('classificacaocnd_id', $classificacaocnd, $documentocnd->classificacaocnd_id, ['class' => 'form-control']) !!}
        </div>
    </div>
    <div class="form-group">
        <div style="width:30%">
            {!! Form::label('numerocnd', 'Número CND:', ['class' => 'control-label']) !!}
            {!! Form::text('numero_cnd', $documentocnd->numero_cnd, ['class' => 'form-control']) !!}
        </div>
    </div>
    <div class="form-group" style="width: 50%">
        {!! Form::label('validade_cnd', 'Validade:', ['class' => 'control-label']) !!}
        {!! Form::date('validade_cnd', $documentocnd->validade_cnd, ['class' => 'form-control']) !!}
    </div>
    <div class="form-group">
        <div style="width:30%">
            <?php if(isset($documentocnd->arquivo_cnd)) { ?>

                <?php echo '<a href="/'.$documentocnd->arquivo_cnd.'" class="btn btn-info" role="button" target="_blank">Download anexo</a>'; ?>
            <?php } else { ?>
                {!! Form::label('arquivo_cnd', 'Arquivo:', ['class' => 'control-label']) !!}
                {!! Form::file('arquivo', null, ['class' => 'form-control']) !!}
            <?php } ?>
        </div>
    </div>

    <?php if(isset($observacoes_extras)) { ?>
        <?php foreach($observacoes_extras as $observacao) { ?>
        <div class="row" style="width:60%;margin-bottom:30px;margin-top:30px;">
            <div class="col-sm-2"><?php  echo $observacao['nome_usuario']; ?></div>
            <div class="col-sm-8"><?php echo $observacao['texto']; ?></div>
            <div class="col-sm-2">
                <?php
                $date = new DateTime($observacao['data']);
                echo date_format($date, 'd/m/Y H:i');
                ?>
            </div>
        </div>
        <?php } ?>
    <?php } ?>

    <div class="form-group">
        <div style="width:30%">
            <label for="descricao_solicitacao">Adicionar uma observação: </label>
            <textarea name="observacao" id="observacao" class="validate" style="height:80px;width:415px"></textarea>
            <input type="hidden" name="add_usuario_edit" value="usuario_edit" />
        </div>
    </div>




    {!! Form::submit('Update CND', ['class' => 'btn btn-default']) !!}
    <a href="{{ route('monitorcnd.search') }}" class="btn btn-default">Voltar</a>

    {!! Form::close() !!}
    <hr/>

    <script>
        jQuery(function($){
            $('input[name="cnpj"]').mask("99.999.999/9999-99");
        });

        $('select').select2();

    </script>

@stop