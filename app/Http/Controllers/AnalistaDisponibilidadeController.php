<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtividadeAnalista;
use App\Models\AtividadeAnalistaFilial;
use App\Models\AnalistaDisponibilidade;
use App\Models\Tributo;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Municipio;
use App\Models\Role;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use Helper;

class AnalistaDisponibilidadeController extends Controller
{
    public $answerPath;
    protected $s_emp = null;

    public function __construct(Request $request = null)
    {
        if (!Auth::guest() && !empty(session()->get('seid')))
            $this->s_emp = Empresa::findOrFail(session('seid'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = User::findOrFail(Auth::user()->id);

        $query = 'SELECT 
                        A.id,
                        B.razao_social as razao_social,
                        G.name as usuario_analista,
						A.periodo_apuracao,
                        A.data_ini_disp,
                        A.data_fim_disp,
                        A.qtd_min_disp_dia
                    FROM
                        analistadisponibilidade A
                            INNER JOIN
                        empresas B ON A.Empresa_id = B.id
                            INNER JOIN 
                        users G ON A.id_usuarioanalista = G.id';

        if (@$this->s_emp->id && !$user->hasRole('admin')) {
            $query .= ' WHERE
                        B.id = '.$this->s_emp->id.'';
        }

        $query .= ' GROUP BY B.razao_social , A.id, usuario_analista';

        $table = DB::select($query);

        $table = json_decode(json_encode($table),true);
        return view('analistadisponibilidade.index')->with('table', $table);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //carregando dados da tela
        $ids = '2,4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->pluck('name','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');

        return view('analistadisponibilidade.adicionar')->withAnalistas($usuarios)->withEmpresas($empresas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $situation = 'status';
        $message = 'Disponbilidade do analista registrada com sucesso';

        $ids = '2,4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->pluck('name','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $var = array();

        $input = $request->all();

		if (!$this->validation($input)) {
			return redirect()->back()->with('alert', 'Já existe tempo de atividade cadastrado para este conjunto de Tributo, Empresa e UF analista.');
        }
        $input["periodo_apuracao"] = str_replace("/","",$input["periodo_apuracao"]);
		$create = AnalistaDisponibilidade::create($input);

		return view('analistadisponibilidade.adicionar')->withAnalistas($usuarios)->withEmpresas($empresas)->with("message", $message)->with("status", $situation);
    }

    public function validation($array)
    {
        $find = DB::table('analistadisponibilidade')->select('*')
            ->where('id_usuarioanalista', $array['id_usuarioanalista'])
            ->whereIn('periodo_apuracao', [$array['periodo_apuracao'], str_replace("/","",$array["periodo_apuracao"]), intval($array['periodo_apuracao']) ])
            ->where('empresa_id', $array['empresa_id'])->get();

        $find = json_decode(json_encode($find),true);

        if (count($find) > 0) {
            return false;
        }

        return true;
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
    public function edit(Request $request)
    {
        $situation = 'status';
        $message = 'Registro atualizado com sucesso';
        //carregando dados da tela
        $ids = '2,4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->pluck('name','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $input = $request->all();

		$analistaDisponibilidade = AnalistaDisponibilidade::findOrFail($input['id']);
		$analistaDisponibilidade->fill($input)->save();

        $dados = json_decode(json_encode(AnalistaDisponibilidade::findOrFail($input['id'])),true);

        return view('analistadisponibilidade.editar')->withAnalistas($usuarios)->withEmpresas($empresas)->with($situation, $message)->with('dados', $dados)->with('returning', true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function editRLT(Request $request)
    {
        $situation = 'status';
        $message = 'Registro carregado com sucesso';
        foreach ($request->all() as $key => $value) {
            $privateid = $key;
        }
        //carregando dados da tela

        $ids = '2,4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->pluck('name','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
		
        $dados = json_decode(json_encode(AnalistaDisponibilidade::findOrFail($privateid)),true);

        return view('analistadisponibilidade.editar')->withAnalistas($usuarios)->withEmpresas($empresas)->with($situation, $message)->with('dados', $dados)->with('returning', true);
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
		if (!empty($id) || $id >= 0) {
			$AnalistaDisponibilidade = AnalistaDisponibilidade::where('id', $id)->get();
			if ($AnalistaDisponibilidade->count() > 0) {
				AnalistaDisponibilidade::where('id', $id)->delete();
				return redirect()->back()->with('status', 'Limite de disponibilidade para o analista excluida com sucesso.');
			} else {
				return redirect()->back()->with('error', 'Ocorreu um erro. Cadastro de disponibilidade não existe. ');
			}
		} else {
			return redirect()->back()->with('error', 'Ocorreu um erro ao tentar excluir o cadastro da disponibilidade para o analista. Não recebemos a sua identificação');
		}
		return redirect()->back()->with('error', 'Erro não especificado.');
    }
}
