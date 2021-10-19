<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\HistoricoContaCorrente;
use App\Models\FeriadoEstadual;
use App\Models\FeriadoMunicipal;
use App\Models\Respfinanceiro;
use App\Models\Movtocontacorrente;
use App\Services\EntregaService;
use App\Models\Statusprocadm;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Database\Eloquent\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Artisan;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

use App\Utils\Database;
use App\Utils\Variavel;
use App\Utils\Model;
use App\Models\Mensageriaprocadm;
use App\Utils\Folder;
use App\Utils\ProjectConfig;
use App\Utils\PString;
use App\Utils\UploadedFile;
use App\Utils\Models\ContaCorrente;
use App\UI\Html;

use Exception;
use stdClass;
use Illuminate\Support\Facades\URL;

class MovtocontacorrentesController extends Controller
{
	protected $eService;

	/** @var Empresa $s_emp */
	protected $s_emp = null;

	public function __construct()
	{
		if (!session()->get('seid')) {
			Session::flash('warning', 'Nenhuma empresa selecionada, favor selecionar uma!');
			return redirect()->route('home', ['selecionar_empresa' => 1])->send();
		}

		$this->middleware('auth');
		if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
			$this->s_emp = Empresa::findOrFail(session('seid'));
		}
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		return view('movtocontacorrentes.index');
	}

	public function action_valid_import(Request $request) {

		$input = $request->all();

		if (empty($input['file_csv'])) {
			echo json_encode(array('success'=>false, 'mensagem'=>'Arquivo Inválido'));
			exit;
		}

		$path = Input::file('file_csv')->getRealPath();
		$f = fopen($path, 'r');

		if (!$f) {
			echo json_encode(array('success'=>false, 'mensagem'=>'Dados inválidos'));
			exit;
		}

		while (!feof($f)) {
			$registro = fgetcsv($f, 0, ';', '"');
			if (!empty($registro[1]) && $registro[1] == 'cnpj') {
				continue;
			}

			if ($registro[0] == '' && empty($registro[1])) {
				continue;
			}

			if (empty($dataApuracao)) {
				$dataApuracao = $registro[0];
			}

			if ($dataApuracao != $registro[0]) {
				echo json_encode(array('success'=>true, 'dataApuracaoDiferente'=>true));
				exit;
			}

			$dataApuracao = $registro[0];
		}

		echo json_encode(array('success'=>true, 'dataApuracaoDiferente'=>false));
		exit;
	}

	public function action_import(Request $request)
	{
		$input = $request->all();
		$importErrorsArr['errorMsg'] = array();

		if (empty($input['file_csv'])) {
			Session::flash('alert', 'Informar arquivo CSV para realizar importação');
			return redirect()->route('movtocontacorrentes.import');
		}

		$path = Input::file('file_csv')->getRealPath();
		$f = fopen($path, 'r');

		if (!$f) {
			Session::flash('alert', 'Arquivo inválido para operação');
			return redirect()->route('movtocontacorrentes.import');
		}

		$movtoID = DB::table('movtocontacorrentes')->orderBy('id', 'desc')->limit(1)->get();
		if (!empty($movtoID[0])) {
			$id = $movtoID[0]->id;
		} else {
			$id = 0;
		}

		DB::beginTransaction();
		$periodoApuracaoDiferente = false;

		$i = 0;
		$r = 0;
		$rni = 0;
		$linha = 1;
		while (!feof($f)) {
			$e = false;
			$registro = fgetcsv($f, 0, ';', '"');

			if (!empty($registro[1]) && $registro[1] == 'cnpj') {
				// echo "[INICIO] Passou pelo cabecalho - Linha ".$linha." <br>";
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				$linha++;
				continue;
			}

			if ($registro[0] == '' && empty($registro[1])) {
				// echo "[INICIO] Passou pelo cabecalho - Linha ".$linha." <br>";
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				$linha++;
				continue;
			}

			$cnpj = preg_replace("/[^0-9]/", "", $registro[1]);
			$estabelecimento = Estabelecimento::where('cnpj', '=', $cnpj)->where('empresa_id', $this->s_emp->id)->first();
			// echo 'Testanto o CNPJ: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';
			//busca estabelecimento
			if (!$estabelecimento) {
				// DB::rollBack();
				// Session::flash('alert', 'CNPJ inválido ('.$registro[1].') - Linha - '.$i);
				// return redirect()->back()->with('movtocontacorrentes.import');

				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['cnpj'] = '[Linha - '.$linha.'] CNPJ do Estabelecimento não é valido para a Empresa selecionada ('.$registro[1].')';
				$e = true;
				$linha++;
				$i++;
				$rni++;
				// echo '[ERRO] CNPJ do Estabelecimento inválido: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				continue;
			// } else {
			// 	echo '[Resultado] CNPJ com estabelecimento válido: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';
			}

			// echo 'Passou pelo teste do CNPJ: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';

			/*
			//valida se já existe periodo cadastrado
			$movto = Movtocontacorrente::where('periodo_apuracao', '=', $registro[0])->first();
			if (!empty($movto->id)) {

				DB::rollBack();
				Session::flash('alert', 'Já existem dados com mês '.$registro[0]);
				return redirect()->back()->with('movtocontacorrentes.import');
			}*/

			//valida periodo de apuracao
			$value = explode('/', $registro[0]);
			if (empty($value[0]) || empty($value[1])) {
				// DB::rollBack();
				// Session::flash('alert', 'Periodo de apuração informado (nulo) inválido ('.$registro[0].') - Linha - '.$linha);
				// return redirect()->back()->with('movtocontacorrentes.import');

				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['periodo_apuracao'] = '[Linha - '.$linha.'] Periodo de apuração informado (nulo) inválido ('.$registro[0].')';
				$e = true;
				$linha++;
				$i++;
				$rni++;
				// echo '[ERRO - NULO] Periodo de apuração inválido: ['.$registro[1].'] - ('.$registro[0].') - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				continue;
			}

			if (!checkdate($value[0], '01', $value[1])) {
				// DB::rollBack();
				// Session::flash('alert', 'Periodo de apuração inválido ['.$registro[0].'] - Linha - '.$linha);
				// return redirect()->back()->with('movtocontacorrentes.import');

				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['periodo_apuracao'] = '[Linha - '.$linha.'] Periodo de apuração inválido ['.$registro[0].']';
				$e = true;
				$linha++;
				$i++;
				$rni++;
				// echo '[ERRO] Periodo de apuração inválido: ['.$registro[1].'] - ('.$registro[0].') - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				continue;
			}

			// echo 'Passou pelo teste do Periodo de Apuracao: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';

			$status = str_replace(" ", "", $registro[6]);
			if (strtolower($status) == 'emandamento') {
				$status_id = 2;
			} elseif (strtolower($status) == 'baixado') {
				$status_id = 1;
			} else {
				// DB::rollBack();
				// Session::flash('alert', 'Status inválido - '.$i);
				// return redirect()->back()->with('movtocontacorrentes.import');

				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['status_id'] = '[Linha - '.$linha.'] Status ('.$registro[6].') inválido';
				$e = true;
				$linha++;
				$i++;
				$rni++;
				// echo '[ERRO] ['.$registro[1].'] - Status ('.$registro[6].') inválido - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				continue;
			}

			//populando array para insert
			$array['periodo_apuracao']      = $registro[0];
			$array['estabelecimento_id']    = $estabelecimento->id;
			$array['usuario_update']    	= Auth::user()->email;
			$array['vlr_guia']              = $this->numberForMysql($registro[2]);
			$array['vlr_gia']               = $this->numberForMysql($registro[3]);
			$array['vlr_sped']              = $this->numberForMysql($registro[4]);
			$array['vlr_dipam']             = $this->numberForMysql($registro[5]);
			$array['status_id']             = $status_id;
			$array['observacao']            = $registro[7];
			$array['dipam']                 = 'S';
			$array['tipo_Importacao']       = 'C';

			if (strtolower($registro[5]) == 's/m') {
				$array['vlr_dipam'] = 0;
				$array['dipam']     = 'N';
			}

			if (!empty($registro[8] && !empty($registro[9]))) {
				$array['Data_inicio']           = $this->dateForMysql($registro[8]);
				$array['DataPrazo']             = $this->dateForMysql($registro[9]);

				if (strtotime($array['Data_inicio']) > strtotime($array['DataPrazo'])) {
					// DB::rollBack();
					// Session::flash('alert', 'Data de Início ('.$registro[8].' => '.strtotime($registro[8]).') não pode ser maior que a data do Prazo ('.$registro[9].' => '.strtotime($registro[9]).') - Linha - '.$linha);
					// return redirect()->back()->with('movtocontacorrentes.import');

					// gera array com erro de importacao e continua o processamento
					$importErrorsArr['errorMsg']['Data_inicio'] = '[Linha - '.$linha.'] Data de Início ('.$registro[8].') não pode ser maior que a data do Prazo ('.$registro[9].')';
					$e = true;
					$linha++;
					$i++;
					$rni++;
					// echo '[ERRO] ['.$registro[1].'] - Data de Início ('.$registro[8].') não pode ser maior que a data do Prazo ('.$registro[9].') - Linha '.$linha.' <br>';
					// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
					continue;
				}
			}

			// echo 'Passou pelo teste das datas: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';

			if (strtolower($registro[10]) == 'fornecedor') {
				$array['Responsavel'] = 1;
			} elseif (strtolower($registro[10]) == 'cliente') {
				$array['Responsavel'] = 2;
			} else {
				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['Responsavel'] = '[Linha - '.$linha.'] Responsavel ('.$registro[10].') inválido';
				$e = true;
				$linha++;
				$i++;
				$rni++;
				// echo '[ERRO] ['.$registro[1].'] - Responsavel ('.$registro[10].') inválido - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				continue;
			}

			if ($e == false) {
				Movtocontacorrente::create($array);
				$r++;
				$linha++;
				// echo '[PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
			}

			$i++;
			// var_dump('teve erro: ', $importErrorsArr['errorMsg']);
		}

		DB::commit();

		$linha--;

		if ($i > 0 && (count($importErrorsArr['errorMsg']) > 0)) {
			$request->session()->flash('warning', '[Atenção] Erros encontrados durante a importação!');
			$request->session()->flash('warning2', 'Log de importação: Erros encontrados durante o processamento');
			$request->session()->flash('totalLines', $linha);
			$request->session()->flash('totalRegisters', $i);
			$request->session()->flash('totalRegistersImported', $r);
			$request->session()->flash('totalRegistersNotImported', $rni);
			$request->session()->flash('errorMsgArr', $importErrorsArr['errorMsg']);
		} else {
			$request->session()->flash('info2', 'Log de importação');
			$request->session()->flash('totalLines', $linha);
			$request->session()->flash('totalRegisters', $i);
			$request->session()->flash('totalRegistersImported', $r);
			$request->session()->flash('totalRegistersNotImported', $rni);
		}

		// var_dump('tiveram erros: ', $importErrorsArr['errorMsg'], 'linha', $linha, 'contador', $i, 'registro', $r, 'nao importado', $rni, 'TEM ERRO? ', count($importErrorsArr['errorMsg']));

		return redirect()->back()->with('status', 'Importação realizada com sucesso!');
	}

	public function downloadLayout(Request $request = null) {

		$arrLayout = ContaCorrente::getLayoutArquivo();
		$name      = 'ContaCorrenteModelo.txt';
		$aux       = [];
		$data      = [
			array_keys($arrLayout),
			$arrLayout
		];

		header("Content-type:text/plain");
		header("Content-Disposition:attachment; filename={$name}");

		foreach ($data as $i => $cols) {

			foreach ($cols as $j => $c) {
				$data[$i][$j] = iconv('UTF-8', 'ISO-8859-1', $c);
			}

		}

		foreach ($data as $cols) {
			array_push($aux, implode("\t", $cols));
		}

		echo implode("\r\n", $aux);
	}

	public function downloadPDF(Request $request = null) {

		$id    = Variavel::getId($request->get('id'));
		$error = null;

		if ($id->isTrue()) {

			$model = new ContaCorrente($id->toInt());

			if ($model->exists()) {

				$estabelecimento = new \App\Utils\Models\Estabelecimento($model->estabelecimento_id);

				if ($estabelecimento->exists()) {

					$municipio = new \App\Utils\Models\Municipio($estabelecimento->cod_municipio);

					if ($municipio->exists()) {

						$cnpj          = $estabelecimento->cnpj;
						$uf            = $municipio->uf;
						$ano           = substr($model->periodo, 3, 4);
						$projectConfig = new ProjectConfig();
						$fullArquivo   = implode($projectConfig->getGlue(), [$projectConfig->getPastaContaCorrenteArquivos($cnpj, $uf, $ano), $model->arquivo]);

						if (file_exists($fullArquivo)) {
							Folder::dowloadFilePDF($fullArquivo);
						}
						else {
							$error = sprintf('O Arquivo PDF (%s) não existe.', $fullArquivo);
						}

					}
					else {
						$error = 'Municipio da conta corrente não localizado.';
					}

				}
				else {
					$error = 'Estabelecimento da conta corrente não localizado.';
				}

			}
			else {
				$error = sprintf('Conta corrente com id (%s) não localizado.', $id->toInt());
			}

		}
		else {
			$error = sprintf('Id da conta corrente invalido, (%s) passado', $request->get('id'));
		}

		if (!empty($error)) {
			die($error);
		}

	}

	public function commit(Request $request = null, Response $response = null) {

		/** @var Variavel $id */

		$json   = (object) [];
		$input  = $request->all();
		$data   = [];
		$errors = [];
		$ac     = '';
		$id     = Variavel::getId([$input, 'id']);
		$file   = new UploadedFile('arquivo');
		$upload = $file->isUploaded();
		$estabe = null;

		Database::getPdo()->beginTransaction();

		if ($id->isTrue()) { // update

			$contacorrente = new ContaCorrente($id->value());

			if (!$contacorrente->exists()) {
				array_push($errors, sprintf("O registro com Id (%s) não existe no banco de dados.", $id->value()));
			}
			else {
				$ac = 'update';
			}

		}
		else {
			$contacorrente = new ContaCorrente();
			$ac            = 'insert';
		}

		$regras = [
			'estabelecimento_id' => Variavel::getId([$input, 'estabelecimento_id'], 'estabelecimentos'),
			'status_id'          => Variavel::getId([$input, 'status_id'], 'statusprocadms'),
			'motivo_id'          => Variavel::getId([$input, 'motivo_id'], 'motivocontacorrente'),
			'risco_id'           => Variavel::getId([$input, 'risco_id'], 'riscocontacorrente'),
			'responsavel_bravo'  => Variavel::getString([$input, 'responsavel_bravo'], 100, 1),
			'acao'               => Variavel::getString([$input, 'acao'], 2000, 1),
			'processo'           => Variavel::getString([$input, 'processo'], 2000, 1),
			'periodo'            => Variavel::getPeriodo([$input, 'periodo']),
			'data_consulta'      => Variavel::getDate([$input, 'data_consulta']),
			'valor_debito'       => Variavel::getMoeda([$input, 'valor_debito']),
			'observacao'         => Variavel::getString([$input, 'observacao'], 2000, 0),
			'ambito'             => Variavel::getAmbito([$input, 'ambito']),
			'obrigacao'          => Variavel::getString([$input, 'obrigacao'], 250, 1),
			'resumo_pendencia'   => Variavel::getString([$input, 'resumo_pendencia'], 2000, 1),
			'resp_financeiro_id' => Variavel::getResponsavel([$input, 'resp_financeiro_id']),
			'resp_acompanhar_id' => Variavel::getResponsavel([$input, 'resp_acompanhar_id']),
			'qtde_nf'            => Variavel::getInt([$input, 'qtde_nf'], 100, 1),
			'juros'              => Variavel::getMoeda([$input, 'juros']),
			'prazo'              => Variavel::getDate([$input, 'prazo']),
			'valor_principal'    => Variavel::getMoeda([$input, 'valor_principal'])
		];

		if ($ac == 'insert') {
			$regras['documento'] = Variavel::getDocumento(ContaCorrente::getPadraoDocumento());
		}

		if ($regras['observacao']->length() == 0) {
			$regras['observacao'] = Variavel::getString(sprintf(ContaCorrente::getPadraoObservacao('EDITADO NO CADASTRO'), $regras['valor_principal']), 2000, 0);
		}

		foreach ($regras as $attr => $value) {

			if ($value->isFalse()) {
				array_push($errors, sprintf("Campo (%s) inválido:%s", $attr, $value->getError()));
			}
			else {
				$data[$attr] = $value->value();
				$contacorrente->__set($attr, $data[$attr]);
			}

		}

		if (empty($errors)) {

			if ($upload) {

				if ($file->isValid()) {

					if (!$file->isPDF()) {
						array_push($errors, 'O arquivo enviado não é do tipo PDF');
					}

				}
				else {
					array_push($errors, $file->getError());
				}

			}

			if (empty($errors)) {

				$ref    = new stdClass();
				$json   = ['status' => 'success', 'message' => 'Registro alterado com sucesso'];
				$onSave = function ($model) use ($upload, $file, $estabe, $regras, $ref) {

					/**
					 * @var Variavel $periodo
					 * @var ContaCorrente $model
					 * */

					if ($upload) {

						try {

							$estabe    = new \App\Utils\Models\Estabelecimento($regras['estabelecimento_id']->toInt());
							$periodo   = $regras['periodo'];
							$municipio = $estabe->municipio();
							$config    = new ProjectConfig();
							$ano       = $periodo->getAno();
							$target    = $config->getPastaContaCorrenteArquivos($estabe->cnpj, $municipio->uf, $ano);

							if (!file_exists($target)) {
								throw new Exception("O diretorio ({$target}) não exite.");
							}

							if ($file->moveTo($target, implode('-', [$model->getId(), $file->getName()]))) {

								if (!$model->update(['arquivo' => $file->getName()])) {
									throw new Exception(sprintf('Ocorreu um erro ao salvar o nome do arquivo no BD (%s)', $file->getName()));
								}

							}
							else {
								throw new Exception("Ocorreu um erro ao salvar o arquivo no diretorio ({$target})");
							}

						}
						catch (Exception $e) {
							$ref->error = $e->getMessage();
						}

					}

				};

				try {

					$validar = false;
					$fields  = $contacorrente->checkRules();
					$success = 0;
					$errors  = [];

					for ($x = 0, $len = strlen($fields); $x < $len; $x++) {

						if ($fields[$x]->isTrue()) {
							$success++;
						}
						else {
							array_push($errors, $fields[$x]->getError());
						}

					}

					if ($len != $success) {

						if ($contacorrente->podeGravar()) {

							if ($ac == 'update') {

								$colsValidate = ContaCorrente::getCamposValidacao();

								foreach ($colsValidate as $column) {

									$vl_db   = Database::auto_get($contacorrente->__get($column));
									$vl_form = Database::auto_get($regras[$column]->value());

									if ($vl_form != $vl_db) {
										$validar = true;
										break;
									}

								}

							}
							else {
								$validar = true;
							}

							if ($validar && $contacorrente->existe()->exists()) {
								$estabe = new \App\Utils\Models\Estabelecimento($contacorrente->estabelecimento_id);
								throw new Exception(sprintf('Já existe um registro com os parametros informados. %s', implode('<br />', [
									sprintf('Documento: %s', $contacorrente->documento), // usuario nao escolhe no formulario (descomentar caso precise debugar ou se o usuario quiser futuramente)
									sprintf('Empresa: %s', $estabe->cnpj),
									sprintf('Periodo: %s', $contacorrente->periodo),
									sprintf('Saldo: %s', $contacorrente->valor_debito),
									sprintf('Observação: %s', $contacorrente->observacao)
								])));
							}

						}

					}

				}
				catch (Exception $e) {
					$errors = $e->getMessage();
				}

				if ($ac == 'update') {

					$data['usuario_alteracao']  = Auth::user()->id;
					$data['data_alteracao_reg'] = date('d/m/Y');

					$contacorrente->update($data, $onSave);
				}
				elseif ($ac == 'insert') {

					$json['message']           = 'Registro inserido com sucesso';
					$data['usuario_inclusao']  = Auth::user()->id;
					$data['data_inclusao_reg'] = date('d/m/Y');

					$contacorrente->insert($data, $onSave);
				}

				if (isset($ref->error)) {
					$json = ['status' => 'failed', 'message' => $ref->error];
				}

			}
			else {
				$json = ['status' => 'failed', 'message' => $errors];
			}

		}
		else {
			$json = ['status' => 'failed', 'message' => $errors];
		}

		$json['message'] = str_replace('"', '', (is_array($json['message']) ? implode('<br />', $json['message']) : $json['message']));

 		printf('
		<script>
			top.scrollTo(0, 0);
			top.$("#div_frm_opt").hide();
			top.hendl.JAlert.hide();
		');

		if ($json['status'] == 'success') {
			Database::getPdo()->commit();
			printf('
				top.hendl.JAlert.success("%s");
				top.$("#div_frm_opt").show();
				top.document["frm_save"]["id"].value = "%s";
				top.doListagem();
			', $json['message'], $contacorrente->getId());
		}
		elseif ($json['status'] == 'failed') {
			Database::getPdo()->rollBack();
			printf('top.hendl.JAlert.danger("%s");', $json['message']);
		}

		echo "top.document.getElementById('ifrm').style.display = 'none';";
		echo '</script>';
	}

	public function search(Request $request) {
		
		$input   = isset($request)?$request->all():null;
		$action  = isset($input['action']) ? $input['action'] : null;
		
		if ($action == 'form') {

			$id = Variavel::getId([$input, 'id']);
			$id = $id->isTrue() ? $id->value() : -1;

			return view('movtocontacorrentes.form')->with('AuthEmpresaId', $this->s_emp->id)->with('id', $id);
		}
		else if ($action == 'list') {

			$where   = ['1=1'];
			$periodo = Variavel::getPeriodo([$input, 'src_periodo']);
			$cnpj    = Variavel::getCNPJ([$input, 'src_cnpj']);
			$limit   = Variavel::getId([$input, 'movtocontacorrentes-table_length']);

			$input['filiais'] = isset($input['filiais']) && is_array($input['filiais']) ? $input['filiais'] : [];
			$input['estados'] = isset($input['estados']) && is_array($input['estados']) ? $input['estados'] : [];
			$input['status']  = isset($input['status'])  && is_array($input['status'])  ? $input['status']  : [];

			array_push($where, sprintf("b.empresa_id = '%s'", $this->s_emp->id));

			if ($cnpj->isTrue()) {
				array_push($where, sprintf("b.cnpj = '%s'", $cnpj->clean()));
			}

			if ($periodo->isTrue()) {
				array_push($where, sprintf("a.periodo = '%s'", $periodo->clean()));
			}

			if (!empty($input['filiais'])) {
				array_push($where, sprintf("b.id IN ('%s')", implode("','", $input['filiais'])));
			}

			if (!empty($input['estados'])) {
				array_push($where, sprintf("c.uf IN ('%s')", implode("','", $input['estados'])));
			}

			if (!empty($input['status'])) {
				array_push($where, sprintf("a.status_id IN ('%s')", implode("','", $input['status'])));
			}

			$resultset = Database::fetchAll(sprintf("
			SELECT
				a.*, c.uf, c.nome cidade, b.cnpj, b.codigo, b.insc_estadual,
				us.name usuario_inc, us2.name usuario_alt, st.descricao status,
				motivo.descricao motivo,
				risco.descricao risco,
				rf.descricao responsavel_financeiro,
				rf2.descricao responsavel_acompanhamento
			FROM contacorrente a
			inner join estabelecimentos b on a.estabelecimento_id = b.id
			inner join municipios c on b.cod_municipio = c.codigo
			left  join motivocontacorrente motivo on motivo.id = a.motivo_id
			left  join riscocontacorrente risco on risco.id = a.risco_id
			inner join users us on us.id = a.usuario_inclusao
			left  join users us2 on us2.id = a.usuario_alteracao
			left  join statusprocadms st on st.id = a.status_id
			left  join respfinanceiros rf on rf.id = a.resp_financeiro_id
			left  join respfinanceiros rf2 on rf2.id = a.resp_acompanhar_id
			WHERE %s %s", implode(' AND ', $where), ($limit->isTrue() ? "LIMIT {$limit}" : '')));

			return view('movtocontacorrentes.grid')
			->with('filter_cnpj',$cnpj->value())
			->with('filter_periodo',$periodo->value())
			->with('filter_limit', $limit->value())
			->with('selected_filiais', $input['filiais'])
			->with('selected_estados', $input['estados'])
			->with('selected_status', $input['status'])
			->with('AuthEmpresaId', $this->s_emp->id)
			->with('resultset', $resultset);
		}

		return view('movtocontacorrentes.search');
	}

	public function consultagerencial(Request $request) {

		$input   = isset($request)?$request->all():null;
		$action  = isset($input['action']) ? $input['action'] : null;

		// TODO: validar arrays

		$input['filiais'] = isset($input['filiais']) && is_array($input['filiais']) ? $input['filiais'] : [];
		$input['estados'] = isset($input['estados']) && is_array($input['estados']) ? $input['estados'] : [];
//		$input['status']  = isset($input['status'])  && is_array($input['status'])  ? $input['status']  : [];

		if ($action == 'form') {

			$uf        = Variavel::getUF([$input, 'uf']);
			$error     = null;
			$id_motivo = null;

			if ($uf->isFalse()) {
				$error = $uf->getError();
			}

			if (!isset($input['id_motivo'])) {
				$error = 'Motivo não informado';
			}
			else {

				if ($input['id_motivo'] !== '') {

					$id_motivo = Variavel::getId([$input, 'id_motivo'], 'motivocontacorrente');

					if ($id_motivo->isFalse()) {
						$error = $id_motivo->getError();
					}

				}

			}

			if ($error === null) {

				$where = ['1=1'];

				array_push($where, sprintf("b.empresa_id = '%s'", $this->s_emp->id));

				if (!empty($input['filiais'])) {
					array_push($where, sprintf("b.id IN ('%s')", implode("','", $input['filiais'])));
				}

				array_push($where, sprintf("c.uf = '%s'", $uf->value()));

				if ($input['id_motivo'] === '') { // id_motivo eh opcional (nao vai no processamento automatico do robô)
					array_push($where, 'a.motivo_id IS NULL');
				}
				else {
					array_push($where, sprintf("a.motivo_id = '%s'", $input['id_motivo']));
				}

				$sql = sprintf("
				SELECT
					a.*, c.uf, c.nome cidade, b.cnpj, b.codigo, b.insc_estadual,
					us.name usuario_inc, us2.name usuario_alt, st.descricao status,
					motivo.descricao motivo,
					risco.descricao risco,
					rf.descricao responsavel_financeiro,
					rf2.descricao responsavel_acompanhamento
				FROM contacorrente a
				inner join estabelecimentos b on a.estabelecimento_id = b.id
				inner join municipios c on b.cod_municipio = c.codigo
				left  join motivocontacorrente motivo on motivo.id = a.motivo_id
				left  join riscocontacorrente risco on risco.id = a.risco_id
				left  join users us on us.id = a.usuario_inclusao
				left  join users us2 on us2.id = a.usuario_alteracao
				left  join statusprocadms st on st.id = a.status_id
				left  join respfinanceiros rf on rf.id = a.resp_financeiro_id
				left  join respfinanceiros rf2 on rf2.id = a.resp_acompanhar_id
				WHERE %s ", implode(' AND ', $where));

				$resultset = Database::fetchAll($sql);

				return view('movtocontacorrentes.consultagerencial_form')
					->with('AuthEmpresaId', $this->s_emp->id)
					->with('resultset', $resultset);
			}

			die($error);
		}
		else if ($action == 'list') {

			$where = ['1=1'];
			$limit = Variavel::getId([$input, 'movtocontacorrentes-table_length']);

			array_push($where, sprintf("b.empresa_id = '%s'", $this->s_emp->id));

			if (!empty($input['filiais'])) {
				array_push($where, sprintf("b.id IN ('%s')", implode("','", $input['filiais'])));
			}

			if (!empty($input['estados'])) {
				array_push($where, sprintf("c.uf IN ('%s')", implode("','", $input['estados'])));
			}

// 			if (!empty($input['status'])) {
// 				array_push($where, sprintf("a.status_id IN ('%s')", implode("','", $input['status'])));
// 			}
/*
			$resultset = Database::fetchAll(sprintf("
			SELECT
				count(a.id) qtde,
				sum(a.valor_debito) valor_debito,
				c.uf,
				motivo.id id_motivo,
				a.responsavel_bravo,
				a.responsavel_cliente,
				a.acao,
				motivo.descricao motivo,
				risco.descricao risco
			FROM contacorrente a
			inner join estabelecimentos b on a.estabelecimento_id = b.id
			inner join municipios c on b.cod_municipio = c.codigo
			left  join motivocontacorrente motivo on motivo.id = a.motivo_id
			left  join riscocontacorrente risco on risco.id = a.risco_id
			left  join statusprocadms st on st.id = a.status_id
			WHERE %s GROUP BY c.uf, motivo.id, a.responsavel_bravo, a.responsavel_cliente, a.acao, motivo.descricao, risco.descricao", implode(' AND ', $where)));
*/
			$resultset = Database::fetchAll(sprintf("
			SELECT
				count(a.id) qtde,
				sum(a.valor_debito) valor_debito,
				c.uf,
				motivo.id motivo_id,
				motivo.descricao motivo
			FROM contacorrente a
			inner join estabelecimentos b on a.estabelecimento_id = b.id
			inner join municipios c on b.cod_municipio = c.codigo
			left  join motivocontacorrente motivo on motivo.id = a.motivo_id
			WHERE %s GROUP BY c.uf, motivo.id, motivo.descricao", implode(' AND ', $where)));

			return view('movtocontacorrentes.consultagerencial_grid')
				->with('filter_limit', ($limit->isTrue() ? $limit->toInt() : 0))
				->with('selected_filiais', $input['filiais'])
				->with('selected_estados', $input['estados'])
//				->with('selected_status', $input['status'])
				->with('AuthEmpresaId', $this->s_emp->id)
				->with('resultset', $resultset);
		}

		return view('movtocontacorrentes.consultagerencial');
	}

	/** @return int */
	private function getValue($value, $glue) {

		if (strpos($value, $glue) !== false) {
			$split = explode($glue, $value);
			return $split[0];
		}

		return null;
	}

	public function importCommit(Request $request = null) {

		$file = new UploadedFile('arquivo');
		$data = ((object) [
			'success'       => 0,
			'failed'        => 0,
			'suc'           => 0,
			'err'           => 0,
			'qtLinhas'      => 0,
			'success_image' => Html::getImage('assets/img/Green-icon.png', ['width' => '16px', 'height' => '16px']),
			'danger_image'  => Html::getImage('assets/img/Red-icon.png', ['width' => '16px', 'height' => '16px'])
		]);

		if ($file->isValid()) {

			if ($file->isTxt()) {

				echo '<table style="width:100%; border-collapse:collapse;" border="1">';

				Database::getPdo()->beginTransaction();

				$file->read(function ($line, $index) use ($data) {

					if ($index == 0) {

						$aux  = explode("\t", $line);
						$cols = [''];

						foreach ($aux as $c) {
							$c = Variavel::getString($c);
							array_push($cols, $c->value());
						}

						echo '<tr>';
						printf('<th>%s</th>', implode('</th><th>', $cols));
						echo '</tr>';
					}
					else if ($index > 0) {

						$split   = explode("\t", $line);
						$qtFound = count($split);
						$qtCols  = count(ContaCorrente::getLayoutArquivo()) - 1; // -1 para descartar campo 'documento'
						$vars    = [];
						$success = 0;
						$failed  = 0;
						$values  = [];

						if ($qtFound > $qtCols) { // arquivo tem mais colunas que o necessario (21)

							$diff = $qtFound - $qtCols;

							for ($i = 1; $i <= $diff; $i++) {
								array_pop($split); // eliminar as colunas desnecessarias
							}

						}

						for ($i = 1; $i <= $qtCols; $i++) {
							$split[$i] = isset($split[$i]) && $split[$i] !== '' ? trim($split[$i]) : null;
						}

						$model = new ContaCorrente();
						$cnpj  = Variavel::getCNPJ($split[0]);

						$model->documento          = ContaCorrente::getPadraoDocumento(); // usuario nao escolhe, e precisamos validar
						$model->estabelecimento_id = 0;
						$model->status_id          = $this->getValue($split[1], ' - ');
						$model->motivo_id          = $this->getValue($split[2], ' - ');
						$model->risco_id           = $this->getValue($split[3], ' - ');
						$model->ambito             = $split[4];
						$model->obrigacao          = $split[5];
						$model->resumo_pendencia   = $split[6];
						$model->resp_financeiro_id = $split[7];
						$model->resp_acompanhar_id = $split[8];
						$model->responsavel_bravo  = $split[9];
						$model->qtde_nf            = $split[10];
						$model->juros              = $split[11];
						$model->prazo              = $split[12];
						$model->acao               = $split[13];
						$model->processo           = $split[14];
						$model->periodo            = $split[15];
						$model->data_consulta      = $split[16];
						$model->valor_principal    = $split[17];
						$model->valor_debito       = $split[18];
						$model->observacao         = $split[19];

						if ($cnpj->isTrue()) {

							$estabe = new \App\Utils\Models\Estabelecimento('cnpj', $cnpj->clean());

							if ($estabe->exists()) {
								$model->estabelecimento_id = $estabe->getId();
							}

						}

						$vars = $model->checkRules();

						echo '<tr>';
						printf('<td>%s</td>', ($index + 1));

						foreach ($vars as $attr => $attValue) {

							if ($attValue->isTrue()) {
								$values[$attr] = $attValue->value();
								echo Html::getGridCellSuccess($attValue->value(), sprintf('%s (%s) está OK', $attr, $attValue->value()));
								$success++;
							}
							else {
								echo Html::getGridCellDanger($attValue->getError(), $attValue->getError());
								$failed++;
							}

						}

						if ($success == count($vars)) {

							$data->suc++;

							if ($vars['observacao']->length() == 0) { // obs eh opcional para o usuario, mas o sistema necessita de um codigo para a validação (então definimos um valor padrão para não discutir com o usuário)
								$vars['observacao']   = Variavel::getString(sprintf(ContaCorrente::getPadraoObservacao('IMPORTADO PELO SISTEMA VIA TXT'), $vars['valor_principal']), 2000, 0);
								$values['observacao'] = $vars['observacao']->value();
							}

							$vars['documento'] = Variavel::getDocumento(ContaCorrente::getPadraoDocumento());

							$ok    = false;
							$model = new ContaCorrente();
							$model->motivo_id          = $values['motivo_id'];
							$model->estabelecimento_id = $values['estabelecimento_id'];
							$model->periodo            = $vars['periodo']->clean();
							$model->valor_debito       = $vars['valor_debito']->toFloat();
							$model->observacao         = $vars['observacao']->value();
							$model->documento          = $vars['documento']->value();

							try {

								if ($model->podeGravar()) {

									$model = $model->existe();

									if ($model->exists()) { // update

										$values['usuario_alteracao']   = Auth::user()->id;
										$values['data_alteracao_reg']  = date('d/m/Y');

										$ac = 'U';
										$ok = $model->update($values);
									}
									else { // insert

										$values['usuario_inclusao']  = Auth::user()->id;
										$values['data_inclusao_reg'] = date('d/m/Y');
										$values['documento']         = $vars['documento']->value();

										$ac = 'I';
										$ok = $model->insert($values);
									}

									if ($ok) {
										$data->success++;
										echo Html::getGridCellSuccess($data->success_image, sprintf('Registro %s com sucesso', ($ac == 'U' ? 'alterado' : 'inserido')));
									}
									else {
										$data->failed++;
										echo Html::getGridCellDanger($data->danger_image, sprintf('Falha na %s do registro', ($ac == 'U' ? 'alteração' : 'inserção')));
									}

								}
								else {
									$data->failed++;
									echo Html::getGridCellDanger($data->danger_image, sprintf('Registro não se aplica a nenhuma regra'));
								}

							}
							catch (Exception $e) {
								$data->failed++;
								echo Html::getGridCellDanger($data->danger_image, sprintf('Falha ao gravar registro:%s', $e->getMessage()));
							}

						}
						else {
							echo Html::getGridCellDanger($data->danger_image, 'Um ou mais campos estão inválidos');
							$data->err++;
						}

						echo '</tr>';

						$data->qtLinhas++;
					}

				});

				echo '</table>';
				echo '<br />';
				printf('Total de Registros: %s<br />', $data->qtLinhas);
				printf('Registros OK: %s<br />', $data->suc);
				printf('Registros inválidos: %s<br />', $data->err);
				printf('Gravados com sucesso: %s<br />', $data->success);
				printf('Falhas na gravação: %s<br />', $data->failed);

				if ($data->qtLinhas == $data->success) {
					Database::getPdo()->commit();
					printf('Importação realizada com sucesso!<br />');
				}
				else {
					Database::getPdo()->rollBack();
					printf('Ocorreram erros na importação, favor, faça as correções e tente novamente.<br />');
				}

			}
			else {
				printf('O Arquivo não é do tipo TXT');
			}

		}
		else {
			echo $file->getError();
		}

	}

	public function import(Request $request = null) {
		return view('movtocontacorrentes.import');
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request = null)
	{

		$status = Statusprocadm::all(['id', 'descricao'])->pluck('descricao', 'id');
		$Responsaveis = Respfinanceiro::all(['id', 'descricao'])->pluck('descricao', 'id');

		$data = $request->session()->all();
		$periodo_apuracao = '';
		if (!empty($data['periodo_apuracao'])) {
			$periodo_apuracao = $data['periodo_apuracao'];
			Session::forget('periodo_apuracao');
		}

	   return view('movtocontacorrentes.create')->with('periodo_apuracao', $periodo_apuracao)->with('status', $status)->with('Responsaveis', $Responsaveis);
	}


	public function edit($id)
	{
		$Responsaveis = Respfinanceiro::all(['id', 'descricao'])->pluck('descricao', 'id');
		$status         = Statusprocadm::all(['id', 'descricao'])->pluck('descricao', 'id');
		$movtocontacorrentes = Movtocontacorrente::findOrFail($id);
		$movtocontacorrentes->vlr_gia  = number_format($movtocontacorrentes->vlr_gia, 2, ',', '.');
		$movtocontacorrentes->vlr_guia = number_format($movtocontacorrentes->vlr_guia, 2, ',', '.');
		$movtocontacorrentes->vlr_sped = number_format($movtocontacorrentes->vlr_sped, 2, ',', '.');
		if ($movtocontacorrentes->dipam == 'S') {
			$movtocontacorrentes->vlr_dipam = number_format($movtocontacorrentes->vlr_dipam, 2, ',', '.');
		}

		$movtocontacorrentes->Data_inicio = $this->formatData($movtocontacorrentes->Data_inicio);
		$movtocontacorrentes->DataPrazo = $this->formatData($movtocontacorrentes->DataPrazo);

		return view('movtocontacorrentes.edit')->withMovtocontacorrentes($movtocontacorrentes)->with('status', $status)->with('Responsaveis', $Responsaveis);
	}

	public function formatData($receiveDate)
	{
		if ($receiveDate != '0000-00-00 00:00:00') {
			$explodedDate = explode('-', $receiveDate);
			$day = substr($explodedDate[2], 0,2);
			$month = $explodedDate[1];
			$year = $explodedDate[0];
			$timestamp = mktime(null, null, null, $month, $day, $year);
			$receiveDate = date('Y-m-d', $timestamp);
			return $receiveDate;
		}
		return $receiveDate;
	}

	public function dateForMysql($date)
	{
		$dateArr = explode('/', $date);
		$day = $dateArr[0];
		$month = $dateArr[1];
		$year = $dateArr[2];
		$timestamp = mktime(null, null, null, $month, $day, $year);
		$date = date('Y-m-d', $timestamp);
		return $date;
	}

	public function numberForMysql($number)
	{
		$numericStrLen = strlen($number);
		if ($numericStrLen < 7) { // 999,99
			$numericStr = str_replace(',', '.', $number);
		} elseif ($numericStrLen > 6) { // 9999,99
			$numericStrTmp = str_replace('.', '', $number);
			$numericStr = str_replace(',', '.', $numericStrTmp);
		}
		return $numericStr;
	}


	public function update(Request $request, $id)
	{
		$movtocontacorrentes = Movtocontacorrente::findOrFail($id);
		$input = $request->all();

		$this->validate($request, [
			'periodo_apuracao' => 'required|formato_valido_periodoapuracao',
			'estabelecimento_id' => 'required',
			'vlr_guia' => 'required',
			'vlr_gia' => 'required',
			'vlr_sped' => 'required',
			'status_id' => 'required',
			'observacao' => 'required',
			'Data_inicio' => 'required',
			'DataPrazo' => 'required'
		],
		$messages = [
			'periodo_apuracao.required' => 'Informar um periodo de apuração',
			'periodo_apuracao.formato_valido_periodoapuracao' => 'Formato do Periodo de apuração inválido',
			'estabelecimento_id.required' => 'Informar um código de Área de um estabelecimento válido.',
			'vlr_guia.required' => 'Informar Valor Guia.',
			'vlr_gia.required' => 'Informar Valor Gia.',
			'vlr_sped.required' => 'Informar Valor Sped.',
			'status_id.required' => 'Informar Status.',
			'observacao.required' => 'Informar Observação.',
			'Data_inicio.required' => 'Informar Data Inicio.',
			'DataPrazo.required' => 'Informar Prazo.'

		]);

		if (!empty($input['dipam']) && !$input['vlr_dipam']) {
			Session::flash('alert', 'Informar valor Dipam');
			return redirect()->route('movtocontacorrentes.edit', $id);
		}

		if (!empty($input['Data_inicio'] && !empty($input['DataPrazo']))) {
			if (strtotime($input['Data_inicio']) > strtotime($input['DataPrazo'])) {
				Session::flash('alert', 'Data de Início não pode ser maior que a data do Prazo');
				return redirect()->route('movtocontacorrentes.edit', $id);
			}
		}

		$input['vlr_guia']          =  $this->formatar_valor($input['vlr_guia']);
		$input['vlr_gia']           =  $this->formatar_valor($input['vlr_gia']);
		$input['vlr_sped']          =  $this->formatar_valor($input['vlr_sped']);
		$input['usuario_update']    = Auth::user()->email;

		if (!empty($input['dipam']) && $input['dipam'] == 'S') {
			$input['vlr_dipam'] =  $this->formatar_valor($input['vlr_dipam']);
		} else {
			$input['dipam'] = 'N';
		}

		$arrayMovto = json_decode(json_encode($movtocontacorrentes),true);
		$Historico = $this->historic($arrayMovto, $input);
		$movtocontacorrentes->fill($input)->save();

		return redirect()->back()->with('status', 'Conta Corrente atualizada com sucesso!');
	}
	public function historic($atual, $new)
	{
		$idMovto = $atual['id'];
		$idUser  = Auth::user()->id;

		$diff = array_diff($new, $atual);
		$txtDiff = '';
		if (array_key_exists('periodo_apuracao', $diff)) {
			$txtDiff .= '<b>Período</b> : '. $atual['periodo_apuracao'].' => '.$new['periodo_apuracao'].'<br />';
		}
		if (array_key_exists('estabelecimento_id', $diff)) {
			$txtDiff .= '<b>Estabelecimento</b> : '. $atual['estabelecimento_id'].' => '.$new['estabelecimento_id'].'<br />';
		}
		if (array_key_exists('vlr_guia', $diff)) {
			$txtDiff .= '<b>Vlr Guia</b> : '. $atual['vlr_guia'].' => '.$new['vlr_guia'].'<br />';
		}
		if (array_key_exists('vlr_gia', $diff)) {
			$txtDiff .= '<b>Vlr Gia</b> : '. $atual['vlr_gia'].' => '.$new['vlr_gia'].'<br />';
		}
		if (array_key_exists('vlr_sped', $diff)) {
			$txtDiff .= '<b>Vlr Sped</b> : '. $atual['vlr_sped'].' => '.$new['vlr_sped'].'<br />';
		}
		if (array_key_exists('dipam', $diff)) {
			$txtDiff .= '<b>Dipam</b> : '. $atual['dipam'].' => '.$new['dipam'].'<br />';
		}
		if (array_key_exists('vlr_dipam', $diff)) {
			$txtDiff .= '<b>Vlr Dipam</b> : '. $atual['vlr_dipam'].' => '.$new['vlr_dipam'].'<br />';
		}
		if (array_key_exists('status_id', $diff)) {
			$txtDiff .= '<b>Status</b> : '. $atual['status_id'].' => '.$new['status_id'].'<br />';
		}
		if (array_key_exists('observacao', $diff)) {
			$txtDiff .= '<b>OBS</b> : '. $atual['observacao'].' => '.$new['observacao'].'<br />';
		}

	$historico = new HistoricoContaCorrenteController();
	$historico->store($txtDiff, $idMovto, $idUser);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$input = $request->all();
		$this->validate($request, [
			'periodo_apuracao' => 'required|formato_valido_periodoapuracao',
			'estabelecimento_id' => 'required',
			'vlr_guia' => 'required',
			'vlr_gia' => 'required',
			'vlr_sped' => 'required',
			'status_id' => 'required',
			'observacao' => 'required',
			'Data_inicio' => 'required',
			'DataPrazo' => 'required'
		],
		$messages = [
			'periodo_apuracao.required' => 'Informar um periodo de apuração',
			'periodo_apuracao.formato_valido_periodoapuracao' => 'Formato do Periodo de apuração inválido',
			'estabelecimento_id.required' => 'Informar um código de Área de um estabelecimento válido.',
			'vlr_guia.required' => 'Informar Valor Guia.',
			'vlr_gia.required' => 'Informar Valor Gia.',
			'vlr_sped.required' => 'Informar Valor Sped.',
			'status_id.required' => 'Informar Status.',
			'observacao.required' => 'Informar Observação.',
			'Data_inicio.required' => 'Informar Data Inicio.',
			'DataPrazo.required' => 'Informar Prazo.'
		]);

		if (!empty($input['dipam']) && !$input['vlr_dipam']) {
			Session::flash('alert', 'Informar valor Dipam');
			return redirect()->route('movtocontacorrentes.create');
		}

		if (!empty($input['Data_inicio'] && !empty($input['DataPrazo']))) {
			if (strtotime($input['Data_inicio']) > strtotime($input['DataPrazo'])) {
				Session::flash('alert', 'Data de Início não pode ser maior que a data do Prazo');
				return redirect()->route('movtocontacorrentes.create');
			}
		}

		$input['vlr_guia'] =  $this->formatar_valor($input['vlr_guia']);
		$input['vlr_gia']  =  $this->formatar_valor($input['vlr_gia']);
		$input['vlr_sped'] =  $this->formatar_valor($input['vlr_sped']);
		$input['usuario_update']    = Auth::user()->email;

		if (!empty($input['dipam']) && $input['dipam'] == 'S') {
			$input['vlr_dipam'] =  $this->formatar_valor($input['vlr_dipam']);
		}
		dd($input);
		Movtocontacorrente::create($input);

		$request->session()->put('periodo_apuracao', $input['periodo_apuracao']);
		return redirect()->back()->with('status', 'Registro adicionada com sucesso!');
	}

	public function delete($id)
	{
		if (!$id) {
			return redirect()->route('movtocontacorrentes.search')->with('error', 'Informar movto para excluir');
		}

		Movtocontacorrente::destroy($id);
		return redirect()->route('movtocontacorrentes.search')->with('status', 'Registro excluido com sucesso!');
	}

	private function validate_dipam($dipam, $valor_dipam)
	{
		if ($dipam == 'S' && !$valor_dipam) {
			return false;
		}

		return true;
	}

	private function formatar_valor($valor)
	{
		if (!$valor) {
			return false;
		}

		$valor = str_replace('.', '', $valor);
		$valor = str_replace(',', '.', $valor);
		return $valor;
	}
}
