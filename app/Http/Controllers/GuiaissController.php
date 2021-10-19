<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Yajra\Datatables\Datatables;
use Carbon\Carbon;
use App\Http\Requests;
use DB;
use App\Models\Regra;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Models\Guiaiss;
use App\Models\CriticasLeitor;
use App\Models\CriticasEntrega;
use App\Models\Atividade;
use App\Models\EntregaExtensao;
use App\Models\User;
use DateTimeZone;

class GuiaissController extends Controller
{
	protected $eService;
	public $msg;
	public $estabelecimento_id;
	public $s_emp = null;

	function __construct()
	{
		if (!session()->get('seid')) {
			Session::flash('warning', 'Nenhuma empresa selecionada, favor selecionar uma!');
			return redirect()->route('home', ['selecionar_empresa' => 1])->send();
		}
		
		$this->middleware('auth');

		if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
			$this->s_emp = Empresa::findOrFail(session()->get('seid'));
		}
	}
	
	public function lerGuiaISS()
	{
		$a = explode('/', $_SERVER['SCRIPT_FILENAME']);
		if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
			$dr = $a[0];
		} else {
			$dr = 'D:';
		}
				
		$ds = '/';
		$dr .= '/storagebravobpo/BK_13574594';
		$storage = 'guiaiss';
		
		$path = $dr.$ds.$storage;
		
		$arquivos = glob($path.'/*.{[pP][dD][fF]}', GLOB_BRACE);

		if (count($arquivos) > 0) :
			$msg = "Foram encontrados ".count($arquivos)." arquivos para processamento";
			$status = 200;
		else :
			$msg = "Não foi encontrado nenhum arquivo para processamento";
			$status = 404;
		endif;
		
		$date = Carbon::now(new DateTimeZone(config('configICMSVars.wamp.timezone_brt')));
		$date->format('F'); 
		$date->startOfMonth()->subMonth();
		$periodo = $date->format('m/Y');
		
		$empresa = Empresa::findOrFail(session('seid'));
		$cnpj = $empresa->cnpj;
		$cnpjRaiz = substr(preg_replace("/[^0-9]/", "", $cnpj), 0, -6);
		
		$municipios = Municipio::where('codigo', '>', 0)->orderBy('nome')->pluck('nome', 'codigo');
		$municipios->prepend('--Selecione o Município--', '');
		
		$municipioselected = '';
		
		return view('guiaiss.pagamento', compact('arquivos', 'status', 'msg', 'periodo', 'cnpjRaiz', 'municipios', 'municipioselected'));
	}
	
	public function processaGuiaISS(Request $request)
	{
		$status = 'success';
		$this->msg = '';
		$input = $request->all();
		
		$guiaISS = array();
		
		if (!empty($input)) {
			if (!$this->validation($input)) {
				$status = 'error';
				return view('guiaiss.pagamento')->with('msg', $this->msg)->with('status', $status);
			}
			$cnpj = $input['cnpjraiz'].$input['cnpj'];
			$estabelecimento = Estabelecimento::where('cnpj', '=', $this->onlyNumbers($cnpj))->where('ativo', 1)->where('empresa_id', '=' ,$this->s_emp->id)->first();
			
			$guiaISS['cnpj'] = $this->onlyNumbers($estabelecimento->cnpj);
			$guiaISS['periodo_apuracao'] = $input['periodo'];
			$guiaISS['periodo_competencia'] = $input['competencia'];
			$guiaISS['cod_municipio'] = $estabelecimento->cod_municipio;
			$guiaISS['vencimento'] = $input['vencimento'];
			
			$valorguia = str_replace('R$ ', '', $input['valorguia']);
			
			if (strlen($valorguia) <= 6) {
				$guiaISS['valor_guia'] = str_replace(',', '.', $valorguia);
			} else {
				$guiaISS['valor_guia'] = str_replace(',', '.', str_replace('.', '', $valorguia));
			}
			if (strlen($input['valorjuros']) <= 6) {
				$guiaISS['valor_juros'] = str_replace(',', '.', $input['valorjuros']);
			} else {
				$guiaISS['valor_juros'] = str_replace(',', '.', str_replace('.', '', $input['valorjuros']));
			}
			if (strlen($input['valormulta']) <= 6) {
				$guiaISS['valor_multa'] = str_replace(',', '.', $input['valormulta']);
			} else {
				$guiaISS['valor_multa'] = str_replace(',', '.', str_replace('.', '', $input['valormulta']));
			}
			
			$guiaISS['codigo_barras'] = trim($this->onlyNumbers($input['codigobarras']));
			$guiaISS['data_leitura_guia'] = Carbon::now(new DateTimeZone(config('configICMSVars.wamp.timezone_brt')));
			$guiaISS['usuario_leitura_guia'] = Auth::user()->id;
			
			$a = explode('/', $_SERVER['SCRIPT_FILENAME']);
			if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
				$dr = $a[0];
			} else {
				$dr = 'D:';
			}
			
			$ds = '/';
			$dr .= '/storagebravobpo/BK_13574594';
			$storage = 'guiaiss';
			
			$path = $dr.$ds.$storage;
			
			// verifica se ja existe a guia	
			$contador = Guiaiss::where('cnpj', '=', $this->onlyNumbers($estabelecimento->cnpj))
				->where('periodo_apuracao', '=', $input['periodo'])
				->where('vencimento', '=', $input['vencimento'])
				->where('codigo_barras', '=', trim($this->onlyNumbers($input['codigobarras'])))
				->count();
			if ($contador > 0) {
				return back()
					->withInput()
					->with('warning','Esta guia já foi salva no sistema! Tente novamente com outra guia...');
			} else {
				Guiaiss::create($guiaISS);
				$request->session()->flash('success', 'Guia ISS ('.basename($input['fileGuiaISS']).') salva com sucesso');
				// muda o arquivo de diretorio
				rename($dr.$input['fileGuiaISS'], $path.'/imported/'.basename($input['fileGuiaISS']));
			}
			// lista os arquivos que sobraram
			$arquivos = glob($path.'/*.{[pP][dD][fF]}', GLOB_BRACE);
			
			if (count($arquivos) > 0) :
				$msg = "Foram encontrados ".count($arquivos)." arquivos para processamento";
				$status = 200;
			else :
				$msg = "Não foi encontrado nenhum arquivo para processamento";
				$status = 404;
			endif;

			$date = Carbon::now(new DateTimeZone(config('configICMSVars.wamp.timezone_brt')));
			$date->format('F'); 
			$date->startOfMonth()->subMonth();
			$periodo = $date->format('m/Y');
			
			$empresa = Empresa::findOrFail(session('seid'));
			$cnpj = $empresa->cnpj;
			$cnpjRaiz = substr(preg_replace("/[^0-9]/", "", $cnpj), 0, -6);
			
			$municipios = Municipio::where('codigo', '>', 0)->orderBy('nome')->pluck('nome', 'codigo');
			$municipios->prepend('--Selecione o Município--', '');
			
			$municipioselected = '';
			
			return view('guiaiss.pagamento', compact('arquivos', 'status', 'msg', 'periodo', 'cnpjRaiz', 'municipios', 'municipioselected'));
		}
	}
	
	public function onlyNumbers($str) {
		return preg_replace("/[^0-9]/", "", $str);
	}
	
	private function validation($input)
	{
		if (empty($input['periodo'])) {
			$this->msg = 'Favor informar o Período de Apuração';
			return false;
		}
		if (!empty($input['periodo'])) {
			$dt = date('Ym', strtotime(date('Y-m')." -1 month"));
			$date = explode('/', $input['periodo']);
			$dtc = $date[1].$date[0];

			if ($dtc > $dt) {
				$this->msg = 'Periodo de apuração incorreto';
				return false;
			}
			return true;
		}
		if (empty($input['competencia'])) {
			$this->msg = 'Favor informar o Período de Competência';
			return false;
		}
		if (!empty($input['competencia'])) {
			$dt = date('Ym', strtotime(date('Y-m')." -1 month"));
			$date = explode('/', $input['competencia']);
			$dtc = $date[1].$date[0];

			if ($dtc > $dt) {
				$this->msg = 'Periodo de Competência incorreto';
				return false;
			}
			return true;
		}
		if (empty($input['cnpj']) || empty($input['insc_municipal'])) {
			$this->msg = 'Favor informar o Final do CNPJ ou a Inscrição Municipal';
			return false;
		}
		if (!empty($input['cnpj']) && !empty($input['cnpjraiz'])) {
			$cnpj = $input['cnpjraiz'].$input['cnpj'];
			$estabelecimento = Estabelecimento::where('cnpj', '=', $this->onlyNumbers($cnpj))->where('ativo', 1)->where('empresa_id', '=',$this->s_emp->id)->first();

			if (empty($estabelecimento)) {
				$this->msg = 'Estabelecimento não habilitado ou inexistente';
				return false;
			}
		}
		if (empty($input['municipio'])) {
			$this->msg = 'Favor informar o Município';
			return false;
		}
		if (empty($input['codigobarras'])) {
			$this->msg = 'Favor informar o Código de Barras';
			return false;
		}
		if (strlen($input['codigobarras']) < 44 || strlen($input['codigobarras']) > 48) {
			$this->msg = 'Comprimento do Código de Barras incorreto';
			return false;
		}
		if (empty($input['vencimento'])) {
			$this->msg = 'Favor informar a Data de Vencimento';
			return false;
		}
		if (empty($input['valorguia'])) {
			$this->msg = 'Favor informar o Valor da Guia';
			return false;
		}
		if (empty($input['valorjuros'])) {
			$this->msg = 'Favor informar o Valor dos Juros ou 0,00 se não houver.';
			return false;
		}
		if (empty($input['valormulta'])) {
			$this->msg = 'Favor informar o Valor da Multa ou 0,00 se não houver';
			return false;
		}
		return true;
	}
	
	public function gerarLotePagamento(Request $request)
	{
		$estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->pluck('codigo', 'id');
		$municipios = Municipio::where('codigo', '>', 0)->orderBy('nome')->pluck('nome', 'codigo');
		$estabelecimentosselected = array();
		$municipiosselected = array();

		return view('guiaiss.gerarlote', compact('estabelecimentos', 'estabelecimentosselected', 'municipios', 'municipiosselected'));
	}

	public function gerarLoteArquivoCSV(Request $request)
	{
		$input = $request->all();
		
		$estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->pluck('codigo', 'id');
		$municipios = Municipio::where('codigo', '>', 0)->orderBy('nome')->pluck('nome', 'codigo');	
		
		$estabelecimentosselected = array();
		if (!empty($input['multiple_select_estabelecimentos'])) {
			$estabelecimentosselected = $input['multiple_select_estabelecimentos'];
		}
		
		$municipiosselected = array();
		if (!empty($input['multiple_select_municipios'])) {
			$municipiosselected = $input['multiple_select_municipios'];
		}
		
		
		if (empty($input['leitura_inicio']) || empty($input['leitura_fim'])) {
			return redirect()->back()->with('danger', 'Data Inicial de Leitura e Data Final de Leitura são obrigatórias!');
		} else {
			$data_leitura_inicio = $input['leitura_inicio'].' 00:00:00';
			$data_leitura_fim = $input['leitura_fim'].' 23:59:59';
		}
		
		$query = "
			SELECT g.*, e.codigo, m.nome AS localidade, m.uf, ccp.centrocusto
			  FROM guiaiss g
		 LEFT JOIN estabelecimentos e ON g.cnpj = e.cnpj
		INNER JOIN municipios m ON m.codigo = g.cod_municipio
		 LEFT JOIN centrocustospagto ccp ON ccp.Estemp_id = e.id
			 WHERE g.data_leitura_guia BETWEEN '".$data_leitura_inicio."' AND '".$data_leitura_fim."' 
		";
		
		if (!empty($input['vencimento_inicio']) && !empty($input['vencimento_fim'])) {
			$vencimento_inicio = $input['vencimento_inicio'];
			$vencimento_fim = $input['vencimento_fim'];
			
			$query .= " AND g.vencimento BETWEEN '".$vencimento_inicio."' AND '".$vencimento_fim."'";
		}
		
		if (!empty($input['multiple_select_estabelecimentos'])) {
			$query .= " AND g.cnpj IN (SELECT cnpj FROM estabelecimentos WHERE id IN (".implode(',', $input['multiple_select_estabelecimentos'])."))";
		}
		
		if (!empty($input['multiple_select_municipios'])) {
			$query .= " AND g.cod_municipio IN (".implode(',', array_map(function($value){
				return "'$value'";
			}, $input['multiple_select_municipios'])).")";
		}
		
		$dados = json_decode(json_encode(DB::Select($query)), true);
		
		$planilha = array();
		foreach ($dados as $key => $dado) {
			$planilha[] = $dado;
		}
		
		foreach ($planilha as $chave => $valorl) {
			if ($valorl['valor_juros'] == 0) {
				$planilha[$chave]['valor_juros'] = '';
			}

			if ($valorl['valor_multa'] == 0) {
				$planilha[$chave]['valor_multa'] = '';
			}
		}
		
		$valorDataLeituraF = date('dmY', strtotime(str_replace('-', '/', $data_leitura_fim)));
		$data_leitura_fim = $valorDataLeituraF;

		$valorDataLeituraI = date('dmY', strtotime(str_replace('-', '/', $data_leitura_inicio)));
		$data_leitura_inicio = $valorDataLeituraI;

		$msg = 'Período carregado com sucesso';
		if (empty($dados)) {
			$msg = 'Não há dados nesse período';
		}
		
		if (!empty($planilha)) {
			foreach ($planilha as $key => $value) {
				$planilha[$key]['valor_guia'] = $this->maskMoeda($value['valor_guia']);
				$planilha[$key]['valor_juros'] = $this->maskMoeda($value['valor_juros']);
				$planilha[$key]['valor_multa'] = $this->maskMoeda($value['valor_multa']);
			}
		}
		
		return view('guiaiss.gerarlote')
			->withEstabelecimentos($estabelecimentos)
			->withMunicipios($municipios)
			->with('planilha', $planilha)
			->with('data_inicio', $data_leitura_inicio)
			->with('data_fim', $data_leitura_fim)
			->with('msg', $msg)
			->withestabelecimentosselected($estabelecimentosselected)
			->withmunicipiosselected($municipiosselected);
	}
	
	private function maskMoeda($valor)
	{
		$string = '';
		if (!empty($valor)) {
			$string = number_format($valor, 2, ",", ".");
		}

		return $string;
	}
	
	public function conciliacaoMemoriaGuias()
	{
		$date = Carbon::now(new DateTimeZone(config('configICMSVars.wamp.timezone_brt')));
		$date->format('F'); 
		$date->startOfMonth()->subMonth();
		$periodo = $date->format('m/Y');
		
		$empresa = Empresa::findOrFail(session('seid'));
		$cnpj = $empresa->cnpj;
		$cnpjRaiz = substr(preg_replace("/[^0-9]/", "", $cnpj), 0, -6);
		
		return view('guiaiss.conciliacao', compact('periodo', 'cnpjRaiz'));
	}

	public function verificaCNPJ($cnpj)
	{
		$status = 'success';
		$this->msg = '';
		
		$cnpj = Estabelecimento::where('cnpj', $cnpj)->get();
		
		return json_encode($cnpj);
	}
	
	public function verificaCCM($ccm)
	{
		$status = 'success';
		$this->msg = '';
		
		$ccm = DB::select('SELECT * FROM estabelecimentos e WHERE getNumber(e.insc_municipal) = getNumber(:ccm)', ['ccm' => $ccm]);
		
		return json_encode($ccm);
	}
	
	public function verificaCodigo($codigo)
	{
		$status = 'success';
		$this->msg = '';
		
		$codigo = DB::select('SELECT * FROM estabelecimentos e WHERE e.codigo = :codigo', ['codigo' => $codigo]);
		
		return json_encode($codigo);
	}
}