<?php
namespace App\UI;

use Illuminate\Support\Facades\URL;
use App\Utils\PString;

// TODO: passar todos para o Javascript

class Html {

	public static function getImagePath($img_name = null) {

		$src = [URL::to('/')];

		if ($img_name) {
			array_push($src, $img_name);
		}

		return implode('/', $src);
	}

	public static function getImage($img_name, $params = []) {

		$style  = [];
		$click  = '';
		$title  = isset($params['title'])  ? $params['title']  : '';
		$width  = isset($params['width'])  ? $params['width']  : '';
		$height = isset($params['height']) ? $params['height'] : '';

		if (isset($params['onclick'])) {
			$click = $params['onclick'];
			array_push($style, 'cursor:pointer');
		}

		if ($width) {
			array_push($style, "width:{$width}");
		}

		if ($height) {
			array_push($style, "height:{$height}");
		}

		return sprintf('<img src="%s" border="0" title="%s" style="%s" onclick="%s" />', self::getImagePath($img_name), $title, implode(';', $style), $click);
	}

	// ============================================================================================

	public static function JComboBox($params) {
		return JComboBox::create($params);
	}

	public static function JComboBoxMultiple($params) {
		return JComboBoxMultiple::create($params);
	}

	public static function button($params) {

		$params['class']     = isset($params['class'])    && $params['class']   !== '' ? $params['class'] : 'btn btn-success';
		$params['id']       = isset($params['id'])        && $params['id']      !== '' ? $params['id']      : '';
		$params['value']    = isset($params['value'])     && $params['value']   !== '' ? $params['value']   : '';
		$params['onclick']  = isset($params['onclick'])   && $params['onclick'] !== '' ? $params['onclick'] : '';
		$params['disabled'] = isset($params['disabled'])  && gettype($params['disabled'])  == 'boolean' ? $params['disabled'] : false;
		$params['type']     = 'button';

		return self::input($params);
	}

	public static function hidden($name, $value) {
		return PString::format('<input type=hidden name="{0}" id="{0}" value="{1}" />', $name, $value);
	}

	public static function input($params, $script = null) {

		$params['class']     = isset($params['class'])     && $params['class']     !== '' ? $params['class'] : 'form-control';
		$params['type']      = isset($params['type'])      && $params['type']      !== '' ? $params['type']  : 'text';
		$params['id']        = isset($params['id'])        && $params['id']        !== '' ? $params['id']    : '';
		$params['value']     = isset($params['value'])     && $params['value']     !== '' ? $params['value'] : '';
		$params['label']     = isset($params['label'])     && $params['label']     !== '' ? $params['label'] : '';
		$params['width']     = isset($params['width'])     && $params['width']     !== '' ? $params['width'] : '100%';
		$params['maxlength'] = isset($params['maxlength']) && $params['maxlength'] !== '' ? $params['maxlength'] : '';
		$params['disabled']  = isset($params['disabled'])  && gettype($params['disabled'])  == 'boolean' ? $params['disabled'] : false;

		if ($params['type'] == 'button') {
			$params['onclick'] = isset($params['onclick'])   && $params['onclick'] !== '' ? $params['onclick'] : '';
		}

		$input = [];
		$style = [];

		if ($params['width']) {
			array_push($style, sprintf('width:%s', $params['width']));
		}

		if ($params['disabled'] === true) {
			array_push($style, 'cursor:not-allowed');
		}

		$atts  = [
			'class'    => $params['class'],
			'type'     => $params['type'],
			'name'     => $params['id'],
			'id'       => $params['id'],
			'value'    => $params['value'],
			'style'    => implode(';', $style)
		];

		if (isset($params['onclick'])) {
			$atts['onclick'] = $params['onclick'];
		}

		if ($params['disabled'] === true) {
			$atts['readOnly'] = 'readOnly';
		}

		if (is_numeric($params['maxlength'])) {
			$atts['maxLength'] = $params['maxlength'];
		}

		array_push($input, sprintf('<label>%s</label>', $params['label']));
		array_push($input, '<input');

		foreach ($atts as $attr => $attValue) {
			array_push($input, sprintf('%s="%s"', $attr, $attValue));
		}

		array_push($input, '/>');

		if ($script) {
			array_push($input, sprintf('<script type="text/javascript">$(document).ready(function () {%s});</script>', $script));
		}

		return implode(' ', $input);
	}

	public static function input_cnpj($params) {
		$params['maxlength'] = 18;
		return self::input($params, sprintf('$("#%s").mask("99.999.999/9999-99");', $params['id']));
	}

	public static function input_cpf($params) {
		$params['maxlength'] = 14;
		return self::input($params, sprintf('$("#%s").mask("999.999.999-99");', $params['id']));
	}

	public static function input_cep($params) {
		$params['maxlength'] = 9;
		return self::input($params, sprintf('$("#%s").mask("99999-999");', $params['id']));
	}

	public static function input_data($params) {
		$params['maxlength'] = 10;
		return self::input($params, sprintf('$("#%s").mask("99/99/9999");', $params['id']));
	}

	public static function input_inscricao_estadual($params) {
		$params['maxlength'] = 15;
		return self::input($params, sprintf('$("#%s").mask("999.999.999.999");', $params['id']));
	}

	public static function input_money($params) {
		$params['maxlength'] = 16;
		$script = sprintf("$('#%s').maskMoney({
			symbol      : 'R$ ',
			allowZero   : true,
			showSymbol  : false,
			thousands   : '.',
			decimal     : ',',
			symbolStay  : false,
			defaultZero : true
		});", $params['id']);
		return self::input($params, $script);
	}

	public static function input_periodo($params) {
		$params['maxlength'] = 7;
		return self::input($params, sprintf('$("#%s").mask("99/9999");', $params['id']));
	}

	public static function input_telefone($params) {
		$params['maxlength'] = 14;
		return self::input($params, sprintf('$("#%s").mask("(99) 9999-9999");', $params['id']));
	}

	public static function input_celular($params) {
		$params['maxlength'] = 15;
		return self::input($params, sprintf('$("#%s").mask("(99) 99999-9999");', $params['id']));
	}

	public static function input_time($params) {
		$params['maxlength'] = 5;
		return self::input($params, sprintf('$("#%s").mask("99:99");', $params['id']));
	}

	public static function label($label, $value = null) {

		$input = [];

		array_push($input, sprintf('<label>%s</label>: ', $label));
		array_push($input, sprintf('<span>%s</span>', $value));

		return implode('', $input);
	}

	public static function textarea($params) {

		$params['class']     = isset($params['class'])     && $params['class']     !== '' ? $params['class']  : 'form-control';
		$params['id']        = isset($params['id'])        && $params['id']        !== '' ? $params['id']     : '';
		$params['value']     = isset($params['value'])     && $params['value']     !== '' ? $params['value']  : '';
		$params['label']     = isset($params['label'])     && $params['label']     !== '' ? $params['label']  : '';
		$params['width']     = isset($params['width'])     && $params['width']     !== '' ? $params['width']  : '100%';
		$params['height']    = isset($params['height'])    && $params['height']    !== '' ? $params['height'] : '120px';
		$params['disabled']  = isset($params['disabled'])  && gettype($params['disabled'])  == 'boolean' ? $params['disabled'] : false;

		$input = [];
		$style = [];

		if ($params['width']) {
			array_push($style, sprintf('width:%s', $params['width']));
		}

		if ($params['height']) {
			array_push($style, sprintf('height:%s', $params['height']));
		}

		$atts  = [
			'class'    => $params['class'],
			'name'     => $params['id'],
			'id'       => $params['id'],
			'value'    => $params['value'],
			'style'    => implode(';', $style)
		];

		if ($params['disabled'] === true) {
			$atts['disabled'] = 'readOnly';
		}

		array_push($input, sprintf('<label>%s</label>', $params['label']));
		array_push($input, '<textarea ');

		foreach ($atts as $attr => $attValue) {
			array_push($input, sprintf(' %s="%s" ', $attr, $attValue));
		}

		array_push($input, sprintf('>%s</textarea>', $params['value']));

		return implode('', $input);
	}

	public static function getGridCellSuccess($value, $title = '') {
		return sprintf('<td style="background:rgb(208, 237, 218); color:rgb(12, 85, 39); padding:4px;" title="%s">%s</td>', $title, $value);
	}

	public static function getGridCellDanger($value, $title = '') {
		return sprintf('<td style="background:rgb(251, 213, 217); color:rgb(113, 17, 31); padding:4px;" title="%s">%s</td>', $title, $value);
	}

}
