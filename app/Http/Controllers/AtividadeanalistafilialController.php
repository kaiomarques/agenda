<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use Illuminate\Http\Request;
use App\Models\AtividadeAnalistaFilial;
use App\Models\AtividadeAnalista;
use App\Models\Estabelecimento;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Tributo;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

use App\Http\Requests;

class AtividadeanalistafilialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function anyData(Request $request)
    {
        //
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

    public function validation($array)
    {
        $id = explode(',', $array['Id_atividadeanalista']);
        $emp = DB::table('atividadeanalista')->select('Emp_id')->wherein('id', $id)->get();
        $emp = json_decode(json_encode($emp),true);
        if (count($emp) == 0) {
            return false;
        }
        //Verifica se existe o cnpj
        $find = DB::table('estabelecimentos')->select('empresa_id', 'id')->where('empresa_id', $emp[0]['Emp_id'])->where('cnpj', $array['cnpj'])->get();
        $find = json_decode(json_encode($find),true);
        if (count($find) == 0) {
            return false;
        }
        //fim verificação

        //Verifica se o estabelecimento já está na Atividadeanalistafilial
        $existencia = DB::Select('SELECT A.id FROM atividadeanalistafilial A INNER JOIN atividadeanalista B on A.Id_atividadeanalista = B.id WHERE A.Id_estabelecimento = '.$find[0]['id'].' AND B.Emp_id = '.$find[0]['empresa_id'].' AND B.Id_usuario_analista = '.$array['Id_usuario'].' AND B.Tributo_id = (SELECT D.Tributo_id FROM atividadeanalista D where D.id = '.$array['Id_atividadeanalista'].')');
        $existencia = json_decode(json_encode($existencia),true);
        if (count($existencia) > 0) {
            return false;
        }
        //fim verificação

        return true;
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

        $input = $request->all();
        if (empty($input['cnpj'])) {
            $cnpjs = DB::table('atividadeanalistafilial')
                ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
                ->select('atividadeanalistafilial.id','atividadeanalistafilial.Id_estabelecimento', 'estabelecimentos.cnpj', 'estabelecimentos.codigo')
                ->where('atividadeanalistafilial.Id_atividadeanalista', $input['Id_atividadeanalista'])
                ->get();

            $cnpjs = json_decode(json_encode($cnpjs),true);
            $ids = '4,6';
            $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
            $user_ids = json_decode(json_encode($user_ids),true);
            $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->pluck('name','id');
            $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
            $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
            $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($input['Id_atividadeanalista'])),true);

            $situation = 'error';
            $message = 'Favor informar o CNPJ.';
            return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs);
        }

        $input['cnpj'] = preg_replace('/[^0-9]/', '', $input['cnpj']);
        if (!empty($input['cnpj']) && !$this->validation($input)) {

            $cnpjs = DB::table('atividadeanalistafilial')
                ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
                ->select('atividadeanalistafilial.id','atividadeanalistafilial.Id_estabelecimento', 'estabelecimentos.cnpj', 'estabelecimentos.codigo')
                ->where('atividadeanalistafilial.Id_atividadeanalista', $input['Id_atividadeanalista'])
                ->get();

            $cnpjs = json_decode(json_encode($cnpjs),true);
            $ids = '4,6';
            $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
            $user_ids = json_decode(json_encode($user_ids),true);
            $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->pluck('name','id');
            $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
            $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
            $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($input['Id_atividadeanalista'])),true);

            $situation = 'error';
            $message = 'O cnpj não é válido para a empresa ou já está sendo utilizado, favor verificar.';
            return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs);
        }

        $Id_estabelecimento =json_decode(json_encode(DB::table('estabelecimentos')->select('id')->where('cnpj', $input['cnpj'])->limit(1)->get()),true) ;

        $date['Id_estabelecimento'] = $Id_estabelecimento[0]['id'];
        $date['Id_atividadeanalista'] = $input['Id_atividadeanalista'];

        $create = AtividadeAnalistaFilial::Create($date);

        $cnpjs = DB::table('atividadeanalistafilial')
            ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
            ->select('atividadeanalistafilial.id','atividadeanalistafilial.Id_estabelecimento', 'estabelecimentos.cnpj', 'estabelecimentos.codigo')
            ->where('atividadeanalistafilial.Id_atividadeanalista', $input['Id_atividadeanalista'])
            ->get();

        $cnpjs = json_decode(json_encode($cnpjs),true);
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->pluck('name','id');
        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($input['Id_atividadeanalista'])),true);

        return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs);
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
    public function excluirFilial(Request $request)
    {
        $situation = 'status';
        $message = 'Registro excluído com sucesso';
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!empty($key)) {
            $id =json_decode(json_encode(AtividadeAnalistaFilial::findOrFail($key)),true);
            $ids = '4,6';
            $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
            $user_ids = json_decode(json_encode($user_ids),true);
            $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->pluck('name','id');
            $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
            $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
            $ufs = Municipio::selectRaw("distinct(uf) as uf")->pluck('uf','uf');

            $dados = json_decode(json_encode(AtividadeAnalista::findOrFail($id['Id_atividadeanalista'])),true);
            AtividadeAnalistaFilial::destroy($key);
            $cnpjs = DB::table('atividadeanalistafilial')
                ->join('estabelecimentos', 'atividadeanalistafilial.Id_estabelecimento', '=', 'estabelecimentos.id')
                ->select('atividadeanalistafilial.id','atividadeanalistafilial.Id_estabelecimento', 'estabelecimentos.cnpj', 'estabelecimentos.codigo')
                ->where('atividadeanalistafilial.Id_atividadeanalista', $id['Id_atividadeanalista'])
                ->get();

            $cnpjs = json_decode(json_encode($cnpjs),true);
            return view('atividadeanalista.editar')->withTributos($tributos)->withEmpresas($empresas)->withUsuarios($usuarios)->with($situation, $message)->with('dados', $dados)->with('cnpjs', $cnpjs)->with('ufs', $ufs);
        }

        return redirect()->back()->with('status', 'Filial excluída com sucesso!');
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
}