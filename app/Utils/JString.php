<?php
namespace App\Utils;

/** @property PString $val */
class JString extends PString {

	public function __construct($string = '') {

		$type = gettype($string);

		if ($type == 'object' && in_array(get_class($string), ['App\Utils\PString', 'App\Utils\JString'])) {
			$this->val = new PString("{$string}");
			unset($string);
		}
		else if ($type == 'string' || $type == 'integer' || $type == 'double') {
			$this->val = new PString("{$string}");
		}
		else {
			die(sprintf('%s expects argument 1 to be string, %s given', __METHOD__, $type));
		}

	}

	public function __toString() {
		return $this->val->__toString();
	}

	/**
	 * @param string $character_mask (opcional)
	 * @return JString */
	public function ltrim($character_mask = null) {
		return $this->toString(parent::ltrim($character_mask));
	}

	/**
	 * @param int $pad_length
	 * @param string $pad_string
	 * @param int $pad_type
	 * @return JString */
	public function pad($pad_length, $pad_string = null, $pad_type = null) {
		return $this->toString(parent::pad($pad_length, $pad_string, $pad_type));
	}

	/**
	 * @param int $multiplier
	 * @return JString */
	public function repeat($multiplier) {
		return $this->toString(parent::repeat($multiplier));
	}

	/**
	 * @param string[]|string $search
	 * @param string[]|string $replace
	 * @return JString */
	public function replaceAll($search, $replace) {
		return $this->toString(parent::replaceAll($search, $replace));
	}

	/**
	 * @param string $character_mask (opcional)
	 * @return JString */
	public function rtrim($character_mask = null) {
		return $this->toString(parent::rtrim($character_mask));
	}

	/**
	 * @param string $delimiter
	 * @return JString[] */
	public function split($delimiter) {
		return $this->toString(parent::split($delimiter));
	}

	/**
	 * @param int $start
	 * @param int $limit (opcional)
	 * @return JString */
	public function substr($start, $limit = null) {
		return $this->toString(parent::substr($start, $limit));
	}

	/** @return JString */
	public function toLowerCase() {
		return $this->toString(parent::toLowerCase());
	}

	/** @return JString */
	public function toUpperCase() {
		return $this->toString(parent::toUpperCase());
	}

	/**
	 * @param string $character_mask (opcional)
	 * @return JString */
	public function trim($character_mask = null) {
		return $this->toString(parent::trim($character_mask));
	}

	/**
	 * @param string $str
	 * @return JString */
	public function ucfirst($str) {
		return $this->toString(parent::ucfirst($str));
	}

	/**
	 * @param string $str
	 * @return JString */
	public function ucwords($str) {
		return $this->toString(parent::ucwords($str));
	}

	// ============================================================================================

	/**
	 * @param mixed $value (opcional)
	 * @return JString */
	public function toString() {

		$num   = func_num_args();
		$value = $this->val;
		$conv  = true;

		if ($num >= 1) {

			$value = func_get_arg(0);
			$type  = gettype($value);

			if ($type == 'array') {

				foreach ($value as $i => $v) {
					$value[$i] = $this->toString($v);
				}

				$conv = false;
			}
			else {

				if ($type == 'object' && in_array(get_class($value), ['App\Utils\PString', 'App\Utils\JString'])) {
					$conv = true;
				}
				else if ($type == 'string' || $type == 'integer' || $type == 'double') {
					$conv = true;
				}
				else {
					$conv = false;
				}

			}

		}

		if (!$conv) {
			return $value;
		}

		return new JString($value);
	}

}
