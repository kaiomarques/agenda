<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Cron;
use App\Models\DocumentoCND;
use App\Models\DocumentoCNDObservacao;
use App\Models\Role;
use App\Models\User;
use App\Models\Mensageriaprocadm;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\TipoCND;
use App\Models\ClassificacaoCND;
use App\Models\Municipio;
use App\Models\FeriadoEstadual;
use App\Models\FeriadoMunicipal;
use App\Models\Movtocontacorrente;
use App\Services\EntregaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

use App\Http\Requests;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Artisan;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use DB;


class MonitorCndController extends Controller
{
    protected $eService;

    public $s_emp = null;

    const ANEXO_DESTINATARIO = 'uploads\files';

    public function __construct()
    {


        if (!session()->get('seid')) {
            Session::flash('warning', 'Nenhuma empresa selecionada, favor selecionar uma!');
            return redirect()->route('home', ['selecionar_empresa' => 1])->send();
        }

        $this->middleware('auth');

        if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
            $this->s_emp = Empresa::findOrFail(session()->get('seid'));
        }

    }

    public function search()
    {
        return view('monitorcnd.search');
    }

    public function create()
    {
        $estabelecimentos = Estabelecimento::selectRaw("codigo, id")->where("empresa_id", $this->s_emp->id)->orderBy('codigo')->pluck('codigo','id');
        $tipoCDN = TipoCND::selectRaw('descricao , id')->pluck('descricao','id');
        $classificacaocnd = ClassificacaoCND::selectRaw('descricao , id')->pluck('descricao','id');

        return view('monitorcnd.create')
                ->with('estabelecimentos', $estabelecimentos)
                ->with('tipocnd', $tipoCDN)
                ->with('classificacaocnd', $classificacaocnd);
    }

    public function anyData(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        $seid = $this->s_emp->id;

        $response = DocumentoCND::select(
            'DocumentoCND.id',
            'estabelecimentos.codigo AS estabelecimento',
            'municipios.uf as uf',
            'tipocnd.descricao as tipocnd_descricao',
            'classificacaocnd.descricao as classificacaocnd_descricao',
            'DocumentoCND.numero_cnd',
            DB::raw('DATE_FORMAT(DocumentoCND.validade_cnd,"%d/%m/%Y") AS validade_cnd'),
            'DocumentoCND.arquivo_cnd'
        )
            ->join('estabelecimentos', 'DocumentoCND.estemp_id', '=', 'estabelecimentos.id')
            ->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
            ->join('tipocnd', 'DocumentoCND.tipocnd_id', '=', 'tipocnd.id')
            ->join('classificacaocnd', 'DocumentoCND.classificacaocnd_id', '=', 'classificacaocnd.id')
            ->where('estabelecimentos.empresa_id', $seid)
            ->orderBy('validade_cnd', 'desc');

        $result = Datatables::of($response)->make(true);

        return $result;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        if ($request->hasFile('arquivo')) {
            $anexo = $this->upload($request->file('arquivo'));
            $request['arquivo_cnd'] = $anexo->getPathname();
        }

        $input = $request->all();
        $addComentario = true;

        $createDocumento = DocumentoCND::create($input);

        if($createDocumento) {
            $id = $createDocumento->id;
            $documentoCNDObservacao = new DocumentoCNDObservacao();
            if(isset($input['observacao']) && $input['observacao'] != '') {
                $documentoCNDObservacao->usuario_id = Auth::user()->id;
                $documentoCNDObservacao->documentocnd_id = $id;
                $documentoCNDObservacao->texto = $input['observacao'];
                $documentoCNDObservacao->data = date('Y-m-d H:i:s');
                $addComentario = $documentoCNDObservacao->save();
            }
        }

        ( $createDocumento && $addComentario )? (DB::commit()) : (DB::rollBack());

        return redirect()->back()->with('status', 'Empresa adicionada com sucesso!');
    }

    public function upload($file)
    {
        $fileName = $file->move(self::ANEXO_DESTINATARIO, $file->getClientOriginalName());
        return $fileName;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $documentocnd = DocumentoCND::findOrFail($id);
        $documentoCNDObservacao = DocumentoCNDObservacao::select(
        	'documentocnd_observacao.id',
            'documentocnd_observacao.documentocnd_id',
            'users.name as nome_usuario',
            'documentocnd_observacao.texto',
            'documentocnd_observacao.data'
        )
            ->join('users', 'users.id',' = ','documentocnd_observacao.usuario_id')
            ->where('documentocnd_observacao.documentocnd_id', $id)->get();

        $estabelecimentos = Estabelecimento::selectRaw("codigo as razao_social, id")->pluck('razao_social','id');
        $tipoCDN = TipoCND::selectRaw('descricao , id')->pluck('descricao','id');
        $classificacaocnd = ClassificacaoCND::selectRaw('descricao , id')->pluck('descricao','id');

        return view('monitorcnd.edit')
            ->with('documentocnd',$documentocnd)
            ->with('estabelecimentos', $estabelecimentos)
            ->with('tipocnd', $tipoCDN)
            ->with('classificacaocnd', $classificacaocnd)
            ->with('observacoes_extras', $documentoCNDObservacao);
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
        DB::beginTransaction();

        $documentoCND = DocumentoCND::findOrFail($id);

        if ($request->hasFile('arquivo')) {
            $anexo = $this->upload($request->file('arquivo'));
            $documentoCND->arquivo_cnd = $anexo->getPathname();
        }

        $input = $request->all();
        $documentoCNDObservacao = new DocumentoCNDObservacao();
        $addComentario = true;

        if(isset($input['observacao']) && $input['observacao'] != '') {
            $documentoCNDObservacao->usuario_id = Auth::user()->id;
            $documentoCNDObservacao->documentocnd_id = $id;
            $documentoCNDObservacao->texto = $input['observacao'];
            $documentoCNDObservacao->data = date('Y-m-d H:i:s');
            $addComentario = $documentoCNDObservacao->save();
        }

        //$documentoCND->estemp_id = $input['estemp_id'];
        $documentoCND->classificacaocnd_id = $input['classificacaocnd_id'];
        $documentoCND->numero_cnd = $input['numero_cnd'];
        $documentoCND->validade_cnd = $input['validade_cnd'];

        $Updatedocumento = $documentoCND->save();

        ( $Updatedocumento && $addComentario )? (DB::commit()) : (DB::rollBack());

        return redirect()->back()->with('status', 'Comentario atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DocumentoCND::where('id', '=', $id)
            ->delete();
        $table = DocumentoCND::all();

        return redirect()->back()->with('status', 'Documento CND excluído com sucesso.')->with(compact('table'));
    }

    public function dashboard(Request $request)
    {
        $today = date('Y-m-d');
        $thisMonth = date('m');
        $nextMonth = date('m', strtotime("+1 months", strtotime($today)));

        $dados[1][1] = DocumentoCND::select('distinct(Estemp_id)')
            ->join('estabelecimentos', 'estabelecimentos.id', '=', 'documentocnd.Estemp_id')
            ->whereRaw('validade_cnd < ?', [$today])
            ->where('classificacaocnd_id', ClassificacaoCND::POSITIVA)
            ->where('tipocnd_id', TipoCND::ESTADUAL)
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        $dados[2][1] = DocumentoCND::select('distinct(Estemp_id)')
            ->join('estabelecimentos', 'estabelecimentos.id', '=',  'documentocnd.Estemp_id')
            ->whereRaw('validade_cnd < ?', [$today])
            ->where('classificacaocnd_id', ClassificacaoCND::POSITIVA_NEGATIVA)
            ->where('tipocnd_id', TipoCND::ESTADUAL)
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        $dados[3][1] = DocumentoCND::select('distinct(Estemp_id)')
            ->join('estabelecimentos', 'estabelecimentos.id', '=',  'documentocnd.Estemp_id')
            ->whereRaw('validade_cnd < ?', [$today])
            ->where('classificacaocnd_id', ClassificacaoCND::NEGATIVA)
            ->where('tipocnd_id', TipoCND::ESTADUAL)
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        $dados[4][1] = 0;

        $dados[1][2] = DocumentoCND::select('*')
            ->join('estabelecimentos', 'estabelecimentos.id', '=',  'documentocnd.Estemp_id')
            ->whereRaw('MONTH(validade_cnd) = ?', [$thisMonth])
            ->where('classificacaocnd_id', ClassificacaoCND::POSITIVA)
            ->where('tipocnd_id', TipoCND::ESTADUAL)
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        $dados[2][2] = DocumentoCND::select('*')
            ->join('estabelecimentos', 'estabelecimentos.id', '=',  'documentocnd.Estemp_id')
            ->whereRaw('MONTH(validade_cnd) = ?', [$thisMonth])
            ->where('classificacaocnd_id', ClassificacaoCND::POSITIVA_NEGATIVA)
            ->where('tipocnd_id', TipoCND::ESTADUAL)
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        $dados[3][2] = DocumentoCND::select('*')
            ->join('estabelecimentos', 'estabelecimentos.id', '=',  'documentocnd.Estemp_id')
            ->whereRaw('MONTH(validade_cnd) = ?', [$thisMonth])
            ->where('classificacaocnd_id', ClassificacaoCND::NEGATIVA)
            ->where('tipocnd_id', TipoCND::ESTADUAL)
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        $dados[4][2] = 0;

        $dados[1][3] = DocumentoCND::select('*')
            ->join('estabelecimentos', 'estabelecimentos.id', '=',  'documentocnd.Estemp_id')
            ->whereRaw('MONTH(validade_cnd) = ?', [$nextMonth])
            ->where('classificacaocnd_id', ClassificacaoCND::POSITIVA)
            ->where('tipocnd_id', TipoCND::ESTADUAL)
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        $dados[2][3] = DocumentoCND::select('*')
            ->join('estabelecimentos', 'estabelecimentos.id', '=',  'documentocnd.Estemp_id')
            ->whereRaw('MONTH(validade_cnd) = ?', [$nextMonth])
            ->where('classificacaocnd_id', ClassificacaoCND::POSITIVA_NEGATIVA)
            ->where('tipocnd_id', TipoCND::ESTADUAL)
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        $dados[3][3] = DocumentoCND::select('*')
            ->join('estabelecimentos', 'estabelecimentos.id', '=',  'documentocnd.Estemp_id')
            ->whereRaw('MONTH(validade_cnd) = ?', [$nextMonth])
            ->where('classificacaocnd_id', ClassificacaoCND::NEGATIVA)
            ->where('tipocnd_id', TipoCND::ESTADUAL)
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        $dados[4][3] = 0;

        $dados[1][4] = 0;

        $dados[2][4] = 0;

        $dados[3][4] = 0;

        $dados[4][4] = Estabelecimento::select('*')
            ->leftjoin('documentocnd', function($join)
            {
                $join->on('documentocnd.Estemp_id', '=', 'estabelecimentos.id')
                    ->where('documentocnd.tipocnd_id','=',  TipoCND::ESTADUAL);
            }, 'left outer')
            ->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
            ->whereNull('documentocnd.id')
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)
            ->count();

        return view('monitorcnd.dashboard')
            ->with('dados',$dados)
            ->with('maior',$this->getMaior($dados));

    }


    public function dashboardAnyData()
    {
        $classificacaoCND = $request->input('classificacaoCND');
        $periodo = $request->input('periodo');

        $user = User::findOrFail(Auth::user()->id);
        $seid = $this->s_emp->id;

        if($classificacaoCND == 'Sem certidão') {
            $response = Estabelecimento::select([
                DB::raw('null as id'),
                'estabelecimentos.codigo',
                'estabelecimentos.cnpj',
                'estabelecimentos.insc_estadual',
                'municipios.uf',
                DB::raw('null as tipocnd_descricao'),
                DB::raw('null as classificacaocnd_descricao'),
                DB::raw('null as validade_cnd'),
                DB::raw('null as numero_cnd')
            ])
            ->leftjoin('documentocnd', function($join)
            {
                $join->on('documentocnd.Estemp_id', '=', 'estabelecimentos.id')
                    ->where('documentocnd.tipocnd_id','=',  TipoCND::ESTADUAL);
            }, 'left outer')
            ->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
            ->whereNull('documentocnd.id')
            ->where('estabelecimentos.empresa_id', $this->s_emp->id)->get();
        } else {
            $response = DocumentoCND::select(
                'documentocnd.id',
                'estabelecimentos.codigo',
                'estabelecimentos.cnpj',
                'municipios.uf as uf',
                'estabelecimentos.insc_estadual',
                'tipocnd.descricao AS tipocnd_descricao',
                'classificacaocnd.descricao AS classificacaocnd_descricao',
                'documentocnd.numero_cnd',
                DB::raw('DATE_FORMAT(DocumentoCND.validade_cnd,"%d/%m/%Y") AS validade_cnd'),
                'documentocnd.arquivo_cnd'
            )
                ->join('estabelecimentos', 'DocumentoCND.estemp_id', '=', 'estabelecimentos.id')
                ->join('tipocnd', 'DocumentoCND.tipocnd_id', '=', 'tipocnd.id')
                ->join('classificacaocnd', 'DocumentoCND.classificacaocnd_id', '=', 'classificacaocnd.id')
                ->join('municipios','estabelecimentos.cod_municipio','=', 'municipios.codigo')
                ->where('tipocnd_id', TipoCND::ESTADUAL)
                ->where('estabelecimentos.empresa_id', $seid)
                ->orderBy('validade_cnd', 'desc');


            switch($classificacaoCND) {
                case 'Positiva': {
                    $response->where('documentocnd.classificacaocnd_id',1);
                }
                    break;
                case 'Positiva com efeito negativa': {
                    $response->where('documentocnd.classificacaocnd_id',2);
                }
                    break;
                case 'Negativa': {
                    $response->where('documentocnd.classificacaocnd_id',3);
                }
            }

            switch($periodo) {
                case 'Vencidos': {
                    $today = date('Y-m-d');
                    $response->whereRaw('validade_cnd < ?', [$today]);
                }
                    break;
                case 'Vence esse mês': {
                    $thisMonth = date('m');
                    $response->whereRaw('MONTH(validade_cnd) = ?', [$thisMonth]);

                }
                    break;
                case 'Vence próximo mês': {
                    $today = date('Y-m-d');
                    $nextMonth = date('m', strtotime("+1 months", strtotime($today)));
                    $response->whereRaw('MONTH(validade_cnd) = ?', [$nextMonth]);

                }
            }
        }

        $result = Datatables::of($response)->make(true);

        return $result;
    }

    public function dashboardRLT() {
        $classificacaoCND = $request->input('classificacaoCND');
        $periodo = $request->input('periodo');
        return view('monitorcnd.dashboardTable')
            ->with("classificacaoCND", $classificacaoCND)
            ->with("periodo", $periodo);
    }

    public function job() {
        $path = 'C:/storagebravobpo/cnd';
        $pathEntregar = 'c:/storagebravobpo/cnd/entregar/';
        $pathUploaded = 'c:/storagebravobpo/cnd/uploaded/';

        foreach(scandir($pathEntregar) as $file) {
            if ($file == '.' || $file == '..') continue;

            $oldPath = $pathEntregar.$file;
            $newPath = self::ANEXO_DESTINATARIO.$file;
            $doneParh = $pathUploaded.$file;

            $extensao = pathinfo($oldPath, PATHINFO_EXTENSION);

            if($extensao != 'pdf') continue;

            $files[] = $file;
            $infos = trim($file, '.pdf');

            $infos = explode("_", $infos);

            $numero = $infos[0];
            $data = $infos[1];
            $dia = substr($data, 0, 2);
            $mes = substr($data, 2, 2);
            $ano = substr($data, 4, 4);

            if(checkdate($mes, $dia, $ano) ) {
                $data_formatada = $ano.'-'.$mes.'-'.$dia;
            } else {
                continue;
            }

            if(!is_numeric($numero)) {
                echo 'O arquivo "'.$file.'" ('.$numero.')  não possui um CNPJ válido.<br/>';
                continue;
            }

            $documentocnd = DocumentoCND::select("documentocnd.id")
                ->join('estabelecimentos','documentocnd.Estemp_id','=','estabelecimentos.id')
                ->where('estabelecimentos.cnpj', $numero)
                ->where("documentocnd.validade_cnd", date($data_formatada))
                ->first();

            if($documentocnd == null) {
                echo 'O CNPJ do arquivo "'.$file.'" (CNPJ: '.$numero.', '.$data_formatada.') não possui um cadastro de CND.<br/>';
                continue;
            }


            $move1 = File::copy($oldPath, $newPath);
            $move2 = File::move($oldPath, $doneParh);

            if($move1 == true && $move2 == true) {
                $documentocnd['arquivo_cnd'] = $newPath;
                $documentocnd->save();
            }
        }
        echo "Upload das CND's realizados com sucesso. ";
    }

    public function getCNPJ($filial_id) {
        $estabelecimento = Estabelecimento::select("estabelecimentos.cnpj", "municipios.uf")
            ->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
            ->where('estabelecimentos.id', $filial_id)
            ->first();

        $cnpj = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $estabelecimento->cnpj);
        $uf = $estabelecimento->uf;

        return '<b>CNPJ: </b>'.$cnpj.' - <b>UF: </b>'.$uf;
    }

    private function getMaior($dados) {
        $maior = 0;
        foreach($dados as $dado) {
            foreach($dado as $d) {
                if ($d > $maior) {
                    $maior = $d;
                }
            }
        }
        return $maior;
    }

}