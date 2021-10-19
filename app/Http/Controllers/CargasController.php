<?php

namespace App\Http\Controllers;

use App\Models\Estabelecimento;
use Auth;
use DB;
use App\Models\Empresa;
use App\Models\User;
use App\Services\EntregaService;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;
use Session;

date_default_timezone_set(config('configICMSVars.wamp.timezone_brt'));

class CargasController extends Controller
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

    public function index(Request $request)
    {
        if ($request->has('switch_val')) {
            $switch = $request->input('switch_val');
        } else {
            $switch = 2;
        }
        //var_dump($request);
        return view('cargas.msaf-load')->withSwitch($switch);
    }
    public function getUser()
    {
        if (empty($_GET['userID'])) {
            $userID = 0;
        } else {
            $userID = $_GET['userID'];
        }
        
        $user = User::select('email')->where('id', $userID)->get();

        if (!$user) {
            echo json_encode(array('success'=>false, 'data'=>array('user'=>$user)));
            exit;
        }

        echo json_encode(array('success'=>true, 'data'=>array('user'=>$user)));
        exit;
    }

    public function anyData(Request $request)
    {
        $seid = $this->s_emp->id;

        //$estabelecimentos = Estabelecimento::select('*')->where('empresa_id', $seid)->with('municipio');

         $estabelecimentos = Estabelecimento::leftjoin('users as UserEntrada', 'estabelecimentos.Id_usuario_entrada', '=', 'UserEntrada.id')->leftjoin('users as UserSaida', 'estabelecimentos.Id_usuario_saida', '=', 'UserSaida.id')->select('estabelecimentos.*', 'UserEntrada.email as userEmailEntrada', 'UserSaida.email as userEmailSaida')->where('empresa_id', $seid)->with('municipio');

        //echo '<Pre>';print_r($estabelecimentos);exit;
        $filter = $request->get('ativo');

        if ($filter == 0 || $filter == 1) {
            $estabelecimentos->where('carga_msaf_entrada',$filter)->where('carga_msaf_saida',$filter);
        }

        if ($filter == 3) {
            $estabelecimentos->where('carga_msaf_entrada', 1)->where('ativo', 1);
        }
        
        if ($filter == 4) {
            $estabelecimentos->where('carga_msaf_entrada', 0)->where('ativo', 1);
        }
        
        if ($filter == 5) {
            $estabelecimentos->where('carga_msaf_saida', 1)->where('ativo', 1);
        }
        
        if ($filter == 6) {
            $estabelecimentos->where('carga_msaf_saida', 0)->where('ativo', 1);
        }

        return Datatables::of($estabelecimentos)->make(true);
    }

    public function grafico()
    {
        $seid = $this->s_emp->id;
        $Grupo_Empresa = new GrupoEmpresasController;
        $emps = $Grupo_Empresa->getEmpresas($seid);
        $emps = explode(',', $emps);
        $first = DB::table('estabelecimentos')
            ->select(DB::raw('count(*) as TOT,  "E" as TIPO'))
            ->whereIn('empresa_id', $emps)
            ->where('carga_msaf_entrada',1);
        $second = DB::table('estabelecimentos')
            ->select(DB::raw('count(*) as TOT,  "S" as TIPO'))
            ->whereIn('empresa_id', $emps)
            ->where('carga_msaf_saida',1);
        $third = DB::table('estabelecimentos')
            ->select(DB::raw('count(*) as TOT,  "C" as TIPO'))
            ->whereIn('empresa_id', $emps)
            ->where('carga_msaf_entrada',1)
            ->where('carga_msaf_saida',1);

        $grafico = DB::table('estabelecimentos')
            ->select(DB::raw('count(*) as TOT,  "T" as TIPO'))
            ->whereIn('estabelecimentos.empresa_id', $emps)
            ->union($first)->union($second)->union($third)
            ->get();

        $retval = array();
        foreach($grafico as $el) {
            $retval[$el->TIPO] = $el->TOT;
        }

        return view('cargas.grafico')->with('graph_data',$retval);
    }

    public function resetData()
    {
        $estabelecimentos = Estabelecimento::all();
        foreach ($estabelecimentos as $el) {
            $el->carga_msaf_entrada = 0;
            $el->carga_msaf_saida = 0;
            $el->Id_usuario_saida = NULL;
            $el->Id_usuario_entrada = NULL;
            $el->Dt_alteracao_entrada = '0000-00-00 00:00:00';
            $el->Dt_alteracao_saida = '0000-00-00 00:00:00';
            $el->save();
        };

        return redirect()->back()->with('status', 'Todos os status de carga foram alterados!');
    }

    public function atualizarEntrada()
    {
        $hoje = date('Y-m-d H:i:s');
        $user_id = Auth::user()->id;

        DB::table('estabelecimentos')
            ->where('empresa_id', $this->s_emp->id)
            ->update(['carga_msaf_entrada' => 1, 'Dt_alteracao_entrada' => $hoje, 'Id_usuario_entrada' => $user_id]);

        return redirect()->back()->with('status', 'O status de carga foi alterado para todos os estabelecimentos da empresa ');
    }

    public function changeStateEntrada($status,$id) {

        $estabelecimento = Estabelecimento::findOrFail($id);
        if ($status=='1') {

            $estabelecimento->carga_msaf_entrada = 1;

        } else if ($status=='0') {

            $estabelecimento->carga_msaf_entrada = 0;

        }

        $hoje = date('Y-m-d H:i:s');
        $user_id = Auth::user()->id;
        $estabelecimento->Dt_alteracao_entrada = $hoje;
        $estabelecimento->Dt_alteracao_saida = $estabelecimento->Dt_alteracao_saida;
        $estabelecimento->Id_usuario_entrada = $user_id;
        $estabelecimento->Id_usuario_saida = $estabelecimento->Id_usuario_saida;
        $estabelecimento->save();

        return redirect()->back()->with('status', 'O status de carga foi alterado para o estabelecimento '.$estabelecimento->codigo.'!');
    }

    public function changeStateSaida($status,$id) {
        
        $estabelecimento = Estabelecimento::findOrFail($id);
        if ($status=='1') {

            $estabelecimento->carga_msaf_saida = 1;

        } else if ($status=='0') {

            $estabelecimento->carga_msaf_saida = 0;

        }

        $hoje = date('Y-m-d H:i:s');

        $user_id = Auth::user()->id;
        $estabelecimento->Dt_alteracao_entrada = $estabelecimento->Dt_alteracao_entrada;
        $estabelecimento->Dt_alteracao_saida = $hoje;
        $estabelecimento->Id_usuario_entrada = $estabelecimento->Id_usuario_entrada;
        $estabelecimento->Id_usuario_saida = $user_id;
        $estabelecimento->save();

        return redirect()->back()->with('status', 'O status de carga foi alterado para o estabelecimento '.$estabelecimento->codigo.'!');
    }

}
