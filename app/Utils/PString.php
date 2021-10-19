<?php
namespace App\Utils;

class PString {

	protected $val;

	public function __construct($string = '') {

		$type = gettype($string);

		if ($type == 'object' && in_array(get_class($string), ['App\Utils\PString', 'App\Utils\JString'])) {
			$this->val = "{$string}";
			unset($string);
		}
		else if ($type == 'string' || $type == 'integer' || $type == 'double') {
			$this->val = "{$string}";
		}
		else {
			die(sprintf('%s expects argument 1 to be string, %s given', __METHOD__, $type));
		}

	}

	public function __toString() {
		return "{$this->val}";
	}

	/** bool contains([$needle1, $needle2, ...]); */
	public function contains() {

		$num  = func_num_args();
		$args = func_get_args();

		if ($num > 0) {

			if ($num > 1) {

				$ok = false;

				foreach ($args as $needle) {

					if ($this->contains($needle)) {
						$ok = true;
						break;
					}

				}

				return $ok;
			}
			else {
				return ($args[0] && strpos($this->val, $args[0]) !== false);
			}

		}
		else {
			die(__METHOD__ . '::error::no argument has passed');
		}

		return false;
	}

	/** bool endsWith([$needle1, $needle2, ...]); */
	public function endsWith() {

		$num  = func_num_args();
		$args = func_get_args();

		if ($num > 0) {

			if ($num > 1) {

				$ok = false;

				foreach ($args as $needle) {

					if ($this->endsWith($needle)) {
						$ok = true;
						break;
					}

				}

				return $ok;
			}
			else {
				return ($args[0] && substr($this->val, (strlen($args[0]) * -1)) == $args[0]);
			}

		}
		else {
			die(__METHOD__ . '::error::no argument has passed');
		}

		return false;
	}

	/** bool equals([$needle1, $needle2, ...]); */
	public function equals() {

		$num  = func_num_args();
		$args = func_get_args();

		if ($num > 0) {

			if ($num > 1) {

				$ok = false;

				foreach ($args as $needle) {

					if ($this->equals($needle)) {
						$ok = true;
						break;
					}

				}

				return $ok;
			}
			else {
				return ($this->val === $args[0]);
			}

		}
		else {
			die(__METHOD__ . '::error::no argument has passed');
		}

		return false;
	}

	/** @return int */
	public function indexOf($needle) {
		$pos = strpos($this->val, "{$needle}");
		return ($pos !== false ? $pos : -1);
	}

	/** @return int */
	public function lastIndexOf($needle) {
		$pos = strrpos($this->val, "{$needle}");
		return ($pos !== false ? $pos : -1);
	}

	/** @return int */
	public function length() {
		return strlen("{$this->val}");
	}

	/**
	 * @param string $character_mask (opcional)
	 * @return string */
	public function ltrim($character_mask = null) {

		if (isset($character_mask)) {
			return ltrim($this->val, $character_mask);
		}

		return ltrim($this->val);
	}

	/**
	 * @param int $pad_length
	 * @param string $pad_string
	 * @param int $pad_type
	 * @return string */
	public function pad($pad_length, $pad_string = null, $pad_type = null) {

		$pad_string = isset($pad_string) ? $pad_string : '';
		$pad_type   = isset($pad_type) ? $pad_type : STR_PAD_RIGHT;

		return str_pad($this->val, $pad_length, $pad_string, $pad_type);
	}

	/**
	 * @param int $multiplier
	 * @return string */
	public function repeat($multiplier) {
		return str_repeat($this->val, $multiplier);
	}

	/**
	 * @param string[]|string $search
	 * @param string[]|string $replace
	 * @return string */
	public function replaceAll($search, $replace) {
		return str_replace($search, $replace, $this->val);
	}

	/**
	 * @param string $character_mask (opcional)
	 * @return string */
	public function rtrim($character_mask = null) {

		if (isset($character_mask)) {
			return rtrim($this->val, $character_mask);
		}

		return rtrim($this->val);
	}

	/**
	 * @param string $delimiter
	 * @return string[] */
	public function split($delimiter) {
		return explode($delimiter, "{$this->val}");
	}

	/**
	 * bool startsWith([$needle1, $needle2, ...]);
	 * @return boolean */
	public function startsWith() {

		$num  = func_num_args();
		$args = func_get_args();

		if ($num > 0) {

			if ($num > 1) {

				$ok = false;

				foreach ($args as $needle) {

					if ($this->startsWith($needle)) {
						$ok = true;
						break;
					}

				}

				return $ok;
			}
			else {
				return (substr($this->val, 0, strlen($args[0])) == $args[0]);
			}

		}
		else {
			die(__METHOD__ . '::error::no argument has passed');
		}

		return false;
	}

	/**
	 * @param int $start
	 * @param int $limit (opcional)
	 * @return string */
	public function substr($start, $limit = null) {

		if (isset($limit)) {
			return substr($this->val, $start, $limit);
		}

		return substr($this->val, $start);
	}

	/** @return string */
	public function toLowerCase() {
		return strtolower($this->val);
	}

	/** @return string */
	public function toUpperCase() {
		return strtoupper($this->val);
	}

	/**
	 * @param string $character_mask (opcional)
	 * @return string */
	public function trim($character_mask = null) {

		if (isset($character_mask)) {
			return trim($this->val, $character_mask);
		}

		return trim($this->val);
	}

	/**
	 * @param string $str
	 * @return string */
	public function ucfirst($str) {
		return ucfirst($str);
	}

	/**
	 * @param string $str
	 * @return string */
	public function ucwords($str) {
		return ucwords($str);
	}

	// ============================================================================================

	/** @return PString */
	public function toString() {
		return new PString($this->val);
	}

	// ====================================================================================================================

	/**
	 * @example format(string ...)
	 * @return string */
	public static function format() {

		$args   = func_get_args();
		$format = array_shift($args) . '';

		if (!empty($format)) {

			foreach ($args as $index => $val) {
				$format = str_replace(('{' . $index . '}'), "{$val}", $format);
			}

		}

		return $format;
	}

	/**
	 * @desc checa se $mixed eh uma string de objeto serializado
	 * @param mixed $mixed
	 * @return boolean */
	public static function isSerialized($mixed) {

		$type = gettype($mixed);

		if ($type == 'string') {

			$aux = new PString($mixed);
			$i   = 2;

			if ($aux->startsWith('O:') && $aux->endsWith('}')) {

				do {
					$i++;
				}
				while (is_numeric($aux->substr($i, 1)));

				if ($aux->substr($i, 2) == ':"') {
					return true;
				}

			}

		}

		return false;
	}

	/**
	 * @param mixed $numeric
	 * @return double|int */
	public static function toNumber($numeric) {
		return (strpos($numeric, '.') !== false ? floatval($numeric) : intval($numeric));
	}

}
