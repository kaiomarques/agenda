<?php
namespace App\Utils;

use Illuminate\Support\Facades\DB as DBLaravel;
use stdClass;

class Database extends DBLaravel {

	public static function auto_get() { // VIEW <-- BD

		$num = func_num_args();

		if ($num == 2) {

			$mixed = func_get_arg(0);
			$glue  = func_get_arg(1);

			if (isset($glue) && $glue !== '') {

				$split = explode($glue, $mixed);
				$aux   = [];

				foreach ($split as $val) {
					array_push($aux, self::auto_get($val));
				}

				return implode($glue, $aux);
			}

			return self::auto_get($mixed);
		}
		elseif ($num == 1) {

			$mixed = func_get_arg(0);

			if (isset($mixed) && $mixed !== '') {

				$type = gettype($mixed);

				if ($type == 'double') {
					$mixed = number_format($mixed, 2, ',', '.');
				}
				elseif ($type == 'integer') {
					$mixed = "{$mixed}";
				}
				elseif ($type == 'string') {

					$len = strlen($mixed);

					if ($len == 19 && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/', $mixed)) {
						$split = explode(' ', $mixed);
						$data  = explode('-', $split[0]);
						$hora  = explode(':', $split[1]);
						$mixed = sprintf('%s/%s/%s %s:%s:%s', $data[2], $data[1], $data[0], $hora[0], $hora[1], $hora[2]);
					}
					elseif ($len == 10 && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', $mixed)) {
						$split = explode('-', $mixed);
						$ano   = $split[0];
						$mes   = $split[1];
						$dia   = $split[2];
						$mixed = sprintf('%s/%s/%s', $dia, $mes, $ano);
					}
					else {

						if (is_numeric($mixed)) {

							$aux = str_replace(',', '.', $mixed);

							if (is_numeric($aux)) {

								if (strpos($aux, '.') !== false) {
									$mixed = number_format($aux, 2, ',', '.');
								}
								else {

									if ($len == 6) {

										$mes  = substr($mixed, 0, 2);
										$ano  = intval(substr($mixed, 2, 4));
										$ames = intval($mes);

										if ($ames >= 0 && $ames <= 12) {
											$mixed = sprintf('%s/%s', $mes, $ano);
										}

									}
									elseif ($len == 5) {

										$ames = intval(substr($mixed, 0, 1));
										$mes  = str_pad($ames, 2, '0', STR_PAD_LEFT);
										$ano  = intval(substr($mixed, 1, 4));

										if ($ano >= 1900 && $ano <= 2100) {
											$mixed = sprintf('%s/%s', $mes, $ano);
										}

									}
									elseif ($len == 14 && preg_match('/^[0-9]{14}/', $mixed)) {
										// TODO: validar CNPJ
										$mixed = sprintf('%s.%s.%s/%s-%s', substr($mixed, 0, 2), substr($mixed, 2, 3), substr($mixed, 5, 3), substr($mixed, 8, 4), substr($mixed, 12, 2));
									}

								}

							}

						}
						else {

							if (strpos($mixed, '\"') !== false) {
								$mixed = stripslashes($mixed);
							}

						}

					}

				}

				return $mixed;
			}

		}
		else {
			die(sprintf('The method %s is not applicable for the arguments ()', __METHOD__));
		}

		return null;
	}

	public static function auto_set($mixed) { // VIEW --> BD

		$type = gettype($mixed);

		if ($type == 'array') {

			$array = [];

			foreach ($mixed as $attName => $attValue) {

				if (!is_array($attValue)) {
					$array[$attName] = self::auto_set($attValue);
				}

			}

			return $array;
		}
		else {

			if (isset($mixed) && $mixed !== '') {

				$mixed = trim($mixed);

				if (strpos($mixed, ',') !== false && strpos($mixed, '.') !== false) { // 1.665,99
					$aux = str_replace(',', '.', str_replace('.', '', $mixed));
				}
				else {
					$aux = str_replace(',', '.', $mixed);
				}

				if (is_numeric($aux)) {
					$mixed = $aux;
				}
				else {

					$len = strlen($mixed);

					if (preg_match("/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}/", $mixed)) {

						$split = explode(' ', $mixed);
						$date  = explode('/', $split[0]);
						$day   = $date[0];
						$mon   = $date[1];
						$year  = $date[2];
						$hour  = 0;
						$min   = 0;
						$sec   = 0;

						if (isset($split[1])) {
							$split[1] = explode(':', $split[1]);
							$hour     = intval($split[1][0]);
							$min      = intval($split[1][1]);
							$sec      = intval($split[1][2]);
						}

						if (!($hour == 0 && $min == 0 && $sec == 0)) {
							$mixed = sprintf('%s-%s-%s %s:%s:%s', $year, $mon, $day, str_pad($hour, 2, '0', STR_PAD_LEFT), str_pad($min, 2, '0', STR_PAD_LEFT), str_pad($sec, 2, '0', STR_PAD_LEFT));
						}
						else {
							$mixed = sprintf('%s-%s-%s', $year, $mon, $day);
						}

					}
					else if ($len == 7 && preg_match("/^[0-9]{2}\/[0-9]{4}/", $mixed)) { // periodo
						$mixed = str_replace('/', '', $mixed);
					}
					else {
						$mixed = addslashes($mixed);
					}

				}

			}
			else {
				$mixed = "";
			}

		}

		return $mixed;
	}

	/**
	 * @param string $sql
	 * @param int $page (opcional)
	 * @param int $num_rows (opcional)
	 * @param int $from (opcional)
	 * @return Paginator */
	public static function fetchPages($sql, $page = null, $num_rows = null, $from = null) {

		$page     = isset($page)     ? intval($page)     : 0;
		$num_rows = isset($num_rows) ? intval($num_rows) : self::getRowsPage();

		if ($page === 1 && $num_rows === 1 && $from === 1) {
			$total = 1;
		}
		else {
			$total = self::rows($sql);
		}

		if ($page > 0) {
			$sql   = self::getPagedSQL($sql, $page, $num_rows, $from);
			$pages = ceil(($total / $num_rows));
			$ate   = $page * $num_rows;
			$de    = $from + 1;
		}
		else {
			$pages = 1;
			$ate   = $total;
			$de    = 1;
		}

		if (!($total > 0 && $ate > 0 && $de > 0)) {
			$total = 0;
			$ate   = 0;
			$de    = 0;
		}
		else {
			$ate = $ate <= $total ? $ate : $total;
		}

		$paginator = new Paginator();
		$paginator->setTotal($total);
		$paginator->setFrom($de);
		$paginator->setTo($ate);
		$paginator->setPages($pages);
		$paginator->setPage($page);
		$paginator->setAll(self::fetchModels($sql));

		return $paginator;
	}

	/**
	 * @param string $sql
	 * @param Model $model
	 * @param callable $onGetValue (opcional)
	 * @return CollectionModel */
	public static function fetchModels($sql, $model, $onGetValue = null) {
		return new CollectionModel(self::fetchAll($sql, function ($object, $index) use ($model) {

			$split = explode('\\', get_class($model));
			$pop   = array_pop($split);

			return $model->getModel($pop)->setBy($object);
		}, $onGetValue));
	}

	/**
	 * @desc an alias for DB Laravel select()
	 * @param string $sql
	 * @param callable $onGetTuple (opcional)
	 * @param callable $onGetValue (opcional)
	 * @return stdClass[] */
	public static function fetchAll($sql, $onGetTuple = null, $onGetValue = null) {

		$dataset = DBLaravel::select($sql);

		foreach ($dataset as $index => $object) {

			$dataset[$index] = ($onGetTuple != null && is_callable($onGetTuple) ? call_user_func_array($onGetTuple, [$object, $index]) : $object);

			foreach (get_object_vars($object) as $attr => $value) {
				$dataset[$index]->$attr = self::auto_get($value);
				$dataset[$index]->$attr = ($onGetValue != null && is_callable($onGetValue) ? call_user_func_array($onGetValue, [$dataset[$index]->$attr, $attr]) : $dataset[$index]->$attr);
			}

		}

		return $dataset;
	}

	/**
	 * @param string $sql
	 * @param callable $onGetValue (opcional)
	 * @return stdClass */
	public static function fetchRow($sql, $onGetValue = null) {

		$select = self::fetchAll(self::getPagedSQL($sql, 1, 1, 1), null, $onGetValue);

		if (!empty($select)) {
			return $select[0];
		}

		return null;
	}

	/**
	 * @param string $sql
	 * @param string $fieldValue
	 * @param string|array $fieldText (opcional) - se nao passado, assume $fieldValue
	 * @param string $glue (opcional) - usado se $fieldText for array
	 * @return array */
	public static function fetchPairs($sql, $fieldValue = null, $fieldText = null, $glue = null) {

		$fieldValue = $fieldValue ? $fieldValue : 'id';

		if (is_array($fieldText)) {

			$aux  = [];
			$glue = isset($glue) && $glue !== '' ? $glue : " - ";

			foreach ($fieldText as $fText) {
				array_push($aux, $fText);
				array_push($aux, "'{$glue}'");
			}

			array_pop($aux);

			$fieldText = ('concat(' . implode(', ', $aux) . ')');
		}
		else {
			$glue      = null;
			$fieldText = ($fieldText ? $fieldText : $fieldValue);
		}

		$result = self::fetchAll(sprintf("SELECT %s AS cd, %s AS ds FROM (%s) SQL_PAIRS", $fieldValue, $fieldText, $sql));
		$pairs  = [];

		foreach ($result as $linha) {
			$pairs[$linha->cd] = self::auto_get($linha->ds, $glue);
		}

		return $pairs;
	}

	/**
	 *
	 * @desc retorna uma SQL paginada concatenada com $sql
	 * @param string $sql
	 * @param int $page
	 * @param int $num_rows
	 * @param int $from
	 * @return string */
	public static function getPagedSQL($sql, $page, $num_rows, $from) {

		$page     = $page > 0 ? $page : 1;
		$num_rows = isset($num_rows) ? $num_rows : self::getRowsPage();

		if (!$from) {

			if ($page > 0) {
				$to   = $page * $num_rows;
				$from = $to - $num_rows;
			}
			else {
				$from = 0;
			}

		}
		else {
			$from--; // use starting in 1
		}

		// SELECT * FROM tabela LIMIT 10 OFFSET 10; 11 a 20
		// SELECT * FROM tabela LIMIT 15 OFFSET 60; 61 a 75

		return sprintf("%s LIMIT %s OFFSET %s", $sql, $num_rows, $from);
	}

	/** @return int */
	public static function getRowsPage() {
		return 10;
	}

	/**
	 * @desc retorna a qtde de linhas de uma instrução SQL
	 * @param string $sql
	 * @return int
	 * */
	public static function rows($sql) {
		$row = self::fetchRow("SELECT count(*) quantos FROM ({$sql}) sqlQuantos");
		return ($row !== null ? $row->quantos : 0);
	}

}
