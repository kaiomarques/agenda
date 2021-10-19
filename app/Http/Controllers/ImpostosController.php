<?php

namespace App\Http\Controllers;

use App\Models\Gerazfic;
use App\Models\Liberarguias;
use App\Models\StatusConferenciaGuias;
use Auth;
use DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Mockery\Exception;
use Prophecy\Doubler\Generator\TypeHintReference;
use Session;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Estabelecimento;
use App\Models\Atividade;
use App\Models\ConferenciaGuias;
use App\Models\Empresa;
use App\Models\Municipio;
use App\Models\Tributo;
use App\Models\User;
use App\Services\EntregaService;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Models\Guiaicms;
use App\Models\CriticasLeitor;
use App\Models\Regra;
use App\Models\CriticasEntrega;
use App\Models\EntregaExtensao;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use DateTime;
use Log;

class ImpostosController extends Controller
{
	protected $eService;
	public $s_emp = null;
	
	public function __construct(EntregaService $service)
	{
		if (!session()->get('seid')) {
			Session::flash('warning', 'Nenhuma empresa selecionada, favor selecionar uma!');
			return redirect()->route('home', ['selecionar_empresa' => 1])->send();
		}
		
		$this->eService = $service;
		
		if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
			$this->s_emp = Empresa::findOrFail(session()->get('seid'));
		}
	}
	
	public function selecionarGuias()
	{
		$empresa = Empresa::findOrFail($this->s_emp->id);
		$empresa_tributos = $empresa->tributos()->get();
		$array_tributos_ativos = [];
		foreach ($empresa_tributos as $at) {
			$array_tributos_ativos[] = $at->id;
		}
//		echo '<pre>', print_r($array_tributos_ativos);
		
		$tributos = Tributo::selectRaw("nome, id")->whereIN('id', $array_tributos_ativos)->where('categoria_id', '2')->orWhere('categoria_id', '3')->pluck('nome', 'id');
		
		$estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->pluck('codigo', 'id');
		$municipios = Municipio::select("uf")->orderBy('uf')->groupBy('uf')->pluck('uf');
		$uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->pluck('UF', 'UF');
		
		return view('impostos.selecionarguias')->with('tributos', $tributos)->withUf($uf);
	}
	
	public function validarGuias(Request $request, GuiaicmsController $guiaicmsController)
	{
		try {
//			throw new \Exception(print_r($_POST));
			if (!isset($_POST['tributo']) || isset($_POST['tributo']) && $_POST['tributo'] == '') {
				throw new \Exception('Selecione o tributo');
			}
			if (!isset($_POST['apuracao']) || isset($_POST['apuracao']) && $_POST['apuracao'] == '') {
				throw new \Exception('Informe o período de apuração');
			}
			if (empty($_FILES)) {
				throw new \Exception('Você precisa selecionar pelo menos um arquivo');
			}
			
			$files = array();
			foreach ($_FILES['guias'] as $key => $value) {
				foreach ($value as $key2 => $value2) {
					$files[$key2][$key] = $value2;
				}
			}
			
			$arrError = [];
			foreach ($files as $key => $file) {
				
				$name_explode = explode('_', str_replace(['.pdf','.PDF'],'',$file['name']));
				$codigo_filial = isset($name_explode[0]) ? $name_explode[0] : 0;
				$tributo_nome = isset($name_explode[1]) ? $name_explode[1] : 0;
				
				if($codigo_filial == 0){
					throw new \Exception('Você precisa selecionar pelo menos um arquivo sa');
				}
				
				$estabelecimento = Estabelecimento::where('codigo', $codigo_filial)->where('empresa_id', $this->s_emp->id)->first();
				if (empty($estabelecimento)) {
					$empresaNome = explode(' ', $this->s_emp->razao_social);
					$arrError[$key] = [
						'key' => $key,
						'message' => 'Esse arquivo não pertence à empresa ' . $empresaNome[0],
						'file' => $file['name']
					];
				}
				$estabelecimento_id = !empty($estabelecimento->id) ? $estabelecimento->id : 0;
				$estabelecimento_codigo = !empty($estabelecimento->codigo) ? $estabelecimento->codigo : 0;
				
				$tributo_id = $_POST['tributo'];
				
				$apuracao = isset($_POST['apuracao']) ? $_POST['apuracao'] : '0000-00';
				$periodo_apuracao = $this->periodoapuracao($apuracao, 'mY');
				
				$uf = isset($_POST['uf']) ? $_POST['uf'] : 'UF';
				$pasta = trim($tributo_nome) . '_' . trim($periodo_apuracao) . '_' . trim($uf);
				
				$empresaNome = explode(' ', $this->s_emp->razao_social);
				$empresaNome = isset($empresaNome[0]) ? $empresaNome[0] : '';
				$empresaCNPJ = substr($this->s_emp->cnpj, 0, 8);
			
				$DIR_EMPRESA = 'impostos/' . trim($empresaNome) . '_' . trim($empresaCNPJ);
				if (!is_dir($DIR_EMPRESA) && !is_file($DIR_EMPRESA)) {
					mkdir($DIR_EMPRESA, 0777);
				}
				
				$DIR_USUARIO_IMPOSTOS = $DIR_EMPRESA . '/' . trim(Auth::user()->id);
				if (!is_dir($DIR_USUARIO_IMPOSTOS) && !is_file($DIR_USUARIO_IMPOSTOS)) {
					mkdir($DIR_USUARIO_IMPOSTOS, 0777);
				}
				
				$DIR_PASTA_IMPOSTOS = $DIR_USUARIO_IMPOSTOS . '/' . trim($pasta);
				if (!is_dir($DIR_PASTA_IMPOSTOS) && !is_file($DIR_PASTA_IMPOSTOS)) {
					mkdir($DIR_PASTA_IMPOSTOS, 0777);
				}
				
				$regra = Regra::where('tributo_id', $tributo_id)->where('ref', $uf)->first();
				if (empty($regra)) {
					$regra = Regra::where('tributo_id', $tributo_id)->where('ref', $estabelecimento->cod_municipio)->first();
					if(empty($regra)){
						if (! array_key_exists($key, $arrError)) {
							$arrError[$key] = [
								'key' => $key,
								'message' => "Nenhuma regra encontrada com Tributo: " . $tributo_id . " e UF: " . $uf,
								'file' => $file['name']
							];
						}
					}
				}
				$regra_id = !empty($regra->id) ? $regra->id : 0;
//				throw new Exception('regra_id: '.$regra_id.', $periodo_apuracao: '.$periodo_apuracao.', estemp_id: '.$estabelecimento_id.'');
				$atividade = Atividade::where('regra_id', $regra_id)
					->with('estemp')
					->where('periodo_apuracao', $periodo_apuracao)
					->where('estemp_id', $estabelecimento_id)
					->where('status', 1)
					->orderby('id', 'asc')
					->first();
				if (empty($atividade)) {
					if (! array_key_exists($key, $arrError)) {
						$arrError[$key] = [
							'key' => $key,
							'message' => 'Nenhuma atividade encontrada para esse arquivo',
							'file' => $file['name']
						];
					}
				}
				$atividade_id = !empty($atividade->id) ? $atividade->id : 0;
				
				$nome_arquivo = trim($atividade_id) . '_' . trim($estabelecimento_codigo) . '_' . trim($pasta);
				
//				throw new Exception("select * from conferenciaguias where atividade_id = ".$atividade_id." and estemp_id = ".$estabelecimento_id." and tributo_id = ".$tributo_id." and periodo_apuracao = '".$periodo_apuracao."' and uf = '".$uf."' and nome_arquivo = '".$nome_arquivo.".pdf' limit 1");
				$conferenciaguias = DB::select("select * from conferenciaguias where atividade_id = ".$atividade_id." and estemp_id = ".$estabelecimento_id." and tributo_id = ".$tributo_id." and periodo_apuracao = '".$periodo_apuracao."' and uf = '".$uf."' and nome_arquivo = '".$nome_arquivo.".pdf' limit 1");
				if (!empty($conferenciaguias)) {
					if ($conferenciaguias[0]->statusconferencia_id == 1) {
						if (! array_key_exists($key, $arrError)) {
							$arrError[$key] = [
								'key' => $key,
								'message' => 'Já importado e está aguardando conferência',
								'file' => $file['name']
							];
						}
					}
					if ($conferenciaguias[0]->statusconferencia_id == 2) {
						if (! array_key_exists($key, $arrError)) {
							$arrError[$key] = [
								'key' => $key,
								'message' => 'Já importado e já foi aprovado. Clique para remover esse arquivo.',
								'file' => $file['name']
							];
						}
					}
				}
			}
			
			if (!empty($arrError)) {
				return json_encode([
					'success' => false,
					'message' => $arrError
				]);
			}
			
			return json_encode([
				'success' => true
			]);
			
		} catch (\Exception $e) {
			return json_encode([
				'success' => false,
				'message' => $e->getMessage()
			]);
		}
	}
	
	public function importarGuias(Request $request, GuiaicmsController $guiaicmsController)
	{
		try {
			if (!isset($_POST['tributo']) || isset($_POST['tributo']) && $_POST['tributo'] == '') {
				throw new \Exception('Selecione o tributo');
			}
			if (!isset($_POST['apuracao']) || isset($_POST['apuracao']) && $_POST['apuracao'] == '') {
				throw new \Exception('Informe o período de apuração');
			}
			if (empty($_FILES)) {
				throw new \Exception('Você precisa selecionar pelo menos um arquivo');
			}

			$files = array();
			foreach ($_FILES['guias'] as $key => $value) {
				foreach ($value as $key2 => $value2) {
					$files[$key2][$key] = $value2;
				}
			}
//			throw new \Exception(print_r($files));
			
			$arrError = [];
			foreach ($files as $key => $file) {
				
				$name_explode = explode('_', str_replace(['.pdf','.PDF'],'',$file['name']));
				$codigo_filial = isset($name_explode[0]) ? $name_explode[0] : 0;
				$tributo_nome = isset($name_explode[1]) ? $name_explode[1] : 0;
				
				$estabelecimento = Estabelecimento::where('codigo', $codigo_filial)->where('empresa_id', $this->s_emp->id)->first();
				if (empty($estabelecimento)) {
					$empresaNome = explode(' ', $this->s_emp->razao_social);
					$arrError[$key] = [
						'key' => $key,
						'message' => 'Esse arquivo não pertence à empresa ' . $empresaNome[0],
						'file' => $file['name']
					];
				}
				$estabelecimento_id = $estabelecimento->id;
				$estabelecimento_codigo = $estabelecimento->codigo;
				
				$tributo_id = $_POST['tributo'];
				
				$apuracao = isset($_POST['apuracao']) ? $_POST['apuracao'] : '0000-00';
				$periodo_apuracao = $this->periodoapuracao($apuracao, 'mY');
				
				$uf = isset($_POST['uf']) ? $_POST['uf'] : 'UF';
				$pasta = trim($tributo_nome) . '_' . trim($periodo_apuracao) . '_' . trim($uf);
				
				$empresaNome = explode(' ', $this->s_emp->razao_social);
				$empresaNome = isset($empresaNome[0]) ? $empresaNome[0] : '';
				$empresaCNPJ = substr($this->s_emp->cnpj, 0, 8);
				
				$DIR_EMPRESA = 'impostos/' . trim($empresaNome) . '_' . trim($empresaCNPJ);
				if (!is_dir($DIR_EMPRESA) && !is_file($DIR_EMPRESA)) {
					mkdir($DIR_EMPRESA, 0777);
				}
				
				$DIR_USUARIO_IMPOSTOS = $DIR_EMPRESA . '/' . trim(Auth::user()->id);
				if (!is_dir($DIR_USUARIO_IMPOSTOS) && !is_file($DIR_USUARIO_IMPOSTOS)) {
					mkdir($DIR_USUARIO_IMPOSTOS, 0777);
				}
				
				$DIR_PASTA_IMPOSTOS = $DIR_USUARIO_IMPOSTOS . '/' . trim($pasta);
				if (!is_dir($DIR_PASTA_IMPOSTOS) && !is_file($DIR_PASTA_IMPOSTOS)) {
					mkdir($DIR_PASTA_IMPOSTOS, 0777);
				}
				
				$regra = Regra::where('tributo_id', $tributo_id)->where('ref', $uf)->first();
				if (empty($regra)) {
					$regra = Regra::where('tributo_id', $tributo_id)->where('ref', $estabelecimento->cod_municipio)->first();
					if(empty($regra)){
						if (! array_key_exists($key, $arrError)) {
							$arrError[$key] = [
								'key' => $key,
								'message' => "Nenhuma regra encontrada com Tributo: " . $tributo_id . " e UF: " . $uf,
								'file' => $file['name']
							];
						}
					}
				}
				$regra_id = $regra->id;

//				throw new \Exception('regra_id: '.$regra_id . ', periodo_apuracao: ' . $periodo_apuracao . ', estemp_id: ' . $estabelecimento_id);
				$atividade = Atividade::where('regra_id', $regra_id)
					->with('estemp')
					->where('periodo_apuracao', $periodo_apuracao)
					->where('estemp_id', $estabelecimento_id)
					->where('status', 1)
					->orderby('id', 'asc')
					->first();
				if (empty($atividade)) {
					if (! array_key_exists($key, $arrError)) {
						$arrError[$key] = [
							'key' => $key,
							'message' => 'Nenhum Atividade encontrada para a regra: ' . $regra_id . ', periodo_apuracao: ' . $periodo_apuracao . ', estabelecimento: ' . $estabelecimento_id . ' status: 1',
							'file' => $file['name']
						];
					}
				}
				$atividade_id = $atividade->id;
				
				$nome_arquivo = trim($atividade_id) . '_' . trim($estabelecimento_codigo) . '_' . trim($pasta);
				
				$conferenciaguias = DB::select("select * from conferenciaguias where atividade_id = ".$atividade_id." and estemp_id = ".$estabelecimento_id." and tributo_id = ".$tributo_id." and periodo_apuracao = '".$periodo_apuracao."' and uf = '".$uf."' and nome_arquivo = '".$nome_arquivo.".pdf' limit 1");
				if (!empty($conferenciaguias)) {
					if ($conferenciaguias[0]->statusconferencia_id == 1) {
						if (! array_key_exists($key, $arrError)) {
							$arrError[$key] = [
								'key' => $key,
								'message' => 'Já importado e está aguardando conferência',
								'file' => $file['name']
							];
						}
					}
					if ($conferenciaguias[0]->statusconferencia_id == 2) {
						if (! array_key_exists($key, $arrError)) {
							$arrError[$key] = [
								'key' => $key,
								'message' => 'Já importado e já foi aprovado',
								'file' => $file['name']
							];
						}
					}
					if ($conferenciaguias[0]->statusconferencia_id == 3) {
						// remove file
						@unlink($DIR_PASTA_IMPOSTOS . '/' . basename($nome_arquivo . '.pdf'));
						// delete
						$conferenciaguiasDel = ConferenciaGuias::where('id', $conferenciaguias[0]->id);
						$conferenciaguiasDel->delete();
					}
				}
				
				if (empty($conferenciaguias) || !empty($conferenciaguias) && $conferenciaguias[0]->statusconferencia_id == 3) {
					
					// mover o arquivo para pasta
					move_uploaded_file($file['tmp_name'], $DIR_PASTA_IMPOSTOS . '/' . basename($nome_arquivo . '.pdf'));
					
					// gravar no banco de dados conferenciaguias com status 1 (aguardando conferencia)
					$conferenciaguias = ConferenciaGuias::create([
						'tributo_id' => $tributo_id,
						'periodo_apuracao' => $periodo_apuracao,
						'uf' => $uf,
						'estemp_id' => $estabelecimento_id,
						'atividade_id' => $atividade_id,
						'usuario_analista_id' => Auth::user()->id,
						'nome_arquivo' => $nome_arquivo . '.pdf',
						'data_importacao' => new \DateTime(),
						'statusconferencia_id' => 1,
						'usuario_conferente_id' => null,
						'data_conferencia' => null,
						'observacao' => null
					]);
					
					// Verifica se é zfic, caso seja gerar guiaicms
					$zfic = Gerazfic::where('tributo_id', $tributo_id)
						->where('empresa_id', $this->s_emp->id)
						->first();
					if (!empty($zfic)) {
						$a = explode('/', $_SERVER['SCRIPT_FILENAME']);
						$path = '';
						if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
							$path = $a[0];
						}
						$path .= '/storagebravobpo/';
						
						$empresaNome = explode(' ', $this->s_emp->razao_social);
						$empresaNome = isset($empresaNome[0]) ? $empresaNome[0] : '';
						$empresaCNPJ = substr($this->s_emp->cnpj, 0, 8);
						
						$path .= $empresaNome . '_' . $empresaCNPJ;
						copy($DIR_PASTA_IMPOSTOS . '/' . basename($nome_arquivo . '.pdf'), $path . '/' . basename($nome_arquivo . '.pdf'));
						
						$file_job = $path . '/' . basename($nome_arquivo . '.pdf');
						$guiaicmsController->JobConferenciaGuias($file_job, $conferenciaguias->id);
						
						$criticas = CriticasEntrega::where('arquivo', basename($nome_arquivo))->first();
						if (!empty($criticas)) {
							$criticas->delete();
							$conferenciaguias->delete();
							if (! array_key_exists($key, $arrError)) {
								$arrError[$key] = [
									'key' => $key,
									'message' => $criticas->critica,
									'file' => $file['name']
								];
							}
						}
						// delete file
						@unlink( $path . '/' . basename($nome_arquivo . '.pdf'));
					}
				}
			}
			
			if (!empty($arrError)) {
				return json_encode([
					'success' => false,
					'message' => $arrError
				]);
			}
			
			return json_encode([
				'success' => true
			]);
			
		} catch (\Exception $e) {
			return json_encode([
				'success' => false,
				'message' => $e->getMessage()
			]);
		}
	}
	
	public function conferenciaGuias()
	{
		$empresa = Empresa::findOrFail($this->s_emp->id);
		$empresa_tributos = $empresa->tributos()->get();
		$array_tributos_ativos = [];
		foreach ($empresa_tributos as $at) {
			$array_tributos_ativos[] = $at->id;
		}
		
		$tributos = Tributo::selectRaw("nome, id")->whereIN('id', $array_tributos_ativos)->where('categoria_id', '2')->orWhere('categoria_id', '3')->pluck('nome', 'id');
		$estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->pluck('codigo', 'id');
		$municipios = Municipio::select("uf")->orderBy('uf')->groupBy('uf')->pluck('uf');
		$uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->pluck('UF', 'UF');
		
		$get_tributo = isset($_GET['tributo'])? $this->numero($_GET['tributo']) : 0;
		$get_uf = isset($_GET['uf'])? $this->letras($_GET['uf']): 0;
		$get_periodo = isset($_GET['periodo'])? $this->periodoapuracao($_GET['periodo'], 'mY') : 0;

		$conferenciaGuias = DB::select('select a.*,
b.nome as tributo_nome,
c.REFERENCIA as guiaicms_referencia,
c.CNPJ as guiaicms_cnpj,
c.IE as guiaicms_ie,
c.VLR_TOTAL as guiaicms_vlr_total,
c.DATA_VENCTO as guiaicms_data_vencto,
d.cnpj as cnpj_estabelecimento,
d.insc_estadual as ie_estabelecimento,
d.codigo as codigo_estabelecimento,
e.id as zfic,
f.name as nome_usuario_analista
from conferenciaguias a
left join tributos b on b.id = a.tributo_id
left join guiaicms c on c.conferenciaguias_id = a.id
left join estabelecimentos d on d.id = a.estemp_id
left join gerazfic e on e.tributo_id = b.id
left join users f on f.id = a.usuario_analista_id
where a.statusconferencia_id = 1
and a.tributo_id = '.$get_tributo.'
and a.uf = "'.$get_uf.'"
and a.periodo_apuracao = "'.$get_periodo.'"');
		
    $empresaNome = explode(' ', $this->s_emp->razao_social);
    $empresaNome = isset($empresaNome[0]) ? $empresaNome[0] : '';
    $empresaCNPJ = substr($this->s_emp->cnpj, 0, 8);

//		var_dump($conferenciaGuias); exit;
		return view('impostos.conferenciaguias')
			->with('tributos', $tributos)
			->with('conferenciaGuias', $conferenciaGuias)
			->with('mostrartabela', isset($_GET['tributo'])? true : false)
			->with('pasta_empresa', $empresaNome . '_' . $empresaCNPJ)
			->withUf($uf);
	}
	
	public function aprovarGuias(Request $request)
	{
		try {
			if (!isset($_POST['id']) || isset($_POST['id']) && $_POST['id'] == '') {
				throw new \Exception('ID inválido');
			}
			
//			$tributo = Tributo::where('id', $_POST['conferencia_tributo'])->first();
			$idconferenciaguias = explode(',', $_POST['id']);
			
			foreach ($idconferenciaguias as $value) {
				$conferenciaguias = ConferenciaGuias::findOrFail($value);
				$conferenciaguias->fill([
					'observacao' => isset($_POST['observacao']) ? $_POST['observacao'] : null,
					'statusconferencia_id' => 2,
					'usuario_conferente_id' => Auth::user()->id,
					'data_conferencia' => new \DateTime()
				])->save();
				
				/*
				$estabelecimento = Estabelecimento::where('id', $conferenciaguias->estemp_id)->first();
				$usuario_analista = User::where('id', $conferenciaguias->usuario_analista_id)->first();
				
				// disparar e-mail
				$data = array(
					'assunto' => $tributo->nome . ' - ' .$this->periodoapuracao($_POST['conferencia_periodo'], 'm/Y') . ' - ' . $_POST['conferencia_uf'] .' - Filial: '.$estabelecimento->codigo. ' Aprovado!',
					'descricao' => '<p><span style="font-weight: bold;">Assunto:</span> '.$tributo->nome.' - '.$this->periodoapuracao($_POST['conferencia_periodo'], 'm/Y').' -
					'.$_POST['conferencia_uf'].' - '.$estabelecimento->codigo.' <span style="color:green;font-weight: bold">Aprovado!</span> <br />
<span style="font-weight: bold;">Mensagem:</span> Impostos da filial <strong>'.$estabelecimento->codigo.'</strong> referente o tributo <strong>'.$tributo->nome.'</strong> do periodo <strong>'.$this->periodoapuracao($_POST['conferencia_periodo'], 'm/Y').'</strong>
está aprovado.</p>',
					'emails' => explode(',', $usuario_analista->email)
				);
				$this->sendEmail(Auth::user(), $data);*/
			}
			
			return json_encode([
				'success' => true,
				'arr' => $idconferenciaguias
			]);
			
		} catch (\Exception $e) {
			return json_encode([
				'success' => false,
				'message' => $e->getMessage()
			]);
		}
	}
	
	public function reprovarGuias(Request $request)
	{
		try {
//			throw new \Exception( print_r($_POST));
			if (!isset($_POST['idconferenciaguias']) || isset($_POST['idconferenciaguias']) && $_POST['idconferenciaguias'] == '') {
				throw new \Exception('Nenhuma guia selecionada');
			}
			
			$tributo = Tributo::where('id', $_POST['conferencia_tributo'])->first();
			$idconferenciaguias = explode(',', $_POST['idconferenciaguias']);
//			throw new \Exception( print_r($idconferenciaguias));
			
			foreach ($idconferenciaguias as $value) {
				$conferenciaguias = ConferenciaGuias::findOrFail($value);
				$conferenciaguias->fill([
					'observacao' => isset($_POST['observacao']) ? $_POST['observacao'] : null,
					'statusconferencia_id' => 3,
					'usuario_conferente_id' => Auth::user()->id,
					'data_conferencia' => new \DateTime()
				])->save();
				
				Guiaicms::where(['CONFERENCIAGUIAS_ID' => $value])->delete();
				
				$estabelecimento = Estabelecimento::where('id', $conferenciaguias->estemp_id)->first();
				$usuario_analista = User::where('id', $conferenciaguias->usuario_analista_id)->first();

				// disparar e-mail
				$data = array(
					'assunto' => $tributo->nome . ' - ' .$this->periodoapuracao($_POST['conferencia_periodo'], 'm/Y') . ' - ' . $_POST['conferencia_uf'] .' - Filial: '.$estabelecimento->codigo. ' Reprovado!',
					'descricao' => '<p><span style="font-weight: bold;">Assunto:</span> '.$tributo->nome.' - '.$this->periodoapuracao($_POST['conferencia_periodo'], 'm/Y').' -
					'.$_POST['conferencia_uf'].' - '.$estabelecimento->codigo.' <span style="color:red;font-weight: bold">Reprovado!</span> <br />
<span style="font-weight: bold;">Mensagem:</span> Impostos da filial <strong>'.$estabelecimento->codigo.'</strong> referente o tributo <strong>'.$tributo->nome.'</strong> do periodo <strong>'.$this->periodoapuracao($_POST['conferencia_periodo'], 'm/Y').'</strong>
estão reprovados, pelo motivo: <strong>'.$_POST['observacao'].'</strong></p>',
					'emails' => explode(',', $usuario_analista->email)
				);
				$this->sendEmail(Auth::user(), $data);
			}
			
			return json_encode([
				'success' => true,
				'arr' => $idconferenciaguias
			]);
			
		} catch (\Exception $e) {
			return json_encode([
				'success' => false,
				'message' => $e->getMessage()
			]);
		}
	}
	
	private function letras($string)
	{
		$nova = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), '', $string);
		return $nova;
	}
	
	private function numero($str)
	{
		return preg_replace("/[^0-9]/", "", $str);
	}
	
	private function periodoapuracao($value, $format = 'mY')
	{
		$apuracao = new \DateTime($value . '-01');
		$periodo_apuracao = $apuracao->format($format);
		
		return $periodo_apuracao;
	}
	
	public function conferenciaGuiasDownload(Request $request)
	{
		try{
			$public_dir=public_path();
			$zipFileName = date('YmdHis').'.zip';
			
			$zip = new \ZipArchive();
			if ($zip->open($public_dir . '/' . $zipFileName, \ZipArchive::CREATE) === TRUE) {
				
				foreach($_POST['data'] as $file){
					$zip->addFile($file['pasta'].$file['arquivo'], $file['arquivo']);
				}
				
				$zip->close();
			}
			
			$filetopath=$public_dir.'/'.$zipFileName;
			
			if(! file_exists($filetopath)) {
				return json_encode([
					'success' => false,
					'arquivo' => 'arquivo não criado'
				]);
			}
			
			return json_encode([
				'success' => true,
				'arquivo' => '/'.$zipFileName,
				'excluir' => $zipFileName
			]);
			
		} catch (\Exception $e) {
			return json_encode([
				'success' => false,
				'message' => $e->getMessage()
			]);
		}
	}
	
	public function deleteArquivoZip($arquivo)
	{
		try{
			
			@unlink($arquivo);
			return json_encode(['arquivo' => $arquivo]);
		
		} catch (\Exception $e) {
			return json_encode([
				'success' => false,
				'message' => $e->getMessage()
			]);
		}
	}
	
	public function liberarClienteSemZfic()
	{
		$empresa = Empresa::findOrFail($this->s_emp->id);
		$empresa_tributos = $empresa->tributos()->get();
		$array_tributos_ativos = [];
		foreach ($empresa_tributos as $at) {
			$array_tributos_ativos[] = $at->id;
		}
		
		$tributos = Tributo::selectRaw("nome, id")->whereIN('id', $array_tributos_ativos)->where('categoria_id', '2')->orWhere('categoria_id', '3')->pluck('nome', 'id');
		$uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->pluck('UF', 'UF');
		
		return view('impostos.liberarclientesemzfic')->with('tributos', $tributos)->withUf($uf);
	}
	
	public function enviarClienteSemZfic(Request $request)
	{
		try{
//			throw new Exception(print_r($_POST));
			$empresa = Empresa::findOrFail($this->s_emp->id);
			$estabelecimentos = DB::select('select id from estabelecimentos where ativo = 1 and empresa_id = ' . $empresa->id);
			$tributo = Tributo::where('id', $_POST['tributo'])->first();
			
			$arrEstabelecimentos=[];
			foreach($estabelecimentos as $value){
				$arrEstabelecimentos[] = $value->id;
			}
			
			$apuracao = isset($_POST['apuracao']) ? $_POST['apuracao'] : '0000-00';
			$periodo_apuracao = $this->periodoapuracao($apuracao, 'mY');
			
			$conferenciaguias = DB::select("select id, usuario_analista_id, nome_arquivo from conferenciaguias
			where statusconferencia_id = 2
			and uf = '".$_POST['uf']."'
			and periodo_apuracao = '".$periodo_apuracao."'
			and tributo_id = ".$_POST['tributo']."
			and estemp_id in (".implode(',',$arrEstabelecimentos).");");
			
			if(count($conferenciaguias) == 0){
				throw new Exception('Nenhum item encontrado.');
			}
			
			// Criar .zip com os arquivos
			$empresaNome = explode(' ', $this->s_emp->razao_social);
			$empresaNome = isset($empresaNome[0]) ? $empresaNome[0] : '';
			$empresaCNPJ = substr($this->s_emp->cnpj, 0, 8);

			$copiarArquivosAtividadesJob = $this->copiarArquivosParaAtividadesJob();
			$public_dir = public_path();
			$zipFileName = date('YmdHis').'.zip';
			$zip = new \ZipArchive();
			if ($zip->open($public_dir . '/' . $zipFileName, \ZipArchive::CREATE) === TRUE) {
				foreach($conferenciaguias as $value){
					
					$nome_arquivo = str_replace(['.pdf','.PDF'], '', $value->nome_arquivo);
					$explode = explode('_', $nome_arquivo);
					$pasta = $explode[2].'_'.$explode[3].'_'.$explode[4];
					
					$zip->addFile('impostos/'.trim($empresaNome).'_'.trim($empresaCNPJ).'/'.$value->usuario_analista_id.'/'.$pasta.'/'.$value->nome_arquivo, $value->nome_arquivo);
					
					$caminhoPastaEntregar = $copiarArquivosAtividadesJob . '/entregar/' . $explode[0].'_'.$explode[1].'_'.str_replace(' ','',$tributo->nome).'_'.$explode[3].'_'.$explode[4];
					if(!is_dir($caminhoPastaEntregar)){
						@mkdir($caminhoPastaEntregar, 0777);
					}
					
					// Copiar arquivos para a pasta `entregar` para rodar o /atividades/Job
					@copy('impostos/'.trim($empresaNome).'_'.trim($empresaCNPJ).'/'.$value->usuario_analista_id.'/'.$pasta.'/'.$value->nome_arquivo,
						$caminhoPastaEntregar .'/'. $value->nome_arquivo);
				}
				$zip->close();
			}
			$filetopath = $public_dir.'/'.$zipFileName;

			// Disparar e-mail
			$data = array(
				'assunto' => $tributo->nome . ' - ' .$this->periodoapuracao($apuracao, 'm/Y') . ' - ' . $_POST['uf'],
				'descricao' => 'Segue guias para pagamento referente: ' . $tributo->nome . ' - ' .$this->periodoapuracao($apuracao, 'm/Y') . ' - ' . $_POST['uf'],
				'anexo' => $filetopath,
				'emails' => explode(',',$_POST['emails'])
			);
			$this->sendEmail(Auth::user(), $data);
			
			// Salvar na tabela `liberaguias`
			$liberarguias = Liberarguias::create([
				'assunto' => $tributo->nome.' - '.$this->periodoapuracao($apuracao, 'm/Y').' - ' . $_POST['uf'],
				'emails' => $_POST['emails'],
				'data_liberada' => new \DateTime(),
				'usuario_id' => Auth::user()->id
			]);
			
			// No retorno da função para o ajax
			// é chamado a função para rodar o /atividades/job
			return json_encode([
				'success' => true
			]);
			
		} catch (\Exception $e) {
			return json_encode([
				'success' => false,
				'message' => $e->getMessage()
			]);
		}
	}
	
	private function copiarArquivosParaAtividadesJob()
	{
		$a = explode('/', $_SERVER['SCRIPT_FILENAME']);
		$path = '';
		
		$funcao = '';
		if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
			$path = $a[0];
		}
		$path .= '/storagebravobpo/';
		
		$empresaNome = explode(' ', $this->s_emp->razao_social);
		$empresaNome = isset($empresaNome[0]) ? $empresaNome[0] : '';
		$empresaCNPJ = substr($this->s_emp->cnpj, 0, 8);
		
		$path .= $empresaNome . '_' . $empresaCNPJ;
		
		return $path;
	}
	
	public function rodarAtividadesJob(GuiaicmsController $guiaicmsController)
	{
		try{
			// por enquanto não rodaremos o job automaticamente. Aguardaremos o cron das 13 e 22 horas.
			return json_encode([ 'success' => true ]);
			
			// Rodar /atividades/job
			$guiaicmsController->jobAtividades();
			
			return json_encode([
				'success' => true
			]);
			
		} catch (\Exception $e) {
			return json_encode([
				'success' => false,
				'message' => $e->getMessage()
			]);
		}
	}
	
	private function sendEmail($user_rec, $data, $content_page='emails.impostos', $array = false)
	{
		Mail::send($content_page, ['data' => $data, 'user' => $user_rec], function ($message) use ($data, $user_rec) {
			// note: if you don't set this, it will use the defaults from config/mail.php
			
			$message->from('no-reply-please@bravobpo.com.br', 'Conferência Fiscal');
			foreach($data['emails'] as $key => $value){
				if($key == 0){
					$message->to($value, $name = null);
				}else{
					$message->cc($value, $name = null);
				}
			}
			if(isset($data['anexo'])){
				$message->subject($data['assunto'])
								->attach($data['anexo']);
			}
		});
	}
	
	public function consultar()
	{
		$empresa = Empresa::findOrFail($this->s_emp->id);
		$empresa_tributos = $empresa->tributos()->get();
		$array_tributos_ativos = [];
		foreach ($empresa_tributos as $at) {
			$array_tributos_ativos[] = $at->id;
		}
		
		$tributos = Tributo::selectRaw("nome, id")->whereIN('id', $array_tributos_ativos)->where('categoria_id', '2')->orWhere('categoria_id', '3')->pluck('nome', 'id');
		$estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->pluck('codigo', 'id');
		$municipios = Municipio::select("uf")->orderBy('uf')->groupBy('uf')->pluck('uf');
		$uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->pluck('UF', 'UF');
		
		$get_tributo = isset($_GET['tributo'])? $this->numero($_GET['tributo']) : 0;
		$get_uf = isset($_GET['uf'])? $this->letras($_GET['uf']): 0;
		$get_periodo = isset($_GET['periodo'])? $this->periodoapuracao($_GET['periodo'], 'mY') : 0;
		
		$conferenciaGuias = DB::select('select a.*,
b.nome as tributo_nome,
c.REFERENCIA as guiaicms_referencia,
c.CNPJ as guiaicms_cnpj,
c.IE as guiaicms_ie,
c.VLR_TOTAL as guiaicms_vlr_total,
c.DATA_VENCTO as guiaicms_data_vencto,
d.cnpj as cnpj_estabelecimento,
d.insc_estadual as ie_estabelecimento,
d.codigo as codigo_estabelecimento,
e.id as zfic,
f.name as nome_usuario_analista,
g.name as nome_usuario_conferente
from conferenciaguias a
left join tributos b on b.id = a.tributo_id
left join guiaicms c on c.conferenciaguias_id = a.id
left join estabelecimentos d on d.id = a.estemp_id
left join gerazfic e on e.tributo_id = b.id
left join users f on f.id = a.usuario_analista_id
left join users g on g.id = a.usuario_conferente_id
where a.tributo_id = '.$get_tributo.'
and a.uf = "'.$get_uf.'"
and a.periodo_apuracao = "'.$get_periodo.'"');

//		var_dump($conferenciaGuias); exit;
		return view('impostos.consultar')
			->with('tributos', $tributos)
			->with('conferenciaGuias', $conferenciaGuias)
			->with('mostrartabela', isset($_GET['tributo'])? true : false)
			->withUf($uf);
	}
}
