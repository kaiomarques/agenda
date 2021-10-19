<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Atividade;
use App\Models\CronogramaAtividade;
use App\Http\Requests;
use Auth;
use DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class UploadsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function entrega($atividade_id) {

        $usuario = User::findOrFail(Auth::user()->id);
        //$atividade = Atividade::findOrFail($atividade_id);
        $atividade = Atividade::with(['comentarios'])->findOrFail($atividade_id);

        return view('entregas.upload')->withUser($usuario)->withAtividade($atividade);
	}
	
    public function comentarios($atividade_id) {

        $usuario = User::findOrFail(Auth::user()->id);
        //$atividade = Atividade::findOrFail($atividade_id);
        $atividade = Atividade::with(['comentarios'])->findOrFail($atividade_id);

        return view('entregas.comentarios')->withUser($usuario)->withAtividade($atividade);
    }

    public function entregaCronograma($atividade_id) {

        $usuario = User::findOrFail(Auth::user()->id);
        //$atividade = Atividade::findOrFail($atividade_id);
        $atividade = CronogramaAtividade::findOrFail($atividade_id);

        return view('entregas.upload-cronograma')->withUser($usuario)->withAtividade($atividade);
    }

    public function entregaCronogramaData($data_atividade) {
        $usuario = User::findOrFail(Auth::user()->id);
        $atividadesFiltered = array();

// a.data_entrega AS DataEntrega,
// a.data_aprovacao AS DataAprovacao
	    
        $query = 'Select A.*, C.nome as tributo, E.uf, F.name, G.id as id_atividade, H.name as usuario, G.status as status, G.data_entrega as data_entrega, G.data_aprovacao as data_aprovacao, D.codigo
									from cronogramaatividades A
									left join regras B on A.regra_id = B.id
									left join tributos C on B.tributo_id = C.id
									left join estabelecimentos D on A.estemp_id = D.id
									left join municipios E on D.cod_municipio = E.codigo
									left join users F on A.Id_usuario_analista = F.id
									left join atividades G on G.regra_id = B.id and G.estemp_id = D.id and G.periodo_apuracao = A.periodo_apuracao
									left join users H on H.id = A.Id_usuario_analista
									where DATE_FORMAT(A.data_atividade, "%Y-%m-%d") = "'.$data_atividade.'"
									-- and DATE_FORMAT(G.data_entrega , "%Y-%m-%d") >= "'.$data_atividade.'"';
				// echo $query; exit;
        if ($usuario->hasRole('analyst')){
            $query .= ' AND A.Id_usuario_analista ='.$usuario->id;
        }   

        $atividades = DB::select($query);
	
      $tributos = [];
	    if (!empty($atividades)) {
		    foreach ($atividades as $key => $atividade) {
					if(!in_array($atividade->tributo, $tributos)){
						$tributos[$atividade->tributo] = [
							'tributo' => $atividade->tributo
						];
					}
		    }
	    }
	
	    if (!empty($atividades)) {
		    foreach ($atividades as $key => $atividade) {
		    	
		    	$atrasado = false;
			    $dataAtividade = new \DateTime($atividade->data_atividade);
			    
			    if(in_array($atividade->status, [1,2]) && strtotime($dataAtividade->format('Y-m-d')) < strtotime(date('Y-m-d'))){
				    $atrasado = true;
			    }
			    if($atividade->status == 3 && !empty($atividade->data_aprovacao)){
				    $dataAprovacao = new \DateTime($atividade->data_aprovacao);
				    if(strtotime($dataAprovacao->format('Y-m-d')) > strtotime($dataAtividade->format('Y-m-d'))){
					    $atrasado = true;
				    }
			    }
		    	$atividade->atrasado = $atrasado;
		    	
			    if($tributos[$atividade->tributo]['tributo'] == $atividade->tributo){
				    $tributos[$atividade->tributo]['atrasado_trib'] = $atrasado;
				    $tributos[$atividade->tributo]['atividades'][] = $atividade;
				    
				    if($tributos[$atividade->tributo]['atrasado_trib'] == false){
					    $tributos[$atividade->tributo]['atrasado_trib'] = $atrasado;
				    }
			    }
		    }
	    }
//	    echo '<pre>', print_r($tributos); exit;
        
        /*if (!empty($atividades)) {
            foreach ($atividades as $key => $atividade) {
                $a = strtotime(substr($atividade->limite, 0,10));
                $b = strtotime(substr($atividade->data_atividade, 0,10));
                $c = $a-$b;
                if ($c < 0) {
                    $atividadesFiltered[$atividade->tributo][$atividade->uf][$atividade->status]['PrazoEstourado'][] = $atividade;
                } else {
                    $atividadesFiltered[$atividade->tributo][$atividade->uf][$atividade->status]['Prazo'][] = $atividade;
                }
            }
        }*/

    return view('entregas.upload-cronograma-data')->withUser($usuario)->with('atividades', $tributos);
    }

    public function upload() {
        // getting all of the post data
        $file = array('image' => Input::file('image'));
        // setting up rules
        $rules = array('image' => 'required|mimes:pdf,zip'); //mimes:jpeg,bmp,png and for max size max:10000
        // doing the validation, passing post data, rules and the messages
        $validator = Validator::make($file, $rules);
        if ($validator->fails()) {
            // send back to the page with the input data and errors
            Session::flash('error', 'Somente arquivos ZIP ou PDF são aceitos.');
            $atividade_id = $request->input('atividade_id');
            return Redirect::to('upload/'.$atividade_id.'/entrega')->withInput()->withErrors($validator);
        }
        else {
            // checking file is valid.
            if (Input::file('image')->isValid()) {
                $atividade_id = $request->input('atividade_id');
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
                $destinationPath = 'uploads/'.substr($estemp->cnpj,0,8).'/'.$estemp->cnpj.'/'.$tipo_label.'/'.$regra->tributo->nome.'/'.$atividade->periodo_apuracao; // upload path
                $extension = Input::file('image')->getClientOriginalExtension(); // getting image extension
                $fileName = time().'.'.$extension; // renameing image
                $fileName = preg_replace('/\s+/', '', $fileName); //clear whitespaces
                Input::file('image')->move($destinationPath, $fileName); // uploading file to given path

                //Save status
                $atividade->arquivo_entrega = $fileName;
                $atividade->usuario_entregador = Auth::user()->id;
                $atividade->data_entrega = date("Y-m-d H:i:s");
                $atividade->status = 2;
                $atividade->save();
                // sending back with message
                Session::flash('success', 'Upload successfully');
                return redirect()->route('entregas.index')->with('status', 'Arquivo carregado com sucesso!');
            }
            else {
                // sending back with error message.
                Session::flash('error', 'Uploaded file is not valid');
                return redirect()->route('entregas.index')->with('status', 'Erro ao carregar o arquivo.');
            }
        }
    }    

    public function uploadCron() {
        // getting all of the post data
        $file = array('image' => Input::file('image'));
        // setting up rules
        $rules = array('image' => 'required|mimes:pdf,zip'); //mimes:jpeg,bmp,png and for max size max:10000
        // doing the validation, passing post data, rules and the messages
        $validator = Validator::make($file, $rules);
        if ($validator->fails()) {
            // send back to the page with the input data and errors
            Session::flash('error', 'Somente arquivos ZIP ou PDF são aceitos.');
            $atividade_id = $request->input('atividade_id');
            return Redirect::to('uploadCron/'.$atividade_id.'/entrega')->withInput()->withErrors($validator);
        }
        else {
            // checking file is valid.
            if (Input::file('image')->isValid()) {
                $atividade_id = $request->input('atividade_id');
                $atividade = CronogramaAtividade::findOrFail($atividade_id);
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
                $destinationPath = 'uploads/'.substr($estemp->cnpj,0,8).'/'.$estemp->cnpj.'/'.$tipo_label.'/'.$regra->tributo->nome.'/'.$atividade->periodo_apuracao; // upload path
                $extension = Input::file('image')->getClientOriginalExtension(); // getting image extension
                $fileName = time().'.'.$extension; // renameing image
                $fileName = preg_replace('/\s+/', '', $fileName); //clear whitespaces
                Input::file('image')->move($destinationPath, $fileName); // uploading file to given path

                //Save status
                $atividade->arquivo_entrega = $fileName;
                $atividade->usuario_entregador = Auth::user()->id;
                $atividade->data_entrega = date("Y-m-d H:i:s");
                $atividade->status = 2;
                $atividade->save();
                // sending back with message
                Session::flash('success', 'Upload successfully');
                return redirect()->route('entregas.index')->with('status', 'Arquivo carregado com sucesso!');
            }
            else {
                // sending back with error message.
                Session::flash('error', 'Uploaded file is not valid');
                return redirect()->route('entregas.index')->with('status', 'Erro ao carregar o arquivo.');
            }
        }
    }

}
