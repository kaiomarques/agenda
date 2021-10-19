<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\HistoricoContaCorrente;
use App\Models\Municipio;
use App\Models\FeriadoEstadual;
use App\Models\FeriadoMunicipal;
use App\Models\Movtocontacorrente;
use App\Services\EntregaService;
use App\Models\Statusprocadm;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Artisan;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use DB;

class HistoricocontacorrenteController extends Controller
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
    public function index($id)
    {
        $query = 'SELECT 
                    A.Id_contacorrente,
                    A.Alteracao_realizada,
                    B.email,
                    DATE_FORMAT(A.updated_at,"%d/%m/%Y %H:%i:%s") as updated_at
                FROM 
                    historicocontacorrente A 
                LEFT JOIN
                    users B on A.Id_usuario_alteracao = B.id 
                WHERE 
                    A.Id_contacorrente = '.$id.' order by A.updated_at desc';

        $dados = json_decode(json_encode(DB::select($query)),true);

    return view('historicocontacorrente.index')->with('dados', $dados);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request = null)
    {
       //  $status = Statusprocadm::all(['id', 'descricao'])->pluck('descricao', 'id');
       //  $data = $request->session()->all();
       //  $periodo_apuracao = '';
       //  if (!empty($data['periodo_apuracao'])) {
       //      $periodo_apuracao = $data['periodo_apuracao'];
       //      Session::forget('periodo_apuracao');
       //  }

       // return view('movtocontacorrentes.create')->with('periodo_apuracao', $periodo_apuracao)->with('status', $status);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($Alteracao_realizada, $Id_contacorrente, $Id_usuario_alteracao)
    {
        $input['Alteracao_realizada'] = $Alteracao_realizada;
        $input['Id_contacorrente'] = $Id_contacorrente;
        $input['Id_usuario_alteracao'] = $Id_usuario_alteracao;
        if (!empty($input['Alteracao_realizada'])) {
            historicocontacorrente::create($input);
        }
        return redirect()->back();
    }

    public function delete($id)
    {   
        //delete
        // if (!$id) {
        //     return redirect()->route('movtocontacorrentes.search')->with('error', 'Informar movto para excluir');
        // }

        // Movtocontacorrente::destroy($id);
        // return redirect()->route('movtocontacorrentes.search')->with('status', 'Registro excluido com sucesso!');
    }
}
