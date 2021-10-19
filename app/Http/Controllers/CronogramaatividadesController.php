<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Regra;
use App\Http\Requests;
use App\Models\Empresa;
use App\Models\Tributo;
use App\Models\Atividade;

use App\Models\Municipio;
use App\Models\Comentario;
use Illuminate\Http\Request;
use App\Models\Estabelecimento;
use App\Models\CronogramaMensal;
use App\Models\CronogramaStatus;
use App\Services\EntregaService;
use Yajra\Datatables\Datatables;

use Illuminate\Support\Facades\DB;
use App\Models\CronogramaAtividade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Collection;

class CronogramaatividadesController extends Controller
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
        return view('cronogramaatividades.index')->with('filter_cnpj',$request->input("vcn"))->with('filter_codigo',$request->input("vco"));
    }

    public function checarCronogramaEmProgresso() {
			
			if(isset($_GET['id']) && is_numeric($_GET['id'])) {
				$dados = DB::table('cronogramastatus')
				->select(
					'cronogramastatus.id as id',
					'cronogramastatus.periodo_apuracao as periodo_apuracao',
					'cronogramastatus.qtd as qtd',
					'cronogramastatus.qtd_realizados as qtd_realizados',
					'cronogramastatus.qtd_mensal as qtd_mensal',
					'cronogramastatus.qtd_priority as qtd_priority',
					'cronogramastatus.emp_id as emp_id',
					'cronogramastatus.tipo_periodo as tipo_periodo',
					'cronogramastatus.created_at as created_at',
					'cronogramastatus.status as status',
					'empresas.razao_social as nome_empresa'
				)
				->leftjoin('empresas','empresas.id','=','cronogramastatus.emp_id')
				->where('cronogramastatus.id', $_GET['id'])->first();
			} else {
				$dados = DB::table('cronogramastatus')
				->select(
					'cronogramastatus.id as id',
					'cronogramastatus.periodo_apuracao as periodo_apuracao',
					'cronogramastatus.qtd as qtd',
					'cronogramastatus.qtd_realizados as qtd_realizados',					
					'cronogramastatus.qtd_mensal as qtd_mensal',
					'cronogramastatus.qtd_priority as qtd_priority',
					'cronogramastatus.emp_id as emp_id',
					'cronogramastatus.tipo_periodo as tipo_periodo',
					'cronogramastatus.created_at as created_at',
					'cronogramastatus.status as status',
					'empresas.razao_social as nome_empresa'
					)
					->leftjoin('empresas','empresas.id','=','cronogramastatus.emp_id')
				->where('cronogramastatus.status','0')->orderBy('id', 'DESC')->first();
			}

		$array = array();
        if(!empty($dados) ) {
			$qtd = $dados->qtd;
			$qtd_realizados = $dados->qtd_realizados;
			$qtd_mensal 	= $dados->qtd_mensal;
			$qtd_priority 	= $dados->qtd_priority;

			$porcentagem = 0;
			$porcentagem2 = 0;
			$porcentagem3 = 0;
			
			
			if($qtd_realizados 	> 0) $porcentagem  = ceil ($qtd_realizados * 100 / $qtd);
			if($qtd_mensal 		> 0) $porcentagem2 = ceil ($qtd_mensal     * 100 / $qtd);
			if($qtd_priority 	> 0) $porcentagem3 = ceil ($qtd_priority   * 100 / $qtd);

			$created_at = new \DateTime($dados->created_at);
			$created_at = $created_at->format('d/m/Y');

			$array = array(
				'id'                		=> $dados->id,
				"porcentagem_realizados"	=> $porcentagem,
				"porcentagem_mensal"    	=> $porcentagem2,
				"porcentagem_priority" 		=> $porcentagem3,
				'periodo_apuracao'  		=> $dados->periodo_apuracao,
				'qtd'               		=> $qtd,
				'qtd_realizados'    		=> $qtd_realizados,
				'qtd_mensal'    			=> $qtd_mensal,
				'qtd_priority'    			=> $qtd_priority,
				'emp_id'            		=> $dados->emp_id,
				'tipo_periodo'      		=> $dados->tipo_periodo,
				'created_at'        		=> $created_at,
				'nome_empresa'				=> $dados->nome_empresa,
				'status'					=> $dados->status
			);
		}		
		
		echo json_encode($array);
    }

    public function anyData(Request $request)
    {
        $input = $request->all();
        $periodo_busca = '';
        $empresa_busca = '';
        $ie_busca = '';
        $municipio_cod = '';
        $analista_busca = '';
        $estabelecimento_busca = '';
        $data_inicio = '';
        $data_termino = '';

        $permite_empresa = false;
        $permite_analista = false;
        $permite_municipio = false;
        $permite_filial = false;


        if (isset($input['periodo_apuracao']) && !empty($input['periodo_apuracao'])) {
            $periodo_busca = str_replace('/', '', $input['periodo_apuracao']);
        }
        if (isset($input['Emp_id']) && !empty($input['Emp_id'])) {
            $empresa_busca = $input['Emp_id'];
        }
        if (!empty($input['municipio_cod'])) {
            $municipio_cod = $input['municipio_cod'];
        }
        if (!empty($input['ie_busca'])) {
            $ie_busca = $input['ie_busca'];
        }
        if (!empty($input['Analista_id'])) {
            $analista_busca = $input['Analista_id'];
        }
        if (!empty($input['Estabelecimento_id'])) {
            $estabelecimento_busca = $input['Estabelecimento_id'];
        }
        if (!empty($input['permite_empresa'])) {
            $permite_empresa = $input['permite_empresa'];
        }
        if (!empty($input['permite_analista'])) {
            $permite_analista = $input['permite_analista'];
        }
        if (!empty($input['permite_municipio'])) {
            $permite_municipio = $input['permite_municipio'];
        }
        if (!empty($input['permite_filial'])) {
            $permite_filial = $input['permite_filial'];
        }
        if (!empty($input['data_inicio'])) {
            $data_inicio = $input['data_inicio'];
        }
        if (!empty($input['data_termino'])) {
            $data_termino = $input['data_termino'];
        }

        $query = 'SELECT A.id, DATE_FORMAT(A.inicio_aviso, "%d/%m/%Y") as inicio_aviso , DATE_FORMAT(A.data_atividade, "%d/%m/%Y") as data_atividade, B.codigo, A.descricao, C.uf, E.Tipo, F.name, C.nome, B.cnpj, B.insc_estadual, A.Id_usuario_analista from cronogramaatividades A inner join estabelecimentos B on A.estemp_id = B.id inner join municipios C on B.cod_municipio = C.codigo left join regras D on A.regra_id = D.id inner join tributos E on D.tributo_id = E.id left join users F on A.Id_usuario_analista = F.id where 1 ';

        if (!empty($empresa_busca) && $permite_empresa) {
            $query .= 'AND A.emp_id = '.$empresa_busca.'';
        }
        if (!empty($periodo_busca)) {
            $query .= ' AND A.periodo_apuracao = '.$periodo_busca.'';
        }
        if (!empty($ie_busca)) {
            $query .= ' AND B.insc_estadual = '.$ie_busca.'';
        }
        if (!empty($estabelecimento_busca) && $permite_filial) {
            $query .= ' AND A.estemp_id = '.$estabelecimento_busca.'';
        }
        if (!empty($municipio_cod) && $permite_municipio) {
            $query .= ' AND C.codigo = '.$municipio_cod.'';
        }
        if (!empty($analista_busca) && $permite_analista) {
            $query .= ' AND A.Id_usuario_analista = '.$analista_busca.'';
        }
        if (!empty($data_inicio)) {
            $query .= ' AND DATE_FORMAT(A.inicio_aviso, "%Y-%m-%d") = "'.$data_inicio.'"';
        }
        if (!empty($data_termino)) {
            $query .= ' AND DATE_FORMAT(A.limite, "%Y-%m-%d") = "'.$data_termino.'"';
        }

        $atividades = DB::select($query);
        $atividades = json_decode(json_encode($atividades),true);
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $municipios = Municipio::selectRaw("nome, codigo")->pluck('nome','codigo');
        $estabelecimentos = Estabelecimento::selectRaw("concat(razao_social, ' - ', codigo, ' - ', cnpj) as razao_social, id")->orderby('codigo')->pluck('razao_social','id');
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $analistas = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->pluck('name','id');

        return view('cronogramaatividades.index')->with('tabela', $atividades)->with('empresas', $empresas)->with('analistas', $analistas)->with('estabelecimentos', $estabelecimentos)->with('municipios', $municipios);
    }

    public function alterarAnalistas(Request $request)
    {
        $input = $request->all();

        if (!empty($input['alterar']) && !empty($input['Analista_id'])) {
            DB::table('cronogramaatividades')
                ->wherein('id', $input['alterar'])
                ->update(['Id_usuario_analista' => $input['Analista_id']]);
        }

        return redirect()->back()->with('status', 'Analistas Alterados com sucesso');
    }


    public function AlterAnalistas(Request $request)
    {
        $input = $request->all();
        if (empty($input['Id_usuario_analista'])) {
            return redirect()->back()->with('status', 'É necessário selecionar um Analista');
        }

        CronogramaAtividade::where('cronograma_mensal', $input['id_cronogramamensal'])->update(['Id_usuario_analista' => $input['Id_usuario_analista']]);
        $msg = 'Analistas alterados com sucesso';

        return redirect()->back()->with('status', $msg);

    }

    public function planejamento(Request $request)
    {
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $usuarios = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->pluck('name','id');
        $input = $request->all();

        $dados = DB::table('cronogramamensal')
            ->leftjoin('cronogramaatividades', 'cronogramamensal.id', '=', 'cronogramaatividades.cronograma_mensal')
            ->selectRaw('cronogramamensal.*, GROUP_CONCAT(cronogramaatividades.Id_usuario_analista SEPARATOR ", ") AS analistas');

        if( isset($input['Emp_id']) && $input['Emp_id'] != '') {
            $dados->where("cronogramamensal.Empresa_id", $input['Emp_id']);
        }

        if( isset($input['Tributo_id']) && $input['Tributo_id'] != '') {
            $dados->where("cronogramamensal.Tributo_id", $input['Tributo_id']);
        }

        if( isset($input['uf']) && $input['uf'] != '') {
            $dados->where("cronogramamensal.uf", $input['uf']);
        }

        if (!empty($input)) {
            $periodo = str_replace('/', '', $input['periodo_apuracao']);
            $dados = $dados->where('cronogramamensal.periodo_apuracao', '=', $periodo);
        }
        // $dados = $dados->orderby('Empresa_id', '=', str_replace('/', '', $input['periodo_apuracao']));
        $dados = $dados->groupBy('cronogramamensal.id')->get();
        $array = array();
        if (!empty($dados)) {
            foreach ($dados as $key => $value) {
                $Tributo = Tributo::find($value->Tributo_id);
                $data_carga = DB::Select('SELECT A.Data_prev_carga FROM previsaocarga A WHERE A.periodo_apuracao = "'.$value->periodo_apuracao.'" AND A.Tributo_id = '.$value->Tributo_id);

                $empresa = Empresa::findOrFail($value->Empresa_id);

                $value->cnpj = $empresa->cnpj;
                $value->Tributo_nome = $Tributo->nome;
                $value->Tempo_estab = $value->Tempo_estab/60;
                $value->Tempo_total = $value->Tempo_total/60;
                $value->Tempo_geracao = $value->Tempo_geracao/60;

                $value->Qtd_analistas = ceil($value->Qtd_analistas);

                $value->carga = null;
                $value->Inicio = null;
                $value->Termino = null;

                if(!empty($data_carga) ) {
                    $value->Inicio = date('d/m/Y', strtotime("+1 days",strtotime($data_carga[0]->Data_prev_carga)));
                    $value->carga = date('d/m/Y', strtotime($data_carga[0]->Data_prev_carga));

                    $inicio = $this->formatData($value->Inicio);
                    $value->Termino = date('d/m/Y', strtotime("+".$value->Qtd_dias." days",strtotime($inicio)));
                }

                $data_vencimento = str_replace('-', '/', $value->DATA_SLA);
                $value->DATA_SLA = date('d/m/Y', strtotime($data_vencimento));

                $str = '';
                if (!empty($value->analistas)) {
                    $analistasArray = array();
                    if (strlen($value->analistas > 4)) {
                        $analistas_explode = explode(',', $value->analistas);
                        foreach ($analistas_explode as $singlekey => $single) {
                            $usuario = User::Find($single);
                            if (!empty($usuario)) {
                                $analistasArray[trim($single)] = $usuario->name;
                            }else {
                                $analistasArray[trim($single)] = '';
                            }
                        }

                        $str = implode("<br>", $analistasArray);
                    }
                }
                $value->names = $str;
                $dados[$key] = $value;
            }
        }

        return view('cronogramaatividades.planejamento')->with('dados', $dados)->with('usuarios', $usuarios);
    }

    private function formatData($date)
    {
        $valorData = trim($date);
        $data = str_replace('/', '-', $valorData);
        $formated = date('Y-m-d', strtotime($data));
        return $formated;
    }

    public function loadPlanejamento()
    {
        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $ufs = Municipio::selectRaw("distinct(uf) as uf")->pluck('uf','uf');

        return view('cronogramaatividades.Loadplanejamento')
            ->with("empresas",$empresas)
            ->with("tributos",$tributos)
            ->with("ufs",$ufs);
    }

    public function alterar(Request $request)
    {
        $input = $request->all();
        if (array_key_exists('inicio_aviso', $input) || array_key_exists('limite', $input)) {
            if (strtotime($input['inicio_aviso']) > strtotime($input['limite'])) {
                return redirect()->back()->with('status', 'Favor informar as datas corretamente');
            }
        }

        if ($input['id_atividade'] != 0) {
            $obj = CronogramaAtividade::findOrFail($input['id_atividade']);
            if (array_key_exists('inicio_aviso', $input) && !empty($input['inicio_aviso'])) {
                $obj->inicio_aviso = $input['inicio_aviso'];
            }

            if (array_key_exists('limite', $input) && !empty($input['limite'])) {
                $obj->limite = $input['limite'];
            }

            if (array_key_exists('Id_usuario_analista', $input) && !empty($input['Id_usuario_analista'])) {
                $obj->Id_usuario_analista = $input['Id_usuario_analista'];
            }

            if (array_key_exists('data_atividade', $input) && !empty($input['data_atividade'])) {
                $obj->data_atividade = $input['data_atividade'];
            }

            $obj->save();

        }

        if ($input['id_atividade'] == 0) {
            if (empty($input['periodo_apuracao']) || empty($input['Emp_id'])) {
                return redirect()->back()->with('status', 'É necessário informar a empresa e o período para busca dos registros a serem atualizados.');
            }
            $input['periodo_apuracao'] = str_replace('/', '', $input['periodo_apuracao']);

            $current = DB::Select('select id from cronogramaatividades where periodo_apuracao = '.$input['periodo_apuracao'].' and emp_id = '.$input['Emp_id'].'');

            foreach ($current as $key => $val) {
                $obj = CronogramaAtividade::findOrFail($val->id);

                if (array_key_exists('inicio_aviso', $input) && !empty($input['inicio_aviso'])) {
                    $obj->inicio_aviso = $input['inicio_aviso'];
                }

                if (array_key_exists('limite', $input) && !empty($input['limite'])) {
                    $obj->limite = $input['limite'];
                }

                if (array_key_exists('Id_usuario_analista', $input) && !empty($input['Id_usuario_analista'])) {
                    $obj->Id_usuario_analista = $input['Id_usuario_analista'];
                }

                if (array_key_exists('data_atividade', $input) && !empty($input['data_atividade'])) {
                    $obj->data_atividade = $input['data_atividade'];
                }

                $obj->save();
            }
        }

        return redirect()->back()->with('status', 'Registro Atualizado com sucesso');
    }
    public function excluir(Request $request)
    {
        $input = $request->all();

        if (array_key_exists('periodo_apuracao', $input)) {

            if (empty($input['periodo_apuracao'])) {
                return redirect()->back()->with('status', 'Favor informar o período desejado para exclusão');
            }

            $input['periodo_apuracao'] = str_replace('/', '', $input['periodo_apuracao']);

            $current = DB::Select('select id from cronogramaatividades where periodo_apuracao = '.$input['periodo_apuracao'].' and emp_id = '.$input['Emp_id'].'');

            if (!empty($current)) {
                foreach ($current as $strls => $vlr) {
                    $obj = CronogramaAtividade::findOrFail($vlr->id);
                    $obj->delete();
                }
            }
        }
        if (array_key_exists('idAtividade', $input)) {
            $id = $input['idAtividade'];
            $obj = CronogramaAtividade::findOrFail($id);
            $obj->delete();
        }

        return redirect()->back()->with('status', 'Registros excluídos com sucesso');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $usuarios = User::selectRaw("concat(name, ' - ( ', email, ' )') as nome_e_mail, id")->pluck('nome_e_mail', 'id');
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        $regras = [''=>''];
        $estabelecimentos = Estabelecimento::selectRaw("concat(razao_social, ' - ', codigo, ' - ', cnpj) as razao_social, id")->orderby('codigo')->pluck('razao_social','id'); //Unidades Federais

        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id'); //Unidades Federais
        $municipios = [''=>''];

        return view('cronogramaatividades.create')->with('usuarios', $usuarios)
            ->with('empresas',$empresas)
            ->with('regras',$regras)
            ->with('estabelecimentos',$estabelecimentos)
            ->with('tributos',$tributos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeEstabelecimento(Request $request)
    {
        $input = $request->all();
        if (empty($input['periodo_apuracao_estab']) || empty($input['multiple_select_estabelecimentos_frm'])) {
            return redirect()->back()->with('status', 'Favor informar quais estabelecimentos e qual o período');
        }

        foreach ($input['select_tributo_estab'] as $key => $trib) {
            $tributo = explode(',', $trib);
        }

        foreach ($tributo as $key => $idTrib) {
            foreach ($input['multiple_select_estabelecimentos_frm'] as $x => $value) {
                $var[$key]['tributo'] = $idTrib;
                $var[$key]['estabelecimento'] = $value;
            }
        }

        $input['periodo_apuracao_estab'] = str_replace('/', '', $input['periodo_apuracao_estab']);
        $periodo_fim = $input['periodo_apuracao_estab'];
        $periodo_ini = $input['periodo_apuracao_estab'];

        foreach ($var as $k => $x) {
            $arr = explode(',', $x['estabelecimento']);

            foreach ($arr as $chave => $id) {
                $this->cronogramageracaoEstab($x['tributo'], $id, $periodo_ini, $periodo_fim);
            }

        }
        return redirect()->back()->with('status', 'Atividades geradas com sucesso');
    }

    public function cronogramageracaoEstab($id_tributo,$id_estab,$periodo_ini,$periodo_fin)
    {
        set_time_limit(0);
        $estabelecimento = Estabelecimento::findOrFail($id_estab);
        if ($periodo_ini==$periodo_fin) {
            Artisan::call('generatecronograma:single', [
                'cnpj' => $estabelecimento->cnpj, 'codigo' => $estabelecimento->codigo, 'tributo_id' => $id_tributo, 'periodo_ini' => $periodo_ini
            ]);
        }
        $exitCode = Artisan::output();
    }
    
    // Inclusão de desvio para poder recriar Cronograma de Atividades
    // O objetivo é limpar todos os dados das tabelas do Cronograma apenas
    // para a Empresa e Período de Apuração especificados, sem afetar diretamenta
    // os dados das tabelas de Atividades.
    
    public function clearActivitiesSchedule(Request $request)
    {
        $dados = array();
        $input = $request->all();
        
        if (empty($input['emp_id']) || empty($input['periodo_apuracao'])) {
            if (empty($input['emp_id'])) {
                $dados['message'] = 'O campo <strong>Empresa</strong> é obrigatório e deve ser informado!';
                $dados['success'] = false;
                $dados['errorType'] = 'review';
                
                return response()->json($dados);
            }
            
            if (empty($input['periodo_apuracao'])) {
                $dados['message'] = 'O campo <strong>Período de Apuração</strong> é obrigatório e deve ser informado!';
                $dados['success'] = false;
                $dados['errorType'] = 'review';
                return response()->json($dados);
            }
        } else {
            $empresa = $input['emp_id'];
            $periodoApuracao = str_replace('/', '', $input['periodo_apuracao']);
            
            $cronogramaStatus = CronogramaStatus::where(['emp_id' => $empresa, 'periodo_apuracao' => $periodoApuracao])->delete();
            $cronogramaMensal = CronogramaMensal::where(['Empresa_id' => $empresa, 'periodo_apuracao' => $periodoApuracao])->delete();
            $cronogramaAtividade = CronogramaAtividade::where(['emp_id' => $empresa, 'periodo_apuracao' => $periodoApuracao])->delete();
            
            $dados['message'] = "Os dados do Cronograna de Atividades para o período de apuração <strong>{$periodoApuracao}</strong> foram excluídos com sucesso! <strong><p>Iniciando a recriação do Cronograma em seguida.</p></strong>";
            $dados['success'] = true;
            $dados['errorType'] = 'info';
            return response()->json($dados);
        }
    }

    public function storeEmpresa(Request $request)
    {
        set_time_limit(0);
        $input = $request->all();

        if (empty($input['select_empresa'])) {
            return redirect()->back()->with('status', 'Informe a empresa.');
        }

        if (empty($input['periodo_apuracao'])) {
            return redirect()->back()->with('status', 'Informe o período de apuração');
        }

        $empresa = Empresa::findOrFail($input['select_empresa']);

        $input['periodo_apuracao'] = str_replace('/', '', $input['periodo_apuracao']);

        Artisan::call('generatecronograma:all', [
            'periodo' => $input['periodo_apuracao'], 'empresa' => $empresa->cnpj
        ]);

        $exitCode = Artisan::output();

        return $exitCode;
    }

    public function cronogramageracaoEmps($periodo,$id_emp) {
        $empresa = Empresa::findOrFail($id_emp);

        $warning = false; // WARNING para periodo anterior não gerado
        if (strlen($periodo) == 4) {
            $knownDate = Carbon::create($periodo,1,1,0,0);
        } else {
            $knownDate = Carbon::create((int)substr($periodo,-4,4),(int)substr($periodo,0,2),1,0,0);
        }

        if (!$warning){
            Artisan::call('generatecronograma:all', [
                'periodo' => $periodo, 'empresa' => $empresa->cnpj
            ]);

            $exitCode = Artisan::output();
        }
    }


    public function store(Request $request)
    {
        $input = $request->all();

        CronogramaAtividade::create($input);

        return redirect()->route('cronogramaatividades.index')->with('status', 'Atividade adicionada com sucesso!');
    }

    public function show($id)
    {
        //
    }


    public function Gerarsemanal()
    {
        return view('cronogramaatividades.generateCalendarSemanal');
    }

    public function GerarchecklistCron()
    {
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');

        return view('cronogramaatividades.generateChecklist')->with('empresas',$empresas);
    }

    public function GerarConsulta()
    {
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');

        $estabelecimentos = Estabelecimento::selectRaw("concat(razao_social, ' - ', codigo, ' - ', cnpj) as razao_social, id")->orderby('codigo')->pluck('razao_social','id');

        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        // $status = array('1' => 'Não efetuada', '2' => 'Em aprovação', '3' => 'Entregue');
        $status = array('1' => 'Entrega não efetuada', '2' => 'Entrega em aprovação', '3' => 'Entrega efetuada');
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $analistas = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->pluck('name','id');

        return view('cronogramaatividades.generateConsulta')->with('empresas',$empresas)->with('estabelecimentos',$estabelecimentos)->with('analistas',$analistas)->with('tributos',$tributos)->with('status', $status);
    }

    public function ConsultaCronograma(Request $request)
    {
        $input = $request->all();
        if (!isset($input['periodo_apuracao']) || empty($input['periodo_apuracao'])) {
            if (!isset($input['data_inicio']) || empty($input['data_inicio'])) {
                return redirect()->back()->with('status', 'Favor informar ambas as datas de Início e Fim');
            }

            if (!isset($input['data_fim']) || empty($input['data_fim'])) {
                return redirect()->back()->with('status', 'Favor informar ambas as datas de Início e Fim');
            }
        } else {
            if (isset($input['periodo_apuracao']) && !empty($input['periodo_apuracao']) && strlen($input['periodo_apuracao']) == 7) {
                $periodo_apuracao = str_replace('/', '', $input['periodo_apuracao']);
            } else {
                return redirect()->back()->with('status', 'Favor informar o Período de Apuração no formato MM/AAAA');
            }
        }
        
        if (!isset($input['empresas_selected']) || empty($input['empresas_selected'])) {
            return redirect()->back()->with('status', 'Favor informar a(s) empresa(s)');
        }
        
        // selecao por datas e não por período de apuração
        if (!isset($input['periodo_apuracao']) || empty($input['periodo_apuracao'])) {
            $input['data_inicio'] = implode("/", array_reverse(explode("-", $input['data_inicio'])));
            $input['data_fim'] = implode("/", array_reverse(explode("-", $input['data_fim'])));

            $dateStart      = $input['data_inicio'];
            $dateStart      = implode('-', array_reverse(explode('/', substr($dateStart, 0, 10)))).substr($dateStart, 10);
            $dateStart      = new \DateTime($dateStart);

            $dateEnd        = $input['data_fim'];
            $dateEnd        = implode('-', array_reverse(explode('/', substr($dateEnd, 0, 10)))).substr($dateEnd, 10);
            $dateEnd        = new \DateTime($dateEnd);
            $dateRange = array();
            
            while($dateStart <= $dateEnd){
                $dateRange[] = $dateStart->format('Y-m-d');
                $dateStart = $dateStart->modify('+1day');
            }

            $datas = $dateRange;

            //datas
            $string['Datas'] = "'0000-00-00',";
            foreach ($datas as $k => $v) {
                $string['Datas'] .= "'".$v."',";
            }
            $string['Datas'] = substr($string['Datas'], 0, -1);
        }
        
        //empresas
        $string['emps'] = '';
        foreach ($input['empresas_selected'] as $k => $v) {
            $string['emps'] .= $v.",";
        }
        $string['emps'] = substr($string['emps'], 0, -1);

        //analistas
        $string['analista_selected'] = '';
        if (!empty($input['analista_selected'])) {
            foreach ($input['analista_selected'] as $k => $v) {
                $string['analista_selected'] .= $v.",";
            }
            $string['analista_selected'] = substr($string['analista_selected'], 0, -1);
        }

        $string['estabelecimentos'] = '';
        if (!empty($input['estabelecimento_selected'])) {
            foreach ($input['estabelecimento_selected'] as $k => $v) {
                $string['estabelecimentos'] .= $v.",";
            }
            $string['estabelecimentos'] = substr($string['estabelecimentos'], 0, -1);
        }

        $string['tributos'] = '';
        if (!empty($input['tributos_selected'])) {
            foreach ($input['tributos_selected'] as $k => $v) {
                $string['tributos'] .= $v.",";
            }
            $string['tributos'] = substr($string['tributos'], 0, -1);
        }

        $string['status'] = '';
        if (!empty($input['status_selected'])) {
            foreach ($input['status_selected'] as $k => $v) {
                $string['status'] .= $v.",";
            }
            $string['status'] = substr($string['status'], 0, -1);
        }
        $user = User::findOrFail(Auth::user()->id);
        $query = "SELECT
                    pg.periodo_apuracao AS PeriodoApuracao,
                    DATE_FORMAT(ca.data_atividade, '%d/%m/%Y %H:%i:%s') AS DataAtividade,
                    e.codigo AS EstabelecimentoCodigo,
                    e.cnpj AS CNPJ,
                    IF(IFNULL(e.insc_estadual, '') = '', 'IE SEM CADASTRO', e.insc_estadual) AS InscEstadual,
                    IF(IFNULL(e.insc_municipal, '') = '', 'CCM SEM CADASTRO', e.insc_municipal) AS InscMunicipal,
                    m.nome AS Municipio,
                    e.cod_municipio AS CodigoIBGE,
                    m.uf AS UF,
                    oa.Prioridade AS PrioridadeApuracao,
                    t.nome AS NomeTributo,
                    CONCAT('(', ca.regra_id, ') ', r.regra_entrega) as Regra,
                    ca.descricao AS Atividade,
                    a.status AS AtividadeStatus,
                    IFNULL(ca.Id_usuario_analista, 0) AS UsuarioAnalistaId,
                    IF(IFNULL(u.name, '') = '', ':: SEM ANALISTA', u.name) AS UsuarioAnalista,
                    pg.Data_prev_carga AS DataPrevisaoCarga,
                    ca.limite AS DataLimite,
                    ta.Qtd_minutos AS TempoAtividade,
                    ca.tempo_excedido AS TempoAtividadeExcedido,
                    ca.tempo_excedido_msg AS TempoAtividadeExcedidoMensagem,
                    ca.id AS CronogramaAtividadeId
				FROM
					agenda.previsaocarga pg
                INNER JOIN agenda.tributos t ON pg.Tributo_id = t.id
                LEFT JOIN agenda.regras r ON t.id = r.tributo_id
                LEFT JOIN agenda.cronogramaatividades ca ON r.id = ca.regra_id
                LEFT JOIN agenda.estabelecimentos e ON ca.estemp_id = e.id
                LEFT JOIN agenda.municipios m ON e.cod_municipio = m.codigo
                LEFT JOIN agenda.tempoatividade ta ON pg.Tributo_id = ta.Tributo_id 
                LEFT JOIN agenda.ordemapuracao oa ON t.id = oa.Tributo_id
                LEFT join agenda.users u ON ca.Id_usuario_analista = u.id
                LEFT join agenda.atividades a ON ca.regra_id = a.regra_id ";

		$query .= " WHERE a.emp_id = ca.emp_id
				AND a.estemp_id = ca.estemp_id
                AND a.periodo_apuracao = ca.periodo_apuracao 
                AND m.uf = ta.UF 
                AND ta.Empresa_id = ca.emp_id ";
        if (!isset($input['periodo_apuracao']) || empty($input['periodo_apuracao'])) {
            $query .= " AND DATE_FORMAT(ca.data_atividade, '%Y-%m-%d') in (".$string['Datas'].") ";
        } else {
            $query .= " AND	ca.periodo_apuracao = '$periodo_apuracao' ";
        }
        $query .= " AND ca.emp_id in (".$string['emps'].") ";

        if (!empty($string['analista_selected']) && !$user->hasRole('analyst')) {
            $query .= " AND ca.Id_usuario_analista in (".$string['analista_selected'].") ";
        }

        if (!empty($string['estabelecimentos'])) {
            $query .= " AND ca.estemp_id in (".$string['estabelecimentos'].") ";
        }

        if (!empty($string['tributos'])) {
            $query .= " AND t.id in (".$string['tributos'].") ";
        }

        if (!empty($string['status'])) {
            $query .= " AND ca.status in (".$string['status'].") ";
        }

        if ($user->hasRole('analyst')){
            $query .= ' AND ca.Id_usuario_analista = '.$user->id;
        }
        
        $query .= " GROUP BY ca.id ORDER BY ca.limite, oa.Prioridade, ca.regra_id, ca.Id_usuario_analista, ca.id ";

        $dados = DB::select($query);

        return view('cronogramaatividades.ConsultaCronograma')->with('dados',$dados);
    }

    public function ChecklistCron(Request $request)
    {
        $input = $request->all();

        if (empty($input['empresas_selected'])) {
            return redirect()->back()->with('status', 'Favor informar ao menos uma empresa');
        }

        if (empty($input['periodo_apuracao'])) {
            return redirect()->back()->with('status', 'Informar o período desejado para busca');
        }

        $empresas = '';

        $periodo_busca = str_replace("/", "", $input['periodo_apuracao']);
        foreach($input['empresas_selected'] as $key => $id) {
            $empresas .= $id.',';
        }

        $empresas = substr($empresas,0,-1);

        $queryCron = "SELECT 
                    A.descricao,
                    DATE_FORMAT(A.limite, '%d/%m/%Y') AS limite,
                    B.razao_social,
                    C.codigo,
                    C.cnpj,
                    D.status
                FROM
                    cronogramaatividades A
                        INNER JOIN
                    empresas B ON A.emp_id = B.id
                        INNER JOIN
                    estabelecimentos C ON A.estemp_id = C.id
                        INNER JOIN
                    atividades D ON A.emp_id = D.emp_id
                        AND A.estemp_id = D.estemp_id
                        AND A.periodo_apuracao = D.periodo_apuracao
                        AND A.regra_id = D.regra_id
                WHERE
                    D.status in (1,2)";

        //Período adicionado
        $queryCron .= " AND A.periodo_apuracao = ".$periodo_busca."";

        //Empresas adicionadas
        $queryCron .= " AND A.emp_id in (".$empresas.")";

        //ordenação
        $queryCron .= " order by A.limite, A.emp_id, C.codigo";

        $array = DB::Select($queryCron);
        $array = json_decode(json_encode($array),true);
        $checklist = array();

        foreach ($array as $chave => $value) {
            $checklist[$value['razao_social']][] = $value;
        }
        foreach ($checklist as $key => $value) {
            foreach ($value as $chave => $dados) {
                $dados['periodo_apuracao'] = $input['periodo_apuracao'];
            }
            $checklist[$key][$chave] = $dados;
        }

        return view('cronogramaatividades.checklist')->with('checklist',$checklist);
    }

    public function semanal(Request $request)
    {
        $input = $request->all();

        if (!isset($input['data_inicio']) || empty($input['data_inicio'])) {
            return redirect()->back()->with('status', 'Favor informar ambas as datas');
        }

        if (!isset($input['data_fim']) || empty($input['data_fim'])) {
            return redirect()->back()->with('status', 'Favor informar ambas as datas');
        }

        if (strtotime($input['data_inicio']) > strtotime($input['data_fim'])) {
            return redirect()->back()->with('status', 'A data de Início não pode ser Maior que a data Final');
        }

        $day1 =substr($input['data_fim'], -2);
        $day2 = substr($input['data_inicio'], -2);
        $diff = $day1 - $day2;
        if ( $diff > 7) {
            return redirect()->back()->with('status', 'Desculpe essa função não permite a busca de mais de uma semana');
        }

        $dataSelected   = $input['data_inicio'];
        $input['data_inicio'] = implode("/", array_reverse(explode("-", $input['data_inicio'])));
        $input['data_fim'] = implode("/", array_reverse(explode("-", $input['data_fim'])));

        $dateStart      = $input['data_inicio'];
        $dateStart      = implode('-', array_reverse(explode('/', substr($dateStart, 0, 10)))).substr($dateStart, 10);
        $dateStart      = new \DateTime($dateStart);

        $dateEnd        = $input['data_fim'];
        $dateEnd        = implode('-', array_reverse(explode('/', substr($dateEnd, 0, 10)))).substr($dateEnd, 10);
        $dateEnd        = new \DateTime($dateEnd);

        $dateRange = array();
        while($dateStart <= $dateEnd){
            $dateRange[] = $dateStart->format('Y-m-d');
            $dateStart = $dateStart->modify('+1day');
        }

        $datas = $dateRange;

        $user_id = Auth::user()->id;
        $events = [];

        $vall = count($datas);
        $datasB = '"';
        foreach ($datas as $key => $dataSing) {
            $datasB .= $dataSing.'","';
        }
        $datasB = substr($datasB, 0,-2);

        $user = User::findOrFail(Auth::user()->id);
        $atividades_estab = DB::table('cronogramaatividades')
            ->join('estabelecimentos', 'estabelecimentos.id', '=', 'cronogramaatividades.estemp_id')
            ->select('cronogramaatividades.id', 'cronogramaatividades.descricao', 'estabelecimentos.codigo','cronogramaatividades.limite','cronogramaatividades.data_atividade', 'cronogramaatividades.status')
            ->whereRaw('DATE_FORMAT(cronogramaatividades.data_atividade, "%Y-%m-%d") in ('.$datasB.')')
            ->where('cronogramaatividades.estemp_type','estab');

        if ($user->hasRole('analyst')){
            $atividades_estab = $atividades_estab->where('cronogramaatividades.Id_usuario_analista', $user->id);
        }

        $atividades_get = $atividades_estab->get();

        $atividades_estab = array();
        foreach ($atividades_get as $index => $atividade_estab) {
            $atividades_estab[substr($atividade_estab->data_atividade, 0,10)][] = $atividade_estab;
        }

        foreach($atividades_estab as $atividades_singulares_estab) {
            $b = 0;
            foreach ($atividades_singulares_estab as $atividade) {
                $cor = 'green';
                if (!empty($atividade->data_atividade) && (strtotime(substr($atividade->limite, 0,10)) < strtotime(substr($atividade->data_atividade, 0,10))) || $b) {
                    $cor = 'red';
                    $b = 1;
                }

                $events[substr($atividade->data_atividade, 0,10)] = \Calendar::event(
                    'Atividades',
                    true,
                    substr($atividade->data_atividade, 0,10),
                    substr($atividade->data_atividade, 0,10),
                    $atividade->id,
                    ['url' => url('/uploadCron/'.substr($atividade->data_atividade, 0,10).'/entrega/data'),'color'=> $cor,'background-color'=>$cor, 'textColor'=>'white']
                );
            }
        }

        $atividades_emp = DB::table('cronogramaatividades')
            ->join('empresas', 'empresas.id', '=', 'cronogramaatividades.emp_id')
            ->select('cronogramaatividades.id','cronogramaatividades.data_atividade', 'cronogramaatividades.descricao', 'empresas.codigo','cronogramaatividades.limite', 'cronogramaatividades.status')
            ->whereRaw('DATE_FORMAT(cronogramaatividades.data_atividade, "%Y-%m-%d") in ('.$datasB.')');

        if ($user->hasRole('analyst')){
            $atividades_emp = $atividades_emp->where('cronogramaatividades.Id_usuario_analista', $user->id);
        }

        $atividades_get_emp = $atividades_emp->get();

        $atividades_emp = array();
        foreach ($atividades_get_emp as $index => $atividade_emp) {
            $atividades_emp[substr($atividade_emp->data_atividade, 0,10)][] = $atividade_emp;
        }

        foreach($atividades_emp as $atividades_singulares) {
            $a = 0;
            foreach ($atividades_singulares as $atividade) {
                $cor = 'green';
                if (!empty($atividade->data_atividade) && (strtotime(substr($atividade->limite, 0,10)) < strtotime(substr($atividade->data_atividade, 0,10))) || $a) {
                    $cor = 'red';
                    $a = 1;
                }

                $events[substr($atividade->data_atividade, 0,10)] = \Calendar::event(
                    'Atividades',
                    true,
                    substr($atividade->data_atividade, 0,10),
                    substr($atividade->data_atividade, 0,10),
                    $atividade->id,
                    ['url' => url('/uploadCron/'.substr($atividade->data_atividade, 0,10).'/entrega/data'),'color'=> $cor,'background-color'=>$cor, 'textColor'=>'white']
                );
            }
        }

        $day = 0;
        $dayofweek = date('w', strtotime($dataSelected));

        //Geração do calendario
        $calendar = \Calendar::addEvents($events) //add an array with addEvents
        ->setOptions([ //set fullcalendar options
            'lang' => 'pt',
            'firstDay' => $dayofweek,
            'aspectRatio' => 30,
            'allDayText' => 'Atividades',
            'eventLimit' => 300,
            'defaultDate' => $dataSelected,
            'header' => [ 'left' => '', 'center'=>'title', 'right' => ''] ,
            'defaultView' => 'agendaWeek'
        ])
            ->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
                'viewRender' => 'function() { }'
            ]);

        return view('cronogramaatividades.calendar', compact('calendar'));
    }

    public function Gerarmensal()
    {
        // $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');
        // return view('cronogramaatividades.generateCalendar')->with('empresas',$empresas);

        return view('cronogramaatividades.generateCalendar');
    }

    public function mensal(Request $request)
    {
        $input = $request->all();
        if (!isset($input['periodo_apuracao']) || empty($input['periodo_apuracao'])) {
            return redirect()->back()->with('status', 'Favor informar o período corretamente');
        }

        $user_id = Auth::user()->id;
        $events = [];
        $empresas = array();

        $periodo_apuracao = str_replace('/', '', $input['periodo_apuracao']);
        $feriados = $this->eService->getFeriadosNacionais();
        $feriados_estaduais = $this->eService->getFeriadosEstaduais();
        $user = User::findOrFail(Auth::user()->id);
        $atividades_estab = DB::table('cronogramaatividades')
            ->join('estabelecimentos', 'estabelecimentos.id', '=', 'cronogramaatividades.estemp_id')
            ->select('cronogramaatividades.id','cronogramaatividades.data_atividade', 'cronogramaatividades.descricao', 'estabelecimentos.codigo','cronogramaatividades.limite', 'cronogramaatividades.status')
            // ->whereIN('cronogramaatividades.emp_id', $empresas)
            ->where('cronogramaatividades.periodo_apuracao', $periodo_apuracao)
            ->where('cronogramaatividades.estemp_type','estab');

        if ($user->hasRole('analyst')){
            $atividades_estab = $atividades_estab->where('cronogramaatividades.Id_usuario_analista', $user->id);
        }
        $atividades_get = $atividades_estab->get();

        $atividades_estab = array();
        foreach ($atividades_get as $index => $atividade_estab) {
            $atividades_estab[substr($atividade_estab->data_atividade, 0,10)][] = $atividade_estab;
        }

        foreach($atividades_estab as $atividades_singulares_estab) {
            $b = 0;
            foreach ($atividades_singulares_estab as $atividade) {
                $cor = 'green';
                if (!empty($atividade->data_atividade) && (strtotime(substr($atividade->limite, 0,10)) < strtotime(substr($atividade->data_atividade, 0,10))) || $b) {
                    $cor = 'red';
                    $b = 1;
                }

                $events[substr($atividade->data_atividade, 0,10)] = \Calendar::event(
                    'Atividades',
                    true,
                    substr($atividade->data_atividade, 0,10),
                    substr($atividade->data_atividade, 0,10),
                    $atividade->id,
                    ['url' => url('/uploadCron/'.substr($atividade->data_atividade, 0,10).'/entrega/data'),'color'=> $cor,'background-color'=>$cor, 'textColor'=>'white']
                );
            }
        }

        //MATRIZ
        $atividades_emp = DB::table('cronogramaatividades')
            ->join('empresas', 'empresas.id', '=', 'cronogramaatividades.emp_id')
            ->select('cronogramaatividades.id','cronogramaatividades.data_atividade', 'cronogramaatividades.descricao', 'empresas.codigo','cronogramaatividades.limite', 'cronogramaatividades.status')
            ->where('cronogramaatividades.periodo_apuracao', $periodo_apuracao);

        if ($user->hasRole('analyst')){
            $atividades_emp = $atividades_emp->where('cronogramaatividades.Id_usuario_analista', $user->id);
        }

        $atividades_get_emp = $atividades_emp->get();

        $atividades_emp = array();
        foreach ($atividades_get_emp as $index => $atividade_emp) {
            $atividades_emp[substr($atividade_emp->data_atividade, 0,10)][] = $atividade_emp;
        }


        foreach($atividades_emp as $atividades_singulares) {
            $a = 0;
            foreach ($atividades_singulares as $atividade) {
                $cor = 'green';
                if (!empty($atividade->data_atividade) && (strtotime(substr($atividade->limite, 0,10)) < strtotime(substr($atividade->data_atividade, 0,10))) || $a) {
                    $cor = 'red';
                    $a = 1;
                }

                $events[substr($atividade->data_atividade, 0,10)] = \Calendar::event(
                    'Atividades',
                    true,
                    substr($atividade->data_atividade, 0,10),
                    substr($atividade->data_atividade, 0,10),
                    $atividade->id,
                    ['url' => url('/uploadCron/'.substr($atividade->data_atividade, 0,10).'/entrega/data'),'color'=> $cor,'background-color'=>$cor, 'textColor'=>'white']
                );
            }
        }

        //Geração do calendario
        $mes = substr($periodo_apuracao, 0, -4)+1;
        $ano = substr($periodo_apuracao, 2);
        if (strlen($mes) == 1) {
            $mes = '0'.$mes;
        }
        if($mes == 13){
            $mes = 1;
            $ano++;
        }
        $dataAcima = $ano.'-'.$mes.'';
        $calendar = \Calendar::addEvents($events) //add an array with addEvents
        ->setOptions([ //set fullcalendar options
            'lang' => 'pt',
            'firstDay' => 1,
            'defaultDate' => $dataAcima,
            'aspectRatio' => 2.5,
            'header' => ['left' => 'prev.next', 'center'=>'title'] //, 'right' => 'month,agendaWeek'
        ])
            ->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
                'viewRender' => 'function() { }'
            ]);

        return view('cronogramaatividades.calendar', compact('calendar'));
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
            return redirect()->route('cronogramaatividades.index')->with('error', 'Atividade já entregue, impossível cancelar!');
        } else {
            $atividade->delete();
        }


        return redirect()->route('cronogramaatividades.index')->with('status', 'Atividade cancelada com sucesso!');
    }

    public function retificar($id)
    {
        $atividade = CronogramaAtividade::findOrFail($id);
        foreach($atividade->retificacoes as $el) {
            if ($el->status<3) {
                Session::flash('message', 'Atividade de retificação já em aberto!');
                return redirect()->route('arquivos.show',$atividade->id);
            }
        }
        $retificacao = new CronogramaAtividade;

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
        $atividade = CronogramaAtividade::findOrFail($id);
        $atividade->status = 3;
        $atividade->usuario_aprovador = Auth::user()->id;
        $atividade->data_aprovacao = date("Y-m-d H:i:s");
        $atividade->save();

        $entregador = User::findOrFail($atividade->usuario_entregador);
        $user = User::findOrFail(Auth::user()->id);
        $subject = "BravoTaxCalendar - Entrega atividade --APROVADA--";
        $data = array('subject'=>$subject,'messageLines'=>array());
        $data['messageLines'][] = $atividade->descricao.' - COD.'.$atividade->estemp->codigo.' - Aprovada, atividade concluída.';
        $data['messageLines'][] = 'Coordenador: '.$user->name;

        //$this->eService->sendMail($entregador, $data, 'emails.notification-aprovacao');

        return redirect()->route('entregas.index')->with('status', 'Atividade aprovada com sucesso!');
    }

    public function reprovar($id)
    {
        $atividade = CronogramaAtividade::findOrFail($id);
        $atividade->status = 1;
        $atividade->arquivo_entrega = '';
        $atividade->save();

        $entregador = User::findOrFail($atividade->usuario_entregador);
        $user = User::findOrFail(Auth::user()->id);
        $subject = "BravoTaxCalendar - Entrega atividade --REPROVADA--";
        $data = array('subject'=>$subject,'messageLines'=>array());
        $data['messageLines'][] = $atividade->descricao.' - COD.'.$atividade->estemp->codigo.' - Reprovada pelo coordenador ('.$user->name.'), efetuar uma nova entrega.';

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
            $exception = 'O arquivo não foi deletado, contatar o administrador.';
        }
        return redirect()->route('entregas.index')->with('status', 'Atividade reprovada com sucesso! '.$exception);
    }

    public function cancelar($id)
    {
        $atividade = CronogramaAtividade::findOrFail($id);
        if (sizeof($atividade->retificacoes)>0) {
            return redirect()->route('cronogramaatividades.index')->with('status', 'Não foi possivel cancelar, porque existem retificações! ');
        }

        $atividade->status = 1;
        $atividade->arquivo_entrega = '';
        $atividade->save();

        $entregador = User::findOrFail($atividade->usuario_entregador);
        $user = User::findOrFail(Auth::user()->id);
        $subject = "BravoTaxCalendar - Entrega atividade --CANCELADA--";
        $data = array('subject'=>$subject,'messageLines'=>array());
        $data['messageLines'][] = $atividade->descricao.' - COD.'.$atividade->estemp->codigo.' - Cancelada pelo coordenador ('.$user->name.'), efetuar uma nova entrega.';

        $var = DB::select("select B.razao_social, C.cnpj, C.codigo from cronogramaatividades A inner join empresas B on A.emp_id = B.id inner join estabelecimentos C on A.estemp_id = C.id where A.id = ".$id."");

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
        return redirect()->route('cronogramaatividades.index')->with('status', 'Entrega atividade cancelada com sucesso! '.$exception);
    }

    #task 416 - Prototipo de tabela para consulta
    public function consultaPeriodoTabela(Request $request)
    {
        $input = $request->all();

        $user_id = Auth::user()->id;
        $events = [];
        $empresas = array();

        $periodo_apuracao = str_replace('/', '', $input['periodo_apuracao']);
        $feriados = $this->eService->getFeriadosNacionais();
        $feriados_estaduais = $this->eService->getFeriadosEstaduais();
        $user = User::findOrFail(Auth::user()->id);
  
        if (!isset($input['periodo_apuracao']) || empty($input['periodo_apuracao'])) {
            if (!isset($input['data_inicio']) || empty($input['data_inicio'])) {
                return redirect()->back()->with('status', 'Favor informar ambas as datas de Início e Fim');
            }

            if (!isset($input['data_fim']) || empty($input['data_fim'])) {
                return redirect()->back()->with('status', 'Favor informar ambas as datas de Início e Fim');
            }
        } else {
            if (isset($input['periodo_apuracao']) && !empty($input['periodo_apuracao']) && strlen($input['periodo_apuracao']) == 7) {
                $periodo_apuracao = str_replace('/', '', $input['periodo_apuracao']);
            } else {
                return redirect()->back()->with('status', 'Favor informar o Período de Apuração no formato MM/AAAA');
            }
        }
        
        if (!isset($input['empresas_selected']) || empty($input['empresas_selected'])) {
            return redirect()->back()->with('status', 'Favor informar a(s) empresa(s)');
        }
        
        // selecao por datas e não por período de apuração
        if (!isset($input['periodo_apuracao']) || empty($input['periodo_apuracao'])) {
            $input['data_inicio'] = implode("/", array_reverse(explode("-", $input['data_inicio'])));
            $input['data_fim'] = implode("/", array_reverse(explode("-", $input['data_fim'])));

            $dateStart      = $input['data_inicio'];
            $dateStart      = implode('-', array_reverse(explode('/', substr($dateStart, 0, 10)))).substr($dateStart, 10);
            $dateStart      = new \DateTime($dateStart);

            $dateEnd        = $input['data_fim'];
            $dateEnd        = implode('-', array_reverse(explode('/', substr($dateEnd, 0, 10)))).substr($dateEnd, 10);
            $dateEnd        = new \DateTime($dateEnd);
            $dateRange = array();
            
            while($dateStart <= $dateEnd){
                $dateRange[] = $dateStart->format('Y-m-d');
                $dateStart = $dateStart->modify('+1day');
            }

            $datas = $dateRange;

            //datas
            $string['Datas'] = "'0000-00-00',";
            foreach ($datas as $k => $v) {
                $string['Datas'] .= "'".$v."',";
            }
            $string['Datas'] = substr($string['Datas'], 0, -1);
        }
        
        //empresas
        $string['emps'] = '';
        foreach ($input['empresas_selected'] as $k => $v) {
            $string['emps'] .= $v.",";
        }
        $string['emps'] = substr($string['emps'], 0, -1);

        //analistas
        $string['analista_selected'] = '';
        if (!empty($input['analista_selected'])) {
            foreach ($input['analista_selected'] as $k => $v) {
                $string['analista_selected'] .= $v.",";
            }
            $string['analista_selected'] = substr($string['analista_selected'], 0, -1);
        }

        $string['estabelecimentos'] = '';
        if (!empty($input['estabelecimento_selected'])) {
            foreach ($input['estabelecimento_selected'] as $k => $v) {
                $string['estabelecimentos'] .= $v.",";
            }
            $string['estabelecimentos'] = substr($string['estabelecimentos'], 0, -1);
        }

        $string['tributos'] = '';
        if (!empty($input['tributos_selected'])) {
            foreach ($input['tributos_selected'] as $k => $v) {
                $string['tributos'] .= $v.",";
            }
            $string['tributos'] = substr($string['tributos'], 0, -1);
        }

        $string['status'] = '';
        if (!empty($input['status_selected'])) {
            foreach ($input['status_selected'] as $k => $v) {
                $string['status'] .= $v.",";
            }
            $string['status'] = substr($string['status'], 0, -1);
        }
        $user = User::findOrFail(Auth::user()->id);
        $query = "SELECT
									ca.id AS CronogramaAtividadeId,
									ca.descricao AS Atividade,
									t.nome AS NomeTributo,
									a.status AS AtividadeStatus,
									DATE_FORMAT(ca.data_atividade, '%Y-%m-%d %H:%i:%s') AS DataAtividade,
									a.data_aprovacao AS DataAprovacao
				FROM
					agenda.previsaocarga pg
                INNER JOIN agenda.tributos t ON pg.Tributo_id = t.id
                LEFT JOIN agenda.regras r ON t.id = r.tributo_id
                LEFT JOIN agenda.cronogramaatividades ca ON r.id = ca.regra_id
                LEFT JOIN agenda.estabelecimentos e ON ca.estemp_id = e.id
                LEFT JOIN agenda.municipios m ON e.cod_municipio = m.codigo
                LEFT JOIN agenda.tempoatividade ta ON pg.Tributo_id = ta.Tributo_id 
                LEFT JOIN agenda.ordemapuracao oa ON t.id = oa.Tributo_id
                LEFT join agenda.users u ON ca.Id_usuario_analista = u.id
                LEFT join agenda.atividades a ON ca.regra_id = a.regra_id ";

		$query .= " WHERE a.emp_id = ca.emp_id
				AND a.estemp_id = ca.estemp_id
                AND a.periodo_apuracao = ca.periodo_apuracao 
                AND m.uf = ta.UF 
                AND ta.Empresa_id = ca.emp_id ";
        if (!isset($input['periodo_apuracao']) || empty($input['periodo_apuracao'])) {
            $query .= " AND DATE_FORMAT(ca.data_atividade, '%Y-%m-%d') in (".$string['Datas'].") ";
        } else {
            $query .= " AND	ca.periodo_apuracao = '$periodo_apuracao' ";
        }
        $query .= " AND ca.emp_id in (".$string['emps'].") ";

        if (!empty($string['analista_selected']) && !$user->hasRole('analyst')) {
            $query .= " AND ca.Id_usuario_analista in (".$string['analista_selected'].") ";
        }

        if (!empty($string['estabelecimentos'])) {
            $query .= " AND ca.estemp_id in (".$string['estabelecimentos'].") ";
        }

        if (!empty($string['tributos'])) {
            $query .= " AND t.id in (".$string['tributos'].") ";
        }

        if (!empty($string['status'])) {
            $query .= " AND ca.status in (".$string['status'].") ";
        }

        if ($user->hasRole('analyst')){
            $query .= ' AND ca.Id_usuario_analista = '.$user->id;
        }
        
        $query .= " GROUP BY ca.id ORDER BY ca.limite, oa.Prioridade, ca.regra_id, ca.Id_usuario_analista, ca.id DESC";
//				echo $query; exit;
        $atividades_estab = DB::select($query);

        if ($user->hasRole('analyst')){
            $atividades_estab = $atividades_estab->where('cronogramaatividades.Id_usuario_analista', $user->id);
        }
        
		    $arrTributos = [];
		    foreach($atividades_estab as $key => $value){

		      $atrasado = false;
		    	$dataAtividade = new \DateTime($value->DataAtividade);
			
			    if(in_array($value->AtividadeStatus, [1,2]) && strtotime($dataAtividade->format('Y-m-d')) < strtotime(date('Y-m-d'))){
				    $atrasado = true;
			    }
		    	if($value->AtividadeStatus == 3 && !empty($value->DataAprovacao)){
		    		$dataAprovacao = new \DateTime($value->DataAprovacao);
		    		if(strtotime($dataAprovacao->format('Y-m-d')) > strtotime($dataAtividade->format('Y-m-d'))){
			        $atrasado = true;
				    }
			    }
		    	if(!array_key_exists($value->NomeTributo.'-'.$dataAtividade->format('Y-m-d'), $arrTributos)){
			      $arrTributos[$value->NomeTributo.'-'.$dataAtividade->format('Y-m-d')] = [
			      	'id' => $value->CronogramaAtividadeId,
				      'atividade' => $value->Atividade,
			      	'tributo' => $value->NomeTributo,
				      'data' => $dataAtividade->format('Y-m-d'),
				      'atrasado' => $atrasado
			      ];
			    }else{
		    		if($arrTributos[$value->NomeTributo.'-'.$dataAtividade->format('Y-m-d')]['atrasado'] == false){
				      $arrTributos[$value->NomeTributo.'-'.$dataAtividade->format('Y-m-d')]['atrasado'] = $atrasado;
				    }
			    }
	      }
//	      echo '<pre>', print_r($arrTributos); exit;
	    
	      foreach($arrTributos as $key => $value){
		
	        $cor = '#3788d8';
	      	if($value['atrasado'] == true){
            $cor = '#c31212';
		      }
		      $events[] = \Calendar::event(
			      $value['atividade'],
			      false,
			      $value['data'],
			      $value['data'],
			      $value['id'],
			      [
				      'url' => url('/uploadCron/'.substr($value['data'], 0,10).'/entrega/data'),
				      'textColor' => $cor
			      ]
		      );
	      }
//	    echo '<pre>', print_r($events); exit;
	      
        //Geração do calendario
        $mes = substr($periodo_apuracao, 0, -4)+1;
        $ano = substr($periodo_apuracao, 2);
        if (strlen($mes) == 1) {
            $mes = '0'.$mes;
        }
        if($mes == 13){
            $mes = 1;
            $ano++;
        }
        $dataAcima = $ano.'-'.$mes.'';

        $calendar = \Calendar::addEvents($events) //add an array with addEvents
        ->setOptions([ //set fullcalendar options
	          'plugins' => [ 'dayGrid' ],
            'lang' => 'pt',
            'firstDay' => 1,
            'defaultDate' => $dataAcima,
            'aspectRatio' => 2.5,
            'header' => ['left' => 'prev.next', 'center'=>'title'] //, 'right' => 'month,agendaWeek'
        ])
            ->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
                'viewRender' => 'function() { }'
            ]);

        return view('cronogramaatividades.calendar', compact('calendar'));
    }

    public function GerarConsultaCalendario()
    {
        $empresas = Empresa::selectRaw("razao_social, id")->pluck('razao_social','id');

        $estabelecimentos = Estabelecimento::selectRaw("concat(razao_social, ' - ', codigo, ' - ', cnpj) as razao_social, id")->orderby('codigo')->pluck('razao_social','id');

        $tributos = Tributo::selectRaw("nome, id")->pluck('nome','id');
        $status = array('1' => 'Entrega não efetuada', '2' => 'Entrega em aprovação', '3' => 'Entrega efetuada');
        $ids = '4,6';
        $user_ids = DB::select('select user_id from role_user where role_id in ('.$ids.')');
        $user_ids = json_decode(json_encode($user_ids),true);
        $analistas = User::selectRaw("name, id")->whereIN("id", $user_ids)->orderby('name', 'asc')->pluck('name','id');

        return view('cronogramaatividades.generateConsultaCalendario')->with('empresas',$empresas)->with('estabelecimentos',$estabelecimentos)->with('analistas',$analistas)->with('tributos',$tributos)->with('status', $status);
    }

}