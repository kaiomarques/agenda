<?php

namespace App\Http\Controllers;

use DB;
use App\Models\Atividade;
use App\Models\User;
use App\Models\GrupoEmpresa;
use App\Models\Empresa;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Yajra\Datatables\Datatables;

class GrupoEmpresasController extends Controller
{

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
        if (!empty($this->s_emp)) {
            $emps = $this->getEmpresas($this->s_emp->id);
            $emps = explode(',', $emps);            
        }

        $Rer = DB::table('grupoempresas')
                ->select('grupoempresas.Nome_grupo');
        
        if (!empty($emps)) {
            $Rer = $Rer->whereIn('grupoempresas.id_empresa', $emps);
        }
                
        $Rer = $Rer->groupBy('Nome_grupo')->get();   

        $Relatorio = json_decode(json_encode($Rer),true);
        return view('grupoempresas.index')->with('Relatorio', $Relatorio);
    }

    public function getEmpresas($empresa_id, $logo = false)
    {
        $emps = $empresa_id;
        $Rer = DB::table('grupoempresas')
                ->select('grupoempresas.id', 'grupoempresas.Nome_grupo')
                ->where('id_empresa', ''.$empresa_id.'')
                ->get();

        $var = json_decode(json_encode($Rer),true);

        if ($logo) {
            if (array_key_exists(0, $var)) {
                $Rer = DB::table('grupoempresas')
                    ->select('grupoempresas.id_empresa')
                    ->where('grupoempresas.Nome_grupo', ''.$var[0]['Nome_grupo'].'')
                    ->where('grupoempresas.Logo_grupo', 'S')
                    ->get();

                if (!empty($Rer)) {
                    $Var = json_decode(json_encode($Rer),true);    
                    if (is_array($Var)) {
                        return $Var[0]['id_empresa'];
                    }            
                } else {
                    return $empresa_id;
                }
            }
        }

        if (array_key_exists(0, $var)) {
            if (!empty($var)) {
                $emps = '';
                $Rer = DB::table('grupoempresas')
                    ->select('grupoempresas.id_empresa')
                    ->where('grupoempresas.Nome_grupo', ''.$var[0]['Nome_grupo'].'')
                    ->get();
            $Rer = json_decode(json_encode($Rer),true);
                foreach ($Rer as $key => $V) {
                    $emps .= $V['id_empresa'].',';
                }
                $emps = substr_replace($emps, '', -1);
            }
        }

        return $emps;
    }

    public function anyData($nome)
    {
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        
        $dadosEmpresa = DB::table('grupoempresas')
                ->join('empresas', 'empresas.id', '=', 'grupoempresas.id_empresa')
                ->select('grupoempresas.id', 'empresas.cnpj', 'empresas.razao_social', 'grupoempresas.Logo_grupo')
                ->where('grupoempresas.Nome_grupo',''.$nome.'')
                ->get();

        $Nome_grupo = $nome;
        $dadosEmpresa = json_decode(json_encode($dadosEmpresa),true);
        $view = true;

        return view('grupoempresas.adicionar')->with('dadosEmpresa', $dadosEmpresa)->with('Nome_grupo',$Nome_grupo)->with('empresas', $empresas)->with('view', $view);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function adicionar()
    {
        if (empty($Nome_grupo)) {
            $Nome_grupo = '';
        }
        $status = '';
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        return view('grupoempresas.adicionar')->withEmpresas($empresas)->with('Nome_grupo', $Nome_grupo)->with('status', $status);
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

    Private function checkDispLogo($Nome_grupo, $Logo_grupo)
    {
        $result = DB::table('grupoempresas')
                ->select('grupoempresas.id')
                ->where('grupoempresas.Nome_grupo',''.$Nome_grupo.'')
                ->where('grupoempresas.Logo_grupo','S')
                ->get();
        
        if (empty($result)) {
            return true;
        }
 
        return false;
    }

    Private function checkDisp($id)
    {
        $result = DB::table('grupoempresas')
                ->select('grupoempresas.id')
                ->where('grupoempresas.id_empresa',''.$id.'')
                ->get();

        if (empty($result)) {
            return true;
        }
 
        return false;
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $success = '';
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $input = $request->all();

        if (empty($input['Nome_grupo'])) {
            $status = 'Favor informar o nome do grupo!'; 
            $success = 'error';            
        }
        
        if (!$this->checkDisp($input['id_empresa']) && $success != 'error') {
            $status = 'A empresa já está cadastrada em um grupo!'; 
            $success = 'error';
        }

        if (!$this->checkDispLogo($input['Nome_grupo'], $input['Logo_grupo']) && $success != 'error' && $input['Logo_grupo'] == 'S') {
            $status = 'Já existe logo liberado para esse grupo!'; 
            $success = 'error';
        }

        if ($success != 'error') {
            $create = GrupoEmpresa::create($input);
            $status = 'Empresa inserida no grupo com sucesso!';
            $success = 'status';
        } 
        
        $dadosEmpresa = DB::table('grupoempresas')
                ->join('empresas', 'empresas.id', '=', 'grupoempresas.id_empresa')
                ->select('grupoempresas.id', 'empresas.cnpj', 'empresas.razao_social', 'grupoempresas.Logo_grupo')
                ->where('grupoempresas.Nome_grupo',''.$input['Nome_grupo'].'')
                ->get();

        $Nome_grupo = $input['Nome_grupo'];
        $dadosEmpresa = json_decode(json_encode($dadosEmpresa),true);

        return view('grupoempresas.adicionar')->with($success, $status)->with('dadosEmpresa', $dadosEmpresa)->with('Nome_grupo',$Nome_grupo)->with('empresas', $empresas);
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
    public function edit($id)
    {
        //
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
        $grupoEmpresa = GrupoEmpresa::findOrFail($id);

        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $Nome_grupo = $grupoEmpresa->Nome_grupo;
        $grupoEmpresa->delete();

        $dadosEmpresa = DB::table('grupoempresas')
                ->join('empresas', 'empresas.id', '=', 'grupoempresas.id_empresa')
                ->select('grupoempresas.id', 'empresas.cnpj', 'empresas.razao_social', 'grupoempresas.Logo_grupo')
                ->where('grupoempresas.Nome_grupo',''.$Nome_grupo.'')
                ->get();

        $dadosEmpresa = json_decode(json_encode($dadosEmpresa),true);        
        $status = 'Empresa removida com sucesso do grupo!';
      
        return view('grupoempresas.adicionar')->with('status', $status)->with('dadosEmpresa', $dadosEmpresa)->with('Nome_grupo', $Nome_grupo)->with('empresas', $empresas);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyRLT($id)
    {
        $grupoEmpresa = DB::table('grupoempresas')
                ->select('grupoempresas.*')
                ->where('grupoempresas.Nome_grupo',''.$id.'')
                ->get();

        $grupoEmpresa = json_decode(json_encode($grupoEmpresa),true);
        
        if (!empty($grupoEmpresa)) {
            foreach ($grupoEmpresa as $key => $finalID) {
                $grupoEmpresa = GrupoEmpresa::findOrFail($finalID['id']);
                $grupoEmpresa->delete();
            }
        }

        $status = 'Grupo apagado com sucesso!';
        return back()->with('status', $status);
    }
}
