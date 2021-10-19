@extends('...layouts.master')

@section('content')

<style>
	.form-control {
		height: 34px;
	}

	.form-group {
		margin-bottom: 10px;
	}
</style>

<div class="content-top">
	<div class="row">
		<div class="col-md-12">
			<h1 class="title">Conciliação Memória x Guias - ISS</h1>
			@if (!empty($msg))
			<div class="alert alert-success pull-right" style="margin-bottom: 0px;padding: 10px;margin-top: -10px;">
				{{ $msg }}
			</div>
			@endif
		</div>
	</div>
</div>

<div class="content">
	{!! Form::open([
		'route' => 'guiaiss.conciliacaoMemoriaGuias'
	]) !!}
	<div class="row">
		<div class="col-md-12">
			<div class="form-group row">
				<div class="col-xs-3">
					{!! Form::label('cnpjraiz', 'CNPJ Raiz', ['class' => 'control-label']) !!}
					{!! Form::text('cnpjraiz1', $cnpjRaiz, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
					{!! Form::hidden('cnpjraiz', $cnpjRaiz, ['class' => 'form-control']) !!}
				</div>
				<div class="col-xs-3">
					{!! Form::label('cnpj', 'Final CNPJ ou', ['class' => 'control-label']) !!}
					{!! Form::text('cnpj', '', ['class' => 'form-control', 'maxlength' => '6', 'min' => '0', 'max' => '999999',
					'required'
					=> 'required']) !!}
					<span class='img-check'></span>
				</div>
				<div class="col-xs-3">
					{!! Form::label('insc_municipal', 'Inscrição Municipal ou', ['class' => 'control-label']) !!}
					{!! Form::text('insc_municipal', '', ['class' => 'form-control']) !!}
					<span class='img-check'></span>
				</div>
				<div class="col-xs-3">
					{!! Form::label('codigo', 'Código', ['class' => 'control-label']) !!}
					{!! Form::text('codigo', '', ['class' => 'form-control']) !!}
					<span class='img-check'></span>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-3">
					{!! Form::label('periodo', 'Periodo Apuração', ['class' => 'control-label']) !!}
					{!! Form::text('periodo', $periodo, ['class' => 'form-control', 'placeholder' => '--/----', 'required' => 'required']) !!}
				</div>
				<div class="col-xs-3">
					{!! Form::label('competencia', 'Competência', ['class' => 'control-label']) !!}
					{!! Form::text('competencia', '', ['class' => 'form-control', 'placeholder' => '--/----']) !!}
				</div>
				<div class="col-xs-6">
					<label>Arquivo CSV</label>
					<div class="input-group">
						<label class="input-group-btn">
							<span class="btn btn-default">
								Procurar&hellip; <input type="file" name="fileGuiaISS" id="fileGuiaISS" style="display: none;" required>
							</span>
						</label>
						<input type="text" class="form-control" readonly>
					</div>
				</div>
			</div>

			<div class="form-group row">
				<div class="col-xs-3">
					{!! Form::label('vencimento_inicio', 'Data Inicial Vencimento', ['class' => 'control-label']) !!}
					{!! Form::date('vencimento_inicio', '', ['class' => 'form-control']) !!}
				</div>
				<div class="col-xs-3">
					{!! Form::label('vencimento_fim', 'Data Final Vencimento', ['class' => 'control-label']) !!}
					{!! Form::date('vencimento_fim', '', ['class' => 'form-control']) !!}
				</div>
				<div class="col-xs-3">
					{!! Form::label('leitura_inicio', 'Data Inicial Leitura', ['class' => 'control-label']) !!}
					{!! Form::date('leitura_inicio', '', ['class' => 'form-control', 'required' => 'required']) !!}
				</div>
				<div class="col-xs-3">
					{!! Form::label('leitura_fim', 'Data Final Leitura', ['class' => 'control-label']) !!}
					{!! Form::date('leitura_fim', '', ['class' => 'form-control', 'required' => 'required']) !!}
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-3">{!! Form::submit('Conciliar Guias', ['class' => 'btn btn-success-block']) !!}</div>
				<div class="col-xs-3"></div>
				<div class="col-xs-3"></div>
				<div class="col-xs-3"></div>
			</div>
		</div>
	</div>
	{!! Form::close() !!}
	<div class="row">
		<div class="col-xs-12">
			<div class="table-responsive">
				<table class="table table-bordered table-hover table-sm" width="100%" cellspacing="0" id="dtConciliacao">
					<thead>
						
					</thead>
					<tbody>
						<!-- featch data from db -->
					</tbody>
					<tfoot>
						
					</tfoot>
				</table>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function () {
	$('#periodo').mask('99/9999');
	$('#competencia').mask('99/9999');
	// load page, focus on cnpj
	$('input[name=cnpj]').focus();
	// Restrict input to digits by using a regular expression filter.
	$('#cnpj').inputFilter(function(value) {
		return /^\d*$/.test(value);
	});
	
	$('#cnpj').on('keyup', function() {
		if (this.value.length == 6) {
			$('.img-check').show();
			$('.img-check').html('<img src="/assets/img/loading_16.gif" />');
			$('#leitura_inicio').focus();
			let cnpjraiz = $('input[name=cnpjraiz]').val(),
			cnpj = this.value;
			axios.get(`verificaCNPJ/${cnpjraiz+cnpj}`)
			.then(response => {
				// console.warn(response.data[0]);
				var res = response.data[0];
				if (res) {
					$('.img-check').html('<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span>');
					$('#insc_municipal').val(res.insc_municipal);
					$('#codigo').val(res.codigo);
					$('input[name=leitura_inicio]').focus();
				} else {
					$('.img-check').html('<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span>');
					$('input[name=cnpj]').val('');
					$('input[name=insc_municipal]').val('');
					$('input[name=codigo]').val('');
					$('input[name=cnpj]').focus();
					Swal.fire({
						type: 'error',
						title: 'Houston, we have a problem!',
						text: 'CNPJ não cadastrado! Favor cadastrar o Estabelecimento.',
					})
				}	
			})
			.catch(error => {
				console.warn('ERR: ', error)
			})
		} else {
			$('input[name=cnpj]').focus();
		}
	});
	
	$('#insc_municipal').on('blur', function() {
		ccm = this.value;
		if (ccm != '') {
			$('.img-check').show();
			$('.img-check').html('<img src="/assets/img/loading_16.gif" />');
			$('#leitura_inicio').focus();
			axios.get(`verificaCCM/${ccm}`)
			.then(response => {
				// console.warn(response.data[0]);
				var res = response.data[0];
				if (res) {
					$('.img-check').html('<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span>');
					$('#cnpj').val(res.cnpj.substring(8,14));
					$('#codigo').val(res.codigo);
					$('input[name=leitura_inicio]').focus();
				} else {
					$('.img-check').html('<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span>');
					$('input[name=cnpj]').val('');
					$('input[name=insc_municipal]').val('');
					$('input[name=codigo]').val('');
					$('input[name=cnpj]').focus();
					Swal.fire({
						type: 'error',
						title: 'Houston, we have a problem!',
						text: 'CNPJ não cadastrado para este CCM! Favor cadastrar o Estabelecimento.',
					})
				}	
			})
			.catch(error => {
				console.warn('ERR: ', error)
			})
		}
	});
	
	$('#codigo').on('blur', function() {
		codigo = this.value;
		if (codigo != '') {
			$('.img-check').show();
			$('.img-check').html('<img src="/assets/img/loading_16.gif" />');
			$('#leitura_inicio').focus();
			axios.get(`verificaCodigo/${codigo}`)
			.then(response => {
				console.warn(response.data[0]);
				var res = response.data[0];
				if (res) {
					$('.img-check').html('<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span>');
					$('#cnpj').val(res.cnpj.substring(8,14));
					$('#insc_municipal').val(res.insc_municipal);
					$('input[name=leitura_inicio]').focus();
				} else {
					$('.img-check').html('<span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span>');
					$('input[name=cnpj]').val('');
					$('input[name=insc_municipal]').val('');
					$('input[name=codigo]').val('');
					$('input[name=cnpj]').focus();
					Swal.fire({
						type: 'error',
						title: 'Houston, we have a problem!',
						text: 'CNPJ não cadastrado para este Código! Favor cadastrar o Estabelecimento.',
					})
				}	
			})
			.catch(error => {
				console.warn('ERR: ', error)
			})
		}
	});
});

// Restricts input for each element in the set of matched elements to the given inputFilter.
(function($) {
$.fn.inputFilter = function(inputFilter) {
	return this.on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
		if (inputFilter(this.value)) {
			this.oldValue = this.value;
			this.oldSelectionStart = this.selectionStart;
			this.oldSelectionEnd = this.selectionEnd;
		} else if (this.hasOwnProperty("oldValue")) {
			this.value = this.oldValue;
			this.setSelectionRange(this.oldSelectionStart, this.oldSelectionEnd);
		}
	});
	};
}(jQuery));

// Some input filters you might want to use:

// Integer values (positive only):
// /^\d*$/.test(value)
// Integer values (positive and up to a particular limit):
// /^\d*$/.test(value) && (value === "" || parseInt(value) <= 500)
// Integer values (both positive and negative):
// /^-?\d*$/.test(value)
// Floating point values (allowing both . and , as decimal separator):
// /^-?\d*[.,]?\d*$/.test(value)
// Currency values (i.e. at most two decimal places):
// /^-?\d*[.,]?\d{0,2}$/.test(value)
// A-Z only (i.e. basic Latin letters):
// /^[a-z]*$/i.test(value)
// Latin letters only (i.e. English and most European languages, see https://unicode-table.com for details about Unicode character ranges):
// /^[a-z\u00c0-\u024f]*$/i.test(value)
// Hexadecimal values:
// /^[0-9a-f]*$/i.test(value)

$(function() {
  // We can attach the `fileselect` event to all file inputs on the page
  $(document).on('change', ':file', function() {
    var input = $(this),
        numFiles = input.get(0).files ? input.get(0).files.length : 1,
        label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
    input.trigger('fileselect', [numFiles, label]);
  });

  // We can watch for our custom `fileselect` event like this
  $(document).ready( function() {
      $(':file').on('fileselect', function(event, numFiles, label) {

          var input = $(this).parents('.input-group').find(':text'),
              log = numFiles > 1 ? numFiles + ' files selected' : label;

          if( input.length ) {
              input.val(log);
          } else {
              if( log ) alert(log);
          }

      });
  });
  
});
</script>

@stop
<footer>
	@include('layouts.footer')
</footer>
