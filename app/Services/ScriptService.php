<?php

namespace App\Services;

use DB;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\Atividade;

class ScriptService
{
	public $s_emp = null;
	
	private $drive = null;
	
	public function spedSort() {
		$a = explode('/', $_SERVER['SCRIPT_FILENAME']);
        $path = '';

        $funcao = '';
        if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
            $path = $a[0];
			$this->drive = $a[0];
        }
		$path .= '/storagebravobpo/';

        $arquivos = scandir($path);

        $data = array();
        foreach ($arquivos as $k => $v) {
            if (strpbrk($v, '0123456789１２３４５６７８９０')) {
                $path_name = $path.$v.'/';
                if(!file_exists($path_name.'/retornopva') && !file_exists($path_name.'/retornopva/')) continue;
				$data[$k]['arquivos'][1][1] = scandir($path_name.'/retornopva');
                $data[$k]['arquivos'][1][2]['path'] = $path_name.'retornopva/';
            }
        }
		
		$files = array();
		
		foreach ($data as $X => $FILENAME) {
            foreach ($FILENAME as $L => $pastas) {
                foreach ($pastas as $key => $arquivos) {					
                    if (is_array($arquivos[1])) {
                        foreach ($arquivos[1] as $A => $arquivo) {
                            if (strlen($arquivo) > 2) {
								$paths = explode("/", $arquivos[2]['path']);
								$empresaPasta = $paths[count($paths)-3];
								$arquivo_rec = $this->scanforREC($arquivos[2]['path'].$arquivo);
								
								if($arquivo_rec !== false) {
									$atividade = $this->searchAtividade($arquivo_rec);
									$arquivos_array = explode(".",$arquivo_rec);
									$padraoNomeArquivo = $arquivos_array[0];
									
									$pdf_old = $arquivos[2]['path'].$arquivo."/".$padraoNomeArquivo.".pdf";
									$txt_old = $arquivos[2]['path'].$arquivo."/".$padraoNomeArquivo.".txt";
									$rec_old = $arquivos[2]['path'].$arquivo."/".$padraoNomeArquivo.".rec";

									$novaPasta = $this->drive.'/storagebravobpo/'.$empresaPasta.'/entregar';
									
									if(file_exists($novaPasta) && file_exists($pdf_old) && file_exists($txt_old) && file_exists($rec_old)) {
										$novaPastaComAtividade = $novaPasta."/".$atividade."/";
										if(!file_exists($novaPastaComAtividade)) mkdir($novaPastaComAtividade);
										
										$pdf_new = $novaPasta."/".$atividade."/".$padraoNomeArquivo.".pdf";
										$txt_new = $novaPasta."/".$atividade."/".$padraoNomeArquivo.".txt";
										$rec_new = $novaPasta."/".$atividade."/".$padraoNomeArquivo.".rec";										
										
										copy($pdf_old, $pdf_new);
										copy($txt_old, $txt_new);
										copy($rec_old, $rec_new);
										
										$old_pvafolder = $arquivos[2]['path'].$arquivo;
										$new_pvapath = $this->drive.'/storagebravobpo/'.$empresaPasta.'/uploadpva';
										
										if(!file_exists($new_pvapath)) mkdir($new_pvapath);

										$this->rename_win($old_pvafolder, $new_pvapath."/".$arquivo);
									}
								}
								
                            }
                        }
                    }
                }
            }
        }
		
		echo "Reorganização de pastas realizada com sucesso.";exit;
		
	}
	
	private function scanforREC($path) {
        $arquivos = scandir($path);
		
        $data = array();
        foreach ($arquivos as $k => $v) {
			$file_ext = explode(".",$v);
			$file_ext = end($file_ext);
			
			if($file_ext == 'rec' || $file_ext == 'REC' ) return $v;
		}
		return false;
	}
	
	private function searchAtividade($recName) {
		$name = explode("_",$recName);
		if(count($name) < 4) return false;

		$estabelecimento_codigo = $name[1];
		$periodo = $name[2].$name[3];
		
		$estabelecimento = Estabelecimento::where('codigo',$estabelecimento_codigo)->first();
		$municipio = Municipio::where('codigo','=',$estabelecimento->cod_municipio)->first();
		$estabelecimento_id = $estabelecimento->id;
		$uf = $municipio->uf;
		
		$atividade = Atividade::select("atividades.id as atividade_id")
		->join('regras', 'regras.id',' = ','atividades.regra_id')
		->where(['regras.tributo_id' => 1, 'estemp_id' => $estabelecimento_id, 'regras.ref' => $uf, 'periodo_apuracao' => $periodo])->first();
		
		if(!empty($atividade)) {
			$atividade_id = $atividade->atividade_id;		
			$nomePasta = $atividade_id."_".$estabelecimento_codigo."_SPEDFISCAL_".$periodo."_".$uf;
			return $nomePasta;	
		}
		return false;
	}
	
	private function rename_win($oldfile,$newfile) {
		if(file_exists($newfile)) {
			$this->deleteDirectory($newfile);
		}
		
		if (!rename($oldfile,$newfile)) {
			if (copy ($oldfile,$newfile)) {
				$this->deleteDirectory($oldfile);
				return TRUE;
			}
		}
		return false;
	}
	
	public function cleanUpFolderUploaded() {
		$a = explode('/', $_SERVER['SCRIPT_FILENAME']);
        $path = '';

        $funcao = '';
        if ($a[0] == 'C:' || $a[0] == 'F:' || $a[0] == 'D:') {
            $path = $a[0];
			$this->drive = $a[0];
        }
		$path .= '/storagebravobpo/';

        $arquivos = scandir($path);

        $data = array();
		
		
        foreach ($arquivos as $k => $v) {
            if (strpbrk($v, '0123456789１２３４５６７８９０')) {
                $path_name = $path.$v.'/';
                if(!file_exists($path_name.'/uploaded') && !file_exists($path_name.'/uploaded/')) continue;
				$data[$k]['arquivos'][1][1] = scandir($path_name.'/uploaded');
                $data[$k]['arquivos'][1][2]['path'] = $path_name.'uploaded/';
            }
        }

		foreach ($data as $X => $FILENAME) {
            foreach ($FILENAME as $L => $pastas) {
                foreach ($pastas as $key => $arquivos) {					
                    if (is_array($arquivos[1])) {
                        foreach ($arquivos[1] as $A => $arquivo) {
                            if (strlen($arquivo) > 2) {
								$paths = explode("/", $arquivos[2]['path']);
								$empresaPasta = $paths[count($paths)-3];
								$arquivo_path = $arquivos[2]['path'].$arquivo;
								$lastmodified = filemtime($arquivo_path);
								$limitDate = time() - (20 * 24 * 60 * 60);
								
								if($lastmodified < $limitDate) {
									if(is_dir($arquivo_path)) {
										$this->deleteDirectory($arquivo_path);
									} else {
										unlink($arquivo_path);
									}
								}							
                            }
                        }
                    }
                }
            }
        }
		
		echo "Limpeza da pasta upload realizada com sucesso.";exit;
	}
	
	private function deleteDirectory($dir) {
		if (!file_exists($dir)) {
			return true;
		}

		if (!is_dir($dir)) {
			return unlink($dir);
		}

		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') {
				continue;
			}

			if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
				return false;
			}

		}

		return rmdir($dir);
	}
}
?>