<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Observacaoprocadm;
use App\Models\Processosadm;
use App\Models\Respfinanceiro;
use App\Models\Statusprocadm;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Yajra\Datatables\Datatables;
use DB;

class ProcessosadmsController extends Controller
{
	protected $eService;
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
	public function index()
	{
		return view('processosadms.index');
	}

	public function import(Request $request = null)
	{
		return view('processosadms.import');
	}

	public function action_valid_import(Request $request)
	{
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
			return redirect()->route('processosadms.import');
		}

		$path = Input::file('file_csv')->getRealPath();
		$f = fopen($path, 'r');
		
		if (!$f) {
			Session::flash('alert', 'Arquivo inválido para operação');
			return redirect()->route('processosadms.import');
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

			$registro[1] = preg_replace("/[^0-9]/", "", $registro[1]);
			$estabelecimento = Estabelecimento::where('cnpj', '=', $registro[1])->where('empresa_id', $this->s_emp->id)->first();
			// echo 'Testanto o CNPJ: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';
			//busca estabelecimento
			if (!$estabelecimento) {
				// DB::rollBack();
				// Session::flash('alert', 'CNPJ inválido - Linha - '.$i);
				// return redirect()->back()->with('processosadms.import');
				
				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['cnpj'] = '[Linha - '.$linha.'] CNPJ do Estabelecimento não é valido para a Empresa selecionada ('.$registro[1].')';
				$e = true;
				$i++;
				$rni++;
				// echo '[ERRO] CNPJ do Estabelecimento inválido: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				$linha++;
				continue;
			// } else {
			// 	echo '[Resultado] CNPJ com estabelecimento válido: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';
			}
			
			// echo 'Passou pelo teste do CNPJ: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';
			
			//valida periodo de apuracao
			$value = explode('/', $registro[0]);
			if ((empty($value[0]) || empty($value[1])) || (!is_numeric($value[0]) || !is_numeric($value[1])) ) {
				// DB::rollBack();
				// Session::flash('alert', 'Periodo de apuração inválido - Linha - '.$i);
				// return redirect()->back()->with('processosadms.import');
				
				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['periodo_apuracao'] = '[Linha - '.$linha.'] Periodo de apuração informado (nulo) inválido ('.$registro[0].')';
				$e = true;
				$i++;
				$rni++;
				// echo '[ERRO - NULO] Periodo de apuração inválido: ['.$registro[1].'] - ('.$registro[0].') - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				$linha++;
				continue;
			}

			if (!checkdate($value[0], '01', $value[1])) {
				// DB::rollBack();
				// Session::flash('alert', 'Periodo de apuração inválido - Linha - '.$i);
				// return redirect()->back()->with('processosadms.import');
				
				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['periodo_apuracao'] = '[Linha - '.$linha.'] Periodo de apuração inválido ['.$registro[0].']';
				$e = true;
				$i++;
				$rni++;
				// echo '[ERRO] Periodo de apuração inválido: ['.$registro[1].'] - ('.$registro[0].') - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				$linha++;
				continue;
			}
			
			// echo 'Passou pelo teste do Periodo de Apuracao: '.$registro[1].' para o periodo '.$registro[0].'  - Linha '.$linha.' <br>';
			
			$responsavel_financeiro = str_replace(" ", "", $registro[3]);
			if (strtolower($responsavel_financeiro) == 'fornecedor') {
				$resp_id = 1;
			} elseif (strtolower($responsavel_financeiro) == 'cliente') {
				$resp_id = 2;
			} else {
				// DB::rollBack();
				// Session::flash('alert', 'Responsável financeiro ('.$registro[3].') inválido - Linha '.$i);
				// return redirect()->back()->with('processosadms.import');
				
				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['resp_id'] = '[Linha - '.$linha.'] Responsável financeiro ('.$registro[3].') inválido';
				$e = true;
				$i++;
				$rni++;
				// echo '[ERRO] ['.$registro[1].'] - Responsável financeiro ('.$registro[3].') inválido - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				$linha++;
				continue;
			}
			
			// echo 'Passou pelo teste do Responsável financeiro: '.$registro[3].' para o CNPJ '.$registro[1].'  - Linha '.$linha.' <br>';

			$status = str_replace(" ", "", $registro[6]);
			if (strtolower($status) == 'emandamento') {
				$status_id = 2;
			} elseif (strtolower($status) == 'baixado') {
				$status_id = 1;
			} else {
				// DB::rollBack();
				// Session::flash('alert', 'Status inválido - '.$i);
				// return redirect()->back()->with('processosadms.import');
				
				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['status_id'] = '[Linha - '.$linha.'] Status ('.$registro[6].') inválido';
				$e = true;
				$i++;
				$rni++;
				// echo '[ERRO] ['.$registro[1].'] - Status ('.$registro[6].') inválido - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				$linha++;
				continue;
			}
			
			// echo 'Passou pelo teste do Status: '.$registro[6].' para o CNPJ '.$registro[1].'  - Linha '.$linha.' <br>';
			
			if (!$registro[5]) {
				// DB::rollBack();
				// Session::flash('alert', 'Informar observação');
				// return redirect()->route('processosadms.create - '.$i);
				
				// gera array com erro de importacao e continua o processamento
				$importErrorsArr['errorMsg']['observacao'] = '[Linha - '.$linha.'] Observação é obrigatória';
				$e = true;
				$i++;
				$rni++;
				// echo '[ERRO] ['.$registro[1].'] - Observação é obrigatorio - Linha '.$linha.' <br>';
				// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
				$linha++;
				continue;
			}

			//populando array para insert
			$array['periodo_apuracao']      = $registro[0];
			$array['estabelecimento_id']    = $estabelecimento->id;
			$array['nro_processo']          = $registro[2];
			$array['resp_financeiro_id']    = $resp_id;
			$array['resp_acompanhamento']   = utf8_decode($registro[4]);
			$array['status_id']             = $status_id;
			$array['usuario_last_update']   = Auth::user()->email;
			$array['tipo_processamento']    = 'C';
			
			if ($e == false) {
				$create = Processosadm::create($array);
				if (!$create) {
					// DB::rollBack();
					// Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo - '.$i);
					// return redirect()->route('processosadms.create');
					
					// gera array com erro de importacao e continua o processamento
					$importErrorsArr['errorMsg']['cria_registro'] = '[Linha - '.$linha.'] Ocorreu um erro ao criar processo administrativo. Os dados informados não foram incluídos no banco de dados.';
					$e = true;
					$i++;
					$rni++;
					// echo '[ERRO] ['.$registro[1].'] - Ocorreu um erro ao criar processo administrativo. Os dados informados não foram incluídos no banco de dados - Linha '.$linha.' <br>';
					// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
					$linha++;
					continue;
				} else {
					$input['processoadm_id'] = $create->id;
					$input['descricao']      = utf8_decode($registro[5]);
					$input['usuario_update'] = Auth::user()->email;
					$input['tipo_processamento']   = 'C';

					$createObs = Observacaoprocadm::create($input);
					if (!$createObs) {
						// DB::rollBack();
						// Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo - observação - '.$i);
						// return redirect()->route('processosadms.create');
						
						// gera array com erro de importacao e continua o processamento
						$importErrorsArr['errorMsg']['cria_registro_observacao'] = '[Linha - '.$linha.'] Ocorreu um erro ao criar processo administrativo (Observação). Os dados informados não foram incluídos no banco de dados.';
						$e = true;
						$i++;
						$rni++;
						// echo '[ERRO] ['.$registro[1].'] - Ocorreu um erro ao criar processo administrativo (Observação). Os dados informados não foram incluídos no banco de dados - Linha '.$linha.' <br>';
						// echo '[NAO PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
						$linha++;
						continue;
					} else {
						$r++;
						$i++;
						// echo '[PROCESSOU] ['.$registro[1].'] - contador => '.$i.', registro => '.$r.' - Linha '.$linha.' <br><br>';
					}
				}
			}
			$linha++;
		}
		// var_dump('teve erro: ', $importErrorsArr['errorMsg']);
		
		// exit;
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

	public function consulta_procadm(Request $request)
	{
		$fim = $request->input("periodo_fim");
		$inicio = $request->input("periodo_inicio");
		$dataBuscaIni = array();
		
		if (empty($fim) || empty($inicio)) {
			$timestamp = strtotime("-4 months");
			$datInicial = date('d-m-Y', $timestamp);
			$datAtual = date('d-m-Y');

			list($dia, $mes, $ano) = explode( "-",$datInicial);
			$datInicial = getdate(strtotime($datInicial));
			$datAtual = getdate(strtotime($datAtual));
			$dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
			$meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
		 
			for($x = 0; $x < $meses; $x++){
				$datas[] =  date("m/Y", strtotime("+".$x." month", mktime(0, 0, 0, $mes, $dia, $ano)));
			}

			$dataBusca = '';
			
			foreach ($datas as $key => $value) {
				$dataBusca .= "'".$value."',";
			}
		}
		
		if (!empty($inicio) && !empty($fim)) {
			$datInicial = date('d/'.$inicio.'');
			$datAtual = date('d/'.$fim.'');
			$datInicial = str_replace('/', '-', $datInicial);
			$datAtual = str_replace('/', '-', $datAtual);
			list($dia, $mes, $ano) = explode("-", $datInicial);
			$datInicial = getdate(strtotime($datInicial));
			$datAtual = getdate(strtotime($datAtual));
			$dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
			$meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
			
			if ($meses < 0) {
				return redirect()->back()->with('alert', 'Favor informar uma data Válida');
			}
			for ($x = 0; $x < $meses; $x++) {
				$datas[] = date("m/Y", strtotime("+".$x." month", mktime(0, 0, 0, $mes, $dia, $ano)));
			}
			$dataBusca = '';
			foreach ($datas as $key => $value) {
				$dataBusca .= "'".$value."',";
			}
		}

		$dataBusca = substr($dataBusca, 0, -1);
		$dataBusca = explode(',', $dataBusca);
		
		$i = 0;
		$keyQ = 0;
		$datePP = "'".date('03/Y')."'";
		
		foreach ($dataBusca as $key => $value) {
			if ($value == $datePP) {
				$i++;
				$keyQ = $key;
			}
		}
		
		if ($keyQ != 0) {
			$keyQ = $keyQ-1;
		}
		
		$dataBusca[$keyQ] = "'".substr_replace($datas[$keyQ], '02', 0, 2)."'";
		$dataBusca = implode(',', $dataBusca);

		$datas = $dataBusca;
		$datas = substr($datas ,0,-1);
		$datas = substr($datas,1);
		$datas = explode("','", $datas);

		$Grupo_Empresa = new GrupoEmpresasController;
		$emps = $Grupo_Empresa->getEmpresas($this->s_emp->id);
		$empsArray = explode(',', $emps);
		
		foreach ($datas as $key => $final) {
			$standing[$final] = DB::select(
				"SELECT (
					SELECT COUNT(*)
					FROM processosadms A 
						INNER JOIN estabelecimentos B ON A.estabelecimento_id = B.id 
					WHERE A.periodo_apuracao IN ('".$final."')
					AND B.empresa_id IN (".$emps.")) AS total,
				(
					SELECT COUNT(*)
					FROM processosadms A 
						INNER JOIN estabelecimentos B ON A.estabelecimento_id = B.id
					WHERE A.periodo_apuracao IN ('".$final."')
					AND A.status_id = 1
					AND B.empresa_id IN (".$emps.")) AS baixados,
				(
					SELECT COUNT(*)
					FROM processosadms A
						INNER JOIN estabelecimentos B ON A.estabelecimento_id = B.id
					WHERE A.periodo_apuracao IN ('".$final."')
					AND A.status_id = 2
					AND B.empresa_id IN (".$emps.")) AS em_andamento ;"
			);
		}

		return view('processosadms.consulta')->with('standing', $standing)->with('datas', $datas)->with('dataBusca', $dataBusca);
	}
	
	public function rlt_detalhado(Request $request)
	{
		$fim = $request->input("periodo_fim");
		$inicio = $request->input("periodo_inicio");
		
		$dataBuscaIni = array();
		
		if (empty($fim) || empty($inicio)) {
			$timestamp = strtotime("-4 months");
			$datInicial = date('d-m-Y', $timestamp);
			$datAtual = date('d-m-Y');

			list($dia, $mes, $ano) = explode("-", $datInicial);
			$datInicial = getdate(strtotime($datInicial));
			$datAtual = getdate(strtotime($datAtual));
			$dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
			$meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
			 
			for($x = 0; $x < $meses; $x++){
				$datas[] =  date("m/Y", strtotime("+".$x." month",mktime(0, 0, 0, $mes, $dia, $ano)));
			}

			$dataBusca = '';
			foreach ($datas as $key => $value) {
				$dataBusca .= "'".$value."',";
			}
		}
		if (!empty($inicio) && !empty($fim)) {
			$datInicial = date('d/'.$inicio.'');
			$datAtual = date('d/'.$fim.'');
			$datInicial = str_replace('/', '-', $datInicial);
			$datAtual = str_replace('/', '-', $datAtual);
			list($dia, $mes, $ano) = explode( "-",$datInicial);
			$datInicial = getdate(strtotime($datInicial));
			$datAtual = getdate(strtotime($datAtual));
			$dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
			$meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
			
			if ($meses < 0) {
				return redirect()->back()->with('alert', 'Favor informar uma data Válida');
			}
			
			for ($x = 0; $x < $meses; $x++) {
				$datas[] =  date("m/Y", strtotime("+".$x." month", mktime(0, 0, 0, $mes, $dia, $ano)));
			}

			$dataBusca = '';
			foreach ($datas as $key => $value) {
				$dataBusca .= "'".$value."',";
			}
		}

		$dataBusca = substr($dataBusca, 0, -1);
		$datas = $dataBusca;
		$datas = substr($datas, 0, -1);
		$datas = substr($datas, 1);
		$datas = explode("','", $datas);

		$graphs = array();

		//$request->session()->put('filter_cnpj', $input['periodo_apuracao']);
		if (!empty($request->input("vcn")) || !empty($request->input("vco")) || !empty($request->input("vcp"))) {
			$request->session()->put('vcn', $request->input("vcn"));
			$request->session()->put('vco', $request->input("vco"));
			$request->session()->put('vcp', $request->input("vcp"));
		}

		if (!empty($request->input("clear"))) {
			Session::forget('vcn');
			Session::forget('vcp');
			Session::forget('vco');
		}

		if (!sizeof($request->input())) {
			$data = $request->session()->all();
			if (!empty($data['vcn']) || !empty($data['vco']) || !empty($data['vcp'])) {
				Input::merge(array('vcn' => $data['vcn']));
				Input::merge(array('vco' => $data['vco']));
				Input::merge(array('vcp' => $data['vcp']));
			} 
		}

		$Grupo_Empresa = new GrupoEmpresasController;
		$emps = $Grupo_Empresa->getEmpresas($this->s_emp->id);
		
		$where = 'b.empresa_id IN ('.$emps.') AND a.periodo_apuracao IN ('.$dataBusca.')';

		$graphs = DB::select(
			"SELECT 
				c.uf,
				SUM(IF(status_id = 1, 1, 0)) as Baixada,
				SUM(IF(status_id = 2, 1, 0)) as Andamento,
				COUNT(*) as total
			FROM processosadms a
				INNER JOIN estabelecimentos b ON a.estabelecimento_id = b.id
				INNER JOIN municipios c ON b.cod_municipio = c.codigo
			WHERE ".$where."                              
			GROUP BY c.uf"
		);

		return view('processosadms.graph')   
			->with('filter_cnpj', $request->input("vcn"))
			->with('filter_area', $request->input("vco"))
			->with('filter_periodo', $request->input("vcp"))
			->with('graphs', $graphs)->with('periodo_inicio', $inicio)->with('periodo_fim', $fim);
	}
	
	public function rlt_processos(Request $request)
	{
		$dataBuscaIni = array();
		
		if (empty($dataBuscaIni)) {
			$timestamp = strtotime("-4 months");
			$datInicial = date('d-m-Y', $timestamp);
			$datAtual = date('d-m-Y');

			list($dia, $mes, $ano) = explode( "-", $datInicial);

			$datInicial = getdate(strtotime($datInicial));
			$datAtual = getdate(strtotime($datAtual));
			$dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
			$meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
		 
			for ($x = 0; $x < $meses; $x++) {
				$datas[] =  date("m/Y", strtotime("+".$x." month", mktime(0, 0, 0, $mes, $dia, $ano)));
			}

			$dataBusca = '';
			foreach ($datas as $key => $value) {
				$dataBusca .= "'".$value."',";
			}
			
			$dataBusca = substr($dataBusca,0,-1);
		}

		$Grupo_Empresa = new GrupoEmpresasController;
		$emps = $Grupo_Empresa->getEmpresas($this->s_emp->id);

		$rpt = DB::select(
			"SELECT 
				A.id,
				A.periodo_apuracao,
				B.cnpj,
				C.uf,
				A.nro_processo,
				D.descricao as resp_financeiro,
				A.resp_acompanhamento,
				E.descricao
			FROM processosadms A
				INNER JOIN estabelecimentos B ON A.estabelecimento_id = B.id
				INNER JOIN municipios C ON B.cod_municipio = C.codigo
				INNER JOIN respfinanceiros D ON A.resp_financeiro_id = D.id
				INNER JOIN statusprocadms E ON A.Status_ID = E.id
			WHERE A.periodo_apuracao IN (".$dataBusca.") 
			AND B.empresa_id in (".$emps.")"
		);
		
		return Datatables::of($rpt)->make(true);
	}

	public function anyData(Request $request)
	{
		$processosadms = Processosadm::join('estabelecimentos', 'processosadms.estabelecimento_id', '=', 'estabelecimentos.id')
			->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
			->select(
				'processosadms.*',
				'processosadms.id as IdProcessosAdms',
				'estabelecimentos.insc_estadual',
				'estabelecimentos.cnpj',
				'estabelecimentos.codigo',
				'municipios.uf',
				'municipios.nome',
				DB::raw('(SELECT GROUP_CONCAT("Observação: ", descricao SEPARATOR " - ") FROM observacaoprocadms WHERE processoadm_id = processosadms.id) AS observacoesGroupConcat')
			)
			->with('estabelecimentos')
			->with('estabelecimentos.municipio')
			->with('statusprocadm')
			->with('respfinanceiro')
			->with('observacoes');

		if ($filter_cnpj = $request->get('cnpj')){
			$cnpj = preg_replace("/[^0-9]/", "", $filter_cnpj);
			$estabelecimento = Estabelecimento::select('id')->where('cnpj', $cnpj)->get();
			if (sizeof($estabelecimento) > 0) {
				$processosadms = $processosadms->where('estabelecimento_id', $estabelecimento[0]->id);
			} else {
				$processosadms = new Collection();
			}
		}

		if ($filter_area = $request->get('area')) {
			$estabelecimento = Estabelecimento::select('id')->where('codigo', $filter_area)->get();
			if (sizeof($estabelecimento) > 0) {
				$processosadms = $processosadms->where('estabelecimento_id', $estabelecimento[0]->id);
			} else {
				$processosadms = new Collection();
			}
		}

		if ($filter_periodo = $request->get('periodo')){
			$processosadms = $processosadms->where('periodo_apuracao', $filter_periodo);
		}

		$array = array();
		$estabelecimentos = Estabelecimento::select('id')->where('empresa_id', $this->s_emp->id)->get();
		foreach ($estabelecimentos as $row) {
			$array[] = $row->id;
		}
		
		$processosadms = $processosadms->whereIn('estabelecimento_id', $array);
		
		if (isset($request['search']) && $request['search']['value'] != '') {
			$str_filter = $request['search']['value'];
		}
		
		return Datatables::of($processosadms)->make(true);
	}

	public function anyDataRLT(Request $request)
	{
		//e com esses( que tinha funcionado na outra página )
		$fim = $request->input("periodo_fim");
		$inicio = $request->input("periodo_inicio");

		$dataBuscaIni = array();
		if (empty($fim) || empty($inicio)) {
			$timestamp = strtotime("-4 months");
			$datInicial = date('d-m-Y', $timestamp);
			$datAtual = date('d-m-Y');

			list($dia, $mes, $ano) = explode( "-", $datInicial);
			$datInicial = getdate(strtotime($datInicial));
			$datAtual = getdate(strtotime($datAtual));
			$dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
			$meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
			 
			for($x = 0; $x < $meses; $x++){
				$datas[] =  date("m/Y", strtotime("+".$x." month", mktime(0, 0, 0, $mes, $dia, $ano)));
			}

			$dataBusca = '';
			foreach ($datas as $key => $value) {
				$dataBusca .= "'".$value."',";
			}
		}
		
		if (!empty($inicio) && !empty($fim)) {
			$datInicial = date('d/'.$inicio.'');
			$datAtual = date('d/'.$fim.'');
			$datInicial = str_replace('/', '-', $datInicial);
			$datAtual = str_replace('/', '-', $datAtual);
			list($dia, $mes, $ano) = explode( "-", $datInicial);
			$datInicial = getdate(strtotime($datInicial));
			$datAtual = getdate(strtotime($datAtual));
			$dif = ( ($datAtual[0] - $datInicial[0]) / 86400 );
			$meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array
			
			if ($meses < 0) {
				return redirect()->back()->with('alert', 'Favor informar uma data Válida');
			}
			
			for($x = 0; $x < $meses; $x++) {
				$datas[] =  date("m/Y", strtotime("+".$x." month", mktime(0, 0, 0, $mes, $dia, $ano)));
			}

			$dataBusca = '';
			foreach ($datas as $key => $value) {
				$dataBusca .= "'".$value."',";
			}
		}

		$dataBusca = substr($dataBusca, 0, -1);
		$dataBusca = explode(',', $dataBusca);
		
		$i = 0;
		$keyQ = 0;
		$datePP = "'".date('03/Y')."'";
		
		foreach ($dataBusca as $key => $value) {
			if ($value == $datePP) {
				$i++;
				$keyQ = $key;
			}
		}
		
		if ($keyQ != 0) {
			$keyQ = $keyQ-1;
		}
		
		$dataBusca[$keyQ] = "'".substr_replace($datas[$keyQ], '02', 0, 2)."'";
		$dataBusca = implode(',', $dataBusca);

		$datas = $dataBusca;
		$datas = substr($datas, 0, -1);
		$datas = substr($datas, 1);
		$datas = explode("', '", $datas);
		
		$processosadms = Processosadm::join('estabelecimentos', 'processosadms.estabelecimento_id', '=', 'estabelecimentos.id')
			->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
			->select(
				'processosadms.*',
				'processosadms.id as IdProcessosAdms',
				'estabelecimentos.insc_estadual', 
				'estabelecimentos.cnpj',
				'estabelecimentos.codigo',
				'municipios.uf',
				'municipios.nome',
				DB::raw('(select GROUP_CONCAT("Observação: ", descricao SEPARATOR " - ") FROM observacaoprocadms where processoadm_id = processosadms.id) as observacoesGroupConcat')
			)
			->with('estabelecimentos')
			->with('estabelecimentos.municipio')
			->with('statusprocadm')
			->with('respfinanceiro')
			->with('observacoes');

		$processosadms = $processosadms->whereIn('periodo_apuracao', $datas);
		$array = array();
		
		$Grupo_Empresa = new GrupoEmpresasController;
		$emps = $Grupo_Empresa->getEmpresas($this->s_emp->id);
		$empsArray = explode(',', $emps);

		$estabelecimentos = Estabelecimento::select('id')->whereIn('empresa_id', $empsArray)->get();
		foreach ($estabelecimentos as $row) {
			$array[] = $row->id;
		}
		
		$processosadms = $processosadms->whereIn('estabelecimento_id', $array);
		
		if (isset($request['search']) && $request['search']['value'] != '' ) {
			$str_filter = $request['search']['value'];
		}
		
		return Datatables::of($processosadms)->make(true);
	}

	public function searchObservacao()
	{
		$processosadms = Processosadm::findOrFail($_GET['processosadm_id']);
		$observacoes = $processosadms->observacoes()->get();

		if (count($observacoes) > 0) {

			$i = 0;
			foreach ($observacoes as $observacao) {
				$usuario = User::where('email', '=', $observacao['usuario_update'])->first();
				if (!$usuario) {
					echo json_encode(array('success'=>false, 'data'=>array('observacoes'=>array())));
					exit;
				}

				$observacoes[$i]['nome'] = $usuario['name'];
				$observacoes[$i]['data'] = date('d/m/Y H:i:s', strtotime($observacoes[$i]['updated_at']));
				$i++;
			}
		}

		echo json_encode(array('success'=>true, 'data'=>array('observacoes'=>$observacoes)));
		exit;
	}

	public function search(Request $request = null)
	{
		$graphs = array();
		
		$where = ' 1 = 1 ';

		//$request->session()->put('filter_cnpj', $input['periodo_apuracao']);
		if (isset($request) && (!empty($request->input("vcn")) || !empty($request->input("vco")) || !empty($request->input("vcp")))) {
			$request->session()->put('vcn', $request->input("vcn"));
			$request->session()->put('vco', $request->input("vco"));
			$request->session()->put('vcp', $request->input("vcp"));
		}

		if (isset($request) && !empty($request->input("clear"))) {
			Session::forget('vcn');
			Session::forget('vcp');
			Session::forget('vco');
		}

		if (isset($request) && !sizeof($request->input())) {
			$data = $request->session()->all();
			if (!empty($data['vcn']) || !empty($data['vco']) || !empty($data['vcp'])) {
				Input::merge(array('vcn' => $data['vcn']));
				Input::merge(array('vco' => $data['vco']));
				Input::merge(array('vcp' => $data['vcp']));
			} 
		}

		if (isset($request) && !empty($request->input("vcn"))) {
			$cnpj = preg_replace("/[^0-9]/", "", $request->input("vcn"));
			$where .= ' AND b.cnpj = '.$cnpj.'';
		}

		if (isset($request) && !empty($request->input("vco"))) {
			$codigo = $request->input("vco");
			$where .= ' AND b.codigo = "'.$codigo.'"';
		}

		if (isset($request) && !empty($request->input("vcp"))) {
			$periodo = $request->input("vcp");
			$where .= ' AND a.periodo_apuracao = "'.$periodo.'"';
		}

		$where .= ' AND b.empresa_id = '.$this->s_emp->id;

		$graphs = DB::select(
			"SELECT 
				c.uf,
				SUM(if(status_id = 1, 1, 0)) as Baixada,
				SUM(if(status_id = 2, 1, 0)) as Andamento,
				COUNT(*) as total
			FROM processosadms a
				INNER JOIN estabelecimentos b on a.estabelecimento_id = b.id
				INNER JOIN municipios c on b.cod_municipio = c.codigo
			WHERE ".$where."                              
			GROUP BY c.uf"
		);

		return view('processosadms.search')
			->with('filter_cnpj', ($request)?$request->input("vcn"):null)
			->with('filter_area', ($request)?$request->input("vco"):null)
			->with('filter_periodo', ($request)?$request->input("vcp"):null)
			->with('graphs', $graphs);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create(Request $request = null)
	{
		$respFinanceiro = Respfinanceiro::all(['id', 'descricao'])->pluck('descricao', 'id');
		$status         = Statusprocadm::all(['id', 'descricao'])->pluck('descricao', 'id');
		
		$data = session()->all();
		
		$periodo_apuracao_processos = '';
		if (!empty($data['periodo_apuracao_processos'])) {
			$periodo_apuracao_processos = $data['periodo_apuracao_processos'];
			Session::forget('periodo_apuracao_processos');
		}

		return view('processosadms.create')
			->with('periodo_apuracao_processos', $periodo_apuracao_processos)
			->with('respFinanceiro', $respFinanceiro)
			->with('status', $status);
	}


	public function edit($id)
	{
		$respFinanceiro = Respfinanceiro::all(['id', 'descricao'])->pluck('descricao', 'id');
		$status         = Statusprocadm::all(['id', 'descricao'])->pluck('descricao', 'id');

		$processosadms = Processosadm::findOrFail($id);
		$observacoes = $processosadms->observacoes()->get();
		
		if (count($observacoes) > 0) {
			$i = 0;
			foreach ($observacoes as $observacao) {
				$usuario = User::where('email', '=', $observacao['usuario_update'])->first();
				if (!$usuario) {
					$observacoes[$i]['nome'] = 'SEM USUARIO ASSOCIADO';
				} else {
					$observacoes[$i]['nome'] = $usuario['name'];
				}
				
				$observacoes[$i]['data'] = date('d/m/Y H:i:s', strtotime($observacoes[$i]['updated_at']));
				$i++;
			}
		}
		
		return view('processosadms.edit')
			->withProcessosadms($processosadms)
			->with('observacoes', $observacoes)
			->with('respFinanceiro', $respFinanceiro)
			->with('status', $status);
	}


	public function update(Request $request, $id)
	{
		$processosadms = Processosadm::findOrFail($id);

		$input = $request->all();
		$this->validate(
			$request,
			[
				'periodo_apuracao' => 'required|formato_valido_periodoapuracao',
				'estabelecimento_id' => 'required',
				'nro_processo' => 'required',
				'resp_financeiro_id' => 'required',
				'resp_acompanhamento' => 'required',
				'status_id' => 'required'
			],
			$messages = [
				'periodo_apuracao.required' => 'Informar um periodo de apuração',
				'periodo_apuracao.formato_valido_periodoapuracao' => 'Formato do Periodo de apuração inválido',
				'estabelecimento_id.required' => 'Informar um código de Área de um estabelecimento válido.',
				'nro_processo.required' => 'Informar Nro do processo.',
				'resp_financeiro_id.required' => 'Informar Responsavel Financeiro.',
				'resp_acompanhamento.required' => 'Informar Responsavel Acompanhamento.',
				'status_id.required' => 'Informar Status.'
			]
		);

		DB::beginTransaction();
		$input['usuario_last_update'] = Auth::user()->email;

		if (!$processosadms->fill($input)->save()) {
			DB::rollBack();
			Session::flash('alert', 'Ocorreu um erro ao editar processo administrativo');
			return redirect()->route('processosadms.edit', $id);
		}

		if (!empty($input['Observacao'])) {

			$input['processoadm_id'] = $id;
			$input['descricao']      = $input['Observacao'];
			$input['usuario_update'] = Auth::user()->email;

			$createObs = Observacaoprocadm::create($input);
			if (!$createObs) {
				DB::rollBack();
				Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo - observação');
				return redirect()->route('processosadms.create');
			}
		}

		DB::commit();
		$processosadms->fill($input)->save();
		return redirect()->back()->with('status', 'Processo Administrativo atualizada com sucesso!');
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
		
		$this->validate(
			$request,
			[
				'periodo_apuracao' => 'required|formato_valido_periodoapuracao',
				'estabelecimento_id' => 'required',
				'nro_processo' => 'required',
				'resp_financeiro_id' => 'required',
				'resp_acompanhamento' => 'required',
				'status_id' => 'required',
				'Observacao' => 'required'
			],
			$messages = [
			'periodo_apuracao.required' => 'Informar um periodo de apuração',
			'periodo_apuracao.formato_valido_periodoapuracao' => 'Formato do Periodo de apuração inválido',
			'estabelecimento_id.required' => 'Informar um código de Área de um estabelecimento válido.',
			'nro_processo.required' => 'Informar Nro do processo.',
			'resp_financeiro_id.required' => 'Informar Responsavel Financeiro.',
			'resp_acompanhamento.required' => 'Informar Responsavel Acompanhamento.',
			'status_id.required' => 'Informar Status.',
			'Observacao.required' => 'Informar Observação.'
			]
		);

		DB::beginTransaction();
		$input['usuario_last_update'] = Auth::user()->email;

		$create = Processosadm::create($input);
		if (!$create) {
			DB::rollBack();
			Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo');
			return redirect()->route('processosadms.create');
		}

		if (!$input['Observacao']) {
			DB::rollBack();
			Session::flash('alert', 'Informar observação');
			return redirect()->route('processosadms.create');
		}

		$input['processoadm_id'] = $create->id;
		$input['descricao']      = $input['Observacao'];
		$input['usuario_update'] = Auth::user()->email;

		$createObs = Observacaoprocadm::create($input);
		if (!$createObs) {
			DB::rollBack();
			Session::flash('alert', 'Ocorreu um erro ao criar processo administrativo - observação');
			return redirect()->route('processosadms.create');
		}

		DB::commit();

		$request->session()->put('periodo_apuracao_processos', $input['periodo_apuracao']);
		return redirect()->back()->with('status', 'Registro adicionada com sucesso!');
	}

	public function delete($id)
	{
		if (!$id) {
			return redirect()->route('processosadms.search')->with('error', 'Informar processo administrativo para excluir');
		}

		Processosadm::destroy($id);
		return redirect()->route('processosadms.search')->with('status', 'Registro excluido com sucesso!');
	}
}
