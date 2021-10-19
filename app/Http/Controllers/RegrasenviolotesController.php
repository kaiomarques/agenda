<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Regraenviolote;
use App\Models\Estabelecimento;
use App\Models\Regraenviolotefilial;
use App\Models\Empresa;
use App\Models\Tributo;
use App\Models\googl;
use App\Models\Municipio;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;


class RegrasenviolotesController extends Controller
{
    protected $eService;   
    public $msg;
    public $estabelecimento_id;

    function __construct(EntregaService $service)
    {
        $this->eService = $service;
    }

    public function lote_consulta(Request $request)
    {
        $standing = DB::select("SELECT 
            A.id, C.razao_social, B.nome, A.regra_geral
        FROM
            regraenviolote A
                INNER JOIN
            empresas C ON A.id_empresa = C.id
                INNER JOIN
            tributos B ON A.id_tributo = B.id");
        
        $array = json_decode(json_encode($standing),true);
        return view('regras.consulta_lote')->with('array', $array);
    }

    public function Job($envio_manual=false, $data_envio = '', $id= '')
    {
        $regra_geral = Regraenviolote::select("id","regra_geral")->where('envioaprovacao', 'N')->get();
        
        if ($envio_manual) {
            $regra_geral = Regraenviolote::select("id","regra_geral")->where("id", $id)->get();    
        }
        
        $parametro_regra_geral = json_decode(json_encode($regra_geral),true);
        $this->findRegrasenviolote($parametro_regra_geral, $envio_manual, $data_envio);
    }

    public function findRegrasenviolote($param, $envio_manual=false, $data_envio = '')
    {
        //montando array mestre
        foreach ($param as $key => $value) {
            $value['dadosRegra'] = Regraenviolote::findOrFail($value['id']);
            $value['dadosRegra']['Matriz'] = Empresa::select('id', 'cnpj')->where('id', $value['dadosRegra']['id_empresa'])->get();
            $value = json_decode(json_encode($value), true);
            
            $query = "SELECT 
                        A.arquivo_entrega, 
                        C.cnpj as EmpresaCNPJ, 
                        B.cnpj as EstabelecimentoCNPJ, 
                        B.razao_social as EstabelecimentoNome,
                        A.periodo_apuracao,
                        (SELECT B.pasta_arquivos FROM regraenviolote A INNER JOIN tributos B on A.id_tributo = B.id where A.id = ".$value['dadosRegra']['id'].") as tributo, 
                        (SELECT B.tipo FROM regraenviolote A INNER JOIN tributos B on A.id_tributo = B.id where A.id = ".$value['dadosRegra']['id'].") as tipo
                        FROM
                            atividades A
                                INNER JOIN
                            estabelecimentos B ON A.estemp_id = B.id
                                INNER JOIN
                            empresas C ON A.emp_id = C.id
                                INNER JOIN
                            regras D ON A.regra_id = D.id";

            if ($value['regra_geral'] == 'S') {
                $query .= " WHERE A.emp_id = ".$value['dadosRegra']['id_empresa']."";                
            }

            if ($value['regra_geral'] == 'N') {
                $query .= " WHERE
                            A.estemp_id IN (SELECT 
                                    id_estabelecimento
                                FROM
                                    regraenviolotefilial
                                WHERE
                                    id_regraenviolote = ".$value['dadosRegra']['id'].")";
            }

            $dataBusca = date('d/m/Y');
            if (!empty($data_envio)) {
                $dataBusca = $data_envio;
            }  

            $query .= " AND DATE_FORMAT(A.data_entrega,'%d/%m/%Y') = '".$dataBusca."' AND D.tributo_id = (SELECT id_tributo FROM regraenviolote WHERE id = ".$value['dadosRegra']['id']."); ";
            
            $data = DB::select($query);
            $data = json_decode(json_encode($data),true);

            $download_link  = array();
            
            if (!empty($data)){
                $server_name    = $_SERVER['SERVER_NAME'];
                $document_root  = $_SERVER['DOCUMENT_ROOT'];
                
                $termo = 'agenda';
                $pattern = '/' . $termo . '/';
                // TODO: não existe mais /agenda/public completando server name ou docroot
                // if (!preg_match($pattern, $_SERVER['SERVER_NAME'])) {
                //     $server_name    = $_SERVER['SERVER_NAME'].'/agenda/public';
                //     $document_root  = $_SERVER['DOCUMENT_ROOT'].'/agenda/public';
                // }
                
                foreach ($data as $campo) {
                    $path_link = "http://".$server_name."/uploads/".substr($campo['EmpresaCNPJ'], 0, 8)."/".$campo['EstabelecimentoCNPJ']."";
                    $path = "".$document_root."/uploads/".substr($campo['EmpresaCNPJ'], 0, 8)."/".$campo['EstabelecimentoCNPJ']."";
                    
                    $campo['tipo'] = $this->getTipo($campo['tipo']);
                    $ult_periodo_apuracao = $campo['periodo_apuracao'];
                    $path .= '/'.$campo['tipo'].'/'.$campo['tributo'].'/'.$ult_periodo_apuracao.'/'.$campo['arquivo_entrega'];
                    $path_link .= '/'.$campo['tipo'].'/'.$campo['tributo'].'/'.$ult_periodo_apuracao.'/'.$campo['arquivo_entrega'];

                    if (file_exists($path)) {
                        $download_link[$campo['EstabelecimentoCNPJ']]['texto'] = $campo['EstabelecimentoNome'].' - '. $campo['tributo'];
                        $download_link[$campo['EstabelecimentoCNPJ']]['link'] = $path_link;
                    }
                }
            }
            if (!empty($download_link)) {
                $this->enviarEmailLote($download_link, $value['dadosRegra']['email_1'], $value['dadosRegra']['email_2'], $value['dadosRegra']['email_3'], $data_envio);
            }
        }
    }

    public function getEstabelecimentos($id_empresa)
    {
        $value = Estabelecimento::select('id', 'cnpj')->where('empresa_id', $id_empresa)->get(); 
        $value = json_decode(json_encode($value),true);
        return $value; 
    }

    public function enviarEmailLote($array, $email_1, $email_2, $email_3, $data_envio = '')
    {
        // $key = 'AIzaSyBI3NnOJV5Zt-hNnUL4BUCaWIgGugDuTC8';
        // $Googl = new Googl($key);
        foreach ($array as $L => $F) {
            $arr[$L]['texto'] = $F['texto'];
            // $arr[$L]['link'] = $Googl->shorten($F['link']);
            $arr[$L]['link'] = $F['link'];
        }

        $dados = array('dados' => $arr, 'emails' => array($email_1, $email_2, $email_3));
        $data['linkDownload'] = $dados['dados'];

        $dataExibe = date('d/m/Y');
        if (!empty($data_envio)) {
            $dataExibe = $data_envio;
        }
        $subject = "TAX CALENDAR - Entrega das obrigações em ".$dataExibe.".";
        $data['subject']      = $subject;
        $data['data']         = $dataExibe;
        
        foreach($dados['emails'] as $user)
        {   
            if (!empty($user)) {
                $this->eService->sendMail($user, $data, 'emails.notification-envio-lote', true);
            }
        }
        return;
    }
    public function envio_lote(Request $request)
    {
        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        return view('regras.envio_lote')->withTributos($tributos)->withEmpresas($empresas);
    }

    private function validaCampos($input){

        if (!empty($input['email_1']) && !$this->validaEmail($input['email_1'])) {
            return false;
        }
        if (!empty($input['email_2']) && !$this->validaEmail($input['email_2'])) {
            return false;
        }

        if (!empty($input['email_3']) && !$this->validaEmail($input['email_3'])) {
            return false;
        }

        if (!$this->validaExistencia($input)) {
            return false;
        }

        return true;
    }    

    private function getCNPJ($input){

        if (!$this->carregaCNPJ($input['cnpj'], $input['id_empresa'])) {
            return false;
        }

        return true;
    }

    private function validaEmail($email) 
    {
        $er = "/^(([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}){0,1}$/";
        if (preg_match($er, $email)){
        return true;
        } else {
        $this->msg = 'Email é inválido, favor verificar !!';
        return false;
        }
    }

    private function carregaCNPJ($cnpj, $id_empresa) 
    {        
        $cnpj = $this->clearCNPJ($cnpj);
        $existe = DB::SELECT("SELECT id FROM estabelecimentos WHERE empresa_id = ".$id_empresa." AND cnpj = ".$cnpj."");
        $var = json_decode(json_encode($existe),true);
        if (empty($var)) {
            $this->msg = 'CNPJ não consta para essa empresa';
            return false;
        }
        foreach ($var as $key => $value) {        
        }

        $this->estabelecimento_id = $value['id'];
        return true;
    }

    private function validaExistencia($input) 
    {        
        $dados = DB::SELECT("SELECT id FROM regraenviolote WHERE id_empresa = ".$input['select_empresas']." AND id_tributo = ".$input['select_tributos']." AND regra_geral = '".$input['regra_geral']."' AND id <> ".$input['id']." AND envioaprovacao ='".$input['envioaprovacao']."'");

        if (!empty($dados)) {
            $this->msg = 'Duplicidade detectada, favor verificar os dados informados';
            return false;
        }        
        return true;
    }

    private function getTipo($tipo)
    {
        if ($tipo == 'E') {
            return 'ESTADUAIS';
        }

        if($tipo == 'M'){
            return 'MUNICIPAIS';
        }

        if ($tipo == 'F') {
            return 'FEDERAIS';
        }
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

        if ($input['envio_manual']) {
            if (empty($input['data_envio'])) {
                return redirect()->back()->with('alert', 'A data é obrigatória para busca.');
            }
            $timestamp = strtotime($input['data_envio']);
            $input['data_envio'] = date("d/m/Y", $timestamp);
            $this->Job($input['envio_manual'], $input['data_envio'], $input['id']);
            return redirect()->back()->with('status', 'Envio manual efetuado com sucesso.');
        }

        if ($input['add_cnpj']) {
            $this->validate($request, [
            'cnpj' => 'required'
            ],
            $messages = [
                'cnpj.required' => 'Informar o CNPJ desejado.'

            ]);

            if (!$this->getCNPJ($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
                
            $value['id_regraenviolote'] = $input['id'];
            $value['id_estabelecimento'] = $this->estabelecimento_id;
            Regraenviolotefilial::create($value);
            return redirect()->back()->with('status', 'Filial adicionada com sucesso.');
        }

        //se não continua
        $this->validate($request, [
            'email_1' => 'required',
            'envioaprovacao' => 'required'
        ],
        $messages = [
            'email_1.required' => 'Informar o email obrigatório.',
            'envioaprovacao.required' => 'Informar se será enviado um email na aprovação.'

        ]);
        
        $input['regra_geral'] = 'N';
        if ($input['label_regra']) {
            $input['regra_geral'] = 'S';
        }

        if (!empty($input)) {
            if (!$this->validaCampos($input)) {
                return redirect()->back()->with('alert', $this->msg);
            }
        }

        //edit
        if ($input['id'] > 0) {
            $Regraenviolote = Regraenviolote::findOrFail($input['id']);
            $Regraenviolote->fill($input)->save();
            return redirect()->back()->with('status', 'Regra atualizada com sucesso!');
        }
        
        $value['id_empresa'] = $input['select_empresas'];
        $value['id_tributo'] = $input['select_tributos'];
        $value['email_1'] = $input['email_1'];
        $value['email_2'] = $input['email_2'];
        $value['email_3'] = $input['email_3'];
        $value['regra_geral'] = $input['regra_geral'];
        $value['envioaprovacao'] = $input['envioaprovacao'];

        //se Não, ele cria
        $create = Regraenviolote::create($value);
        return redirect()->route('regraslotes.envio_lote')->with('status', 'Regra adicionada com sucesso!');
    }

    private function clearCNPJ($cnpj)
    {
        $cnpj = trim($cnpj);
        $cnpj = str_replace(".", "", $cnpj);
        $cnpj = str_replace(",", "", $cnpj);
        $cnpj = str_replace("-", "", $cnpj);
        $cnpj = str_replace("/", "", $cnpj);
        return $cnpj;
    }

    public function edit_lote(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        $dados = Regraenviolote::findOrFail($key);
        $dadosfiliais = $dados->filiais;
        $dadosfiliais = json_decode(json_encode($dadosfiliais),true);

        foreach ($dadosfiliais as $key => $value) {
            $dadosfiliais[$key]['dadosFilial'] = Estabelecimento::select('cnpj', 'codigo')->where('id', $value['id_estabelecimento'])->get();
        }
        
        $dadosfiliais = json_decode(json_encode($dadosfiliais),true);    
        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $dados = json_decode(json_encode($dados),true);

        return view('regras.edit_lote')->with('dados', $dados)->with('dadosfiliais', $dadosfiliais)->withTributos($tributos)->withEmpresas($empresas);   
    }

    private function checkFiliais($id_regra)
    {
        $dados = DB::SELECT("SELECT id FROM regraenviolotefilial WHERE id_regraenviolote = ".$id_regra."");
        if (!empty($dados)) {
            return false;
        }        
        return true;
    }

    public function excluir(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!$this->checkFiliais($key)) {
            return redirect()->back()->with('alert', 'Para excluir esse registro, você terá que excluir os registros internos (Filiais cadastradas)!');
        }

        if (!empty($key)) {
            Regraenviolote::destroy($key);
        }

        return redirect()->back()->with('status', 'Regra excluída com sucesso!');
    }

    public function excluirFilial(request $request)
    {
        $id = $request->all();
        foreach ($id as $key => $value) {
        }

        if (!empty($key)) {
            Regraenviolotefilial::destroy($key);
        }

        return redirect()->back()->with('status', 'Filial excluída com sucesso!');
    }
}
