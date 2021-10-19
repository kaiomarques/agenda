<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Estabelecimento;
use App\Models\Empresa;
use App\Models\Municipio;
use App\Models\FeriadoEstadual;
use App\Models\FeriadoMunicipal;
use App\Models\Regra;
use App\Models\Tributo;
use Illuminate\Http\Request;
use App\Services\EntregaService;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Auth;
use DB;
use Session;

class EstabelecimentosController extends Controller
{
    protected $eService;
    protected $s_emp = null;

    public function __construct(EntregaService $service)
    {

        if (!session()->get('seid')) {
            Session::flash('warning', 'Nenhuma empresa selecionada, favor selecionar uma!');
			return redirect()->route('home', ['selecionar_empresa' => 1])->send();
        }

        $this->middleware('auth');
        $this->eService = $service;

        if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
            $this->s_emp = Empresa::findOrFail(session()->get('seid'));
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('estabelecimentos.index');
    }

    public function anyData()
    {
        $estabelecimentos = Estabelecimento::select('*')->with('municipio');
        return Datatables::of($estabelecimentos)->make(true);
    }

    public function searchArea()
    {
        if (empty($_GET['estabelecimento_id'])) {
            $_GET['estabelecimento_id'] = 0;
        }

        if (!empty($_GET['codigo_area'])) {
            $estabelecimento = Estabelecimento::where('codigo', '=', $_GET['codigo_area'])->where('empresa_id', $this->s_emp->id)->first();
        }

        if ($_GET['estabelecimento_id'] > 0) {
            $estabelecimento = Estabelecimento::where('id', '=', $_GET['estabelecimento_id'])->where('empresa_id', $this->s_emp->id)->first();
        }

        if (!$estabelecimento) {
            echo json_encode(array('success'=>false, 'data'=>array()));
            exit;
        }

        $municipio = Municipio::where('codigo', '=', $estabelecimento->cod_municipio)->first();
        echo json_encode(array('success'=>true, 'data'=>array('estabelecimento'=>$estabelecimento, 'municipio'=>$municipio)));
        exit;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $empresas = Empresa::selectRaw("cnpj, id")->pluck('cnpj','id');
        $empresasArray = array();
        $empresasArray[0] = 'Selecione uma empresa';
        foreach($empresas as $key => $empresa) {
            $empresasArray[$key] = $empresa;
        }

        $municipios = Municipio::selectRaw("concat(nome, ' - ', uf) as nome_and_uf, codigo")->orderBy('nome')->pluck('nome_and_uf', 'codigo');
        return view('estabelecimentos.create')->with('municipios', $municipios)->with('empresas', $empresasArray);
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
        $empresa_id = (int)$input['empresa_id'];
        $empresa = Empresa::findOrFail($empresa_id);

        $input['cnpj']= preg_replace("/[^0-9]/","",$input['cnpj']);

        $this->validate($request, [
            'cnpj' => 'required|size:18|valida_cnpj|valida_cnpj_unique:'.$empresa->cnpj,
            'razao_social' => 'required',
            'cod_municipio' => 'required',
            'empresa_id' => 'required',
            'empresa_id' => 'valida_envio_empresa'
        ],
        $messages = [
            'cnpj.valida_cnpj' => 'O CNPJ é inválido.',
            'cnpj.valida_cnpj_unique' => 'O CNPJ indicado é já cadastrado.',
            'empresa_id.valida_envio_empresa' => 'Informar Empresa'
        ]);

        Estabelecimento::create($input);

        return redirect()->back()->with('status', 'Estabelecimento adicionada com sucesso!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $estabelecimento = Estabelecimento::findOrFail($id);
        $empresa = Empresa::findOrFail($estabelecimento->empresa_id);
        $empresa_tributos = $empresa->tributos()->get();
        $array_tributos_ativos = array();
        foreach($empresa_tributos as $at) {
            $array_tributos_ativos[] = $at->id;
        }
        $tributos = Tributo::selectRaw("nome, id")->whereIN('id',$array_tributos_ativos)->where('tipo','E')->orWhere('tipo','M')->pluck('nome','id');

        $atividades = Atividade::where('estemp_type','estab')->where('estemp_id',$id)->where('status','<',3)->get();

        $bloqueios = $estabelecimento->regras;



        return view('estabelecimentos.show')->withEstabelecimento($estabelecimento)->withAtividades($atividades)->withTributos($tributos)->withBloqueios($bloqueios);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $estabelecimento = Estabelecimento::findOrFail($id);
        $municipios = Municipio::selectRaw("concat(nome, ' - ', uf) as nome_and_uf, codigo")->orderBy('nome')->pluck('nome_and_uf', 'codigo');
        $empresas = Empresa::lists('cnpj', 'id');

        return view('estabelecimentos.edit')->withEstabelecimento($estabelecimento)->with('municipios', $municipios)->with('empresas', $empresas);
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
        $input = $request->all();
        $empresa_id = (int)$input['empresa_id'];
        $empresa = Empresa::findOrFail($empresa_id);
        $estabelecimento = Estabelecimento::findOrFail($id);
        $input['cnpj']= preg_replace("/[^0-9]/","",$input['cnpj']);
        $input['ativo'] = (Input::has('ativo')) ? 1 : 0;

        // card Nº 417 - inicio

        $regra_cnpj = 'required|size:18|valida_cnpj|valida_cnpj_estab:'.$empresa->cnpj; // regra original
        $messages   = [
        	'cnpj.valida_cnpj'       => 'O CNPJ é inválido.',
        	'cnpj.valida_cnpj_estab' => 'Estabelecimento não pode ter o mesmo cnpj da matriz'
        ];

        if ($input['cnpj'] === $empresa->cnpj) { // se cnpj do form for igual ao da matriz, não preciso validar 'valida_cnpj_estab'
        	$regra_cnpj = 'required|size:18|valida_cnpj'; // se fosse matriz, não estava salvando os dados. card Nº 417
        	$messages   = [
        		'cnpj.valida_cnpj' => 'O CNPJ é inválido.'
        	];
        }

        // TODO: por algum motivo, as mensagens de erro não aparecem na tela
        // card Nº 417 - final

        $this->validate($request, [
            'cnpj' => $regra_cnpj,
            'razao_social' => 'required',
            'cod_municipio' => 'required',
            'empresa_id' => 'required'
        ], $messages);

        //$input = $request->all();
        //$input['cnpj']= preg_replace("/[^0-9]/","",$input['cnpj']);

        $estabelecimento->fill($input)->save();

        return redirect()->back()->with('status', 'Estabelecimento atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $estabelecimento = Estabelecimento::findOrFail($id);
        $ativid_relacionadas = Atividade::first()->where('estemp_type','estab')->where('estemp_id',$estabelecimento->id);

        if (empty($ativid_relacionadas)) {
            $estabelecimento->delete();
            return redirect()->route('estabelecimentos.index')->with('status', 'Estabelecimento cancelado com sucesso!');
        } else {
            return redirect()->back()->with('status', 'Estabelecimento com movimentação, impossível cancelar!');
        }

    }

    public function geracao($id_tributo,$id_estab,$periodo_ini,$periodo_fin) {

        $estabelecimento = Estabelecimento::findOrFail($id_estab);
        if ($periodo_ini==$periodo_fin) {
            Artisan::call('generate:single', [
                'cnpj' => $estabelecimento->cnpj, 'codigo' => $estabelecimento->codigo, 'tributo_id' => $id_tributo, 'periodo_ini' => $periodo_ini
            ]);
        } else {
            Artisan::call('generate:single', [
                'cnpj' => $estabelecimento->cnpj, 'codigo' => $estabelecimento->codigo, 'tributo_id' => $id_tributo, 'periodo_ini' => $periodo_ini, 'periodo_fin' => $periodo_fin
            ]);
        }
        $exitCode = Artisan::output();
        return redirect()->back()->with('status', $exitCode);

    }

    public function cronogramageracao($id_tributo,$id_estab,$periodo_ini,$periodo_fin) {
        $estabelecimento = Estabelecimento::findOrFail($id_estab);
        if ($periodo_ini==$periodo_fin) {
            Artisan::call('generatecronograma:single', [
                'cnpj' => $estabelecimento->cnpj, 'codigo' => $estabelecimento->codigo, 'tributo_id' => $id_tributo, 'periodo_ini' => $periodo_ini
            ]);
        }
        $exitCode = Artisan::output();
        return redirect()->back()->with('status', $exitCode);

    }


}
