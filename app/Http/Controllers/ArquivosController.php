<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

use App\Http\Requests;
use Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Yajra\Datatables\Datatables;

class ArquivosController extends Controller
{
	public $s_emp = null;
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

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		return view('arquivos.index')->with('filter_cnpj',$request->input("vcn"))->with('filter_codigo',$request->input("vco"))->with('filter_tributo',$request->input("vct"))->with('filter_uf', $request->input("vuf"));
	}   

	public function Downloads(Request $request)
	{
		$input = $request->all();
		
		if (!empty($input)) {
			if (empty($input['ufs']) || empty($input['tributo_id']) || (empty($input['periodo_apuracao_inicio']) || empty($input['periodo_apuracao_fim']))) {
				return redirect()->back()->with('status', 'Os campos Tributo, UF e Período de apuração são obrigatórios para essa busca.');
			}
			
			if (!empty($input['ufs']) && count($input['ufs']) > 4) {
				return redirect()->back()->with('status', 'Você pode selecionar um máximo de 4 UFs por operação');
			}

			$periodo = $this->calcPeriodo($input['periodo_apuracao_inicio'], $input['periodo_apuracao_inicio']);
			$atividades = Atividade::select('municipios.uf','atividades.*')
						->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
						->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
						->join('regras', 'atividades.regra_id', '=', 'regras.id')
						->whereIn('atividades.periodo_apuracao', $periodo)->whereIn('municipios.uf', $input['ufs'])
						->where('regras.tributo_id', $input['tributo_id'])->where('atividades.emp_id', $this->s_emp->id);
				
			if (!empty($input['estabelecimentos_selected'])) {
				$atividades = $atividades->whereIn('atividades.estemp_id', $input['estabelecimentos_selected']);
			}

			if (!empty($input['data_entrega_inicio'])) {
				$atividades = $atividades->whereRaw('DATE_FORMAT(atividades.data_entrega, "%Y-%m-%d") between "'.$input['data_entrega_inicio'].'" AND "'.$input['data_entrega_fim'].'"');
			}

			if (!empty($input['data_aprovacao_inicio'])) {
				$atividades = $atividades->whereRaw('DATE_FORMAT(atividades.data_aprovacao, "%Y-%m-%d") between "'.$input['data_aprovacao_inicio'].'" AND "'.$input['data_aprovacao_fim'].'"');
			}
			$files = array();
			$atividades = $atividades->get();

			if (count($atividades) > 0) {
				foreach ($atividades as $kk => $atividade) {
					if ($atividade->arquivo_entrega != '-' && !empty($atividade->arquivo_entrega)) {
						$files[] = $this->downloadById($atividade->id, $atividade->uf);
					}
				}
				
				$this->zipDownload($files);
			} else {
				return redirect()->back()->with('status', 'Não foram encontradas atividades para essa busca.');
			}
		}

		$estabelecimentos = Estabelecimento::selectRaw("codigo, id")->where('empresa_id','=',$this->s_emp->id)->orderby('codigo')->groupBy('codigo')->pluck('codigo','id');
		$ufs = Municipio::selectRaw("uf, uf")->orderby('uf','asc')->pluck('uf','uf');
		$tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');

		return view('arquivos.downloads')->with('estabelecimentos', $estabelecimentos)->with('ufs', $ufs)->with('tributos', $tributos);
	}
	
	private function zipFileErrMsg($errno) {
		// using constant name as a string to make this function PHP4 compatible
		$zipFileFunctionsErrors = array(
			'ZIPARCHIVE::ER_MULTIDISK' => 'Multi-disk zip archives not supported.',
			'ZIPARCHIVE::ER_RENAME' => 'Renaming temporary file failed.',
			'ZIPARCHIVE::ER_CLOSE' => 'Closing zip archive failed',
			'ZIPARCHIVE::ER_SEEK' => 'Seek error',
			'ZIPARCHIVE::ER_READ' => 'Read error',
			'ZIPARCHIVE::ER_WRITE' => 'Write error',
			'ZIPARCHIVE::ER_CRC' => 'CRC error',
			'ZIPARCHIVE::ER_ZIPCLOSED' => 'Containing zip archive was closed',
			'ZIPARCHIVE::ER_NOENT' => 'No such file.',
			'ZIPARCHIVE::ER_EXISTS' => 'File already exists',
			'ZIPARCHIVE::ER_OPEN' => 'Can\'t open file',
			'ZIPARCHIVE::ER_TMPOPEN' => 'Failure to create temporary file.',
			'ZIPARCHIVE::ER_ZLIB' => 'Zlib error',
			'ZIPARCHIVE::ER_MEMORY' => 'Memory allocation failure',
			'ZIPARCHIVE::ER_CHANGED' => 'Entry has been changed',
			'ZIPARCHIVE::ER_COMPNOTSUPP' => 'Compression method not supported.',
			'ZIPARCHIVE::ER_EOF' => 'Premature EOF',
			'ZIPARCHIVE::ER_INVAL' => 'Invalid argument',
			'ZIPARCHIVE::ER_NOZIP' => 'Not a zip archive',
			'ZIPARCHIVE::ER_INTERNAL' => 'Internal error',
			'ZIPARCHIVE::ER_INCONS' => 'Zip archive inconsistent',
			'ZIPARCHIVE::ER_REMOVE' => 'Can\'t remove file',
			'ZIPARCHIVE::ER_DELETED' => 'Entry has been deleted',
		);
		$errmsg = 'unknown';
		foreach ($zipFileFunctionsErrors as $constName => $errorMessage) 
		{
			if (defined($constName) and constant($constName) === $errno) {
				$errmsg = $errorMessage;
				break;
			}
		}
		return 'Zip File Function error: '.$errmsg;
	}
	
	private function zipDownload($files)
	{
		$fileUFCtl = "";
		$zipFileMaster = new \ZipArchive();
		
		foreach ($files as $index => $fileUrl) {
			// echo 'Index: '.$index.' <=> '.$fileUrl.'<br>';
			$filePartsUrl = explode('/', $fileUrl, 2);	
			$filePartsBase = explode('/', $filePartsUrl[1], 2);	
			$fileSourcePath = $filePartsUrl[0];
			$fileParts = $filePartsBase[1];
			$fileUF = $filePartsBase[0];
			$fileBase = $fileSourcePath.'/'.$fileParts;
			
			$fileAux = explode('/', $fileParts);
			
			$fileCNPJRaiz = $fileAux[0];
			$fileCNPJ = $fileAux[1];
			$fileTipoTributo = $fileAux[2];
			$fileTributo = $fileAux[3];
			$fileApuracao = $fileAux[4];
			$fileArquivo = $fileAux[5];
						
			$fileDestinationPath = public_path('dowloads');
			$fileDestinationPathTemp = $fileDestinationPath.'/'.$fileCNPJRaiz;
			
			$zipFileName = $fileDestinationPathTemp.'/'.$fileApuracao.'_'.$fileCNPJRaiz.'_'.$fileUF.'_'.$fileTipoTributo.'_'.$fileTributo.'.zip';
			$zipFileNameMaster = $fileDestinationPathTemp.'/'.date('YmdHis').'_'.$fileApuracao.'_'.$fileCNPJRaiz.'_'.$fileTipoTributo.'_'.$fileTributo.'.zip';
			
			// echo 'File Source Path => '.$fileSourcePath.'<br>';
			// echo 'File Destination Path => '.$fileDestinationPath.'<br>';
			// echo 'UF => '.$fileUF.' :::: Arquivo => '.$fileBase.'<br>';
			// echo 'File Destination Path Temp => '.$fileDestinationPathTemp.'<br><br>';
			
			File::isDirectory($fileDestinationPathTemp) or File::makeDirectory($fileDestinationPathTemp, 0777, true, true);
			
			if (is_writeable(dirname($fileDestinationPathTemp))) {
				if (file_exists($fileBase) && is_readable($fileBase)) {
					if ($fileUFCtl == $fileUF || $fileUFCtl == "") {
						if ($zipFileMaster->open($zipFileName, \ZipArchive::CREATE) === TRUE) {
							
							$zipFileMaster->addFromString($fileCNPJRaiz.'/'.$fileUF.'/'.$fileCNPJ.'/'.$fileArquivo, $fileBase) or die ("ERROR: Could not add file string: $fileBase");
							
							$zipFileMaster->addFile(realpath($fileBase), $fileCNPJRaiz.'/'.$fileUF.'/'.$fileCNPJ.'/'.$fileArquivo) or die ("ERROR: Could not add file: $fileBase");
							
						}
					} else {
						$zipFileMaster->close();
						
						if ($zipFileMaster->open($zipFileName, \ZipArchive::CREATE) === TRUE) {
							
							$zipFileMaster->addFromString($fileCNPJRaiz.'/'.$fileUF.'/'.$fileCNPJ.'/'.$fileArquivo, $fileBase) or die ("ERROR: Could not add file string:: $fileBase");
							
							$zipFileMaster->addFile(realpath($fileBase), $fileCNPJRaiz.'/'.$fileUF.'/'.$fileCNPJ.'/'.$fileArquivo) or die ("ERROR: Could not add file:: $fileBase");
						}
					}
				}
				
				$fileUFCtl = $fileUF;
			} else {
				return redirect()->back()->witError('error', 'Não foi possivel criar o arquivo para download. O diretório de destino não possui permissão de escrita.');
			}
		}
		
		sleep(5);
		if($zipFileMaster->close() !== TRUE) trigger_error( "Unable to create master file, ".$fileBase,	E_USER_ERROR);
		sleep(5);
		
		$rootPath = realpath($fileDestinationPathTemp);
		
		if (is_writeable(dirname($rootPath))) {
			$zip = new \ZipArchive();
			$zip->open($zipFileNameMaster, \ZipArchive::CREATE);

			$filesToDelete = array();

			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($rootPath),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);
			
			foreach ($files as $name => $file) {
				if (!$file->isDir()) {
					$filePath = $file->getRealPath();
					$relativePath = substr($filePath, strlen($rootPath) + 1);

					$zip->addFile($filePath, $relativePath) or die ("ERROR: Could not add file: $filePath");

					if ($file->getFilename() != 'bravobpo.txt')	{
						$filesToDelete[] = $filePath;
					}
				}
			}
			
			sleep(5);
			$zip->close();
			sleep(5);
			
			foreach ($filesToDelete as $file) {
				unlink($file);
			}
			
			$this->ForceDown($zipFileNameMaster);
			
			return redirect()->back()->with('status', 'Arquivo gerado com sucesso!.');
		} else {
			return redirect()->back()->witError('error', 'Não foi possivel criar o arquivo para download. O diretório de destino não possui permissão de escrita.');
		}
	}

	private function calcPeriodo($inicio, $fim){
		$dataBusca['periodo_inicio'] = $inicio;
		$dataBusca['periodo_fim'] = $fim ;
		$dataExibe = array("periodo_inicio"=>$dataBusca['periodo_inicio'], "periodo_fim"=>$dataBusca['periodo_fim']);   
		
		$dataBusca['periodo_inicio'] = str_replace('/', '-', '01/'.$dataBusca['periodo_inicio']);
		$dataBusca['periodo_fim'] = str_replace('/', '-', '01/'.$dataBusca['periodo_fim']);
		list($dia, $mes, $ano) = explode( "-",$dataBusca['periodo_inicio']);
		$dataBusca['periodo_inicio'] = getdate(strtotime($dataBusca['periodo_inicio']));
		$dataBusca['periodo_fim'] = getdate(strtotime($dataBusca['periodo_fim']));
		$dif = ( ($dataBusca['periodo_fim'][0] - $dataBusca['periodo_inicio'][0]) / 86400 );
		$meses = round($dif/30)+1;  // +1 serve para adiconar a data fim no array

		for($x = 0; $x < $meses; $x++){
			$datas[] =  date("mY",strtotime("+".$x." month",mktime(0, 0, 0,$mes,$dia,$ano)));
		}

		return $datas;
	}

	public function downloadById($id,$uf='XX') {

		$atividade = Atividade::findOrFail($id);
		$tipo = $atividade->regra->tributo->tipo;
		$tipo_label = 'UNDEFINED';
		switch ($tipo) {
			case 'F':
				$tipo_label = 'FEDERAIS'; 
				break;
			case 'E':
				$tipo_label = 'ESTADUAIS'; 
				break;
			case 'M':
				$tipo_label = 'MUNICIPAIS'; 
				break;
		}

		@$destinationPath = $uf.'/'.substr($atividade->estemp->cnpj, 0, 8) . '/' . $atividade->estemp->cnpj .'/' .$tipo_label. '/' . $atividade->regra->tributo->nome . '/' . $atividade->periodo_apuracao . '/' . $atividade->arquivo_entrega; // upload path
		$headers = array(
			'Content-Type' => 'application/pdf',
		);

		$file_path = public_path('uploads/'.$destinationPath);
		return $file_path;
	}

	private function ForceDown($filepath)
	{
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"".basename($filepath)."\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize($filepath));
		ob_end_flush();
		@readfile($filepath);
		ignore_user_abort(true);
		if (connection_aborted()) {
			unlink($filepath);
		}
		unlink($filepath);
	}

	public function anyData(Request $request)
	{
		$user = User::findOrFail(Auth::user()->id);
		$seid = $this->s_emp->id;

		$atividades = Atividade::select(
			'atividades.*',
			'municipios.uf'
			)
			->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
			->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
			->where('emp_id', $seid)
			->with('regra')
			->with('regra.tributo')
			->with('estemp')
			->where('status', 3)
			->where('tipo_geracao', '=', 'A')
			->orderBy('data_entrega', 'desc');

        $with_user = function ($query) {
            $query->where('user_id', Auth::user()->id);
        };
        $tributos_granted = Tributo::select('id')->whereHas('users',$with_user)->get();
        $granted_array = array();
        foreach ($tributos_granted as $el) {
            $granted_array[] = $el->id;
        }

        $atividades = $atividades->whereHas('regra.tributo', function ($query) use ($granted_array) {
            $query->whereIn('tributos.id', $granted_array);
        });

		if($filter_cnpj =  preg_replace('/[^A-Za-z0-9]/', '', $request->get('cnpj'))){

			// if (substr($filter_cnpj, -6, 4) == '0001') {
			//     $estemp = Empresa::select('id')->where('cnpj', $filter_cnpj)->get();
			//     $type = 'emp';
			// } else {
				$estemp = Estabelecimento::select('id')->where('cnpj', $filter_cnpj)->where('empresa_id', $seid)->get();
				$type = 'estab';
			// }

			if (sizeof($estemp) > 0) {
				$atividades = $atividades->where('estemp_id', $estemp[0]->id)->where('estemp_type',$type);
			} else {
				$atividades = new Collection();
			}

		}

		if($filter_codigo = $request->get('codigo')){

			// if ($filter_codigo == '1001') {
			//     $estemp = Empresa::select('id')->where('codigo', $filter_codigo)->get();
			//     $type = 'emp';
			// } else {
				$estemp = Estabelecimento::select('id')->where('codigo', $filter_codigo)->where('empresa_id', $seid)->get();
				$type = 'estab';
			// }

			if (sizeof($estemp)>0) {
				$atividades = $atividades->where('estemp_id', $estemp[0]->id)->where('estemp_type',$type);
			} else {
				$atividades = new Collection();
			}

		}

		if($filter_tributo = $request->get('tributo')){

			$tributosearch = Tributo::select('id')->where('nome', 'like', '%'.$filter_tributo.'%')->get();

			if (sizeof($tributosearch)>0) {

				$atividades = $atividades->whereHas('regra.tributo', function ($query) use ($tributosearch) {
				$query->whereIn('id', $tributosearch);
			});

			} else {
				$atividades = new Collection();
			}
		}
		
		if($filter_uf = $request->get('uf')){
			$atividades = $atividades->where('municipios.uf', strtoupper($filter_uf));
		}
/*
		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str_filter = $request['search']['value'];
		}
*/

		return Datatables::of($atividades)->make(true);
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

	public function upload()
	{
		// getting all of the post data
		$file = array('image' => Input::file('image'));
		// checking file is valid.
		if (Input::file('image')->isValid()) {

			$atividade_id = $request->input('atividade_id');

			$atividade = Atividade::findOrFail($atividade_id);
			
			$destinationPath = 'uploads/'.$atividade_id; // upload path
			$extension = Input::file('image')->getClientOriginalExtension(); // getting image extension
			$fileName = time().'.'.$extension; // renameing image
			$fileName = preg_replace('/\s+/', '', $fileName); //clear whitespaces

			Input::file('image')->move($destinationPath, $fileName); // uploading file to given path

			//Save status
			$atividade->arquivo_comprovante = $fileName;
			$atividade->save();

			// sending back with message
			Session::flash('success', 'Upload successfully');
			return redirect()->route('arquivos.index')->with('status', 'Arquivo carregado com sucesso!');
		}
		else {
			// sending back with error message.
			Session::flash('error', 'Uploaded file is not valid');
			return redirect()->route('arquivos.index')->with('status', 'Erro ao carregar o arquivo.');
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
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		$atividade = Atividade::findOrFail($id);
		$destinationPath = '#';
		if ($atividade->status > 1) {
			$tipo = $atividade->regra->tributo->tipo;
			$tipo_label = 'UNDEFINED';
			switch($tipo) {
				case 'F':
					$tipo_label = 'FEDERAIS'; break;
				case 'E':
					$tipo_label = 'ESTADUAIS'; break;
				case 'M':
					$tipo_label = 'MUNICIPAIS'; break;
			}
			$destinationPath = url('uploads') .'/'. substr($atividade->estemp->cnpj, 0, 8) . '/' . $atividade->estemp->cnpj . '/' . $tipo_label . '/' . $atividade->regra->tributo->nome . '/' . $atividade->periodo_apuracao . '/' . $atividade->arquivo_entrega; // upload path
		}

		$dadosOriginais = json_decode(json_encode(DB::select('Select A.*, B.name as entregador, C.name as aprovador from atividades A left join users B on A.usuario_entregador = B.id left join users C on A.usuario_aprovador = C.id where A.retificacao_id = '.$atividade->id.';')), true);
	   
		if (empty($dadosOriginais)) {
			$dadosOriginais = false;
		}

		return view('arquivos.show')->withAtividade($atividade)->withDownload($destinationPath)->with('dadosOriginais', $dadosOriginais);
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
		//
	}
}
