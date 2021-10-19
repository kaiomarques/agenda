<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\CronogramaStatus;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Models\FeriadoEstadual;
use App\Models\FeriadoMunicipal;
use App\Services\EntregaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Input;

class EmpresasController extends Controller
{
    protected $eService;

    public function __construct(EntregaService $service)
    {
        $this->middleware('auth');
        $this->eService = $service;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('empresas.index');
    }

    public function anyData(Request $request)
    {
	
        $empresas = Empresa::select('*')->with('municipio');

        if ( isset($request['search']) && $request['search']['value'] != '' ) {
        $str_filter = $request['search']['value'];
    }

        return Datatables::of($empresas)->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //$municipios = Municipio::lists('nome', 'codigo');
        $municipios = Municipio::selectRaw("concat(nome, ' - ', uf) as nome_and_uf, codigo")->orderBy('nome')->pluck('nome_and_uf', 'codigo');

        return view('empresas.create')->with('municipios', $municipios);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $this->validate($request, [
            'cnpj' => 'required|size:18|valida_cnpj_raiz|valida_cnpj|valida_cnpj_unique',
            'razao_social' => 'required',
            'cod_municipio' => 'required'
        ],
        $messages = [
            'cnpj.valida_cnpj_raiz' => 'O CNPJ inserido não é um cnpj de tipo "raiz".',
            'cnpj.valida_cnpj' => 'O CNPJ é inválido.',
            'cnpj.valida_cnpj_unique' => 'O CNPJ inserido é já cadastrado.'
        ]);

        $input = $request->all();
        $input['cnpj']= preg_replace("/[^0-9]/","",$input['cnpj']);  //Eliminate the CNPJ MASK - Only numbers will be written on DB

        Empresa::create($input);

        return redirect()->back()->with('status', 'Empresa adicionada com sucesso!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $empresa = Empresa::findOrFail($id);
        //$feriados_nacionais = $this->_getFeriadosNacionais();
        //$feriados_estaduais = $this->_getFeriadosEstaduais($id);
        //$feriados_municipais = $this->_getFeriadosMunicipais($id);
        //$entregas = $this->eService->calculaProximasEntregasEstemp($empresa->cnpj);
        $atividades = Atividade::where('estemp_type','emp')->where('estemp_id',$id)->where('status','<',3)->get();

        $empresa = Empresa::findOrFail($id);
        $empresa_tributos = $empresa->tributos()->get();
        $array_tributos_ativos = array();
        foreach($empresa_tributos as $at) {
            $array_tributos_ativos[] = $at->id;
        }

        $tributos = Tributo::selectRaw("nome, id")->whereIN('id',$array_tributos_ativos)->pluck('nome','id');

        return view('empresas.show')->withEmpresa($empresa)->withAtividades($atividades)->withTributos($tributos);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $empresa = Empresa::findOrFail($id);
        $municipios = Municipio::selectRaw("concat(nome, ' - ', uf) as nome_and_uf, codigo")->orderBy('nome')->pluck('nome_and_uf', 'codigo');
        $users = User::selectRaw("name, id")->pluck('name','id');
        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        return view('empresas.edit')->withEmpresa($empresa)->with('municipios', $municipios)->with('users', $users)->with('tributos',$tributos);
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
        $empresa = Empresa::findOrFail($id);

        $input = $request->all();

        $add_validation = '';
        if ($empresa['cnpj'] != preg_replace("/[^0-9]/","",$input['cnpj'])) {
            $add_validation = '|valida_cnpj|valida_cnpj_raiz|valida_cnpj_unique';
        }

        $this->validate($request, [
            'cnpj' => 'required|size:18'.$add_validation,
            'razao_social' => 'required',
            'cod_municipio' => 'required'
        ],
            $messages = [
                'cnpj.valida_cnpj_raiz' => 'O CNPJ inserido não é um cnpj de tipo "raiz".',
                'cnpj.valida_cnpj' => 'O CNPJ é inválido.',
                'cnpj.valida_cnpj_unique' => 'O CNPJ inserido é já cadastrado.'
        ]);

        $input['cnpj']= preg_replace("/[^0-9]/","",$input['cnpj']);

        $empresa->fill($input)->save();

        $users = $request->input('multiple_select_users');
        if ($users) {
            $empresa->users()->sync($users);
        } else {
            $empresa->users()->detach();
        }

        //MULTISELECT
        $tributos = $request->input('multiple_select_tributos');
        if ($tributos) {
            $empresa->tributos()->sync($tributos);
        } else {
            $empresa->tributos()->detach();
        }


        return redirect()->back()->with('status', 'Empresa atualizada com sucesso!');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $empresa = Empresa::findOrFail($id);
        $ativid_relacionadas = Atividade::first()->where('estemp_type','emp')->where('estemp_id',$empresa->id);

        if (empty($ativid_relacionadas)) {
            $empresa->delete();
            return redirect()->route('empresas.index')->with('status', 'Empresa cancelada com sucesso!');
        } else {
            return redirect()->back()->with('status', 'Empresa com movimentação, impossível cancelar!');
        }



    }

    public function ajax(){
        switch($_GET['action']){
        case 1: 
            $tributo = DB::table('tributos')->where('id', $_GET['idTributo'])->value('tipo');
            die(json_encode($tributo));
            break;
        case 2: 
            $municipio = DB::table('municipios')->where('uf', $_GET['estado'])->select('codigo')->addSelect('nome')->get();
            die(json_encode($municipio));
            break;
        }
    }

    public function geracao($periodo, $id_emp, $tributo = null, $ref = null) {
        if(isset($tributo)){
            $empresa = Empresa::findOrFail($id_emp);
            $warning = true; // WARNING para periodo anterior não gerado
            $knownDate = Carbon::create((int)substr($periodo,-4,4), (int)substr($periodo,0,2), 1, 0, 0);
            $periodo_apuracao_anterior = $knownDate->subMonth()->format('mY');
            if (Cron::where('periodo_apuracao', $periodo_apuracao_anterior)->count() >0) {
                $warning = false;
            }
            if ($warning) {
                $exitCode = "Periodo anterior ($periodo_apuracao_anterior) não gerado.";
            } else {
                Artisan::call('generate:all', [
                    'periodo' => $periodo, 'empresa' => $empresa->cnpj, 'tributo' => $tributo, 'ref' => $ref
                ]);

                $exitCode = Artisan::output();
            }

            return redirect()->back()->with('status', $exitCode);

        }else{
            $empresa = Empresa::findOrFail($id_emp);
            $warning = true; // WARNING para periodo anterior não gerado
            if (strlen($periodo) == 4) {
                $knownDate = Carbon::create($periodo,1,1,0,0);
            } else {
                $knownDate = Carbon::create((int)substr($periodo,-4,4),(int)substr($periodo,0,2),1,0,0);
            }
            $periodo_apuracao_anterior = $knownDate->subMonth()->format('mY');

            if (Cron::where('periodo_apuracao', $periodo_apuracao_anterior)->count() >0) {
                $warning = false;
            }
            if ($warning) {
                $exitCode = "Periodo anterior ($periodo_apuracao_anterior) não gerado.";
            } else {
                Artisan::call('generate:all', [
                    'periodo' => $periodo, 'empresa' => $empresa->cnpj
                ]);

                $exitCode = Artisan::output();
            }

            return redirect()->back()->with('status', $exitCode);
        }

    }

    private function _getFeriadosNacionais()
    {
        $formatoDataDeComparacao    =  "d-m"; // Dia / Mês
        $ano = intval(date('Y'));

        $pascoa = easter_date($ano); // Limite de 1970 ou após 2037 da easter_date PHP consulta http://www.php.net/manual/pt_BR/function.easter-date.php
        $dia_pascoa = date('j', $pascoa);
        $mes_pascoa = date('n', $pascoa);
        $ano_pascoa = date('Y', $pascoa);

        $feriados = array(
            // Tatas Fixas dos feriados Nacionail Basileiras
            date($formatoDataDeComparacao ,mktime(0, 0, 0, 1, 1, $ano)), // Confraternização Universal - Lei nº 662, de 06/04/49
            date($formatoDataDeComparacao ,mktime(0, 0, 0, 4, 21, $ano)), // Tiradentes - Lei nº 662, de 06/04/49
            date($formatoDataDeComparacao ,mktime(0, 0, 0, 5, 1, $ano)), // Dia do Trabalhador - Lei nº 662, de 06/04/49
            date($formatoDataDeComparacao ,mktime(0, 0, 0, 9, 7, $ano)), // Dia da Independência - Lei nº 662, de 06/04/49
            date($formatoDataDeComparacao ,mktime(0, 0, 0, 10, 12, $ano)), // N. S. Aparecida - Lei nº 6802, de 30/06/80
            date($formatoDataDeComparacao ,mktime(0, 0, 0, 11, 2, $ano)), // Todos os santos - Lei nº 662, de 06/04/49
            date($formatoDataDeComparacao ,mktime(0, 0, 0, 11, 15, $ano)), // Proclamação da republica - Lei nº 662, de 06/04/49
            date($formatoDataDeComparacao ,mktime(0, 0, 0, 12, 25, $ano)), // Natal - Lei nº 662, de 06/04/49

            // These days have a date depending on easter
            //date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 48, $ano_pascoa)),//2ºferia Carnaval
            date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 47, $ano_pascoa)),//3ºferia Carnaval
            date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 2, $ano_pascoa)),//6ºfeira Santa
            date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa, $ano_pascoa)),//Pascoa
            date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa + 60, $ano_pascoa)),//Corpus Christ
        );
        return $feriados;
    }

    private function _getFeriadosEstaduais($id)
    {
        $empresa = Empresa::findOrFail($id);

        $cod_municipio = $empresa->cod_municipio;
        $uf = $empresa->municipio->uf;

        $retval = FeriadoEstadual::where('uf', '=', $uf)->get();

        $feriados_estaduais = explode(';',$retval->first()->datas);

        return $feriados_estaduais;

    }

    private function _getFeriadosMunicipais($id)
    {
        $empresa = Empresa::findOrFail($id);

        $cod_municipio = $empresa->cod_municipio;

        $retval = FeriadoMunicipal::where('municipio_id', '=', $cod_municipio)->get();

        return $retval;

    }
}
