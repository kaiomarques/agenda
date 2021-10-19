<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use App\Models\Mensageriaprocadm;
use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\FeriadoEstadual;
use App\Models\FeriadoMunicipal;
use App\Models\Movtocontacorrente;
use App\Services\EntregaService;
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


class MensageriaprocadmsController extends Controller
{
	protected $eService;

    function __construct(EntregaService $service)
    {
        $this->eService = $service;
    }
    //
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request = null)
    {
    	$situacao = array('A' => 'Ativo', 'I'=>'Inativo');
    	$rolesObject = Role::all(['id', 'name'])->pluck('name', 'id');
    	$roles[0] = 'Selecione';
    	foreach($rolesObject as $key => $role){
    		$roles[$key] = $role;
    	}
       return view('mensageriaprocadms.create')
       ->with('roles', $roles)
       ->with('situacao', $situacao);
    }

    public function SearchRole()
    {
    	if (empty($_GET['role_id'])) {
            echo json_encode(array('success'=>false, 'data'=>array()));
            exit;
        }

        $mensageria = Mensageriaprocadm::where('role_id', '=', $_GET['role_id'])->first();
        if (!$mensageria) {
            echo json_encode(array('success'=>false, 'data'=>array()));
            exit;
        }

        echo json_encode(array('success'=>true, 'data'=>$mensageria));
        exit;
    }

    public function Job()
    {
    	$mensagerias = Mensageriaprocadm::all(['role_id', 'parametro_qt_dias'])->pluck('parametro_qt_dias', 'role_id');
    	foreach($mensagerias as $role_id => $parametro_qt_dias) {
    		$this->findProcessosMensageria($role_id, $parametro_qt_dias);
    	}
    }

    public function findProcessosMensageria($role_id, $dias)
    {
    	$processos = DB::select("select a.* FROM processosadms a inner join observacaoprocadms b ON b.id = (select id FROM observacaoprocadms where processoadm_id = a.id AND datediff(DATE_FORMAT(NOW(), '%Y/%m/%d'), DATE_FORMAT(b.created_at, '%Y/%m/%d')) = ".$dias." ORDER BY created_at DESC LIMIT 1)");
    	
    	foreach($processos as $processo)
    	{
    		$processo->dias_diferenca = $dias;
    		$this->enviarEmailProcessoMensageria($processo, $role_id);
    	}
    }

    public function enviarEmailProcessoMensageria($array, $role_id)
    {
    	$emails = DB::select("select email, name from users a inner join role_user b ON a.id = b.user_id where b.role_id = ".$role_id);
    	
    	if (empty($emails[0])) {
    		return;
    	}

        $var = DB::select("select B.cnpj, B.codigo, C.razao_social from processosadms A inner join estabelecimentos B on A.estabelecimento_id = B.id inner join empresas C on B.empresa_id = C.id where A.id = ".$array->id."");
        
        $var = json_decode(json_encode($var),true);
        foreach ($var as $t) {
        }

        $formatedVar  = 'Empresa: '. $t['razao_social'].' - CNPJ: '. $t['cnpj'] . ' Código da área: '.$t['codigo'];

        $subject = "BravoTaxCalendar - Processo ".$array->id." sem atualização.";
    	$data['nro_processo'] = $array->id;
    	$data['dias'] 	      = $array->dias_diferenca;		
    	$data['subject']      = $subject;
        $data['dadosEmp']     = $formatedVar;

    	foreach($emails as $user)
    	{
    		$this->eService->sendMail($user, $data, 'emails.notificacao-processos');
    	}
    	return;
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
            'role_id' => 'required',
            'parametro_qt_dias' => 'required|multiplo_cinco'
        ],
        $messages = [
            'role_id.required' => 'Informar tipo de usuário',
            'parametro_qt_dias.required' => 'Informar quantidade de dias',
            'parametro_qt_dias.multiplo_cinco' => 'Informar um valor multiplo de 5 para quantidade de dias'
        ]);

        $input['usuario_update']    = Auth::user()->email;

        $mensageria = Mensageriaprocadm::where('role_id', '=', $input['role_id'])->first();
        if ($mensageria) {
        	$mensageriaEdit = Mensageriaprocadm::findOrFail($mensageria->id);
        	$mensageriaEdit->fill($input)->save();
        	$mensagem = 'Registro atualizado com sucesso';
        } else {
        	Mensageriaprocadm::create($input);
        	$mensagem = 'Registro adicionado com sucesso';
        }
        
        return redirect()->back()->with('status', $mensagem);
    }
}
