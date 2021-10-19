@extends('...layouts.master')

@section('content')

<style>
.form-control {
	height: 35px;
}
.form-group {
	margin-bottom: 10px;
}
</style>

<div class="content-top">
    <div class="row">
        <div class="col-md-12">
			<h1 class="title">Guias de Pagamento - ISS</h1>
			@if (!empty($msg))
			<div class="alert alert-success pull-right" style="margin-bottom: 0px;padding: 10px;margin-top: -10px;">
				{{ $msg }}
			</div>
			@endif
        </div>
    </div>
</div>
    
<div class="content">
	<div class="row">
		<div class="col-md-4">
		{!! Form::open([
			'route' => 'guiaiss.processaGuiaISS'
		]) !!}
			<div class="form-group row">
				<div class="col-xs-6">
					{!! Form::label('periodo', 'Periodo Apuração', ['class' => 'control-label']) !!}
					{!! Form::text('periodo', $periodo, ['class' => 'form-control', 'placeholder' => '--/----', 'required' => 'required']) !!}
				</div>
				<div class="col-xs-6">
					{!! Form::label('competencia', 'Competência', ['class' => 'control-label']) !!}
					{!! Form::text('competencia', '', ['class' => 'form-control', 'placeholder' => '--/----', 'required' => 'required']) !!}
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-6">
					{!! Form::label('cnpjraiz', 'CNPJ Raiz', ['class' => 'control-label']) !!}
					{!! Form::text('cnpjraiz1', $cnpjRaiz, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
					{!! Form::hidden('cnpjraiz', $cnpjRaiz, ['class' => 'form-control']) !!}
					{!! Form::hidden('fileGuiaISS', '', ['class' => 'form-control']) !!}
				</div>
				<div class="col-xs-6">
					{!! Form::label('cnpj', 'Final CNPJ', ['class' => 'control-label']) !!}
					{!! Form::text('cnpj', '', ['class' => 'form-control', 'maxlength' => '6', 'min' => '0', 'max' => '999999',
					'required'
					=> 'required']) !!}
					<span class='img-check'></span>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-6">
					{!! Form::label('insc_municipal', 'Inscrição Municipal', ['class' => 'control-label']) !!}
					{!! Form::text('insc_municipal', '', ['class' => 'form-control']) !!}
					<span class='img-check'></span>
				</div>
				<div class="col-xs-6">
					{!! Form::label('codigo', 'Código', ['class' => 'control-label']) !!}
					{!! Form::text('codigo', '', ['class' => 'form-control']) !!}
					<span class='img-check'></span>
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-12">
					{!! Form::label('municipio', 'Município', ['class' => 'control-label']) !!}
					{!! Form::select('municipio', $municipios, $municipioselected, ['class' => 'form-control', 'required' => 'required']) !!}
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-12">
					{!! Form::label('codigobarras', 'Código de Barras', ['class' => 'control-label']) !!}
					{!! Form::text('codigobarras', '', ['class' => 'form-control input-sm', 'maxlength' => '48', 'required' => 'required']) !!}
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-6">
					{!! Form::label('vencimento', 'Vencimento', ['class' => 'control-label']) !!}
					{!! Form::date('vencimento', '', ['class' => 'form-control input-sm', 'required' => 'required']) !!}
				</div>
				<div class="col-xs-6">
					{!! Form::label('valorguia', 'Valor Guia', ['class' => 'control-label']) !!}
					{!! Form::text('valorguia', '', ['class' => 'form-control', 'required' => 'required']) !!}
				</div>
			</div>
			<div class="form-group row">
				<div class="col-xs-6">
					{!! Form::label('valormulta', 'Valor Multa', ['class' => 'control-label']) !!}
					{!! Form::text('valormulta', '', ['class' => 'form-control']) !!}
				</div>
				<div class="col-xs-6">
					{!! Form::label('valorjuros', 'Valor Juros', ['class' => 'control-label']) !!}
					{!! Form::text('valorjuros', '', ['class' => 'form-control']) !!}
				</div>
			</div>
		{!! Form::submit('Salvar Guia', ['class' => 'btn btn-success-block']) !!}
		{!! Form::close() !!}
		</div>
		<div class="col-md-8">
			<div>
				<object id="guiaiss" data="/guiaiss/0.pdf" type="application/pdf" width="100%" height="550px"></object>
			</div>
			<div>
				<button class="btn btn-sm btn-info" id="behind" type="button" onclick="getNextFile">Anterior</button> <button class="btn btn-sm btn-info" id="forward" type="button" onclick="getNextFile">Próximo</button>
			</div>
		</div>
	</div>
</div>                                          

<script type="text/javascript">
$(document).ready(function (){
	$('#periodo').mask('99/9999');
	$('#competencia').mask('99/9999');
	var pages = [	
		@foreach($arquivos as $file)
		'{{ basename($file) }}',
		@endforeach 
	];
	console.log('Pages: ', pages, 'Length: ', pages.length);
	var newUrl = '/guiaiss/'+pages[0]+'#zoom=145,270,575',
	newPDF = '/guiaiss/'+pages[0];
	$('#guiaiss').attr('data', newUrl);
	$('input[name=fileGuiaISS]').val(newPDF)
	
	elpdf = $('input[name=fileGuiaISS]');
	el = $('#guiaiss'),
	idx = 0,
	idx == 0 ? document.getElementById("behind").disabled = true : document.getElementById("behind").disabled = false;
	getNextFile = e => {
		if (e.target.id == "forward") {
			var indice = ++idx%pages.length;
			newUrl = '/guiaiss/'+pages[idx = indice]+'#zoom=145,270,575';
			newPDF = '/guiaiss/'+pages[idx = indice];
			// console.warn('indice = ', idx, 'file = ', newPDF);
			
			el.attr('data', newUrl);
			elpdf.val(newPDF);
			idx >= pages.length-1 ? document.getElementById("forward").disabled = true : document.getElementById("forward").disabled = false;
			
			idx == 0 ? document.getElementById("behind").disabled=true : document.getElementById("behind").disabled=false;
		} else {
			var indice = (pages.length - (pages.length - --idx)%pages.length)%pages.length;
			newUrl = '/guiaiss/'+pages[idx = indice]+'#zoom=145,270,575';
			newPDF = '/guiaiss/'+pages[idx = indice];
			
			el.attr('data', newUrl);
			elpdf.val(newPDF);
			// console.warn('indiceb = ', idx, 'file = ', newPDF);
			idx >= pages.length-1 ? document.getElementById("forward").disabled = true : document.getElementById("forward").disabled = false;
			idx == 0 ? document.getElementById("behind").disabled = true : document.getElementById("behind").disabled = false;
		}
	}
		
	document.getElementById("forward").onclick = getNextFile;
	document.getElementById("behind").onclick = getNextFile;
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
			$('#municipio').focus();
			let cnpjraiz = $('input[name=cnpjraiz]').val(),
			cnpj = this.value;
			axios.get(`verificaCNPJ/${cnpjraiz+cnpj}`)
			.then(response => {
				// console.warn(response.data[0]);
				var res = response.data[0];
				if (res) {
					$('.img-check').html('<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span>');
					$('#municipio').val(res.cod_municipio);
					$('#insc_municipal').val(res.insc_municipal);
					$('#codigo').val(res.codigo);
					$('input[name=codigobarras]').val('');
					$('input[name=vencimento]').val('');
					$('input[name=valorguia]').val('');
					$('input[name=valormulta]').val('');
					$('input[name=valorjuros]').val('');
					$('input[name=codigobarras]').focus();
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
			$('#municipio').focus();
			axios.get(`verificaCCM/${ccm}`)
			.then(response => {
				// console.warn(response.data[0]);
				var res = response.data[0];
				if (res) {
					$('.img-check').html('<span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span>');
					$('#municipio').val(res.cod_municipio);
					$('#cnpj').val(res.cnpj.substring(8,14));
					$('#codigo').val(res.codigo);
					$('input[name=codigobarras]').val('');
					$('input[name=vencimento]').val('');
					$('input[name=valorguia]').val('');
					$('input[name=valormulta]').val('');
					$('input[name=valorjuros]').val('');
					$('input[name=codigobarras]').focus();
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
					$('input[name=codigobarras]').val('');
					$('input[name=vencimento]').val('');
					$('input[name=valorguia]').val('');
					$('input[name=valormulta]').val('');
					$('input[name=valorjuros]').val('');
					$('input[name=codigobarras]').focus();
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
	
	$('input[name=codigobarras]').blur('keyup', function() {
		var arrBancos = [
			'104','107','168','184','204','208','212','213','214','215','217','218','222','224','225',
			'229','230','233','237','241','243','246','248','249','250','254','263','265','266','300',
			'318','320','341','356','366','370','376','389','394','399','409','412','422','453','456',
			'464','473','477','479','487','488','492','494','495','505','600','604','610','611','612',
			'613','623','626','630','633','634','637','638','641','643','652','653','654','655','707',
			'719','721','724','734','735','738','739','740','741','743','744','745','746','747','748',
			'749','751','752','753','755','756','757','000','001','003','004','012','014','019','021',
			'024','025','029','031','033','036','037','039','040','041','044','045','047','062','063',
			'064','065','066','069','070','072','073','074','075','076','077','078','079','081','082',
			'083','084','085','086','087','088','089','090','091','092','094','096','097','098','099',
			'260','M03','M06','M07','M08','M09','M10','M11','M12','M13','M14','M15','M16','M17','M18',
			'M19','M20','M21','M22','M23','M24'
		];
		var valorTotal = 0;
		if (this.value.length == 44) {
			// console.warn('vlr ', this.value.substring(5,15));
			// console.warn('banco ', this.value.substring(0,3));
			var banco = this.value.substring(0,3);
			if (arrBancos.indexOf(banco) > -1) { // boletos de recolhimento
				$('input[name=valorguia]').val(new Intl.NumberFormat('pt-BR').format(this.value.substring(10,19)/100));
				$('#vencimento').val(formatDate(converteFatorVencto(this.value.substring(6,9))));
			} else { // guias de recolhimento
				var codMunicipio = $('#municipio').val();
				var dataVencimento = '';
				$('input[name=valorguia]').val(new Intl.NumberFormat('pt-BR').format(this.value.substring(5,15)/100));
				$('input[name=valormulta]').val('0,00');
				$('input[name=valorjuros]').val('0,00');
				// codigo diferente de São Paulo/SP - 3550308 (dt = AAAAMMDD == Febraban)
				if (codMunicipio && codMunicipio != '3550308') { 
					var str = this.value.substring(19,27),
					dtYear = str.substring(0,4),
					dtMonth = str.substring(4,6),
					dtDay = str.substring(6,8);
					dataVencimento = dtYear+'-'+dtMonth+'-'+dtDay;
					$('#vencimento').val(dataVencimento);
				} else { // codigo de São Paulo/SP - 3550308 (dt = AAMMDD != Febraban)
					var str = this.value.substring(19,25),
					dtYear = str.substring(0,2),
					dtMonth = str.substring(2,4),
					dtDay = str.substring(4,6);
					dataVencimento = '20'+dtYear+'-'+dtMonth+'-'+dtDay;
					$('#vencimento').val(dataVencimento);
				}
			}
		}
	});
	
	setTimeout(() => {
		$('.alertMessage').slideUp();
	}, 5000);
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


function converteFatorVencto(fator) {
	var baseDate = new Date('10/07/1997');
    var date = new Date(baseDate);
    var newdate = new Date(date);

    newdate.setDate(newdate.getDate() + fator);
    
    var dd = newdate.getDate();
    var mm = newdate.getMonth() + 1;
    var y = newdate.getFullYear();

    return formatedDate = mm + '/' + dd + '/' + y;
}

function formatDate(date) {
    var d = new Date(date),
        month = '' + (d.getMonth() + 1),
        day = '' + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) 
        month = '0' + month;
    if (day.length < 2) 
        day = '0' + day;

    return [year, month, day].join('-');
}

</script>

@stop
	
	