<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Tributo;
use Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Atividade;
use App\Http\Requests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Input;
use Yajra\Datatables\Datatables;
use Session;
use Response;
use date;



class EntregasController extends Controller
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
		return view('entregas.index')
			->with('filter_cnpj', $request->input("vcn"))
			->with('filter_codigo', $request->input("vco"))
			->with('filter_status', $request->input("vst"))
			->with('filter_uf', $request->input("vuf"))
			->with('filter_tributo', $request->input("vtb"));
	}

	public function anyData(Request $request)
	{
		

	
		$user = User::findOrFail(Auth::user()->id);
		$seid = $this->s_emp->id;


           $response = Atividade::select(
              'atividades.id',
              'atividades.descricao',
              'atividades.periodo_apuracao',
              'atividades.regra_id',
              'atividades.status',
              'atividades.limite',
              'atividades.estemp_id',
              'atividades.emp_id',
              'atividades.data_entrega',
              'atividades.estemp_type',
              'municipios.uf',
              'estabelecimentos.cnpj',
              'estabelecimentos.codigo',
              'estabelecimentos.cod_municipio',
              'empresas.cliente_aprova'
            )
           ->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
           ->join('empresas', 'empresas.id', '=', 'atividades.emp_id')
            ->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
            ->where('emp_id', $seid)
            ->with('regra')
            ->with('regra.tributo')
            ->with('entregador')
            ->with('entregador.roles')
            ->with('estemp')
            ->orderBy('data_entrega', 'desc');

	
		if ($user->hasRole('owner') || $user->hasRole('admin') ) {
			$response = $response->whereIn('status', [1, 2]);
			// ->whereHas('entregador.roles', function ($query) {
			//     $query->where('name', 'supervisor');
			// })
		} else if ($user->hasRole('supervisor')) {

			$with_user = function ($query) {
				$query->where('user_id', Auth::user()->id);
			};
			$tributos_granted = Tributo::select('id')->whereHas('users',$with_user)->get();
			$granted_array = array();
			foreach($tributos_granted as $el) {
				$granted_array[] = $el->id;
			}

			$response = $response->where('atividades.status','<=', 2)->whereHas('regra.tributo', function ($query) use ($granted_array) {
				$query->whereIn('atividades.id', $granted_array);
			});
        } else if ($user->hasRole('gcliente') && session('Empresa')->cliente_aprova == 'S') {
            $response = $response->where('status','=', 3)->where('status_cliente','=', 'P');
		} else {

			$response = $response->where('atividades.status','<',3);  //whereHas('users', $with_user)

		}

		if($request->get('tributo') != ""){


	            
	            $response = $response->join('regras','atividades.regra_id','=','regras.id');
	            $response = $response->join('tributos' , 'regras.tributo_id' , '=', 'tributos.id');
	            $response = $response->where('tributos.nome',$request->get('tributo'));
	            
			

		}

		if($request->get('cnpj') != ""){

			// if (substr($filter_cnpj, -6, 4) == '0001') {
			//     $estemp = Empresa::select('id')->where('cnpj', $filter_cnpj)->get();
			//     $type = 'emp';
			// } else {
				$estemp = Estabelecimento::select('id')->where('cnpj', $request->get('cnpj'))->get();
				$type = 'estab';
			// }

			if (sizeof($estemp) > 0) {
				$response = $response->where('atividades.estemp_id', $estemp[0]->id)->where('atividades.estemp_type',$type);
			} else {
				$response = new Collection();
			}

		}

		if($request->get('codigo') != "") {

			// if ($filter_codigo == '1001') {
			//     $estemp = Empresa::select('id')->where('codigo', $filter_codigo)->get();
			//     $type = 'emp';
			// } else {
				$estemp = Estabelecimento::select('id')->where('codigo','=',$request->get('codigo'))->get();
				$type = 'estab';
			// }

			if (sizeof($estemp)>0) {
				$response = $response->where('atividades.estemp_id', $estemp[0]->id)->where('estemp_type',$type);
			} else {
				$response = new Collection();
			}

		}

		if($request->get('status_filter') != ""){

			if ($request->get('status_filter') == 'E') {
				$response = $response->where('atividades.status', 1);
			}
			else if ($request->get('status_filter')== 'A') {
				$response = $response->where('atividades.status', 2);
			}
		}
		
		if($request->get('uf') != ""){
			$response = $response->where('municipios.uf', strtoupper($request->get('uf')));
		}

		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str_filter = $request['search']['value'];
		}

         set_time_limit(0);

		
         $result = Datatables::of($response)->make(true);
 		
 		return $result;

	
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

		return view('entregas.show')->withAtividade($atividade)->withDownload($destinationPath);
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
		$atividade = Atividade::findOrFail($id);
		$input = $request->all();
		$atividade->obs = $input['obs'];

		$atividade->save();

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
		//
	}

	#created André 30/02/2020
	public function downloadCSV(Request $request){

		error_reporting(E_ALL);
		
		$user          = User::findOrFail(Auth::user()->id);
		$seid          = $this->s_emp->id;


		$codigo        = $_REQUEST['codigo'];
		$status_filter = $_REQUEST['status_filter'];
		$uf            = $_REQUEST['uf'];
		$cnpj          = $_REQUEST['cnpj'];
		$tributo       = $_REQUEST['tributo'];

		$file          = "";

		if($codigo !="" or $status_filter !="" or $uf != "" or $cnpj != "" or $tributo != "" ){

           $response = Atividade::select(
              'atividades.id',
              'atividades.descricao',
              'atividades.periodo_apuracao',
              'atividades.regra_id',
              'atividades.status',
              'atividades.limite',
              'atividades.estemp_id',
              'atividades.emp_id',
              'atividades.data_entrega',
              'atividades.estemp_type',
              'municipios.uf',
              'estabelecimentos.cnpj',
              'estabelecimentos.codigo',
              'estabelecimentos.cod_municipio'
            )
            ->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
            ->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
            ->join('regras','atividades.regra_id','=','regras.id')
            ->join('tributos' , 'regras.tributo_id' , '=', 'tributos.id')
            ->where('emp_id', $seid)
            ->with('regra')
            ->with('regra.tributo')
            ->with('entregador')
            ->with('entregador.roles')
            ->with('estemp')
            ->orderBy('data_entrega', 'desc');



	
		if ($user->hasRole('owner') || $user->hasRole('admin') ) {
			$response = $response->whereIn('status', [1, 2]);
			// ->whereHas('entregador.roles', function ($query) {
			//     $query->where('name', 'supervisor');
			// })
		} else if ($user->hasRole('supervisor')) {

			$with_user = function ($query) {
				$query->where('user_id', Auth::user()->id);
			};
			$tributos_granted = Tributo::select('id')->whereHas('users',$with_user)->get();
			$granted_array = array();
			foreach($tributos_granted as $el) {
				$granted_array[] = $el->id;
			}

			$response = $response->where('atividades.status','<=', 2)->whereHas('regra.tributo', function ($query) use ($granted_array) {
				$query->whereIn('atividades.id', $granted_array);
			});

		} else {

			$response = $response->where('atividades.status','<',3);  //whereHas('users', $with_user)

		}


		if($tributo != "" ){

            $response = $response->where('tributos.nome',$tributo);

		}

		if($cnpj != ""){

			// if (substr($filter_cnpj, -6, 4) == '0001') {
			//     $estemp = Empresa::select('id')->where('cnpj', $filter_cnpj)->get();
			//     $type = 'emp';
			// } else {
				$estemp = Estabelecimento::select('id')->where('cnpj', $cnpj)->get();
				$type = 'estab';
			// }

			if (sizeof($estemp) > 0) {
				$response = $response->where('atividades.estemp_id', $estemp[0]->id)->where('atividades.estemp_type',$type);
			} else {
				$response = new Collection();
			}

		}

		if($codigo != "" ){

			// if ($filter_codigo == '1001') {
			//     $estemp = Empresa::select('id')->where('codigo', $filter_codigo)->get();
			//     $type = 'emp';
			// } else {
				$estemp = Estabelecimento::select('id')->where('codigo','=',$codigo)->get();
				$type = 'estab';
			// }

			if (sizeof($estemp)>0) {
				$response = $response->where('atividades.estemp_id', $estemp[0]->id)->where('estemp_type',$type);
			} else {
				$response = new Collection();
			}

		}

		if($status_filter != ""){

			if ($status_filter == 'E') {
				$response = $response->where('atividades.status', 1);
			}
			else if ($status_filter == 'A') {
				$response = $response->where('atividades.status', 2);
			}
		}
		
		if($uf != "" ){
			$response = $response->where('municipios.uf', strtoupper($uf));
		}

		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str_filter = $request['search']['value'];
		}

         set_time_limit(0);


         #created André 31/01/2019
         #$item["item"] = $response;
         $response     = $response->take(20000000)->get();
         #$item["item"] = Datatables::of($item["item"])->make(true);
         #$item         = json_decode(json_encode($item["item"]->getData()),true);




         $data = $response;

         #echo "<pre>" ,print_r($data);

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
		 #path save csv
		 $savePath =  storage_path("csv\\");
		 $savePath = str_replace("storage\\csv\\", "", $savePath);
		 $savePath = $savePath . "public\\assets\\download\\csv\\";		
		} else {
		 #path save csv
		 $savePath =  storage_path("csv/");
		 $savePath = str_replace("storage/csv/", "", $savePath);
		 $savePath = $savePath . "public/assets/download/csv/";
		}


		 if (is_dir($savePath)) {
		 		
		      $datetime  = stat($savePath);

		      $datetime  = $datetime[9];
		      $lastDate  = date('d-m-Y', $datetime);
		      $date      = date('d-m-Y');


		      if ($lastDate <  date('d-m-Y')) 
		         $resp = $this->delete_files($savePath, TRUE);#colocar metodo helper
		        


		 }

		
		 #get date and hour
		 $hourfile = date('H-i-s');
    	 $datefile = date('Y-m-d') . '_' . $hourfile;

	     #name file
   		 $fileCSV = 'ENTREGAS_' .  $datefile;



	     #permission file 	
	     if (!file_exists($fileCSV))
	      shell_exec(" chmod 777 -R $savePath ");


	  	 $file = $savePath . $fileCSV.".csv"; 	

	  	 
         $h = fopen($file, 'w+');

         fwrite($h, '"ID";');
         fwrite($h, '"DESCRICAO";');
         fwrite($h, '"TRIBUTO";');
         fwrite($h, '"P.A.";');
         fwrite($h, '"ENTREGA";');
         fwrite($h, '"F.P.";');
         fwrite($h, '"CNPJ";');
         fwrite($h, '"COD";');
         fwrite($h, '"UF";');
         fwrite($h, '"STATUS";');
         fwrite($h, "\r\n");



         foreach ($data as  $value) {



         	fwrite($h, '"' . $value->getAttributes()["id"] . '";');         	
         	fwrite($h, '"' . $value->getAttributes()["descricao"] . '";');
         	fwrite($h, '"' . $value->getRelations()["regra"]["tributo"]["nome"] . '";');
         	fwrite($h, '"' . $value->getAttributes()["periodo_apuracao"] . '";');
					
			

			if($value->getAttributes()["data_entrega"] != "0000-00-00 00:00:00"){
				$date = date_create($value->getAttributes()["data_entrega"]);
				fwrite($h, '"' . date_format($date,"d/m/Y") . '";');		
			}else{
				  fwrite($h, '"-";');
			}
         	
         	

         	

         	if(Auth::user()->hasRole('analyst')){
			  
			  $date = date_create($value->getAttributes()["limite"]);
         	  fwrite($h, '"' . date_format($date) . '";');

         	}else{

				$date1  = $value->getAttributes()["limite"];  
				$date2  = $value->getAttributes()["data_entrega"]; 
				
				#echo($date1)."- data1 <br>";
				#echo($date2)."- data2 <br>";

				$dStart = date_create($date1);
				$dEnd   = date_create($date2);
				$dDiff  = $dStart->diff($dEnd);
				$days   = $dDiff->format('%R%a');
				$days   = $days + 1;



         		if ($days > 1 ) {
         		  fwrite($h, '"' . $days ." dias" .'";');
  			    } else {
  			      fwrite($h, '"-";');
										
				}
         	}
         	

         	fwrite($h, '"' . $value->getAttributes()["cnpj"] . '";');
         	fwrite($h, '"' . $value->getAttributes()["codigo"] . '";');
         	fwrite($h, '"' . $value->getAttributes()["uf"] . '";');
			
			#rules get file: view/entregas/index.blade.php
			switch ($value["status"]) {
			    case "1":
			        if($value["regra"]["tributo"]["tipo"]=="R")
						fwrite($h, '"' . "Entregar" . '";');        	
			        else
			        	fwrite($h, '"' . "Comentário" . '";');


			        break;
			    case "2":
			        if ( Auth::user()->hasRole('admin') || Auth::user()->hasRole('owner') || Auth::user()->hasRole('supervisor'))
			        	fwrite($h, '"' . "Em Aprovação" . '";');
			        else
			        	fwrite($h, '"' . "Em Aprovação" . '";');		

			        break;
			    case "3":

			        fwrite($h, '"' . "Recibo" . '";');
			    
			        break;
			}

         	

         	fwrite($h, "\r\n");

         	
         }

         fclose($h);
        
     }

        echo json_encode($file);

	}


	function delete_files($path, $del_dir = FALSE, $htdocs = FALSE, $_level = 0)
	{
		// Trim the trailing slash
		$path = rtrim($path, '/\\');

		if ( ! $current_dir = @opendir($path))
		{
			return FALSE;
		}

		while (FALSE !== ($filename = @readdir($current_dir)))
		{
			if ($filename !== '.' && $filename !== '..')
			{
				$filepath = $path.DIRECTORY_SEPARATOR.$filename;

				if (is_dir($filepath) && $filename[0] !== '.' && ! is_link($filepath))
				{
					delete_files($filepath, $del_dir, $htdocs, $_level + 1);
				}
				elseif ($htdocs !== TRUE OR ! preg_match('/^(\.htaccess|index\.(html|htm|php)|web\.config)$/i', $filename))
				{
					@unlink($filepath);
				}
			}
		}

		closedir($current_dir);

		return ($del_dir === TRUE && $_level > 0)
			? @rmdir($path)
			: TRUE;
	}


}
