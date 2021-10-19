<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AtividadeAnalista;
use App\Models\AtividadeAnalistaFilial;
use App\Models\Atividade;
use App\Models\TransmitirSped;
use App\Models\Regra;
use App\Models\Tributo;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Services\EntregaService;
use App\Models\User;
use App\Models\Role;
use App\Models\googl;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;


use App\Http\Requests;


class SpedFiscalController extends Controller
{
	protected $eService;
    public $s_emp = null;

    public function __construct(EntregaService $service)
    {
        $this->eService = $service;
        if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
            $this->s_emp = Empresa::findOrFail(session()->get('seid')); 
        }
    }

    public function job()
    {
        set_time_limit(0);
        $files = array();
        $Emps = Empresa::All();
        $raizes = $this->getPathNames($Emps);
        if (!empty($raizes)) {
            foreach ($raizes as $x => $raiz) {
                $scandir = scandir($raiz);
                $raiz_name = explode('/', $raiz);
                foreach ($scandir as $x => $k) {
                    foreach ($raiz_name as $ll => $singlePath) {}
                    if (strlen($k) > 2) {
                        $files[$singlePath][] = $raiz.'/'.$k;
                    }
                }
            }
        }

        if (!empty($files)) {

            foreach ($files as $estab_id => $multiple_files) {
                $create = false;
                $errorFile = array();
                foreach ($multiple_files as $key => $single_files) {
                    $exploded_final = explode('/', $single_files);
                    foreach ($exploded_final as $xx => $poped_final) {}
                    $explode = explode('.', $poped_final);
                    foreach ($explode as $randon => $exploded) {
                        if ($exploded == 'TXT-ERRO') {
                            $create = true;
                            $errorFile = $single_files;
                        }
                    }
                }
                if ($create) {
                    $zipPath = $this->createZipWithFiles($multiple_files);
                    $downloadPath = $this->getZipDownloadPath($zipPath);
                    $this->createCritica($errorFile, $downloadPath);
                    continue;
                }
            }
        }

        echo "Job rodado com sucesso";exit;
    }

    private function getZipDownloadPath($zipPath)
    {
        $zipPath = str_replace("\\", '/', $zipPath);
        $exploded = explode('/', $zipPath);
        $a = 0;
        $download = $_SERVER['SERVER_NAME'].'/agenda/public';
        foreach ($exploded as $key => $separated) {
            if ($separated == 'spedfiscal' || $a) {
                $a = 1;
                $download .= '/'.$separated;
            }
        }
		
        return $download;
    }

    private function createZipWithFiles($files){
	
        $fileName = 'spedfiscal/'.date('dmYHis').'.zip';
		$zip = new \ZipArchive();
        touch($fileName);
		
        $res = $zip->open($fileName, \ZipArchive::CREATE);
        if($res === true){
            foreach ($files as $index => $file) {
                $singlefilename = explode('/', $file);
                foreach ($singlefilename as $xx => $v) {
                }
                if (file_exists($file)) {
                    $zip->addFile($file, $v);
                }
            }

            $zip->close();
        }

        return public_path($fileName);
    }

    public function createCritica($arquivo, $zipPath)
    {
        $exploded = explode('/', $arquivo);
        $empresaraiz = explode('_', $exploded[2]);
        $empresacnpjini = $empresaraiz[1];
        foreach ($exploded as $key => $errorFile) { }
        $empresaraizid = 0;
        $empresaRaizBusca = DB::select('select id, razao_social, cnpj from empresas where LEFT(cnpj, 8)= "'.$empresacnpjini.'"');
        if (!empty($empresaRaizBusca[0]->id)) {
            $empresaraizid = $empresaRaizBusca[0]->id;
        }

        $empresa_razao = $empresaRaizBusca[0]->razao_social;
        $empresa_cnpj = $empresaRaizBusca[0]->cnpj;
        $filial = $exploded[5];
        $filename = $exploded[6];
        $estemp = Estabelecimento::where('codigo', $filial)->where('empresa_id', $empresaraizid)->first();

        $user_id = $this->loadResponsavel($empresaraizid, $estemp->id);
        
        $key = 'AIzaSyBI3NnOJV5Zt-hNnUL4BUCaWIgGugDuTC8';
        $Googl = new Googl($key);

        $now = date('d/m/Y');
        $data['subject'] = "CRÍTICAS SPED FISCAL ICMS-IPI FILIAL : ".$filial." - ".$empresa_razao;
        $data['messageLines'] = "Segue arquivo de críticas da empresa ".$empresa_cnpj.", código da filial ".$filial.", para análise e correção.";
        $data['messageLines_2'] = " Arquivo de Erro : ".$errorFile;
        $data['downloadLink'] = $Googl->shorten($zipPath);
        
        if (!empty($user_id)) {
            $user = User::findOrFail($user_id);
            $this->eService->sendMail($user, $data, 'emails.notification-spedfiscal-error', false);
        }
    }    

    private function loadResponsavel($emp_id, $estemp_id)
    {
        $query = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B inner join atividadeanalistafilial C on B.id = C.Id_atividadeanalista where B.Tributo_id = 1 and B.Emp_id = " .$emp_id. " AND C.Id_atividadeanalista = B.id AND C.Id_estabelecimento = " .$estemp_id. " AND B.Regra_geral = 'N') limit 1";

        $retornodaquery = DB::select($query);

        $sql = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B where B.Tributo_id = 1 and B.Emp_id = " .$emp_id. " AND B.Regra_geral = 'S') limit 1";
        
        $queryGeral = DB::select($sql);
        
        $idanalistas = $retornodaquery;
        if (empty($retornodaquery)) {
            $idanalistas = $queryGeral;   
        }

        if (!empty($idanalistas)) {
            foreach ($idanalistas as $k => $analista) {
                return $analista->id;
            }
        }
    }

    private function getPathNames($empresas)
    {
        $raizes = array();
        $path = '';
        $server = explode('/', $_SERVER['SCRIPT_FILENAME']);
        if ($server[0] == 'C:' || $server[0] == 'F:') {
            $path = 'F:';
        }

        $path .= '/storagebravobpo/';
        
        if (!empty($empresas)) {
            foreach ($empresas as $key => $empresa) {
                $a = explode(' ', $empresa->razao_social);
                foreach ($empresa->estabelecimentos as $x => $estabelecimento) {
                    $raizes[] = $path.$a[0].'_'.substr($empresa->cnpj, 0,8).'/pvaspedfiscal/retornopva/'.$estabelecimento->codigo;
                }
            }
        }

        if (!empty($raizes)) {
            foreach ($raizes as $anyValue => $raiz) {
                if (!is_dir($raiz)) {
                    unset($raizes[$anyValue]);
                } else {
                    $scandir = scandir($raiz);
                    $files = array();
                    foreach ($scandir as $chave => $file) {
                        if (strlen($file) > 2) {
                            $files[] = $file;
                        }
                    }
                    if (empty($files)) {
                        unset($raizes[$anyValue]);
                    }
                }
            }
        }

        return $raizes;
    }

    public function index()
    {
        $user = User::findOrFail(Auth::user()->id);

    	$query = 'SELECT 
                        A.id,
                        A.descricao,
                        E.codigo,
                        E.cnpj,
                        A.periodo_apuracao
                    FROM
                        atividades A
					INNER JOIN
                        regras B on A.regra_id = B.id
                    INNER JOIN 
                    	tributos C on B.tributo_id = C.id
                    INNER JOIN 
                    	estabelecimentos E on A.estemp_id = E.id
                    WHERE  C.id = 1 AND A.status = 1 AND A.emp_id = '.$this->s_emp->id;

        $table = DB::select($query);
   
        if (!empty($table)) {
        	foreach ($table as $key => $activities) {
        		$table[$key]->color = $this->getFileContent($activities->id);
        	}
        }

        return view('spedfiscal.index')->with('table', $table);
    }


    private function getFileContent($id, $returnfile = false)
    {
    	$array = array();
		$path = '';
    	
    	//carregamento
    	$atividade = Atividade::findOrFail($id);
		$emp['cnpj'] = substr($atividade->empresa->cnpj, 0,8);
		$a = explode(' ', $atividade->empresa->razao_social);
		$emp['name'] = $a[0];
		$codigo_estab = $atividade->estemp->codigo;
	
		//formação do path
		$server = explode('/', $_SERVER['SCRIPT_FILENAME']);
        if ($server[0] == 'C:' || $server[0] == 'F:') {
            $path = 'F:';
        }

		//a busca dos arquivos e a formação do array, precisa ser de acordo com os dados acima
        $path .= '/storagebravobpo/'.$emp['name'].'_'.$emp['cnpj'].'/pvaspedfiscal/retornopva/'.$codigo_estab;

        if (!is_dir($path)) {
        	return 'Yellow';
        }

		//carrega files dentro de uma váriavel ($array)
        $array = explode("/", $path);

        $contend_path = scandir($path);

        $arquivos = array();
        foreach ($contend_path as $key => $value) {
        	if (strlen($value) > 2) {
        		$arquivos[] = $path.'/'.$value;
        	}
        }

        if ($returnfile) {
        	if (empty($arquivos)) {
        		return 'Yellow';
        	}
        	return $arquivos;
        }

        if (!empty($arquivos)) {
        	$returnError = false;
        	$returnSuccess = false;
        	foreach ($arquivos as $key => $arquivo) {
        		$namefile = $this->getLastName($arquivo);
        		$exploded_namefile = explode('.', $namefile);
        		foreach ($exploded_namefile as $index => $cuttedfile) {
        			if ($cuttedfile == 'txt' && count($arquivos) == 1 && count($exploded_namefile) == 2) {
        				return 'Yellow';
        			}
        			if ($cuttedfile == 'TXT-ERRO') {
        				$returnError = true;
        			}
        			if ($cuttedfile == 'TXT-SUCESSO') {
        				$returnSuccess = true;
        			}
        			if ($cuttedfile == 'rec' || $cuttedfile == 'REC') {
        				return 'Green';
        			}
        		}
        	}
        	if ($returnSuccess) {
        		return 'Blue';
        	}
        	if ($returnError) {
        		return 'Red';
        	}
        }
        return 'Yellow';
    }

    private function getLastName($file)
    {
		$exploded_arquivo = explode('/', $file);
        foreach ($exploded_arquivo as $k => $namefile) {
        }
    	return $namefile;
    }

    public function DownloadPath($id)
    { 	
    	$files = $this->getFileContent($id, true);
    	if ($files == 'Yellow') {
    		return redirect()->back()->with('alert', 'Não existem arquivos na pasta ou a pasta não existe!');
    	}

        $fileName = date('dmYHis').'.zip';
        $zip = new \ZipArchive();
        touch($fileName);

        $res = $zip->open($fileName, \ZipArchive::CREATE);
        if($res === true){
            foreach ($files as $index => $file) {
                $singlefilename = explode('/', $file);
                foreach ($singlefilename as $xx => $v) {
                }
                if (file_exists($file)) {
                    $zip->addFile($file, $v);
                }
            }

            $zip->close();
            $this->ForceDown($fileName);
        }
    }

    private function ForceZipDownload($filepath)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        flush();
        readfile($filepath);   
    }

    private function ForceDown($filepath)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        flush();
        readfile($filepath);
        unlink($filepath);
    }

    public function TransmissionIndex()
    {
        $user = User::findOrFail(Auth::user()->id);

        $query = 'SELECT 
                        A.id,
                        A.descricao,
                        E.codigo,
                        E.cnpj,
                        A.periodo_apuracao
                    FROM
                        atividades A
                    LEFT JOIN
                        transmitirsped F ON A.id = F.id_atividade
                    INNER JOIN
                        regras B on A.regra_id = B.id
                    INNER JOIN 
                        tributos C on B.tributo_id = C.id
                    INNER JOIN 
                        estabelecimentos E on A.estemp_id = E.id 
                    WHERE  C.id = 1 AND A.status = 1 AND F.id_atividade IS NULL AND A.emp_id = '.$this->s_emp->id;

        $table = DB::select($query);
   
        if (!empty($table)) {
            foreach ($table as $key => $activities) {
                $table[$key]->color = $this->getFileContent($activities->id);
                if ($table[$key]->color != 'Blue') {
                    unset($table[$key]);
                }
            }
        }

        return view('spedfiscal.transmitir')->with('table',$table);
    }

    public function transmitir(Request $request)
    {
        $input = $request->all();
        $activities = array();

        if (!empty($input)) {
            foreach ($input as $index => $xValue) {
                if ($xValue == 'on') {
                    $activities[] = $index;
                }
            }
        }

        $files = array();
        if (!empty($activities)) {
            foreach ($activities as $x => $id) {
                $files[$id] = $this->getFileContent($id, true);
            }
        }

        $arquivofinal = array(); 
        foreach ($files as $key => $first) {
            foreach ($first as $index => $value) {
                $namefile = $this->getLastName($value);
                $exploded_namefile = explode('.', $namefile);
                foreach ($exploded_namefile as $x => $finalvalue) {
                    if (count($exploded_namefile) == 2 && ($finalvalue == 'txt' || $finalvalue == 'TXT')) {
                        $arquivofinal[$key] = $value;
                    }
                }        
            }
        }

        if (!empty($arquivofinal)) {
            foreach ($arquivofinal as $x => $single_path) {
                $explodedWay = explode('/', $single_path);
                $finalWay = '';
                foreach ($explodedWay as $xx => $kk) {
                    if ($kk == 'retornopva') {
                        $name = $this->getLastName($single_path);
                        $finalWay .= 'transmitir/'.$name;
                        break;
                    }                    
                    $finalWay .= $kk.'/';
                }

                copy($single_path, $finalWay);
                $this->createTransmissionHistoric($name, $x);
            }
        }
    return redirect()->back()->with('status', 'Arquivos transmitidos com sucesso!');
    }

    private function createTransmissionHistoric($file, $id)
    {
        $array['id_atividade'] = $id;
        $array['nome_arquivo'] = $file;
        $array['data_copia'] = date('Y-m-d H:i:s');
        $array['user'] = Auth::user()->id;

        TransmitirSped::Create($array);
    }
}