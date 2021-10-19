<?php
namespace App\UI;

// TODO: passar os parametros daqui para a classe Javascript correspondente
// TODO: implementar o select All (na minha classe JS)

class JComboBoxMultiple extends JComponent {
/*
	public static function create2($params) { // pesquisar sem selectAll (terei de implementar no JS)

		$params['options']     = isset($params['options'])     && is_array($params['options'])  ? $params['options']  : [];
		$params['selected']    = isset($params['selected'])    && is_array($params['selected']) ? $params['selected'] : [];
		$params['id']          = isset($params['id'])          && $params['id']    !== ''       ? $params['id']       : '';
		$params['placeholder'] = isset($params['placeholder']) && $params['placeholder'] !== '' ? $params['placeholder'] : '';
		$params['width']       = isset($params['width'])       && $params['width'] !== ''       ? $params['width']    : '100%';
		$params['selectAll']   = isset($params['selectAll'])   && gettype($params['selectAll'])  == 'boolean' ? $params['selectAll']  : true;
		$params['name']        = implode('', [$params['id'], '[]']);

		$html = [];

		array_push($html, sprintf('<select multiple data-live-search="true" class="selectpicker" name="%s" id="%s" data-placeholder="%s" style="width:%s">', $params['name'], $params['id'], $params['placeholder'], $params['width']));

		foreach ($params['options'] as $v => $t) {
			array_push($html, sprintf('<option %s value="%s">%s</option>', (!empty($params['selected']) && in_array($v, $params['selected']) ? 'selected' : ''), $v, $t));
		}

		array_push($html, '</select>');
		array_push($html, sprintf('<script>$("#%s").selectpicker();</script>', $params['id']));

		return implode('', $html);
	}
*/
	public static function create($params) {

		// TODO: implementar um no Javascript com selectAll, Ajax (traz tudo com loading) e pesquisar

		$params['options']     = isset($params['options'])     && is_array($params['options'])  ? $params['options']  : [];
		$params['selected']    = isset($params['selected'])    && is_array($params['selected']) ? $params['selected'] : [];
		$params['id']          = isset($params['id'])          && $params['id']    !== ''       ? $params['id']       : '';
		$params['placeholder'] = isset($params['placeholder']) && $params['placeholder'] !== '' ? $params['placeholder'] : '';
		$params['width']       = isset($params['width'])       && $params['width'] !== ''       ? $params['width']    : '100%';
		$params['selectAll']   = isset($params['selectAll'])   && gettype($params['selectAll'])  == 'boolean' ? $params['selectAll']  : true;
		$params['name']        = implode('', [$params['id'], '[]']);

		$html = [];
		$code = [];

		array_push($html, sprintf('<select multiple class="form-control" name="%s" id="%s" data-placeholder="%s" style="width:%s">', $params['name'], $params['id'], $params['placeholder'], $params['width']));

		foreach ($params['options'] as $v => $t) {
			array_push($html, sprintf('<option %s value="%s">%s</option>', (!empty($params['selected']) && in_array($v, $params['selected']) ? 'selected' : ''), $v, $t));
		}

		array_push($html, '</select>');
		array_push($code, 'nonSelectedText : "Nenhum selecionado"');

 		if ($params['selectAll']) {
 			array_push($code, 'includeSelectAllOption : true');
 			array_push($code, 'selectAllText : "Todos"');
 		}

 		array_push($html, sprintf('<script>$("#%s").multiselect({%s});</script>', $params['id'], implode(', ', $code)));

 		return implode('', $html);
	}

}
