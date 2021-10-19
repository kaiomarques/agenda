<?php
namespace App\Utils;

/**
 * @desc buscar algumas informações importantes do projeto que o LARAVEL desconhece para facilitar a manutenção e evitar codigo sujo
 * */
class ProjectConfig {

	/**
	 * @desc (ex: retorna a letra do disco onde esta rodando a aplicação) ex: F:
	 * @var string $drive*/
	private $drive;

	/**
	 * @desc (ex: retorna a barra usada para montar os diretorios)
	 * @var string $glue*/
	private $glue;

	/**
	 * @desc ambiente da aplicacao (PRD || HML || LOCAL)
	 * @var string $ambiente */
	private $ambiente;

	/**
	 * @desc o caminho da pasta da empresa dentro de storagebravobpo
	 * @var Folder $pastaEmpresa */
	private $pastaEmpresa;

	/**
	 * @desc o caminho da pasta storagebravobpo
	 * @var string $pastaStorage
	 * */
	private $pastaStorage;

	/**
	 * @desc a pasta raiz do projeto independente do ambiente
	 * @var string $pastaRaizProjeto */
	private $pastaRaizProjeto;

	public function __construct() {

		$this->pastaRaizProjeto = explode('\\', public_path()); // C: ou F:
		$this->drive            = array_shift($this->pastaRaizProjeto);

		array_pop($this->pastaRaizProjeto);

		$this->pastaEmpresa     = null;
		$this->glue             = '/';
		$this->pastaRaizProjeto = implode($this->glue, [$this->drive, implode($this->glue, $this->pastaRaizProjeto)]);
		$this->pastaStorage     = implode($this->glue, [$this->drive, 'storagebravobpo']);
		$this->ambiente         = 'LOCAL';

		if ($this->drive == 'F:') {

			$gitBranch = shell_exec('git branch');

			if (strpos($gitBranch, '* master') !== false) {
				$this->ambiente = 'PRD';
			}
			else if (strpos($gitBranch, '* devel') !== false) {
				$this->ambiente = 'HML';
			}

		}

	}

	/** @desc (ex: retorna a letra do disco onde esta rodando a aplicação) ex: F: */
	public function getDrive() {
		return $this->drive;
	}

	/** @desc (ex: retorna a barra usada para montar os diretorios) */
	public function getGlue() {
		return $this->glue;
	}

	/** @desc (ex: retorna F:/wamp64/www/agenda ou C:/wamp/www/bravo/taxcalendar.bravobpo.local) */
	public function getPastaRaizProjeto() {
		return $this->pastaRaizProjeto;
	}

	/** @desc (ex: retorna F:/storagebravobpo) */
	public function getPastaStorage() {
		return $this->pastaStorage;
	}

	/**
	 * @desc diretorio onde os PDFs de storage serão copiados para dentro do projeto (em public)
	 * @param string $cnpj (possui zero a esquerda, por isso deve ser string)
	 * @param string $uf
	 * @param string $ano
	 * @return string */
	public function getPastaContaCorrenteArquivos($cnpj, $uf, $ano) {

		$folder = $this->getPastaEmpresa($cnpj);

		if ($folder == null) {
			throw new \Exception(sprintf('%s ERROR:: pasta da empresa (%s) nao existe.', __METHOD__, $cnpj));
		}

		return implode($this->glue, [$this->getPastaRaizProjeto(), 'public', 'storagebravobpo', $folder->name, 'conta_corrente', $uf, 'ARQUIVOS', $ano]);
	}

	/**
	* @desc procura pela pasta contendo o prefixo de cnpj e retorna-a se encontrada (NULL se não for encontrada)
	* @param string $cnpj ($cnpj_preffix tb funciona) e formatado ou somente numeros
	* @return Folder */
	public function getPastaEmpresa($cnpj) {

		if ($this->pastaEmpresa == null) {

			$cnpj         = str_replace(['-', '.', '/'], '', $cnpj);
			$cnpj_preffix = substr($cnpj, 0, 8);
			$folders      = Folder::readDir($this->getPastaStorage(), function (\DirectoryIterator $f, Folder $params) use ($cnpj_preffix) {
				return ($params->type == 'dir' && strpos($params->name, "_{$cnpj_preffix}") !== false);
			});

			$this->pastaEmpresa = (!empty($folders) ? $folders[0] : null);
		}

		return $this->pastaEmpresa;
	}

	/** @return boolean */
	public function isProducao() {
		return ($this->ambiente == 'PRD');
	}

	/** @return boolean */
	public function isHomologacao() {
		return ($this->ambiente == 'HML');
	}

	/** @return boolean */
	public function isLocal() {
		return ($this->ambiente == 'LOCAL');
	}

}
