<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtividadeAnalista;
use App\Models\AtividadeAnalistaFilial;
use App\Models\TempoAtividade;
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

class TempoAtividadeController extends Controller
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
                        G.nome as Tributo,
                        A.uf,
						A.Qtd_minutos
                    FROM
                        tempoatividade A
                            INNER JOIN
                        empresas B ON A.Empresa_id = B.id
                            INNER JOIN 
                        tributos G ON A.Tributo_id = G.id';

        if (@$this->s_emp->id && !$user->hasRole('admin')) {
            $query .= ' WHERE
                        B.id = '.$this->s_emp->id.'';
        }

        $query .= ' GROUP BY B.razao_social , A.id, Tributo';

        $table = DB::select($query);

        $table = json_decode(json_encode($table),true);
        return view('tempoatividade.index')->with('table', $table);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //carregando dados da tela
        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $ufs = Municipio::selectRaw("distinct(uf) as uf")->pluck('uf','uf');

        return view('tempoatividade.adicionar')->withTributos($tributos)->withEmpresas($empresas)->withUfs($ufs);
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
        $message = 'Registro inserido com sucesso';

        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $ufs = Municipio::selectRaw("distinct(uf) as uf")->pluck('uf','uf');
        $var = array();

        $input = $request->all();

		if (!$this->validation($input)) {
			return redirect()->back()->with('alert', 'Já existe tempo de atividade cadastrado para este conjunto de Tributo, Empresa e UF analista.');
		}
		$create = TempoAtividade::create($input);

		return view('tempoatividade.adicionar')->withTributos($tributos)->withEmpresas($empresas)->withUfs($ufs)->with("message", $message)->with("status", $situation);
    }

    public function validation($array)
    {
        $find = DB::table('tempoatividade')->select('*')
            ->where('Tributo_id', $array['Tributo_id'])
            ->where('uf', $array['UF'])
            ->where('Empresa_id', $array['Empresa_id'])->get();

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
        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $ufs = Municipio::selectRaw("distinct(uf) as uf")->pluck('uf','uf');
        $input = $request->all();

		$tempoAtividade = TempoAtividade::findOrFail($input['id']);
		$tempoAtividade->fill($input)->save();

        $dados = json_decode(json_encode(TempoAtividade::findOrFail($input['id'])),true);

        return view('tempoatividade.editar')->withTributos($tributos)->withEmpresas($empresas)->with($situation, $message)->with('dados', $dados)->with('returning', true)->with('ufs', $ufs);
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

        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $ufs = Municipio::selectRaw("distinct(uf) as uf")->pluck('uf','uf');
		
        $dados = json_decode(json_encode(TempoAtividade::findOrFail($privateid)),true);

        return view('tempoatividade.editar')->withTributos($tributos)->withEmpresas($empresas)->with($situation, $message)->with('dados', $dados)->with('returning', true)->with('ufs', $ufs);
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
			$tempoAtividade = TempoAtividade::where('id', $id)->get();
			if ($tempoAtividade->count() > 0) {
				TempoAtividade::where('id', $id)->delete();
				return redirect()->back()->with('status', 'Tempo de Atividade excluido com sucesso.');
			} else {
				return redirect()->back()->with('error', 'Ocorreu um erro. Tempo de atividade não existe. ');
			}
		} else {
			return redirect()->back()->with('error', 'Ocorreu um erro ao tentar excluir o tempo de atividade. Não recebemos a sua identificação');
		}
		return redirect()->back()->with('error', 'Erro não especificado.');
    }
}
