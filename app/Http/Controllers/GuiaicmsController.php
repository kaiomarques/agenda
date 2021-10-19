<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Liberarguias;
use DB;
use Illuminate\Http\Request;
use App\Models\Regra;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Models\Guiaicms;
use App\Models\CriticasLeitor;
use App\Models\CriticasEntrega;
use App\Models\Atividade;
use App\Models\EntregaExtensao;
use App\Models\User;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Mockery\Exception;
use Yajra\Datatables\Datatables;
use Carbon\Carbon;
use DateTime;
use Log;

/*
* Acre			icmsAC
* icmsAL
* icmsAP
*/

class GuiaicmsController extends Controller
{
    protected $eService;
    protected $logName;
    public $msg;
    public $estabelecimento_id;
	
	private $processedFiles;

    function __construct(EntregaService $service)
    {
        date_default_timezone_set(config('configICMSVars.wamp.timezone_brt'));
//        date_default_timezone_set('America/Belem');
        $this->eService = $service;
        if (!Auth::guest() && !empty(session()->get('seid')))
            $this->s_emp = Empresa::findOrFail(session('seid'));
        
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function AnyData(Request $request)
    {
        $status = 'success';
        
        $src_inicio = $request->get('src_inicio');
        $src_fim = $request->get('src_fim');
     
        $Registros = Guiaicms::where('ID', '>', '0');
        
        if ((!empty($src_inicio) && !empty($src_fim)) || (!empty(Session::get('src_inicio')) && !empty(Session::get('src_fim')))) {

            if (!empty($src_inicio) && !empty($src_fim)) {
                Session::put('src_inicio', $src_inicio);
                Session::put('src_fim', $src_fim);
            }

            $Registros = $Registros->whereBetween('DATA', [Session::get('src_inicio').' 00:00:00', Session::get('src_fim').' 23:59:59']);
        }
        
        $Registros = $Registros->get();
        $estabelecimentos = $this->findAllEstabelecimentos();

        if (!empty($Registros)) {
            foreach ($Registros as $k => $Registro) {
                if(isset($estabelecimentos[$Registro->CNPJ])) {
                    $estabelecimento = $estabelecimentos[$Registro->CNPJ];
                    $Registros[$k]['codigo'] = $estabelecimento->codigo;
                } else {
                    $Registros[$k]['codigo'] = null;
                }
            }
        }
        
        return Datatables::of($Registros)->make(true);
    }

    public function listar(Request $request)
    {
        return view('guiaicms.index')->with('src_inicio',$request->input("src_inicio"))
            ->with('src_fim',$request->input("src_fim"));
    }

    private function findAllEstabelecimentos()
    {
        $query = "SELECT codigo,cnpj FROM estabelecimentos";
        $filial = DB::select($query);

        foreach($filial as $f) {
            $estabelecimentos_resultado[$f->cnpj] = $f;
        }

        return $estabelecimentos_resultado;
    }

    private function findEstabelecimento($cnpj)
    {
        if (!empty($cnpj)) {

            $query = "SELECT codigo FROM estabelecimentos WHERE cnpj = '".$cnpj."'";
            $filial = DB::select($query);

            if (!empty($filial)) {
                return $filial[0]->codigo;
            }else {
                return 'Filial não encontrada';
            }
        }
        return 'Sem Cnpj';
    }

    public function create(Request $request)
    {
        $status = 'success';
        $this->msg = '';
        $input = $request->all();

        if (!empty($input)) {
            if (!$this->validation($input)) {
                $status = 'error';
                return view('guiaicms.create')->with('msg', $this->msg)->with('status', $status);
            }

            $estabelecimento = Estabelecimento::where('cnpj', '=', $this->numero($input['CNPJ']))->where('ativo', 1)->where('empresa_id','=',$this->s_emp->id)->first();
            $municipio = Municipio::where('codigo','=',$estabelecimento->cod_municipio)->first();
            $input['UF'] = $municipio->uf;
            $input['USUARIO'] = Auth::user()->id;
            $input['DATA'] = date('Y-m-d H:i:s');
            $input['CNPJ'] = $this->numero($input['CNPJ']);

            $input['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $input['VLR_RECEITA']));
            $input['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $input['VLR_TOTAL']));
            $input['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $input['MULTA_MORA_INFRA']));
            $input['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $input['JUROS_MORA']));
            $input['TAXA'] = str_replace(',', '.', str_replace('.', '', $input['TAXA']));
            $input['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $input['ACRESC_FINANC']));
            $input['CODBARRAS'] = trim($this->numero($input['CODBARRAS']));

            Guiaicms::create($input);
            $this->msg = 'Guia criada com sucesso';
        }

        return view('guiaicms.create')->with('msg', $this->msg)->with('status', $status);
    }

    private function validation($input)
    {
        if (empty($input['CNPJ'])) {
            $this->msg = 'Favor informar o cnpj';
            return false;
        }
        if (!empty($input['CNPJ'])) {
            $estabelecimento = Estabelecimento::where('cnpj', '=', $this->numero($input['CNPJ']))->where('ativo', 1)->where('empresa_id','=',$this->s_emp->id)->first();
            if (!empty($estabelecimento)) {
                $municipio = Municipio::where('codigo','=',$estabelecimento->cod_municipio)->first();
            }

            if (empty($estabelecimento)) {
                $this->msg = 'Estabelecimento não habilitado ou não existente';
                return false;
            }
        }

        if (empty($input['IE'])) {
            $this->msg = 'Favor informar a inscrição estadual';
            return false;
        }
        if (empty($input['COD_RECEITA'])) {
            $this->msg = 'Favor informar o código da receita';
            return false;
        }
        if (empty($input['REFERENCIA'])) {
            $this->msg = 'Favor informar a referência';
            return false;
        }
        if (empty($input['DATA_VENCTO'])) {
            $this->msg = 'Favor informar a data de vencimento';
            return false;
        }
        if (empty($input['VLR_RECEITA'])) {
            $this->msg = 'Favor informar o valor da receita';
            return false;
        }
        if (empty($input['JUROS_MORA'])) {
            $this->msg = 'Favor informar o Juros Mora ';
            return false;
        }
        if (empty($input['MULTA_MORA_INFRA'])) {
            $this->msg = 'Favor informar o valor da multa mora infra';
            return false;
        }
        if (empty($input['ACRESC_FINANC'])) {
            $this->msg = 'Favor informar o acrescimo financeiro ';
            return false;
        }
        if (empty($input['TAXA'])) {
            $this->msg = 'Favor informar a taxa';
            return false;
        }
        if (empty($input['VLR_TOTAL'])) {
            $this->msg = 'Favor informar o valor total da guia';
            return false;
        }

        if (strtolower($municipio->uf) != 'sp') {
            if (empty($input['CODBARRAS'])) {
                $this->msg = 'Favor informar o código de barras';
                return false;
            }
        }

        return true;
    }

    public function editar($id, Request $request)
    {
        $status = 'success';
        $this->msg = '';
        $input = $request->all();
        $guiaicms = Guiaicms::findOrFail($id);

        $guiaicms->VLR_RECEITA = $this->maskMoeda($guiaicms->VLR_RECEITA);
        $guiaicms->VLR_TOTAL = $this->maskMoeda($guiaicms->VLR_TOTAL);
        $guiaicms->MULTA_MORA_INFRA = $this->maskMoeda($guiaicms->MULTA_MORA_INFRA);
        $guiaicms->JUROS_MORA = $this->maskMoeda($guiaicms->JUROS_MORA);
        $guiaicms->TAXA = $this->maskMoeda($guiaicms->TAXA);
        $guiaicms->ACRESC_FINANC = $this->maskMoeda($guiaicms->ACRESC_FINANC);

        if (!empty($input)) {

            if (!$this->validation($input)) {
                $status = 'error';
                return view('guiaicms.editar')->with('icms', $guiaicms)->with('msg', $this->msg)->with('status', $status);
            }

            if (!empty($guiaicms)) {

                $estabelecimento = Estabelecimento::where('cnpj', '=', $this->numero($input['CNPJ']))->where('ativo', 1)->where('empresa_id','=',$this->s_emp->id)->first();
                $municipio = Municipio::where('codigo','=',$estabelecimento->cod_municipio)->first();
                $input['UF'] = $municipio->uf;
                $input['USUARIO'] = Auth::user()->id;
                $input['DATA'] = date('Y-m-d');
                $input['CNPJ'] = $this->numero($input['CNPJ']);
                $input['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $input['VLR_RECEITA']));
                $input['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $input['VLR_TOTAL']));
                $input['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $input['MULTA_MORA_INFRA']));
                $input['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $input['JUROS_MORA']));
                $input['TAXA'] = str_replace(',', '.', str_replace('.', '', $input['TAXA']));
                $input['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $input['ACRESC_FINANC']));
                $input['CODBARRAS'] = trim($this->numero($input['CODBARRAS']));

                $guiaicms->fill($input);
                $guiaicms->save();
                $this->msg = 'Guia atualizada com sucesso';
            }
        }

        return view('guiaicms.editar')->with('icms', $guiaicms)->with('msg', $this->msg)->with('status', $status);
    }

    public function excluir($id)
    {
        $this->msg = '';
        $status = 'success';

        if (!empty($id)) {
            Guiaicms::destroy($id);
            $this->msg = 'Registro excluído com sucesso';
            return redirect()->back()->with('status', 'Registro Excluido com sucesso.');
        }
    }

    public function Job()
    {
        $a = explode('/', $_SERVER['SCRIPT_FILENAME']);
        $path = '';

        $funcao = '';
        if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
            $path = $a[0];
        }
        $path .= '/storagebravobpo/';

        $arquivos = scandir($path);

        $data = array();
        foreach ($arquivos as $k => $v) {
            if (strpbrk($v, '0123456789１２３４５６７８９０')) {
                $path_name = $path.$v.'/';
                $data[$k]['arquivos'] = scandir($path_name);
                $data[$k]['path'] = $path_name;
            }
        }
	    
        foreach ($data as $X => $FILENAME) {
            foreach ($FILENAME as $L => $arquivos) {
                if (is_array($arquivos)) {
                    foreach ($arquivos as $A => $arquivo) {
                        if (substr(strtolower($arquivo), -3) == 'pdf') {
                            $arrayNameFile = explode("_", $arquivo);
                            if (empty($arrayNameFile[2])) {
                                continue;
                            }
	                        if ($this->letras($arrayNameFile[2]) != 'ICMS'
		                        && $this->letras($arrayNameFile[2]) != 'DIFAL'
		                        && $this->letras($arrayNameFile[2]) != 'ANTECIPADO'
		                        && $this->letras($arrayNameFile[2]) != 'TAXA'
		                        && $this->letras($arrayNameFile[2]) != 'PROTEGE'
		                        && $this->letras($arrayNameFile[2]) != 'UNIVERSIDADE'
		                        && $this->letras($arrayNameFile[2]) != 'FITUR'
		                        && $this->letras($arrayNameFile[2]) != 'FECP'
		                        && $this->letras($arrayNameFile[2]) != 'FEEF'
		                        && $this->letras($arrayNameFile[2]) != 'ICMSST'
		                        && $this->letras($arrayNameFile[2]) != 'PATROCINIO'
		                        && $this->letras($arrayNameFile[2]) != 'FUNTUR' && $this->letras($arrayNameFile[2]) != 'LIVROFISCAL' && $this->letras($arrayNameFile[3]) != 'LIVROFISCAL') {
		                        continue;
	                        }

                            $files[] = $FILENAME['path'].$arquivo;
                            if (count($files) >= 40) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        $funcao = 'pdftotext.exe ';

        if (!empty($files)) {
            foreach ($files as $K => $file) {
                $filetxt = str_replace(['.pdf', '.PDF'], '_CONVERTIDO.txt', $file);
                $caminho1 = explode('/', $filetxt);
                $caminho1_result = '';
                foreach ($caminho1 as $key => $value) {
                    $arquivonome = $value;
                    $caminho1_result .= $value.'/';
                    if (strpbrk($value, '0123456789１２３４５６７８９０')) {
                        $caminho1_result .= 'results/';
                    }
                }

                $caminho1_result = substr($caminho1_result, 0, -1);
                shell_exec($funcao.$file.' '.substr($caminho1_result, 0, -8));
//                echo $funcao.$file.' '.substr($caminho1_result, 0, -8); exit; // salsicha
                $destino = str_replace('results', 'imported', str_replace('txt', 'pdf', $caminho1_result));

                $arr[$file]['arquivo'] = str_replace('txt', 'pdf', $arquivonome);
                $arr[$file]['path'] = substr($destino, 0, -9);
                $arr[$file]['arquivotxt'] = $arquivonome;
                $arr[$file]['pathtxt'] = substr($caminho1_result, 0, -8);
            }
        }

//         echo "<pre>";
//         echo "<br>>>>>>>>>>> INI arr<br>";
//         print_r($arr);
//         echo "<br><<<<<<<<<< FIM arr<br>";
//         echo "</pre>";

        if (!empty($files)) {
            $this->saveICMS($arr);
        }

        if (empty($_GET['getType'])) {
            echo "Nenhum arquivo foi encontrado disponível para salvar";exit;
        }

        if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
            $cmd = $a[0].'\wamp64\bin\php\php5.6.40\php.exe '.$a[0].'\wamp64\www\agenda\public\Background\LeitorMails.php';
        }

        if (substr(php_uname(), 0, 7) == "Windows"){
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            exec($cmd . " > /dev/null &");
        }

        $mensagem = 'Concluído com sucesso';
        return view('guiaicms.job_return')->withMensagem($mensagem);
    }

    public function saveICMS($array, $return = false, $conferenciaguias_id = null)
    {
        $icmsarray = array();

        // echo "<pre>";
        // echo "<br>>>>>>>>>>> INI array (saveICMS)<br>";
        // print_r($array);
        // echo "<br><<<<<<<<<< FIM array<br>";
        // echo "</pre>";

        foreach ($array as $key => $value) {

            $arrayExplode = explode("_", $value['arquivo']);

            $AtividadeID = 0;
            if (!empty($arrayExplode[0]))
                $AtividadeID = $arrayExplode[0];

            $CodigoEstabelecimento = 0;
            if (!empty($arrayExplode[1]))
                $CodigoEstabelecimento = $arrayExplode[1];

            $NomeTributo = '';
            if (!empty($arrayExplode[2]))
                $NomeTributo = $this->letras($arrayExplode[2]);

            $PeriodoApuracao = '';
            if (!empty($arrayExplode[3]))
                $PeriodoApuracao = $arrayExplode[3];

            $UF = '';
            if (!empty($arrayExplode[4]))
                $UF = substr($arrayExplode[4], 0, 2);

            $estemp_id = 0;
            $arrayEstempId = DB::select('select id FROM estabelecimentos where codigo = "'.$CodigoEstabelecimento.'" ');
            if (!empty($arrayEstempId[0]->id)) {
                $estemp_id = $arrayEstempId[0]->id;
            }

            $validateAtividade = DB::select("Select count(1) as countAtividade from atividades where id = ".$AtividadeID."");
            if (!$validateAtividade[0]->countAtividade) {
                $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'A Atividade não existe', 'N');
                continue;
            }

            $arqu = 'foo '.$value['arquivotxt'].' bar';

            // echo "<pre>";
            // echo "<br>>>>>>>>>>> INI arqu<br>";
            // print_r($arqu);
            // echo "<br>";
            // print_r(strpos($arqu, 'PR'));
            // echo "<br>";
            // print_r(substr($arqu, -21));
            // echo "<br>";
            // echo "<br><<<<<<<<<< FIM arqu<br>";
            // echo "</pre>";

            if (strpos($arqu, '_SP_') && substr($arqu, -22) == '_SP_CONVERTIDO.txt bar') {
		            if($NomeTributo == 'PATROCINIO'){
			              $icmsarray = $this->patrocinioSP($value);
		            }else{
			              $icmsarray = $this->icmsSP($value);
		            }
            }

            if (strpos($arqu, '_RJ_') && substr($arqu, -22) == '_RJ_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsRJ($value);
            }

            if (strpos($arqu, '_RS_') && substr($arqu, -22) == '_RS_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsRS($value);
            }

            if (strpos($arqu, '_AL_') && substr($arqu, -22) == '_AL_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsAL($value);
            }

            if (strpos($arqu, '_DF_') && substr($arqu, -22) == '_DF_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsDF($value);
            }

            if (strpos($arqu, '_PA_') && substr($arqu, -22) == '_PA_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsPA($value);
            }

            if (strpos($arqu, '_GO_') && substr($arqu, -22) == '_GO_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsGO($value);
            }

            if (strpos($arqu, '_ES_') && substr($arqu, -22) == '_ES_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsES($value);
            }

            if (strpos($arqu, '_PB_') && substr($arqu, -22) == '_PB_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsPB($value);
            }

            if (strpos($arqu, '_SE_') && substr($arqu, -22) == '_SE_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsSE($value);
            }

            if (strpos($arqu, '_BA_') && substr($arqu, -22) == '_BA_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsBA($value);
            }

            if (strpos($arqu, '_RN_') && substr($arqu, -22) == '_RN_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsRN($value);
            }

            if (strpos($arqu, '_PE_') && substr($arqu, -22) == '_PE_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsPE($value);
            }

            if (strpos($arqu, '_MA_') && substr($arqu, -22) == '_MA_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsMA($value);
            }

            if (strpos($arqu, '_MG_') && substr($arqu, -22) == '_MG_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsMG($value);
            }

            if (strpos($arqu, '_CE_') && substr($arqu, -22) == '_CE_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsCE($value);
            }

            if (strpos($arqu, '_PI_') && substr($arqu, -22) == '_PI_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsPI($value);
            }

            if (strpos($arqu, '_PR_') && substr($arqu, -22) == '_PR_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsPR($value);
            }

            if (strpos($arqu, '_MS_') && substr($arqu, -22) == '_MS_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsMS($value);
            }

            if (strpos($arqu, '_MT_') && substr($arqu, -22) == '_MT_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsMT($value);
            }

            if (strpos($arqu, '_SC_') && substr($arqu, -22) == '_SC_CONVERTIDO.txt bar') {
                $icmsarray = $this->icmsSC($value);
            }
	        
            if (!empty($icmsarray)) {
                foreach ($icmsarray as $key => $icms) {
                    if (empty($icms) || count($icms) < 6) {
                        $this->createCritica(1, 0, 8, $value['arquivo'], 'Não foi possível ler o arquivo', 'N');
                        continue;
                    }

                    $validateAtividade = DB::select("Select COUNT(1) as countAtividade FROM atividades where id = ".$AtividadeID);

                    if (empty($AtividadeID) || !$validateAtividade[0]->countAtividade) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Código de atividade não existe', 'N');
                        continue;
                    }
	                
                    $validateCodigo = DB::select("Select COUNT(1) as countCodigo FROM atividades where id = ".$AtividadeID. " AND estemp_id = ".$estemp_id);
                    if (!$estemp_id || !$validateCodigo[0]->countCodigo) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Filial divergente com a filial da atividade', 'N');
                        continue;
                    }

                    $validateTributo = DB::select("Select count(1) as countTributo from regras where id = (select regra_id from atividades where id = ".$AtividadeID.") and tributo_id = 8 or tributo_id = 28");
                    if (!$validateTributo[0]->countTributo) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'O Tributo ICMS não confere com o tributo da atividade', 'N');
                        continue;
                    }

                    $validatePeriodoApuracao = DB::select("Select COUNT(1) as countPeriodoApuracao FROM atividades where id = ".$AtividadeID. " AND periodo_apuracao = '{$PeriodoApuracao}'");
                    if (empty($PeriodoApuracao) || !$validatePeriodoApuracao[0]->countPeriodoApuracao) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Período de apuração diverente do período da atividade', 'N');
                        continue;
                    }

                    $validateUF = DB::select("select count(1) as countUF FROM municipios where codigo = (select cod_municipio from estabelecimentos where id = (select estemp_id FROM atividades where id = ".$AtividadeID.")) AND uf = '".$UF."'");

                    if (empty($UF) || !$validateUF[0]->countUF) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'UF divergente da UF da filial da atividade', 'N');
                        continue;
                    }

                    $alertCentroCusto = DB::select("select count(1) countCentroCusto FROM centrocustospagto where estemp_id = ".$estemp_id." AND centrocusto <> '' AND centrocusto is not null");
                    if (!$alertCentroCusto[0]->countCentroCusto) {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Centro de custo não cadastrado', 'S');
                    }

                    $alertCodigoSap = DB::select("select count(1) as countCodigoSap FROM municipios where codigo = (select cod_municipio from estabelecimentos where id = ".$estemp_id.") AND codigo_sap <> '' AND codigo_sap is not null");
                    if (!$alertCodigoSap[0]->countCodigoSap && $UF == 'SP') {
                        $this->createCritica(1, $estemp_id, 8, $value['arquivo'], 'Código SAP do Municipio não cadastrado', 'S');
                    }

                    //if (!$this->validateEx($icms)) {
                    //    continue;
                    //}
                    //Pedido de remoção da validação pois em alguns casos é necessário importar a validação de regisros iguais


                    if (!empty($icms['COD_RECEITA'])) {
                        $icms['COD_RECEITA'] = strtoupper($icms['COD_RECEITA']);
                    }

                    if (!empty($icms['UF'])) {
                        $icms['UF'] = strtoupper($icms['UF']);
                    }

                    $icms['DATA'] = date('Y-m-d H:i:s');
                    if (!empty($_GET['getType'])) {
                        $input['USUARIO'] = Auth::user()->id;
                    }
	
	                  $icms['CONFERENCIAGUIAS_ID'] = null;
                    if($conferenciaguias_id != null){
	                    $icms['CONFERENCIAGUIAS_ID'] = $conferenciaguias_id;
                    }

//                    echo '<pre>',print_r($icms); exit;
                    Guiaicms::create($icms);
                    $strFile = str_replace('_CONVERTIDO', '', $value['path']);
                    $destino = str_replace('/imported', '', $strFile);
                    if (file_exists($destino)) {
//                        @copy($destino, $strFile);
//                        @unlink($destino);
                    }
                }
            }
        }

        if($return == true){
          return true;
        }
        
        if (empty($_GET['getType'])) {
            echo "Dados gravados com sucesso"; exit;
        }

        $mensagem = 'Dados gravados com sucesso';
        return view('guiaicms.job_return')->withMensagem($mensagem);

    }

    public function createCritica($empresa_id=1, $estemp_id=0, $tributo_id=8, $arquivo, $critica, $importado)
    {
        $array['importado']     = $importado;
        $array['critica']       = $critica;
        $array['arquivo']       = $arquivo;
        $array['Tributo_id']    = $tributo_id;
        $array['Estemp_id']     = $estemp_id;
        $array['Empresa_id']    = $empresa_id;
        $array['Data_critica']  = date('Y-m-d h:i:s');
        CriticasLeitor::create($array);
    }

    public function createCriticaEntrega($empresa_id=1, $estemp_id=0, $tributo_id=8, $arquivo, $critica, $importado)
    {
        $array['importado']     = $importado;
        $array['critica']       = $critica;
        $array['arquivo']       = $arquivo;
        $array['Tributo_id']    = $tributo_id;
        $array['Estemp_id']     = $estemp_id;
        $array['Empresa_id']    = $empresa_id;
        $array['Data_critica']  = date('Y-m-d h:i:s');
        CriticasEntrega::create($array);
    }

    public function validateEx($icms)
    {
        if (empty($icms)) {
            return false;
        }
        if (empty($icms['CNPJ'])) {
            $icms['CNPJ'] = 0;
        }
        if (empty($icms['REFERENCIA'])) {
            $icms['REFERENCIA'] = 0;
        }
        $query = 'SELECT * FROM guiaicms WHERE CNPJ = "'.$icms['CNPJ'].'" AND REFERENCIA = "'.$icms['REFERENCIA'].'" AND TRIBUTO_ID = '.$icms['TRIBUTO_ID'].'';

        if (!empty($icms['VLR_TOTAL'])) {
            $query .= ' AND VLR_TOTAL = '.$icms['VLR_TOTAL'];
        }

        if (!empty($icms['IMPOSTO'])) {
            $query .= ' AND IMPOSTO = "'.$icms['IMPOSTO'].'"';
        }

        $validate = DB::select($query);
        if (!empty($validate)) {
            return false;
        }

        return true;
    }

    public function icmsMT($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'MT';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL' || $this->letras($file_content[2]) == 'FUNTUR') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }
	    
        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~05 - cnpj ou cpf([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['CNPJ'] = trim(preg_replace("/[^0-9]/", "", $i[0]));
        }

        preg_match('~06 - inscricao estadual([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~25 - codigo([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['COD_RECEITA'] = trim($i[0]);
        }

        preg_match('~21 - periodo ref.([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 4) $icms['REFERENCIA'] = trim($i[4]);
        }

        preg_match('~22 - data vencto.([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $valorData = $i[0];
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }

        preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));

            if(isset($i) && count($i) > 0) {
                $a = explode(' ', $i[0]);
                if(isset($a) && count($a) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }
        }

        preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $a = explode(' ', $i[0]);
                if(isset($a) && count($a) > 3) $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[3])));
            }
        }

        preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $a = explode(' ', $i[0]);
                if(isset($a) && count($a) > 2) $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[2])));
            }

        }

        preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 1) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[1])));
        }

        preg_match('~33 - valor a recolher por extenso
novecentos e quarenta e quatro reais e quinze centavos
modelo aprovada pela portaria nº 085/2002([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
                $icms['CODBARRAS'] = $codbarras;
            }
        }

        if (empty($icms['CODBARRAS'])) {
            preg_match('~modelo aprovada pela portaria([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 1) {
                    $codbarras = str_replace('-', '', str_replace(' ', '', $i[1]));
                    $icms['CODBARRAS'] = $codbarras;
                }
            }
        }

        if (isset($icms['VLR_RECEITA']) && !is_numeric($icms['VLR_RECEITA'])) {

            preg_match('~21 - periodo ref.([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = trim($i[0]);
            }

            preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0)
                {
                    $a = explode(' ', $i[6]);
                    if(isset($a) && count($a) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                }

            }

            preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $a = explode(' ', $i[6]);
                    if(isset($a) && count($a) > 0) $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[3])));
                }
            }

            preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $a = explode(' ', $i[6]);
                    if(isset($a) && count($a) > 0) $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[2])));
                }
            }

            preg_match('~40 - autenticacao mecanica([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[7])));
            }


            preg_match('~modelo aprovada pela portaria([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $codbarras = str_replace('-', '', str_replace(' ', '', $i[2]));
                    $icms['CODBARRAS'] = $codbarras;
                }
            }

        }
		
		if(isset($icms['REFERENCIA'])) {
			$v = $this->numero($icms['REFERENCIA']);
			if (empty($v)) {
				preg_match('~21 - periodo ref.([^{]*)~i', $str, $match);
				if (!empty($match)) {
					if(isset($i) && count($i) > 0) {
						$i = explode("\n", trim($match[1]));
						$icms['REFERENCIA'] = trim($i[0]);
					}
				}
			}	
		}
	
	    preg_match('~26 - valor([^{]*)~i', $str, $match);
	    if (!empty($match)) {
		    $i = str_replace(PHP_EOL, PHP_EOL, trim($match[1]));
		    $i = explode("\n", trim($i));
		    $arr = [];
		    foreach($i as $value){
		    	if(strlen($value) > 1){
		    		$arr[] = $value;
			    }
		    }
				foreach($arr as $key => $value){
					if(trim($value) == 'mecanica'){
						$output = array_slice($arr, $key);
						$icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($output[1])));
						$icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($output[3])));
						$icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($output[4])));
						$icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($output[6])));
						break;
					}
				}
	    }
	
	    if(empty($icms['VLR_TOTAL'])){
		    $icms['VLR_TOTAL'] = $icms['VLR_RECEITA'];
	    }
	    
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms; //echo '<pre>', print_r($icmsarray[0]); exit;
        return $icmsarray;
    }

    public function icmsMS($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);

        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'MS';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"), $str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) == 'TAXA' || $this->letras($file_content[2]) == 'PROTEGE' || $this->letras($file_content[2]) == 'FECP' || $this->letras($file_content[2]) == 'FEEF' || $this->letras($file_content[2]) == 'UNIVERSIDADE' || $this->letras($file_content[2]) == 'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        // echo "<pre>";
        // echo "<br>>>>>>>>>>> INI str<br>";
        // print_r($str);
        // echo "<br><<<<<<<<<< FIM str<br>";
        // echo "</pre>";

        // adição de desvio para processar guias sem anterar antigo
        preg_match('~emissao pelo site: www.sefaz.ms.gov.br. nao use copias, emita um daems por pagamento.([^{]*)~i', $str, $match_layout_novo);
        if (!empty($match_layout_novo)) { // BEGIN layout novo
            // echo "<pre>";
            // echo "<br>>>>>>>>>>>>>>>>>>>>> INI match_layout_novo<br>";
            // print_r($match_layout_novo);
            // echo "<br><<<<<<<<<<<<<<<<<<<< FIM match_layout_novo<br>";
            // echo "</pre>";
            $varArrTxt = explode("\n", trim($match_layout_novo[1]));
            // echo "<pre>";
            // echo "<br>>>>>>>>>>>>>>>>>>>>> INI varArrTxt<br>";
            // print_r($varArrTxt);
            // echo "<br><<<<<<<<<<<<<<<<<<<< FIM varArrTxt<br>";
            // echo "</pre>";

            $icms['CODBARRAS'] = str_replace('-', '', str_replace(' ', '', $varArrTxt[0]));
            $icms['IE'] = trim($this->numero($icms['IE']));

            if (trim($varArrTxt[16]) == 'documento de arrecadacao estadual') {
                // 01-codigo do tributo [25]
                $icms['COD_RECEITA'] = trim($varArrTxt[25]);
                // 04-referencia [37]
                $icms['REFERENCIA'] = trim($varArrTxt[37]);
                // 02-vencimento [29]
                $valorData = trim($varArrTxt[29]);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                // 06-principal [43]
                $vlrPrincipal2 = explode(' ', trim($varArrTxt[43]));
                $vlrPrincipal = $vlrPrincipal2[1];
                if (strlen($vlrPrincipal) <= 6) {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', $vlrPrincipal);
                } else {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $vlrPrincipal));
                }

                // 07-multa 08-juros 09-correcao monetaria 10-total
                if (trim($varArrTxt[45]) == '07-multa 08-juros 09-correcao monetaria 10-total') {
                    // 07-multa 08-juros 09-correcao monetaria 10-total [47]
                    $arrValores = explode(' ',$varArrTxt[47]);
                    // 07-multa 08-juros [0]
                    $vlrMulta = $arrValores[0];
                    if (strlen($vlrMulta) <= 6) {
                        $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                    } else {
                        $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                    }
                    // 07-multa 08-juros [1]
                    $vlrJuros = $arrValores[1];
                    if (strlen($vlrJuros) <= 6) {
                        $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                    } else {
                        $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                    }

                    // 09-correcao monetaria 10-total [2]
                    $vlrCorrecao = $arrValores[2];
                    if (strlen($vlrCorrecao) <= 6) {
                        $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                    } else {
                        $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                    }
                    // 09-correcao monetaria 10-total [3]
                    $vlrTotal = $arrValores[3];
                    if (strlen($vlrTotal) <= 6) {
                        $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                    } else {
                        $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                    }
                } elseif (trim($varArrTxt[39]) == '07-multa 08-juros' && trim($varArrTxt[44]) == '09-correcao monetaria 10-total') {
                    // 07-multa 08-juros 09-correcao monetaria 10-total [46]
                    $arrValores = explode(' ',$varArrTxt[46]);
                    // 07-multa 08-juros [0]
                    $vlrMulta = $arrValores[0];
                    if (strlen($vlrMulta) <= 6) {
                        $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                    } else {
                        $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                    }
                    // 07-multa 08-juros [1]
                    $vlrJuros = $arrValores[1];
                    if (strlen($vlrJuros) <= 6) {
                        $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                    } else {
                        $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                    }

                    // 09-correcao monetaria 10-total [2]
                    $vlrCorrecao = $arrValores[2];
                    if (strlen($vlrCorrecao) <= 6) {
                        $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                    } else {
                        $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                    }
                    // 09-correcao monetaria 10-total [3]
                    $vlrTotal = $arrValores[3];
                    if (strlen($vlrTotal) <= 6) {
                        $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                    } else {
                        $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                    }
                } else {
                    // 07-multa 08-juros 09-correcao monetaria 10-total
                    // 07-multa [45, 47] 08-juros [49, 51] 09-correcao monetaria [53, 55] 10-total [57, 59]
                    // 07-multa 08-juros [47]
                    $vlrMulta = $varArrTxt[47];
                    if (strlen($vlrMulta) <= 6) {
                        $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                    } else {
                        $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                    }
                    // 07-multa 08-juros [51]
                    $vlrJuros = $varArrTxt[51];
                    if (strlen($vlrJuros) <= 6) {
                        $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                    } else {
                        $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                    }

                    // 09-correcao monetaria 10-total [55]
                    $vlrCorrecao = $varArrTxt[55];
                    if (strlen($vlrCorrecao) <= 6) {
                        $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                    } else {
                        $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                    }
                    // 09-correcao monetaria 10-total [59]
                    $vlrTotal = $varArrTxt[59];
                    if (strlen($vlrTotal) <= 6) {
                        $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                    } else {
                        $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                    }
                }
            } elseif (trim($varArrTxt[26]) == 'documento de arrecadacao estadual' && trim($varArrTxt[10]) == '01-codigo do tributo 02-vencimento') {
				// novo layout incluído em 2020-06-22 14:21 - Vinny <marcus.coimbra@bravocorp.com.br> L1005:L1238
				if (!isset($icms['IE']) || $icms['IE'] == "") {
					// 03-cpf/cnpj/ie/renavam [20]
					$icms['IE'] = trim($varArrTxt[21]);
				}
                // 01-codigo do tributo 02-vencimento [10]
                $icms['COD_RECEITA'] = trim($varArrTxt[12]);
                // 04-referencia [22]
                $icms['REFERENCIA'] = trim($varArrTxt[23]);
                // 02-vencimento [19]
                $valorData = trim($varArrTxt[19]);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                // 06-principal [29]
                $vlrPrincipal2 = explode(' ', trim($varArrTxt[31]));
                $vlrPrincipal = $vlrPrincipal2[1];
                if (strlen($vlrPrincipal) <= 6) {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', $vlrPrincipal);
                } else {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $vlrPrincipal));
                }

                // 07-multa 08-juros 09-correcao monetaria 10-total [40]
                if (trim($varArrTxt[45]) == '07-multa 08-juros 09-correcao monetaria 10-total' || trim($varArrTxt[40]) == '07-multa 08-juros 09-correcao monetaria 10-total') {
					if (!isset($icms['IE']) || $icms['IE'] == "") {
						// 03-cpf/cnpj/ie/renavam [20]
						$icms['IE'] = trim($varArrTxt[21]);
					}
                    // 07-multa 08-juros 09-correcao monetaria 10-total [42]
                    $arrValores = explode(' ',$varArrTxt[42]);
                    // 07-multa 08-juros [0]
                    if(isset($arrValores[0])) {
                        $vlrMulta = $arrValores[0];
                        if (strlen($vlrMulta) <= 6) {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                        } else {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                        }
                    }
                    // 07-multa 08-juros [1]
                    if(isset($arrValores[1])) {
                        $vlrJuros = $arrValores[1];
                        if (strlen($vlrJuros) <= 6) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                        } else {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                        }
                    }

                    // 09-correcao monetaria 10-total [2]

                    if(isset($arrValores[2])) {
                        $vlrCorrecao = $arrValores[2];
                        if (strlen($vlrCorrecao) <= 6) {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                        } else {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                        }
                    }
					
					if (count($arrValores) > 3) {
						// 09-correcao monetaria 10-total [3]
						$vlrTotal = $arrValores[3];
						if (strlen($vlrTotal) <= 6) {
							$icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
						} else {
							$icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
						}
					} elseif (count($arrValores) == 3) {
						// 09-correcao monetaria 10-total [3]
						$vlrTotal = $varArrTxt[43];
						if (strlen($vlrTotal) <= 6) {
							$icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
						} else {
							$icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
						}
					} elseif (count($arrValores) == 2) {
						// 09-correcao monetaria 10-total [3]
						$vlrTotal = explode(" ", $varArrTxt[43]);
						$vlrTotal = $vlrTotal[1];
						if (strlen($vlrTotal) <= 6) {
							$icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
						} else {
							$icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
						}
					}
                } elseif (trim($varArrTxt[41]) == '07-multa 08-juros 09-correcao monetaria 10-total') {
					if (!isset($icms['IE']) || $icms['IE'] == "") {
						// 03-cpf/cnpj/ie/renavam [20]
						$icms['IE'] = trim($varArrTxt[21]);
					}
                    // 07-multa 08-juros 09-correcao monetaria 10-total [42]
                    $arrValores = explode(' ',$varArrTxt[43]);
                    // 07-multa 08-juros [0]
                    $vlrMulta = $arrValores[0];
                    if (strlen($vlrMulta) <= 6) {
                        $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                    } else {
                        $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                    }
                    // 07-multa 08-juros [1]
                    if(isset($arrValores[1])) {
                        $vlrJuros = $arrValores[1];
                        if (strlen($vlrJuros) <= 6) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                        } else {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                        }
                    }
                    // 09-correcao monetaria 10-total [2]
                    if(isset($arrValores[2])) {
                        $vlrCorrecao = $arrValores[2];
                        if (strlen($vlrCorrecao) <= 6) {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                        } else {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                        }
                    }
                    // 09-correcao monetaria 10-total [3]
                    $vlrTotal = $varArrTxt[44];
					
					if(isset($varArrTxt[44]) && strlen($varArrTxt[44]) == 1) {
						$vlrTotal = explode(" ",$varArrTxt[43]);
						$vlrTotal = end($vlrTotal);
					}

                    if (strlen($vlrTotal) <= 6) {
                        $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                    } else {
                        $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                    }
                } elseif (trim($varArrTxt[39]) == '07-multa 08-juros' && trim($varArrTxt[44]) == '09-correcao monetaria 10-total') {
					if (!isset($icms['IE']) || $icms['IE'] == "") {
						// 03-cpf/cnpj/ie/renavam [20]
						$icms['IE'] = trim($varArrTxt[21]);
					}
                    // 07-multa 08-juros 09-correcao monetaria 10-total [46]
                    $arrValores = explode(' ',$varArrTxt[46]);
                    // 07-multa 08-juros [0]
                    if(isset($arrValores[0])) {
                        $vlrMulta = $arrValores[0];
                        if (strlen($vlrMulta) <= 6) {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                        } else {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                        }
                    }
                    // 07-multa 08-juros [1]
                    if(isset($arrValores[1])) {                    
                        $vlrJuros = $arrValores[1];
                        if (strlen($vlrJuros) <= 6) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                        } else {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                        }
                    }

                    // 09-correcao monetaria 10-total [2]
                    if(isset($arrValores[2])) {
                        $vlrCorrecao = $arrValores[2];
                        if (strlen($vlrCorrecao) <= 6) {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                        } else {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                        }
                    }
                    // 09-correcao monetaria 10-total [3]
                    if(isset($arrValores[3])) {
                        $vlrTotal = $arrValores[3];
                        if (strlen($vlrTotal) <= 6) {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                        } else {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                        }
                    }
                } elseif (trim($varArrTxt[38]) == '07-multa 08-juros' && trim($varArrTxt[43]) == '09-correcao monetaria 10-total') {
					if (!isset($icms['IE']) || $icms['IE'] == "") {
						// 03-cpf/cnpj/ie/renavam [20]
						$icms['IE'] = trim($varArrTxt[21]);
					}
                    // 07-multa 08-juros 09-correcao monetaria 10-total [45]
					$arrValores = explode(' ',$varArrTxt[45]);
                    if(isset($arrValores[0])) {					
                        // 07-multa 08-juros [0]
                        $vlrMulta = $arrValores[0];
                        if (strlen($vlrMulta) <= 6) {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                        } else {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                        }
                    }
                    // 07-multa 08-juros [1]
                    if(isset($arrValores[1])) {
                        $vlrJuros = $arrValores[1];
                        if (strlen($vlrJuros) <= 6) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                        } else {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                        }
                    }

                    // 09-correcao monetaria 10-total [2]
                    if(isset($arrValores[2])) {
                        $vlrCorrecao = $arrValores[2];
                        if (strlen($vlrCorrecao) <= 6) {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                        } else {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                        }
                    }
					
					if (count($arrValores) > 3) {
						// 09-correcao monetaria 10-total [3]
						$vlrTotal = $arrValores[3];
						if (strlen($vlrTotal) <= 6) {
							$icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
						} else {
							$icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
						}
					} elseif (count($arrValores) == 3) {
						// 10-total [46]
						$vlrTotal = $varArrTxt[46];
						if (strlen($vlrTotal) <= 6) {
							$icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
						} else {
							$icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
						}
					}
                } elseif (trim($varArrTxt[41]) == '07-multa' && trim($varArrTxt[45]) == '08-juros' && trim($varArrTxt[49]) == '09-correcao monetaria 10-total') {
					if (!isset($icms['IE']) || $icms['IE'] == "") {
						// 03-cpf/cnpj/ie/renavam [20]
						$icms['IE'] = trim($varArrTxt[21]);
					}
                    // 07-multa [41]
                    if(isset($varArrTxt[43])) {
                        $vlrMulta = $varArrTxt[43];
                        if (strlen($vlrMulta) <= 6) {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                        } else {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                        }
                    }
                    // 08-juros [45]
                    
                    if(isset($varArrTxt[47])) {
                        $vlrJuros = $varArrTxt[47];
                        if (strlen($vlrJuros) <= 6) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                        } else {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                        }
                    }
                    // 09-correcao monetaria 10-total [49]
                    if(isset($varArrTxt[51])) {
                        $vlrCorrecao = $varArrTxt[51];
                        if (strlen($vlrCorrecao) <= 6) {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                        } else {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                        }
                    }
                    // 09-correcao monetaria 10-total [49]
                    if(isset($varArrTxt[0])) {
                    $vlrTotal = $varArrTxt[53];
                        if (strlen($vlrTotal) <= 6) {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                        } else {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                        }
                    }
                } else {
                    // 07-multa 08-juros 09-correcao monetaria 10-total
                    // 07-multa [45, 47] 08-juros [49, 51] 09-correcao monetaria [53, 55] 10-total [57, 59]
                    // 07-multa 08-juros [47]
                    if(isset($varArrTxt[47])) {
                        $vlrMulta = $varArrTxt[47];
                        if (strlen($vlrMulta) <= 6) {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                        } else {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                        }
                    }
                    if(isset($varArrTxt[51])) {
                        // 07-multa 08-juros [51]
                        $vlrJuros = $varArrTxt[51];
                        if (strlen($vlrJuros) <= 6) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                        } else {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                        }
                    }
                    if(isset($varArrTxt[55])) {
                        // 09-correcao monetaria 10-total [55]
                        $vlrCorrecao = $varArrTxt[55];
                        if (strlen($vlrCorrecao) <= 6) {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                        } else {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                        }
                    }
                        // 09-correcao monetaria 10-total [59]
                    if(isset($varArrTxt[59])) {    
                        $vlrTotal = $varArrTxt[59];
                        if (strlen($vlrTotal) <= 6) {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                        } else {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                        }
                    }
                }
            } else {
                if(isset($varArrTxt[16])) $icms['COD_RECEITA'] = trim($varArrTxt[16]);
                // 04-referencia [22]
                if(isset($varArrTxt[22])) $icms['REFERENCIA'] = trim($varArrTxt[22]);
                // 02-vencimento [18]
                if(isset($varArrTxt[18])) $valorData = trim($varArrTxt[18]);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                // 06-principal [33]
                if(isset($varArrTxt[33])) $vlrPrincipal = trim($varArrTxt[33]);
                if (strlen($vlrPrincipal) <= 6) {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', $vlrPrincipal);
                } else {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $vlrPrincipal));
                }

                // 07-multa 08-juros 09-correcao monetaria 10-total [49]
                // 07-multa 08-juros [48, 49] 09-correcao monetaria 10-total [47, 49]
                $strArrTxt16 = preg_replace("/[^0-9]/", '', $varArrTxt[16]);
                if (isset($varArrTxt) && count($varArrTxt) > 49  &&
                    is_numeric($strArrTxt16) && trim($varArrTxt[42]) == '07-multa 08-juros' && 
                    trim($varArrTxt[47]) == '09-correcao monetaria 10-total') 
                {
                    $arrValores = explode(' ', $varArrTxt[49]);
                    // 07-multa 08-juros [0]
                    if(isset($arrValores[0]))  {
                        $vlrMulta = $arrValores[0];
                        if (strlen($vlrMulta) <= 6) {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                        } else {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                        }
                    }
                    
                    // 07-multa 08-juros [1]
                    if(isset($arrValores[1]))  {
                        $vlrJuros = $arrValores[1];
                        if (strlen($vlrJuros) <= 6) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                        } else {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                        }
                    }
                    // 09-correcao monetaria 10-total [2]
                    if(isset($arrValores[2]))  {
                        $vlrCorrecao = $arrValores[2];
                        if (strlen($vlrCorrecao) <= 6) {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                        } else {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                        }
                    }
                    // 09-correcao monetaria 10-total [3]
                    if(isset($arrValores[3]))  {
                        $vlrTotal = $arrValores[3];
                        if (strlen($vlrTotal) <= 6) {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                        } else {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                        }
                    }
                } else if (
                    isset($varArrTxt) && count($varArrTxt) > 50 &&
                    is_numeric($strArrTxt16) && trim($varArrTxt[38]) == '07-multa 08-juros' && 
                    trim($varArrTxt[48]) == '09-correcao monetaria 10-total') 
                {
                    // 07-multa 08-juros 09-correcao monetaria 10-total [40, 50]
                    // 07-multa 08-juros [38, 40] 09-correcao monetaria 10-total [48, 50]
                    $arrValores1 = explode(' ', $varArrTxt[40]);
                    $arrValores2 = explode(' ', $varArrTxt[50]);
                    // 07-multa 08-juros [0]
                    if(isset($arrValores1[0])) {
                        $vlrMulta = $arrValores1[0];
                        if (strlen($vlrMulta) <= 6) {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                        } else {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                        }
                    }
                    // 07-multa 08-juros [40]
                    if(isset($arrValores1[1])) {
                        $vlrJuros = $arrValores1[1];
                        if (strlen($vlrJuros) <= 6) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                        } else {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                        }
                    }
                    // 09-correcao monetaria 10-total [50]
                    if(isset($arrValores2[0])) {
                        $vlrCorrecao = $arrValores2[0];
                        if (strlen($vlrCorrecao) <= 6) {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                        } else {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                        }
                    }
                    // 09-correcao monetaria 10-total [1]
                    if(isset($arrValores2[1])) {
                        $vlrTotal = $arrValores2[1];
                        if (strlen($vlrTotal) <= 6) {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                        } else {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                        }
                    }
                } else if (is_numeric($strArrTxt16) && trim($varArrTxt[44]) == '07-multa' && trim($varArrTxt[48]) == '08-juros' && trim($varArrTxt[52]) == '09-correcao monetaria 10-total') {
                    // 07-multa 08-juros 09-correcao monetaria 10-total [46, 50, 54, 56]
                    // 07-multa [44, 46]
                    // 08-juros [48, 50]
                    // 09-correcao monetaria 10-total [52, 54, 56]

                    // 07-multa [46]
                    if(isset($varArrTxt[46])) {
                        $vlrMulta = $varArrTxt[46];
                        if (strlen($vlrMulta) <= 6) {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', $vlrMulta);
                        } else {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $vlrMulta));
                        }
                    }
                        // 08-juros [50]
                    if(isset($varArrTxt[50])) {
                        $vlrJuros = $varArrTxt[50];
                        if (strlen($vlrJuros) <= 6) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', $vlrJuros);
                        } else {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $vlrJuros));
                        }
                    }
                        // 09-correcao monetaria 10-total [54]
                    if(isset($varArrTxt[54])) {
                        $vlrCorrecao = $varArrTxt[54];
                        if (strlen($vlrCorrecao) <= 6) {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', $vlrCorrecao);
                        } else {
                            $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', $vlrCorrecao));
                        }
                    }
                    if(isset($varArrTxt[56])) {
                        // 09-correcao monetaria 10-total [56]
                        $vlrTotal = $varArrTxt[56];
                        if (strlen($vlrTotal) <= 6) {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                        } else {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                        }
                    }
                }
            }
        } else {
            preg_match('~03-cpf/cnpj/ie/renavam([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) $icms['IE'] = trim($this->numero($i[0]));
            }

            preg_match('~01-codigo do tributo 02-vencimento([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) $icms['COD_RECEITA'] = trim($i[0]);
            }

            preg_match('~04-referencia([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = trim($i[0]);
            }

            preg_match('~11 - codigo do municipio([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $valorData = trim($i[2]);
                $data_vencimento = str_replace('/', '-', $valorData);
                if(isset($i) && count($i) > 0) $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }

            preg_match('~06-principal([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0)
                {
                    $a = explode(' ', $i[0]);
                    if(isset($a) && count($a) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
                }
            }

            preg_match('~07-multa 08-juros 09-correcao monetaria 10-total([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $a = explode(' ', $i[0]);
                    if(isset($a) && count($a) > 0) $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
                }
            }

            preg_match('~07-multa 08-juros 09-correcao monetaria 10-total([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $a = explode(' ', $i[0]);
                    if(isset($a) && count($a) > 0) $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                }
            }

            preg_match('~07-multa 08-juros 09-correcao monetaria 10-total([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $a = explode(' ', $i[0]);
                    if(isset($a) && count($a) > 0) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[3])));
                }
            }

            preg_match('~emissao pelo site: www.sefaz.ms.gov.br. nao use copias, emita um daems por pagamento.([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                $icms['CODBARRAS'] = str_replace('-', '', str_replace(' ', '', $i[0]));
            }

            if (!isset($icms['VLR_TOTAL'])) {
                preg_match('~06-principal([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode("\n", trim($match[1]));
                    if(isset($i) && count($i) > 0) {
                        $a = explode(' ', $i[0]);
                        if(isset($a) && count($a) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
                    }
                }

                preg_match('~07-multa([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode("\n", trim($match[1]));
                    if(isset($i) && count($i) > 0) {
                        $a = explode(' ', $i[0]);
                        if(isset($a) && count($i) > 0) $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                    }
                }

                preg_match('~08-juros([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode("\n", trim($match[1]));
                    if(isset($i) && count($i) > 0) {
                        $a = explode(' ', $i[0]);
                        if(isset($a) && count($i) > 0) $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                    }
                }

                preg_match('~09-correcao monetaria 10-total([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode("\n", trim($match[1]));
                    if(isset($i) && count($i) > 2) {
                        $a = explode(' ', $i[0]);
                        if(isset($a) && count($a) > 0)  $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                        $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
                    }
                }
            }
        }

        // echo "<pre>";
        // echo "<br>>>>>>>>>>>>>>>>>>>>> INI icms[]<br>";
        // print_r($icms);
        // echo "<br><<<<<<<<<<<<<<<<<<<< FIM icms[]<br>";
        // echo "</pre>";

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }


    public function icmsRS($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/"),explode(" ","a A e E i I o O u U n N c C"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~razao social:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['CONTRIBUINTE'] = trim($i[0]);
        }

        preg_match('~produto:

cnpj/cpf/insc. est.:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['IE'] = preg_replace("/[^0-9]/", "", str_replace('.', '', trim($i[0])));
        }

        preg_match('~uf:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if (strlen($i[0]) > 2) {
                $i[0] = trim($i[0]);
                if(isset($i) && count($i) > 0) $icms['UF'] =substr($i[0], 0,2);
            }
        }

        preg_match('~periodo de referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~codigo da receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['COD_RECEITA'] = trim($i[0]);
        }

        preg_match('~data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $valorData = $i[0];
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }

        preg_match('~parcela([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 23) {
                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', $i[0])));
                $icms['ATUALIZACAO_MONETARIA'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', $i[5])));;
                $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', $i[11])));;
                $icms['ACRESC_FINANC'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', $i[17])));;
                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('r$', '', str_replace('.', '', str_replace('o', '', $i[23]))));;
            }
        }

        preg_match('~informacoes complementares:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['OBSERVACAO'] = trim($i[0]);
        }

        preg_match('~documento valido para pagamento ate([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[2]));
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }

        $arr = explode('_', $value['arquivotxt']);
        if (!empty($arr)) {
            $atividadeID = $arr[0];
            if (!is_numeric($atividadeID)) {
                $atividadeID = 0;
            }
            $atividade = json_decode(json_encode(DB::select('SELECT * FROM atividades where id = '.$atividadeID.' limit 1')),true);
            if (!empty($atividade)) {
                $estabelecimento = Estabelecimento::where('id', '=', $atividade[0]['estemp_id'])->where('ativo', '=', 1)->first();
                if (!empty($estabelecimento)) {
                    $icms['CNPJ'] = $estabelecimento->cnpj;
                    $icms['ENDERECO'] = $estabelecimento->endereco.', '.$estabelecimento->num_endereco;
                    $municipio = Municipio::findOrFail($estabelecimento->cod_municipio);
                    if (!empty($municipio)) {
                        $icms['MUNICIPIO'] = $municipio->nome;
                    }
                }
            }
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }



    public function icmsRJ($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);

        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['IE'] = $estabelecimento->insc_estadual;

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~\(01\) nome / razao social \(estabelecimento principal\)([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) $icms['CONTRIBUINTE'] = trim($i[0]);
        }

        preg_match('~\(10\) cnpj/cpf([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) $icms['CNPJ'] =str_replace('/', '', str_replace('-', '', str_replace('.', '', trim($i[0]))));
        }

        preg_match('~\(04\) uf ([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) $icms['UF'] = trim($i[0]);
        }

        preg_match('~periodo de referencia: data vencimento:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) {
                $l = explode(' ', $i[0]);
                if(count($l) >  1) {
                    $icms['REFERENCIA'] = trim($l[0]);
                    $valorData = trim($l[1]);
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                }
            }
        }

        if (empty($icms['IE'])) {
            preg_match('~apuracao \(debitos/creditos\) normal([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  1) $icms['IE'] = str_replace(',', '.', trim(str_replace('.', '', $i[1])));
            }
        }

        if (empty($icms['IE'])) {
            preg_match('~natureza da receita: cnpj/cpf: inscricao estadual/rj: nome/razao social: endereco: municipio: uf: cep: telefone:([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $a = explode(' ', trim($match[1]));
                if(isset($a) && count($a) >  0) $icms['IE'] = trim($this->numero($a[4]));
            }
        }

        preg_match('~\(06\) receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) $icms['COD_RECEITA'] = trim($i[0]);
        }

        preg_match('~\(13\) valor principal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $i[0])));
        }

        preg_match('~\(14\) juros de mora

\(15\) multa de mora([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  2) {
                $icms['JUROS_MORA'] = str_replace(',', '.', trim(str_replace('.', '', $i[0])));
                $a = explode(' ', $i[2]);
                if(isset($a) && count($a) >  0) $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $a[0]));
            }
        }

        preg_match('~\(16\) multa penal/formal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  1) {
                $a = explode(' ', $i[0]);
                if(isset($a) && count($a) >  0) $icms['MULTA_PENAL_FORMAL'] = str_replace(',', '.', trim(str_replace('.', '', $a[0])));
                $icms['VLR_TOTAL'] = str_replace(',', '.', trim(str_replace('.', '', $i[1])));
            }

        }

        preg_match('~\(08\) informacoes complementares([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) $icms['OBSERVACAO'] = trim($i[0]);
        }

        preg_match('~\(18\) autenticacao bancaria([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) {
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    // TODO: verificando PA
    public function icmsPA($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'PA';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) == 'TAXA' || $this->letras($file_content[2]) == 'PROTEGE' || $this->letras($file_content[2]) == 'FECP' || $this->letras($file_content[2]) == 'FEEF' || $this->letras($file_content[2]) == 'UNIVERSIDADE' || $this->letras($file_content[2]) == 'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }
        // preg_match layout novo
        preg_match('~01 - cod. receita: 02 - referencia: 03 - identificacao: 04 - doc. origem([^{]*)~i', $str, $match_layout_novo);

        if (!empty($match_layout_novo)) { // BEGIN layout novo
            $arrTxt = explode(':', trim($match_layout_novo[0]));
            $varArrTxt14 = explode(' ', trim($arrTxt[14]));

            // 01 - cod. receita [0]
            $icms['COD_RECEITA'] = $varArrTxt14[0];
            // 02 - referencia [1]
            $icms['REFERENCIA'] = $varArrTxt14[1];

            if (count($varArrTxt14) > 18) {
                // 03 - identificacao / IE [2]
                if (empty($icms['IE'])) {
                    $icms['IE'] = $this->numero(substr($varArrTxt14[2], 0, 12));
                }
                // 5 - vencimento [3]
                $icms['DATA_VENCTO'] = $varArrTxt14[3];
                // 02 - referencia [1] - GOTO Line 1260 =)
                if (empty($icms['REFERENCIA'])) {
                    $date = substr($icms['DATA_VENCTO'], -7);
                    $date = explode('/', $date);
                    $date = Carbon::createFromDate($date[1], $date[0]-1, 1, config('configICMSVars.wamp.timezone_brt'))->format('m/Y');
                    $icms['REFERENCIA'] = $date;
                }
                $data_vencimento = str_replace('/', '-', $varArrTxt14[3]);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                // 08 - taxa [6]
                if (strlen(trim($varArrTxt14[6])) <= 6) {
                    $icms['TAXA'] = str_replace(',', '.', trim($varArrTxt14[6]));
                } else {
                    $icms['TAXA'] = str_replace(',', '.', str_replace('.', '', trim($varArrTxt14[6])));
                }
                // 09 - principal [8]
                $vlrPrincipal = trim($varArrTxt14[8]);
                if (strlen($vlrPrincipal) <= 6) {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', $vlrPrincipal);
                } else {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $vlrPrincipal));
                }
                // 14 - total [18]
                $vlrTotal = trim(substr($varArrTxt14[18], 0, -4));
                if (strlen($vlrTotal) <= 6) {
                    $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                } else {
                    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                }
                // 12 - multa [14]
                if (strlen(trim($varArrTxt14[14])) <= 6) {
                    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', trim($varArrTxt14[14]));
                } else {
                    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($varArrTxt14[14])));
                }
            } else if (count($varArrTxt14) == 18) {
                // 03 - identificacao / IE [2]
                if (empty($icms['IE'])) {
                    $icms['IE'] = $this->numero(substr($varArrTxt14[2], 0, 12));
                }
                if (strlen($varArrTxt14[3]) > 10) {
                    // 5 - vencimento [2]
                    $icms['DATA_VENCTO'] = substr($varArrTxt14[2], -10);
                    // 08 - taxa [5]
                    if (strlen(trim($varArrTxt14[5])) <= 6) {
                        $icms['TAXA'] = str_replace(',', '.', trim($varArrTxt14[5]));
                    } else {
                        $icms['TAXA'] = str_replace(',', '.', str_replace('.', '', trim($varArrTxt14[6])));
                    }
                    // 09 - principal [7]
                    $vlrPrincipal = trim($varArrTxt14[7]);
                    if (strlen($vlrPrincipal) <= 6) {
                        $icms['VLR_RECEITA'] = str_replace(',', '.', $vlrPrincipal);
                    } else {
                        $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $vlrPrincipal));
                    }
                } else {
                    // 5 - vencimento [3]
                    $icms['DATA_VENCTO'] = $varArrTxt14[3];
                    // 08 - taxa [6]
                    if (strlen(trim($varArrTxt14[6])) <= 6) {
                        $icms['TAXA'] = str_replace(',', '.', trim($varArrTxt14[6]));
                    } else {
                        $icms['TAXA'] = str_replace(',', '.', str_replace('.', '', trim($varArrTxt14[6])));
                    }
                    // 09 - principal [8]
                    $vlrPrincipal = trim(substr($varArrTxt14[8], 0, -2));
                    if (strlen($vlrPrincipal) <= 6) {
                        $icms['VLR_RECEITA'] = str_replace(',', '.', $vlrPrincipal);
                    } else {
                        $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $vlrPrincipal));
                    }
                }
                // 02 - referencia [1] - GOTO Line 1260 =)
                if (empty($icms['REFERENCIA'])) {
                    $date = substr($icms['DATA_VENCTO'], -7);
                    $date = explode('/', $date);
                    $date = Carbon::createFromDate($date[1], $date[0]-1, 1, config('configICMSVars.wamp.timezone_brt'))->format('m/Y');
                    $icms['REFERENCIA'] = $date;
                }
                $data_vencimento = str_replace('/', '-', $icms['DATA_VENCTO']);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                // 14 - total [17]
                $vlrTotal = trim(substr($varArrTxt14[17], 0, -4));
                if (strlen($vlrTotal) <= 6) {
                    $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                } else {
                    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                }
                // 12 - multa [13]
                if (strlen(trim($varArrTxt14[13])) <= 6) {
                    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', trim($varArrTxt14[13]));
                } else {
                    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($varArrTxt14[13])));
                }
            } else if (count($varArrTxt14) < 18) {
                // 03 - identificacao / IE [2]
                if (empty($icms['IE'])) {
                    $icms['IE'] = $this->numero(substr($varArrTxt14[2], 0, 12));
                }
                // 5 - vencimento [2]
                $icms['DATA_VENCTO'] = substr($varArrTxt14[2], -10);
                // 02 - referencia [1] - GOTO Line 1260 =)
                if (empty($icms['REFERENCIA'])) {
                    $date = substr($icms['DATA_VENCTO'], -7);
                    $date = explode('/', $date);
                    $date = Carbon::createFromDate($date[1], $date[0]-1, 1, config('configICMSVars.wamp.timezone_brt'))->format('m/Y');
                    $icms['REFERENCIA'] = $date;
                }
                $data_vencimento = str_replace('/', '-', $icms['DATA_VENCTO']);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                // 08 - taxa [5]
                if (strlen(trim($varArrTxt14[5])) <= 6) {
                    $icms['TAXA'] = str_replace(',', '.', trim($varArrTxt14[5]));
                } else {
                    $icms['TAXA'] = str_replace(',', '.', str_replace('.', '', trim($varArrTxt14[5])));
                }
                // 09 - principal [7]
                $vlrPrincipal = trim(substr($varArrTxt14[7], 0, -2));
                if (strlen($vlrPrincipal) <= 6) {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', $vlrPrincipal);
                } else {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $vlrPrincipal));
                }
                // 14 - total [16]
                $vlrTotal = trim(substr($varArrTxt14[16], 0, -4));
                if (strlen($vlrTotal) <= 6) {
                    $icms['VLR_TOTAL'] = str_replace(',', '.', $vlrTotal);
                } else {
                    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $vlrTotal));
                }
                // 12 - multa [12]
                if (strlen(trim($varArrTxt14[12])) <= 6) {
                    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', trim($varArrTxt14[12]));
                } else {
                    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($varArrTxt14[12])));
                }
            }

            if(preg_match('~\*\*\*autenticacao no verso \*\*\*([^{]*)~i', $str, $arrCodBarra)) {
                $i = explode(' ', trim($arrCodBarra[1]));
                $codBarra = '';

                foreach ($i as $k => $x) {
                    if (strlen($x) > 6) {
                        $codBarra .= $this->numero($x);
                    }
                    if ($k == 4) {
                        break;
                    }
                }

                $icms['CODBARRAS'] = trim($codBarra);
            }
        } else { // END layout novo / BEGIN layout velho
            // preg_match layout antigo
            preg_match('~1 - codigo da receita 2 - referencia 34 - documento origem([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  0) $icms['COD_RECEITA'] = $i[0];
                if (empty($icms['IE'])) {
                    if(isset($i) && count($i) >  4) $icms['IE'] = $this->numero($i[4]);
                }
                if(isset($i) && count($i) >  3) $icms['REFERENCIA'] = $i[1].$i[2].$i[3];

                if (empty($icms['REFERENCIA'])) {
                    preg_match('~5 - vencimento([^{]*)~i', $str, $match);
                    if (!empty($match)) {
                        $i = explode(' ', trim($match[1]));
                        if(isset($i) && count($i) >  0)
                        {
                            $valorData = trim(substr($i[0], 0, 10));
                            $data_vencimento = str_replace('/', '-', $valorData);
                            $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                            $referencia = date('m/Y', strtotime($data_vencimento));
                            $k = explode('/', $referencia);
                            $k[0] = $k[0]-1;
                            if ($k[0] == 0) {
                                $k[1] = $k[1] - 1;
                            }
                            if (strlen($k[0]) == 1) {
                                $k[0] = '0'.$k[0];
                            }
                            $icms['REFERENCIA'] = $k[0].'/'.$k[1];
                        }
                    }
                }

                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  0) $icms['CODBARRAS'] = trim($i[0]);
            }

            preg_match('~5 - vencimento([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  0)
                {
                    $valorData = trim(substr($i[0], 0, 10));
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                }
            }

            preg_match('~8 - taxa r\$([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  0) $a = explode("\n", $i[0]);
                if(isset($a) && count($a) >  0) $icms['TAXA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }

            preg_match('~14 - total r\$([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  0) $a = explode("\n", $i[0]);
                if(isset($a) && count($a) >  0) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }

            preg_match('~9 - principal([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  0) $a = explode("\n", $i[0]);
                if(isset($a) && count($a) >  0) $icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', trim($a[0]))));
            }

            preg_match('~12 - multa([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  0) $a = explode("\n", $i[0]);
                if(isset($a) && count($a) >  0) $icms['MULTA_MORA_INFRA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', trim($a[0]))));
            }

            preg_match('~\*\*\*autenticacao no verso \*\*\*([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                $codbarras = '';

                if(isset($i) && count($i) >  0) {
                    foreach ($i as $k => $x) {
                        if (strlen($x) > 6) {
                            $codbarras .= $this->numero($x);
                        }
                        if ($k == 4) {
                            break;
                        }
                    }
                    $icms['CODBARRAS'] = trim($codbarras);
                }
            }

            if (isset($icms['VLR_RECEITA']) && empty($this->numero($icms['VLR_RECEITA']))) {
                preg_match('~01 - cod. receita: 02 - referencia: 03 - identificacao: 04 - doc. origem: 05 - vencimento: 06 - documento: 07 - cod. munic.: 08 - taxa: 09 - principal: 10 - correcao: 11 - acrescimo: 12 - multa: 13 - honorarios: 14 - total:([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode(' ', trim($match[1]));

                    if(isset($i) && count($i) >  0) {
                        if (!empty($icms['REFERENCIA'])) {
                            $icms['REFERENCIA'] = $i[1];
                        }

                        $a = explode("\n", $i[2]);
                        $valorData = trim($a[1]);
                        $data_vencimento = str_replace('/', '-', $valorData);
                        $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                        $icms['VLR_RECEITA'] = trim(str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[7]))));
                        $icms['MULTA_MORA_INFRA'] =  str_replace(',', '.', str_replace('.', '', $i[14]));
                        $icms['VLR_TOTAL'] = trim(str_replace('nome:', '', str_replace(',', '.', str_replace('.', '', $i[16]))));
                        $icms['TAXA'] = str_replace(',', '.', str_replace('.', '', $i[5]));
                        $p = explode(' ', $i[4]);

                        if(strlen($i[4]) < 5){
                            $p = explode(' ', $i[3]);
                        }

                        $icms['IE'] =  $p[0];
                        $icms['COD_RECEITA'] =  $p[1];
                    }
                }

                preg_match('~\*\*\*autenticacao no verso \*\*\*([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode(' ', trim($match[1]));
                    $codbarras = '';

                    if(isset($i) && count($i) >  0) {
                        foreach ($i as $k => $x) {
                            if (strlen($x) > 6) {
                                $codbarras .= $this->numero($x);
                            }
                            if ($k == 4) {
                                break;
                            }
                        }
                        $icms['CODBARRAS'] = trim($codbarras);
                    }
                }
            }

            if (isset($icms['CODBARRAS']) && strlen($icms['CODBARRAS']) <= 6) {
                preg_match('~\*\*\* autenticacao no verso \*\*\*([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode(' ', trim($match[1]));
                    if(isset($i) && count($i) >  0) {
                        $codbarras = '';
                        foreach ($i as $k => $x) {
                            if (strlen($x) > 6) {
                                $codbarras .= $this->numero($x);
                            }
                            if ($k == 4) {
                                break;
                            }
                        }

                        $icms['CODBARRAS'] = trim($codbarras);
                    }
                }
            }

            if (isset($icms['MULTA_MORA_INFRA']) && strlen($icms['MULTA_MORA_INFRA'])  <= 2) {
                preg_match('~receber ate :([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode(' ', trim($match[1]));
                    if(isset($i) && count($i) >  0) {
                        $valorData = trim(substr($i[0], 0, 10));
                        $data_vencimento = str_replace('/', '-', $valorData);
                        $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                    }
                }

                preg_match('~01 - cod. receita: 02 - referencia: 03 - identificacao: 04 - doc. origem: 05 - vencimento: 06 - documento: 07 - cod. munic.: 08 - taxa: 09 - principal: 10 - correcao: 11 - acrescimo: 12 - multa: 13 - honorarios: 14 - total:([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode(' ', trim($match[1]));
                    if(isset($i) && count($i) >  3) {
                        $a = explode(' ', $i[2]);
                        $b = explode(' ', $i[3]);

                        if(isset($a) && count($a) >  4) {
                            $icms['VLR_RECEITA'] = trim(str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[4]))));
                            $icms['TAXA'] =  str_replace(',', '.', str_replace('.', '', $a[2]));
                        }

                        if(isset($b) && count($b) >  9) {
                            $icms['VLR_TOTAL'] = trim(str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $b[9]))));
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $b[5]));
                        }
                    }
                }
            }

            preg_match('~6 - documento([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  3) {
                    $a = explode("\n", trim($i[0]));
                    if(isset($a) && count($a) >  3) $icms['IE'] = $a[0];
                }
            }
	
		        if(!isset($icms['REFERENCIA']) || isset($icms['REFERENCIA']) && strlen($icms['REFERENCIA']) != 7){
			        preg_match('~1 - codigo da receita([^{]*)~i', $str, $match);
			        if (!empty($match)) {
				        $i = explode(' ', trim($match[1]));
				        $icms['COD_RECEITA'] = $i[0];
				        $icms['REFERENCIA'] = $i[4].$i[5].$i[6];
				
			        }
			        preg_match('~autenticacao no verso([^{]*)~i', $str, $match);
			        if (!empty($match)) {
				        $i = explode(' ', trim($match[1]));
				        // echo '<pre>', print_r($i); exit;
				        $icms['CODBARRAS'] = $this->numero(trim($i[1]).trim($i[5]).trim($i[9]).trim($i[13]));
			        }
	        }
        } // END layout novo/velho

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsPB($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'PB';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~03 - receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['COD_RECEITA'] = $this->numero($i[2]);
        }

        preg_match('~05 - inscricao estadual/cgc/cpf([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) >  0) {
                $a = explode("\n", trim($i[0]));
                if(isset($a) && count($a) >  0) $icms['IE'] = $this->numero($a[0]);
            }
        }

        preg_match('~06 - referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~07 - data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));

            if(isset($i) && count($i) >  0) {
                $valorData = trim($i[0]);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }

        preg_match('~29 - matricula([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));

            if(isset($i) && count($i) >  4) {
                $a = explode(' ', $i[0]);
                $b = explode(' ', $i[1]);

                if(isset($a) && count($a) >  0) $icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[0])));
                if(isset($a) && count($a) >  1) $icms['JUROS_MORA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[1])));
                if(isset($b) && count($b) >  0) $icms['MULTA_MORA_INFRA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $b[0])));
                $icms['VLR_TOTAL'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[2])));
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[4]));
                $icms['CODBARRAS'] = trim($codbarras);
            }



        }
        if (isset($icms['MULTA_MORA_INFRA']) && isset($icms['VLR_RECEITA']) && (($icms['MULTA_MORA_INFRA'] > $icms['VLR_RECEITA']))) {
            preg_match('~29 - matricula([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));

                if(isset($i) && count($i) >  3) {
                    $a = explode(' ', $i[0]);
                    if(isset($a) && count($a) >  0) $icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[0])));
                    if(isset($a) && count($a) >  1) $icms['JUROS_MORA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[1])));
                    if(isset($a) && count($a) >  2) $icms['MULTA_MORA_INFRA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[2])));
                    $icms['VLR_TOTAL'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[1])));
                    $codbarras = str_replace('-', '', str_replace(' ', '', $i[3]));
                    $icms['CODBARRAS'] = trim($codbarras);
                }
            }
        }

        if (empty($icms['CODBARRAS'])) {
            preg_match('~29 - matricula([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if(isset($i) && count($i) >  1) $icms['VLR_TOTAL'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[1])));
                if(isset($i) && count($i) >  3) {
                    $codbarras = str_replace('-', '', str_replace(' ', '', $i[3]));
                    $icms['CODBARRAS'] = trim($codbarras);
                }
            }
        }
	
	
	      if (isset($icms['VLR_TOTAL']) && isset($icms['MULTA_MORA_INFRA']) &&  ($icms['VLR_TOTAL'] == $icms['MULTA_MORA_INFRA'])) {
            $icms['MULTA_MORA_INFRA'] = '0.00';
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsES($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'ES';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~servico icms - comercio([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  4) {
                $a = explode(' ', $i[4]);
                if(isset($a) && count($a) >  1) $icms['COD_RECEITA'] = $this->numero($a[1]);
            }
        }

        if(empty($icms['COD_RECEITA'])){
            preg_match('~
receita ([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) >  0) {
                    $a = explode('
', $i[0]);
                    if(isset($a) && count($a) >  0) $icms['COD_RECEITA'] = $this->numero($a[0]);
                }
            }
        }

        preg_match('~data de referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) >  0) {
                $a = explode('
', $i[0]);
                if(isset($a) && count($a) >  0) $icms['REFERENCIA'] = $a[0];
            }


        }

        preg_match('~vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) >  0) {
                $a = explode('
', $i[0]);
                if(isset($a) && count($a) >  0) {
                    $valorData = $a[0];
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                }
            }
        }

        preg_match('~valor da receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) >  1) {
                $a = explode('
', $i[1]);
                if(isset($a) && count($a) >  0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }
        }

        preg_match('~credito total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  0) $a = explode(' ', $i[0]); {
                if(isset($a) && count($a) >  2) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[2])));
            }
        }

        preg_match('~documento unico de arrecadacao versao internet([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) >  2) {
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[2]));
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsGO($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'GO';
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~inscricao estadual:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~documento de origem referencia 300-mensal -([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = trim($i[0]);
        }

        if(!isset($icms['REFERENCIA']) || (isset($icms['REFERENCIA']) && $icms['REFERENCIA'])) {
            preg_match('~referencia 300-mensal -([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $icms['REFERENCIA'] = trim(explode("\n", $i[0])[0]);
                }
            }
        }

        preg_match('~data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $valorData = substr(trim($i[0]), 0,10);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }

        preg_match('~validade do calculo: total a recolher:
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 1) {
                $i[1] = explode("\n",$i[1])[0];

                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[1])));
                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($i[1])));
            }
        }

        preg_match('~foo([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }

        preg_match('~
receita
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            $k = explode(' ', $a[6]);
            $icms['COD_RECEITA'] = trim($k[0]);
        }


        if (empty($icms['COD_RECEITA'])) {
            preg_match('~
receita([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                $icms['COD_RECEITA'] = trim($i[0]);
            }
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsSE($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'SE';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAC';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }


        preg_match('~inscricao estadual / cpf / cnpj

numero do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $icms['IE'] = trim($this->numero($i[0]));
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST'){

            preg_match('~
valor total

([^{]*)~i', $str, $match);

            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 2) {
                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
                    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
                    $icms['CODBARRAS'] = trim(str_replace('observacao', '', trim(str_replace(' ', '', $i[0]))));
                }
            }

            preg_match('~
validade([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) > 2) {
                    $valorData = substr($i[0], 0,10);
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));

                    $y = explode("\n", $i[25]);

                    if( floatval($y[0]) != 0 ) {
	                    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($y[0])));
	                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($y[0])));
                    }

                    $referencia = date('m/Y', strtotime($data_vencimento));
                    $k = explode('/', $referencia);
                    if(isset($k) && count($k) > 0) {
                        $k[0] = $k[0]-1;
                        if ($k[0] == 0) {
                            $k[1] = $k[1] - 1;
                        }
                        if (strlen($k[0]) == 1) {
                            $k[0] = '0'.$k[0];
                        }
                        $icms['REFERENCIA'] = $k[0].'/'.$k[1];
                    }
                }
            }
	
	        preg_match('~via do banco([^{]*)~i', $str, $match);
	        if (!empty($match)) {
		        $i = explode(' ', trim($match[1]));
		        $icms['CODBARRAS'] = str_replace(' ','', $this->numero($i[0])).str_replace(' ','', $this->numero($i[2])).str_replace(' ','', $this->numero($i[4])).str_replace(' ','', $this->numero($i[6]));
	        }

        } else {

            preg_match('~validade

valor total([^{]*)~i', $str, $match);

            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $valorData = $i[0];
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                    $referencia = date('m/Y', strtotime($data_vencimento));
                    $k = explode('/', $referencia);
                    if(isset($k) && count($k) > 1) {
                        $k[0] = $k[0]-1;
                        if ($k[0] == 0) {
                            $k[1] = $k[1] - 1;
                        }
                        if (strlen($k[0]) == 1) {
                            $k[0] = '0'.$k[0];
                        }
                        $icms['REFERENCIA'] = $k[0].'/'.$k[1];
                    }
                }
            }

            preg_match('~validade

valor total([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 2) {
                    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
                    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[2])));
                    $codbarras = str_replace('-', '', str_replace(' ', '', $i[4]));
                    $icms['CODBARRAS'] = trim($codbarras);
                }
            }
	
	        preg_match('~ref. ([^{]*)~i', $str, $match);
	        if (!empty($match)) {
		        $i = explode(PHP_EOL, trim($match[1]));
		        if(isset($i[0])){
		        	$dataRef = substr($i[0], 0, 7);
			        $refExplode = explode('/', $dataRef);
			        if(strlen($refExplode[0]) == 1){
				        $dataRef = '0'.trim($refExplode[0]).'/'.trim($refExplode[1]);
			        }
			        $icms['REFERENCIA'] = $dataRef;
		        }
	        }
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms; //echo '<pre>', print_r($icmsarray[0]); exit;
        return $icmsarray;
    }

    public function icmsBA($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'BA';
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~3-inscricao estadual/cpf ou cnpj([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~4-referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~1-codigo da receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['COD_RECEITA'] = trim($i[0]);
        }

        preg_match('~2-data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $valorData = $i[0];
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }

        preg_match('~7-valor principal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 1) {
                $a = explode('
', $i[1]);
                if(isset($a) && count($a) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }

        }
        preg_match('~9-acres. moratorio e/ou juros([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 1) {
                $a = explode('
', $i[1]);
                if(isset($a) && count($a) > 0) $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }
        }
        preg_match('~10-multa por infracao([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 1) {
                $a = explode('
', $i[1]);
                if(isset($a) && count($a) > 0) $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }

        }
        preg_match('~11-total a recolher([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 1) {
                $a = explode('
', $i[1]);
                if(isset($a) && count($a) > 0)  $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }
        }

        preg_match('~---------------------------------------------------------------------------------------------------------------------------------------------------([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
                if(isset($codbarras)) $icms['CODBARRAS'] = trim($codbarras);
            }
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsRN($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'RN';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAC';
	
		        $ano = substr($file_content[3], 2, 4);
		        $mes = substr($file_content[3], 0, 2);
		        $icms['REFERENCIA'] = $mes.'/'.$ano;
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~receita ([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms['COD_RECEITA'] = trim($this->numero($i[0]));
        }

        preg_match('~vencimento
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $valorData = $i[0];
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }
	
	    preg_match('~debitos efd - ([^{]*)~i', $str, $match);
	    if (!empty($match)) {
	    	if(isset($match[1])){
	    		$mes = substr($match[1], 4, 2);
	    		$ano = substr($match[1], 0, 4);
			    $icms['REFERENCIA'] = $mes.'/'.$ano;
		    }
	    }

        preg_match('~
valor do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
            }
        }
	
	
	      $icms['CODBARRAS'] = '';
        preg_match('~
valor do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = '';

            foreach ($i as $k => $x) {
                if (strlen($x) == 13) {
                    $codbarras .= $this->numero($x);
                }
                if (strlen($codbarras) != 36) {
                    if ($k == 14) {
                        break;
                    }
                }
                if ($k == 16) {
                    break;
                }
            }

            $icms['CODBARRAS'] = substr(trim($codbarras), 0, 48);
        }

        if($icms['CODBARRAS'] != '' && strlen($icms['CODBARRAS']) < 48){
			        preg_match('~'.PHP_EOL.'valor do documento([^{]*)~i', $str, $match);
			        if (!empty($match)) {
					        $i = explode(PHP_EOL, trim($match[1]));

									$cod1 = isset($i[13])? $i[13] : '';
									$cod2 = isset($i[15])? $i[15] : '';
									$cod3 = isset($i[17])? $i[17] : '';
									$cod4 = isset($i[19])? $i[19] : '';
				
				          if(! is_numeric(str_replace(['-',' ','.'], '', $cod1.$cod2.$cod3.$cod4))){
						        $cod1 = isset($i[14])? $i[14] : '';
						        $cod2 = isset($i[16])? $i[16] : '';
						        $cod3 = isset($i[18])? $i[18] : '';
						        $cod4 = isset($i[20])? $i[20] : '';
					        }
									
					        $codigo = str_replace(['-',' ','.'], '', $cod1.$cod2.$cod3.$cod4);
					        $icms['CODBARRAS'] = substr(trim($codigo), 0, 48);
		          }
        }

        if($icms['CODBARRAS'] == ''){
	        if(isset($match[1])){ $i = explode(PHP_EOL, trim($match[1]));
	
	        $cod1 = isset($i[15])? $i[15] : '';
	        $cod2 = isset($i[17])? $i[17] : '';
	        $cod3 = isset($i[19])? $i[19] : '';
	        $cod4 = isset($i[21])? $i[21] : '';
	        
	        $codigo = substr(str_replace(['-',' ','.'], '', $cod1.$cod2.$cod3.$cod4), 0, 48);
	        $icms['CODBARRAS'] = $codigo; }
        }

        if (isset($icms['COD_RECEITA']) && trim($icms['COD_RECEITA']) == 1245) {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        preg_match('~03 - receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 2) $icms['COD_RECEITA'] = $this->numero($i[2]);
        }

        preg_match('~06 - referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~07 - data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));

            if(isset($i) && count($i) > 0) {
                $valorData = trim($i[0]);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }

        preg_match('~05 - inscricao estadual/cgc/cpf([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['IE'] = $this->numero($i[0]);
        }

        preg_match('~29 - matricula([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));

            if(isset($i) && count($i) > 4) {
                $a = explode(' ', $i[0]);
                $b = explode(' ', $i[1]);

                if( (isset($a) && count($a) > 1) && (isset($b) && count($b) > 2) ) {
                    $icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[0])));
                    $icms['JUROS_MORA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $a[1])));
                    $icms['MULTA_MORA_INFRA'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $b[0])));
                    $icms['VLR_TOTAL'] = str_replace('r$', '', str_replace(',', '.', str_replace('.', '', $i[2])));
                    $codbarras = str_replace('-', '', str_replace(' ', '', $i[4]));
                    $icms['CODBARRAS'] = trim($codbarras);
                }
            }

        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;

        return $icmsarray;
    }

    public function icmsMG($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms[0]['CNPJ'] = $estabelecimento->cnpj;
        $icms[0]['IE'] = $estabelecimento->insc_estadual;
        $icms[0]['UF'] = 'MG';

        $icms[1]['CNPJ'] = $estabelecimento->cnpj;
        $icms[1]['IE'] = $estabelecimento->insc_estadual;
        $icms[1]['UF'] = 'MG';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms[0]['TRIBUTO_ID'] = 8;

        $icms[1]['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }
        
        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms[0]['IMPOSTO'] = 'SEFAZ';
            $icms[1]['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO') {
            $icms[0]['IMPOSTO'] = 'SEFAB';
            $icms[1]['IMPOSTO'] = 'SEFAB';
		}
		
        if ($this->letras($file_content[2]) == 'ICMSST' || $this->letras($file_content[2]) == 'ICMS') {
            $icms[0]['IMPOSTO'] = 'SEFAZ';
            $icms[1]['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms[0]['IMPOSTO'] = 'SEFAT';
            $icms[1]['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~validade([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $valorData = substr($i[0], 0,12);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms[0]['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                $icms[1]['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                $referencia = date('m/Y', strtotime($data_vencimento));
                $k = explode('/', $referencia);
                $k[0] = $k[0]-1;
                if ($k[0] == 0) {
                    $k[1] = $k[1] - 1;
                }
                if (strlen($k[0]) == 1) {
                    $k[0] = '0'.$k[0];
                }
                $icms[0]['REFERENCIA'] = $k[0].'/'.$k[1];
                $icms[1]['REFERENCIA'] = $k[0].'/'.$k[1];
            }
        }

        preg_match('~receita

periodo ref.([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $a = explode(' ', $i[0]);
                if(isset($a) && count($a) > 0) {
                    $icms[0]['COD_RECEITA'] = $this->numero($a[1]);

                    if (!empty($i[2])) {
                        $k = explode(' ', $i[2]);
                        if(isset($k) && count($k) > 1) $icms[1]['COD_RECEITA'] = $this->numero($k[1]);
                    }
                }
            }
        }

        preg_match('~valor([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if (isset($i) && count($i) > 0) {
                $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[0], 0, -5))));

                if (strlen(substr($i[0], 0, -5)) > 8) {
                    $a = explode('
', substr($i[0], 0, -5));
                    if (isset($a) && count($a) > 0) $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                    if (isset($a) && count($a) > 1) $icms[1]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
                }
            }
        }

        preg_match('~numero identificacao([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if (isset($i[0])) {
                $a = explode(" ", trim($i[0]));
                if (isset($a[1])) {
                    $icms[0]['IE'] = $this->numero($a[1]);
                }
            }
        }

        preg_match('~multa([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 1) {
                $icms[0]['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[0], 0,-5))));
	            
                if (!strstr($i[0], "j"))  {
                    $icms[0]['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
                    $icms[1]['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[1], 0,-5))));
                }
            }
        }
        
        preg_match('~juros([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 1)  {
                $icms[0]['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[0], 0,-5))));

                if (!strstr($i[0], "t"))  {
                    $icms[0]['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
                    $icms[1]['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[1], 0,-5))));
                }
            }
        }
        
        preg_match('~total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim(substr($i[0], 0,-5))));

            if (strlen(substr($i[0], 0,-5)) > 8) {
                $a = explode('
', substr($i[0], 0,-5));
                if(isset($a) && count($a) > 1) {
                    $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                    $icms[1]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[1])));
                }
            }
        }

        preg_match('~total

r\$
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
                $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
            }
        }

        preg_match('~linha digitavel:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $icms[0]['CODBARRAS'] = trim($this->numero($i[0]));
                $icms[1]['CODBARRAS'] = trim($this->numero($i[0]));
            }
        }

        if (empty($icms[0]['IE'])) {
            preg_match('~numero identificacao([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
	', trim($match[1]));
                if(isset($i) && count($i) > 1) {
                    $a = explode(' ', $i[0]);
                    $icms[0]['IE'] = trim($this->numero($a[1]));
                    $icms[1]['IE'] = trim($this->numero($a[1]));
                }
            }
        }

        if (empty($icms[0]['COD_RECEITA'])) {
            preg_match('~receita

periodo ref.([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(" ", trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $icms[0]['COD_RECEITA'] = trim($i[0]);
                }
            }
        }

        if (isset($icms[0]['VLR_RECEITA'])) {
            $check = $this->letras($icms[0]['VLR_RECEITA']);
            if (!empty($check)) {
                preg_match('~total

r\$([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode("\n", trim($match[1]));
                    if (isset($i) && count($i) > 4) {
                        $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[4])));
                        $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[4])));
                    }
                }
            }
        }

        if (isset($icms[0]['IE'])) {
            if (empty($icms[0]['IE'])) {
                preg_match('~numero([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode("\n", trim($match[1]));
                    if(isset($i) && count($i) > 0) {
                        $a = explode(' ', $i[0]);
                        if(isset($i) && count($i) > 1) $icms[0]['IE'] = trim($this->numero($a[1]));
                    }
                }
            }
        }

        if (isset($icms[0]['VLR_RECEITA'])) {
            if (strlen($icms[0]['VLR_RECEITA'] > 11)) {
                preg_match('~valor([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode(" ", trim($match[1]));
                    if (isset($i) && count($i) > 0) {
                        $a = explode("\n", trim($i[0]));
                        if (isset($a) && count($a) > 0) {
                            $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($a[0])));
                            $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($a[0])));
                        }
                    }
                }
            }
        }

        preg_match('~mes ano de referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if (isset($i) && count($i) > 0) $icms[0]['REFERENCIA'] = str_replace(' ', '',trim($i[0]));
        }

        preg_match('~numero do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if (isset($i) && count($i) > 21)
                $icms[0]['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($i[21])));
        }

        if (isset($icms[0]['REFERENCIA'])) {
            if (strlen($icms[0]['REFERENCIA']) != 7) {
                preg_match('~validade([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode(' ', trim($match[1]));

                    if (isset($i) && count($i) > 0) {
                        $valorData = substr($i[0], 0,12);
                        $data_vencimento = str_replace('/', '-', $valorData);
                        $icms[0]['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                        $icms[1]['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                        $referencia = date('m/Y', strtotime($data_vencimento));
                        $k = explode('/', $referencia);
                        $k[0] = $k[0]-1;
                        if ($k[0] == 0) {
                            $k[1] = $k[1] - 1;
                        }
                        if (strlen($k[0]) == 1) {
                            $k[0] = '0'.$k[0];
                        }
                        $icms[0]['REFERENCIA'] = $k[0].'/'.$k[1];
                    }
                }
            }
        }

        $vlr_total = 'a';

        preg_match('~numero do documento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));
            if (isset($i) && count($i) > 20) $vlr_total = str_replace(',', '.', str_replace('.', '',trim($i[20])));
        }

        if (empty($icms[0]['VLR_TOTAL']) || is_numeric($vlr_total)) {
            $icms[0]['VLR_TOTAL'] = $vlr_total;
        }

        if (empty($icms[0]['REFERENCIA'])) {
            $ano = substr($file_content[3], -4);
            $mes = substr($file_content[3], 0,2);
            $icms[0]['REFERENCIA'] = $mes.'/'.$ano;
        }

        if (substr($icms[0]['REFERENCIA'], 0,2) == '00') {
            preg_match('~periodo ref.([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));
                if (!empty($i[0])) {
                    $a = explode(' ', $i[0]);
                    foreach ($a as $x => $data) {
                    }
                    $icms[0]['REFERENCIA'] = substr($data, -8);
                }
            }
        }
	
	    preg_match('~01 a ([^{]*)~i', $str, $match);
	    if (!empty($match)) {
		    $i = explode(PHP_EOL, trim($match[0]));
		    if (!empty($i[0])) {
			    $icms[0]['REFERENCIA'] = substr($i[0], -7);
		    }
	    }
	
		    preg_match('~multa([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[1]));
			    foreach($i as $key => $value){
				    if(substr_count($value, ',') == 3) {
					    $explode = explode(' ', $value);
					    if(count($explode) == 3){
						    $icms[0]['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($explode[0])));
						    $icms[0]['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($explode[1])));
						    $icms[0]['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($explode[2])));
					    }
				    }
			    }
		    }
	    
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms[0]; // echo '<pre>', print_r($icmsarray[0]); exit;
        return $icmsarray;
    }

    private function letras($string)
    {
        $nova = str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'), '', $string);
        return $nova;
    }

    public function icmsDF($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }
        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['UF'] = 'DF';
        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);
        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }
        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }
        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }
        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }
        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }
        preg_match('~12.res. sef([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));

            if(isset($a) && count($a) > 30) {
                $i = explode(' ', $a[0]);

				//possíveis localizações das datas (referência e vencimento)
                $k0 = explode(' ', $a[1]);
                $k1 = explode(' ', $a[30]);
				$k2 = explode(' ', $a[15]);


                if(  count($k0) == 2 ) {
                    if( (DateTime::createFromFormat('m/Y',   $k0[0]) &&
                        DateTime::createFromFormat('d/m/Y', $k0[1]) )) {
                        $k = explode(' ', $a[1]);
                    }
                }
                if(  count($k1) == 2 ) {
                    if( (DateTime::createFromFormat('m/Y',   $k1[0]) &&
                        DateTime::createFromFormat('d/m/Y', $k1[1]) )) {
                        $k = explode(' ', $a[30]);
                    }
                }

                if(  count($k2) == 2 ) {
                    if( (DateTime::createFromFormat('m/Y',   $k2[0]) &&
                        DateTime::createFromFormat('d/m/Y', $k2[1]) )) {
                        $k = explode(' ', $a[15]);
                    }
                }

                if (isset($i[1])) {
                    $icms['COD_RECEITA'] = $i[1];
                }

                if(isset($icms['IE']) && empty($icms['IE'])){
                    $icms['IE'] = $i[0];
                }

                if(isset($k) && count($k) > 0) {
					$icms['REFERENCIA'] = $k[0];
				} else {
					if(strpos($a[4],"icms ref.")!==false) {
						if(count($a) > 4) $referencia = explode(" ", $a[4]);
						if(count($referencia) > 2) $icms['REFERENCIA'] = $referencia[2];
					}
				}
				
                if (isset($k[1])) {
                    $valorData = trim($k[1]);
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                } else {
					if(strpos($a[8],"receber ate")!==false) {
						if(count($a) > 8) $referencia = explode(" ", $a[8]);
						if(count($referencia) > 6) $icms['DATA_VENCTO'] = $referencia[6];
					}
				}
            }
        }

        preg_match('~13.principal - r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));


            if(isset($a) && count($a) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$a[0]));
            if(isset($a) && count($a) > 4) $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',$a[4]));

            $codbarras = '';
            if(isset($a) && count($a) > 6) $cod_barras = explode(' ', $a[6]);
            foreach($cod_barras as $single){
                if($this->numero($single) > 8){
                    $codbarras .= $this->numero($single);
                }
            }
            $icms['CODBARRAS'] = $codbarras;
        }

        preg_match('~15.juros - r\$ 16.outros - r\$ 17.valor total - r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $a = explode('
', trim($match[1]));
            if(isset($a) && count($a) > 0)
            {
                $p = explode(' ', $a[0]);

                if(isset($p[1])) $icms['TAXA'] = str_replace(',', '.', str_replace('.', '',$p[1]));
                if(isset($p[2])) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$p[2]));
            }
        }
        if (isset($icms['CODBARRAS']) && count($icms['CODBARRAS']) <= 8) {
            if (empty($icms['IE']) || strlen($this->letras($icms['IE'])) > 4) {
                preg_match('~df ([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $a = explode(' ', trim($match[1]));
                    if(isset($a) && count($a) > 0) $icms['IE'] = trim(substr($a[0], 0,8));
                }
            }

            preg_match('~17.valor total - r\$
([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));

                if(isset($i) && count($i) > 57) {
                    $icms['CODBARRAS'] = trim($this->numero($i[0]));
	
	                if($i[2] != '' && $i[54] != '' && $i[55] != '' && $i[57] != '') {
		                $a = explode(' ', $i[2]);
		
		                $k0 = explode(' ', $i[54]);
		                $k1 = explode(' ', $i[55]);
		                $k2 = explode(' ', $i[57]);
		
		                if (count($k0) == 2) {
			                if ((DateTime::createFromFormat('m/Y', $k0[0]) &&
				                DateTime::createFromFormat('d/m/Y', $k0[1]))) {
				                $k = explode(' ', $i[54]);
			                }
		                }
		                if (count($k1) == 2) {
			                if ((DateTime::createFromFormat('m/Y', $k1[0]) &&
				                DateTime::createFromFormat('d/m/Y', $k1[1]))) {
				                $k = explode(' ', $i[55]);
			                }
		                }
		                if (count($k2) == 2) {
			                if ((DateTime::createFromFormat('m/Y', $k2[0]) &&
				                DateTime::createFromFormat('d/m/Y', $k2[1]))) {
				                $k = explode(' ', $i[57]);
			                }
		                }
		
		                $custos = explode(' ', $i[5]);
		                $icms['COD_RECEITA'] = $a[1];
		
		                if (isset($k) && count($k) > 1) {
			                $icms['REFERENCIA'] = $k[0];
			                if (isset($k[1])) {
				                $valorData = trim($k[1]);
				                $data_vencimento = str_replace('/', '-', $valorData);
				                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
			                }
		                }
		
		                if (count($custos) == 2) {
			                $custos_pp = explode(' ', $i[6]);
			                if (isset($custos[1])) {
				                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $custos[1]));
			                }
			                if (isset($custos_pp[1])) {
				                $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $custos_pp[1]));
			                }
			                if (isset($custos_pp[2])) {
				                $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $custos_pp[2]));
			                }
			
			                if (isset($custos_pp[3])) {
				                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $custos_pp[3]));
			                }
		                } else {
			                if (isset($custos[1])) {
				                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', $custos[1]));
			                }
			                if (isset($custos[2])) {
				                $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', $custos[2]));
			                }
			                if (isset($custos[3])) {
				                $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $custos[3]));
			                }
			                if (isset($custos[5])) {
				                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', $custos[5]));
			                }
		                }
	                }
                }
            }
        }

        if (isset($icms['JUROS_MORA']) && strlen($this->letras($icms['JUROS_MORA'])) > 5) {
            preg_match('~16.outros - r\$ 17.valor total - r\$([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 7)
                {
                    $custos = explode(' ', $i[7]);
                    if (isset($custos[0])) {
                        $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',$custos[0]));
                    }
                    if (isset($custos[1])) {
                        $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',$custos[1]));
                    }
                    if (isset($custos[3])) {
                        $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$custos[3]));
                    }
                }
            } else {
                preg_match('~17.valor total - r\$([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $i = explode('
', trim($match[1]));
                    if(isset($i) && count($i) > 7) {
                        $custos = explode(' ', $i[7]);
                        if (isset($custos[0])) {
                            $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',$custos[0]));
                        }
                        if (isset($custos[1])) {
                            $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',$custos[1]));
                        }
                        if (isset($custos[3])) {
                            $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$custos[3]));
                        }
                    }
                }
            }
        }
        preg_match('~valor original: r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if (isset($i[0])) {
                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$i[0]));
            }
        }
        if (isset($icms['JUROS_MORA']) && $icms['JUROS_MORA'] != '') {
            preg_match('~12.res. sef ([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 0) $a = explode(' ', trim($i[0]));
                if (isset($a[1])) {
                    $icms['COD_RECEITA'] = $a[1];
                }
            }
        }
        if (isset($icms['REFERENCIA']) && empty($this->numero($icms['REFERENCIA']))) {
            preg_match('~12.res. sef ([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 1) $a = explode(' ', trim($i[1]));
                if (isset($a[0])) {
                    $icms['REFERENCIA'] = $a[0];
                }
            }
        }
        if (isset($icms['CODBARRAS']) && strlen($icms['CODBARRAS']) < 20) {
            preg_match('~aviso aos bancos : receber ate([^{]*)~i', $str, $match);
            if(!empty($match)){
                $i = explode(' ', $match[1]);
                if (is_array($i)) {
                    $codbarras = '';
                    foreach ($i as $k => $v) {
                        if (strlen($this->numero($v)) > 8) {
                            $codbarras .= trim($v);
                        }
                        if ($k == 5) {
                            break;
                        }
                    }
                    $icms['CODBARRAS'] = trim(substr($codbarras, 0, -12));
                }
            }
        }
        if (isset($icms['COD_RECEITA']) && empty($this->numero($icms['COD_RECEITA']))) {
            preg_match('~01.cf/df 02.cod receita 03.cota ou refer. 04.vencimento 05.exercicio([^{]*)~i', $str, $match);

            if(!empty($match)){
                $i = explode('
', $match[1]);
                if(isset($i) && count($i) > 3) {
                    $a = explode(' ', $i[2]);
                    if($a[1] !== '') $icms['COD_RECEITA'] = $a[1];
                    $c = explode(' ', $i[3]);
                    if($c[0] !== '') $icms['REFERENCIA'] = $c[0];
                }
            }
        }
	
		    preg_match('~02.cod receita([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    if(empty($icms['COD_RECEITA'])){
				    $i = explode(PHP_EOL, trim($match[0]));
				    // echo '<pre>', print_r($i); exit;
				    if($i[2] != ''){
					    $expl = explode(' ', $i[2]);
					    $cod_receita = isset($expl[1])? $expl[1] : '0';
					    $icms['COD_RECEITA'] = $this->numero($cod_receita);
				    }elseif($i[3] != ''){
					    $expl = explode(' ', $i[3]);
					    $cod_receita = isset($expl[1])? $expl[1] : '0';
					    $icms['COD_RECEITA'] = $this->numero($cod_receita);
				    }
				    if(strlen($icms['COD_RECEITA']) != 4){
					    $expl = explode(' ', $i[13]);
					    $cod_receita = isset($expl[1])? $expl[1] : '0';
					    $icms['COD_RECEITA'] = $this->numero($cod_receita);
				    }
				    if(isset($icms['COD_RECEITA']) && $icms['COD_RECEITA'] == '0'){
					    $cod_receita = isset($i[14])? $i[14] : '0';
					    $cod_receita = substr($cod_receita, 0,4);
					    $icms['COD_RECEITA'] = $this->numero($cod_receita);
				    }
				    if(isset($icms['COD_RECEITA']) && strlen($icms['COD_RECEITA'])!=4){
					    $cod_receita = isset($i[17])? $i[17] : '0';
					    $cod_receita = substr($cod_receita, 0,4);
					    $icms['COD_RECEITA'] = $this->numero($cod_receita);
				    }
			    }
		    }
		    preg_match('~03.cota ou refer.([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[0]));
			    // echo '<pre>', print_r($i); exit;
			
			    $expl = explode(' ', $i[2]);
			    $ref = isset($expl[0])? $expl[0] : '0';
			    $venc = isset($expl[1])? $expl[1] : '0';
			    $ano = date('Y');
			    if (preg_match("/\b{$ano}\b/i", $ref) && strlen($ref) == 7) {
				    $icms['REFERENCIA'] = trim($ref);
				    $data_vencimento = str_replace('/', '-', $venc);
				    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
			    }
			
			    $expl = explode(' ', $i[3]);
			    $ref = isset($expl[0])? $expl[0] : '0';
			    $venc = isset($expl[1])? $expl[1] : '0';
			    $ano = date('Y');
			    if (preg_match("/\b{$ano}\b/i", $ref) && strlen($ref) == 7) {
				    $icms['REFERENCIA'] = trim($ref);
				    $data_vencimento = str_replace('/', '-', $venc);
				    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
			    }
			
			    $expl = explode(' ', $i[4]);
			    $ref = isset($expl[0])? $expl[0] : '0';
			    $venc = isset($expl[1])? $expl[1] : '0';
			    $ano = date('Y');
			    if (preg_match("/\b{$ano}\b/i", $ref) && strlen($ref) == 7) {
				    $icms['REFERENCIA'] = trim($ref);
				    $data_vencimento = str_replace('/', '-', $venc);
				    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
			    }
			
			    $expl = explode(' ', $i[13]);
			    $ref = isset($expl[0])? $expl[0] : '0';
			    $venc = isset($expl[1])? $expl[1] : '0';
			    $ano = date('Y');
			    if (preg_match("/\b{$ano}\b/i", $ref) && strlen($ref) == 7) {
				    $icms['REFERENCIA'] = trim($ref);
				    $data_vencimento = str_replace('/', '-', $venc);
				    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
			    }
			
			    $expl = explode(' ', $i[14]);
			    $ref = isset($expl[0])? $expl[0] : '0';
			    $venc = isset($expl[1])? $expl[1] : '0';
			    $ano = date('Y');
			    if (preg_match("/\b{$ano}\b/i", $ref) && strlen($ref) == 7) {
				    $icms['REFERENCIA'] = trim($ref);
				    $data_vencimento = str_replace('/', '-', $venc);
				    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
			    }
			
			    $expl = explode(' ', $i[17]);
			    $ref = isset($expl[0])? $expl[0] : '0';
			    $venc = isset($expl[1])? $expl[1] : '0';
			    $ano = date('Y');
			    if (preg_match("/\b{$ano}\b/i", $ref) && strlen($ref) == 7) {
				    $icms['REFERENCIA'] = trim($ref);
				    $data_vencimento = str_replace('/', '-', $venc);
				    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
			    }
		    }
		    preg_match('~17.valor total([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[0]));
	//          echo '<pre>', print_r($i); exit;
			    if(isset($icms['VLR_RECEITA']) && preg_match("/^-?[0-9]+(?:\.[0-9]{1,2})?$/", $icms['VLR_RECEITA']) == false){
				    $expl = explode(' ', $i[2]);
				    if(isset($expl[0]) && isset($expl[2]) && isset($expl[3])){
					    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($expl[0])));
					    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($expl[0])));
					
					    $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',trim($expl[2])));
					    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',trim($expl[3])));
				    }
			    }
		    }
		    if(!isset($icms['VLR_TOTAL']) && isset($icms['VLR_RECEITA'])){
			    $icms['VLR_TOTAL'] = $icms['VLR_RECEITA'];
		    }
		    if(!isset($icms['JUROS_MORA']) || !isset($icms['MULTA_MORA_INFRA'])){
			    $icms['JUROS_MORA'] = '0.00';
			    $icms['MULTA_MORA_INFRA'] = '0.00';
		    }
		    
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
//         echo '<pre>', print_r($icmsarray[0]); exit;
        return $icmsarray;
    }


    public function icmsCE($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'CE';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~numeracao do codigo de barras([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            $codbarras = '';

            foreach ($i as $key => $value) {
                $codbarras .= $this->numero($value);
                if ($key == 3) {
                    break;
                }
            }
            $icms['CODBARRAS'] = substr($codbarras, 0, -1);
        }

        preg_match('~1 - codigo/especificacao da receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['COD_RECEITA'] = $i[0];
        }

        preg_match('~5 - periodo referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = trim(substr($i[0], 0,-1));
        }

        preg_match('~3 - pagamento ate([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $valorData = trim(substr($i[0], 0,10));
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }

        preg_match('~6 - valor principal \*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        }

        preg_match('~7 - multa

\*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        }

        if(empty($icms['MULTA_MORA_INFRA'])){
            preg_match('~7 - multa \*\*\*\*\* r\$([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) > 0) $a = explode('
', trim($i[0]));
                if(isset($a) && count($a) > 0) $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
            }
        }

        preg_match('~8 - juros

\*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        }

        if(empty($icms['JUROS_MORA'])){
            preg_match('~8 - juros \*\*\*\*\* r\$([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $a = explode('
', trim($i[0]));
                    if(isset($a) && count($a) > 0) $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                }
            }
        }

        preg_match('~10 - total a recolher

\*\*\*\*\* r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($i[0])));
        }

        if(empty($icms['VLR_TOTAL'])){
            preg_match('~10 - total a recolher \*\*\*\*\* r\$([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $a = explode('
', $i[0]);
                    if(isset($a) && count($a) > 0) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '', trim($a[0])));
                }
            }
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsPR($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'PR';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~periodo de referencia
05([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~codigo da receita
01
data de vencimento
02([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $a = explode(' ', $i[0]);
                if(isset($a) && count($a) > 0) {
                    $icms['COD_RECEITA'] = trim($a[0]);
                    $valorData = $a[1];
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                }
            }
        }

        preg_match('~valor da receita \(r\$\)
09([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$i[0]));;
        }

        preg_match('~total a recolher \(r\$\)
13([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$i[0]));;
        }


        preg_match('~contribuinte pagar no banco do brasil, itau, bradesco, santander, sicredi, bancoob ou rendimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));

            foreach ($i as $k => $x) {
                if (strlen($this->numero($x)) == 48) {
                    $codbarras = $this->numero($x);
                }
                if ($k == 7) {
                    break;
                }
            }

            $icms['CODBARRAS'] = trim($codbarras);
        }


        if (!isset($icms['CODBARRAS'])) {
            preg_match('~os valores e informacoes foram fornecidos pelo contribuinte pagar no banco do brasil, bancoob, bradesco, itau, rendimento, santander ou sicredi([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode("\n", trim($match[1]));

                foreach ($i as $k => $x) {
                    if (strlen($this->numero($x)) == 48) {
                        $codbarras = $this->numero($x);
                    }
                    if ($k == 7) {
                        break;
                    }
                }

                $icms['CODBARRAS'] = trim($codbarras);
            }
        }


        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsPE($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'PE';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~06 - codigo da receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['COD_RECEITA'] = trim($this->numero($i[0]));
        }

        preg_match('~07 - periodo fiscal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = trim($i[0]);
        }

        preg_match('~02 - data de vencimento([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) {
                $valorData = trim($i[0]);
                $data_vencimento = str_replace('/', '-', $valorData);
                $data_vencimento = substr($data_vencimento, 6, 4) . "-". substr($data_vencimento, 3, 2) . "-" . substr($data_vencimento, 0, 2);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
            }
        }

        if(!isset($icms['DATA_VENCTO'])) {
            preg_match('~Vencimento:([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $valorData = trim($i[0]);
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $data_vencimento = substr($data_vencimento, 6, 4) . "-". substr($data_vencimento, 3, 2) . "-" . substr($data_vencimento, 0, 2);
                    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                }
            }
        }

        preg_match('~05 - valor do tributo em r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
        }

        preg_match('~10 - valor dos juros em r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
        }

        preg_match('~08 - valor da multa em r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
        }

        preg_match('~16 - total a pagar em r\$([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
        }

        preg_match('~governo do estado de pernambuco secretaria da fazenda documento de arrecadacao estadual([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 2) {
                $codbarras = str_replace('-', '', str_replace(' ', '', $i[2]));
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }

        preg_match('~09 - documento de identificacao do contribuinte([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            if(isset($i) && count($i) > 2)  {
                $a = explode(' ', $i[2]);
                if(isset($a) && count($a) > 1) $icms['COD_IDENTIFICACAO'] = trim($this->numero($a[1]));
            }
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function icmsMA($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'MA';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        // novo layout incluído em 2020-12-01 10:02 - Vinny <marcus.coimbra@bravocorp.com.br>
		// echo "<pre>";
        // echo "<br>>>>>>>>>>> INI str<br>";
        // print_r($str);
        // echo "<br><<<<<<<<<< FIM str<br>";
		// echo "</pre>";

        // START old layout
//         preg_match('~data vencimento([^{]*)~i', $str, $match);
//         if (!empty($match)) {
//             $i = explode(' ', trim($match[1]));
//             if(isset($i) && count($i) > 0) {
//                 $valorData = trim(substr($i[0], 0,10));
//                 $data_vencimento = str_replace('/', '-', $valorData);
//                 $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
//             }
//         }

//         preg_match('~referencia/ parcela vencimento codigo da receita valor principal valor dos juros valor da multa

// valor total([^{]*)~i', $str, $match);

//         if (!empty($match)) {
//             $i = explode('
// ', trim($match[1]));

//             if(isset($i) && count($i) > 8) {
//                 $icms['REFERENCIA'] = trim($i[0]);
//                 if(strlen($i[0]) > 7){
//                     $icms['REFERENCIA'] = trim($i[2]);
//                 }

//                 $icms['COD_RECEITA'] = trim($i[4]);
//                 if(strlen($i[4]) > 4){
//                     $icms['COD_RECEITA'] = trim($i[6]);
//                 }
//                 $valores = explode(' ', $i[6]);
//                 if(count($valores) > 3){
//                     $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[0]))));
//                     $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[1]))));
//                     $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[2]))));
//                     $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim(str_replace('*', '', $valores[3])))));
//                 } else if(count($valores) === 3) {
//                     $valores = explode(' ', $i[8]);
//                     $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[0]))));
//                     $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[1]))));
//                     $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[2]))));
//                     $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim(str_replace('*', '', $valores[3])))));
//                 }
//             }
//         }

//         preg_match('~linha digitavel:([^{]*)~i', $str, $match);
//         if (!empty($match)) {
//             $i = explode(' ', trim($match[1]));
//             $codbarras = '';
//             foreach ($i as $key => $value) {
//                 if (is_numeric($this->numero($value)) && (strlen($this->numero($value)) == 11 || strlen($this->numero($value)) == 1)) {
//                     $codbarras .= $this->numero($value);
//                 }
//                 if ($key == 8) {
//                     break;
//                 }
//             }
//             $codbarras = str_replace('-', '', str_replace(' ', '', $codbarras));
//             $icms['CODBARRAS'] = trim($codbarras);
//         }

        // END old layout
        
        // START new layout 2020-12-04
        preg_match('~referencia/ parcela vencimento codigo da receita valor principal valor dos juros valor da multa

valor total([^{]*)~i', $str, $match);

        // echo "<pre>";
        // echo "<br>>>>>>>>>>> INI match<br>";
        // print_r($match);
        // echo "<br><<<<<<<<<< FIM match<br>";
        // echo "</pre>";
        
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));

            // echo "<pre>";
            // echo "<br>>>>>>>>>>> INI i OK<br>";
            // print_r($i);
            // echo "<br><<<<<<<<<< FIM i OK<br>";
            // echo "</pre>";
            
            // data vencimento (ANTECIPADOS) 1o layout
            if (isset($i[4]) && strlen($i[4]) == 10 && trim($i[43]) == "data vencimento" && trim($i[4]) == trim($i[49])) {
                $data_vencimento = str_replace('/', '-', $i[4]);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                // referencia
                if(isset($i[2]) && strlen($i[2]) == 7){
                    $icms['REFERENCIA'] = trim($i[2]);
                }
                // codigo receita 
                if(isset($i[6]) && strlen($i[6]) >= 3){
                    $icms['COD_RECEITA'] = trim($i[6]);
                }
                // valores ZFIC
                if (isset($i[8]) && strlen($i[8]) >= 12) {
                    $valores = explode(' ', $i[8]);
                    
                    if(count($valores) > 3){
                        $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[0]))));
                        $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[1]))));
                        $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[2]))));
                        $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim(str_replace('*', '', $valores[3])))));
                    }
                }
            // data vencimento (ICMS) 2o layout
            } else if (isset($i[2]) && strlen($i[2]) == 10 && trim($i[53]) == "data vencimento" && trim($i[2]) == trim($i[55])) {
                $data_vencimento = str_replace('/', '-', $i[2]);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                // referencia
                if(isset($i[0]) && strlen($i[0]) == 7){
                    $icms['REFERENCIA'] = trim($i[0]);
                }
                // codigo receita 
                if(isset($i[4]) && strlen($i[4]) >= 3){
                    $icms['COD_RECEITA'] = trim($i[4]);
                }
                // valores ZFIC
                if (isset($i[6]) && strlen($i[6]) >= 12) {
                    $valores = explode(' ', $i[6]);
                    
                    if(count($valores) > 3){
                        $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[0]))));
                        $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[1]))));
                        $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[2]))));
                        $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim(str_replace('*', '', $valores[3])))));
                    }
                }
            }
            // codigo de barras (ANTECIPADO + ICMS) 1o e 2o layouts
            preg_match('~linha digitavel:([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode(' ', trim($match[1]));
                $codbarras = '';
                foreach ($i as $key => $value) {
                    if (is_numeric($this->numero($value)) && (strlen($this->numero($value)) == 11 || strlen($this->numero($value)) == 1)) {
                        $codbarras .= $this->numero($value);
                    }
                    if ($key == 8) {
                        break;
                    }
                }
                $codbarras = str_replace('-', '', str_replace(' ', '', $codbarras));
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }
        // END new layout 2020-12-04
		
				if(empty($icms['DATA_VENCTO'])){
					preg_match('~data vencimento([^{]*)~i', $str, $match);
					if (!empty($match)) {
						$i = explode(PHP_EOL, trim($match[1]));
						$data_vencimento = str_replace('/', '-', $i[0]);
						$icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
					}
					
					preg_match('~valor total([^{]*)~i', $str, $match);
					if (!empty($match)) {
						$i = explode(PHP_EOL, trim($match[1]));
						$valor = explode(' ', $i[6]);
						$valor = trim(str_replace(',', '.', str_replace('.', '', trim($valor[0]))));
						$icms['VLR_TOTAL'] = $valor;
						$icms['VLR_RECEITA'] = $valor;
					}
					
					preg_match('~total juros([^{]*)~i', $str, $match);
					if (!empty($match)) {
						$i = explode(PHP_EOL, trim($match[1]));
						$icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
					}
					
					preg_match('~total multa([^{]*)~i', $str, $match);
					if (!empty($match)) {
						$i = explode(PHP_EOL, trim($match[1]));
						$icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[0]))));
					}
					
					preg_match('~codigo da receita([^{]*)~i', $str, $match);
					if (!empty($match)) {
						$i = explode(PHP_EOL, trim($match[1]));
						$icms['COD_RECEITA'] = trim($i[8]);
					}
					
					preg_match('~referencia/ parcela([^{]*)~i', $str, $match);
					if (!empty($match)) {
						$i = explode(PHP_EOL, trim($match[1]));
						$icms['REFERENCIA'] = trim($i[4]);
					}
				}

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms; //echo '<pre>', print_r($icmsarray[0]); exit;
        return $icmsarray;
    }

    public function icmsPI($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }
        
        $file_content = explode('_', $value['arquivo']);
/*        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        //$icms['IE'] = $estabelecimento->insc_estadual;
        $icms['CNPJ'] = $estabelecimento->cnpj;*/
        $icms['UF'] = 'PI';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~01 - inscricao estadual / renavam

02 - cpf/cnpj([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(PHP_EOL, trim($match[1]));
            if(isset($i) && count($i) > 2) $icms['CNPJ'] = $i[2];
            if(isset($i) && count($i) > 0){
            	$exp = explode(' ', $i[0]);
              $icms['IE'] = $exp[0];
            }
        }

        preg_match('~valor principal 18 - atualizacao monetaria 19 - juros 20 - multa 21 - taxa 22 - total a recolher
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));

            if(isset($i) && count($i) > 3) {
                $a = explode(' ', $i[0]);
                if(isset($a) && count($a) > 0) {
                    $icms['REFERENCIA'] = $a[0];
                    $valorData = trim($a[1]);
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                }

                $k = explode(' ', $i[1]);
                if(isset($k) && count($k) > 0) {
                    $icms['COD_RECEITA'] = $k[0];
                    $icms['VLR_TOTAL'] = trim(str_replace(',', '.', str_replace('.', '', trim($i[3]))));;
                }


                $valores = explode(' ', $i[2]);
                if(isset($valores) && count($valores) > 3) {
                    $icms['VLR_RECEITA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[0]))));
                    $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[3]))));
                    $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '', trim($valores[2]))));
                }
            }
        }

        preg_match('~11 - linha digitavel([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = '';
            foreach ($i as $key => $value) {
                if (is_numeric($this->numero($value))) {
                    $codbarras .= $this->numero($value);
                }
                if ($key == 5) {
                    break;
                }
            }
            $codbarras = str_replace('-', '', str_replace(' ', '', $codbarras));
            $icms['CODBARRAS'] = trim($codbarras);
        }

        if(!isset($icms['VLR_TOTAL']) || !isset($icms['VLR_RECEITA'])){
            preg_match('~17 - valor principal 18 - atualizacao monetaria([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',$i[0]));
            }

            preg_match('~19 - juros 20 - multa 21 - taxa([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $a = explode(' ', $i[0]);
                    if(isset($a) && count($a) > 1) {
                        $icms['JUROS_MORA'] = trim(str_replace(',', '.', str_replace('.', '',$a[0])));
                        $icms['MULTA_MORA_INFRA'] = trim(str_replace(',', '.', str_replace('.', '',$a[1])));
                    }
                }
            }

            preg_match('~22 - total a recolher([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));

                if(isset($i) && count($i) > 0) $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',$i[0]));
            }

            preg_match('~12 - periodo de referencia([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));

                if(isset($i) && count($i) > 0) $icms['REFERENCIA'] = $i[0];

            }

            preg_match('~13 - data de vencimento([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 0) {
                    $valorData = trim($i[0]);
                    $data_vencimento = str_replace('/', '-', $valorData);
                    $icms['DATA_VENCTO'] = date ('Y-m-d', strtotime($data_vencimento));
                }
            }

            preg_match('~14 - codigo da receita([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                if(isset($i) && count($i) > 0) $icms['COD_RECEITA'] = $i[0];
            }

            preg_match('~02 - cnpj/cpf([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));

                if(isset($i) && count($i) > 0) $icms['IE'] = $i[0];

            }

            preg_match('~11 - linha digitavel([^{]*)~i', $str, $match);
            if (!empty($match)) {
                $i = explode('
', trim($match[1]));
                $codbarras = '';
                foreach ($i as $key => $value) {
                    if (is_numeric($this->numero($value))) {
                        $codbarras .= $this->numero($value);
                    }
                    if ($key == 5) {
                        break;
                    }
                }
                $codbarras = str_replace('-', '', str_replace(' ', '', $codbarras));
                $icms['CODBARRAS'] = trim($codbarras);
            }
        }

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }


    public function numero($str) {
        return preg_replace("/[^0-9]/", "", $str);
    }


    public function icmsAL($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['CNPJ'] = $estabelecimento->cnpj;
        $icms['IE'] = $estabelecimento->insc_estadual;
        $icms['UF'] = 'AL';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAC';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
        }

        preg_match('~caceal([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 0) $icms['IE'] = trim($this->numero($i[0]));
        }

        preg_match('~receita([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));
            if(isset($i) && count($i) > 0){
                $icms['COD_RECEITA'] =substr(str_replace('/', '', str_replace('-', '', str_replace('.', '', trim($this->numero($i[0]))))), 0, -6);
                if (empty($icms['COD_RECEITA'])) {
                    $icms['COD_RECEITA'] = trim($this->numero($i[0]));
                }
            }
        }

        preg_match('~referencia([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(' ', trim($match[1]));

            if(isset($i) && count($i) > 0) {
                $a = explode('
', $i[0]);
                if(isset($a[2])){
                    $icms['REFERENCIA'] = trim($a[2]);
                }
                if (isset($a[2]) && !is_numeric(str_replace('/','',$a[2]))) {
                    $icms['REFERENCIA'] = substr($i[0], 0,7);
                }
            }
        }

        preg_match('~vencimento principal cm desconto juros multa total([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));

            if(isset($i) && count($i) > 0) {
                $a = explode(' ', $i[0]);

                $valorData = trim($a[0]);
                $data_vencimento = str_replace('/', '-', $valorData);
                $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
                if(empty($icms['REFERENCIA'])){
                    $referencia = date('m/Y', strtotime($data_vencimento));
                    $k = explode('/', $referencia);
                    $k[0] = $k[0]-1;
                    if ($k[0] == 0) {
                        $k[1] = $k[1] - 1;
                    }
                    if (strlen($k[0]) == 1) {
                        $k[0] = '0'.$k[0];
                    }
                    $icms['REFERENCIA'] = $k[0].'/'.$k[1];
                }

                if(empty($icms['COD_RECEITA'])){
                    preg_match('~
data de emissao
([^{]*)~i', $str, $match);
                    if (!empty($match)) {
                        $i = explode('
', trim($match[1]));
                        $icms['COD_RECEITA'] = $this->numero(trim($i[0]));
                    }
                }

                $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($a[1])));
                if(isset($a[1]) && strlen($a[1]) == 1){
                    // $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($a[2])));
                    $icms['VLR_RECEITA'] = $a[1].str_replace(',', '.', str_replace('.', '',trim($a[2])));
                }

                $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',trim($a[4])));
                $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',trim($a[5])));
                $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($a[6])));
                if(isset($a[7]) && strlen($a[7]) == 1){
                    // $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($a[8])));
                    $icms['VLR_TOTAL'] = $a[7].str_replace(',', '.', str_replace('.', '',trim($a[8])));
                }
            }
        }

        preg_match('~via - banco([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode('
', trim($match[1]));
            $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
            $icms['CODBARRAS'] = trim($codbarras);
        }
	
		    preg_match('~receita([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[1]));
			    $icms['COD_RECEITA'] = trim($this->numero($i[4]));
		    }
	
	      if(!isset($icms['REFERENCIA']) || isset($icms['REFERENCIA']) && strlen($icms['REFERENCIA']) != 7){
			    preg_match('~referencia([^{]*)~i', $str, $match);
			    if (!empty($match)) {
				    $i = explode(PHP_EOL, trim($match[1]));
				    $icms['REFERENCIA'] = trim($i[6]);
			    }
		    }
		
		    preg_match('~vencimento:([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[1]));
			    $valorData = trim($i[0]);
			    $data_vencimento = str_replace('/', '-', $valorData);
			    $icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
		    }
		
		    preg_match('~principal:([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[1]));
			    $icms['VLR_RECEITA'] = str_replace(',', '.', str_replace('.', '',trim($i[0])));
		    }
		
		    preg_match('~juros:([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[1]));
			    $icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '',trim($i[0])));
		    }
		
		    preg_match('~multa:([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[1]));
			    $icms['MULTA_MORA_INFRA'] = str_replace(',', '.', str_replace('.', '',trim($i[0])));
		    }
		
		    preg_match('~total:([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[1]));
			    $total = explode(' ', $i[0]);
			    $icms['VLR_TOTAL'] = str_replace(',', '.', str_replace('.', '',trim($total[0])));
		    }
		
		    preg_match('~1º via- banco([^{]*)~i', $str, $match);
		    if (!empty($match)) {
			    $i = explode(PHP_EOL, trim($match[1]));
			    $codbarras = str_replace('-', '', str_replace(' ', '', $i[0]));
			    $icms['CODBARRAS'] = trim($codbarras);
		    }
		    
        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }


    public function icmsSP($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['UF'] = 'SP';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && in_array($file_content[4], ['SP.pdf','SP'])) {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && !in_array($file_content[4], ['SP.pdf','SP'])) {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
		}
		
		// novo layout incluído em 2020-06-22 17:21 - Vinny <marcus.coimbra@bravocorp.com.br> 
		// echo "<pre>";
        // echo "<br>>>>>>>>>>> INI str<br>";
        // print_r($str);
        // echo "<br><<<<<<<<<< FIM str<br>";
		// echo "</pre>";
		// TODO: Refazer icmsSP()
		// adição de desvio para processar guias sem anterar antigo
		// preg_match('~governo do estado de sao paulo secretaria de estado dos negocios da fazenda([^{]*)~i', $str, $match_layout_novo);
		// if (!empty($match_layout_novo)) { // BEGIN layout novo
		// 		// echo "<pre>";
		// 		// echo "<br>>>>>>>>>>>>>>>>>>>>> INI match_layout_novo<br>";
		// 		// print_r($match_layout_novo);
		// 		// echo "<br><<<<<<<<<<<<<<<<<<<< FIM match_layout_novo<br>";
		// 		// echo "</pre>";
		// 		$varArrTxt = explode("\n", trim($match_layout_novo[1]));
		// 		echo "<pre>";
		// 		echo "<br>>>>>>>>>>>>>>>>>>>>> INI varArrTxt<br>";
		// 		print_r($varArrTxt);
		// 		echo "<br><<<<<<<<<<<<<<<<<<<< FIM varArrTxt<br>";
		// 		echo "</pre>";
				
		// 		// cpnj ou cpf - $varArrTxt[41]
		// 		preg_match('~cnpj ou cpf'.PHP_EOL.'05([^{]*)~i', $str, $match);
		// 			if(!empty($match)){
		// 				$icms['CNPJ'] = trim(preg_replace("/[^0-9]/", "", explode("\n", trim($match[1]))[0]));
		// 			}
		// 		// inscricao estadual - $varArrTxt[24]
		// 		preg_match('~inscricao estadual'.PHP_EOL.'04([^{]*)~i', $str, $match);
		// 			if(!empty($match)){
		// 				$icms['IE'] = trim(preg_replace("/[^0-9]/", "", explode("\n", trim($match[1]))[0]));
		// 			}

							

						
		// 		echo "<pre>";
		// 		echo "<br>>>>>>>>>>>>>>>>>>>>> INI ICMS[]<br>";
		// 		print_r($icms);
		// 		echo "<br><<<<<<<<<<<<<<<<<<<< FIM ICMS[]<br>";
		// 		echo "</pre>";
		// 		exit;
		// 	} else {
					//inscricao estadual
					// preg_match('~inscricao estadual([^{]*)~i', $str, $match);
					// if (!empty($match)) {
					// 	$a = explode("\n", trim($match[1]));
			
					// 	if(isset($a) && count($a) > 2) {
					// 		$i = explode(' ', trim($a[2]));
					// 		if(isset($i) && count($i) > 0) $icms['IE'] = trim(str_replace(".", "", $i[0]));
					// 	}
					// }
		//inscricao estadual
		preg_match('~inscricao estadual'.PHP_EOL.'04([^{]*)~i', $str, $match);
			if(!empty($match)){
				$icms['IE'] = trim(preg_replace("/[^0-9]/", "", explode("\n", trim($match[1]))[0]));
			}
	
		//razão social
		preg_match('~nome ou razao social
15([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$a = explode(' ', trim($match[1]));
			if(isset($a) && count($a) > 0) $icms['CONTRIBUINTE'] = trim($a[0]);
		}

		//municipio
		preg_match('~municipio([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$i = explode('
', trim($match[1]));
			if(isset($i) && count($i) > 0) $icms['MUNICIPIO'] = trim($i[0]);
		}

		//cpf - cpnj
		preg_match('~cnpj ou cpf
05([^{]*)~i', $str, $match);

		if(!empty($match)){
			$i = explode("
", trim($match[1]));
			if(isset($i) && count($i) > 0) $icms['CNPJ'] = trim(preg_replace("/[^0-9]/", "", $i[0]));
		} else {
			preg_match('~cnpj ou cpf ([^{]*)~i', $str, $match);
			if(!empty($match)){
				$i = explode("

", trim($match[1]));
				if(isset($i) && count($i) > 0) $icms['CNPJ'] = substr(trim(preg_replace("/[^0-9]/", "", $i[0])), 0, 14);
			}			
		}

	    preg_match('~cnpj/cpf:([^{]*)~i', $str, $match);
	    if (!empty($match)) {
		    $k = explode(PHP_EOL, trim($match[0]));
		    $icms['CNPJ'] = isset($k[0])? $this->numero($k[0]) : null;
	    }

		//referencia verificar
		preg_match('~referencia \(mes/ano\)
07 ([^{]*)~i', $str, $match);

		if (!empty($match)) {
			$k = explode('
', trim($match[1]));
			if(isset($k) && count($k) > 0) $icms['REFERENCIA'] = trim($k[0]);
		} else {
			preg_match('~referencia \(mes/ano\)([^{]*)~i', $str, $match);

			if (!empty($match)) {
				$k = explode('

', trim($match[1]));
				if(isset($k) && count($k) > 1) $icms['REFERENCIA'] = trim($k[1]);
			}			
		}

		//cod_receita
		preg_match('~codigo da receita
03([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$k = explode('
', trim($match[1]));
			if(isset($k) && count($k) > 0) $icms['COD_RECEITA'] = trim($k[0]);
		}
	
	    preg_match('~inscricao estadual:([^{]*)~i', $str, $match);
	    if (!empty($match)) {
		    $k = explode(PHP_EOL, trim($match[1]));
		    $icms['IE'] = isset($k[0])? $this->numero(trim($k[0])) : null;
	    }
	    
		if (empty($icms['IE'])) {
			//inscricao estadual
			preg_match('~inscricao estadual([^{]*)~i', $str, $match);
			if (!empty($match)) {
				$k = explode('
', trim($match[1]));
				if(isset($k) && count($k) > 2) $icms['IE'] = $this->numero(trim($k[2]));
				if(isset($k[0]) && isset($k[121]) && $k[121] == 'pagina 1 de 1'){
					$icms['IE'] = str_replace('.','',$k[0]);
				}
				
			}
		}
		
		//observacao
		preg_match('~observacoes
21([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$l = explode('
', $match[1]);
			$icms['OBSERVACAO'] = '';
			foreach($l as $lk)
			{
				if($lk === '22 autenticacao mecanica') {
					break;
				}
				$icms['OBSERVACAO'] .= ' '.trim($lk);
			}
		}


		//vlr_receita
		preg_match('~valor da receita \(nominal ou corrigida\)
09
juros de mora
10
([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$i = explode("
", trim($match[1]));
			if(isset($i) && count($i) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $i[0])));;
		} else {
			preg_match('~VIA CONTRIBUINTE([^{]*)~i', $str, $match);
			if (!empty($match)) {
				$i = explode("

", trim($match[1]));
				if(isset($i) &&  count($i) > 0) {
					$j = explode("09 valor da receita ", $i[0]);
					if(isset($j) && count($j) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $j[0])));;					
				} 
			}	
		}

		//vlr_total
		preg_match('~valor total
14([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$string = explode('
',trim($match[1]));
			if(isset($string) && count($string) > 0) $icms['VLR_TOTAL'] = str_replace(',', '.', trim(str_replace('.', '', $string[0])));
		} else {
			preg_match('~valor total([^{]*)~i', $str, $match);
					if (!empty($match)) {
						$string = explode('

',trim($match[1]));
						if(isset($string) && count($string) > 0) $icms['VLR_TOTAL'] = str_replace(',', '.', trim(str_replace('.', '', $string[0])));
					}			
		}
	
	    preg_match('~valor total do saldo credor do periodo anterior([^{]*)~i', $str, $match);
	    if (!empty($match)) {
		    $k = explode(PHP_EOL, trim($match[1]));
		    if(isset($k[3])){
		    	$k = explode(' ', $k[3]);
			    $icms['VLR_TOTAL'] = isset($k[12])? str_replace(',', '.', trim(str_replace('.', '', $k[12]))) : '0,00';
		    }
	    }

		//ie e cnae
		preg_match('~placa do veiculo
20([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$string = trim($match[1]);
			$string = explode('
',$string);
			if(isset($string) && count($string) > 0) $icms['CNAE'] = $string[0];
		}

		//cidade e endereço e data vencimento
		preg_match('~endereco
16 ([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$string = trim($match[1]);

			$string = explode('
',$string);
			if(isset($string) && count($string) > 0) $icms['ENDERECO'] = $string[0];
		}

		preg_match('~data de vencimento([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$string = trim($match[1]);
			$string = explode('
',$string);
			if(isset($string) && count($string) > 0) {
				$valorData = $string[0];
				$data_vencimento = str_replace('/', '-', $valorData);
				$icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
			}
		}

		preg_match('~juros de mora([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$string = trim($match[1]);
			$string = explode('
',$string);
			if(strlen($string[0]) != 2)
				$icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $string[0]));
		}

        if(isset($icms['IE'])) {
            $v = $this->numero($icms['IE']);
            if (empty($v)) {
                //inscricao estadual
                preg_match('~inscricao estadual([^{]*)~i', $str, $match);
                if (!empty($match)) {
                    $k = explode("\n", trim($match[1]));
                    if(isset($k) && count($k) > 2) $icms['IE'] = $this->numero(trim($k[2]));
                }
            }
        }

		if (!isset($icms['VLR_RECEITA']) || empty($icms['VLR_RECEITA'])) {
			preg_match('~valor da receita \(nominal ou corrigida\)([^{]*)~i', $str, $match);

			if (!empty($match)) {
				$i = explode("\n", trim($match[1]));
				if(isset($i) && count($i) > 2) $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $i[2])));;
			}
		}
		// } fim do if novo layout, comentado no TODO
		
		// echo "<pre>";
        // echo "<br>>>>>>>>>>>>>>>>>>>>> INI icms[]<br>";
        // print_r($icms);
        // echo "<br><<<<<<<<<<<<<<<<<<<< FIM icms[]<br>";
        // echo "</pre>";

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }


    public function icmsSC($value)
    {
        $icms = array();
        if (!file_exists($value['pathtxt'])) {
            return $icms;
        }

        $file_content = explode('_', $value['arquivo']);
        $atividade = Atividade::findOrFail($file_content[0]);
        $estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
        $icms['UF'] = 'SC';

        $handle = fopen($value['pathtxt'], "r");
        $contents = fread($handle, filesize($value['pathtxt']));
        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        $icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] == 'SP.pdf') {
            $icms['IMPOSTO'] = 'GAREI';
        }

        if ($this->letras($file_content[2]) == 'ICMS' && $file_content[4] != 'SP.pdf') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'DIFAL') {
            $icms['IMPOSTO'] = 'SEFAZ';
        }

        if ($this->letras($file_content[2]) == 'ANTECIPADO' || $this->letras($file_content[2]) == 'ICMSST') {
            $icms['IMPOSTO'] = 'SEFAB';
        }

        if ($this->letras($file_content[2]) ==  'TAXA' || $this->letras($file_content[2]) ==  'PROTEGE' || $this->letras($file_content[2]) ==  'FECP' || $this->letras($file_content[2]) ==  'FEEF' || $this->letras($file_content[2]) ==  'UNIVERSIDADE' || $this->letras($file_content[2]) ==  'FITUR') {
            $icms['IMPOSTO'] = 'SEFAT';
		}
		
		preg_match('~rg ([^{]*)~i', $str, $match);
			if(!empty($match)){
				$i = explode('

', $match[0]);
				$icms['IE'] = trim(preg_replace("/[^0-9]/", "", trim($i[0]) ) );
			}
	
		//razão social
		preg_match('~nome/razao social ([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$i = explode('

', $match[0]);
			if(isset($i) && count($i) > 0) $j = explode("nome/razao social ", $i[0]);
			if(isset($j) && count($j) > 1) $icms['CONTRIBUINTE'] = trim($j[1]);
		}

		$meses = array( 'janeiro' 	=> '01', 'fevereiro' 	=> '02', 'marco' 	=> '03', 'abril' 	=> '04', 'maio' 	=> '05', 'junho' 	=>'06', 
						'julho'		=> '07', 'agosto'		=> '08', 'setembro'	=> '09', 'outubro'	=> '10', 'novembro'	=> '11', 'dezembro'	=> '12');

		//referencia verificar
		preg_match('~referencia/parcela ([^{]*)~i', $str, $match);

		if (!empty($match)) {
			$k = explode('
', trim($match[1]));
			if(isset($k) && count($k) > 0) {
				$data = explode("/", $k[0]);
				if(isset($data) && count($data) > 0) {
					$data[0] = $meses[$data[0]];
					if(isset($k) && count($k) > 0) $icms['REFERENCIA'] = $data[0]."/".$data[1];
				}
			}
		}

		//cod_receita
		preg_match('~codigo receita ([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$k = explode('
', trim($match[1]));
			if(isset($k) && count($k) > 0) $icms['COD_RECEITA'] = trim($k[2]);
		}

		//vlr_total
		preg_match('~principal([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$string = explode('
',trim($match[1]));
			$vlr_receita = explode(" ", $string[2]);
			$vlr_receita = $vlr_receita[0];
			if(isset($string) && count($string) > 0) $icms['VLR_RECEITA'] = str_replace(',', '.', trim(str_replace('.', '', $vlr_receita)));
		}

		//vlr_total
		preg_match('~total a pagar([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$string = explode('
',trim($match[1]));
			if(isset($string) && count($string) > 0) $icms['VLR_TOTAL'] = str_replace(',', '.', trim(str_replace('.', '', $string[0])));
		}

		preg_match('~vencimento([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$string = trim($match[1]);
			$string = explode('
',$string);
			if(isset($string) && count($string) > 0) {
				$valorData = $string[0];
				$data_vencimento = str_replace('/', '-', $valorData);
				$icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
			}
		}

		preg_match('~juros([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$string = trim($match[1]);
			$string = explode('
',$string);
			if(strlen($string[0]) != 2)
				$icms['JUROS_MORA'] = str_replace(',', '.', str_replace('.', '', $string[0]));
		}
		

        fclose($handle);
        $icmsarray = array();
        $icmsarray[0] = $icms;
        return $icmsarray;
    }

    public function search_criticas()
    {
        return view('guiaicms.search_criticas');
    }


    public function criticas(Request $request)
    {
        $mensagem = "Não existem críticas no período selecionado.";
        $input = $request->all();
        if (empty($input['inicio']) || empty($input['fim'])) {
            return redirect()->back()->with('status', 'É necessário informar as duas datas.');
        }

        $data_inicio = $input['inicio']. ' 00:00:00';
        $data_fim = $input['fim'].' 23:59:59';

        $sql = "Select DATE_FORMAT(A.Data_critica, '%d/%m/%Y') as Data_critica, B.codigo, C.nome, A.critica, A.arquivo, A.importado FROM criticasleitor A LEFT JOIN estabelecimentos B ON A.Estemp_id = B.id LEFT JOIN tributos C ON A.Tributo_id = C.id WHERE A.Data_critica BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.Empresa_id = ".$this->s_emp->id." ";

        $dados = json_decode(json_encode(DB::Select($sql)),true);

        if (!empty($dados)) {
            $mensagem = '';
        }

        return view('guiaicms.search_criticas')->withDados($dados)->with('mensagem', $mensagem);
    }


    public function search_criticas_entrega()
    {
        return view('guiaicms.search_criticas_entrega');
    }

    public function criticas_entrega(Request $request)
    {
        $input = $request->all();
        if (empty($input['inicio']) || empty($input['fim'])) {
            return redirect()->back()->with('status', 'É necessário informar as duas datas.');
        }

        $data_inicio = $input['inicio'].' 00:00:00';
        $data_fim = $input['fim'].' 23:59:59';

        $sql = "Select DATE_FORMAT(A.Data_critica, '%d/%m/%Y') as Data_critica, B.codigo, C.nome, A.critica, A.arquivo, A.importado FROM criticasentrega A LEFT JOIN estabelecimentos B ON A.Estemp_id = B.id INNER JOIN tributos C ON A.Tributo_id = C.id WHERE A.Data_critica BETWEEN '".$data_inicio."' AND '".$data_fim."' AND A.Empresa_id = ".$this->s_emp->id." ";

        $dados = json_decode(json_encode(DB::Select($sql)),true);

        return view('guiaicms.search_criticas_entrega')->withDados($dados);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function icms()
    {

        $estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->pluck('codigo','id');
        $uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->pluck('UF','UF');
        $estabelecimentosselected = array();
        $ufselected = array();

        return view('guiaicms.icms')->withEstabelecimentos($estabelecimentos)->withUf($uf)->withestabelecimentosselected($estabelecimentosselected)->withufselected($estabelecimentosselected)->with('enviar_zfic', false);
    }

    public function planilha(Request $request)
    {
	    $input = $request->all();
	    $estabelecimentosselected = array();
	    if (!empty($input['multiple_select_estabelecimentos'])) {
		    $estabelecimentosselected = $input['multiple_select_estabelecimentos'];
	    }
	
	    $ufselected = array();
	    if (!empty($input['multiple_select_uf'])) {
		    $ufselected = $input['multiple_select_uf'];
	    }
	
	    $estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->pluck('codigo', 'id');
	    $uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->pluck('UF', 'UF');
	
	    if (empty($input['inicio']) || empty($input['fim'])) {
		    return redirect()->back()->with('status', 'É necessário informar as datas de inicio e fim.');
	    }
	
	    $data_inicio = $input['inicio'] . ' 00:00:00';
	    $data_fim = $input['fim'] . ' 23:59:59';
	
	    $sql = "SELECT A.*, T.nome as tributo_nome, E.nome_arquivo, E.usuario_analista_id, B.empresa_id, B.codigo, C.uf, D.centrocusto, if(A.CONFERENCIAGUIAS_ID is not null and E.statusconferencia_id in(1,3), 'N', 'S') as valido FROM guiaicms A LEFT JOIN estabelecimentos B on A.CNPJ = B.cnpj inner join municipios C on B.cod_municipio = C.codigo left join centrocustospagto D on B.id = D.estemp_id left join conferenciaguias E on E.id = A.CONFERENCIAGUIAS_ID left join tributos T on T.id = A.TRIBUTO_ID WHERE A.DATA_VENCTO BETWEEN '" . $data_inicio . "' AND '" . $data_fim . "' AND A.CODBARRAS <> ''";
	
	    if (!empty($input['inicio_leitura']) && !empty($input['fim_leitura'])) {
		    $inicio_leitura = $input['inicio_leitura'] . ' 00:00:00';
		    $fim_leitura = $input['fim_leitura'] . ' 23:59:59';
		
		    $sql .= " AND A.DATA BETWEEN '" . $inicio_leitura . "' AND '" . $fim_leitura . "'";
	    }
	
	    if (!empty($input['multiple_select_estabelecimentos'])) {
		    $sql .= " AND A.CNPJ IN (Select cnpj FROM estabelecimentos where id IN (" . implode(',', $input['multiple_select_estabelecimentos']) . "))";
	    }
	
	    if (!empty($input['multiple_select_uf'])) {
		    $sql .= " AND A.UF IN (" . implode(',', array_map(function ($value) {
				    return "'$value'";
			    }, $input['multiple_select_uf'])) . ")";
	    }
			// echo $sql; exit;
	    $dados = json_decode(json_encode(DB::Select($sql)), true);
	
	    $planilha = array();
	    foreach ($dados as $key => $dado) {
		    if ($dado['empresa_id'] == $this->s_emp->id) {
			    $planilha[] = $dado;
		    }
	    }
	
	    $sql_semcod = "SELECT A.*, T.nome as tributo_nome, E.nome_arquivo, E.usuario_analista_id, B.empresa_id, B.codigo, C.uf, D.centrocusto, C.codigo_sap, if(A.CONFERENCIAGUIAS_ID is not null and E.statusconferencia_id in(1,3), 'N', 'S') as valido FROM guiaicms A LEFT JOIN estabelecimentos B on A.CNPJ = B.cnpj left join municipios C on B.cod_municipio = C.codigo left join centrocustospagto D on B.id = D.estemp_id left join conferenciaguias E on E.id = A.CONFERENCIAGUIAS_ID left join tributos T on T.id = A.TRIBUTO_ID WHERE A.DATA_VENCTO BETWEEN '" . $data_inicio . "' AND '" . $data_fim . "' AND A.CODBARRAS = ''";
	
	    if (!empty($input['multiple_select_estabelecimentos'])) {
		    $sql_semcod .= " AND A.CNPJ IN (Select cnpj FROM estabelecimentos where id IN (" . implode(',', $input['multiple_select_estabelecimentos']) . "))";
	    }
	
	    if (!empty($input['multiple_select_uf'])) {
		    $sql_semcod .= " AND A.UF IN (" . implode(',', array_map(function ($value) {
				    return "'$value'";
			    }, $input['multiple_select_uf'])) . ")";
	    }
	
	    if (!empty($input['inicio_leitura']) && !empty($input['fim_leitura'])) {
		    $inicio_leitura = $input['inicio_leitura'] . ' 00:00:00';
		    $fim_leitura = $input['fim_leitura'] . ' 23:59:59';
		
		    $sql_semcod .= " AND A.DATA BETWEEN '" . $inicio_leitura . "' AND '" . $fim_leitura . "'";
	    }
	    // echo $sql_semcod; exit;
	    $dados_semcod = json_decode(json_encode(DB::Select($sql_semcod)), true);
	
	    $planilha_semcod = array();
	    foreach ($dados_semcod as $key => $dado) {
		    if ($dado['empresa_id'] == $this->s_emp->id) {
			    $planilha_semcod[] = $dado;
		    }
	    }
	
	    foreach ($planilha as $chave => $valorl) {
		    if ($valorl['MULTA_MORA_INFRA'] == 0) {
			    $planilha[$chave]['MULTA_MORA_INFRA'] = '';
		    }
		
		    if ($valorl['HONORARIOS_ADV'] == 0) {
			    $planilha[$chave]['HONORARIOS_ADV'] = '';
		    }
		
		    if ($valorl['ACRESC_FINANC'] == 0) {
			    $planilha[$chave]['ACRESC_FINANC'] = '';
		    }
		
		    if ($valorl['JUROS_MORA'] == 0) {
			    $planilha[$chave]['JUROS_MORA'] = '';
		    }
		
		    if ($valorl['MULTA_PENAL_FORMAL'] == 0) {
			    $planilha[$chave]['MULTA_PENAL_FORMAL'] = '';
		    }
	    }
	
	    foreach ($planilha_semcod as $chave2 => $valorl2) {
		    if ($valorl2['MULTA_MORA_INFRA'] == 0) {
			    $planilha_semcod[$chave2]['MULTA_MORA_INFRA'] = '';
		    }
		
		    if ($valorl2['HONORARIOS_ADV'] == 0) {
			    $planilha_semcod[$chave2]['HONORARIOS_ADV'] = '';
		    }
		
		    if ($valorl2['ACRESC_FINANC'] == 0) {
			    $planilha_semcod[$chave2]['ACRESC_FINANC'] = '';
		    }
		
		    if ($valorl2['JUROS_MORA'] == 0) {
			    $planilha_semcod[$chave2]['JUROS_MORA'] = '';
		    }
		
		    if ($valorl2['MULTA_PENAL_FORMAL'] == 0) {
			    $planilha_semcod[$chave2]['MULTA_PENAL_FORMAL'] = '';
		    }
	    }
	
	    $valorData = $data_fim;
	    $data_vencimento_2 = str_replace('-', '/', $valorData);
	    $data_fim = date('dmY', strtotime($data_vencimento_2));
	
	    $valorData2 = $data_inicio;
	    $data_vencimento = str_replace('-', '/', $valorData2);
	    $data_inicio = date('dmY', strtotime($data_vencimento));
	
	    $mensagem = 'Período carregado com sucesso';
	    if (empty($dados) && empty($dados_semcod)) {
		    $mensagem = 'Não há dados nesse período';
	    }
	
	    if (!empty($planilha)) {
		    foreach ($planilha as $key => $value) {
			    $planilha[$key]['VLR_RECEITA'] = $this->maskMoeda($value['VLR_RECEITA']);
			    $planilha[$key]['JUROS_MORA'] = $this->maskMoeda($value['JUROS_MORA']);
			    $planilha[$key]['MULTA_MORA_INFRA'] = $this->maskMoeda($value['MULTA_MORA_INFRA']);
			    $planilha[$key]['ACRESC_FINANC'] = $this->maskMoeda($value['ACRESC_FINANC']);
			    $planilha[$key]['HONORARIOS_ADV'] = $this->maskMoeda($value['HONORARIOS_ADV']);
			    $planilha[$key]['MULTA_PENAL_FORMAL'] = $this->maskMoeda($value['MULTA_PENAL_FORMAL']);
			    $planilha[$key]['VLR_TOTAL'] = $this->maskMoeda($value['VLR_TOTAL']);
		    }
	    }
	
	    if (!empty($planilha_semcod)) {
		    foreach ($planilha_semcod as $key => $value) {
			    $planilha_semcod[$key]['VLR_RECEITA'] = $this->maskMoeda($value['VLR_RECEITA']);
			    $planilha_semcod[$key]['JUROS_MORA'] = $this->maskMoeda($value['JUROS_MORA']);
			    $planilha_semcod[$key]['MULTA_MORA_INFRA'] = $this->maskMoeda($value['MULTA_MORA_INFRA']);
			    $planilha_semcod[$key]['ACRESC_FINANC'] = $this->maskMoeda($value['ACRESC_FINANC']);
			    $planilha_semcod[$key]['HONORARIOS_ADV'] = $this->maskMoeda($value['HONORARIOS_ADV']);
			    $planilha_semcod[$key]['MULTA_PENAL_FORMAL'] = $this->maskMoeda($value['MULTA_PENAL_FORMAL']);
			    $planilha_semcod[$key]['VLR_TOTAL'] = $this->maskMoeda($value['VLR_TOTAL']);
		    }
	    }
	
	    if(!empty($input['enviar_zfic'])){
		    $comCodBarras = $this->geraCsvZfic($planilha, $data_inicio, $data_fim);
		    $semCodBarras = $this->geraCsvZfic($planilha_semcod, $data_inicio, $data_fim, false);
		
		    $empresaNome = explode(' ', $this->s_emp->razao_social);
		    $empresaNome = isset($empresaNome[0]) ? $empresaNome[0] : '';
		    $empresaCNPJ = substr($this->s_emp->cnpj, 0, 8);
		    $pathEmpresa = $empresaNome . '_' . $empresaCNPJ;
		
		    // Criar .zip com os arquivos
		    $copiarArquivosAtividadesJob = $this->copiarArquivosParaAtividadesJob();
		    $public_dir = public_path();
		    $zipFileName = date('YmdHis') . '.zip';
		    $zip = new \ZipArchive();
		    if ($zip->open($public_dir . '/' . $zipFileName, \ZipArchive::CREATE) === TRUE) {
			
			    if (!empty($comCodBarras)) {
				    $zip->addFile($comCodBarras['arquivo'], $comCodBarras['nome']);
			    }
			    if (!empty($semCodBarras)) {
				    $zip->addFile($semCodBarras['arquivo'], $semCodBarras['nome']);
			    }
			
			    if (!empty($planilha)) {
				    foreach ($planilha as $value) {
					    $pasta = '';
					    if (!empty($value['nome_arquivo']) && $value['valido'] == 'S') {
						    $nome_arquivo = str_replace(['.pdf', '.PDF'], '', $value['nome_arquivo']);
						    $explode = explode('_', $nome_arquivo);
						    $pasta = $explode[2] . '_' . $explode[3] . '_' . $explode[4];
						
						    if (file_exists('impostos/' . $pathEmpresa .'/'. $value['usuario_analista_id'] . '/' . $pasta . '/' . $value['nome_arquivo'])) {
							    // echo 'impostos/' . $pathEmpresa .'/'. $value['usuario_analista_id'] . '/' . $pasta . '/' . $value['nome_arquivo'];
							    $zip->addFile('impostos/' . $pathEmpresa .'/'. $value['usuario_analista_id'] . '/' . $pasta . '/' . $value['nome_arquivo'], $value['nome_arquivo']);
							
							    $caminhoPastaEntregar = $copiarArquivosAtividadesJob . '/entregar/' . $explode[0].'_'.$explode[1].'_'.str_replace(' ','',$value['tributo_nome']).'_'.$explode[3].'_'.$explode[4];
							    if(!is_dir($caminhoPastaEntregar)){
								    @mkdir($caminhoPastaEntregar, 0777);
							    }
							    // Copiar arquivos para a pasta `entregar` para rodar o /atividades/Job
							    @copy('impostos/' . $pathEmpresa .'/'. $value['usuario_analista_id'] . '/' . $pasta . '/' . $value['nome_arquivo'], $caminhoPastaEntregar.'/'. $value['nome_arquivo']);
						    }
					    }
				    }
			    }
			
			    if (!empty($planilha_semcod)) {
				    foreach ($planilha_semcod as $value) {
					    $pasta = '';
					    if (!empty($value['nome_arquivo']) && $value['valido'] == 'S') {
						    $nome_arquivo = str_replace(['.pdf', '.PDF'], '', $value['nome_arquivo']);
						    $explode = explode('_', $nome_arquivo);
						    $pasta = $explode[2] . '_' . $explode[3] . '_' . $explode[4];
						
						    if (file_exists('impostos/' . $pathEmpresa .'/'. $value['usuario_analista_id'] . '/' . $pasta . '/' . $value['nome_arquivo'])) {
							    $zip->addFile('impostos/' . $pathEmpresa .'/'. $value['usuario_analista_id'] . '/' . $pasta . '/' . $value['nome_arquivo'], $value['nome_arquivo']);
							
							    $caminhoPastaEntregar = $copiarArquivosAtividadesJob . '/entregar/' . $explode[0].'_'.$explode[1].'_'.str_replace(' ','',$value['tributo_nome']).'_'.$explode[3].'_'.$explode[4];
							    if(!is_dir($caminhoPastaEntregar)){
								    @mkdir($caminhoPastaEntregar, 0777);
							    }
							    // Copiar arquivos para a pasta `entregar` para rodar o /atividades/Job
							    @copy('impostos/' . $pathEmpresa .'/'. $value['usuario_analista_id'] . '/' . $pasta . '/' . $value['nome_arquivo'], $caminhoPastaEntregar .'/'. $value['nome_arquivo']);
						    }
					    }
				    }
			    }
			    $zip->close();
			    // var_dump($zip);
		    }
		    $filetopath = $public_dir . '/' . $zipFileName;
		
		    // Disparar e-mail
		    $data = array(
			    'assunto' => 'Guias: ' . implode(', ', $input['multiple_select_uf']),
			    'descricao' => 'Seguem guias para pagamento.',
			    'anexo' => $filetopath,
			    'emails' => explode(',', $_POST['emails'])
		    );
		    $this->sendEmail(Auth::user(), $data);
		
		    // Salvar na tabela `liberaguias`
		    $liberarguias = Liberarguias::create([
			    'assunto' => 'Guias: ' . implode(', ', $input['multiple_select_uf']),
			    'emails' => $_POST['emails'],
			    'data_liberada' => new \DateTime(),
			    'usuario_id' => Auth::user()->id
		    ]);
	    }
	    
	    return view('guiaicms.icms')->withUf($uf)->withEstabelecimentos($estabelecimentos)->with('planilha', $planilha)->with('planilha_semcod', $planilha_semcod)->with('data_inicio', $data_inicio)->with('data_fim', $data_fim)->with('mensagem', $mensagem)->withestabelecimentosselected($estabelecimentosselected)->withufselected($ufselected)->with('enviar_zfic', true);
    }

    public function conferencia(Request $request)
    {
        $estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->pluck('codigo','id');
        $uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->pluck('UF','UF');
        $estabelecimentosselected = array();
        $ufselected = array();

        $input = $request->all();
        if (!empty($input)) {

            $estabelecimentosselected = array();
            if (!empty($input['multiple_select_estabelecimentos'])) {
                $estabelecimentosselected = $input['multiple_select_estabelecimentos'];
            }

            $ufselected = array();
            if (!empty($input['multiple_select_uf'])) {
                $ufselected = $input['multiple_select_uf'];
            }


            $estabelecimentos = Estabelecimento::where('empresa_id', $this->s_emp->id)->selectRaw("codigo, id")->pluck('codigo','id');
            $uf = Municipio::distinct('UF')->orderBy('UF')->selectRaw("UF, UF")->pluck('UF','UF');

            if (empty($input['inicio']) || empty($input['fim'])) {
                return redirect()->back()->with('status', 'É necessário informar as duas datas.');
            }
            $data_inicio = $input['inicio'].' 00:00:00';
            $data_fim = $input['fim'].' 23:59:59';

            $sql = "SELECT A.*, B.codigo, B.empresa_id FROM guiaicms A INNER JOIN estabelecimentos B on replace(replace(replace(A.CNPJ,'-',''),'/',''), '.', '') = B.cnpj WHERE A.DATA_VENCTO BETWEEN '".$data_inicio."' AND '".$data_fim."'";

            if (!empty($input['multiple_select_estabelecimentos'])) {
                $sql .= " AND replace(replace(replace(A.CNPJ,'-',''),'/',''), '.', '') IN (Select cnpj FROM estabelecimentos where id IN (".implode(',', $input['multiple_select_estabelecimentos'])."))";
            }

            if (!empty($input['multiple_select_uf'])) {
                $sql .= " AND A.UF IN (".implode(',', array_map(function($value){
                        return "'$value'";
                    }, $input['multiple_select_uf'])).")";
            }

            $dados = json_decode(json_encode(DB::Select($sql)),true);
            $planilha = array();
            foreach ($dados as $key => $dado) {
                if ($dado['empresa_id'] == $this->s_emp->id) {
                    $planilha[] = $dado;
                }
            }

            foreach ($planilha as $chave => $valorl) {
                if ($valorl['MULTA_MORA_INFRA'] == 0) {
                    $planilha[$chave]['MULTA_MORA_INFRA'] = '0.00';
                }

                if ($valorl['HONORARIOS_ADV'] == 0) {
                    $planilha[$chave]['HONORARIOS_ADV'] = '0.00';
                }

                if ($valorl['ACRESC_FINANC'] == 0) {
                    $planilha[$chave]['ACRESC_FINANC'] = '0.00';
                }

                if ($valorl['JUROS_MORA'] == 0) {
                    $planilha[$chave]['JUROS_MORA'] = '0.00';
                }

                if ($valorl['MULTA_PENAL_FORMAL'] == 0) {
                    $planilha[$chave]['MULTA_PENAL_FORMAL'] = '0.00';
                }
            }

            $valorData = $data_fim;
            $data_vencimento_2 = str_replace('-', '/', $valorData);
            $data_fim = date('dmY', strtotime($data_vencimento_2));

            $valorData2 = $data_inicio;
            $data_vencimento = str_replace('-', '/', $valorData2);
            $data_inicio = date('dmY', strtotime($data_vencimento));
            $mensagem = 'Período carregado com sucesso';
            if (empty($dados)) {
                $mensagem = 'Não há dados nesse período';
            }

            if (!empty($planilha)) {
                foreach ($planilha as $key => $value) {
                    $dataven = $value['DATA_VENCTO'];
                    $data_vencimento2 = str_replace('-', '/', $dataven);
                    $dataven2 = date('d/m/Y', strtotime($data_vencimento2));
                    $planilha[$key]['DATA_VENCTO'] = $dataven2;
                    $planilha[$key]['VLR_RECEITA'] = $this->maskMoeda($value['VLR_RECEITA']);
                    $planilha[$key]['JUROS_MORA'] = $this->maskMoeda($value['JUROS_MORA']);
                    $planilha[$key]['MULTA_MORA_INFRA'] = $this->maskMoeda($value['MULTA_MORA_INFRA']);
                    $planilha[$key]['ACRESC_FINANC'] = $this->maskMoeda($value['ACRESC_FINANC']);
                    $planilha[$key]['HONORARIOS_ADV'] = $this->maskMoeda($value['HONORARIOS_ADV']);
                    $planilha[$key]['MULTA_PENAL_FORMAL'] = $this->maskMoeda($value['MULTA_PENAL_FORMAL']);
                    $planilha[$key]['VLR_TOTAL'] = $this->maskMoeda($value['VLR_TOTAL']);
                }
            }

            return view('guiaicms.conferencia')->withUf($uf)->withEstabelecimentos($estabelecimentos)->with('planilha', $planilha)->with('data_inicio', $data_inicio)->with('data_fim', $data_fim)->with('mensagem', $mensagem)->withestabelecimentosselected($estabelecimentosselected)->withufselected($ufselected);
        }

        return view('guiaicms.conferencia')->withEstabelecimentos($estabelecimentos)->withUf($uf)->withestabelecimentosselected($estabelecimentosselected)->withufselected($estabelecimentosselected);
    }

    private function maskMoeda($valor)
    {
        $string = '';
        if (!empty($valor)) {
            $string = number_format($valor,2,",",".");
        }

        return $string;
    }

    public function jobAtividades()
    {
		$this->processedFiles = array();
		
        $a = explode('/', $_SERVER['SCRIPT_FILENAME']);
        $path = '';

        $funcao = '';
        if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
            $path = $a[0];
        }
        $path .= '/storagebravobpo/';
        $arquivos = scandir($path);

        $data = array();
        foreach ($arquivos as $k => $v) {
            if (strpbrk($v, '0123456789１２３４５６７８９０')) {
                $path_name = $path.$v.'/';
                $data[$k]['arquivos'][1][1] = scandir($path_name.'/entregar');
                $data[$k]['arquivos'][1][2]['path'] = $path_name.'entregar/';
            }
        }

        CriticasEntrega::NoDuplicity();
        foreach ($data as $X => $FILENAME) {
            foreach ($FILENAME as $L => $pastas) {
                foreach ($pastas as $key => $arquivos) {
                    if (is_array($arquivos[1])) {
                        foreach ($arquivos[1] as $A => $arquivo) {
                            if (strlen($arquivo) > 2) {
                                $arrayNameFile = explode("_", $arquivo);
                                if (empty($arrayNameFile[2])) {
                                    continue;
                                }
                                $files[] = $arquivos[2]['path'].$arquivo;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($files)) {
            $this->savefiles($files);
        } else {
            echo "Não foram encontrados arquivos para realizar o processo.";exit;
        }

        // $cmd = 'D:\wamp64\bin\php\php5.6.40\php.exe D:\wamp64\www\agenda\public\Background\UploadMails.php';
        if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
            $cmd = $a[0].'\wamp64\bin\php\php5.6.40\php.exe '.$a[0].'\wamp64\www\agenda\public\Background\UploadMails.php';
        }

        if (substr(php_uname(), 0, 7) == "Windows"){
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            exec($cmd . " > /dev/null &");
        }
        $this->clearEmptyPaths($files);
        echo "Job foi rodado com sucesso.";exit;
    }
    private function clearEmptyPaths($paths)
    {
        $clear = array();
        if (!empty($paths)) {
            foreach ($paths as $k => $path) {
                if (is_dir($path) && !is_file($path)) {
                    $a = scandir($path);
                    if (count($a) == 2) {
                        $clear[] = $path;
                    }
                }
            }
        }

        if (!empty($clear)) {
            foreach ($clear as $key => $valuetoclear) {
                @rmdir($valuetoclear);
            }
        }
    }

    private function savefiles($paths){
        $this->logName = "log_".date('d-m-Y-H-i-s');
        Log::useFiles(storage_path().'/logs/atividade_upload/'.$this->logName.'.log');

        $arr = array();

        foreach ($paths as $K => $pathname) {
            try {
                $arquivo = explode('/', $pathname);
                $fileexploded = end($arquivo);

                $empresaraiz = explode('_', $arquivo[2]);
                $empresacnpjini = $empresaraiz[1];

                $empresaraizid = 0;
                $empresaRaizBusca = DB::select('select id from empresas where LEFT(cnpj, 8)= "'.$empresacnpjini.'"');
                if (!empty($empresaRaizBusca[0]->id)) {
                    $empresaraizid = $empresaRaizBusca[0]->id;
                }

                $arrayExplode = explode("_", $fileexploded);

                $AtividadeID = 0;
                if (!empty($arrayExplode[0]))
                    $AtividadeID = $arrayExplode[0];

                $CodigoEstabelecimento = 0;
                if (!empty($arrayExplode[1]))
                    $CodigoEstabelecimento = $arrayExplode[1];

                $NomeTributo = '';
                if (!empty($arrayExplode[2]))
                    $NomeTributo = $arrayExplode[2];
                if ($empresaraizid == 7) {
                    $NomeTributo = $this->letras($NomeTributo);
                }

                $PeriodoApuracao = '';
                if (!empty($arrayExplode[3]))
                    $PeriodoApuracao = $arrayExplode[3];

                $UF = '';
                if (!empty($arrayExplode[4]))
                    $UF = substr($arrayExplode[4], 0, 2);
                $estemp_id = 0;
                $arrayEstempId = DB::select('select id FROM estabelecimentos where codigo = "'.$CodigoEstabelecimento.'" and ativo = 1 and empresa_id ='.$empresaraizid.'');
                if (!empty($arrayEstempId[0]->id)) {
                    $estemp_id = $arrayEstempId[0]->id;
                }

                if(
                    $AtividadeID === NULL || $CodigoEstabelecimento === NULL || $NomeTributo === NULL || $PeriodoApuracao === NULL || $UF === NULL ||
                    $AtividadeID === 0 || $CodigoEstabelecimento === 0 || $NomeTributo === '' || $PeriodoApuracao === '' || $UF === '') {
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, NULL, $fileexploded, 'Erro na nomeclatura da pasta. ', 'N');
                    continue;
                }

                // if (!$this->validatePasta($AtividadeID, $CodigoEstabelecimento, $NomeTributo, $PeriodoApuracao, $UF)) {
                //     $this->createCriticaEntrega($empresaraizid, $estemp_id, 8, $fileexploded, 'Nome do arquivo invalido', 'N');
                //     continue;
                // }
	            
                $NomeTributo = $this->LoadNomeTributo($NomeTributo);
                if (!$this->checkTributo($NomeTributo)) {
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, 8, $fileexploded, 'Tributo não existente', 'N');
                    continue;
                }

                $IdTributo = $this->loadTributo($NomeTributo);
                if(!is_numeric($AtividadeID)){
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Erro ao tentar extrair ID da atividade no nome da pasta/arquivo. ', 'N');
                    continue;
                }
                $validateAtividade = DB::select("Select COUNT(1) as countAtividade FROM atividades where id = ".$AtividadeID);
                if (empty($AtividadeID) || !$validateAtividade[0]->countAtividade) {
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Código de atividade não existe', 'N');
                    continue;
                }

                if (!$this->checkTribAtividade($AtividadeID, $IdTributo)) {
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Tributo divergente do tributo da atividade', 'N');
                    continue;
                }
	            
                $validateCodigo = DB::select("Select COUNT(1) as countCodigo FROM atividades where id = ".$AtividadeID. " AND estemp_id = ".$estemp_id);
                if (!$estemp_id || !$validateCodigo[0]->countCodigo) {
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Filial divergente com a filial da atividade', 'N');
                    continue;
                }

                if (strlen($PeriodoApuracao) == 10) {
                    $PeriodoApuracao = substr($PeriodoApuracao, 0, -4);
                }
                $validatePeriodoApuracao = DB::select("Select COUNT(1) as countPeriodoApuracao FROM atividades where id = ".$AtividadeID. " AND periodo_apuracao = ".$PeriodoApuracao."");
                if (empty($PeriodoApuracao) || !$validatePeriodoApuracao[0]->countPeriodoApuracao) {
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Período de apuração diferente do período da atividade', 'N');
                    continue;
                }

                if (count($arrayExplode) >= 4) {
                    $validateUF = DB::select("select count(1) as countUF FROM municipios where codigo = (select cod_municipio from estabelecimentos where id = ".$estemp_id.") AND uf = '".$UF."'");
                    if (empty($UF) || !$validateUF[0]->countUF) {
                        $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'UF divergente da UF da filial da atividade', 'N');
                        continue;
                    }
                }

                if (!$this->checkSubPath($pathname)) {
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Erro existe subpasta, eliminar a subpasta para a entrega', 'N');
                    continue;
                }

                $return = $this->validateGeral($pathname, $AtividadeID);
                if (!is_numeric($return)) {
                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Está faltando o arquivo com extensão '.$return, 'N');
                    continue;
                }

                $unchecked = true;
				$parseFile = true;
				$atividade = Atividade::findOrFail($AtividadeID);
	            
                if ($IdTributo === 1 || $IdTributo === 8 || $IdTributo === 18 || $IdTributo === 28 || $IdTributo === 32) {
                    if (!$this->validateGeral($pathname, $AtividadeID, false, false, true)) {
						if($atividade->status === 3) {
							$this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'A atividade ('.$atividade->id.') já foi entregue anteriormente. ', 'N');
							continue;							
						}
	                    
                        $existsTXT = $this->validateGeral($pathname, $AtividadeID, true);
                        if ($existsTXT && $IdTributo === 1) {
                            $checkTXTvalue_read = $this->checkTXTvalue($pathname, $AtividadeID);
                            if ($checkTXTvalue_read == 'error-read') {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Contém arquivos TXT que não atendem o lay-out de leitura.', 'N');
                                continue;
                            }

                            if ($checkTXTvalue_read == 'error-signature' && $IdTributo == 1) {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Arquivo Sped TXT sem assinatura.', 'N');
                                continue;
                            }

                            $checkTXTvalue = $this->checkTXTvalue($pathname, $AtividadeID);
                            if (!is_numeric($checkTXTvalue)) {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'CNPJ do TXT '.$checkTXTvalue.' não confere com CNPJ da filial da atividade.', 'N');
                                continue;
                            }

                            $checkTXTvalue_2 = $this->checkTXTvalue($pathname, $AtividadeID, true);
                            if (!is_numeric($checkTXTvalue_2)) {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'PERÍODO do TXT '.$checkTXTvalue_2.' não confere com Período da atividade.', 'N');
                                continue;
                            }
                        }

                        $existsPDF = $this->validateGeral($pathname, $AtividadeID, false, true);
	                    
                        if ($existsPDF) {
                            $checkPDFvalue_read = $this->checkPDFvalue($pathname, $AtividadeID);
	                        
                            if ($checkPDFvalue_read == 'error-repeated') {
								$parseFile = false;
								$this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'O arquivo ('.$fileexploded.") já foi processado na mesma listagem anteriormente. ", 'N');
                                continue;
                            }

                            if ($checkPDFvalue_read == 'error-txt') {
                                continue;
                            }

                            if ($checkPDFvalue_read == 'error-uf') {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Não foi possível encontrar a UF no arquivo : '.$fileexploded, 'N');
                                continue;
                            }

                            if ($checkPDFvalue_read == 'error-space') {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Elimine o espaço no nome do arquivo ou no nome da pasta : '.$fileexploded, 'N');
                                continue;
                            }

                            if ($checkPDFvalue_read == 'error-read') {
                                $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Erro do plugin PdfToTxt ao gerar o .TXT. '.$fileexploded, 'N');
                                continue;
                            }

                            if ($IdTributo == 1) {
                                $checkPDFRecibos = $this->hasRecibo($pathname);
                                if ($checkPDFRecibos == 'error') {
                                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Recibo PDF não encontrado', 'N');
                                    continue;
                                }

                                if ($checkPDFRecibos == 'error-multiple') {
                                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Só é permitido ter um Recibo', 'N');
                                    continue;
                                }
                            }

                            if ($checkPDFvalue_read == 'error-read') {
                                /*$this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Não foi possível ler o arquivo '.$fileexploded, 'N');
                                continue;*/
                            }

                            if ($checkPDFvalue_read == 'error-read-guia') {
                                /*$this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Não foi possível ler a guia '.$fileexploded, 'N');
                                continue;*/
                            }
	                        
                            $checkPDFvalue = $this->checkPDFvalue($pathname, $AtividadeID, false, false, false, true);
	                        
                            if (is_numeric($checkPDFvalue)) {
                                //$this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Aprovação: Erro leitura no layout.', 'N');
                                //continue;
                                $checkPDFvalue = $this->checkPDFvalue($pathname, $AtividadeID);
                                if (!is_numeric($checkPDFvalue)) {
                                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Aprovação: Existe mais de um arquivo PDF, não é possível identificar qual dos arquivos é o recibo.', 'N');
                                    continue;
                                }
                                $checkPDFvalue_2 = $this->checkPDFvalue($pathname, $AtividadeID, true);
                                if (!is_numeric($checkPDFvalue_2)) {
                                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'CNPJ|IE do arquivo '.$fileexploded.' não confere com CNPJ|IE da filial da atividade.', 'N');
                                    continue;
                                }
                                $checkPDFvalue_3 = $this->checkPDFvalue($pathname, $AtividadeID, false, true);
	                              if (!is_numeric($checkPDFvalue_3) && $NomeTributo != 'ANTECIPADO' && !in_array($UF, ['CE','ST','MG'])) {
                                    $this->createCriticaEntrega($empresaraizid, $estemp_id, $IdTributo, $fileexploded, 'Período do '.$fileexploded.' não confere com o período da atividade.', 'N');
                                    continue;
                                }
                                $unchecked = false;
                                Log::info('Aprovado: '.$fileexploded. PHP_EOL );
                                $this->checkPDFvalue($pathname, $AtividadeID, false, false, true);
                            } else {
                                Log::info('Layout Reprovado: '.$fileexploded. PHP_EOL);
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $this->createCriticaEntrega(
                /*Empresa Id*/ null ,
                    /*Estabelecimento Id*/ null ,
                    /*Id Tributo */ null,
                    $fileexploded,
                    substr("Erro genérico: ". $e->getMessage(),0,199),
                    'N');
                continue;
            }
			if($parseFile === true ) {
				$arr[$AtividadeID]["unchecked"] = $unchecked;
				$arr[$AtividadeID]['files'][$K]['filename'] = $fileexploded;
				$arr[$AtividadeID]['files'][$K]['path'] = $pathname;
				$arr[$AtividadeID]['files'][$K]['atividade'] = $AtividadeID;	
			}
        }

        if (!empty($arr)) {
            foreach ($arr as $k => $singlearray) {
                $path = $k.'.zip';
                $this->createZipFile($singlearray['files'], $path, $singlearray['unchecked']);
            }
        }
    }

    private function hasRecibo($file)
    {
        if (is_dir($file)) {
            $formated = array();
            $files = scandir($file);
            $counter = 0;

            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    if (!empty($exp[1]) && strtolower($exp[1]) == 'pdf') {
                        $formated[$counter]['path'] = $file.'/'.$k;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }

            if (empty($formated)) {
                return 'error';
            }

            if (count($formated) > 1) {
                return 'error-multiple';
            }

            if (!$this->checkPath($formated[0]['path'])) {
                if (!$this->checkPathSpace($formated[0]['path'])) {
                    return 'error-space';
                }
            } else {
                return 'error-read';
            }


            if (!empty($formated)) {
                foreach ($formated as $x => $files) {

                    if (!$this->isRecibo($files['path'])) {
                        unset($formated[$x]);
                    }
                }
            }
        }

        if (!is_dir($file)) {
            $formated = array();
            $files = $this->getFilesByAtividadeId($id, $file);
            $counter = 0;
            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    if (strtolower($exp[1]) == 'pdf') {
                        $formated[$counter]['path'] = $file;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }

            $atividade = Atividade::findOrFail($id);
            if (!$this->checkPath($formated[0]['path'])) {
                if (!$this->checkPathSpace($formated[0]['path'])) {
                    return 'error-space';
                }
            } else {
                return 'error-read';
            }

            if (!empty($formated)) {
                foreach ($formated as $x => $files) {
                    if (!$this->isRecibo($files['path'])) {
                        unset($formated[$x]);
                    }
                }
            }
        }

        return 'not-error';
    }

    private function checkSubPath($file)
    {
        if (!is_dir($file)) {
            return true;
        }

        $scandir = scandir($file);
        foreach ($scandir as $x => $filename) {
            if (strlen($filename) > 2) {
                if (!is_dir($file.'/'.$filename)) {
                    continue;
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    private function checkTXTvalue($file, $id, $periodo = false)
    {
        if (is_dir($file)) {
            $formated = array();
            $files = scandir($file);
            $counter = 0;
            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    if (count($exp) > 1  && strtolower($exp[1]) == 'txt') {
                        $formated[$counter]['path'] = $file.'/'.$k;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }
        }

        if (!is_dir($file)) {
            $formated = array();
            $files = $this->getFilesByAtividadeId($id, $file);
            $counter = 0;
            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    if (count($exp) > 1  && strtolower($exp[1]) == 'txt') {
                        $formated[$counter]['path'] = $file;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }
        }

        $atividade = Atividade::findOrFail($id);
        if (!empty($formated)) {
            foreach ($formated as $single_key => $single_formated) {
                $handle = fopen($single_formated['path'], "r");
                if (filesize($single_formated['path']) == 0) {
                    return 'error-read';
                }

                $contents = fread($handle, filesize($single_formated['path']));
                if (empty($contents)) {
                    return 'error-read';
                }

                $exploded_rows = explode("\n", utf8_encode($contents));
                if (count($exploded_rows) < 10) {
                    return 'error-read';
                }

                //debug1
                //echo "<Pre>";
                //print_r($exploded_rows);exit;

                if ($atividade->regra->tributo->id == 1) {

                    //Modelo 1 - TXT
                    $exploded_column = explode("|", $exploded_rows[0]);
                    if (count($exploded_column) > 1) {
                        if ($periodo) {
                            if (substr($exploded_column[5], -6) != $this->numero($atividade->periodo_apuracao)) {
                                return $single_formated['file'];
                            }
                        } else {
                            if ($exploded_column[7] != $atividade->estemp->cnpj) {
                                return $single_formated['file'];
                            }
                        }
                    }

                    //Modelo 2 - TXT
                    if (isset($exploded_rows[12]) && substr($exploded_rows[12], 0,8) == 'CNPJ/CPF') {
                        $exp_1 = explode(' ', $exploded_rows[12]);
                        $cnpj_v = $this->numero($exp_1[1]);
                        $exp_2 = explode(' ', $exploded_rows[18]);
                        $periodo_v = substr($exp_2[1], 3);
                        if ($periodo) {
                            if ($this->numero($periodo_v) != $this->numero($atividade->periodo_apuracao)) {
                                return $single_formated['file'];
                            }
                        } else {
                            if ($cnpj_v != $atividade->estemp->cnpj) {
                                return $single_formated['file'];
                            }
                        }
                    }

                    //Modelo 3 - TXT
                    if (isset($exploded_rows[10]) && substr($exploded_rows[10], 0,8) == 'CNPJ/CPF') {
                        $exp_1 = explode(' ', $exploded_rows[10]);
                        $cnpj_v = $this->numero($exp_1[1]);
                        $exp_2 = explode(' ', $exploded_rows[16]);
                        $periodo_v = substr($exp_2[1], 3);
                        if ($periodo) {
                            if ($this->numero($periodo_v) != $this->numero($atividade->periodo_apuracao)) {
                                return $single_formated['file'];
                            }
                        } else {
                            if ($cnpj_v != $atividade->estemp->cnpj) {
                                return $single_formated['file'];
                            }
                        }
                    }
                }
                fclose($handle);
            }
        }

        return '1';

    }

    private function checkPDFvalue($file, $id, $cnpj = false, $periodo = false, $save = false, $erroleitura = false)
    {
        if (is_dir($file)) {
            $formated = array();
            $files = scandir($file);
            $counter = 0;
            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    if (isset($exp[1]) && strtolower($exp[1]) == 'pdf') {
                        $formated[$counter]['path'] = $file.'/'.$k;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }

            $atividade = Atividade::findOrFail($id);
			
            foreach ($formated as $indexing => $formm) {
                $explode = explode('_', $formm['file']);
			
				if(in_array($formm['file'], $this->processedFiles)) {
					return 'error-repeated';
				}

				$this->emptyAtividade($atividade);

                if (!$this->checkPathSpace($formm['path'])) {
                    return 'error-space';
                }

                if($this->checkPath($formm['path']) ) {
                    if ($this->isRecibo($formm['path'])) {

                        $pdf = $this->readRecibo($formm['path'], $save, $id, true);
                        if (!$pdf) {
                            return 'error-read';
                        }
                    } else {
                        $pdf = $this->readRecibo($formm['path'], $save, $id);

                        if (!$pdf) {
                            return 'error-read-guia';
                        }
                    }
                } else {
                    return 'error-read';
                }

                if($erroleitura) {
                    if (!$this->array_keys_exists($pdf, $atividade->regra->tributo->id)) {
                        return 'error-read';
                    }
                }

                if ($atividade->regra->tributo->id == 1) {
                    if ($periodo) {
                        if ($pdf['REFERENCIA'] != $atividade->periodo_apuracao) {
                            return 'error';
                        }
                    }

                    if ($cnpj) {
                        if ($pdf['CNPJ'] != $atividade->estemp->cnpj) {
                            return 'error';
                        }
                    }

                    if (count($formated) > 1) {
                        return 'error';
                    }
                } else if($atividade->regra->tributo->id == 8 || $atividade->regra->tributo->id == 18 || $atividade->regra->tributo->id == 28 || $atividade->regra->tributo->id == 32) {
                    if ($periodo) {
                        $referencia = str_replace('/', '', $pdf['REFERENCIA']);
                        if ($referencia != $atividade->periodo_apuracao) {
	                        if(! strlen($pdf['REFERENCIA']) > 7){
		                        $referencia = str_replace('/', '', $pdf['REFERENCIA']);
		                        if ($referencia != $atividade->periodo_apuracao) {
			                        return 'error';
		                        }
	                        }
                        }
                    }

                    if ($cnpj) {
                        if (isset($pdf['CNPJ']) && !empty($pdf['CNPJ'])) {
                            if (preg_replace('/[^0-9]/', '', $pdf['CNPJ'])
                                != preg_replace('/[^0-9]/', '', $atividade->estemp->cnpj)) {
                                return 'error';
                            }
                        }

                        if (isset($pdf['IE']) && !empty($pdf['IE']) && empty($pdf['CNPJ'])) {
                            if (preg_replace('/[^0-9]/', '', $pdf['IE'])
                                != preg_replace('/[^0-9]/', '', $atividade->estemp->insc_estadual)) {
                                return 'error';
                            }
                        }
                    }
                }
            }
        }

        if (!is_dir($file)) {
            if(substr($file, -3,3) == 'txt' && !file_exists($file)) {
                return 'error-txt';
            }

            $formated = array();
            $files = $this->getFilesByAtividadeId($id, $file);
            $counter = 0;

            foreach ($files as $x => $k) {
                if (strlen($k) > 2) {
                    $exp = explode('.',$k);
                    $test = explode('/', $k);
                    if (count($k) > 0) {
                        foreach ($test as $p => $l) {
                        }
                        $k = $l;
                    }
                    if (isset($exp[1]) && strtolower($exp[1]) == 'pdf') {
                        $formated[$counter]['path'] = $file;
                        $formated[$counter]['file'] = $k;
                        $counter++;
                    }
                }
            }
            $atividade = Atividade::findOrFail($id);
            $UF = '';
            $explode = explode('_', $formated[0]['file']);

			if(in_array($formated[0]['file'], $this->processedFiles)) {
				return 'error-repeated';
			}
			
			$this->emptyAtividade($atividade);

            if (!empty($explode[4]))
                $UF = substr($explode[4], 0, 2);

            $validateUF = DB::select("select count(1) as countUF FROM municipios WHERE uf = '".$UF."'");
            if (!$this->isRecibo($formated[0]['path'])) {
                if (empty($UF) || !$validateUF[0]->countUF) {
                    return 'error-uf';
                }
            }

            if (!$this->checkPathSpace($formated[0]['path'])) {
                return 'error-space';
            }

            if($this->checkPath($formated[0]['path']) ) {
                if ($this->isRecibo($formated[0]['path'])) {
                    $pdf = $this->readRecibo($formated[0]['path'], $save, $id, true);
                    if (!$pdf && $atividade->regra->tributo->id == 1) {
                        return 'error-read';
                    }
                } else {
                    $pdf = $this->readRecibo($formated[0]['path'], $save, $id);
                    if (!$pdf) {
                        return 'error-read-guia';
                    }
                }
            } else {
                return 'error-read';
            }

            if($erroleitura) {
                if (!$this->array_keys_exists($pdf, $atividade->regra->tributo->id)) {
                    return 'error-read';
                }
            }

            if ($atividade->regra->tributo->id == 1) {
                if ($periodo) {
                    if ($pdf['REFERENCIA'] != $atividade->periodo_apuracao) {
                        return 'error';
                    }
                }

                if ($cnpj) {
                    if ($pdf['CNPJ'] != $atividade->estemp->cnpj) {
                        return 'error';
                    }

                }

                if (count($formated) > 1) {
                    return 'error';
                }
            } else if ($atividade->regra->tributo->id == 8 || $atividade->regra->tributo->id == 18 || $atividade->regra->tributo->id == 28 || $atividade->regra->tributo->id == 32) {
                if ($periodo) {
                    $referencia = str_replace(['/',' '], '', $pdf['REFERENCIA']);
                    if ($referencia != $atividade->periodo_apuracao) {
                        return 'error';
                    }
                }

                if ($cnpj) {
                    if (isset($pdf['CNPJ']) && !empty($pdf['CNPJ'])) {
                        if (preg_replace('/[^0-9]/', '', $pdf['CNPJ'])
                            != preg_replace('/[^0-9]/', '', $atividade->estemp->cnpj)) {
                            return 'error';
                        }
                    }

                    if (isset($pdf['IE']) && !empty($pdf['IE'])) {
                        if (preg_replace('/[^0-9]/', '', $pdf['IE'])
                            != preg_replace('/[^0-9]/', '', $atividade->estemp->insc_estadual)) {
                            return 'error';
                        }
                    }
                }
            }
        }
        return '1';
    }

    private function checkPath($path)
    {
        $dados = array();
        $funcao = 'pdftotext.exe';
        $filetxt = str_replace(['.pdf', '.PDF'], '_CONVERTIDO.txt', $path);
        $caminho1 = explode('/', $filetxt);
        $caminho1_result = '';
        foreach ($caminho1 as $key => $value) {
            $arquivonome = $value;
            $key++;
            if (isset($caminho1[$key])) {
                $caminho1_result .= $value.'/';
            }
        }

        $pasta = $caminho1_result;
        $caminho1_result = $caminho1_result.$arquivonome;

        $comando =  $funcao.' '.$path.' '.$caminho1_result; //echo $comando .' salsicha1'; exit; // salsicha

        if ($this->checkPathSpace($path)) {
            $A = shell_exec($comando);
            if (!is_file($caminho1_result)) {
                return false;
            }
            return true;
        }

        return false;
    }

    private function isRecibo($path)
    {
        $dados = array();
        $funcao = 'pdftotext.exe';
        $filetxt = str_replace(['.pdf', '.PDF'], '_CONVERTIDO.txt', $path);

        $caminho1 = explode('/', $filetxt);
        $caminho1_result = '';
        foreach ($caminho1 as $key => $value) {
            $arquivonome = $value;
            $key++;
            if (isset($caminho1[$key])) {
                $caminho1_result .= $value.'/';
            }
        }

        $pasta = $caminho1_result;
        $caminho1_result = $caminho1_result.$arquivonome;

        $comando =  $funcao.' '.$path.' '.$caminho1_result;  //echo $comando .' salsicha2'; exit; // salsicha
        if ($this->checkPathSpace($path)) {

            $A = shell_exec($comando);

            $arr = array();
            $arr['arquivotxt'] = $arquivonome;
            $arr['pathtxt'] = $caminho1_result;
            $arr['arquivo'] = str_replace('txt', 'pdf', $arquivonome);
            $dados = $this->loadRecibo($arr);
            if (file_exists($caminho1_result)) {
                @unlink($caminho1_result);
            }

            if (!empty($dados) && isset($dados['vlr_recibo_1'])) {
                return true;
            }
        }
        return false;
    }

    private function checkPathSpace($name)
    {
        $a = explode('/', $name);
        foreach ($a as $x => $namefile) {}

        $p = explode(' ', $namefile);
        if (count($p) > 1) {
            return false;
        }
        return true;
    }

    private function readRecibo($path, $save = false, $idAtividade = false, $isRecibo = false)
    {
        $dados = array();
        $funcao = 'pdftotext.exe';
        $filetxt = str_replace(['.pdf', '.PDF'], '_CONVERTIDO.txt', $path);
        $caminho1 = explode('/', $filetxt);
        $caminho1_result = '';
        foreach ($caminho1 as $key => $value) {
            $arquivonome = $value;
            $key++;
            if (isset($caminho1[$key])) {
                $caminho1_result .= $value.'/';
            }
        }
        $caminho1_result = $caminho1_result.$arquivonome;
        $comando = $funcao.' '.$path.' '.$caminho1_result;  //echo $comando .' salsicha3'; exit; // salsicha
        $A = shell_exec($comando);

        $arr = array();
        $arr['arquivotxt'] = $arquivonome;
        $arr['pathtxt'] = $caminho1_result;
        $arr['arquivo'] = str_replace('txt', 'pdf', $arquivonome);
	    
        $fill = array();
        $atividade = Atividade::FindOrFail($idAtividade);
        if ($isRecibo) {
            $dados = $this->loadRecibo($arr);
        } else {
            $dados = $this->loadGuia($arr);
        }

        if ($save) {
            $atividade->status = 3;
            $atividade->usuario_aprovador = 112;
            $atividade->data_aprovacao = date('Y-m-d H:i:s');

            if ($isRecibo) {
                $atividade->vlr_recibo_1 = $dados['vlr_recibo_1'];
                $atividade->vlr_recibo_2 = $dados['vlr_recibo_2'];
                $atividade->vlr_recibo_3 = $dados['vlr_recibo_3'];
                $atividade->vlr_recibo_4 = $dados['vlr_recibo_4'];
                $atividade->vlr_recibo_5 = $dados['vlr_recibo_5'];
            } else {
                $exploded = explode('_', $arr['arquivo']);
                $exploded[2] = $this->letras($exploded[2]);

                // if (strtoupper($exploded[2]) == 'ICMS' || strtoupper($exploded[2]) == 'DIFAL') {
                //     $atividade->vlr_recibo_1 += $dados['VLR_TOTAL'];
                // }
				
                if (strtoupper($exploded[2]) == 'ICMS' || strtoupper($exploded[2]) == 'FUNTUR') {
                    $atividade->vlr_recibo_1 += $dados['VLR_TOTAL'];
                }
				
				if (strtoupper($exploded[2]) == 'DIFAL') {
                    $atividade->vlr_recibo_4 += $dados['VLR_TOTAL'];
                }

                if (strtoupper($exploded[2]) == 'ICMSST' || strtoupper($exploded[2]) == 'ANTECIPADO') {
                    $atividade->vlr_recibo_2 += $dados['VLR_TOTAL'];
                }

                if (strtoupper($exploded[2]) == 'TAXA' || strtoupper($exploded[2]) == 'PROTEGE' || strtoupper($exploded[2]) == 'FECP' || strtoupper($exploded[2]) == 'FEEF' || strtoupper($exploded[2]) == 'UNIVERSIDADE' || strtoupper($exploded[2]) == 'FITUR') {
                    $atividade->vlr_recibo_3 += $dados['VLR_TOTAL'];
                }
            }

            $atividade->save();
			$this->processedFiles[] = str_replace("_CONVERTIDO", "", $arr['arquivo']);

        }

        if (file_exists($arr['pathtxt'])) {
            @unlink($arr['pathtxt']);
        }
        return $dados;
    }
	
	private function emptyAtividade($atividade) {
		$atividade->vlr_recibo_1 = 0;
		$atividade->vlr_recibo_2 = 0;
		$atividade->vlr_recibo_3 = 0;
		$atividade->vlr_recibo_4 = 0;
		$atividade->vlr_recibo_5 = 0;
		
		return $atividade->save();
	}

    private function loadGuia($arr)
    {
        $dados = array();
        $fields = explode('_', $arr['arquivo']);
        if(!isset($fields[4])) return null;
        $uf = substr($fields[4], 0,2);
	
	    if(!in_array($uf, ['RO','AC','AM','RR','PA','AP','TO','MA','PI','CE','RN','PB','PE','AL','SE','BA','MG','ES','RJ','SP','PR','SC','RS','MS','MT','GO','DF'])){
		    $fields = explode('_', str_replace(['/','\/'],'_',$arr['pathtxt']));
		    foreach($fields as $field){
			    if(in_array($field, ['RO','AC','AM','RR','PA','AP','TO','MA','PI','CE','RN','PB','PE','AL','SE','BA','MG','ES','RJ','SP','PR','SC','RS','MS','MT','GO','DF'])){
				    $uf = $field;
				    // substitui a variavel $arr['arquivo'] para o nome da pasta,
				    // para obedecer o padrão IDatividades_IDestabelecimentos_TRIBUTO_PERIODO_UF
				    $arquivo = explode('/',$arr['pathtxt']);
				    $arr['arquivo'] = empty($arquivo[count($arquivo)-2])? '' : $arquivo[count($arquivo)-2];
				    break;
			    }
		    }
	    }
	
	    $arquivo = explode('_', $arr['arquivo']);
	    if(!is_numeric($arquivo[0]) || $uf == 'CO'){
		    $arquivo = explode('/',$arr['pathtxt']);
		    $arquivo = empty($arquivo[count($arquivo)-2])? '' : $arquivo[count($arquivo)-2];
		    $arquivo = explode('_', $arquivo);
		    $arr['arquivo'] = $arquivo[0] .'_'. $arr['arquivo'];
	    }

        if ($uf == 'SP') {
            $icmsarray = $this->icmsSP($arr);
        }

        if ($uf == 'RJ') {
            $icmsarray = $this->icmsRJ($arr);
        }

        if ($uf == 'RS') {
            $icmsarray = $this->icmsRS($arr);
        }

        if ($uf == 'AL') {
            $icmsarray = $this->icmsAL($arr);
        }

        if ($uf == 'DF') {
            $icmsarray = $this->icmsDF($arr);
        }

        if ($uf == 'PA') {
            $icmsarray = $this->icmsPA($arr);
        }

        if ($uf == 'GO') {
            $icmsarray = $this->icmsGO($arr);
        }

        if ($uf == 'ES') {
            $icmsarray = $this->icmsES($arr);
        }

        if ($uf == 'PB') {
            $icmsarray = $this->icmsPB($arr);
        }

        if ($uf == 'SE') {
            $icmsarray = $this->icmsSE($arr);
        }

        if ($uf == 'BA') {
            $icmsarray = $this->icmsBA($arr);
        }

        if ($uf == 'RN') {
            $icmsarray = $this->icmsRN($arr);
        }

        if ($uf == 'PE') {
            $icmsarray = $this->icmsPE($arr);
        }

        if ($uf == 'MA') {
            $icmsarray = $this->icmsMA($arr);
        }

        if ($uf == 'MG') {
            $icmsarray = $this->icmsMG($arr);
        }

        if ($uf == 'CE') {
            $icmsarray = $this->icmsCE($arr);
        }

        if ($uf == 'PI') {
            $icmsarray = $this->icmsPI($arr);
        }

        if ($uf == 'PR') {
            $icmsarray = $this->icmsPR($arr);
        }

        if ($uf == 'MS') {
            $icmsarray = $this->icmsMS($arr);
        }
        if ($uf == 'MT') {
            $icmsarray = $this->icmsMT($arr);
        }
		if ($uf == 'SC') {
            $icmsarray = $this->icmsSC($arr);
        }
	
		    if ($uf == 'AM') {
			    $icmsarray = $this->icmsAM($arr);
		    }
	    
        if (isset($icmsarray[0])) {
            $dados = $icmsarray[0];
        }

        if (file_exists($arr['pathtxt'])) {
            @unlink($arr['pathtxt']);
        }

        return $dados;
    }

    private function sendMailError($error, $file)
    {
        $query = "select id FROM users where id IN (select user_id FROM role_user where role_id = 2 )";
        $admins = DB::select($query);

        $subject = "Erro Upload – ".date('d/m/Y h:i:s');
        $text = 'O arquivo '.$file.' gerou o erro abaixo, que impossibilitou a continuação do processamento do upload de arquivos no horário agendado. <hr /> <hr /> '.$error;

        $data = array('subject'=>$subject,'messageLines'=>$text);

        set_time_limit(0);
        if (!empty($admins)) {
            foreach($admins as $row) {
                $user = User::findOrFail($row->id);
                //$this->eService->sendMail($user, $data, 'emails.notificacao-erro-upload', false);
            }
        }
    }

    private function loadRecibo($arr, $delete = false)
    {
        $dados = array();
        try {
            $handle = fopen($arr['pathtxt'], "r");
            $contents = fread($handle, filesize($arr['pathtxt']));
        } catch (\Exception $e) {
            //$this->sendMailError($e->getMessage(), $arr['arquivo']);
            echo $e->getMessage();
            exit();
        }

        $str = 'foo '.$contents.' bar';
        $str = utf8_encode($str);
        $str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
        $str = strtolower($str);

        preg_match('~cnpj/cpf:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(" ", trim($match[1]));
            $cn = trim($this->numero($i[0]));
            if (!empty($cn)) {
                $dados['CNPJ'] = $cn;
            }
        }

        preg_match('~periodo:([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode(" ", trim($match[1]));
            $a = explode("\n", trim($i[0]));
            $dados['REFERENCIA'] = substr($this->numero($a[0]), -6);
        }
	
        preg_match('~periodo de apuracao valor total dos debitos por saidas e prestacoes com debito do imposto valor total dos creditos por entradas e aquisicoes com credito do imposto valor total do icms a recolher valor total do saldo credor a transportar para o periodo seguinte valor recolhidos ou a recolher, extra-apuracao
([^{]*)~i', $str, $match);
        if (!empty($match)) {
            $i = explode("\n", trim($match[1]));

            //Modelo PDF 1
            $a = explode(" ", trim($i[0]));
            $a1 = explode(" ", trim($i[1]));
            $a2 = explode(" ", trim($i[2]));
			// TODO: remove here 22/09/2020
			// var_dump($match, $i, $a, $a1, $a2, count($a), count($a1), count($a2)); exit;
					
            //modelo PDF 1
            if($a1[0] == "r$" && $a2[0] == "r$" && count($a) == 5 && count($a1) == 4 && count($a2) == 4) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a2[1]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a2[3]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a2[1]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a2[3]));
            } else if (count($a) == 5 && count($a2) == 5) {//modelo PDF 6
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a2[1]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a2[2]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a2[1]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a2[2]));
                if (isset($fill['vlr_recibo_5']) && $fill['vlr_recibo_5'] == 'r$') {
                    $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a2[3]));
                }
            } else if (count($a) == 5 && count($a1) == 6  && count($a2) == 2) {
				// ajuste Cinepolis 08/2020 - Vinny
				$dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[5]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a2[1]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[5]));
				$fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a2[1]));
			}

            //modelo PDF 2
            if($a1[0] == "r$" && (isset($a1[2]) && $a1[2] == "r$") && $a2[0] != "r$" && count($a) == 9 && count($a1) == 4) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[3]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
            }

            //modelo PDF 3
            if (count($a) > 11 && !empty($a[12])) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[12]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[12]));
            }

            //modelo PDF 4
            if (count($a) == 7 && count($a1) > 4) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[4]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[4]));
            }

            //modelo PDF 10
            if (count($a) == 7 && count($a1) == 3 && count($a1) == 2) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a2[1]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a2[1]));
            }

            //Modelo PDF 5
            if (count($a) > 11 && !isset($a[12])) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[11]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[11]));
            }

            //Modelo PDF 7
            if (count($a) > 11 && !empty($a[11]) && !isset($a[12])) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[11]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a[11]));
            }
			
			//Modelo PDF 11
            if (	count($a) === 5 &&  count($a1) === 8 && count($a2) === 1 &&
					$a[3] == 'r$' && $a1[0] == 'r$' && $a1[2] == 'r$' && $a1[4] == 'r$' && $a1[6] == 'r$' )
			{
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[5]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[7]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[5]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[7]));
            }

            //Modelo PDF 8
            if (isset($fill['vlr_recibo_5']) && $fill['vlr_recibo_5'] == 'r$') {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[5]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a1[3]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[5]));
            }

            //Modelo PDF 9
            if (count($a) == 11 && count($a1) == 2) {
                $dados['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $dados['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $dados['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $dados['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $dados['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[1]));

                $fill['vlr_recibo_1'] = str_replace(',', '.', str_replace('.', '', $a[4]));
                $fill['vlr_recibo_2'] = str_replace(',', '.', str_replace('.', '', $a[6]));
                $fill['vlr_recibo_3'] = str_replace(',', '.', str_replace('.', '', $a[8]));
                $fill['vlr_recibo_4'] = str_replace(',', '.', str_replace('.', '', $a[10]));
                $fill['vlr_recibo_5'] = str_replace(',', '.', str_replace('.', '', $a1[1]));
            }

	        fclose($handle);
	        if (file_exists($arr['pathtxt'])) {
		        @unlink($arr['pathtxt']);
	        }
        }
        return $dados;
    }

    private function validateGeral($file, $id, $checkTXT = false, $checkPDF = false, $checkDOC = false)
    {
        $validations = array();
        $atividade = Atividade::findOrFail($id);
        $loadExtensoes = EntregaExtensao::Where('tributo_id', $atividade->regra->tributo->id)->get()->toarray();

        if (!empty($loadExtensoes)) {
            foreach ($loadExtensoes as $x => $k) {
                $validations[strtolower($k['extensao'])] = false;
            }
        }
        if (is_dir($file)) {
            //inicia validação de pasta
            $file_extensions = array();
            $validation = scandir($file);
            if (!empty($validation)) {
                foreach ($validation as $kk => $value_value) {
                    if (strlen($value_value) > 2) {
                        $exp = explode('.',$value_value);
                        if(isset($exp[1])){
                            $file_extensions[] = strtolower($exp[1]);
                        }
                    }
                }
            }

            if (!empty($file_extensions)) {
                foreach ($file_extensions as $x => $valid) {

                    if ($checkTXT) {
                        if ($valid == 'txt') {
                            return true;
                        }
                    }
                    if ($checkPDF) {
                        if ($valid == 'pdf') {
                            return true;
                        }
                    }
                    if ($checkDOC) {
                        if (substr($valid, 0,3) == 'doc') {
                            return true;
                        }
                    }

                    if (isset($validations[$valid]) && empty($validations[$valid])) {
                        $validations[$valid] = true;
                    }
                }
            }
        }

        if (!is_dir($file)) {
            //inicia validação de pasta geral
            $allfiles = $this->getFilesByAtividadeId($atividade->id, $file);
            $file_extensions = array();

            $validation = $allfiles;
            if (!empty($validation)) {
                foreach ($validation as $kk => $value_value) {
                    $exp = explode('.',$value_value);
                    if(isset($exp[1])){
                        $file_extensions[] = strtolower($exp[1]);
                    }
                }
            }

            if (!empty($file_extensions)) {
                foreach ($file_extensions as $x => $valid) {

                    if ($checkTXT) {
                        if ($valid == 'txt') {
                            return true;
                        }
                    }
                    if ($checkPDF) {
                        if ($valid == 'pdf') {
                            return true;
                        }
                    }
                    if ($checkDOC) {
                        if (substr($valid, 0,3) == 'doc') {
                            return true;
                        }
                    }

                    if (isset($validations[$valid]) && empty($validations[$valid])) {
                        $validations[$valid] = true;
                    }
                }
            }
        }

        if ($checkPDF || $checkTXT || $checkDOC) {
            return false;
        }

        $retorno = 1;
        if (!empty($validations)) {
            foreach ($validations as $x => $index_true) {
                if (!$index_true) {
                    $retorno = $x;
                }
            }
        }

        return $retorno;
    }

    private function getFilesByAtividadeId($id, $file)
    {
        $explode = explode('/', $file);
        $path = '';
        foreach ($explode as $k => $way) {
            $path.= $way.'/';
            if ($way == 'entregar') {
                break;
            }
        }
        $path = substr($path, 0,-1);
        $files_formated = array();
        $allFiles = scandir($path);
        foreach ($allFiles as $single_index => $single_file) {
            $directory = $path.'/'.$single_file;
            if (strlen($single_file) > 2 && $directory != $file && !is_dir($directory)) {
                $detalhamento = explode('_', $single_file);
                if ($detalhamento[0] == $id) {
                    $files_formated[] = $directory;
                }
            }
        }
        $files_formated[] = $file;

        return $files_formated;
    }

    private function loadTributo($tributo_nome)
    {
        $tributo = Tributo::where('nome', $tributo_nome)->first();
        return $tributo->id;
    }

    private function LoadNomeTributo($nomeTributo)
    {
        $nomeTributo = $this->letras($nomeTributo);
        if ($nomeTributo == "SPEDFISCAL") {
            return "SPED FISCAL";
        }
        if ($nomeTributo == "EFD") {
            return "EFD CONTRIBUIÇÕES";
        }
        if ($nomeTributo == "ICMSST") {
            return "ICMS ST";
        }
        if ($nomeTributo == "GIAST") {
            return "GIA ST";
        }
        if ($nomeTributo == "DCTFWEB") {
            return "DCTF WEB";
        }
        if ($nomeTributo == "LIVROFISCAL") {
            return "LIVRO FISCAL";
        }
        if ($nomeTributo == "DESONERACAO") {
            return "DESONERAÇÃO FOLHA";
        }
        return $nomeTributo;
    }

    private function checkTribAtividade($id_atividade, $id_tributo)
    {
        $atividade = Atividade::where('id', $id_atividade)->first();
        if ($atividade->regra->tributo_id == $id_tributo) {
            return true;
        }
        return false;
    }

    public function checkTributo($tributo)
    {
        $permission = DB::table('tributos')
            ->select('tributos.id')
            ->where('tributos.nome', '=', $tributo)
            ->get();

        if (empty($permission)) {
            return false;
        }

        return true;
    }

    private function validatePasta($atividade_id, $codigo_estabelecimento, $nome_tributo, $periodo_apuracao, $uf)
    {
        if (!is_numeric($atividade_id)) {
            return false;
        }

        if (!strlen($uf == 2) && is_numeric($uf)) {
            return false;
        }

        if (!strlen($periodo_apuracao) == 6 && !is_numeric($periodo_apuracao)) {
            return false;
        }

        if (strlen($codigo_estabelecimento) > 5) {
            return false;
        }

        $nm_tributo = $this->numero($nome_tributo);
        if (!empty($nm_tributo)) {
            return false;
        }

        return true;
    }

    public function createZipFile($f = array(),$fileName, $unchecked){
        $zip = new \ZipArchive();

        touch($fileName);
        $arrayDelete = array();
        $res = $zip->open($fileName, \ZipArchive::CREATE);
        if($res === true){
            foreach ($f as $in => $name) {
                if (!is_file($name['path'])) {
                    $name['path'] = $name['path'].'/';
                    $name['filename'] = $name['filename'].'/';

                    if(is_dir($name['path'])){
                        $arrayExtra = scandir($name['path']);
                        foreach ($arrayExtra as $M => $singlefile) {
                            if (strlen($singlefile) > 2) {
                                $extra_files[$M]['path'] = $name['path'].$singlefile;
                                $extra_files[$M]['filename'] = $singlefile;
                            }
                        }

                        if(isset($extra_files)){
                            foreach ($extra_files as $keyExtra => $extra_file) {
                                if ($zip->addFile($extra_file['path'] , $extra_file['filename'])) {
                                    $destinoArray = explode('/', $extra_file['path']);
                                    $destino = '';
                                    foreach ($destinoArray as $key => $value) {
                                        $destino .= $value.'/';
                                        if ($key == 2) {
                                            break;
                                        }
                                    }
                                    $destino .= 'uploaded/';
                                    $arrayDelete['pasta'][$keyExtra]['path'] = $extra_file['path'];
                                    $arrayDelete['pasta'][$keyExtra]['filename'] = $extra_file['filename'];
                                    $arrayDelete['pasta'][$keyExtra]['pastaname'] = $name['filename'];
                                    $arrayDelete['pasta'][$keyExtra]['destino'] = $destino;
                                    $arrayDelete['pasta'][$keyExtra]['raiz'] = $name['path'];
                                    $arrayDelete['pasta'][$keyExtra]['pasta'] = 1;
                                }
                            }
                        } else {
                            $name['path'] =substr($this->limpaWay($name['path']), 0, -1);
                            if ($this->checkDiretorio($name['path'])) {
                                @rmdir($name['path']);
                            }
                        }
                    }
                }

                if (is_file($name['path'])) {
                    if ($zip->addFile($name['path'] , $name['filename'])) {
                        $destinoArray = explode('/', $name['path']);
                        $destino = '';
                        foreach ($destinoArray as $key => $value) {
                            $destino .= $value.'/';
                            if ($key == 2) {
                                break;
                            }
                        }
                        $destino .= 'uploaded/';
                        $arrayDelete['pasta'][$in]['path'] = $name['path'];
                        $arrayDelete['pasta'][$in]['filename'] = $name['filename'];
                        $arrayDelete['pasta'][$in]['destino'] = $destino;
                        $arrayDelete['pasta'][$in]['pastaname'] = $name['filename'];
                    }
                }

            }
        }

        $zip->close();

        if (!empty($arrayDelete)) {
            foreach ($arrayDelete as $chave => $single) {
                if (is_array($single) && $chave === 'pasta') {
                    foreach ($single as $p => $mostsingle) {

                        $creationpath = $mostsingle['destino'].$mostsingle['pastaname'];
                        $verifypath = str_replace('uploaded', 'entregar', $creationpath);

                        if (!is_dir($creationpath) && !is_file($verifypath)) {
                            mkdir($creationpath, 0777);
                        }

                        $currentFile = $creationpath;
                        if (!is_file($verifypath)) {
                            $creationpath = $creationpath.'/';
                            $currentFile = $creationpath.'/'.$mostsingle['filename'];
                        }
                        @copy($mostsingle['path'], $currentFile);
                        @unlink($mostsingle['path']);
                    }

                    if (isset($mostsingle['raiz'])) {
                        if ($this->checkDiretorio($mostsingle['raiz'])) {
                            @rmdir($mostsingle['raiz']);
                        }
                    }
                }
                if (!is_array($single)) {
                    @copy($single['path'], $single['destino']);
                    @unlink($single['path']);
                }

                if(is_array($single) && is_numeric($chave)){
                    @copy($single['path'], $single['destino']);
                    @unlink($single['path']);
                }
            }
        }

        if (file_exists($fileName)) {
            $data = ['image' => $fileName, 'atividade_id' => $name['atividade'], 'unchecked' => $unchecked,'_token' => csrf_token()];
            $this->upload($data);
        }
    }

    private function checkDiretorio($diretorio)
    {
        $verify = array();
        if (!empty($diretorio) && is_dir($diretorio)) {
            $scandir = scandir($diretorio);
            foreach ($scandir as $index => $pasta) {
                if (strlen($pasta) > 2) {
                    $verify[] = $pasta;
                }
            }

            if (!empty($verify)) {
                return false;
            }
            return true;
        }
    }

    private function limpaWay($way)
    {
        $anotherway = '';
        if(!empty($way)){
            $exploded = explode('/', $way);
            foreach($exploded as $index => $single){
                if(!empty($single)){
                    $anotherway .= $single;
                    if(is_file($anotherway)){
                        break;
                    } else {
                        $anotherway .= '/';
                    }
                }
            }
        }
        return $anotherway;
    }

    public function upload($data) {
        $file = array('image' => $data['image']);
        $rules = array('image' => 'required|mimes:pdf,zip');
        $validator = Validator::make($file, $rules);

        $atividade_id = $data['atividade_id'];
        $atividade = Atividade::findOrFail($atividade_id);
        $estemp = $atividade->estemp;
        $regra = $atividade->regra;
        $tipo = $regra->tributo->tipo;
        $tipo_label = 'UNDEFINED';
        switch($tipo) {
            case 'F':
                $tipo_label = 'FEDERAIS'; break;
            case 'E':
                $tipo_label = 'ESTADUAIS'; break;
            case 'M':
                $tipo_label = 'MUNICIPAIS'; break;
        }

        $destinationPath = 'uploads/'.substr($estemp->cnpj,0,8);

        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }

        $destinationPath .= '/'.$estemp->cnpj;
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }

        $destinationPath .= '/'.$tipo_label;
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }

        $destinationPath .= '/'.$regra->tributo->nome;
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }

        $destinationPath .= '/'.$atividade->periodo_apuracao;
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777);
        }

        $destinationPath .='/';
        @copy($data['image'], $destinationPath.$data['image']);
        @unlink($data['image']);


        $query = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B inner join atividadeanalistafilial C on B.id = C.Id_atividadeanalista where B.Tributo_id = " .$regra->tributo->id. " and B.Emp_id = " .$atividade->emp_id. " AND C.Id_atividadeanalista = B.id AND C.Id_estabelecimento = " .$estemp->id. " AND B.Regra_geral = 'N') limit 1";

        $retornodaquery = DB::select($query);

        $sql = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B where B.Tributo_id = " .$regra->tributo->id. " and B.Emp_id = " .$atividade->emp_id. " AND B.Regra_geral = 'S') limit 1";

        $queryGeral = DB::select($sql);

        $idanalistas = $retornodaquery;
        if (empty($retornodaquery)) {
            $idanalistas = $queryGeral;
        }

        $user_aprovador = '';
        if (!empty($idanalistas)) {
            foreach ($idanalistas as $k => $analista) {
                $user_aprovador = $analista->id;
            }
        }

        if (empty($user_aprovador)) {
            $user_aprovador = 112;
        }

        $atividade->arquivo_entrega = $data['image'];
        $atividade->usuario_entregador = $user_aprovador;
        $atividade->data_entrega = date("Y-m-d H:i:s");
        $atividade->status = 2;

        if(($atividade->regra->tributo->id == 1 || $atividade->regra->tributo->id == 8 ||
                $atividade->regra->tributo->id == 18 || $atividade->regra->tributo->id == 28 || $atividade->regra->tributo->id == 32) &&
            $data['unchecked'] === false
        ) {
            $atividade->usuario_aprovador = 112;
            $atividade->data_aprovacao = date('Y-m-d H:i:s');
            $atividade->status = 3;
        }

        $atividade->save();
    }

    private function array_keys_exists(array $arr, $tributo_id) {
        if($tributo_id === 8 || $tributo_id === 18 || $tributo_id === 28) {
            if( array_key_exists ('VLR_TOTAL', $arr) === false && array_key_exists ('VALOR_TOTAL', $arr) === false) {
                return false;
            }

            if( array_key_exists ('VLR_RECEITA', $arr) === false && array_key_exists ('VALOR_RECEITA', $arr) === false) {
                return false;
            }

            if( array_key_exists ('DATA_VENCTO', $arr) === false && array_key_exists ('DATA_VENCIMETO', $arr) === false) {
                return false;
            }
        }

        if( array_key_exists ('REFERENCIA', $arr) === false || $arr['REFERENCIA'] === '') {
            return false;
        }
        return true;
    }

    private function writeLog($texto) {
        return Log::info($texto);

    }
	
		private function patrocinioSP($value)
		{
				$icms = array();
				if (!file_exists($value['pathtxt'])) {
					return $icms;
				}
				
				$file_content = explode('_', $value['arquivo']);
				$atividade = Atividade::findOrFail($file_content[0]);

				$empresa = Estabelecimento::where('id', '=', $atividade->estemp_id)->first();
				$estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
				
				$icms['UF'] = 'SP';
				$handle = fopen($value['pathtxt'], "r");
				$contents = fread($handle, filesize($value['pathtxt']));
				$str = 'foo '.$contents.' bar';
				$str = utf8_encode($str);
				$str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
				$str = strtolower($str);
				$icms['TRIBUTO_ID'] = 34;
				$icms['IMPOSTO'] = 'SDEC-SP';
				$icms['COD_RECEITA'] = 'SDEC-SP';
				$icms['REFERENCIA'] = substr_replace($file_content[3], '/', 2, 0);
				$icms['CNPJ'] = $estabelecimento->cnpj;
				$icms['IE'] = $estabelecimento->insc_estadual;
				
				// data de vencimento
				preg_match('~vencimento ([^{]*)~i', $str, $match);
				if (!empty($match)) {
					$string = trim($match[1]);
					$string = explode(PHP_EOL, $string);
					if(isset($string) && count($string) > 0) {
						$valorData = $string[0];
						$data_vencimento = str_replace('/', '-', $valorData);
						$icms['DATA_VENCTO'] = date('Y-m-d', strtotime($data_vencimento));
					}
				}
			
				//vlr_receita
				preg_match('~valor documento ([^{]*)~i', $str, $match);
				if (!empty($match)) {
					$i = explode(PHP_EOL, trim($match[1]));
					if(isset($i) && count($i) > 0){
						$icms['VLR_RECEITA'] = str_replace('r$', '', str_replace(',', '.', trim(str_replace('.', '', $i[0]))) );
						$icms['VLR_TOTAL'] = $icms['VLR_RECEITA'];
					}
				}
			
				// codigo de barras
				preg_match('~banco ([^{]*)~i', $str, $match);
				if (!empty($match)) {
					$i = explode(PHP_EOL, trim($match[1]));
					$icms['CODBARRAS'] = str_replace([' ','.'], '', $i[29]);
				}
				
				fclose($handle);
				$icmsarray = array();
				$icmsarray[0] = $icms;
			
				//echo '<pre>', print_r($icmsarray); exit;
				return $icmsarray;
		}
	
	private function uploadArquivoLivroFiscal($file_content, $str, $icms)
	{
		if(!in_array('LIVROFISCAL', [substr($file_content[2], -11, 11), $file_content[3]])){
			return false;
		}
		
		$icms['VLR_TOTAL'] = '0.00';
		$icms['VLR_RECEITA'] = '0.00';
		
		preg_match('~mes ou periodo/ano :([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$i = explode(' ', trim($match[1]));
			$data_vencimento = str_replace('/', '-', str_replace('cnpj','', $i[2]));
			$date = DateTime::createFromFormat('d-m-Y', trim($data_vencimento));
			if(!empty($date))
				$icms['DATA_VENCTO'] = $date->format('Y-m-d');
		}
		
		preg_match('~cnpj :([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$i = explode(' ', trim($match[1]));
			$cnpj = isset($i[0])? str_replace(['.','-','/'],'', $i[0]) : 0;
			$icms['CNPJ'] = $cnpj;
		}
		
		preg_match('~cnpj/cpf:([^{]*)~i', $str, $match);
		if (!empty($match)) {
			$i = explode(' ', trim($match[1]));
			$cnpj = isset($i[0])? str_replace(['.','-','/'],'', $i[0]) : 0;
			$icms['CNPJ'] = $cnpj;
		}
		
		$icms['REFERENCIA'] = substr($file_content[3],0, 2).'/'.substr($file_content[3],2);
// echo '<pre>', print_r($icms); exit;
		return $icms;
	}
	
	public function icmsAM($value)
	{
		$icms = array();
		if (!file_exists($value['pathtxt'])) {
			return $icms;
		}
		
		$file_content = explode('_', $value['arquivo']);
		$atividade = Atividade::findOrFail($file_content[0]);
		$estabelecimento = Estabelecimento::where('id', '=', $atividade->estemp_id)->where('ativo', '=', 1)->first();
		$icms['CNPJ'] = $estabelecimento->cnpj;
		$icms['IE'] = $estabelecimento->insc_estadual;
		$icms['UF'] = 'AM';
		
		$handle = fopen($value['pathtxt'], "r");
		$contents = fread($handle, filesize($value['pathtxt']));
		$str = 'foo '.$contents.' bar';
		$str = utf8_encode($str);
		$str = preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/","/(ç)/","/(Ç)/","/(ª)/","/(°)/"),explode(" ","a A e E i I o O u U n N c C um um"),$str);
		$str = strtolower($str);
		$icms['TRIBUTO_ID'] = 8; if(!empty($this->uploadArquivoLivroFiscal($file_content, $str, $icms))){ $icms = $this->uploadArquivoLivroFiscal($file_content, $str, $icms); }
		
		fclose($handle);
		$icmsarray = array();
		$icmsarray[0] = $icms;
		return $icmsarray;
	}
	
	public function JobConferenciaGuias($file, $conferenciaguias_id)
	{ // return true;
		$funcao = 'pdftotext.exe ';
		
		$filetxt = str_replace(['.pdf', '.PDF'], '_CONVERTIDO.txt', $file);
		$caminho1 = explode('/', $filetxt);
		$caminho1_result = '';
		foreach ($caminho1 as $key => $value) {
			$arquivonome = $value;
			$caminho1_result .= $value.'/';
			if (strpbrk($value, '0123456789１２３４５６７８９０')) {
				$caminho1_result .= 'results/';
			}
		}
		
		$caminho1_result = substr($caminho1_result, 0, -1);
		shell_exec($funcao.$file.' '.substr($caminho1_result, 0, -8));
//				return $funcao.$file.' '.substr($caminho1_result, 0, -8);
		
		$destino = str_replace('results', 'imported', str_replace('txt', 'pdf', $caminho1_result));
		
		$arr[$file]['arquivo'] = str_replace('txt', 'pdf', $arquivonome);
		$arr[$file]['path'] = substr($destino, 0, -9);
		$arr[$file]['arquivotxt'] = $arquivonome;
		$arr[$file]['pathtxt'] = substr($caminho1_result, 0, -8);
		
		$this->saveICMS($arr, true, $conferenciaguias_id);
		
		$a = explode('/', $_SERVER['SCRIPT_FILENAME']);
		
		if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
			$cmd = $a[0].'\wamp64\bin\php\php5.6.40\php.exe '.$a[0].'\wamp64\www\agenda\public\Background\LeitorMails.php';
		}
		
		if (substr(php_uname(), 0, 7) == "Windows"){
			pclose(popen("start /B " . $cmd, "r"));
		} else {
			exec($cmd . " > /dev/null &");
		}
		
		return true;
	}
	
	public function geraCsvZfic($planilha, $data_inicio, $data_fim, $comBarras = true)
	{
		if (empty($planilha)) {
			return false;
		}
		
		$fileName = '';
		$content = '';
		if($comBarras == true) {
			
			$fileName = 'ZFIC_COMCODBARRAS_'.$data_inicio.'_'.$data_fim.'_GERADO'.date('dmYHis');
			
			
$content = 'CAB_CDRCIN;CAB_CODTBT;CAB_BUKRS;CAB_BARCOD;CAB_DTVENC;CAB_GSBER;CAB_CNPJE;CAB_COMPCM;CAB_COMENT;CAB_RGINST;CAB_NFENUM;CAB_SERIES;CAB_SUBSER;CAB_ACCESS_KEY;CAB_AUTHCOD;CAB_DATANF;CAB_FGTSID;CAB_AUFNR;RAT_KOSTL;RAT_GSBER;RAT_VALOR;RAT_VAL_ATU;RAT_VAL_MULTA;RAT_VAL_JUROS;RAT_VAL_OUTROS;RAT_VAL_ACRES;RAT_VAL_DESCONT;RAT_AUFNR
Codigo de Receita (Interno);Codigo do Tributo;Empresa;Codigo de Barras;Data de vencimento;Divisao;CNPJ;Comentario para Comprovante;Comentarios;Registro da instalacao;Numero de documento de nove posicoes;Series NF/NFE;Subseries;Chave de acesso de 44 posicoes;Codigo de Autoriza;BTP - Data da Nota;Identifcacao processo FGTS;Ordem;Centro de custo;Divisao;Valor total;Valor atualizado;Valor multa;Valor Juros;Valor outros;Valor acrescimento;Valor desconto;Ordem';

			foreach ($planilha as $key => $value):
				if($value['valido'] == 'S') {
					$cnpj = '';
					if (substr($value['CNPJ'], 0, 8) == 13574594) {
						$cnpj = "1000";
					}
					$valorData = $value['DATA_VENCTO'];
					$data_vencimento = str_replace('-', '/', $valorData);
					$value['DATA_VENCTO'] = date('d/m/Y', strtotime($data_vencimento));
					
$content .= '
' . $value['uf'] . ';' . $value['IMPOSTO'] . ';' . $cnpj . ';' . $value['CODBARRAS'] . ';' . $value['DATA_VENCTO'] . ';' . $value['codigo'] . ';;;Pagto ICMS ' . $value['codigo'] . '/' . $value['centrocusto'] . ';;ICMS;;;;;;;;' . $value['centrocusto'] . ';' . $value['codigo'] . ';' . $value['VLR_TOTAL'] . ';;' . $value['MULTA_MORA_INFRA'] . ';' . $value['JUROS_MORA'] . ';;' . $value['ACRESC_FINANC'] . ';;';
				}
			endforeach;
			
		}else{
			$fileName = 'ZFIC_SEMCODBARRAS_'.$data_inicio.'_'.$data_fim.'_GERADO'.date('dmYHis');
			
$content = 'CAB_CDRCIN;CAB_CODTBT;CAB_BUKRS;CAB_DTVENC;CAB_TPIDENT;CAB_IDENT;CAB_DTAPUR;CAB_NUMREF;CAB_TIPODARF;CAB_GSBER;CAB_CNPJE;CAB_COMPCM;CAB_COMENT;CAB_PRDCPT;CAB_INFADI;CAB_DARF11;CAB_DARJ22;CAB_GARE13;CAB_GARE14;CAB_GARE15;CAB_ANOBAS;CAB_RENAVA;CAB_INSEST;CAB_ESTADO;CAB_MUNICI;CAB_CPLACA;CAB_OPCPAG;CAB_OPCRET;CAB_NOMGPS;CAB_ENDGPS;CAB_NUMGPS;CAB_BAIGPS;CAB_CEPGPS;CAB_ESTGPS;CAB_MUNGPS;CAB_TELGPS;CAB_AUFNR;RAT_KOSTL;RAT_GSBER;RAT_VALOR;RAT_VAL_ATU;RAT_VAL_MULTA;RAT_VAL_JUROS;RAT_VAL_OUTROS;RAT_VAL_ACRES;RAT_VAL_DESCONT;RAT_AUFNR
Código de Receita (Interno);Codigo do Tributo;Empresa;Data de vencimento;Tipo de identificação;Identificação;Data Apuração;Numero Refencia;Tipo DARF;Divisão;CNPJ;Comentário para Comprovante;Comentários;Periodo de Competência/Referência/Apuração;Informações Adicionais;Data de Apuração;Percentual sobre Receita Bruta;Documento de Origem;Referência GARE Bradesco;Número do Parcelamento/AIIM/OEICM;Divida ativa / Nº Etiqueta;Ano Base;Inscrição Estadual;Estado (UF);Município;Placa Veículo;Opção de Pagamento;Opção de Retirada;Nome (GPS);Endereço (GPS);Numero do Endereço (GPS);Bairro (GPS);CEP (GPS);UF (GPS);Município (GPS);Telefone (GPS);Ordem;Centro de custo;Divisao;Valor total;Valor atualizado;Valor multa;Valor Juros;Valor outros;Valor acrescimento;Valor desconto;Ordem';
			
			foreach ($planilha as $key => $value):
				if($value['valido'] == 'S') {
					$cnpj = '';
					if (substr($value['CNPJ'], 0, 8) == 13574594) {
						$cnpj = "1000";
					}
					
					$valorData = $value['DATA_VENCTO'];
					$data_vencimento = str_replace('-', '/', $valorData);
					$value['DATA_VENCTO'] = date('d/m/Y', strtotime($data_vencimento));
					
$content .= '
046-2;' . $value['IMPOSTO'] . ';' . $cnpj . ';' . $value['DATA_VENCTO'] . ';1;' . $value['CNPJ'] . ';' . str_replace('/', '', $value['REFERENCIA']) . ';;;' . $value['codigo'] . ';' . $value['CNPJ'] . ';;ICMS SP;' . str_replace('/', '', $value['REFERENCIA']) . ';;;;;' . str_replace('/', '', $value['REFERENCIA']) . ';;;;' . $value['IE'] . ';' . $value['uf'] . ';' . $value['codigo_sap'] . ';;;;;;;;;;;;;' . $value['centrocusto'] . ';' . $value['codigo'] . ';' . $value['VLR_TOTAL'] . ';;' . $value['MULTA_MORA_INFRA'] . ';' . $value['JUROS_MORA'] . ';;' . $value['ACRESC_FINANC'] . ';;';
				}
			endforeach;
		}
		
		$new_csv = fopen('impostos/'.$fileName.'.csv', 'w');
		// var_dump($new_csv); exit;
		fwrite($new_csv, $content);
		fclose($new_csv);
		
		return [
			'arquivo' => 'impostos/'.$fileName.'.csv',
			'nome' => $fileName.'.csv'
		];
	}
	
	private function copiarArquivosParaAtividadesJob()
	{
		$a = explode('/', $_SERVER['SCRIPT_FILENAME']);
		$path = '';
		
		$funcao = '';
		if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
			$path = $a[0];
		}
		$path .= '/storagebravobpo/';
		
		$empresaNome = explode(' ', $this->s_emp->razao_social);
		$empresaNome = isset($empresaNome[0]) ? $empresaNome[0] : '';
		$empresaCNPJ = substr($this->s_emp->cnpj, 0, 8);
		
		$path .= $empresaNome . '_' . $empresaCNPJ;
		
		return $path;
	}
	
	private function sendEmail($user_rec, $data, $content_page='emails.impostos', $array = false)
	{
		Mail::send($content_page, ['data' => $data, 'user' => $user_rec], function ($message) use ($data, $user_rec) {
			// note: if you don't set this, it will use the defaults from config/mail.php
			
			$message->from('no-reply-please@bravobpo.com.br', $data['assunto']);
			foreach($data['emails'] as $key => $value){
				if($key == 0){
					$message->to($value, $name = null);
				}else{
					$message->cc($value, $name = null);
				}
			}
			if(isset($data['anexo'])){
				$message->subject($data['assunto'])
					->attach($data['anexo']);
			}
		});
	}
}