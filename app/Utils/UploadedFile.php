<?php
namespace App\Utils;

class UploadedFile {

// 	private $errors = [
// 		0 => 'There is no error, the file uploaded with success',
// 		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
// 		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
// 		3 => 'The uploaded file was only partially uploaded',
// 		4 => 'No file was uploaded',
// 		6 => 'Missing a temporary folder',
// 		7 => 'Failed to write file to disk.',
// 		8 => 'A PHP extension stopped the file upload.'
// 	];

	/** @var array $data */
	private $data   = [];

	/** @var string $glue */
	private $glue   = null;

	/** @var array $errors */
	private $errors = [
		0 => 'Não há erros, o upload do arquivo foi feito com sucesso',
		1 => 'O arquivo enviado excedeu a directiva upload_max_filesize do php.ini',
		2 => 'O arquivo enviado excedeu a directiva MAX_FILE_SIZE que foi especificada no formulario HTML',
		3 => 'O arquivo enviado só foi processado parcialmente',
		4 => 'Nenhum arquivo foi enviado',
		6 => 'Diretorio temporario não existe',
		7 => 'Falha ao gravar arquivo no disco.',
		8 => 'Uma extensao PHP parou o upload do aquivo.'
	];

	private $uploaded;

	public function __construct($_files_attrFileName) {

		if (isset($_FILES[$_files_attrFileName])) {

			$this->uploaded = true;
			$this->data     = $_FILES[$_files_attrFileName];
			$this->glue     = strpos($this->getTempFilename(), '\\') !== false ? '\\' : '/';

			$this->data['message'] = $this->getError();
		}
		else {
			$this->uploaded        = false;
			$this->data['message'] = $this->errors[4];
		}

	}

	public function __toString() {
		return sprintf('<pre>%s</pre>', print_r($this->data, true));
	}

	private function get($name) {
		return (isset($this->data[$name]) ? $this->data[$name] : null);
	}

	/** @return int */
	public function getErrorCode() {
		return $this->get('error');
	}

	/** @return string */
	public function getError() {
		return $this->errors[$this->getErrorCode()];
	}

	/** @return string */
	public function getName() {
		return $this->get('name');
	}

	/** @return string */
	public function getTempFilename() {
		return $this->get('tmp_name');
	}

	public function getSize() {
		return $this->get('size');
	}

	/** @return string */
	public function getType() {
		return $this->get('type');
	}

	/** @return boolean */
	public function isPDF() {
		return ($this->getType() == 'application/pdf');
	}

	/** @return boolean */
	public function isTxt() {
		return ($this->getType() == 'text/plain');
	}

	/** @return boolean */
	public function isUploaded() {
		return ($this->uploaded && $this->isValid());
	}

	/** @return boolean */
	public function isValid() {
		return ($this->getErrorCode() == 0);
	}

	/**
	 * @param callable $callback
	 * @return void */
	public function read($callback) {

		if (is_callable($callback)) {

			$handle = fopen($this->getTempFilename(), 'r');
			$index  = 0;

			while (!feof($handle)) {

				$line = trim(fgets($handle));

				if (!empty($line)) {
					call_user_func_array($callback, [$line, $index]);
					$index++;
				}

			}

			fclose($handle);
		}
		else {
			throw new \Exception(sprintf('%s expects parameter 1 to be callable, %s given', __METHOD__, gettype($callback)));
		}

	}

	/** @return boolean */
	public function moveTo($destination, $newName = null) {

		if ($newName) {
			$this->data['name'] = $newName;
		}

		return move_uploaded_file($this->getTempFilename(), implode($this->glue, [$destination, $this->getName()]));
	}

}
