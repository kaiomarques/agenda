<?php

namespace App\Http\Controllers;

use App\Models\Comentario;
use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\Tributo;
use App\Models\Regra;
use App\Models\User;
use App\Models\Regraenviolote;
use App\Models\Empresa;
use App\Models\googl;

use App\Services\EntregaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Models\Atividade;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\DB;
use App\Http\Requests;


class AtividadesController extends Controller
{
    protected $eService;

    function __construct(EntregaService $service)
    {
        $this->middleware('auth');
        $this->eService = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('atividades.index')->with('filter_cnpj',$request->input("vcn"))->with('filter_codigo',$request->input("vco"));
    }

    public function anyData(Request $request)
    {
        $atividades = Atividade::select('*')
                        ->with('regra')->with('regra.tributo')->with('estemp')
                        ->orderBy('status','asc')->orderBy('limite','asc');

        if($filter_cnpj = $request->get('cnpj')){

            if (substr($filter_cnpj, -6, 4) == '0001') {
                $estemp = Empresa::select('id')->where('cnpj', $filter_cnpj)->get();
                $type = 'emp';
            } else {
                $estemp = Estabelecimento::select('id')->where('cnpj', $filter_cnpj)->get();
                $type = 'estab';
            }

            if (sizeof($estemp) > 0) {
                $atividades = $atividades->where('estemp_id', $estemp[0]->id)->where('estemp_type', $type);
            } else {
                $atividades = new Collection();
            }

        }

        if($filter_codigo = $request->get('codigo')){

            if ($filter_codigo == '1001') {
                $estemp = Empresa::select('id')->where('codigo', $filter_codigo)->get();
                $type = 'emp';
            } else {
                $estemp = Estabelecimento::select('id')->where('codigo','like','%'.$filter_codigo)->get();
                $type = 'estab';
            }

            if (sizeof($estemp)>0) {
                $atividades = $atividades->where('estemp_id', $estemp[0]->id)->where('estemp_type',$type);
            } else {
                $atividades = new Collection();
            }

        }

        if ( isset($request['search']) && $request['search']['value'] != '' ) {
            $str_filter = $request['search']['value'];
        }

        return Datatables::of($atividades)->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $usuarios = User::selectRaw("concat(name, ' - ( ', email, ' )') as nome_e_mail, id")->pluck('nome_e_mail', 'id');
        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $regras = [''=>''];
        $ufs = Municipio::selectRaw("uf, uf")->orderby('uf','asc')->pluck('uf','uf'); //Unidades Federais
        $municipios = [''=>''];

        return view('atividades.create')->with('usuarios', $usuarios)
                                        ->with('tributos',$tributos)
                                        ->with('regras',$regras)
                                        ->with('ufs',$ufs)
                                        ->with('municipios',$municipios);
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

        Atividade::create($input);

        return redirect()->route('atividades.index')->with('status', 'Atividade adicionada com sucesso!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $aprovacao = false;
        $aprovacao_referer = $_SERVER['HTTP_REFERER'];
        $pos = strpos( $aprovacao_referer, 'aprovacao' );
        if ($pos) {
            $aprovacao = true;
        }
        
        $atividade = Atividade::findOrFail($id);

        $destinationPath = '#';

        if ($atividade->tipo_geracao == 'R') {
            $atividade = Atividade::findOrFail($atividade->retificacao_id);
        }

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
        return view('atividades.show')->withAtividade($atividade)->withDownload($destinationPath)->with('aprovacao', $aprovacao);

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

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $atividade = Atividade::findOrFail($id);

        if (sizeof($atividade->retificacoes)>0 || $atividade->status>1) {
            return redirect()->route('atividades.index')->with('error', 'Atividade já entregue, impossível cancelar!');
        } else {
            $atividade->delete();
        }


        return redirect()->route('atividades.index')->with('status', 'Atividade cancelada com sucesso!');
    }

    public function storeComentario(Request $request)
    {
        $this->validate($request, [
                'obs' => 'required|max:120'
            ],
            $messages = [
                'required' => 'Comentário mandatório.',
                'max' => 'Max 120 caracteres'
            ]);

        $input = $request->all();
		
		if($request->input('com')) {
            Comentario::create($input);
            return redirect()->back()->with('status', 'Comentario adicionado com sucesso!');

        } else if ($request->input('esd')) {
            Comentario::create($input);
            $atividade_id = $request->input('atividade_id');
            $atividade = Atividade::findOrFail($atividade_id);
            $atividade->usuario_entregador = Auth::user()->id;
            $atividade->data_entrega = date("Y-m-d H:i:s");
            $atividade->status = 2;
            $atividade->save();
            // sending back with message

            return redirect()->route('entregas.index')->with('status', 'Atividade encaminhada com sucesso, sem documentação.');

		} else if ($request->input('bpo')) {
            Comentario::create($input);
            $atividade_id = $request->input('atividade_id');
            $atividade = Atividade::findOrFail($atividade_id);
            $atividade->usuario_entregador = Auth::user()->id;
            $atividade->data_entrega = date("Y-m-d H:i:s");
            $atividade->status = 2;
            $atividade->save();
            // sending back with message

            return redirect()->back()->with('status', 'Comentario adicionado com sucesso!');

        } else {
            return redirect()->back()->with('error', 'Operação inválida!');
		}
		
        if($request->input('com')) {
            Comentario::create($input);
            $atividade_id = $request->input('atividade_id');
            $atividade = Atividade::findOrFail($atividade_id);
            $atividade->usuario_entregador = Auth::user()->id;
            $atividade->data_entrega = date("Y-m-d H:i:s");
            $atividade->status = 2;
            $atividade->save();
            // sending back with message

            return redirect()->back()->with('status', 'Comentario adicionado com sucesso!');

        } else {
            return redirect()->back()->with('error', 'Operação inválida!');
        }

    }

    public function retificar($id)
    {
        $atividade = Atividade::findOrFail($id);
        foreach($atividade->retificacoes as $el) {
            if ($el->status<3) {
                Session::flash('message', 'Atividade de retificação já em aberto!');
                return redirect()->route('arquivos.show',$atividade->id);
            }
        }
        $retificacao = new Atividade;

        $retificacao->descricao = str_replace('Entrega','Retificacao',$atividade->descricao);
        $retificacao->recibo = $atividade->recibo;
        $retificacao->status = 1;
        $retificacao->regra_id = $atividade->regra_id;
        $retificacao->emp_id = $atividade->emp_id;
        $retificacao->estemp_id = $atividade->estemp_id;
        $retificacao->estemp_type = $atividade->estemp_type;
        $retificacao->periodo_apuracao = $atividade->periodo_apuracao;
        $retificacao->inicio_aviso = $atividade->inicio_aviso;
        $retificacao->limite = $atividade->limite;
        $retificacao->tipo_geracao = 'R';
        $retificacao->arquivo_entrega = '-';
        $retificacao->retificacao_id = $atividade->id;

        $retificacao->save();
        $lastInsertedId= $retificacao->id;

        /* NOTIFICAÇÃO */
        $user = User::findOrFail(Auth::user()->id);
        $entregador = User::findOrFail($atividade->usuario_entregador);
        $subject = "BravoTaxCalendar - Pedido retificação atividade";
        $data = array('subject'=>$subject,'messageLines'=>array());
        $data['messageLines'][] = ' Foi efetuado um pedido de retificação para a "'.$atividade->descricao.' - COD.'.$atividade->estemp->codigo.'".';
        $data['messageLines'][] = 'Coordenador: '.$user->name;
        
        $var = DB::select("select B.razao_social, C.cnpj, C.codigo from atividades A inner join empresas B on A.emp_id = B.id inner join estabelecimentos C on A.estemp_id = C.id where A.id = ".$id."");
        
        $var = json_decode(json_encode($var),true);
        foreach ($var as $t) {
        }
        
        $data['messageLines'][] = 'Empresa: '. $t['razao_social'].' - CNPJ: '. $t['cnpj'] . ' Código da área: '.$t['codigo'];
        $this->eService->sendMail($entregador, $data, 'emails.notification-aprovacao');

        return redirect()->route('entregas.index')->with('status', 'Atividade ('.$lastInsertedId.') de retificação gerada com sucesso.');

    }

    public function aprovar($id)
    {
        $atividade = Atividade::findOrFail($id);
        $atividade->status = 3;
        $atividade->status_cliente = 'P';
        $atividade->usuario_aprovador = Auth::user()->id;
        $atividade->data_aprovacao = date("Y-m-d H:i:s");

        $regra = Regraenviolote::where('id_empresa', $atividade->emp_id)->where('id_tributo', $atividade->regra->tributo_id)->get();
        if (count($regra) > 0 && $regra[0]->envioaprovacao == 'S') {
            $this->sendMail($atividade);
        }
        $atividade->save();
        return redirect()->route('entregas.index')->with('status', 'Atividade aprovada com sucesso!');
    }

    public function aprovar_cliente($id)
    {
        $atividade = Atividade::findOrFail($id);
        $atividade->status_cliente = 'A';
        $atividade->cliente_aprovador = Auth::user()->id;
        $atividade->data_aprovacao_cliente = date("Y-m-d H:i:s");

        $atividade->save();

        $subject = "Bravo Plataforma – Fiscal :: Entrega atividade --APROVADA POR CLIENTE--";
        $data = array('subject'=>$subject,'messageLines'=>array());

        $var = DB::select(
            "SELECT B.razao_social, 
		 			E.cnpj, 
		 			E.codigo, 
		 			CONCAT(SUBSTRING(A.periodo_apuracao, 1, 2),'/',SUBSTRING(A.periodo_apuracao, 3, 4)) AS periodo_apuracao,
		 			(SELECT obs FROM comentarios WHERE comentarios.atividade_id = A.id ORDER BY id DESC LIMIT 1) AS motivo,
              		u.name as cliente_aprovador_nome,
              		A.usuario_aprovador
			   FROM atividades A 
		 INNER JOIN empresas B ON A.emp_id = B.id
		 INNER JOIN estabelecimentos E ON A.estemp_id = E.id
         INNER JOIN users u ON u.id = cliente_aprovador
		 	  WHERE A.id =  ".$id
        );

        $var = json_decode(json_encode($var),true);

        foreach ($var as $t) {
            $usuario = User::findOrFail($t['usuario_aprovador']);

            $data['messageLines'][] = $atividade->descricao.' - COD. '.$atividade->estemp->codigo.' - P.A. '.$t['periodo_apuracao'].' - Aprovada pelo cliente ('.$t["cliente_aprovador_nome"].').';
            $data['messageLines'][] = 'Empresa: '. $t['razao_social'].' - CNPJ: '. $t['cnpj'] . ' Código da área: '.$t['codigo'];

            $data['linkDownload'] = $this->generateLinks($atividade);

            $this->eService->sendMail($usuario, $data, 'emails.notification-aprovacao');
        }

        return redirect()->route('entregas.index')->with('status', 'Atividade aprovada com sucesso!');
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

    private function generateLinks($atividade) {
        $download_link = false;

        $server_name    = $_SERVER['SERVER_NAME'];
        $document_root  = $_SERVER['DOCUMENT_ROOT'];

        $termo = 'agenda';
        $pattern = '/' . $termo . '/';

        if (!preg_match($pattern, $_SERVER['SERVER_NAME'])) {
            $server_name    = $_SERVER['SERVER_NAME'].'/agenda/public';
            $document_root  = $_SERVER['DOCUMENT_ROOT'].'/agenda/public';
        }

        $path_link = "http://".$server_name."/uploads/".substr($atividade->empresa->cnpj, 0, 8)."/".$atividade->estemp->cnpj."";
        $path = "".$document_root."/uploads/".substr($atividade->empresa->cnpj, 0, 8)."/".$atividade->estemp->cnpj."";
        $tipo = $this->getTipo($atividade->regra->tributo->tipo);
        $ult_periodo_apuracao = $atividade->periodo_apuracao;
        $path .= '/'.$tipo.'/'.$atividade->regra->tributo->nome.'/'.$ult_periodo_apuracao.'/'.$atividade->arquivo_entrega;
        $path_link .= '/'.$tipo.'/'.$atividade->regra->tributo->nome.'/'.$ult_periodo_apuracao.'/'.$atividade->arquivo_entrega;

        if (file_exists($path)) {
            $array = array();
            $array['texto'] = $atividade->estemp->razao_social.' - '. $atividade->regra->tributo->nome;
            $array['link'] = $path_link;
            $download_link[] = $array;
        }

        return $download_link;
    }

    private function sendMail($atividade)
    {
        $server_name    = $_SERVER['SERVER_NAME'];
        $document_root  = $_SERVER['DOCUMENT_ROOT'];
            
        $termo = 'agenda';
        $pattern = '/' . $termo . '/';
        
        if (!preg_match($pattern, $_SERVER['SERVER_NAME'])) {
          $server_name    = $_SERVER['SERVER_NAME'].'/agenda/public';
          $document_root  = $_SERVER['DOCUMENT_ROOT'].'/agenda/public';
        }   

        $path_link = "http://".$server_name."/uploads/".substr($atividade->empresa->cnpj, 0, 8)."/".$atividade->estemp->cnpj."";
        $path = "".$document_root."/uploads/".substr($atividade->empresa->cnpj, 0, 8)."/".$atividade->estemp->cnpj."";
        $tipo = $this->getTipo($atividade->regra->tributo->tipo);
        $ult_periodo_apuracao = $atividade->periodo_apuracao;
        $path .= '/'.$tipo.'/'.$atividade->regra->tributo->nome.'/'.$ult_periodo_apuracao.'/'.$atividade->arquivo_entrega;
        $path_link .= '/'.$tipo.'/'.$atividade->regra->tributo->nome.'/'.$ult_periodo_apuracao.'/'.$atividade->arquivo_entrega;

        if (file_exists($path)) {
            $download_link[$atividade->estemp->cnpj]['texto'] = $atividade->estemp->razao_social.' - '. $atividade->regra->tributo->nome;
            $download_link[$atividade->estemp->cnpj]['link'] = $path_link;
        }

        $regra = Regraenviolote::where('id_empresa', $atividade->emp_id)->where('id_tributo', $atividade->regra->tributo_id)->first();   
        if (!empty($download_link)) {    
            $this->enviarEmailLote($download_link, $regra->email_1, $regra->email_2, $regra->email_3);
        }
    }


    public function enviarEmailLote($array, $email_1, $email_2, $email_3)
    {   
        $key = 'AIzaSyBI3NnOJV5Zt-hNnUL4BUCaWIgGugDuTC8';
        $Googl = new Googl($key);
		foreach ($array as $L => $F) {
            $arr[$L]['texto'] = $F['texto'];
            $arr[$L]['link'] = @$Googl->shorten($F['link']);
			if(empty($arr[$L]['link']))
			{
				$arr[$L]['link'] = $F['link'];
			}
        }
		
        $dados = array('dados' => $arr, 'emails' => array($email_1, $email_2, $email_3));
        $data['linkDownload'] = $dados['dados'];

        $dataExibe = date('d/m/Y');

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

    public function reprovar($id)
    {
        $atividade = Atividade::findOrFail($id);
        $atividade->status = 1;
        $atividade->arquivo_entrega = '';
        $atividade->save();

        $entregador = User::findOrFail($atividade->usuario_entregador);
        $user = User::findOrFail(Auth::user()->id);
        $subject = "Bravo Plataforma – Fiscal :: Entrega atividade --REPROVADA--";
        $data = array('subject'=>$subject,'messageLines'=>array());
		
        $var = DB::select(
			"SELECT B.razao_social, 
		 			E.cnpj, 
		 			E.codigo, 
		 			CONCAT(SUBSTRING(A.periodo_apuracao, 1, 2),'/',SUBSTRING(A.periodo_apuracao, 3, 4)) AS periodo_apuracao,
		 			(SELECT obs FROM comentarios WHERE comentarios.atividade_id = A.id ORDER BY id DESC LIMIT 1) AS motivo
			   FROM atividades A
		 INNER JOIN empresas B ON A.emp_id = B.id
		 INNER JOIN estabelecimentos E ON A.estemp_id = E.id
		 	  WHERE A.id = ".$id
		);
        
        $var = json_decode(json_encode($var),true);
        foreach ($var as $t) {
			$data['messageLines'][] = $atividade->descricao.' - COD. '.$atividade->estemp->codigo.' - P.A. '.$t['periodo_apuracao'].' - Reprovada pelo coordenador ('.$user->name.'), efetuar uma nova entrega.';
			$data['messageLines'][] = 'Empresa: '. $t['razao_social'].' - CNPJ: '. $t['cnpj'] . ' Código da área: '.$t['codigo'];
			$data['messageLines'][] = 'Motivo da reprovação: '. $t['motivo'];
		}

        $this->eService->sendMail($entregador, $data, 'emails.notification-aprovacao');

        // Delete the file
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
        $destinationPath = substr($atividade->estemp->cnpj, 0, 8) . '/' . $atividade->estemp->cnpj .'/'.$tipo_label. '/' . $atividade->regra->tributo->nome . '/' . $atividade->periodo_apuracao . '/' . $atividade->arquivo_entrega; // upload path
        File::delete(public_path('uploads/'.$destinationPath));
        $exception = '';
        if (File::exists($destinationPath)) {
            $exception = 'O arquivo não foi deletado, contatar o administrador.';
        }
        return redirect()->route('entregas.index')->with('status', 'Atividade reprovada com sucesso! '.$exception);
    }

    public function reprovar_cliente($id)
    {
        $atividade = Atividade::findOrFail($id);
        $atividade->status_cliente = 'R';
        $atividade->cliente_aprovador = Auth::user()->id;
        $atividade->data_aprovacao_cliente = date("Y-m-d H:i:s");

        $atividade->status = 2;
        $atividade->save();

        $subject = "Bravo Plataforma – Fiscal :: Entrega atividade --REPROVADA POR CLIENTE--";
        $data = array('subject'=>$subject,'messageLines'=>array());

        $var = DB::select(
            "SELECT B.razao_social, 
		 			E.cnpj, 
		 			E.codigo, 
		 			CONCAT(SUBSTRING(A.periodo_apuracao, 1, 2),'/',SUBSTRING(A.periodo_apuracao, 3, 4)) AS periodo_apuracao,
		 			(SELECT obs FROM comentarios WHERE comentarios.atividade_id = A.id ORDER BY id DESC LIMIT 1) AS motivo,
              		u.name as cliente_aprovador_nome,
              		A.usuario_aprovador,
              		A.cliente_aprovador
			   FROM atividades A 
		 INNER JOIN empresas B ON A.emp_id = B.id
		 INNER JOIN estabelecimentos E ON A.estemp_id = E.id
         INNER JOIN users u ON u.id = cliente_aprovador
		 	  WHERE A.id =  ".$id
        );

        $var = json_decode(json_encode($var),true);

        foreach ($var as $t) {
            $usuario = User::findOrFail($t['usuario_aprovador']);

            $data['messageLines'][] = $atividade->descricao.' - COD. '.$atividade->estemp->codigo.' - P.A. '.$t['periodo_apuracao'].' - Reprovada pelo cliente ('.$t["cliente_aprovador_nome"].').';
            $data['messageLines'][] = 'Empresa: '. $t['razao_social'].' - CNPJ: '. $t['cnpj'] . ' Código da área: '.$t['codigo'];

            $data['linkDownload'] = $this->generateLinks($atividade);

            $this->eService->sendMail($usuario, $data, 'emails.notification-aprovacao');
        }

        return redirect()->route('entregas.index')->with('status', 'Atividade reprovada com sucesso! ');
    }

    public function cancelar($id)
    {
        $atividade = Atividade::findOrFail($id);
        if (sizeof($atividade->retificacoes)>0) {
            return redirect()->route('atividades.index')->with('status', 'Não foi possivel cancelar, porque existem retificações! ');
        }

        $atividade->status = 1;
        $atividade->arquivo_entrega = '';
        $atividade->save();

        $entregador = User::findOrFail($atividade->usuario_entregador);
        $user = User::findOrFail(Auth::user()->id);
        $subject = "BravoTaxCalendar - Entrega atividade --CANCELADA--";
        $data = array('subject'=>$subject,'messageLines'=>array());
        $data['messageLines'][] = $atividade->descricao.' - COD.'.$atividade->estemp->codigo.' - Cancelada pelo coordenador ('.$user->name.'), efetuar uma nova entrega.';

        $var = DB::select("select B.razao_social, C.cnpj, C.codigo from atividades A inner join empresas B on A.emp_id = B.id inner join estabelecimentos C on A.estemp_id = C.id where A.id = ".$id."");
        
        $var = json_decode(json_encode($var),true);
        foreach ($var as $t) {
        }
        $data['messageLines'][] = 'Empresa: '. $t['razao_social'].' - CNPJ: '. $t['cnpj'] . ' Código da área: '.$t['codigo'];

        $this->eService->sendMail($entregador, $data, 'emails.notification-aprovacao');

        // Delete the file
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
        $destinationPath = substr($atividade->estemp->cnpj, 0, 8) . '/' . $atividade->estemp->cnpj .'/'.$tipo_label. '/' . $atividade->regra->tributo->nome . '/' . $atividade->periodo_apuracao . '/' . $atividade->arquivo_entrega; // upload path
        File::delete(public_path('uploads/'.$destinationPath));
        $exception = '';
        if (File::exists($destinationPath)) {
            $exception = 'Não foi possivel cancelar o arquivo, por favor contatar o administrador de sistema.';
        }
        return redirect()->route('atividades.index')->with('status', 'Entrega atividade cancelada com sucesso! '.$exception);
    }
}
