<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrevisaoCarga;
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

class PrevisaoCargaController extends Controller
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
                        A.Data_prev_carga,
                        A.periodo_apuracao,
                        G.nome as Tributo_Nome
                    FROM
                        previsaocarga A
                            INNER JOIN 
                        tributos G ON A.Tributo_id = G.id';

        if (@$this->s_emp->id && !$user->hasRole('admin')) {
            $query .= ' WHERE
                        B.id = '.$this->s_emp->id.'';
        }

        $query .= ' GROUP BY A.id, G.nome';

        $table = DB::select($query);

        $table = json_decode(json_encode($table),true);
        return view('previsaocarga.index')->with('table', $table);
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
        $ufs = Municipio::selectRaw("distinct(uf) as uf")->pluck('uf','uf');

        return view('previsaocarga.adicionar')->withUfs($ufs)->withTributos($tributos);
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
        //carregando dados da tela
        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $ufs = Municipio::selectRaw("distinct(uf) as uf")->pluck('uf','uf');
        
        $var = array();
        $input = $request->all();

        $c = 0;
        foreach($input['Tributo_id'] as $tributo_id) {
            $var[$c] = $input;
            $var[$c]['Tributo_id'] = $tributo_id;
            $c++;
        }

        foreach($var as $v) {
            if (!$this->validation($v)) {
                return redirect()->back()->with('alert', 'Já existe tempo Previsão de Carga para esse Tributo e Período de Apuração.');
            }
            $v["periodo_apuracao"] = str_replace("/","",$v["periodo_apuracao"]);
            $create = PrevisaoCarga::create($v);
        }

        return view('previsaocarga.adicionar')->withTributos($tributos)->withUfs($ufs)->with("message", $message)->with("status", $situation);
    }

    public function validation($array)
    {
        $find = DB::table('previsaocarga')->select('*')
            ->where('Tributo_id', $array['Tributo_id'])
            ->whereIn('periodo_apuracao', [
                $array['periodo_apuracao'],
                str_replace("/","",$array["periodo_apuracao"]),
                intval($array['periodo_apuracao'])
             ] )->get();

        $find = json_decode(json_encode($find),true);

        if (count($find) > 0) {
            return false;
        }

        return true;
    }

    public function validationEdit($array)
    {
        $id = explode(',', $array['id']);
        $find = DB::table('previsaocarga')->select('*')->where('Tributo_id', $array['Tributo_id'])->where('periodo_apuracao', $array['periodo_apuracao'])->whereNotIn('id', $id)->get();

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

        $var = array();

        $input = $request->all();

        $Atividade = PrevisaoCarga::findOrFail($input['id']);
        $Atividade->fill($input)->save();

        $dados = json_decode(json_encode(PrevisaoCarga::findOrFail($input['id'])),true);

        return view('previsaocarga.editar')->withTributos($tributos)->with($situation, $message)->with('dados', $dados);
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

        $previsaoCarga = PrevisaoCarga::findOrFail($privateid);
        $dados = json_decode(json_encode(PrevisaoCarga::findOrFail($privateid)),true);

        return view('previsaocarga.editar')->withTributos($tributos)->with($situation, $message)->with('dados', $dados)->with('returning', true);
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
			$previsaoCarga = PrevisaoCarga::where('id', $id)->get();
			if ($previsaoCarga->count() > 0) {
                PrevisaoCarga::where('id', $id)->delete();
				return redirect()->back()->with('status', 'Previsão de carga excluida com sucesso.');
			} else {
				return redirect()->back()->with('error', 'Ocorreu um erro. Cadastro não existe. ');
			}
		} else {
			return redirect()->back()->with('error', 'Ocorreu um erro ao tentar excluir a Previsão de carga. Não recebemos a sua identificação');
		}
		return redirect()->back()->with('error', 'Erro não especificado.');
    }
}
