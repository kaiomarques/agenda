@extends('...layouts.master')
@section('content')

{!! Form::open([
	'route' => 'arquivos.downloads'
]) !!}
<div class="main" id="empresaMultipleSelectSelecionar" style="display:block;">
        <div class="row">
            <div class="col-md-12">
				<h2 class="sub-title">
					
				</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
            	{!! Form::label('estabelecimentos_selected[]', 'Selecionar estabelecimentos', ['class' => 'control-label']) !!}
                {!! Form::select('estabelecimentos_selected[]', $estabelecimentos, '', ['class' => 'form-control s2_multi', 'multiple' => 'multiple']) !!}
            </div>
        </div>
        <br />
        <div class="row">
            <div class="col-md-6">
            	{!! Form::label('uf_selected[]', 'Selecionar UFS', ['class' => 'control-label']) !!}
                {!! Form::select('ufs[]', $ufs, '', ['class' => 'form-control s2_multi selectUFsMultiDown', 'multiple' => 'multiple']) !!}
				<div id="infoMessage" class="alert alert-warning" role="alert">Você pode selecionar um máximo de <strong>4 UFs</strong> por operação <i class="fa fa-exclamation" aria-hidden="true"></i></div>
            </div>
        </div>
        <BR />

        <div class="row">
            <div class="col-md-6">
            	{!! Form::label('tributo_id', 'Selecionar Tributo', ['class' => 'control-label']) !!}
				{!! Form::select('tributo_id', $tributos, '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <BR />
        <div class="row">
            <div class="col-md-3">
                {!! Form::label('data_entrega_inicio', 'Data Início Entrega', ['class' => 'control-label']) !!}         
                {!! Form::date('data_entrega_inicio', '', ['class' => 'form-control']) !!}
            </div>

            <div class="col-md-3">
                {!! Form::label('data_entrega_fim', 'Data Fim Entrega', ['class' => 'control-label']) !!}         
                {!! Form::date('data_entrega_fim', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <br />

        <div class="row">
            <div class="col-md-3">
                {!! Form::label('data_aprovacao_inicio', 'Data Início Aprovação', ['class' => 'control-label']) !!}         
                {!! Form::date('data_aprovacao_inicio', '', ['class' => 'form-control']) !!}
            </div>

            <div class="col-md-3">
                {!! Form::label('data_aprovacao_fim', 'Data Fim Aprovação', ['class' => 'control-label']) !!}         
                {!! Form::date('data_aprovacao_fim', '', ['class' => 'form-control']) !!}
            </div>
        </div>
        <br />

        <div class="row">
            <div class="col-md-3">    
                {!! Form::label('periodo_apuracao_inicio', 'Periodo de apuração', ['class' => 'control-label']) !!}     
                {!! Form::text('periodo_apuracao_inicio', '', ['class' => 'form-control']) !!}
            </div>
            <div class="col-md-9">    
                {{-- {!! Form::label('periodo_apuracao_fim', 'Periodo de apuração', ['class' => 'control-label']) !!}     
				{!! Form::text('periodo_apuracao_fim', '', ['class' => 'form-control']) !!} --}}
				{!! Form::hidden('periodo_apuracao_fim', '00/0000', ['class' => 'form-control']) !!}
            </div>
        </div>
        <br />


        <div class="row">
            <div class="col-md-3">
                {!! Form::submit('Download', ['class' => 'btn btn-success-block', 'id' => 'frmDownZipSubmit']) !!}
                {!! Form::close() !!}
			</div>
        </div>
    </div>
<script type="text/javascript">
    
jQuery(function($){
    $('input[name="periodo_apuracao_inicio"]').mask("99/9999");
    $('input[name="periodo_apuracao_fim"]').mask("99/9999");
	$('select').select2();
	
	$('input[name="periodo_apuracao_inicio"]').blur(function() {
		$('input[name="periodo_apuracao_fim"]').val($('input[name="periodo_apuracao_inicio"]').val());
	});
	
	// $("form").submit(function(event) {
	// 	$('#frmDownZipSubmit').attr('disabled', true);
	// 	setTimeout(() => {
	// 		$('#frmDownZipSubmit').attr('disabled', false);
	// 	}, 10000);
	// 	$("#alert-status-warning").slideUp(
	// 		{
	// 		opacity: "show"
	// 		},
	// 		"slow"
	// 	);
	// 	$("#alert-status").slideUp(
	// 		{
	// 		opacity: "show"
	// 		},
	// 		"slow"
	// 	);
	// });
	
	$("#frmDownZipSubmit").click(function(event) {
		event.preventDefault();
		if ($(".select2-selection__rendered")[1].childNodes.length > 5) {
			$(this).removeAttr("selected");
			$("#infoMessage").slideDown({
				opacity: "show"
			},
			"slow"
			);
		} else {
			$(this).removeAttr("selected");
			$("#infoMessage").slideUp({
				opacity: "show"
			},
			"slow"
			);
			$('form').submit();
		}
		
		// $('#frmDownZipSubmit').attr('disabled', true);
		// setTimeout(() => {
		// 	$('#frmDownZipSubmit').attr('disabled', false);
		// }, 10000);
		// $("#alert-status-warning").slideUp(
		// 	{
		// 	opacity: "show"
		// 	},
		// 	"slow"
		// );
		// $("#alert-status").slideUp(
		// 	{
		// 	opacity: "show"
		// 	},
		// 	"slow"
		// );
	});
	
	$('.selectUFsMultiDown').change(function(){
		if ($(".select2-selection__rendered")[1].childNodes.length > 5) {
			$(this).removeAttr("selected");
			$("#infoMessage").slideDown({
				opacity: "show"
			},
			"slow"
			);
			return false;
		} else {
			$("#infoMessage").slideUp({
				opacity: "show"
			},
			"slow"
			);
			return true;
		}
	});
	
	
});



</script>
@stop
<footer>
    @include('layouts.footer')
</footer>