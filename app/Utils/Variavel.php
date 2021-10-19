<?php
namespace App\Utils;

use Exception;
use App\Utils\Models\ContaCorrente;

/**
 * @desc uma classe para validação de entrada de dados
 * */
class Variavel {

	/** @var string $typing */
	private $typing;

	/** @var string $value */
	private $value;

	/**
	 * @desc a mensagem de erro (caso haja algum erro na validação)
	 * @var string|array $error */
	private $error;

	/** @var array $params */
	private $params;

	public function __construct($typing, $value, $params = []) {

		$this->typing = $typing;
		$this->value  = trim((is_array($value) ? $this->getNeedle($value[1], $value[0]) : $value), ' ');
		$this->error  = null;
		$this->params = $params;

		$this->validate();
	}

	/** @return string */
	public function __toString() {
		return ("{$this->value}");
	}

	/**
	 * @param string $glue (opcional e somente se $this->error for array)
	 * @return string */
	public function getError($glue = null) {
		return (is_array($this->error) ? implode($glue, $this->error) : $this->error);
	}

	/** @return string */
	public function getAno() {

		if ($this->typing != 'PERIODO') {
			die(sprintf('Exception in %s:: O valor deve ter o tipo PERIODO, %s given', __METHOD__, $this->typing));
		}

		$aux = explode('/', $this->value);

		return $aux[1];
	}

	/** @return string */
	public function toDate() {

		if ($this->typing != 'PERIODO') {
			die(sprintf('Exception in %s:: O valor deve ter o tipo PERIODO, %s given', __METHOD__, $this->typing));
		}

		return sprintf('01/%s', $this->value);
	}

	/** @return boolean */
	public function hasError() {

		if (isset($this->error)) {
			return (is_array($this->error) ? (!empty($this->error)) : ($this->error !== ''));
		}

		return false;
	}

	/** @return boolean */
	public function isFalse() {
		return ($this->isValid() == false);
	}

	/** @return boolean */
	public function isTrue() {
		return $this->isValid();
	}

	/** @return boolean */
	public function isValid() {
		return ($this->hasError() == false);
	}

	/**
	 * @param string|array $error
	 * @return Variavel */
	public function setError($error) {

		if ($error === null || $error === '' || (is_array($error) && empty($error))) {
			throw new Exception(sprintf('The method %s(string|array) is not applicable for the arguments()', __METHOD__));
		}

		$this->error = $error;

		return $this;
	}

	/** @return double */
	public function toFloat() {

		if ($this->typing != 'MOEDA') {
			die(sprintf('%s expects value to be MOEDA, %s given', __METHOD__, $this->typing));
		}

		return floatval(str_replace(['.', ','], ['', '.'], $this->value));
	}

	/** @return int */
	public function toInt() {

		$intTypes = ['ID', 'INTEGER'];

		if (!in_array($this->typing, $intTypes)) {
			die(sprintf('Exception in %s:: O valor deve ter o tipo %s, %s given', __METHOD__, implode(' ou ', $intTypes), $this->typing));
		}

		return intval($this->value);
	}

	/** @return string */
	public function value() {
		return $this->value;
	}

	/** @return string[] */
	public function validFunctions() {
		return [
			'CELULAR'            => 'getCelular',
			'CEP'                => 'getCEP',
			'CPF'                => 'getCPF',
			'CNPJ'               => 'getCNPJ',
			'DATE'               => 'getDate',
			'ID'                 => 'getId',
			'INTEGER'            => 'getInt',
			'INSCRICAO_ESTADUAL' => 'getInscricaoEstadual',
			'MOEDA'              => 'getMoeda',
			'PERIODO'            => 'getPeriodo',
			'TELEFONE'           => 'getTelefone',
			'TIME'               => 'getTime',
			'STRING'             => 'getString',
			'UF'                 => 'getUF',
			'AMBITO'             => 'getAmbito',
			'RESPONSAVEL'        => 'getResponsavel',
			'DOCUMENTO'          => 'getDocumento'
		];
	}

	/** @return string[] */
	public function validTypes() {
		return array_keys($this->validFunctions());
	}

	private function validate() {

		$this->error = null;

		if (!in_array($this->typing, $this->validTypes())) {
			die(sprintf('The method %s::__construct expects parameter 1 to be %s, %s given', get_class($this), implode('|', $this->validTypes()), $this->typing));
		}

		if (!is_array($this->params)) {
			die(sprintf('The method %s::__construct expects parameter 3 to be array, %s given', get_class($this), gettype($this->params)));
		}

		$params = $this->params;
		$value  = $this->value;
		$len    = $this->length();
		$error  = true;

		if ($this->typing == 'CELULAR') {

			if ($len == 15) {

				if (substr($value, 0, 1) == '(' && substr($value, 3, 1) == ')') {

					if (substr($value, 4, 1) == ' ' && substr($value, 5, 1) == '9') {

						if (preg_match('/^[0-9]{4}-[0-9]{4}/', substr($value, 6, 9))) {
							$error = false;
						}

					}

				}

			}

			if ($error) {
				$this->setError(sprintf('Celular informado (%s) inválido. Favor, digite assim: (11) 91234-5678', $value));
			}

		}
		else if ($this->typing == 'CEP') {

			if ($len == 9 && preg_match('/^[0-9]{5}-[0-9]{3}/', $value)) {
				$error = false;
			}

			if ($error) {
				$this->setError(sprintf('CEP informado (%s) inválido. Favor, digite assim: 12345-000', $value));
			}

		}
		else if ($this->typing == 'CPF') {

			if ($len == 14 && preg_match('/^[0-9]{3}.[0-9]{3}.[0-9]{3}-[0-9]{2}/', $value)) {
				$error = false;
			}

			if ($error) {
				$this->setError(sprintf('CPF informado (%s) inválido. Favor, digite assim: 609.899.440-01', $value));
			}

		}
		else if ($this->typing == 'CNPJ') {

			if ($len == 18 && preg_match('/^[0-9]{2}.[0-9]{3}.[0-9]{3}\/[0-9]{4}-[0-9]{2}/', $value)) {
				$error = false;
			}

			if ($error) {
				$this->setError(sprintf('CNPJ informado (%s) inválido. Favor, digite assim: 60.989.944/0001-65', $value));
			}

		}
		else if ($this->typing == 'DATE') {

			if ($len == 10 && preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}/', $value)) {

				$split = explode('/', $value);
				$day   = intval($split[0]);
				$month = intval($split[1]);
				$year  = $split[2];

				if (checkdate($month, $day, $year)) {
					$error = false;
				}

			}

			if ($error) {
				$this->setError(sprintf('Data informada (%s) inválida. Favor, digite assim: DD/MM/AAAA', $value));
			}

		}
		else if ($this->typing == 'ID') {

			$msg = null;

			if ($value === '') {
				$msg = 'Id não informado';
			}
			else {

				if (!is_numeric($value)) {
					$msg = sprintf('Id informado (%s) não é numérico', $value);
				}
				else {

					if ($value <= 0) {
						$msg = sprintf('Id informado (%s) não é maior que zero', $value);
					}
					else {

						if (strpos($value, '.') !== false) {
							$msg = sprintf('Id informado (%s) não é inteiro', $value);
						}
						else {

							if (!empty($params)) {

								$table = $params[0];
								$pk    = $params[1];
								$linha = Database::fetchRow(PString::format("SELECT {1} FROM {0} WHERE {1} = '{2}'", $table, $pk, $value));

								if ($linha === null) {
									$msg = sprintf('Não existe registro na tabela (%s) com o id (%s)', $table, $value);
								}

							}

						}

					}

				}

			}

			if ($msg !== null) {
				$this->setError($msg);
			}

		}
		else if ($this->typing == 'INTEGER') {

			$msg = null;

			if ($value === '') {
				$msg = 'Número não informado';
			}
			else {

				if (!is_numeric($value)) {
					$msg = sprintf('Número informado (%s) não é numérico', $value);
				}
				else {

					if ($value < 0) {
						$msg = sprintf('Número informado (%s) é negativo', $value);
					}
					else {

						if (strpos($value, '.') !== false) {
							$msg = sprintf('Número informado (%s) não é inteiro', $value);
						}
						else {

							if (!empty($params)) {

								$min = $params['min'];
								$max = $params['max'];
								$val = intval($value);

								if (!($val >= $min && $len <= $max)) {
									$msg = sprintf('Número informado (%s) deve ser entre %s e %s caracteres (inclusive)', $value, $min, $max);
								}

							}

						}

					}

				}

			}

			if ($msg !== null) {
				$this->setError($msg);
			}

		}
		else if ($this->typing == 'INSCRICAO_ESTADUAL') {

			if ($len == 15 && preg_match('/^[0-9]{3}.[0-9]{3}.[0-9]{3}.[0-9]{3}/', $value)) {
				$error = false;
			}

			if ($error) {
				$this->setError(sprintf('Inscrição Estadual informada (%s) inválida. Favor, digite assim: 111.111.111.111', $value));
			}

		}
		else if ($this->typing == 'MOEDA') { // 1.665,98 ou 0,50 ou 1,35

			$mask  = [
				16 => '/^[0-9]{1}.[0-9]{3}.[0-9]{3}.[0-9]{3},[0-9]{2}/',
				14 => '/^[0-9]{3}.[0-9]{3}.[0-9]{3},[0-9]{2}/',
				13 => '/^[0-9]{2}.[0-9]{3}.[0-9]{3},[0-9]{2}/',
				12 => '/^[0-9]{1}.[0-9]{3}.[0-9]{3},[0-9]{2}/',
				10 => '/^[0-9]{3}.[0-9]{3},[0-9]{2}/',
				9  => '/^[0-9]{2}.[0-9]{3},[0-9]{2}/',
				8  => '/^[0-9]{1}.[0-9]{3},[0-9]{2}/',
				6  => '/^[0-9]{3},[0-9]{2}/',
				5  => '/^[0-9]{2},[0-9]{2}/',
				4  => '/^[0-9]{1},[0-9]{2}/'
			];

			if (isset($mask[$len]) && preg_match($mask[$len], $value)) {
				$error = false;
			}

			if (!$error) {

				if (!empty($params)) {

					if (isset($params['checaValorZero']) && $params['checaValorZero'] == true) {

						if($value == '0,00') {
							$this->setError(sprintf('Valor (%s) não pode ser zero.', $value));
						}

					}

				}

			}
			else {
				$this->setError(sprintf('Valor informado (%s) inválido. Favor, digite assim: 1.665,98 ou 14,50', $value));
			}

		}
		else if ($this->typing == 'PERIODO') {

			if ($len == 7 && preg_match('/^[0-9]{2}\/[0-9]{4}/', $value)) {

				$split = explode('/', $value);
				$day   = 1;
				$month = intval($split[0]);
				$year  = $split[1];

				if (checkdate($month, $day, $year)) {
					$error = false;
				}

			}

			if ($error) {
				$this->setError(sprintf('Periodo informado (%s) inválido. Favor, digite assim: 01/2021', $value));
			}

		}
		else if ($this->typing == 'TELEFONE') {

			if ($len == 14) {

				if (substr($value, 0, 1) == '(' && substr($value, 3, 1) == ')') {

					if (substr($value, 4, 1) == ' ') {

						if (preg_match('/^[0-9]{4}-[0-9]{4}/', substr($value, 5, 9))) {
							$error = false;
						}

					}

				}

			}

			if ($error) {
				$this->setError(sprintf('Telefone informado (%s) inválido. Favor, digite assim: (11) 1234-5678', $value));
			}

		}
		else if ($this->typing == 'TIME') {

			if (($len == 5 || $len == 8)) {

				$match = [
					8 => '/^[0-9]{2}:[0-9]{2}:[0-9]{2}/',
					5 => '/^[0-9]{2}:[0-9]{2}/'
				];

				if (preg_match($match[$len], $value)) {

					$split   = explode(':', $value);
					$hora    = intval($split[0]);
					$minuto  = intval($split[1]);
					$segundo = ($len == 8 ? intval($split[2]) : 0);

					if (($hora >= 0 && $hora <= 23) && ($minuto >= 0 && $minuto <= 59) && ($segundo >= 0 && $segundo <= 59)) {
						$error = false;
					}

				}

			}

			if ($error) {
				$this->setError(sprintf('Hora informada (%s) inválida. Favor, digite assim: (hh:mm ou hh:mm:ss)', $value));
			}

		}
		else if ($this->typing == 'STRING') {

			if (!empty($params)) {

				if (isset($params['callback']) && is_callable($params['callback'])) {

					$error = call_user_func_array($params['callback'], [$value, $len]);

					if ($error !== null && !empty($error)) {
						$this->setError($error);
					}

				}
				else {

					$min = $params['min'];
					$max = $params['max'];

					if ($len >= $min && $len <= $max) {
						$error = false;
					}

					if ($error) {
						$this->setError(sprintf('Você digitou %s caracteres. Favor, digite entre %s e %s caracteres.', $len, $min, $max));
					}

				}

			}
			else {

				if ($len == 0) {
					$this->setError(sprintf('Você não digitou nada', $value));
				}
				else {
					$error = false;
				}

			}

			if (!$error) {

				if (!mb_check_encoding($value, 'UTF-8')) {

					try {
						$this->value = iconv('ISO-8859-1', 'UTF-8', $value);
					}
					catch (Exception $e) {
						$this->setError(sprintf('Ocorreu um erro ao converter de ISO-8859-1 para UTF-8 (%s)', $value));
					}

				}

			}

		}
		else if ($this->typing == 'UF') {

			if (Estados::exists($value)) {
				$error = false;
			}

			if ($error) {
				$this->setError(sprintf('UF (%s) inválido', $value));
			}

		}
		else if ($this->typing == 'AMBITO') {

			if (ContaCorrente::getAmbitos($value) !== null) {
				$error = false;
			}

			if ($error) {
				$this->setError(sprintf('Ambito (%s) inválido. Válidos são: (%s)', $value, implode('|', ContaCorrente::getAmbitos())));
			}

		}
		else if ($this->typing == 'DOCUMENTO') {

			if (ContaCorrente::getDocumentos($value) !== null) {
				$error = false;
			}

			if ($error) {
				$this->setError(sprintf('Documento (%s) inválido. Válidos são: (%s)', $value, implode('|', ContaCorrente::getDocumentos())));
			}

		}
		else if ($this->typing == 'RESPONSAVEL') {

			$responsaveis = Database::fetchPairs("SELECT id, descricao FROM respfinanceiros WHERE descricao IN ('Cliente', 'Bravo')", 'id', 'descricao');

			if (is_numeric($value)) { // checar id vindo de um combobox

				if (!empty($responsaveis) && in_array($value, array_keys($responsaveis))) {
					$error = false;
				}
				else {
					$this->setError(sprintf('Responsavel (%s) não está cadastrado no banco de dados.', $value));
				}

			}
			else { // checar texto vindo do excel

				if (!empty($responsaveis) && in_array($value, $responsaveis)) {

					$linha = Database::fetchRow("SELECT id FROM respfinanceiros WHERE descricao = '{$value}'");

					if ($linha != null) {
						$error = false;
						$this->value = $linha->id;
					}
					else {
						$this->setError(sprintf('Responsavel (%s) não está cadastrado no banco de dados.', $value));
					}

				}
				else {
					$this->setError(sprintf('Responsavel (%s) inválido. Deve ser: (%s)', $value, implode('|', $responsaveis)));
				}

			}

		}

	}

	// ============================================================================================

	/** @return string */
	public function clean() {
		return str_replace(['/', '-', '.'], '', $this->value);
	}

	/** @return int */
	public function length() {
		return strlen($this->value);
	}

	/**
	 * @param string $needle
	 * @param array|object $haystack
	 * @param callable $callback (opcional)
	 * @return string */
	public function getNeedle($needle, $haystack, $callback = null) {

		if (isset($needle) && $needle !== '') {

			$htype    = gettype($haystack);
			$haystack = $htype == 'object' ? get_object_vars($haystack) : ($htype == 'array' ? $haystack : null);

			if ($haystack != null) {

				$value = (isset($haystack[$needle]) && $haystack[$needle] !== '' ? $haystack[$needle] : null);

				if ($callback && is_callable($callback)) {
					$value = call_user_func_array($callback, [$value, $needle]);
				}

				return "{$value}";
			}
			else {
				die(sprintf('The method %s expects $haystack to be array|object, %s given', __METHOD__, $htype));
			}

		}
		else {
			die(sprintf('The method %s expects $needle have a value, empty given', __METHOD__));
		}

		return null;
	}

	/** @return Variavel */
	private function setValue($value) {
		$this->value = $value;
		return $this;
	}

	// ============================================================================================

	/** @return Variavel */
	public static function getCelular($value) {
		return new Variavel('CELULAR', $value);
	}

	/** @return Variavel */
	public static function getCEP($value) {
		return new Variavel('CEP', $value);
	}

	/** @return Variavel */
	public static function getCPF($value) {
		return new Variavel('CPF', $value);
	}

	/** @return Variavel */
	public static function getCNPJ($value) {
		return new Variavel('CNPJ', $value);
	}

	/** @return Variavel */
	public static function getDate($value) {
		return new Variavel('DATE', $value);
	}

	/**
	 * @param string $value (opcional caso queira personalizar uma mensagem de erro)
	 * @param string $tablename (opcional)
	 * @param string $pkname (opcional)
	 * @return Variavel */
	public static function getId() {

		$num = func_num_args();

		if ($num > 0) {

			$value = func_get_arg(0);

			if ($num >= 3) {
				return new Variavel('ID', $value, [func_get_arg(1), func_get_arg(2)]);
			}
			else if ($num == 2) {
				return new Variavel('ID', $value, [func_get_arg(1), 'id']);
			}
			else if ($num == 1) {
				return new Variavel('ID', $value);
			}

		}

		return new Variavel('ID', null);
	}

	/** @return Variavel */
	public static function getInt($value, $max = 99, $min = 1) {

		$num = func_num_args();

		if ($num >= 3) {
			return new Variavel('INTEGER', $value, ['max' => $max, 'min' => $min]);
		}
		else if ($num == 1) {
			return new Variavel('INTEGER', $value);
		}
		else {
			throw new Exception(sprintf('The method %s is not applicable for the arguments()', __METHOD__));
		}

	}

	/** @return Variavel */
	public static function getInscricaoEstadual($value) {
		return new Variavel('INSCRICAO_ESTADUAL', $value);
	}

	/** @return Variavel */
	public static function getMoeda($value, $checaValorZero = false) {
		return new Variavel('MOEDA', $value, ['checaValorZero' => $checaValorZero]);
	}

	/** @return Variavel */
	public static function getPeriodo($value) {
		return new Variavel('PERIODO', $value);
	}

	/** @return Variavel */
	public static function getTelefone($value) {
		return new Variavel('TELEFONE', $value);
	}

	/** @return Variavel */
	public static function getTime($value) {
		return new Variavel('TIME', $value);
	}

	/**
	 * @example getString(string|array $value, int $max, int $min)
	 * @example getString(string|array $value, callable $callback)
	 * @example getString(string|array $value)
	 * @return Variavel */
	public static function getString($value, $max = 50, $min = 1) {

		$num = func_num_args();

		if ($num >= 3) {
			return new Variavel('STRING', $value, ['max' => $max, 'min' => $min]);
		}
		else if ($num == 2) {
			return new Variavel('STRING', $value, ['callback' => func_get_arg(1)]);
		}
		else if ($num == 1) {
			return new Variavel('STRING', $value);
		}
		else {
			throw new Exception(sprintf('The method %s is not applicable for the arguments()', __METHOD__));
		}

	}

	/** @return Variavel */
	public static function getUF($value) {
		return new Variavel('UF', $value);
	}

	/** @return Variavel */
	public static function getAmbito($value) {
		return new Variavel('AMBITO', $value);
	}

	/** @return Variavel */
	public static function getResponsavel($value) {
		return new Variavel('RESPONSAVEL', $value);
	}

	/** @return Variavel */
	public static function getDocumento($value) {
		return new Variavel('DOCUMENTO', $value);
	}

}
