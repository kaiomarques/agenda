<?php
namespace App\Services;

use App\Console\Commands\IntegracaoContaCorrenteSefaz;
use App\Utils\Database;
use App\Utils\Folder;
use App\Utils\JString;
use stdClass;
use App\Utils\ProjectConfig;
use App\Utils\Variavel;
use App\Utils\Models\ContaCorrente;

class IntegracaoContaCorrenteSefazService {

	private $pastaStorage       = null;
	private $pastaProjetoPublic = null;

	/** @var IntegracaoContaCorrenteSefaz $command */
	private $command;

	/** @var ProjectConfig $projectConfig */
	private $projectConfig;

	function __construct() {
		$this->projectConfig      = new ProjectConfig();
		$this->pastaStorage       = $this->projectConfig->getPastaStorage();
		$this->pastaProjetoPublic = implode($this->projectConfig->getGlue(), [$this->projectConfig->getPastaRaizProjeto(), 'public']);
	}

	public function getPastaProjetoPublic() {
		return $this->pastaProjetoPublic;
	}

	/**
	 * @param IntegracaoContaCorrenteSefaz $command
	 * @return void */
	public function setCommand($command) {
		$this->command = $command;
	}

	public function auto_get($value) {

		$value = utf8_encode((isset($value) ? trim($value) : ''));

		if (strpos($value, '"') !== false) {
			$value = str_replace('"', '', $value);
		}

		if ($value == ' ') {
			$value = '';
		}

		$len = strlen($value);

		if ($len == 9 && preg_match('/^[0-9]{2}\/[0-9]{4}.[0-9]{1}/', $value)) { // bug no python
			$aux   = explode('/', $value);
			$mes   = array_shift($aux);
			$ano   = intval(array_shift($aux));
			$value = implode('', [$mes, $ano]);
		}
		else if ($len == 9 && preg_match('/^[0-9]{1}\/[0-9]{2}\/[0-9]{4}/', $value)) {
			$aux   = explode('/', $value);
			$dia   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
			$mes   = array_shift($aux);
			$ano   = array_shift($aux);
			$value = implode('/', [$dia, $mes, $ano]);
		}
		else if ($len == 9 && preg_match('/^[0-9]{2}\/[0-9]{1}\/[0-9]{4}/', $value)) {
			$aux   = explode('/', $value);
			$dia   = array_shift($aux);
			$mes   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
			$ano   = array_shift($aux);
			$value = implode('/', [$dia, $mes, $ano]);
		}
		else if ($len == 8 && preg_match('/^[0-9]{1}\/[0-9]{1}\/[0-9]{4}/', $value)) {
			$aux   = explode('/', $value);
			$dia   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
			$mes   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
			$ano   = array_shift($aux);
			$value = implode('/', [$dia, $mes, $ano]);
		}
		else if ($len == 7 && preg_match('/^[0-9]{2}\/[0-9]{4}/', $value)) {
			$value = str_replace('/', '', $value);
		}
		else if ($len == 6 && preg_match('/^[0-9]{1}\/[0-9]{4}/', $value)) {
			$aux   = explode('/', $value);
			$mes   = str_pad(array_shift($aux), 2, '0', STR_PAD_LEFT);
			$ano   = array_shift($aux);
			$value = implode('', [$mes, $ano]);
		}
		else {

			if (strpos($value, ',') !== false && strpos($value, '.') !== false) { // 1.665,99
				$aux = str_replace(',', '.', str_replace('.', '', $value));
			}
			else {
				$aux = str_replace(',', '.', $value);
			}

			if (is_numeric($aux)) {

				if (strpos($aux, '.') !== false) { // float
					$value = floatval($aux);
				}
				else {

					if (strlen($aux) >= 2 && substr($aux, 0, 1) == '0') { // começa com zero a esquerda
						$value = $aux; // string (cnpj, cpf)
					}
					else {
						$value = intval($aux);
					}

				}

			}

		}

		return $value;
	}

	/**
	 * @param string $filename
	 * @return ContaCorrente[] */
	public function txt2object($filename) {

		$handle =  fopen($filename, 'r');
		$array   = [];
		$columns = ['periodo', 'documento', 'data', 'banco', 'identificador', 'debito', 'valor_principal', 'lanc_esp', 'valor_debito', 'observacao'];

		while (!feof($handle)) {

			$line = trim(fgets($handle));

			if (!empty($line)) {

				$split = explode("\t", $line);
				$tuple = new ContaCorrente();

				foreach ($columns as $index => $columnName) {

					if ($columnName == 'debito' || $columnName == 'valor_principal' || $columnName == 'valor_debito') {
						$defValue = 0;
					}
					else {
						$defValue = '';
					}

					if ($tuple->hasKey($columnName)) {
						$tuple->__set($columnName, ($this->auto_get(isset($split[$index]) ? $split[$index] : $defValue)));
					}

				}

				array_push($array, $tuple);
			}

		}

		fclose($handle);

		return $array;
	}

	public function doProcessa($cnpj_preffix, $uf) {

		$command = $this->command;
		$service = $this;

		/** @var IntegracaoContaCorrenteSefazService $service */

		if (file_exists($this->pastaStorage)) {

			$folder = $this->projectConfig->getPastaEmpresa($cnpj_preffix);

			if ($folder !== null) {

				$pdfFileDest = sprintf($service->getPastaProjetoPublic() . "/storagebravobpo/%s/conta_corrente/%s/ARQUIVOS", $folder->name, $uf);

				// TODO: criar Pattern para criar subpastas implicitamente (metodo recebe um parametro informando a partir de qual subpasta ira começar a verificar e criar)

				if (!file_exists($pdfFileDest)) {
					$command->info(sprintf('Caminho (%s) não existe.', $pdfFileDest));
					return 0;
				}

				$folderFiles = implode($this->projectConfig->getGlue(), [$folder->fullpath, 'conta_corrente', $uf, 'ARQUIVOS']);
				$folderTemp  = implode($this->projectConfig->getGlue(), [$folder->fullpath, 'conta_corrente', $uf, 'ARQUIVOS_IMPORTADOS']);
				$global      = ((object) [
					'errors' => [
						0 => [],
						1 => [],
						2 => []
					],
					'totalArquivos' => 0,
					'qt_total'      => 0,
					'qt_success'    => 0,
					'qt_failed'     => 0,
					'qt_discard'    => 0
				]);

				if (!file_exists($folderTemp)) {
					$command->info(sprintf('A pasta (%s) não existe', $folderTemp));
					return 0;
				}

				if (file_exists($folderFiles)) {

					Folder::readDir($folderFiles, function (\DirectoryIterator $f, Folder $params) use ($folderTemp, $pdfFileDest, $uf, $folder, $service, $global, $cnpj_preffix) {

						if ($params->extension == 'txt') {

							$name = new JString($params->name);

							if ($name->startsWith('conta_fiscal') && $name->endsWith('VERIFICAR')) {

								$name    = $name->split('_');
								$cnpj    = $name[2];
								$empresa = Database::fetchRow("SELECT * FROM estabelecimentos WHERE cnpj = '{$cnpj}'");
								$usuario = 112; // bravo plataforma

								if ($empresa != null) {

									$array = $this->txt2object($params->fullpath);
									$qtSuc = 0;
									$qtArr = 0;

									if (!empty($array)) {

										Database::getPdo()->beginTransaction();

										$ano = substr($array[0]->periodo, 2, 4);

										foreach ($array as $tupla) {

											$tupla->estabelecimento_id = $empresa->id;
											$tupla->motivo_id          = 2; // aqui nao pode gravar se saldo = 0 e doc 046, mas na importação pode gravar com 0 se motivo 17

											try {

												if ($tupla->podeGravar()) {

													$exec = false;
													$row  = $tupla->existe();
													$ac   = 'I';

													if (!$row->exists()) {
														$values = [
															'documento'           => $tupla->documento,
															'estabelecimento_id'  => $tupla->estabelecimento_id,
															'periodo'             => $tupla->periodo,
															'valor_debito'        => $tupla->valor_debito,
															'data_consulta'       => date('Y-m-d', filemtime($params->fullpath)),
															'processo'            => $tupla->observacao,
															'arquivo'             => implode('', [$params->name, '.pdf']),
															'status_id'           => 3, // pendente
															'usuario_inclusao'    => $usuario,
															'usuario_alteracao'   => $usuario,
															'data_inclusao_reg'   => date('Y-m-d'),
															'data_alteracao_reg'  => date('Y-m-d')
														];

														$exec = $row->insert($values);
													}
													else { // update
														$values = [
															'data_consulta'      => date('Y-m-d', filemtime($params->fullpath)),
															'data_alteracao_reg' => date('Y-m-d')
														];
														$row->update($values);
														$exec = true;
														$ac   = 'U';
													}

													if (!$exec) {
														$global->qt_failed++;
														array_push($global->errors[0], sprintf('%s dados... FALHA (Empresa: %s, Periodo: %s, Saldo: %s, Documento: %s, Obs: %s)', ($ac == 'I' ? 'Inserindo' : 'Atualizando'), $cnpj, $tupla->periodo, $tupla->valor_debito, $tupla->documento, $tupla->observacao));
													}
													else {
														array_push($global->errors[0], sprintf('%s dados... SUCESSO (Empresa: %s, Periodo: %s, Saldo: %s, Documento: %s, Obs: %s)', ($ac == 'I' ? 'Inserindo' : 'Atualizando'), $cnpj, $tupla->periodo, $tupla->valor_debito, $tupla->documento, $tupla->observacao));
														$qtSuc++;
													}

													$qtArr++;

													$global->qt_total++;
												}
												else {
													$global->qt_discard++;
													array_push($global->errors[0], sprintf('Registro descartado: nenhuma regra se aplica. (Empresa: %s, Periodo: %s, Saldo: %s, Documento: %s, Obs: %s)', $cnpj, $tupla->periodo, $tupla->valor_debito, $tupla->documento, $tupla->observacao));
												}

											}
											catch (\Exception $e) {
												$global->qt_discard++;
												array_push($global->errors[0], sprintf('Registro descartado: %s (Empresa: %s, Periodo: %s, Saldo: %s, Documento: %s, Obs: %s)', $e->getMessage(), $cnpj, $tupla->periodo, $tupla->valor_debito, $tupla->documento, $tupla->observacao));
											}

										}

										if ($qtSuc == $qtArr) {

											$global->qt_success += $qtSuc;

											// só posso criar pasta com ano e apagar os arquivos se
											// [ todos os registros forem gravados com sucesso

											$pdfFileSource = $params->getNewName($params->name, '.pdf');
											$pdfFileDest2  = "{$pdfFileDest}/{$ano}";
											$pdfFileDest3  = "{$pdfFileDest2}/{$params->name}.pdf";

											if (!file_exists($pdfFileDest2)) {
												mkdir($pdfFileDest2); // separando por ano para nao inchar demais a pasta (o HD pode travar)
											}

											if (file_exists($pdfFileSource)) {
												copy($pdfFileSource, $pdfFileDest3);
												copy($pdfFileSource, ($folderTemp . "/{$params->name}.pdf"));
												unlink($pdfFileSource);
											}

											copy($params->fullpath, ($folderTemp . "/{$params->filename}"));
											$params->delete();

											Database::getPdo()->commit();
											array_push($global->errors[2], sprintf('%s Registros gravados com sucesso no arquivo da empresa: %s', $qtSuc, $cnpj));
										}
										else {
											Database::getPdo()->rollBack();
											array_push($global->errors[1], sprintf('Apenas %s de %s registros foram gravados com sucesso. Abortando transacao para esta empresa (%s)', $qtSuc, $qtArr, $cnpj));
										}

									}

								}
								else {
									array_push($global->errors[0], sprintf('CNPJ (%s) não localizado', $cnpj));
								}

								$global->totalArquivos++;
							}

						}

						return false;
					});
				}
				else {
					$command->info(sprintf('A pasta (%s) não existe', $folderFiles));
				}

				$log = [];

				array_push($log, sprintf('%s registros encontrados em %s arquivos', $global->qt_total, $global->totalArquivos));
				array_push($log, sprintf('Total de Registros gravados com sucesso: %s', $global->qt_success));
				array_push($log, sprintf('Total de Registros nao gravados (FALHA): %s', $global->qt_failed));
				array_push($log, sprintf('Total de Registros nao gravados (fora das regras): %s', $global->qt_discard));

				if (!empty($global->errors)) {

					if (!empty($global->errors[0])) {
						array_push($log, implode("\n", $global->errors[0]));
					}
					else {
						array_push($log, implode("\n", $global->errors[2]));
						array_push($log, implode("\n", $global->errors[1]));
					}

				}

				echo implode("\n", $log);
			}
			else {
				$command->info(sprintf('A pasta da empresa (%s) não existe em %s', $cnpj_preffix, $this->pastaStorage));
			}

		}
		else {
			$command->info(sprintf('Caminho (%s) não existe.', $this->pastaStorage));
		}

	}

}
