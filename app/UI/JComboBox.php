<?php
namespace App\UI;

// TODO: passar os parametros daqui para a classe Javascript correspondente

class JComboBox extends JComponent {

	public static function create($params) {

		$params['options']  = isset($params['options'])  && is_array($params['options'])  ? $params['options']     : [];
		$params['selected'] = isset($params['selected']) && $params['selected']    !== '' ? $params['selected']    : '';
		$params['id']       = isset($params['id'])       && $params['id']          !== '' ? $params['id']          : '';
		$params['width']    = isset($params['width'])    && $params['width']       !== '' ? $params['width']       : '100%';
		$params['label']    = isset($params['label'])    && $params['label']       !== '' ? $params['label']       : '';
		$params['nullable'] = isset($params['nullable']) && $params['nullable']    !== '' ? htmlentities($params['nullable']) : '';
		$params['disabled'] = isset($params['disabled']) && is_bool($params['disabled'])  ? $params['disabled']    : false;
		$params['name']     = $params['id'];
		$params['options']  = self::toOptions($params['options'], $params['nullable'], $params['disabled']);

		$html = [];

		array_push($html, sprintf('<label>%s</label>', $params['label']));
		array_push($html, sprintf('<select class="form-control" name="%s" id="%s" data-placeholder="" style="width:%s">', $params['name'], $params['id'], $params['width']));

		if (!empty($params['options'])) {

			foreach ($params['options'] as $v => $t) {

				$selected = ($params['selected'] == $v);
				$add      = $params['disabled'] ? ($selected) : true;

				if ($add) {
					array_push($html, sprintf('<option %s value="%s">%s</option>', ($selected ? 'selected' : ''), $v, $t));
				}

			}

		}

		array_push($html, '</select>');
		array_push($html, sprintf('
		<script>
			$("#%s").chosen({
				allow_single_deselect : true,
				search_contains       : true,
				no_results_text       : "Nenhum resultado encontrado!"
			});
		</script>
		', $params['id']));

		return implode('', $html);
	}

	private static function toOptions($options, $nullable, $disabled) {

		$novo = [];

		if (!empty($options)) {

			if (!$disabled) {

				if ($nullable !== '') {
					$novo[''] = $nullable;
				}

			}

			foreach ($options as $v => $t) {

				$v  = isset($v) && $v !== '' ? $v : '';
				$t  = isset($t) && $t !== '' ? $t : '';

				$novo[$v] = $t;
			}

		}

		unset($options);
		return $novo;
	}

}
