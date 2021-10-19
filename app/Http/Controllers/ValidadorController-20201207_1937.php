<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Models\User;
use App\Models\Validador;
use App\Http\Requests;

use Response;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

use Yajra\Datatables\Datatables;
Use Log;

class ValidadorController extends Controller
{
	public $empresa = null;

	private $atividadesRetificadas = array();
	
	public function __construct()
	{
		if (!session()->get('seid')) {
			redirect()->back()->with('warning', 'Nenhuma empresa selecionada');
		}
		
		$this->middleware('auth');

		if (!Auth::guest() && $this->empresa == null && !empty(session()->get('seid'))) {
			$this->empresa = Empresa::findOrFail(session()->get('seid'));
		}
	}
	
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		$tributosA = Tributo::selectRaw('nome, id')->whereRaw('id IN (1)')->pluck('nome', 'id');
		$tributosB = Tributo::selectRaw('nome, id')->whereRaw('id IN (8)')->pluck('nome', 'id');

		return view('validador.index')->with(compact('tributosA', 'tributosB'));
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function validaDados(Request $request)
	{
		$input = $request->all();

		if (!empty($input)) {
			if (empty($input['tributo_id_a']) || empty($input['tributo_id_b']) || (empty($input['periodo_apuracao']))) {
				return redirect()->back()->with('error', 'Os campos Tributo A, Tributo B e Período de apuração são obrigatórios para essa validação.');
			}

			$tributoA = $input['tributo_id_a'];
			$tributoB = $input['tributo_id_b'];

			if (!empty($tributoA) && !empty($tributoB)) {
				if ($tributoA == $tributoB) {
					return redirect()->back()->with('error', 'Você deve selecionar tributos diferentes para realizar a validação');
				}
			} else {
				return redirect()->back()->with('error', 'Você deve selecionar os Tributos A e B para realizar a validação');
			}

			$periodo = $this->calcPeriodo($input['periodo_apuracao'], $input['periodo_apuracao']);
			$ufs = array('AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO');

			$this->getAtividadesRetificadas($periodo, $ufs, $this->empresa->id);
			
			// Sped vs ICMS
			if ($tributoA == 1 && $tributoB == 8) {
				$retificadasIDs = $this->getIdAtividadesRetificadas($periodo, $ufs, $this->empresa->id);
				$retIDs = explode(',', $retificadasIDs);
				// var_dump($retIDs);exit;
				
				$atividades = $this->getAtividades($tributoA, $periodo, $ufs, $this->empresa->id, $retIDs);

				if (count($atividades) > 0) {
					// zera tabela de criticasvalores
					DB::table('criticasvalores')->where('empresa_id', '=', $this->empresa->id)->delete();
					
					/* TODO: Backlog (Vinny <marcus.coimbra@bravocorp.com.br>)
					* Fizemos o teste em 03/07/2020 e foi sugerido deixar apenas a regra 3 para todo mundo.
					* Fizemos o teste em 17/08/2020 e 1) foi detectado que os tipos comparados ($valorSped e $valorICMSDIFAL),
					* apesar de serem "double", não geravam "match" quando comparados. Por algum motivo, mesmos ambos sendo
					* double, o PHP 5 não estava entendendo que os tipos eram iguais, mesmo forçando a checagem de tipo (=== e !==)
					* sendo assim, foi necessário "formatar" os tipos como "numéricos" com 2 casas decimais. Isso resolveu o problema de mismatch
					* e 2) vamos incluir o campo +$atividadeB->vlr_recibo_3 na soma do ICMSDIFAL conforme entendimento entre a Geisi e a Fabrica de Software
					* Fizemos o teste em 06/10/2020 e foi solicitado a alteração da Regra de Comparação entre os valor de ICMS e SPED FISCAL, ficando a regras desta forma (L143:L149):
						SE a UF do estabelecimento for igual a PB
							Para o registro da atividade de ICMS, manterá a soma dos campos atual do registro:
							Valor de icms = vlr_recibo_1+vlr_recibo_3
						SENÃO
							Para o registro da atividade de ICMS acrescentar a soma do campo atividade.vlr_recibo_4 (DIFAL), então ficará:
							Valor de icms = vlr_recibo_1+vlr_recibo_3+vlr_recibo4
						FIMSE
					* Em 19/11/2020 foi necessário desativar a função substituirAtividadesRetificadas() que substtuia as atividades atuaias pelas retificadas,
					* causando duplicidade na hora da crítica uma vez que as funções getAtividade() e getAtividades() não faziam diferenciação em suas querys SQL
					* das atividades que possuiam compos {retificacao_id > 0} e {tipo_geracao = 'R'} da tabela atividades. Além da desativação (que ficou apenas como um bypass)
					* foi incluida a where condition {->where('atividades.tipo_geracao', '!=', 'R')} no querybuilder das funções getAtividade() e getAtividades().
					* Desta forma, a atividade original que possuia retificacao_id e tipo_geracao = R preenchidos, foram desconsiderados eliminando assim a duplicidade
					* das críticas.
					*/
					
					foreach ($atividades as $i => $atividadeA) {
						$atividade = $this->getAtividade($tributoB, $periodo, array($atividadeA->uf), $this->empresa->id, $atividadeA->estemp_id, $retIDs);
						
						if (count($atividade) > 0) {
							$atividadeB = $atividade[0];
						} else {
							/*
							* exceção a regra:
							* Sped Fiscal[tributo_id=1] (Valor Total do ICMS)->atividadesA-vlr_recibo_3 não possui Guia de ICMS
							*/
							if ($atividadeA->vlr_recibo_3 > 0) {
								DB::table('criticasvalores')->insert(
									[
									'empresa_id' => $this->empresa->id,
									'estemp_id' => $atividadeA->estemp_id,
									'periodo_apuracao' => str_replace('/', '', $input['periodo_apuracao']),
									'critica' => 'Valor Sped - id atividades: '.$atividadeA->id.' não possui Guia de ICMS'
									]
								);
							}
						}

						/*
						* 3a regra:
						* Sped Fiscal[tributo_id=1] (Valor Recolhido ou a Recolher, extra-apuração)->atividadesA-vlr_recibo_5+vlr_recibo_3 != ICMS[tributo_id=8] (Valor ICMS/DIFAL por Fora)->atividadesB-vlr_recibo_4+vlr_recibo_1
						*/
						$valorSped = number_format($atividadeA->vlr_recibo_5+$atividadeA->vlr_recibo_3, 2);
						
						if($atividadeA->uf == 'PB') {
							$valorICMSDIFAL = number_format($atividadeB->vlr_recibo_3+$atividadeB->vlr_recibo_1, 2);
						} else {
							$valorICMSDIFAL = number_format($atividadeB->vlr_recibo_4+$atividadeB->vlr_recibo_3+$atividadeB->vlr_recibo_1, 2);
						}
						
						if ($valorSped !== $valorICMSDIFAL) {
							// echo "<pre>";
							// echo 'UF SPED (Tributo A): '.$atividadeA->uf;
							// echo '<br>';
							// echo $atividadeA;
							// echo '<br><br>';
							// echo 'UF ICMS (Tributo B): '.$atividadeB->uf;
							// echo '<br>';
							// echo $atividadeB;
							// echo '<br><br>';
							// echo 'Valor SPED != ICMSDIFAL? => ';
							// echo ($valorSped !== $valorICMSDIFAL) ? 'É DIFERENTE' : 'É IGUAL';
							// echo '<br>';
							// echo ' valorSped (tipo): '.gettype($valorSped);
							// echo '<br>';
							// echo ' valorICMSDIFAL (tipo): '.gettype($valorICMSDIFAL);
							// echo '<br>';
							// echo
							// 	" ### valorSped != valorICMSDIFAL ? ($valorSped != $valorICMSDIFAL) => {$valorSped} e {$valorICMSDIFAL}",
							// 	'<br> empresa_id: ', $this->empresa->id, 
							// 	'<br> estemp_id: ', $atividadeA->estemp_id,
							// 	'<br> periodo_apuracao: ', str_replace('/', '', $input['periodo_apuracao']),
							// 	'<br> critica: ', 'Valor Sped Fiscal/Extra Apuração e ICMS/DIFAL estão diferentes - id atividades: '.$atividadeA->id.' e '.$atividadeB->id
							// ;
							// echo "<br><br>";
							// echo "</pre>";
							DB::table('criticasvalores')->insert(
								[
								'empresa_id' => $this->empresa->id, 
								'estemp_id' => $atividadeA->estemp_id,
								'periodo_apuracao' => str_replace('/', '', $input['periodo_apuracao']),
								'critica' => 'Valor Sped Fiscal/Extra Apuração e ICMS/DIFAL estão diferentes - id atividades: '.$atividadeA->id.' e '.$atividadeB->id
								]
							);
						}
						
						// if ($atividadeA->uf != 'MG') {
						// 	/*
						// 	* 1a regra:
						// 	* Sped Fiscal[tributo_id=1] (Valor Total do ICMS)->atividadesA-vlr_recibo_3 != ICMS[tributo_id=8] (Valor ICMS Guia)->atividadesB-vlr_recibo_1
						// 	*/
						// 	if ($atividadeA->vlr_recibo_3 != $atividadeB->vlr_recibo_1) {
						// 		DB::table('criticasvalores')->insert(
						// 			[
						// 			'empresa_id' => $this->empresa->id,
						// 			'estemp_id' => $atividadeA->estemp_id,
						// 			'periodo_apuracao' => str_replace('/', '', $input['periodo_apuracao']),
						// 			'critica' => 'Valor Sped e ICMS estão diferentes - id atividades: '.$atividadeA->id.' e '.$atividadeB->id
						// 			]
						// 		);
						// 	}
						// 	/*
						// 	* 2a regra:
						// 	* Sped Fiscal[tributo_id=1] (Valor Recolhido ou a Recolher, extra-apuração)->atividadesA-vlr_recibo_5 != ICMS[tributo_id=8] (Valor DIFAL por Fora)->atividadesB-vlr_recibo_4
						// 	*/
						// 	if ($atividadeA->vlr_recibo_5 != $atividadeB->vlr_recibo_4) {
						// 		DB::table('criticasvalores')->insert(
						// 			[
						// 			'empresa_id' => $this->empresa->id,
						// 			'estemp_id' => $atividadeA->estemp_id,
						// 			'periodo_apuracao' => str_replace('/', '', $input['periodo_apuracao']),
						// 			'critica' => 'Valor Sped Extra Apuração e DIFAL estão diferentes - id atividades: '.$atividadeA->id.' e '.$atividadeB->id
						// 			]
						// 		);
						// 	}
						// } else {
						// 	/*
						// 	* 3a regra:
						// 	* Sped Fiscal[tributo_id=1] (Valor Recolhido ou a Recolher, extra-apuração)->atividadesA-vlr_recibo_5+vlr_recibo_3 != ICMS[tributo_id=8] (Valor ICMS/DIFAL por Fora)->atividadesB-vlr_recibo_4+vlr_recibo_1
						// 	*/
						// 	$valorSped = $atividadeA->vlr_recibo_5+$atividadeA->vlr_recibo_3;
						// 	$valorICMSDIFAL = $atividadeB->vlr_recibo_4+$atividadeB->vlr_recibo_1;
						// 	if ($valorSped != $valorICMSDIFAL) {
						// 		DB::table('criticasvalores')->insert(
						// 			[
						// 			'empresa_id' => $this->empresa->id, 
						// 			'estemp_id' => $atividadeA->estemp_id,
						// 			'periodo_apuracao' => str_replace('/', '', $input['periodo_apuracao']),
						// 			'critica' => 'Valor Sped Fiscal/Extra Apuração e ICMS/DIFAL estão diferentes - id atividades: '.$atividadeA->id.' e '.$atividadeB->id
						// 			]
						// 		);
						// 	}
						// }
					}
					
					// Procura atividade com Guia ICMS sem Guia Sped
					$atividadesICMS = $this->getAtividades($tributoB, $periodo, $ufs, $this->empresa->id, $retIDs);
				
					if (count($atividadesICMS) > 0) {
						foreach ($atividadesICMS as $i => $atividadeICMS) {
							$atividadeSped = $this->getAtividade($tributoA, $periodo, array($atividadeICMS->uf), $this->empresa->id, $atividadeICMS->estemp_id, $retIDs);
							
							if (count($atividadeSped) < 1) {
								/*
								* exceção a  regra:
								* Sped Fiscal[tributo_id=1] (Valor Total do ICMS)->atividadesA-vlr_recibo_3 não possui Guia de ICMS
								*/
								if ($atividadeICMS->vlr_recibo_1 > 0) {
									DB::table('criticasvalores')->insert(
										[
										'empresa_id' => $this->empresa->id,
										'estemp_id' => $atividadeICMS->estemp_id,
										'periodo_apuracao' => str_replace('/', '', $input['periodo_apuracao']),
										'critica' => 'Valor ICMS - id atividades: '.$atividadeICMS->id.' não possui Guia de Sped'
										]
									);
								}
							}
						}
					}
					
					$tributosA = Tributo::selectRaw('nome, id')->whereRaw('id IN (1)')->pluck('nome', 'id');
					$tributosB = Tributo::selectRaw('nome, id')->whereRaw('id IN (8)')->pluck('nome', 'id');
					
					$table = DB::select(
						'SELECT criticasvalores.criticasvalores_id,
								criticasvalores.empresa_id,
								empresas.razao_social, 
								criticasvalores.estemp_id,
								estabelecimentos.cnpj,
								estabelecimentos.codigo,
								criticasvalores.periodo_apuracao,
								criticasvalores.critica,
								criticasvalores.data_critica
						FROM criticasvalores
						LEFT JOIN empresas ON empresas.id = criticasvalores.empresa_id
						LEFT JOIN estabelecimentos ON estabelecimentos.empresa_id = empresas.id
						WHERE criticasvalores.empresa_id = :empid
							AND estabelecimentos.id = criticasvalores.estemp_id',
						['empid' => $this->empresa->id]
					);
					
					// Session::flash('status', 'Processamento finalizado com sucesso.');
					
					return view('validador.index')
						->with(compact('tributosA', 'tributosB', 'table'))
						->with('status', 'Processamento finalizado com sucesso.');
				} else {
					return redirect()->back()->with('status', 'Não foram encontradas atividades para essa validação.');
				}	
			} else {
				return redirect()->back()->with('status', 'Os critérios informados não satisfazem os requisitos para validação.');
			}
		}
	}
	
	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		//
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		//
	}
	
	private function getAtividade($tributoB, $periodo, $uf, $empid, $estempid, $retIds)
	{		
		$retIds = implode(",", $retIds);
		$retIds = substr($retIds, 1, strlen($retIds) - 2);
		$retIds = explode(",", $retIds);
		// var_dump($retIds);exit;
		$atividade = Atividade::select(
			'atividades.id',
			'atividades.regra_id',
			'municipios.uf',
			'atividades.emp_id',
			'atividades.estemp_id',
			'atividades.periodo_apuracao',
			'regras.tributo_id',
			'atividades.vlr_recibo_1',
			'atividades.vlr_recibo_3',
			'atividades.vlr_recibo_4',
			'atividades.vlr_recibo_5',
			'atividades.retificacao_id',
			'atividades.tipo_geracao'
		)
		->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
		->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
		->join('regras', 'atividades.regra_id', '=', 'regras.id')
		->whereIn('atividades.periodo_apuracao', $periodo)->whereIn('municipios.uf', $uf)
		->where('regras.tributo_id', $tributoB)->where('atividades.emp_id', $empid)->where('atividades.estemp_id', $estempid)
		->whereNotIn('atividades.id', $retIds)
		->orderBy('atividades.estemp_id', 'asc');
		// ->where('atividades.tipo_geracao', '!=', 'R')
		
		return $this->substituirAtividadesRetificadas($atividade);
	}
	
	private function getAtividades($tributoA, $periodo, $ufs, $empid, $retIds)
	{
		$retIds = implode(",", $retIds);
		$retIds = substr($retIds, 1, strlen($retIds) - 2);
		$retIds = explode(",", $retIds);
		// var_dump($retIds);exit;
		$atividades = Atividade::select(
			'atividades.id',
			'atividades.regra_id',
			'municipios.uf',
			'atividades.emp_id',
			'atividades.estemp_id',
			'atividades.periodo_apuracao',
			'regras.tributo_id',
			'atividades.vlr_recibo_1',
			'atividades.vlr_recibo_3',
			'atividades.vlr_recibo_4',
			'atividades.vlr_recibo_5',
			'atividades.retificacao_id',
			'atividades.tipo_geracao'
		)
		->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
		->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
		->join('regras', 'atividades.regra_id', '=', 'regras.id')
		->whereIn('atividades.periodo_apuracao', $periodo)->whereIn('municipios.uf', $ufs)
		->where('regras.tributo_id', $tributoA)->where('atividades.emp_id', $empid)
		->whereNotIn('atividades.id', $retIds)
		->orderBy('atividades.estemp_id', 'asc');
		// ->where('atividades.tipo_geracao', '!=', 'R')

		return $this->substituirAtividadesRetificadas($atividades);
	}

	private function getAtividadesRetificadas($periodo, $ufs, $empid) {
		$atividades = Atividade::select(
			'atividades.id',
			'atividades.regra_id',
			'municipios.uf',
			'atividades.emp_id',
			'atividades.estemp_id',
			'atividades.periodo_apuracao',
			'regras.tributo_id',
			'atividades.vlr_recibo_1',
			'atividades.vlr_recibo_3',
			'atividades.vlr_recibo_4',
			'atividades.vlr_recibo_5',
			'atividades.retificacao_id',
			'atividades.tipo_geracao'
		)
		->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
		->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
		->join('regras', 'atividades.regra_id', '=', 'regras.id')
		->whereIn('atividades.periodo_apuracao', $periodo)
		->whereIn('municipios.uf', $ufs)
		->whereNotNull('atividades.retificacao_id')->where('atividades.emp_id', $empid);

		$atividadesRetificacao = array();

		foreach($atividades->get() as $atividade) {
			$atividadesRetificacao[$atividade->retificacao_id] = $atividade;
		}

		return $this->atividadesRetificadas = $atividadesRetificacao;
	}
	private function getIdAtividadesRetificadas($periodo, $ufs, $empid) {
		$atividades = Atividade::select('atividades.retificacao_id')
		->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
		->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
		->join('regras', 'atividades.regra_id', '=', 'regras.id')
		->whereIn('atividades.periodo_apuracao', $periodo)
		->whereIn('municipios.uf', $ufs)
		->whereNotNull('atividades.retificacao_id')
		->where('atividades.emp_id', $empid)
		->pluck('atividades.retificacao_id');

		return $atividades;
	}

	private function substituirAtividadesRetificadas($atividades) {
		$atividades = $atividades->get();
		// foreach($atividades as $key => $atividade) {
		// 	if(isset($this->atividadesRetificadas[$atividade->id])) {
		// 		$atividades[$key] = $this->atividadesRetificadas[$atividade->id];
		// 	}
		// }
		return $atividades;
	}
	
	private function calcPeriodo($inicio, $fim)
	{
		$dataBusca['periodo_inicio'] = $inicio;
		$dataBusca['periodo_fim'] = $fim ;
		$dataBusca['periodo_inicio'] = str_replace('/', '-', '01/'.$dataBusca['periodo_inicio']);
		$dataBusca['periodo_fim'] = str_replace('/', '-', '01/'.$dataBusca['periodo_fim']);
		list($dia, $mes, $ano) = explode("-", $dataBusca['periodo_inicio']);
		$dataBusca['periodo_inicio'] = getdate(strtotime($dataBusca['periodo_inicio']));
		$dataBusca['periodo_fim'] = getdate(strtotime($dataBusca['periodo_fim']));
		$dif = ( ($dataBusca['periodo_fim'][0] - $dataBusca['periodo_inicio'][0]) / 86400 );
		$meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array

		for ($x = 0; $x < $meses; $x++) {
			$datas[] =  date("mY", strtotime("+".$x." month", mktime(0, 0, 0, $mes, $dia, $ano)));
		}

		return $datas;
	}
}
