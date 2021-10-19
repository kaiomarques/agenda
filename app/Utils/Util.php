<?php
namespace App\Utils;

use Exception;

class Util {

	/**
	 * @desc cria um novo array com as chaves iguais aos valores (util quando não o id e vai gravar a descrição no bd)
	 * @param array $values
	 * @return array */
	public static function addKeys($values) {

		$type = gettype($values);

		if ($type != 'array') {
			throw new Exception(sprintf('The method %s expects parameter 1 to be array, %s given', __METHOD__, $type));
		}

		$novo = [];

		foreach ($values as $value) {
			$novo[$value] = $value;
		}

		return $novo;
	}

}
