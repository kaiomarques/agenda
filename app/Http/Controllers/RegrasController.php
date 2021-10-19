<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Regra;
use App\Models\Empresa;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;


class RegrasController extends Controller
{
	protected $eService;   
	public $msg;
	public $estabelecimento_id;

	function __construct(EntregaService $service)
	{
		$this->eService = $service;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		return view('regras.index');
	}

	public function anyData(Request $request)
	{
		$regras = Regra::
		with(
			[
				'tributo' => function ($q) {
					$q->select('id', 'nome');
				}
			]
		)->get();

		return Datatables::of($regras)->make(true);
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
		$regra = Regra::with('estabelecimentos')->findOrFail($id);
		$tributo = Tributo::findOrFail($regra->tributo_id);

		//Verifica data proxima entrega/as
		$datas_entrega = array();
		// Regras Especiais têm mais de um resultado
		if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

			$retval_array = $this->eService->calculaProximaDataRegrasEspeciais($regra->regra_entrega);

			foreach ($retval_array as $el) {

				$val = array('data'=>$el['data'],'desc'=>$el['desc']);
				$datas_entrega[]=$val;

			}

		} else {  // Regra standard

			$ref = $regra->ref;
			if ($municipio = Municipio::find($regra->ref)) {
				$ref = $municipio->nome.' ( '.$municipio->uf.' )';
			}
			$desc = $regra->tributo->nome.' '.$ref;

			$data = $this->eService->calculaProximaData($regra->regra_entrega);
			$val = array('data'=>$data,'desc'=>$desc);
			$datas_entrega[]=$val;

		}
		//Empresas/Estabelecimentos
		$estabs = array();
		$empresas = array();

		if ($tributo->tipo == 'F') { //Federal
			$empresas = Empresa::all();
		} else if ($tributo->tipo == 'E') { //Estadual
			$ref = $regra->ref;

			$empresas = DB::table('empresas')
				->select('empresas.id', 'empresas.cnpj', 'empresas.codigo', 'municipios.nome', 'municipios.uf')
				->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
				->where('municipios.uf', $ref)
				->get();

			$estabs = DB::table('estabelecimentos')
				->select('estabelecimentos.id', 'estabelecimentos.cnpj', 'estabelecimentos.codigo', 'municipios.nome', 'municipios.uf')
				->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
				->where('municipios.uf', $ref)
				->orderBy('estabelecimentos.codigo')
				->get();
		} else { //Municipal
			$ref = $regra->ref;
			$empresas = DB::table('empresas')
				->select('empresas.id','empresas.cnpj','empresas.codigo','municipios.nome', 'municipios.uf')
				->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
				->where('municipios.codigo', $ref)
				->get();
				
				$estabs = DB::table('estabelecimentos AS est')
				->select('est.id','est.cnpj','est.codigo', 'mun.nome', 'mun.uf')
				->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
				->where('mun.codigo', $ref)
				->orderBy('est.codigo')
				->get();

		}
		//Verifica Municipio
		if ($municipio = Municipio::find($regra->ref)) {
			$regra->ref = $municipio->nome.' ( '.$municipio->uf.' )';
		}

		$blacklist = array();  //Lista dos estab (id) que não estão ativos para esta regra
		foreach($regra->estabelecimentos as $el) {
			$blacklist[] = $el->id;
		}
		//var_dump($estabs);
		return view('regras.show')->withRegra($regra)->withTributo($tributo)->withEntregas($datas_entrega)->withEmpresas($empresas)->withEstabs($estabs)->withBlacklist($blacklist);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		$regra = Regra::findOrFail($id);
		$tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');

		return view('regras.edit')->withRegra($regra)->with('tributos', $tributos);
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
		$regra = Regra::findOrFail($id);
		$uf = DB::table('municipios')->select('uf')->distinct()->get();
		$codigos = DB::table('municipios')->select('codigo')->distinct()->get();
		$output = array_merge(array_map(function ($object) { return $object->uf; }, $uf), array_map(function ($object) { return $object->codigo; }, $codigos));
		$valid = implode(',', $output); //var_dump($valid);

		$this->validate($request, [
			'freq_entrega' => 'in:A,M',
			'ref' => 'in:MATRIZ,'.$valid,
			'regra_entrega' => array('required','valida_regra','regex:/(^["MS","QS","AS"]{2}\d["DF","DU"]{2}[+,-]{1}\d{2,4})(?!.)|(^["RE"]{2}\d{2})+$/')
		],
		$messages = [
				'freq_entrega.in' => 'A frequência de entrega pode ser A (Anual) ou M (Mensal).',
				'ref.in' => 'A referência aceita como input MATRIZ, a UF ou um CODIGO de municipio cadastrado.',
				'regra_entrega.regex' => 'O formato da regra está errado, verificar os possivéis formatos no manual.',
				'regra_entrega.valida_regra' => 'A regra não é valida, verificar no manual.'
		]);

		$input = $request->all();
		$input['ativo'] = (Input::has('ativo')) ? 1 : 0;
		$input['afds'] = (Input::has('afds')) ? 1 : 0;
		$regra->fill($input)->save();

		return redirect()->back()->with('status', 'Regra atualizada com sucesso!');
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

	/**
	 * Enable or Disable the specified regra from estabelecimento.
	 *
	 * @param  int  $regra_id
	 * @param  int  $estabelecimento_id
	 * @param  bool  $enable
	 * @return \Illuminate\Http\Response
	 */
	public function setBlacklist($regra_id, $estabelecimento_id, $enable) {
		$regra = Regra::with('estabelecimentos')->findOrFail($regra_id);
		if ($enable) {
			$regra->estabelecimentos()->attach($estabelecimento_id);
		} else {
			$regra->estabelecimentos()->detach($estabelecimento_id);
		}

		return redirect()->back()->with('status', 'Regra atualizada com sucesso!');
	}
	
	/**
	 * Enable or Disable the specified regra from estabelecimento.
	 *
	 * @param  int  $regra_id
	 * @param  array  $estabelecimento_id
	 * @param  bool  $active_type
	 * @return \Illuminate\Http\Response
	 */
	public function ruleEnableDisable($regra_id, $estabelecimento_id, $active_type) {	
		$e = explode(',', $estabelecimento_id);
		$regra = Regra::with('estabelecimentos')->findOrFail($regra_id);
		if ($active_type) {
			$regra->estabelecimentos()->attach($e);
		} else {
			$regra->estabelecimentos()->detach($e);
		}
		
		// echo "regra: ".$regra_id."<br>";
		// echo "estab: ".$estabelecimento_id."<br>";
		// echo "active: ".$active_type."<br>";
		// echo "e: ".print_r($e);
	}
}
