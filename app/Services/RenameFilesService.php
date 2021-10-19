<?php
/**
 * Created by Eclipse.
 * Date: 28/01/2021
 */
namespace App\Services;

use App\Console\Commands\RenameFiles;
use App\Utils\Folder;
use App\Utils\Model;
use App\Utils\Singleton;
use App\Utils\Models\Estabelecimento;
use App\Utils\Models\Tributo;
use App\Utils\Models\Regra;
use App\Utils\Models\Atividade;

class RenameFilesService {

	private $pastaStorage = null;

	/** @var RenameFiles $command */
	private $command;
	private $glue;

	function __construct() {

//		$this->pastaStorage = 'F:\storagebravobpo'; // Controller do Laravel nao conseguiu ler em letra de unidade diferente

		$split = explode('/', $_SERVER['SCRIPT_FILENAME']);

		$this->glue         = '/';
		$this->pastaStorage = implode($this->glue, [$split[0], 'storagebravobpo']);
	}

	public function setCommand($command) {
		$this->command = $command;
	}

	public function doProcessa($cnpj_preffix, $tipo_tributo) {

		$command = $this->command;
		$vars    = [];
		$service = $this;

		/** @var RenameFilesService $service */

		if (file_exists($this->pastaStorage)) {

			$folder = $this->searchCompanyFolder($cnpj_preffix);
			$error  = null;

			if ($folder !== null) {

				$vars['backup_folder']  = implode($this->glue, [$folder->fullpath, 'processados']);
				$vars['backup2_folder'] = implode($this->glue, [$vars['backup_folder'], $tipo_tributo]);
				$vars['input_folder']   = implode($this->glue, [$folder->fullpath, 'renomearguias', $tipo_tributo]);
				$vars['output_folder']  = implode($this->glue, [$folder->fullpath, 'entregar']);
				$vars['tipo_tributo']   = strtoupper($tipo_tributo);

				if (file_exists($vars['input_folder'])) {

					if (file_exists($vars['output_folder'])) {

						if (!file_exists($vars['backup_folder'])) {

							if (!mkdir($vars['backup_folder'])) {
								$error = sprintf('A pasta de backup da empresa (%s) não pôde ser criada.', $vars['backup_folder']);
							}

						}

						if ($error === null) {

							$error = null;

							if (file_exists($vars['backup_folder'])) {

								if (!file_exists($vars['backup2_folder'])) {

									if (!mkdir($vars['backup2_folder'])) {
										$error = sprintf('A pasta de backup da empresa (%s) não pôde ser criada.', $vars['backup2_folder']);
									}

								}

							}

							if ($error === null) {

								$tributo_id = 8; // ICMS
								$Tributo    = new Tributo($tributo_id);

								if ($Tributo->exists()) {
									Folder::readDir($vars['input_folder'], function (\DirectoryIterator $f, Folder $params) use ($Tributo, $folder, $vars, $service) {

										if ($params->extension == 'pdf') {

											Singleton::getProgress()->incTodos(1);

											$split    = explode('_', $params->name);
											$codEstab = $split[0];
											$periodo  = $split[2];
											$oldFile  = implode('_', [$split[0], $split[1], $split[2]]);
											$Estabe   = new Estabelecimento('codigo', $codEstab);
											$error    = null;

											if (!$Estabe->exists()) {
												$error = "Estabelecimento ({$codEstab}) não encontrado.";
											}
											else {

												$Municipio = $Estabe->hasParent('Municipio', 'cod_municipio');

												if (!$Municipio->exists()) {
													$error = "Municipio do estabelecimento ({$codEstab}) não encontrado.";
												}
												else {

													$Regra = new Regra(['tributo_id' => $Tributo->id, 'ref' => $Municipio->uf]);

													if (!$Regra->exists()) {
														$error = "Regra não encontrada com os parametros: tributo_id ({$Tributo->id}) e uf: {$Municipio->uf}";
													}
													else {

														$Atividade = new Atividade([
															'estemp_id'        => $Estabe->id,
															'regra_id'         => $Regra->id,
															'periodo_apuracao' => $periodo
														]);

														if (!$Atividade->exists()) {
															$error = "Atividade não encontrada com os parametros: id_estabelecimento ({$Estabe->id}), id_regra ({$Regra->id}) e periodo_apuracao: {$periodo}";
														}
														else {

															if (intval($Atividade->status) !== 1) {
																$error = sprintf('A Atividade (%s) está com status (%s)', $Atividade->id, $Atividade->status);
															}

														}

													}

												}

											}

											if ($error === null) {

												// DONE: criar pasta se nao existir
												// DONE: copia arquivo velho com nome novo na pasta criada
												// DONE: move (copia para backup e apaga da input) arquivo antigo para pasta backup

												$vars['newFolder']   = implode('_', [$Atividade->id, $Estabe->codigo, $vars['tipo_tributo'], $periodo, $Municipio->uf]);
												$vars['newFile']     = implode('_', [$Atividade->id, $oldFile, $Municipio->uf]);
												$vars['bkpFile']     = $params->filename;

												$vars['newFolder']   = implode($this->glue, [$vars['output_folder'], $vars['newFolder']]);
												$vars['newFilename'] = implode('', [implode($this->glue, [$vars['newFolder'], $vars['newFile']]), '.pdf']);
												$vars['bkpFilename'] = implode($this->glue, [$vars['backup2_folder'], $vars['bkpFile']]);

												if (!file_exists($vars['newFolder'])) {

													if (!mkdir($vars['newFolder'])) {
														$error = sprintf('A pasta da empresa (%s) não pôde ser criada.', $vars['newFolder']);
													}

												}

												if ($error === null) {

													if (copy($params->fullpath, $vars['newFilename'])) {

														if (copy($params->fullpath, $vars['bkpFilename'])) {

															if (unlink($params->fullpath)) {

																$ok = true;

																// card437 INICIO

																if ($Estabe->empresa_id == 1) { // se Burger King

																	$newFileCopy = implode($this->glue, [$folder->fullpath, $vars['newFile']]) . '.pdf';

																	if (!copy($vars['newFilename'], $newFileCopy)) {
																		$error = sprintf('Ops, ocorreu um erro ao mover o arquivo (%s) para a pasta (%s)', $vars['newFilename'], $newFileCopy);
																		$ok    = false;
																	}

																}

																// card437 FIM

																if ($ok) {
																	Singleton::getProgress()->incSuccess(1);
																	$service->command->info(sprintf('Arquivo (%s) (%s) ... [OK]', $params->fullpath, $vars['newFilename']));
																	return true;
																}

															}
															else {
																$error = sprintf('Ops, ocorreu um erro ao excluir o arquivo (%s)', $params->fullpath);
															}

														}
														else {
															$error = sprintf('Ops, ocorreu um erro ao mover o arquivo (%s) para a pasta (%s)', $params->fullpath, $vars['bkpFilename']);
														}

													}
													else {
														$error = sprintf('Ops, ocorreu um erro ao mover o arquivo (%s) para a pasta (%s)', $params->fullpath, $vars['newFilename']);
													}

												}

											}

											if ($error !== null) {
												Singleton::getProgress()->incFailed(1);
												$service->command->info(implode('', [$error, ' [FALHA]']));
											}

										}

										return false;
									});
								}
								else {
									$command->info(sprintf('Tributo (%s) não encontrado.', $tributo_id));
								}
							}
							else {
								$command->info($error);
							}

						}
						else {
							$command->info($error);
						}

					}
					else {
						$command->info(sprintf('A pasta de saida da empresa (%s) não existe.', $vars['output_folder']));
					}

				}
				else {
					$command->info(sprintf('A pasta de entrada da empresa (%s) não existe', $vars['input_folder']));
				}

			}
			else {
				$command->info(sprintf('A pasta da empresa (%s) não existe em %s', $cnpj_preffix, $this->pastaStorage));
			}

		}
		else {
			$command->info(sprintf('Caminho (%s) não existe.', $this->pastaStorage));
		}

	}

	/**
	 * @desc procura pela pasta contendo o prefixo de cnpj e retorna-a se encontrada (NULL se não for encontrada)
	 * @param string $cnpj_preffix
	 * @return Folder */
	public function searchCompanyFolder($cnpj_preffix) {

		$folders = Folder::readDir($this->pastaStorage, function (\DirectoryIterator $f, Folder $params) use ($cnpj_preffix) {
			return ($params->type == 'dir' && strpos($params->name, "_{$cnpj_preffix}") !== false);
		});

		return (!empty($folders) ? $folders[0] : null);
	}

}
