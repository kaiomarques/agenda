<?php
namespace App\Utils;

class Estados {

	/**
	 * @desc retorna uma array com os estados (tanto na chave quanto no valor do array)
	 * @return array */
	public static function getAll() {

		$aux     = ['RS', 'SC', 'PR', 'SP', 'MG', 'ES', 'RJ', 'MS', 'MT', 'GO', 'DF', 'BA', 'SE', 'AL', 'PE', 'PB', 'RN', 'CE', 'PI', 'MA', 'TO', 'PA', 'RR', 'RO', 'AM', 'AC', 'AP'];
		$estados = [];

		foreach ($aux as $uf) {
			$estados[$uf] = $uf;
		}

		asort($estados);

		return $estados;
	}

	/**
	 * @param string $uf
	 * @return boolean */
	public static function exists($uf) {

		$all = self::getAll();

		if (!empty($all) && isset($all[$uf])) {
			return true;
		}

		return false;
	}

}
