<?php
/**
 * Created by PhpStorm.
 * User: Silver
 * Date: 10/03/2016
 * Time: 11:28
 */

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Log;
use DateTime;
use Carbon\Carbon;
use App\Http\Requests;
use App\Helpers\Helper;
use App\Models\Cron;
use App\Models\User;
use App\Models\Regra;
use App\Models\Empresa;
use App\Models\Tributo;
use App\Models\Atividade;
use App\Models\Municipio;
use App\Models\OrdemApuracao;
use App\Models\Estabelecimento;
use App\Models\FeriadoEstadual;
use App\Models\CronogramaMensal;
use App\Models\CronogramaStatus;
use App\Models\CronogramaAtividade;
use App\Models\AnalistaDisponibilidade;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use SebastianBergmann\Environment\Console;
//use Illuminate\Mail\Mailer;
//use Swift_Mailer;

class EntregaService {

	protected $notification_system;
	public $array = array();
	public $qtd_estabs = array();
	public $prioridade = array();

	private $lastDate;
	private $minutosTrabalhadosNoDia;

	function __construct()
	{
		$this->notification_system = true; //ENABLED = true / DISABLED = false
	}

	public function calculaProximasEntregasEstemp($cnpj,$offset=null){

		$estemp = null;
		$isEmpresa = (substr($cnpj,8,4)=='0001');
		if ($isEmpresa) {
			$estemp = Empresa::where('cnpj', $cnpj)->first();
		} else {
			$estemp = Estabelecimento::where('cnpj', $cnpj)->first();
		}
		$cod_municipio = $estemp->cod_municipio;
		$uf = $estemp->municipio->uf;

		$param = null;

		if ($isEmpresa) {
			$param = array('cnpj'=>$estemp->cnpj,'IE'=>$estemp->insc_estadual);
			$regras = DB::table('regras')
				->join('tributos', 'tributos.id', '=', 'regras.tributo_id')
				->select('regras.*', 'tributos.nome AS tnome')
				->where('ref', $uf)
				->orWhere('ref','MATRIZ')
				->orWhere('ref', $cod_municipio)
				->get();
		} else {
			$param = array('cnpj'=>$estemp->empresa->cnpj,'IE'=>$estemp->empresa->insc_estadual);
			$regras = DB::table('regras')
				->join('tributos', 'tributos.id', '=', 'regras.tributo_id')
				->select('regras.*', 'tributos.nome AS tnome')
				->where('ref', $uf)
				->orWhere('ref', $cod_municipio)
				->get();
		}
		$prox_entregas = array();
		foreach($regras as $regra) {

			$entrega = array();
			$nome_tributo = $regra->tnome;
			if ($regra->nome_especifico) {
				$nome_tributo .= ' ('.$regra->nome_especifico.')';
			}
			$adiant_fds = $regra->afds;

			if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

				$data = $this->calculaProximaDataRegrasEspeciais($regra->regra_entrega,$param, null, $offset,$adiant_fds);
				$entrega = array('desc'=>$nome_tributo,'data'=>$data[0]['data']);

			} else {
				$data = $this->calculaProximaData($regra->regra_entrega, null, $offset,$adiant_fds);
				$entrega = array('desc'=>$nome_tributo,'data'=>$data);
			}

			$prox_entregas[] = $entrega;
		}
		usort($prox_entregas, function ($a, $b) {
			if ($a['data'] == $b['data']) {
				return 0;
			}
			return ($a['data'] < $b['data']) ? -1 : 1;
		});

		return $prox_entregas;
	}

	public function calculaProximaData($regra, $periodo=null, $offset=null, $adiant_fds=true)
	{
		/* Attenzione - Manca considerare i giorni festivi!!! */

		$tipo_periodo = substr($regra,0,2);
		$valor_periodo = substr($regra,2,2);
		$tipo_dia = substr($regra,4,1);
		$val_sign = substr($regra,5,1);
		$val_dia = substr($regra,6,2);

		//Carbon::setLocale(LC_TIME,'pt_BR');
		Carbon::setTestNow();  //reset
		if ($periodo!=null) {
			$month = 1;
			if (strlen($periodo)>4) {
				$month = intval(substr($periodo, 0, 2));
			}
			$year = intval(substr($periodo,-4,4));
			Carbon::setTestNow(Carbon::createFromDate($year, $month, 1, config('configICMSVars.wamp.timezone_brt')));
		}

		if ($tipo_periodo == 'MS') {
			if ($tipo_dia == 'F') {
				for ($i = 1; $i <= $valor_periodo; $i++) {
					if ($val_sign=='+') {
						Carbon::setTestNow(Carbon::parse('first day of next month')->startOfDay()->addDays($val_dia)->subHours(6));
					} else {
						Carbon::setTestNow(Carbon::parse('last day of next month')->startOfDay()->subDays($val_dia)->subHours(6));
					}
					if (Carbon::now()->isWeekEnd()){
						if ($adiant_fds) {
							Carbon::setTestNow(Carbon::parse('last friday'));
						} else {
							Carbon::setTestNow(Carbon::parse('next monday'));
						}
					}
				}
			} else if ($tipo_dia == 'U') {
				for ($i = 1; $i <= $valor_periodo; $i++) {
					if ($val_sign=='+') {
						Carbon::setTestNow(Carbon::parse('first day of next month')->startOfDay()->addWeekDays($val_dia)->subHours(6));
					} else {
						Carbon::setTestNow(Carbon::parse('last day of next month')->startOfDay()->subWeekDays($val_dia)->subHours(6));
					}

				}
			}
		} else if ($tipo_periodo == 'QS') {
			$addQ = 15*($valor_periodo-1); //var_dump($addQ); var_dump($tipo_dia); var_dump($val_sign);
			//Estamos na primeira quinzena
			if ($tipo_dia == 'F') {
				if ($val_sign == '+') {
					Carbon::setTestNow(Carbon::parse("first day of next month")->addDays($addQ+$val_dia-1)->startOfDay()->addHours(18));
				} else {
					Carbon::setTestNow(Carbon::parse('first day of next month')->addDays($addQ+15-$val_dia)->startOfDay()->addHours(18));
				}
				if (Carbon::now()->isWeekEnd()){
					if ($adiant_fds) {
						Carbon::setTestNow(Carbon::parse('last friday'));
					} else {
						Carbon::setTestNow(Carbon::parse('next monday'));
					}
				} else if ($tipo_dia == 'U') {
					if ($val_sign == '+') {
						Carbon::setTestNow(Carbon::parse("first day of next month")->addDays($addQ)->addWeekDays($val_dia-1)->startOfDay()->addHours(18));
					} else {
						Carbon::setTestNow(Carbon::parse('first day of next month')->addDays($addQ+15)->subWeekDays($val_dia)->startOfDay()->addHours(18));
					}

				}
			}

		} else if ($tipo_periodo == 'AS') {   //Somente para AS1DF+DDMM

			$val_mes = substr($regra,8,2);

			Carbon::setTestNow(Carbon::parse('first day of January next year')->startOfDay()->addMonths($val_mes-1)->addDays($val_dia)->subHours(6));

			if (Carbon::now()->isWeekEnd()){
				if ($adiant_fds) {
					Carbon::setTestNow(Carbon::parse('last friday'));
				} else {
					Carbon::setTestNow(Carbon::parse('next monday'));
				}
			}

		}

		if ($offset!=null){
			Carbon::setTestNow(Carbon::now()->subWeekDays($offset));
		}

		return Carbon::now()->endOfDay();
	}

	public function calculaProximaDataRegrasEspeciais($regra, $param=null, $periodo=null, $offset=null, $adiant_fds=true) {

		$retval_array = array();

		switch ($regra) {
			case 'RE01':    //GIA SP - $param = último dígito do número de Inscrição Estadual
				if ($param) {
					$retval = null;
					switch(substr($param['IE'],-1,1)) {
						case '0':
						case '1':
							$retval = array('data' => $this->calculaProximaData("MS1DF+16",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 0/1');
							break;
						case '2':
						case '3':
						case '4':
							$retval = array('data' => $this->calculaProximaData("MS1DF+17",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 2/3/4');
							break;
						case '5':
						case '6':
						case '7':
							$retval = array('data' => $this->calculaProximaData("MS1DF+18",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 5/6/7');
							break;
						case '8':
						case '9':
							$retval = array('data' => $this->calculaProximaData("MS1DF+19",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 8/9');
							break;
					}
					$retval_array[] = $retval;

				} else {   //Regra geral
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+16",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 0/1');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+17",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 2/3/4');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+18",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 5/6/7');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+19",$periodo,$offset,$adiant_fds), 'desc' => 'GIA SP - IE finais 8/9');
				}
				break;
			case 'RE02':    //ICMS - Livro Eletronico - DF - $param = 8 dígito do cnpj
				if ($param) {
					$retval = null;
					switch(substr($param['cnpj'],7,1)) {
						case '0':
						case '1':
							$retval = array('data' => $this->calculaProximaData("MS1DF+24",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 0/1');
							break;
						case '2':
						case '3':
							$retval = array('data' => $this->calculaProximaData("MS1DF+25",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 2/3');
							break;
						case '4':
						case '5':
							$retval = array('data' => $this->calculaProximaData("MS1DF+26",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 4/5');
							break;
						case '6':
						case '7':
							$retval = array('data' => $this->calculaProximaData("MS1DF+27",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 6/7');
							break;
						case '8':
						case '9':
							$retval = array('data' => $this->calculaProximaData("MS1DF+28",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 8/9');
							break;
					}
					$retval_array[] = $retval;

				} else {    //Regra geral
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+24",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 0/1');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+25",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 2/3');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+26",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 4/5');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+27",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 6/7');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+28",$periodo,$offset,$adiant_fds), 'desc' => 'ICMS DF 8dig CNPJ = 8/9');
				}
				break;
			case 'RE03':    //DIPAM SP - $param = último dígito do número de Inscrição Estadual
				if ($param) {
					$retval = null;
					switch(substr($param['IE'],-1,1)) {
						case '0':
						case '1':
							$retval = array('data'=>$this->calculaProximaData("MS1DF+16",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 0/1');
							break;
						case '2':
						case '3':
						case '4':
							$retval = array('data'=>$this->calculaProximaData("MS1DF+17",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 2/3/4');
							break;
						case '5':
						case '6':
						case '7':
							$retval = array('data'=>$this->calculaProximaData("MS1DF+18",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 5/6/7');
							break;
						case '8':
						case '9':
							$retval = array('data'=>$this->calculaProximaData("MS1DF+19",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 8/9');
							break;
					}
					$retval_array[] = $retval;

				} else {   //Regra geral
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+16",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 0/1');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+17",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 2/3/4');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+18",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 5/6/7');
					$retval_array[] = array('data' => $this->calculaProximaData("MS1DF+19",$periodo,$offset,$adiant_fds), 'desc' => 'DIPAM SP - IE finais 8/9');
				}
				break;
		}

		return $retval_array;
	}

	public function getFeriadosNacionais($ano=null)
	{
		$formatoDataDeComparacao    =  "d-m"; // Dia / Mês
		//$diaDeComparacao            = date("d-m",strtotime($data));
		//$ano = intval(date('Y',strtotime($data)));
		if ($ano==null) $ano = date('Y');

		$pascoa = easter_date($ano); // Limite de 1970 ou após 2037 da easter_date PHP consulta http://www.php.net/manual/pt_BR/function.easter-date.php
		$dia_pascoa = date('j', $pascoa);
		$mes_pascoa = date('n', $pascoa);
		$ano_pascoa = date('Y', $pascoa);

		$feriados = array(
			// Tatas Fixas dos feriados Nacionail Basileiras
			'Confraternização Universal - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 1, 1, $ano)), // Confraternização Universal - Lei nº 662, de 06/04/49
			'Tiradentes - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 4, 21, $ano)), // Tiradentes - Lei nº 662, de 06/04/49
			'Dia do Trabalhador - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 5, 1, $ano)), // Dia do Trabalhador - Lei nº 662, de 06/04/49
			'Dia da Independência - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 9, 7, $ano)), // Dia da Independência - Lei nº 662, de 06/04/49
			'N. S. Aparecida - Lei nº 6802'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 10, 12, $ano)), // N. S. Aparecida - Lei nº 6802, de 30/06/80
			'Todos os santos - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 11, 2, $ano)), // Todos os santos - Lei nº 662, de 06/04/49
			'Proclamação da republica - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 11, 15, $ano)), // Proclamação da republica - Lei nº 662, de 06/04/49
			'Natal - Lei nº 662'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, 12, 25, $ano)), // Natal - Lei nº 662, de 06/04/49

			// These days have a date depending on easter
			'2ºfeira Carnaval'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 48, $ano_pascoa)),//2ºferia Carnaval
			'3ºfeira Carnaval'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 47, $ano_pascoa)),//3ºferia Carnaval
			'6ºfeira Santa'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 2, $ano_pascoa)),//6ºfeira Santa
			'Páscoa'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa, $ano_pascoa)),//Pascoa
			'Corpus Christ'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa + 60, $ano_pascoa)),//Corpus Christ
		);

		return $feriados;
	}

	public function getFeriadosEstaduais()
	{
		$retval = FeriadoEstadual::all();

		//$feriados_estaduais = explode(';',$retval->first()->datas);

		return $retval;

	}

	public function sendMail($user_rec, $data, $content_page='emails.test', $array = false) {
		//  echo "<pre>".$user_rec."<br><br>";
		// print_r($data)."</pre><br><br>";
		// echo $content_page."<br><br></pre>";

		if ($this->notification_system) {
			// $user_rec = 'viniciuskawakami@hotmail.com';
			// note, to use $subject within your closure below you have to pass it along in the "use (...)" clause.
			if (!$array) {
				Mail::send($content_page, ['data' => $data, 'user' => $user_rec], function ($message) use ($data, $user_rec) {
					// note: if you don't set this, it will use the defaults from config/mail.php

					$message->from('no-reply-please@bravobpo.com.br', 'Bravo Plataforma - Fiscal');
					$message->to($user_rec->email, $user_rec->name)->subject($data['subject']); //$user_rec->email
				});
			}

			if ($array) {
				Mail::send($content_page, ['data' => $data, 'user' => $user_rec], function ($message) use ($data, $user_rec) {
					// note: if you don't set this, it will use the defaults from config/mail.php

					$message->from('taxcalendar@bravobpo.com.br', 'Bravo Plataforma - Fiscal');
					$message->to($user_rec)->subject($data['subject']); //$user_rec->email
				});
			}
		}
	}

	public function writeLog($description,$type='ADM') {
		$log = new Log();
		$log->user_id = Auth::user()->id;
		$log->description = $description;
		$log->type = $type;
		$log->save();
	}


	public function generateSingleCnpjCronActivities($periodo_apuracao,$cnpj,$codigo,$tributo_id) {
		// Single 'estabelecimento' generation for newly registered
		$generate = true;
		//
		$estab = Estabelecimento::where('cnpj',$cnpj)->where('codigo',$codigo)->firstOrFail();
		$empresa = Empresa::where('id',$estab->empresa_id)->firstOrFail();

		//Verifica existencia atividades
		if ($tributo_id==0) {
			$exists = CronogramaAtividade::where('periodo_apuracao', $periodo_apuracao)->where('estemp_type','estab')->where('estemp_id',$estab->id)->count();
		} else {
			$exists = CronogramaAtividade::where('periodo_apuracao', $periodo_apuracao)->where('estemp_type','estab')->where('estemp_id',$estab->id)->whereHas('regra.tributo', function ($query) use ($tributo_id) {
				$query->where('id', $tributo_id);
			})->count();
		}

		if ($exists >0 || $estab->ativo == 0) {
			$generate = false;
		}

		$id_cronogramastatus = DB::getPdo()->lastInsertId();

		if ($generate) {
			//TODAS AS REGRAS ATIVAS
			$matchThese = ['freq_entrega'=>'M','ativo' => 1, 'ref'=>$estab->municipio->uf];
			$orThose = ['freq_entrega'=>'M','ativo' => 1, 'ref'=>$estab->municipio->codigo];
			//FILTRO TRIBUTO
			if ($tributo_id>0) {
				$matchThese['tributo_id']= $tributo_id;
				$orThose   ['tributo_id']= $tributo_id;
			}
			//FILTRO BLOQUEIO DE REGRA
			$blacklist = array();  //Lista dos estab (id) que não estão ativos para esta regra
			foreach($estab->regras as $el) {
				$blacklist[] = $el->id;
			}

			if (sizeof($blacklist)>0) {
				$regras = Regra::whereNotIn('id',$blacklist)->where($matchThese)->orWhere($orThose)->get();
			} else {
				$regras = Regra::where($matchThese)->orWhere($orThose)->get();
			}

			//GERAÇÂO
			$count = 0;
			foreach ($regras as $regra) {

				$trib = DB::table('tributos')
					->join('empresa_tributo', 'tributos.id', '=', 'empresa_tributo.tributo_id')
					->join('empresas', 'empresas.id', '=', 'empresa_tributo.empresa_id')
					->select('empresa_tributo.adiantamento_entrega')
					->where('tributos.id',$regra->tributo->id)
					->where('empresas.cnpj',$empresa->cnpj)
					->get();

				//VERIFICA ADIANTAMENTO DE ENTREGA
				$offset = null;
				if (!empty($trib[0]->adiantamento_entrega)) {
					$offset = $trib[0]->adiantamento_entrega;
				}

				//VERIFICA REGRA PARA GESTAO DAS ATIVIDADES QUE CAEM NO FIM DA SEMANA
				$adiant_fds = $regra->afds;

				$val = array();
				// Regras Especiais
				if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

					$session = Session::all();
					$ult = array_pop( $session );
					$id_user = array_pop( $session );
					if (!is_numeric($id_user)) {
						$id_user = $ult;
					}

					$param = array('cnpj'=>$estab->cnpj,'IE'=>$estab->insc_estadual);
					$retval_array = $this->calculaProximaDataRegrasEspeciais($regra->regra_entrega,$param,$periodo_apuracao,$offset,$adiant_fds);

					foreach ($retval_array as $el) {
						$data_limite = $el['data']->toDateTimeString();
						$alerta = intval($regra->tributo->alerta);
						$inicio_aviso = $el['data']->subDays($alerta)->toDateTimeString();
						$desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';
						$val = array(
							'descricao' => $desc_prefix . $el['desc'],
							'recibo' => $regra->tributo->recibo,
							'status' => 1,
							'periodo_apuracao' => $periodo_apuracao,
							'inicio_aviso' => $inicio_aviso,
							'limite' => $data_limite,
							'tipo_geracao' => 'A',
							'regra_id' => $regra->id,
							'Data_cronograma' => date('Y-m-d H:i:s'),
							'data_atividade' => date('Y-m-d H:i:s'),
							'Resp_cronograma' => $id_user
						);
					}

				} else {  // Regra standard

					$ref = $regra->ref;
					if ($municipio = Municipio::find($regra->ref)) {
						$ref = $municipio->nome . ' (' . $municipio->uf . ')';
					}
					$nome_especifico = $regra->nome_especifico;
					if (!$nome_especifico) {
						$nome_especifico = $regra->tributo->nome;
					}
					$desc = $nome_especifico . ' ' . $ref;
					$desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';

					$session = Session::all();
					$ult = array_pop( $session );
					$id_user = array_pop( $session );
					if (!is_numeric($id_user)) {
						$id_user = $ult;
					}

					$data = $this->calculaProximaData($regra->regra_entrega,$periodo_apuracao,$offset,$adiant_fds);
					$data_limite = $data->toDateTimeString();
					$alerta = intval($regra->tributo->alerta);
					$inicio_aviso = $data->subDays($alerta)->toDateTimeString();

					$val = array(
						'descricao' => $desc_prefix . $desc,
						'recibo' => $regra->tributo->recibo,
						'status' => 1,
						'periodo_apuracao' => $periodo_apuracao,
						'inicio_aviso' => $inicio_aviso,
						'limite' => $data_limite,
						'tipo_geracao' => 'A',
						'regra_id' => $regra->id
					);

				}
				$uf_cron = Municipio::find($estab->cod_municipio);

				//CRIA ATIVIDADE
				$val['estemp_type'] = 'estab';
				$val['estemp_id'] = $estab->id;
				$val['emp_id'] = $estab->empresa_id;

				$analista = $this->loadAnalista($val);
				if (!empty($analista)) {
					$val['Id_usuario_analista'] = $analista;
				}

				$val['Resp_cronograma'] =$id_user;
				$val['Data_cronograma'] = date('Y-m-d H:i:s');
				$val['data_atividade'] = date('Y-m-d H:i:s');

				if ($val['estemp_id'] > 0) {
					$estabelecimento_tempo = Estabelecimento::find($val['estemp_id']);
					if (!empty($estabelecimento_tempo)) {
						$uf_cron = Municipio::find($estabelecimento_tempo->cod_municipio);
						$val['tempo'] = $this->getTempo($regra->tributo->id, $uf_cron->uf);
					}
				}

				if (!$this->checkDuplicidadeCronograma($val)) {
					continue;
				}
				$nova_atividade = CronogramaAtividade::create($val);
				if (!empty($val)) {
					$val['id'] = $nova_atividade->id;
					$this->array[$val['estemp_id']][$tributo_id][] = $val;
					$this->qtd_estabs[$regra->tributo->id][$uf_cron->uf][$val['estemp_id']][] = $val;
					$this->prioridade[$tributo_id][] = $val;
				}
				$count++;
			}
		}

		if (!empty($this->array)) {
			$this->generateMensal($this->array, $id_cronogramastatus);
			// TODO: vamos criar nova função para calcular a data_atividade
			// $this->setPriority($this->prioridade, $periodo_apuracao, $empresa->id, $id_cronogramastatus);
			$this->setPriorityActivityDate($this->prioridade, $periodo_apuracao, $empresa->id, $id_cronogramastatus);
		}

		return $generate;

	}

	private function loadAnalista($val)
	{
		$regra = Regra::findorFail($val['regra_id']);
		$query = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B inner join atividadeanalistafilial C on B.id = C.Id_atividadeanalista where B.Tributo_id = " .$regra->tributo->id. " and B.Emp_id = " .$val['emp_id']. " AND C.Id_atividadeanalista = B.id AND C.Id_estabelecimento = " .$val['estemp_id']. " AND B.Regra_geral = 'N') limit 1";

		$retornodaquery = DB::select($query);

		$sql = "select A.id FROM users A where A.id IN (select B.id_usuario_analista FROM atividadeanalista B where B.Tributo_id = " .$regra->tributo->id. " and B.Emp_id = " .$val['emp_id']. " AND B.Regra_geral = 'S') limit 1";

		$queryGeral = DB::select($sql);

		$idanalistas = $retornodaquery;
		if (empty($retornodaquery)) {
			$idanalistas = $queryGeral;
		}
		$analistafinal = 0;
		if (!empty($idanalistas)) {
			foreach ($idanalistas as $k => $analista) {
				$analistafinal = $analista->id;
			}
		}
		return $analistafinal;
	}

	public function generateMensal($array, $id_cronogramastatus)
	{
		DB::table('cronogramastatus')
		->where('id', $id_cronogramastatus)
		->update(['qtd_mensal' => 0]);

		$var = array();
		$cc = 0;
		foreach ($array as $estab_id => $single) {
			foreach ($single as $tributo => $mostsingle) {
				$generate = 1;
				foreach ($mostsingle as $key => $atividade) {
					$var['Tempo_estab'] = $atividade['tempo'];
					$var['DATA_SLA'] = $atividade['limite'];
					$var['periodo_apuracao'] = $atividade['periodo_apuracao'];
					$var['Empresa_id'] = $atividade['emp_id'];

					$Regra = Regra::find($atividade['regra_id']);
					$Estabelecimento = Estabelecimento::find($atividade['estemp_id']);
					$Municipio = Municipio::find($Estabelecimento->cod_municipio);
					$var['Tributo_id'] = $tributo;
					$var['uf'] = $Municipio->uf;

					$var['Qtde_estab'] = count($this->qtd_estabs[$tributo][$Municipio->uf]);

					$tempo = $this->getTempo($tributo, $Municipio->uf);
					$var['Tempo_total'] = $tempo * $var['Qtde_estab'];

					$data_carga = DB::Select('SELECT A.Data_prev_carga FROM previsaocarga A WHERE A.periodo_apuracao = "'.$atividade['periodo_apuracao'].'" AND A.Tributo_id = '.$var['Tributo_id']);

					if (!empty($data_carga) && $generate) {
						$generate = 0;
						$var['Qtd_dias'] = $this->diffTempo(substr($atividade['limite'], 0,10), $data_carga[0]->Data_prev_carga);
						$var['Tempo_geracao'] = $var['Qtd_dias'] * 480;
						if ($var['Tempo_geracao'] <= 0) {
							$var['Tempo_geracao'] = 1; }
						$var['Qtd_analistas'] = $var['Tempo_total']/$var['Tempo_geracao'];
					}
					if ($this->checkduplicidadeMensal($var)) {
						$result_cronograma = CronogramaMensal::Create($var);
						$this->CronogramaAtividadeMensal($result_cronograma->id, $atividade);
					}
				}
				$cc++;
				DB::table('cronogramastatus')
					->where('id', $id_cronogramastatus)
					->update(['qtd_mensal' => $cc]);
			}
		}

		return true;
	}

	private function checkduplicidadeMensal($value)
	{
		$mensal = CronogramaMensal::where('periodo_apuracao', $value['periodo_apuracao'])->where('Empresa_id', $value['Empresa_id'])->where('Tributo_id', $value['Tributo_id'])->where('uf', $value['uf'])->get();

		if (count($mensal) > 0) {
			return false;
		}

		return true;
	}


	private function CronogramaAtividadeMensal($id, $atividade)
	{
		$regra = Regra::findorFail($atividade['regra_id']);
		$estabelecimento = Estabelecimento::findorFail($atividade['estemp_id']);

		$atividades = DB::table('cronogramaatividades')
			->join('regras', 'cronogramaatividades.regra_id', '=', 'regras.id')
			->join('estabelecimentos', 'cronogramaatividades.estemp_id', '=', 'estabelecimentos.id')
			->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
			->select('cronogramaatividades.*')
			->where('regras.tributo_id', $regra->tributo->id)
			->where('cronogramaatividades.emp_id',$atividade['emp_id'])
			->where('municipios.uf',$estabelecimento->municipio->uf)
			->where('cronogramaatividades.periodo_apuracao',$atividade['periodo_apuracao'])
			->get();

		if (!empty($atividades)) {
			foreach ($atividades as $key => $single) {
				$single_activity = CronogramaAtividade::findorFail($single->id);
				$single_activity->cronograma_mensal = $id;
				$single_activity->save();
			}
		}

		// CronogramaAtividade::where('regra_id',$atividade['regra_id'])
		// ->where('emp_id',$atividade['emp_id'])
		// ->where('emp_id',$atividade['emp_id'])
		// ->where('periodo_apuracao',$atividade['periodo_apuracao'])
		// ->update(['cronograma_mensal' => $id]);
	}

	private function diffTempo($data1, $data2)
	{
		$data_inicio = new \DateTime($data1);
		$data_fim = new \DateTime($data2);

		$dateInterval = $data_inicio->diff($data_fim);
		return $dateInterval->days;
	}

	public function getTempo($tributo, $uf)
	{
		$tempo = 0;
		$tributo_tempo = DB::select('SELECT A.Qtd_minutos FROM tempoatividade A where A.Tributo_id ='.$tributo.' AND A.UF ="'.$uf.'"');

		if (!empty($tributo_tempo)) {
			$tempo = $tributo_tempo[0]->Qtd_minutos;
		}

		return $tempo;
	}


	public function generateSingleCnpjActivities($periodo_apuracao,$cnpj,$codigo,$tributo_id) {
		// Single 'estabelecimento' generation for newly registered
		$generate = true;
		//
		$estab = Estabelecimento::where('cnpj',$cnpj)->where('codigo',$codigo)->firstOrFail();
		$empresa = Empresa::where('id',$estab->empresa_id)->firstOrFail();

		//Verifica existencia atividades
		if ($tributo_id==0) {
			$exists = Atividade::where('periodo_apuracao', $periodo_apuracao)->where('estemp_type','estab')->where('estemp_id',$estab->id)->count();
		} else {
			$exists = Atividade::where('periodo_apuracao', $periodo_apuracao)->where('estemp_type','estab')->where('estemp_id',$estab->id)->whereHas('regra.tributo', function ($query) use ($tributo_id) {
				$query->where('id', $tributo_id);
			})->count();
		}

		if ($exists >0 || $estab->ativo == 0) {
			$generate = false;
		}

		if ($generate) {
			//TODAS AS REGRAS ATIVAS
			$matchThese = ['freq_entrega'=>'M','ativo' => 1, 'ref'=>$estab->municipio->uf];
			$orThose = ['freq_entrega'=>'M','ativo' => 1, 'ref'=>$estab->municipio->codigo];
			//FILTRO TRIBUTO
			if ($tributo_id>0) {
				$matchThese['tributo_id']= $tributo_id;
				$orThose   ['tributo_id']= $tributo_id;
			}
			//FILTRO BLOQUEIO DE REGRA
			$blacklist = array();  //Lista dos estab (id) que não estão ativos para esta regra
			foreach($estab->regras as $el) {
				$blacklist[] = $el->id;
			}

			if (sizeof($blacklist)>0) {
				$regras = Regra::whereNotIn('id',$blacklist)->where($matchThese)->orWhere($orThose)->get();
			} else {
				$regras = Regra::where($matchThese)->orWhere($orThose)->get();
			}

			//GERAÇÂO
			$count = 0;
			foreach ($regras as $regra) {

				$trib = DB::table('tributos')
					->join('empresa_tributo', 'tributos.id', '=', 'empresa_tributo.tributo_id')
					->join('empresas', 'empresas.id', '=', 'empresa_tributo.empresa_id')
					->select('empresa_tributo.adiantamento_entrega')
					->where('tributos.id',$regra->tributo->id)
					->where('empresas.cnpj',$empresa->cnpj)
					->get();

				//VERIFICA ADIANTAMENTO DE ENTREGA
				$offset = null;
				if (!empty($trib[0]->adiantamento_entrega)) {
					$offset = $trib[0]->adiantamento_entrega;
				}

				//VERIFICA REGRA PARA GESTAO DAS ATIVIDADES QUE CAEM NO FIM DA SEMANA
				$adiant_fds = $regra->afds;

				$val = array();
				// Regras Especiais
				if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

					$param = array('cnpj'=>$estab->cnpj,'IE'=>$estab->insc_estadual);
					$retval_array = $this->calculaProximaDataRegrasEspeciais($regra->regra_entrega,$param,$periodo_apuracao,$offset,$adiant_fds);


					foreach ($retval_array as $el) {
						$data_limite = $el['data']->toDateTimeString();
						$alerta = intval($regra->tributo->alerta);
						$inicio_aviso = $el['data']->subDays($alerta)->toDateTimeString();
						$desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';
						$val = array(
							'descricao' => $desc_prefix . $el['desc'],
							'recibo' => $regra->tributo->recibo,
							'status' => 1,
							'periodo_apuracao' => $periodo_apuracao,
							'inicio_aviso' => $inicio_aviso,
							'limite' => $data_limite,
							'tipo_geracao' => 'A',
							'regra_id' => $regra->id
						);

					}

				} else {  // Regra standard

					$ref = $regra->ref;
					if ($municipio = Municipio::find($regra->ref)) {
						$ref = $municipio->nome . ' (' . $municipio->uf . ')';
					}
					$nome_especifico = $regra->nome_especifico;
					if (!$nome_especifico) {
						$nome_especifico = $regra->tributo->nome;
					}
					$desc = $nome_especifico . ' ' . $ref;
					$desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';

					$data = $this->calculaProximaData($regra->regra_entrega,$periodo_apuracao,$offset,$adiant_fds);
					$data_limite = $data->toDateTimeString();
					$alerta = intval($regra->tributo->alerta);
					$inicio_aviso = $data->subDays($alerta)->toDateTimeString();

					$val = array(
						'descricao' => $desc_prefix . $desc,
						'recibo' => $regra->tributo->recibo,
						'status' => 1,
						'periodo_apuracao' => $periodo_apuracao,
						'inicio_aviso' => $inicio_aviso,
						'limite' => $data_limite,
						'tipo_geracao' => 'A',
						'regra_id' => $regra->id
					);

				}

				//CRIA ATIVIDADE
				$val['estemp_type'] = 'estab';
				$val['estemp_id'] = $estab->id;
				$val['emp_id'] = $estab->empresa_id;

				if (!$this->checkDuplicidade($val)) {
					continue;
				}

				if ($this->checkGeneration($regra->created_at, $regra->freq_entrega)) {
					$nova_atividade = Atividade::create($val);
					$count++;
				}
			}

		}

		return $generate;

	}

	private function findEstabelecimentoCNPJ($cnpj)
	{
		$id_estab_emp = 0;
		$queryEstabelecimentoIDCNPJ = DB::select("Select id FROM estabelecimentos where cnpj = '".$cnpj."' ");
		$jsonEstab = json_decode(json_encode($queryEstabelecimentoIDCNPJ),true);
		if (!empty($jsonEstab[0])) {
			$id_estab_emp = $jsonEstab[0]['id'];
		}

		return $id_estab_emp;
	}

	private function findEmpresaEstabelecimentoID($estabelecimentoID)
	{
		$id_empresa = 0;
		$findEmpresaEstabelecimentoID = DB::select("Select empresa_id FROM estabelecimentos where id = ".$estabelecimentoID." ");
		$jsonEstab = json_decode(json_encode($findEmpresaEstabelecimentoID),true);
		if (!empty($jsonEstab[0])) {
			$id_empresa = $jsonEstab[0]['empresa_id'];
		}

		return $id_empresa;
	}

	public function generateMonthlyActivities($periodo_apuracao,$cnpj_empresa, $tributo = null, $ref = null) {
		set_time_limit(0);
		// Activate auto activity generation
		$generate = true;
		//
		$empresa = Empresa::where('cnpj',$cnpj_empresa)->firstOrFail();

		if (Cron::where('periodo_apuracao', $periodo_apuracao)->where('emp_id', $empresa->id)->count() >0) {
			$generate = false;
		}

		//TODAS AS REGRAS ATIVAS PARA A EMPRESA SOLICITADA
		$empresa_tributos = $empresa->tributos()->get();
		$array_tributos_ativos = array();
		foreach($empresa_tributos as $at) {
			$array_tributos_ativos[] = $at->id;
		}
		//
		if(isset($tributo)){
			if($tributo == 0){
				$regras = Regra::where('freq_entrega','M')->where('ativo',1)->whereIN('tributo_id',$array_tributos_ativos)->get();
			}else{
				if(isset($ref)){
					$regras = Regra::where('freq_entrega','M')->where('ativo',1)->where('ref',$ref)->whereIN('tributo_id',[$tributo])->get();
				}else{
					$regras = Regra::where('freq_entrega','M')->where('ativo',1)->whereIN('tributo_id',[$tributo])->get();
				}
			}
		}else{
			$regras = Regra::where('freq_entrega','M')->where('ativo',1)->whereIN('tributo_id',$array_tributos_ativos)->get();
		}

		if ($generate) {
			$count = 0;
			foreach ($regras as $regra) {
				//VERIFICA CNPJ QUE FORAM BANIDOS PARA ESTA REGRA
				$blacklist = array();
				foreach($regra->estabelecimentos as $el) {
					$blacklist[] = $el->id;
				}

				//VERIFICA CNPJ QUE UTILIZAM A REGRA
				$ativ_estemps = array();
				if ($regra->tributo->tipo == 'F') { //Federal

					$empresas = DB::table('empresas')
						->select('id', 'cnpj')
						->where('cnpj',$empresa->cnpj)
						->get();

					$ativ_estemps = $empresas;

				} else if ($regra->tributo->tipo == 'E') { //Estadual

					$ref = $regra->ref;

					$empresas = DB::table('empresas')
						->select('empresas.cnpj', 'empresas.id', 'empresas.id', 'municipios.uf', 'municipios.nome', 'empresas.insc_estadual')
						->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
						->where('municipios.uf', $ref)
						->where('cnpj',$empresa->cnpj)
						->get();

					$estabs = DB::table('estabelecimentos')
						->select('estabelecimentos.cnpj', 'estabelecimentos.id', 'estabelecimentos.empresa_id', 'municipios.uf', 'municipios.nome','estabelecimentos.insc_estadual')
						->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
						->where('municipios.uf', $ref)
						->where('estabelecimentos.ativo', 1)
						->where('empresa_id',$empresa->id)
						->get();

					$ativ_estemps = array_merge($empresas, $estabs);

				} else { //Municipal
					$ref = $regra->ref;
					if (strlen($ref)==2) {  // O tributo é municipal, porem a regra é estadual

						$empresas = DB::table('empresas')
							->select('empresas.cnpj', 'empresas.id', 'empresas.id')
							->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
							->where('municipios.uf', $ref)
							->where('cnpj',$empresa->cnpj)
							->get();

						$estabs = DB::table('estabelecimentos AS est')
							->select('est.cnpj', 'est.id', 'est.empresa_id')
							->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
							->where('mun.uf', $ref)
							->where('est.ativo', 1)
							->where('empresa_id',$empresa->id)
							->get();

					} else {    // O tributo é municipal, e a regra é municipal

						$empresas = DB::table('empresas')
							->select('empresas.cnpj', 'empresas.id', 'empresas.id')
							->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
							->where('municipios.codigo', $ref)
							->where('cnpj',$empresa->cnpj)
							->get();

						$estabs = DB::table('estabelecimentos AS est')
							->select('est.cnpj', 'est.id', 'est.empresa_id')
							->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
							->where('mun.codigo', $ref)
							->where('est.ativo', 1)
							->where('empresa_id',$empresa->id)
							->get();
					}
					$ativ_estemps = array_merge($empresas, $estabs);
				}

				$trib = DB::table('tributos')
					->join('empresa_tributo', 'tributos.id', '=', 'empresa_tributo.tributo_id')
					->join('empresas', 'empresas.id', '=', 'empresa_tributo.empresa_id')
					->select('empresa_tributo.adiantamento_entrega')
					->where('tributos.id',$regra->tributo->id)
					->where('empresas.cnpj',$empresa->cnpj)
					->get();

				//VERIFICA ADIANTAMENTO DE ENTREGA
				$offset = $trib[0]->adiantamento_entrega;

				//VERIFICA REGRA PARA GESTAO DAS ATIVIDADES QUE CAEM NO FIM DA SEMANA
				$adiant_fds = $regra->afds;

				$val = array();

				// REGRAS ESPECIAIS: RE01,RE02,RE03...
				if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

					foreach($ativ_estemps as $ae) {
						$param = array('cnpj' => $ae->cnpj, 'IE' => $ae->insc_estadual);
						$retval_array = $this->calculaProximaDataRegrasEspeciais($regra->regra_entrega, $param, $periodo_apuracao, $offset, $adiant_fds);


						$data_limite = $retval_array[0]['data']->toDateTimeString();
						$alerta = intval($regra->tributo->alerta);
						$inicio_aviso = $retval_array[0]['data']->subDays($alerta)->toDateTimeString();
						$desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';
						$val = array(
							'descricao' => $desc_prefix . $retval_array[0]['desc'],
							'recibo' => $regra->tributo->recibo,
							'status' => 1,
							'periodo_apuracao' => $periodo_apuracao,
							'inicio_aviso' => $inicio_aviso,
							'limite' => $data_limite,
							'tipo_geracao' => 'A',
							'regra_id' => $regra->id
						);

						//FILTRO TRIBUTOS SUSPENSOS (ex. DIPAM)

						$val['estemp_type'] = substr($ae->cnpj, -6, 4) === '0001' ? 'emp' : 'estab';
						$val['estemp_id'] = $ae->id;
						if ($val['estemp_type'] == 'estab') {
							$val['emp_id'] = $ae->empresa_id;
						} else {
						   $id_estab = $this->findEstabelecimentoCNPJ($ae->cnpj);
							$val['emp_id'] = $this->findEmpresaEstabelecimentoID($id_estab);
							$val['estemp_id'] = $id_estab;
							$val['estemp_type'] = 'estab';
						}

						//Verifica blacklist dos estabelecimentos para esta regra
						if (!in_array($ae->id,$blacklist)) {
							if (!$this->checkDuplicidade($val)) {
								continue;
							}

							if ($this->checkGeneration($regra->created_at, $regra->freq_entrega)) {
								Atividade::create($val);
								$count++;
							}
						}
					}
				} else {
					// REGRAS PADRÃO
					$ref = $regra->ref;
					if ($municipio = Municipio::find($regra->ref)) {
						$ref = $municipio->nome . ' (' . $municipio->uf . ')';
					}
					$nome_especifico = $regra->nome_especifico;
					if (!$nome_especifico) {
						$nome_especifico = $regra->tributo->nome;
					}
					$desc = $nome_especifico . ' ' . $ref;
					$desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';

					$data = $this->calculaProximaData($regra->regra_entrega,$periodo_apuracao,$offset,$adiant_fds);
					$data_limite = $data->toDateTimeString();
					$alerta = intval($regra->tributo->alerta);
					$inicio_aviso = $data->subDays($alerta)->toDateTimeString();

					$val = array(
						'descricao' => $desc_prefix . $desc,
						'recibo' => $regra->tributo->recibo,
						'status' => 1,
						'periodo_apuracao' => $periodo_apuracao,
						'inicio_aviso' => $inicio_aviso,
						'limite' => $data_limite,
						'tipo_geracao' => 'A',
						'regra_id' => $regra->id
					);

					//FILTRO TRIBUTOS SUSPENSOS (ex. DIPAM)
					if (sizeof($ativ_estemps) > 0) {
						foreach ($ativ_estemps as $el) {
							$val['estemp_type'] = substr($el->cnpj, -6, 4) === '0001' ? 'emp' : 'estab';
							$val['estemp_id'] = $el->id;

							if ($val['estemp_type'] == 'estab') {
								$val['emp_id'] = $el->empresa_id;
							} else {
								$id_estab = $this->findEstabelecimentoCNPJ($el->cnpj);
								$val['emp_id'] = $this->findEmpresaEstabelecimentoID($id_estab);
								$val['estemp_id'] = $id_estab;
								$val['estemp_type'] = 'estab';
							}

							//Verifica blacklist dos estabelecimentos para esta regra
							if (!in_array($el->id, $blacklist)) {
								if (!$this->checkDuplicidade($val)) {
									continue;
								}
								if ($this->checkGeneration($regra->created_at, $regra->freq_entrega)) {
									if ($regra->tributo_id == 28 && $tributo !== null) {
										// se o tributo for 28 (ANTECIPADO)
										for ($i = 0; $i < $regra->qtd_atividade; $i++) {
											Atividade::create($val);
											$count++;
										}
									} else {
										Atividade::create($val);
										$count++;
									}
								}
							}
						}
					}
				}
			}

			if(isset($tributo)){
				if($tributo == 0){
					DB::table('crons')->insert(
						['periodo_apuracao' => $periodo_apuracao,'qtd'=>$count,'tipo_periodo'=>'M','emp_id'=>$empresa->id]
					);
				}
			}else{
				DB::table('crons')->insert(
					['periodo_apuracao' => $periodo_apuracao,'qtd'=>$count,'tipo_periodo'=>'M','emp_id'=>$empresa->id]
				);
			}


		}

		return $generate;

	}

	public function generateYearlyActivities($periodo_apuracao,$cnpj_empresa) {
		// Activate auto activity generation
		$generate = true;
		//
		$empresa = Empresa::where('cnpj',$cnpj_empresa)->firstOrFail();

		if (Cron::where('periodo_apuracao', $periodo_apuracao)->where('emp_id', $empresa->id)->count() >0) {
			$generate = false;
		}
		//TODAS AS REGRAS ATIVAS PARA A EMPRESA SOLICITADA
		$empresa_tributos = $empresa->tributos()->get();
		$array_tributos_ativos = array();
		foreach($empresa_tributos as $at) {
			$array_tributos_ativos[] = $at->id;
		}
		//
		$regras = Regra::where('freq_entrega','A')->where('ativo',1)->whereIN('tributo_id',$array_tributos_ativos)->get();

		if ($generate) {
			$count = 0;
			foreach ($regras as $regra) {

				$trib = DB::table('tributos')
					->join('empresa_tributo', 'tributos.id', '=', 'empresa_tributo.tributo_id')
					->join('empresas', 'empresas.id', '=', 'empresa_tributo.empresa_id')
					->select('empresa_tributo.adiantamento_entrega')
					->where('tributos.id',$regra->tributo->id)
					->where('empresas.cnpj',$empresa->cnpj)
					->get();

				//VERIFICA ADIANTAMENTO DE ENTREGA
				$offset = $trib[0]->adiantamento_entrega;

				//VERIFICA REGRA PARA GESTAO DAS ATIVIDADES QUE CAEM NO FIM DA SEMANA
				$adiant_fds = $regra->afds;
				$val = array();

				// Não tem regras especiais para a geração anual

				// Regra standard

				$ref = $regra->ref;
				if ($municipio = Municipio::find($regra->ref)) {
					$ref = $municipio->nome . ' (' . $municipio->uf . ')';
				}
				$desc = $regra->tributo->nome . ' ' . $ref;
				$desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';

				$data = $this->calculaProximaData($regra->regra_entrega,$periodo_apuracao,$offset,$adiant_fds);
				$data_limite = $data->toDateTimeString();
				$alerta = intval($regra->tributo->alerta);
				$inicio_aviso = $data->subDays($alerta)->toDateTimeString();

				$val = array('descricao' => $desc_prefix . $desc,
					'recibo' => $regra->tributo->recibo,
					'status' => 1,
					'periodo_apuracao' => $periodo_apuracao,
					'inicio_aviso' => $inicio_aviso,
					'limite' => $data_limite,
					'tipo_geracao' => 'A',
					'regra_id' => $regra->id
				);

				//print_r($val);
				$ativ_estemps = array();

				if ($regra->tributo->tipo == 'F') { //Federal

					$empresas = DB::table('empresas')
						->select('id', 'cnpj')
						->where('cnpj',$empresa->cnpj)
						->get();

					$ativ_estemps = $empresas;


				} else if ($regra->tributo->tipo == 'E') { //Estadual

					$ref = $regra->ref;

					$empresas = DB::table('empresas')
						->select('empresas.cnpj', 'empresas.id', 'municipios.uf', 'municipios.nome')
						->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
						->where('municipios.uf', $ref)
						->where('cnpj',$empresa->cnpj)
						->get();

					$estabs = DB::table('estabelecimentos')
						->select('estabelecimentos.cnpj', 'estabelecimentos.id', 'municipios.uf', 'municipios.nome')
						->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
						->where('municipios.uf', $ref)
						->where('estabelecimentos.ativo', 1)
						->where('empresa_id',$empresa->id)
						->get();

					$ativ_estemps = array_merge($empresas, $estabs);

				} else { //Municipal
					$ref = $regra->ref;
					$empresas = DB::table('empresas')
						->select('empresas.cnpj', 'empresas.id')
						->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
						->where('municipios.codigo', $ref)
						->where('cnpj',$empresa->cnpj)
						->get();

					$estabs = DB::table('estabelecimentos AS est')
						->select('est.cnpj', 'est.id')
						->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
						->where('mun.codigo', $ref)
						->where('est.ativo', 1)
						->where('empresa_id',$empresa->id)
						->get();

					$ativ_estemps = array_merge($empresas, $estabs);
				}
				//FILTRO ESTAB ATIVOS
				if (sizeof($ativ_estemps) > 0) {
					foreach ($ativ_estemps as $el) {

						$val['estemp_type'] = substr($el->cnpj, -6, 4) === '0001' ? 'emp' : 'estab';
						$val['estemp_id'] = $el->id;
						if ($val['estemp_type'] == 'estab') {
							$val['emp_id'] = $el->empresa_id;
						} else {
							$id_estab = $this->findEstabelecimentoCNPJ($el->cnpj);
							$val['emp_id'] = $this->findEmpresaEstabelecimentoID($id_estab);
							$val['estemp_id'] = $id_estab;
							$val['estemp_type'] = 'estab';
						}

						if (!$this->checkDuplicidade($val)) {
							continue;
						}

						if ($this->checkGeneration($regra->created_at, $regra->freq_entrega)) {
							$nova_atividade = Atividade::create($val);
							$count++;
						}

						//Assignment usuario
						//foreach($regra->tributo->users as $user) {
						//$nova_atividade->users()->save($user);
						//}
					}
				}

			}

			DB::table('crons')->insert(
				['periodo_apuracao' => $periodo_apuracao,'qtd'=>$count,'tipo_periodo'=>'A','emp_id'=>$empresa->id]
			);
		}

		return $generate;
	}

	public function generateMonthlyCronActivities($periodo_apuracao,$cnpj_empresa) {

		// Activate auto activity generation
		$generate = true;
		//
		$empresa = Empresa::where('cnpj',$cnpj_empresa)->firstOrFail();

		if (CronogramaStatus::where('periodo_apuracao', $periodo_apuracao)->where('emp_id', $empresa->id)->count() >0) {
			$generate = false;
			return $generate;
		}

		//TODAS AS REGRAS ATIVAS PARA A EMPRESA SOLICITADA
		$empresa_tributos = $empresa->tributos()->get();
		$array_tributos_ativos = array();
		foreach($empresa_tributos as $at) {
			$array_tributos_ativos[] = $at->id;
		}

		$regras = Regra::where('freq_entrega','M')->where('ativo',1)->whereIN('tributo_id',$array_tributos_ativos)->get();

		if ($generate) {
			$insert = DB::table('cronogramastatus')->insert(
				['status' => 0, 'periodo_apuracao' => $periodo_apuracao,'qtd'=>0,'qtd_priority'=>null,'qtd_mensal' => 0,'tipo_periodo'=>'M','emp_id'=>$empresa->id, 'created_at' => Carbon::now()->toDateTimeString() ]
			);

			$id_cronogramastatus = DB::getPdo()->lastInsertId();

			$count = 0;
			$registros = 0;
			foreach ($regras as $regra) {
				//VERIFICA CNPJ QUE FORAM BANIDOS PARA ESTA REGRA
				$blacklist = array();
				foreach($regra->estabelecimentos as $el) {
					$blacklist[] = $el->id;
				}

				//VERIFICA CNPJ QUE UTILIZAM A REGRA
				$ativ_estemps[$regra->id] = array();
				if ($regra->tributo->tipo == 'F') { //Federal
					$empresas = DB::table('empresas')
						->select('id', 'cnpj')
						->where('cnpj',$empresa->cnpj)
						->get();

					$ativ_estemps[$regra->id] = $empresas;

				} else if ($regra->tributo->tipo == 'E') { //Estadual

					$ref = $regra->ref;

					$empresas = DB::table('empresas')
						->select('empresas.cnpj', 'empresas.id', 'empresas.id', 'municipios.uf', 'municipios.nome', 'empresas.insc_estadual')
						->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
						->where('municipios.uf', $ref)
						->where('cnpj',$empresa->cnpj)
						->get();

					$estabs = DB::table('estabelecimentos')
						->select('estabelecimentos.cnpj', 'estabelecimentos.id', 'estabelecimentos.empresa_id', 'municipios.uf', 'municipios.nome','estabelecimentos.insc_estadual')
						->join('municipios', 'municipios.codigo', '=', 'estabelecimentos.cod_municipio')
						->where('municipios.uf', $ref)
						->where('estabelecimentos.ativo', 1)
						->where('empresa_id',$empresa->id)
						->get();

					$ativ_estemps[$regra->id] = array_merge($empresas, $estabs);

				} else { //Municipal

					$ref = $regra->ref;
					if (strlen($ref)==2) {  // O tributo é municipal, porem a regra é estadual

						$empresas = DB::table('empresas')
							->select('empresas.cnpj', 'empresas.id', 'empresas.id')
							->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
							->where('municipios.uf', $ref)
							->where('cnpj',$empresa->cnpj)
							->get();

						$estabs = DB::table('estabelecimentos AS est')
							->select('est.cnpj', 'est.id', 'est.empresa_id')
							->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
							->where('mun.uf', $ref)
							->where('est.ativo', 1)
							->where('empresa_id',$empresa->id)
							->get();

					} else {    // O tributo é municipal, e a regra é municipal

						$empresas = DB::table('empresas')
							->select('empresas.cnpj', 'empresas.id', 'empresas.id')
							->join('municipios', 'municipios.codigo', '=', 'empresas.cod_municipio')
							->where('municipios.codigo', $ref)
							->where('cnpj',$empresa->cnpj)
							->get();

						$estabs = DB::table('estabelecimentos AS est')
							->select('est.cnpj', 'est.id', 'est.empresa_id')
							->join('municipios AS mun', 'mun.codigo', '=', 'est.cod_municipio')
							->where('mun.codigo', $ref)
							->where('est.ativo', 1)
							->where('empresa_id',$empresa->id)
							->get();
					}
					$ativ_estemps[$regra->id] = array_merge($empresas, $estabs);
				}

				$trib = DB::table('tributos')
					->join('empresa_tributo', 'tributos.id', '=', 'empresa_tributo.tributo_id')
					->join('empresas', 'empresas.id', '=', 'empresa_tributo.empresa_id')
					->select('empresa_tributo.adiantamento_entrega')
					->where('tributos.id',$regra->tributo->id)
					->where('empresas.cnpj',$empresa->cnpj)
					->get();

				//VERIFICA ADIANTAMENTO DE ENTREGA
				$offset = $trib[0]->adiantamento_entrega;

				//VERIFICA REGRA PARA GESTAO DAS ATIVIDADES QUE CAEM NO FIM DA SEMANA
				$adiant_fds = $regra->afds;

				$val = array();

				$registros += count($ativ_estemps[$regra->id]);

			}

			DB::table('cronogramastatus')
				->where('id', $id_cronogramastatus)
				->update(['qtd' => $registros]);

			foreach ($regras as $regra) {
				// REGRAS ESPECIAIS: RE01,RE02,RE03...
				if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {
					foreach($ativ_estemps[$regra->id] as $ae) {

						DB::table('cronogramastatus')
							->where('id', $id_cronogramastatus)
							->update(['qtd_realizados'=>$count]);

						$param = array('cnpj' => $ae->cnpj, 'IE' => $ae->insc_estadual);
						$dataRegraEspecial = $this->calculaProximaDataRegrasEspeciais(
							$regra->regra_entrega,
							$param,
							$periodo_apuracao,
							$offset,
							$adiant_fds);

						$data_limite = $dataRegraEspecial[0]['data']->toDateTimeString();
						$alerta = intval($regra->tributo->alerta);
						$inicio_aviso = $dataRegraEspecial[0]['data']->subDays($alerta)->toDateTimeString();
						$desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';
						$session = Session::all();
						$ult = array_pop( $session );
						$id_user = array_pop( $session );
						if (!is_numeric($id_user)) {
							$id_user = $ult;
						}

						$val = array(
							'descricao' => $desc_prefix . $dataRegraEspecial[0]['desc'],
							'recibo' => $regra->tributo->recibo,
							'status' => 1,
							'periodo_apuracao' => $periodo_apuracao,
							'inicio_aviso' => $inicio_aviso,
							'limite' => $data_limite,
							'tipo_geracao' => 'A',
							'regra_id' => $regra->id,
							'Data_cronograma'   => date('Y-m-d H:i:s'),
							'data_atividade'    => '0000-00-00 00:00:00',
							'Resp_cronograma' => $id_user
						);

						//FILTRO TRIBUTOS SUSPENSOS (ex. DIPAM)

						$val['estemp_type'] = substr($ae->cnpj, -6, 4) === '0001' ? 'emp' : 'estab';
						$val['estemp_id'] = $ae->id;
						if ($val['estemp_type'] == 'estab') {
							$val['emp_id'] = $ae->empresa_id;
						} else {
							$id_estab = $this->findEstabelecimentoCNPJ($ae->cnpj);
							$val['emp_id'] = $this->findEmpresaEstabelecimentoID($id_estab);
							$val['estemp_id'] = $id_estab;
							$val['estemp_type'] = 'estab';
						}

						$analista = $this->loadAnalista($val);

						if (!empty($analista)) {
							$val['Id_usuario_analista'] = $analista;
						}

						if ($val['estemp_id'] > 0) {

							$estabelecimento_tempo = Estabelecimento::find($val['estemp_id']);

							if (!empty($estabelecimento_tempo)) {

								$uf_cron = Municipio::find($estabelecimento_tempo->cod_municipio);
								$val['tempo'] = $this->getTempo($regra->tributo->id, $uf_cron->uf);

								// INICIO CARD 422 Erro 1
/*
								if (isset($val['Id_usuario_analista']) && !$this->existeAtividadeAnalista($val['Id_usuario_analista'], $uf_cron->uf, $regra->tributo->id)) {
									$count++;
									continue;
								}
*/
								// FIM CARD 422 Erro 1
							}
						}

						if (!$this->checkDuplicidadeCronograma($val)) {
							$count++;
							continue;
						}

						//Verifica blacklist dos estabelecimentos para esta regra
						if (!in_array($ae->id,$blacklist)) {
							$nova_atividade = CronogramaAtividade::create($val);
							if (!empty($val)) {
								$val['id'] = $nova_atividade->id;
								$this->array[$val['estemp_id']][$regra->tributo->id][] = $val;
								$this->qtd_estabs[$regra->tributo->id][$uf_cron->uf][$val['estemp_id']][] = $val;
								$this->prioridade[$regra->tributo->id][] = $val;
								$count++;
							}
						}

					}
				}
				// REGRAS PADRÃO
				else {

					$ref = $regra->ref;
					if ($municipio = Municipio::find($regra->ref)) {
						$ref = $municipio->nome . ' (' . $municipio->uf . ')';
					}
					$nome_especifico = $regra->nome_especifico;
					if (!$nome_especifico) {
						$nome_especifico = $regra->tributo->nome;
					}
					$desc = $nome_especifico . ' ' . $ref;
					$desc_prefix = $regra->tributo->recibo == 1 ? 'Entrega ' : '';

					$data = $this->calculaProximaData($regra->regra_entrega,$periodo_apuracao,$offset,$adiant_fds);
					$data_limite = $data->toDateTimeString();
					$alerta = intval($regra->tributo->alerta);
					$inicio_aviso = $data->subDays($alerta)->toDateTimeString();

					$session = Session::all();
					$ult = array_pop( $session );
					$id_user = array_pop( $session );
					if (!is_numeric($id_user)) {
						$id_user = $ult;
					}

					$val = array(
						'descricao' => $desc_prefix . $desc,
						'recibo' => $regra->tributo->recibo,
						'status' => 1,
						'periodo_apuracao' => $periodo_apuracao,
						'inicio_aviso' => $inicio_aviso,
						'limite' => $data_limite,
						'tipo_geracao' => 'A',
						'data_atividade'   => '0000-00-00 00:00:00',
						'regra_id' => $regra->id
					);

					//FILTRO TRIBUTOS SUSPENSOS (ex. DIPAM)
					if (sizeof($ativ_estemps[$regra->id]) > 0) {
						foreach ($ativ_estemps[$regra->id] as $el) {
							DB::table('cronogramastatus')
								->where('id', $id_cronogramastatus)
								->update(['qtd_realizados'=>$count]);

							if (@!$el->empresa_id) {
								$empresa_id = $el->id;
							} else {
								$empresa_id = $el->empresa_id;
							}
							$val['estemp_type'] = substr($el->cnpj, -6, 4) === '0001' ? 'emp' : 'estab';
							$val['estemp_id'] = $el->id;
							if ($val['estemp_type'] == 'estab') {
								$val['emp_id'] = $el->empresa_id;
							} else {
								$id_estab = $this->findEstabelecimentoCNPJ($el->cnpj);
								$val['emp_id'] = $this->findEmpresaEstabelecimentoID($id_estab);
								$val['estemp_id'] = $id_estab;
								$val['estemp_type'] = 'estab';
							}
							$analista = $this->loadAnalista($val);

							if (!empty($analista)) {

								$val['Id_usuario_analista'] = $analista;

								// INICIO CARD 422 Erro 1
/*
								if (!$this->existeAtividadeAnalista($val['Id_usuario_analista'], $ref, $regra->tributo->id)) {
									$count++;
									continue;
								}
*/
								// FIM CARD 422 Erro 1

							}

							$val['Resp_cronograma'] = $id_user;
							$val['Data_cronograma'] = date('Y-m-d H:i:s');

							if ($val['estemp_id'] > 0) {
								$estabelecimento_tempo = Estabelecimento::find($val['estemp_id']);
								if (!empty($estabelecimento_tempo)) {
									$uf_cron = Municipio::find($estabelecimento_tempo->cod_municipio);
									$val['tempo'] = $this->getTempo($regra->tributo->id, $uf_cron->uf);
								}
							}

							if (!$this->checkDuplicidadeCronograma($val)) {
								$count++;
								continue;
							}

							//Verifica blacklist dos estabelecimentos para esta regra
							if (!in_array($el->id,$blacklist)) {
								$nova_atividade = CronogramaAtividade::create($val);
								if (!empty($val)) {
									$val['id'] = $nova_atividade->id;
									$this->array[$val['estemp_id']][$regra->tributo->id][] = $val;
									$this->qtd_estabs[$regra->tributo->id][$uf_cron->uf][$val['estemp_id']][] = $val;
									$this->prioridade[$regra->tributo->id][] = $val;
									$count++;
								}
							}
						}
					}
				}
			}

			if (!empty($this->array)) {
				$this->generateMensal($this->array, $id_cronogramastatus);
				// TODO: vamos criar nova função para calculo da data_atividade
				// $this->setPriority($this->prioridade, $periodo_apuracao, $empresa->id, $id_cronogramastatus);
				$this->setPriorityActivityDate($this->prioridade, $periodo_apuracao, $empresa->id, $id_cronogramastatus);
			}

			DB::table('cronogramastatus')
				->where('id', $id_cronogramastatus)
				->update(['qtd_realizados'=>$count, 'status' => 1]);

		}

		return $generate;
	}
	// START recalculation of the activity date
	/**
	 * The function setPriorityActivityDate() define the priority of date of activity
	 *
	 * @param array $array - array of activities; verify when this have or not Id_usuario_analista to calculate
	 * @param string $periodo_apuracao - calculation period composed by month year (MMYYYY)
	 * @param integer $emp_id - company identification number
	 * @param string $cronogramastatus_id - identification number of cronograma in table cronogramastatus
	 *
	 * $array content (with Id_usuario_analista):
	 *
	 *  'descricao' => string 'Entrega DIPAM SP' (length=16)
		'recibo' => int 1
		'status' => int 1
		'periodo_apuracao' => string '082020' (length=6)
		'inicio_aviso' => string '2020-09-07 23:59:59' (length=19)
		'limite' => string '2020-09-22 23:59:59' (length=19)
		'tipo_geracao' => string 'A' (length=1)
		'data_atividade' => string '0000-00-00 00:00:00' (length=19)
		'regra_id' => int 459
		'estemp_type' => string 'estab' (length=5)
		'estemp_id' => int 1334
		'emp_id' => int 4
		'Id_usuario_analista' => int 308
		'Resp_cronograma' => string '1' (length=1)
		'Data_cronograma' => string '2020-10-28 18:17:08' (length=19)
		'tempo' => int 30
		'id' => int 239095
	 *
	 * $array content (without Id_usuario_analista):
	 *
	 *  'descricao' => string 'Entrega   SP' (length=12)
		'recibo' => int 1
		'status' => int 1
		'periodo_apuracao' => string '082020' (length=6)
		'inicio_aviso' => string '2020-09-14 23:59:59' (length=19)
		'limite' => string '2020-09-29 23:59:59' (length=19)
		'tipo_geracao' => string 'A' (length=1)
		'data_atividade' => string '0000-00-00 00:00:00' (length=19)
		'regra_id' => int 473
		'estemp_type' => string 'estab' (length=5)
		'estemp_id' => int 914
		'emp_id' => int 4
		'Resp_cronograma' => string '1' (length=1)
		'Data_cronograma' => string '2020-10-28 18:17:08' (length=19)
		'tempo' => int 60
		'id' => int 239096
	 *
	 * @return boolean
	 */
	private function setPriorityActivityDate($array, $periodo_apuracao, $empresa_id, $cronogramastatus_id)
	{
		// Test Area
		// var_dump(
		// 	'setPriorityActivityDate ::: $array', $array,
		// 	'setPriorityActivityDate ::: $periodo_apuracao', $periodo_apuracao,
		// 	'setPriorityActivityDate ::: $empresa_id', $empresa_id,
		// 	'setPriorityActivityDate ::: $id_cronogramastatus', $cronogramastatus_id
		// );
		// setup vars and local arrays
		$totalActivityTime = 0;
		$qtd_priority = 0;
		$workDayStartAt =  "09:00:00";
		$workDayEndtAt = "18:00:00";
		$workDayLimitAt = "23:59:59";
		$workDayDefaultTotalTime = 480; // in minutes
		$firstDayOfAnalystAvailability = 0;
		$lastDayOfAnalystAvailability = 0;
		$workDayAnalystTotalTime = 0;
		$firstAvailableActivityDate = "";
		$lastAvailableActivityDate = "";
		$nextBusinessDay = "";
		$countingYear = substr($periodo_apuracao, -4);
		$arrActivities = array();
		$arrDataSorted = array();
		$arrAvailableAnalysts = array();
		$arrAnalystAvailability = array();
		$arrMultiSearchResult = array();
		$socNationalHolidays = array();
		$arrHolidayDates = array();

		// get data_previsao_carga, usuario_analista_id (Id_usuario_analista) and tempo_atividade (tempo)
		// and add this fields into this array $arrActivities
		$arrActivities = $this->getLoadForecastDate($array, $periodo_apuracao, $empresa_id);

		// now, We can sort this array by five key columns:
		// data_limite ($arrActivities['limite']),
		// prioridade_apuracao ($arrActivities['prioridade_apuracao']),
		// regra_id ($arrActivities['regra_id']),
		// usuario_analista_id ($arrActivities['Id_usuario_analista'])
		// and cronogramaatividades_id ($arrActivities['id'])
		$arrSortCriteria = array(
			'limite' => array(SORT_ASC, SORT_STRING ),
			'prioridade_apuracao' => array(SORT_ASC, SORT_NUMERIC),
			'regra_id' => array(SORT_ASC, SORT_NUMERIC),
			'Id_usuario_analista' => array(SORT_ASC, SORT_NUMERIC),
			'id' => array(SORT_ASC, SORT_NUMERIC)
		);
		$arrDataSorted = $this->arrayMultiSorter($arrActivities, $arrSortCriteria, true);
		// Total time of all activities
		$totalActivityTime = $arrDataSorted[0]['tempo_total'];
		// get the number of days necessary to finish activity. using ceil() to round up the number
		$totalDays = ceil($totalActivityTime/$workDayDefaultTotalTime);
		// get all available analysts for this activities
		$arrAvailableAnalysts = DB::table('analistadisponibilidade')->where(['periodo_apuracao' => $periodo_apuracao, 'empresa_id' => $empresa_id])->get();

		if (!empty($arrAvailableAnalysts) && count($arrAvailableAnalysts) > 0) {
			foreach ($arrAvailableAnalysts as $key => $analyst) {
				// load Analyst availability
				$arrAnalystAvailability[] = (array) $analyst;
			}
		}

		$temAnalistaDisp = (!empty($arrAnalystAvailability) && count($arrAnalystAvailability) > 0);

		if (!$temAnalistaDisp) {

			// INICIO CARD 422 ERRO 2 - a data_atividade nao estava sendo calculada se nao encontrasse registro na tabela analistadisponibilidade

			$users         = DB::select("SELECT DISTINCT Id_usuario_analista AS id FROM atividadeanalista WHERE Emp_id = '{$empresa_id}'");
			$datasAnalista = $this->doCriarDisponibilidadeAnalista($periodo_apuracao);

			if (!empty($users)) {

				foreach ($users as $User) {
					$insert = DB::table('analistadisponibilidade')->insert([
						'id_usuarioanalista' => $User->id,
						'empresa_id'         => $empresa_id,
						'qtd_min_disp_dia'   => $workDayDefaultTotalTime,
						'data_ini_disp'      => $datasAnalista[0],
						'data_fim_disp'      => $datasAnalista[1],
						'periodo_apuracao'   => $periodo_apuracao
					]);

					$availableAnalystCreatedNow = DB::table('analistadisponibilidade')->where([
						'periodo_apuracao'   => $periodo_apuracao,
						'empresa_id'         => $empresa_id,
						'id_usuarioanalista' => $User->id
					])->get();

					array_push($arrAnalystAvailability, ((array) $availableAnalystCreatedNow[0]));
				}

				$temAnalistaDisp = true;
			}
			else {
				die('Nenhum analista possui atividade (Não deveria cair aqui)');
			}

			// FIM CARD 422 ERRO 2

		}

		// get Brazil national holidays from DB and create a new array with one specific collumn
		$socNationalHolidays = $this->getNacionalHolidays($countingYear, 'N');
		$arrHolidayDates = Helper::arrayByCollumnFromStdClass($socNationalHolidays, 'holiday_date');
		// First update in table cronogramastatus from start of process activities
		// After, one update is required for each loop
		DB::table('cronogramastatus')->where(['id' => $cronogramastatus_id])->update(['qtd_priority' => $qtd_priority]);
		// START loop over Activities
		foreach ($arrDataSorted as $key => $activity) {

			// Set fisrt and last day of activity
			$firstAvailableActivityDate = Helper::getNextBusinessDay($activity['data_previsao_carga'], $arrHolidayDates);
			$lastAvailableActivityDate = explode(' ', $activity['limite'])[0];

			// creating a temporary array to store the data of the analyst's activities
			if ($temAnalistaDisp) {
				$arrMultiSearchResult = $this->arrayMultiSearch($arrAnalystAvailability, 'id_usuarioanalista', $activity['Id_usuario_analista']);
				// Has Analyst with Availability and personalized time
				if (!empty($arrMultiSearchResult) && count($arrMultiSearchResult) > 0) {
					// Verifiy if the Analyst availability is between the first and the last day of activity
					// Setting Analyst data
					$firstDayOfAnalystAvailability = $arrMultiSearchResult[0]['data_ini_disp'];
					$lastDayOfAnalystAvailability = $arrMultiSearchResult[0]['data_fim_disp'];
					$workDayAnalystTotalTime = $arrMultiSearchResult[0]['qtd_min_disp_dia'];
					// Verifying if exist Analyst Activity
					$verifyLastActivity = $this->getLastAnalystActivity($empresa_id, $periodo_apuracao, $arrMultiSearchResult[0]['id_usuarioanalista']);

					// If exist activity, verify if availability is between start and last day of activity
					// and calculate the next business day, if necessary, and setup the activity date to save in DB
					if (!empty($verifyLastActivity) && count($verifyLastActivity) > 0) {
						// this is the last activity for this analyst, add the next activity
						if ($workDayAnalystTotalTime > 0 && $activity['tempo'] < $workDayAnalystTotalTime) {
							// subtract activity time
							$workDayAnalystTotalTime -= $activity['tempo'];
							// set new activity date
							$time = ($verifyLastActivity[0]->data_atividade == '0000-00-00 00:00:00') ? '00:00:00' : '00:'.$activity['tempo'].':00';
							// set dateTarget
							$dateTarget = ($verifyLastActivity[0]->data_atividade == '0000-00-00 00:00:00') ? $firstDayOfAnalystAvailability." ".$workDayStartAt : $verifyLastActivity[0]->data_atividade;
							$activityDate = Helper::addTimeToUTCDate($time, $dateTarget);

							// var_dump('workDayAnalystTotalTime 1', $workDayAnalystTotalTime, 'tempo 1', $activity['tempo'], 'dateTarget 1', $dateTarget, 'activityDate 1', $activityDate, 'cronograma_id 1', $activity['id']);
							// Verifiy if $activityDate is between $workDayStartAt and $workDayEndAt. If not,
							// getNextBusinessDay() again

							// clean variables
							$dateStart = "";
							$dateEnd = "";
							$timestampStart = "";
							$timestampEnd = "";
							$timestamp = "";
							// setup variables to compare
							$dateStr = explode(" ", $activityDate)[0];
							$dateStart = $dateStr." ".$workDayStartAt;
							$dateEnd = $dateStr." ".$workDayEndtAt;
							$timestampStart = strtotime($dateStart);
							$timestampEnd = strtotime($dateEnd);
							$timestampEnd = $timestampEnd-$activity['tempo'];
							$timestamp = strtotime($dateTarget);
							// comparing dates
							if ($timestampStart <= $timestamp && $timestamp < $timestampEnd) {
								// save the new activity date
								DB::table('cronogramaatividades')
									->where(['id' => $activity['id']])
									->update(['data_atividade' => $activityDate, 'data_previsao_carga' => $activity['data_previsao_carga']]);
							} else {
								// we need get next business day 'cause last activity date start after 6 PM
								$dateTmp = Helper::getNextBusinessDay($dateTarget, $arrHolidayDates);
								$dateTmp = $dateTmp." ".$workDayStartAt;
								$activityDate = Helper::addTimeToUTCDate($time, $dateTmp);
								// save the new activity date
								DB::table('cronogramaatividades')
									->where(['id' => $activity['id']])
									->update(['data_atividade' => $activityDate, 'data_previsao_carga' => $activity['data_previsao_carga']]);
							}
						} else {
							// TODO: devemos verificar aqui
							// Tempo de atividade disponivel do analista está abaixo do tempo de atividade
							// associado à ele. verificar se havera proximo dia de atividade, processa a data_atividade e salva DB
							// Reset $workDayDefaultTotalTime to your default value (new day)
							$workDayAnalystTotalTime = $arrMultiSearchResult[0]['qtd_min_disp_dia'];
							// subtract activity time
							$workDayAnalystTotalTime -= $activity['tempo'];
							// verify if availability dates of analyst is between dates of activity
							$nextBusinessDay = Helper::getNextBusinessDay($verifyLastActivity[0]->data_atividade, $arrHolidayDates);

							if (!empty($nextBusinessDay) && $firstDayOfAnalystAvailability <= $nextBusinessDay && $nextBusinessDay <= $lastDayOfAnalystAvailability) {
								$time = '00:00:00';
								$dateTarget = $nextBusinessDay." ".$workDayStartAt;
								$activityDate = $dateTarget;

								// var_dump('workDayAnalystTotalTime 1.5', $workDayAnalystTotalTime, 'tempo 1.5', $activity['tempo'], 'dateTarget 1.5', $dateTarget, 'activityDate 1.5', $activityDate, 'cronograma_id 1.5', $activity['id']);
								// save the new activity date
								DB::table('cronogramaatividades')
									->where(['id' => $activity['id']])
									->update(['data_atividade' => $activityDate, 'data_previsao_carga' => $activity['data_previsao_carga']]);
							} else {
								// tempo de atividade disponivel do analista não é compatível com o intervalo de
								// datas de atividade informadas para esta atividade e período de apuracao
								$time = '00:00:00';
								$dateTarget = $nextBusinessDay." ".$workDayStartAt;
								$activityDate = $dateTarget;
								DB::table('cronogramaatividades')
									->where(['id' => $activity['id']])
									->update(['data_previsao_carga' => $activity['data_previsao_carga'], 'data_atividade' => $activityDate, 'tempo_excedido_msg' => "O tempo de atividade disponivel do analista ({$firstDayOfAnalystAvailability} e {$lastDayOfAnalystAvailability}) não é compatível com o intervalo de datas de atividade ({$firstAvailableActivityDate} e {$lastAvailableActivityDate}) informadas para esta atividade e período de apuracao"]);
							}
						}
					} else {
						//  this is the first activity for this analyst
						// subtract activity time
						$workDayAnalystTotalTime -= $activity['tempo'];

						// verify if availability dates of analyst is between dates of activity
						$arrDayInterval = Helper::getDayInterval($firstAvailableActivityDate, $lastAvailableActivityDate, $firstDayOfAnalystAvailability, $lastDayOfAnalystAvailability);
						if ($arrDayInterval['status'] == 'OK') {
							$time = '00:00:00';
							$dateTarget = $arrDayInterval['startDate']." ".$workDayStartAt;
							$activityDate = $dateTarget;

							// var_dump('workDayAnalystTotalTime 2', $workDayAnalystTotalTime, 'tempo 2', $activity['tempo'], 'dateTarget 2', $dateTarget, 'activityDate 2', $activityDate, 'cronograma_id 2', $activity['id'], 'arrDayInterval', $arrDayInterval);
							// save the new activity date
							DB::table('cronogramaatividades')
								->where(['id' => $activity['id']])
								->update(['data_atividade' => $activityDate, 'data_previsao_carga' => $activity['data_previsao_carga']]);
						} else {
							// tempo de atividade disponivel do analista não é compatível com o intervalo de
							// datas de atividade informadas para esta atividade e período de apuracao
							$time = '00:00:00';
							$dateTarget = $firstDayOfAnalystAvailability." ".$workDayStartAt;
							$activityDate = $dateTarget;

							// var_dump('workDayAnalystTotalTime 2.1', $workDayAnalystTotalTime, 'tempo 2.1', $activity['tempo'], 'dateTarget 2.1', $dateTarget, 'activityDate 2.1', $activityDate, 'cronograma_id 2.1', $activity['id'], 'arrDayInterval', $arrDayInterval);

							DB::table('cronogramaatividades')
								->where(['id' => $activity['id']])
								->update(['data_previsao_carga' => $activity['data_previsao_carga'], 'data_atividade' => $activityDate, 'tempo_excedido_msg' => $arrDayInterval['msg']]);
						}
					}
				} else { // Has Analyst with standard time
					if (isset($activity['Id_usuario_analista']) && !empty($activity['Id_usuario_analista'])) {;
						// verifying if exist Analyst activity
						$verifyLastActivity = $this->getLastAnalystActivity($empresa_id, $periodo_apuracao, $activity['Id_usuario_analista']);

						// if exist activity, calculate the next business day, if necessary, and setup the activity
						// date to save in DB
						if (!empty($verifyLastActivity) && isset($verifyLastActivity)) {
							// this is the last activity for this analyst, add the next activity
							if ($workDayDefaultTotalTime > 0 && $activity['tempo'] < $workDayDefaultTotalTime) {
								// set new activity date
								$time = ($verifyLastActivity[0]->data_atividade == '0000-00-00 00:00:00') ? '00:00:00' : '00:'.$activity['tempo'].':00';
								// subtract activity time
								$workDayDefaultTotalTime -= $activity['tempo'];

								// set dateTarget
								$dateTarget = ($verifyLastActivity[0]->data_atividade == '0000-00-00 00:00:00') ? $firstAvailableActivityDate." ".$workDayStartAt : $verifyLastActivity[0]->data_atividade;
								$activityDate = Helper::addTimeToUTCDate($time, $dateTarget);

								// var_dump('workDayDefaultTotalTime 3', $workDayDefaultTotalTime, 'tempo 3', $activity['tempo'], 'dateTarget 3', $dateTarget, 'activityDate 3', $activityDate, 'cronograma_id 3', $activity['id']);
								// Verifiy if $activityDate is between $workDayStartAt and $workDayEndAt. If not,
								// getNextBusinessDay() again

								// clean variables
								$dateStart = "";
								$dateEnd = "";
								$timestampStart = "";
								$timestampEnd = "";
								$timestamp = "";
								// setup variables to compare
								$dateStr = explode(" ", $activityDate)[0];
								$dateStart = $dateStr." ".$workDayStartAt;
								$dateEnd = $dateStr." ".$workDayEndtAt;
								$timestampStart = strtotime($dateStart);
								$timestampEnd = strtotime($dateEnd);
								$timestampEnd = $timestampEnd-$activity['tempo'];
								$timestamp = strtotime($dateTarget);
								// comparing dates
								if ($timestampStart <= $timestamp && $timestamp < $timestampEnd) {
									// Verify again if $activityDate it´s smaller than $lastDayOfAnalystAvailability
									// if YES, process the activity and update the tempo_excedido and tempo_excedido_msg fields
									// clean variables
									$dateStart = "";
									$dateEnd = "";
									$timestampStart = "";
									$timestampEnd = "";
									$timestamp = "";
									// setup variables to compare
									$dateStart = $firstAvailableActivityDate." ".$workDayStartAt;
									$dateEnd = $lastAvailableActivityDate." ".$workDayLimitAt;
									$timestampStart = strtotime($dateStart);
									$timestampEnd = strtotime($dateEnd);
									$timestampEnd = $timestampEnd-$activity['tempo'];
									$timestamp = strtotime($activityDate);

									// comparing dates
									if ($timestampStart <= $timestamp && $timestamp < $timestampEnd) {
										// save the new activity date
										DB::table('cronogramaatividades')
											->where(['id' => $activity['id']])
											->update(['data_atividade' => $activityDate, 'data_previsao_carga' => $activity['data_previsao_carga']]);
									} else {
										// the activity date is out of range of date of activity
										// save the new activity date and update exceeded msg
										DB::table('cronogramaatividades')
											->where(['id' => $activity['id']])
											->update(
												[
													'data_atividade' => $activityDate,
													'data_previsao_carga' => $activity['data_previsao_carga'],
													'tempo_excedido' => $activity['tempo'],
													'tempo_excedido_msg' => "A atividade ({$activity['id']}) programada para o analista ({$activity['Id_usuario_analista']}) está em uma data ({$activityDate}) acima da data limite ({$activity['data_limite']})",
												]
											);
									}
								} else {
									// we need get next business day 'cause last activity date start after 6 PM
									$dateTmp = Helper::getNextBusinessDay($dateTarget, $arrHolidayDates);
									$dateTmp = $dateTmp." ".$workDayStartAt;
									$activityDate = Helper::addTimeToUTCDate($time, $dateTmp);

									// clean variables
									$dateStart = "";
									$dateEnd = "";
									$timestampStart = "";
									$timestampEnd = "";
									$timestamp = "";
									// setup variables to compare
									$dateStart = $firstAvailableActivityDate." ".$workDayStartAt;
									$dateEnd = $lastAvailableActivityDate." ".$workDayLimitAt;
									$timestampStart = strtotime($dateStart);
									$timestampEnd = strtotime($dateEnd);
									$timestampEnd = $timestampEnd-$activity['tempo'];
									$timestamp = strtotime($activityDate);

									// comparing dates
									if ($timestampStart <= $timestamp && $timestamp < $timestampEnd) {
										// save the new activity date
										DB::table('cronogramaatividades')
											->where(['id' => $activity['id']])
											->update(['data_atividade' => $activityDate, 'data_previsao_carga' => $activity['data_previsao_carga']]);
									} else {
										// the activity date is out of range of date of activity
										// save the new activity date and update exceeded msg
										DB::table('cronogramaatividades')
											->where(['id' => $activity['id']])
											->update(
												[
													'data_atividade' => $activityDate,
													'data_previsao_carga' => $activity['data_previsao_carga'],
													'tempo_excedido' => $activity['tempo'],
													'tempo_excedido_msg' => "A atividade ({$activity['id']}) programada para o analista ({$activity['Id_usuario_analista']}) está em uma data ({$activityDate}) acima da data limite ({$activity['data_limite']})",
												]
											);
									}
								}
							} else {
								// The last workday is finished. One new day must be found
								// o tempo de atividade para 1 dia acabou, logo, precisamos passar essa atividade
								// para um novo dia e este novo dia não pode ser maior do que o ultimo dia de
								// atividade previsto. Se passar disso, tempo_excedido e tempo_excedido_msg
								// devem ser contabilizados para mostrar que existe mais atividade do que
								// analistas/tempo disponivel para o periodo_apuracao informado (isso é um erro)

								// Reset $workDayDefaultTotalTime to your default value (new day)
								$workDayDefaultTotalTime = 480;
								// subtract activity time
								$workDayDefaultTotalTime -= $activity['tempo'];
								// verify if availability dates of analyst is between dates of activity
								$nextBusinessDay = Helper::getNextBusinessDay($verifyLastActivity[0]->data_atividade, $arrHolidayDates);

								if (!empty($nextBusinessDay) && $firstAvailableActivityDate <= $nextBusinessDay && $nextBusinessDay <= $lastAvailableActivityDate) {
									$time = '00:00:00';
									$dateTarget = $nextBusinessDay." ".$workDayStartAt;
									$activityDate = $dateTarget;

									// var_dump('workDayDefaultTotalTime 3.5', $workDayDefaultTotalTime, 'tempo 3.5', $activity['tempo'], 'dateTarget 3.5', $dateTarget, 'activityDate 3.5', $activityDate, 'cronograma_id 3.5', $activity['id']);
									// save the new activity date
									DB::table('cronogramaatividades')
										->where(['id' => $activity['id']])
										->update(['data_atividade' => $activityDate, 'data_previsao_carga' => $activity['data_previsao_carga']]);
								} else {
									// tempo de atividade disponivel do analista não é compatível com o intervalo de
									// datas de atividade informadas para esta atividade e período de apuracao
									// var_dump('workDayDefaultTotalTime 3.5.1', $workDayDefaultTotalTime, 'tempo 3.5.1', $activity['tempo'], 'Id_usuario_analista 3.5.1', $activity['Id_usuario_analista'], 'cronograma_id 3.5.1', $activity['id']);
									DB::table('cronogramaatividades')
										->where(['id' => $activity['id']])
										->update(['data_previsao_carga' => $activity['data_previsao_carga'], 'data_atividade' => '0000-00-00 00:00:00', 'tempo_excedido_msg' => "[0] A data de atividade ({$nextBusinessDay} a partir desta [" . $verifyLastActivity[0]->data_atividade . "]) disponível do analista ({$activity['Id_usuario_analista']}) não é compatível com o intervalo de datas da atividade ($firstAvailableActivityDate) e ($lastAvailableActivityDate) informados para esta atividade ({$activity['id']}) e periodo de apuração ({$periodo_apuracao})"]);
								}
							}
						} else {
							//  this is the first activity for this analyst
							// subtract activity time
							$workDayDefaultTotalTime -= $activity['tempo'];

							// verify if availability dates of analyst is between dates of activity
							$nextBusinessDay = Helper::getNextBusinessDay($activity['data_previsao_carga'], $arrHolidayDates);

							if (!empty($nextBusinessDay) && $firstAvailableActivityDate <= $nextBusinessDay && $nextBusinessDay <= $lastAvailableActivityDate) {
								$time = '00:00:00';
								$dateTarget = $nextBusinessDay." ".$workDayStartAt;
								$activityDate = $dateTarget;

								// var_dump('workDayDefaultTotalTime 4', $workDayDefaultTotalTime, 'tempo 4', $activity['tempo'], 'dateTarget 4', $dateTarget, 'activityDate 4', $activityDate, 'cronograma_id 4', $activity['id']);
								// save the new activity date
								DB::table('cronogramaatividades')
									->where(['id' => $activity['id']])
									->update(['data_atividade' => $activityDate, 'data_previsao_carga' => $activity['data_previsao_carga']]);
							} else {
								// tempo de atividade disponivel do analista não é compatível com o intervalo de
								// datas de atividade informadas para esta atividade e período de apuracao
								// var_dump('workDayDefaultTotalTime 4.5', $workDayDefaultTotalTime, 'tempo 4.5', $activity['tempo'], 'Id_usuario_analista 4.5', $activity['Id_usuario_analista'], 'cronograma_id 4.5', $activity['id']);
								DB::table('cronogramaatividades')
									->where(['id' => $activity['id']])
									->update(['data_previsao_carga' => $activity['data_previsao_carga'], 'data_atividade' => '0000-00-00 00:00:00', 'tempo_excedido_msg' => "[1] A data de atividade ({$nextBusinessDay}) disponível do analista ({$activity['Id_usuario_analista']}) não é compatível com o intervalo de datas da atividade ($firstAvailableActivityDate) e ($lastAvailableActivityDate) informados para esta atividade ({$activity['id']}) e periodo de apuração ({$periodo_apuracao})"]);
							}
						}
					} else {
						// We don't have any analyst to work with this activity. Update the registry without any modification
						// except by fields updatet_at and data_atividade = '0000-00-00 00:00:00'
						DB::table('cronogramaatividades')
							->where(['id' => $activity['id']])
							->update(['data_previsao_carga' => $activity['data_previsao_carga'], 'data_atividade' => '0000-00-00 00:00:00', 'tempo_excedido_msg' => "Não tem analista disponível para esta atividade"]);
					}
				}
			}

			// Finished step, update cronogramastatus table
			$qtd_priority++;
			DB::table('cronogramastatus')
				->where(['id' => $cronogramastatus_id])
				->update(['qtd_priority' => $qtd_priority]);
		}
		// END loop over Activities

		// var_dump('totalDays', $totalDays, 'workDayDefaultTotalTime', $workDayDefaultTotalTime, 'totalActivityTime', $totalActivityTime, 'arrAvailableAnalysts', $arrAvailableAnalysts, 'arrAnalystAvailability', $arrAnalystAvailability, 'firstAvailableActivityDate', $firstAvailableActivityDate, 'lastAvailableActivityDate', $lastAvailableActivityDate);


	}

	/**
	 * function to get last activity from analyst
	 *
	 * @param integer $empresa_id
	 * @param string $periodo_apuracao
	 * @param integer $analista_id
	 * @return array
	 */
	public function getLastAnalystActivity($empresa_id, $periodo_apuracao, $analista_id)
	{
		$sqlAnalystActivity =
			"SELECT
				ca.id AS cronogramaatividade_id,
				ca.Id_usuario_analista AS usuario_analista_id,
				pg.Data_prev_carga AS data_previsao_carga,
				MAX(ca.data_atividade) AS data_atividade,
				ca.limite AS data_limite,
				ta.Qtd_minutos AS tempo_atividade
			FROM
				agenda.previsaocarga pg
			INNER JOIN agenda.tributos t	ON pg.Tributo_id = t.id
			LEFT JOIN agenda.regras r ON t.id = r.tributo_id
			LEFT JOIN agenda.cronogramaatividades ca ON r.id = ca.regra_id
			LEFT JOIN agenda.estabelecimentos e ON ca.estemp_id = e.id
			LEFT JOIN agenda.municipios m ON e.cod_municipio = m.codigo
			LEFT JOIN agenda.tempoatividade ta ON pg.Tributo_id = ta.Tributo_id
			WHERE
				ca.emp_id = {$empresa_id}
			AND	ca.periodo_apuracao = '{$periodo_apuracao}'
			AND m.uf = ta.UF
			AND ta.Empresa_id = ca.emp_id
			AND ca.Id_usuario_analista = {$analista_id}
			-- AND ca.data_atividade <> '0000-00-00 00:00:00'
			";

		$result = DB::select($sqlAnalystActivity);

		if (!empty($result)) {
			return $result;
		} else {
			return false;
		}
	}

	/**
	 * function to get national holidays
	 *
	 * @param integer $year (four digits)
	 * @param string $is_optional (inclui ponto facultativo ou não)
	 *
	 * @return array $arrNationalHolidays
	 */
	public function getNacionalHolidays($year, $is_optional = 'S')
	{
		$sqlNationalHolidays = "";
		$sqlNationalHolidays =
			DB::table('feriados')
				->select(
					'holiday_year',
					'holiday_date',
					'holiday_name',
					'is_optional'
				)->where(['is_active' => 'A', 'holiday_year' => $year]);

		if ($is_optional == 'N') {
			$sqlNationalHolidays->where('is_optional', '=', $is_optional);
		}

		return $sqlNationalHolidays->get();
	}

	/**
	 * function to add load forecats date to array
	 *
	 * @param array $arrActivities
	 * @param string $periodo_apuracao - calculation period composed by month year (MMYYYY)
	 * @param integer $emp_id - company identification number
	 * @param string $cronogramastatus_id - identification number of cronograma in table cronogramastatus
	 *
	 * @return array $arrReturn
	 */
	private function getLoadForecastDate($arrActivities, $periodo_apuracao, $empresa_id)
	{
		$totalActivityTime = 0;
		$arrReturn = array();
		$arrTemp = array();

		$sqlTotalActivityTime =
			"SELECT
				SUM(ta.Qtd_minutos) AS tempo_total
			FROM
				agenda.previsaocarga pg
			LEFT JOIN agenda.tributos t	ON pg.Tributo_id = t.id
			LEFT JOIN agenda.regras r ON t.id = r.tributo_id
			LEFT JOIN agenda.cronogramaatividades ca ON r.id = ca.regra_id
			LEFT JOIN agenda.estabelecimentos e ON ca.estemp_id = e.id
			LEFT JOIN agenda.municipios m ON e.cod_municipio = m.codigo
			LEFT JOIN agenda.tempoatividade ta ON pg.Tributo_id = ta.Tributo_id
			LEFT JOIN agenda.ordemapuracao oa ON t.id = oa.Tributo_id
			WHERE
				ca.emp_id = {$empresa_id}
			AND	ca.periodo_apuracao = '{$periodo_apuracao}'
			AND m.uf = ta.UF
			AND ta.Empresa_id = ca.emp_id
			";
		$totalActivityTime = DB::select($sqlTotalActivityTime);
		$totalActivityTime = $totalActivityTime[0]->tempo_total;

		foreach ($arrActivities as $j => $Activities) {
			foreach ($Activities as $k => $Activity) {
				$sqlActivity =
				"SELECT
					ca.id AS cronograma_atividade_id,
					pg.periodo_apuracao,
					pg.Tributo_id AS tributo_id,
					CONCAT('(', t.id, ') ', t.nome) AS nome_tributo,
					oa.Prioridade AS prioridade_apuracao,
					ca.regra_id,
					ca.Id_usuario_analista AS usuario_analista_id,
					pg.Data_prev_carga AS data_previsao_carga,
					ca.data_atividade,
					ca.limite AS data_limite,
					ta.Qtd_minutos AS tempo_atividade,
					e.cod_municipio,
 					m.uf,
					ca.tempo_excedido,
					ca.tempo_excedido_msg
				FROM
					agenda.previsaocarga pg
				LEFT JOIN agenda.tributos t	ON pg.Tributo_id = t.id
				LEFT JOIN agenda.regras r ON t.id = r.tributo_id
				LEFT JOIN agenda.cronogramaatividades ca ON r.id = ca.regra_id
				LEFT JOIN agenda.estabelecimentos e ON ca.estemp_id = e.id
				LEFT JOIN agenda.municipios m ON e.cod_municipio = m.codigo
				LEFT JOIN agenda.tempoatividade ta ON pg.Tributo_id = ta.Tributo_id
				LEFT JOIN agenda.ordemapuracao oa ON t.id = oa.Tributo_id
				WHERE
					ca.emp_id = {$Activity['emp_id']}
				AND	ca.periodo_apuracao = '{$Activity["periodo_apuracao"]}'
				AND m.uf = ta.UF
				AND ta.Empresa_id = ca.emp_id
				AND ca.id = {$Activity['id']}
				";

				$loadForecastDate = DB::select($sqlActivity);

				if (!empty($loadForecastDate)) {
					$replacements = array(
						'tributo_id' => $loadForecastDate[0]->tributo_id,
						'Id_usuario_analista' => $loadForecastDate[0]->usuario_analista_id,
						'data_previsao_carga' => $loadForecastDate[0]->data_previsao_carga,
						'data_limite' => $loadForecastDate[0]->data_limite,
						'tempo' => $loadForecastDate[0]->tempo_atividade,
						'prioridade_apuracao' => $loadForecastDate[0]->prioridade_apuracao,
						'tempo_total' => $totalActivityTime
					);

					$arrTemp = array_replace($Activity, $replacements);
					$arrReturn[] = $arrTemp;
					$arrTemp = array();
				}
			}
		}
		return $arrReturn;
	}

	/**
	 * Search a pair $key => value in a multidimentional array
	 *
	 * @param array $array
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public function arrayMultiSearch($array, $key, $value)
	{
		$results = array();

		if (is_array($array)) {
			if (isset($array[$key]) && $array[$key] == $value) {
				$results[] = $array;
			}

			foreach ($array as $subarray) {
				$results = array_merge($results, $this->arrayMultiSearch($subarray, $key, $value));
			}
		}

		return $results;
	}

	/**
	 * Sort a 2-dimensional array on some key(s)
	 *
	 * @param array $arrData - 2-dimentional array to sort
	 * @param array $arrSortCriteria - sort criteria be passed as a separate array (It is possible to use sort order and flags)
	 * @param boolean $caseInsensitive - optional parameter (true is default value)
	 *
	 * @return array
	 */
	public function arrayMultiSorter($arrData, $arrSortCriteria, $caseInSensitive = true)
	{
		if( !is_array($arrData) || !is_array($arrSortCriteria))
			return false;

		$args = array();
		$i = 0;

		foreach($arrSortCriteria as $sortColumn => $sortAttributes)
		{
			$colList = array();
			foreach ($arrData as $key => $row)
			{
				$convertToLower = $caseInSensitive && (in_array(SORT_STRING, $sortAttributes) || in_array(SORT_REGULAR, $sortAttributes));
				$rowData = $convertToLower ? strtolower($row[$sortColumn]) : $row[$sortColumn];
				$colLists[$sortColumn][$key] = $rowData;
			}
			$args[] = &$colLists[$sortColumn];

			foreach($sortAttributes as $sortAttribute)
			{
				$tmp[$i] = $sortAttribute;
				$args[] = &$tmp[$i];
				$i++;
			}
		}
		$args[] = &$arrData;
		call_user_func_array('array_multisort', $args);
		return end($args);
	}
	// END recalculation of the activity date

	private function setPriority($array, $periodo_apuracao, $empresa_id, $id_cronogramastatus)
	{
		// var_dump('setPriority ::: $array', $array, 'setPriority ::: $periodo_apuracao', $periodo_apuracao, 'setPriority ::: $empresa_id', $empresa_id, 'setPriority ::: $id_cronogramastatus', $id_cronogramastatus);
		$analistaDisponibilidade = DB::table('analistadisponibilidade')->where(['periodo_apuracao' => $periodo_apuracao, "Empresa_id" => $empresa_id])->get();
		$disponibilidade = array();
		if (!empty($analistaDisponibilidade)) {
			foreach ($analistaDisponibilidade as $key => $analista) {
				$disponibilidade[$analista->id_usuarioanalista] =
				$analista;
			}
		}

		$priority = array();
		$Oldpriority = array();
		foreach ($array as $tributo_id => $atividades) {
			$ordem = OrdemApuracao::where('Tributo_id', $tributo_id)->first();
			if (!empty($ordem)) {
				$Oldpriority[$ordem->Prioridade] = $atividades;
			}
		}

		$horaInicial = array("08","00","00");
		$horaFinal = array("17","00","00");
		$minutosPorDia = 480; //60 x 8

		$time = 0;
		$a = 0;

		if (!empty($Oldpriority)) {
			foreach ($Oldpriority as $x => $ordering) {
				$a++;
				if (isset($Oldpriority[$a])) {
					$priority[$a] = $Oldpriority[$a];
				} else {
					continue;
				}
			}
		}

		$this->lastDate = array();
		$this->minutosTrabalhadosNoDia = array();

		$qtd_priority = 0;

		DB::table('cronogramastatus')
			->where('id', $id_cronogramastatus)
			->update(['qtd_priority' => $qtd_priority]);

		if (!empty($priority)) {
			foreach ($priority as $x => $non_single) {
				foreach ($non_single as $unicKey => $single_priority) {
					DB::table('cronogramastatus')
					->where('id', $id_cronogramastatus)
					->update(['qtd_priority' => $qtd_priority]);

					$cronograma = CronogramaAtividade::where('id',$single_priority['id'])->first();
					$estabelecimento = Estabelecimento::where('id', $single_priority['estemp_id'])->first();
					$municipios = Municipio::where('codigo',$estabelecimento->cod_municipio)->first();

					$uf = $municipios->uf;

					if (!empty($cronograma)) {
						// var_dump('if em cronograma', $cronograma);
						$data_hora_inicio = $cronograma->inicio_aviso;
						$data_hota_fim = null;
						$minutos_por_dia = null;
						if( isset($disponibilidade[$cronograma->Id_usuario_analista]) ) {
							$data_hora_inicio = $disponibilidade[$cronograma->Id_usuario_analista]->data_ini_disp;
							$data_hota_fim = $disponibilidade[$cronograma->Id_usuario_analista]->data_fim_disp;
							$minutos_por_dia = $disponibilidade[$cronograma->Id_usuario_analista]->qtd_min_disp_dia;

						}

						if( isset($lastDate[$cronograma->Id_usuario_analista]) ) {
							// var_dump('GOMU GOMU NO...Bazooka', $cronograma->Id_usuario_analista);
							$lastDate[$cronograma->Id_usuario_analista] =
							$this->atualizarUltimaData(
								$cronograma->data_atividade,
								$lastDate[$cronograma->Id_usuario_analista]
							);
							// var_dump('GOMU GOMU NO...Rocket', $cronograma->data_atividade, $lastDate);
						} else if($cronograma->Id_usuario_analista !== null) {
							$lastDate[$cronograma->Id_usuario_analista] = $this->dataHoraValida($data_hora_inicio, $id_cronogramastatus);
							// var_dump('GOMU GOMU NO...Pistol Shot', $lastDate);
							// TODO: aqui já calcula a data_atividade errado
						}
						if(isset($lastDate) && isset($cronograma->Id_usuario_analista) && isset($lastDate[$cronograma->Id_usuario_analista])) {

							$novaHora = $this->calculaProximaDataAtividade(
								$cronograma->Id_usuario_analista,
								$lastDate,
								$cronograma->tempo,
								$horaInicial,
								$horaFinal,
								$data_hora_inicio,
								$data_hota_fim,
								$uf,
								$minutos_por_dia, $id_cronogramastatus
							);
							// var_dump('GOMU GOMU NO...novaHORA', $novaHora);
							// TODO: aqui já calcula a data_atividade errado (+30min diff)

							if($novaHora !== false && $novaHora !== null) {
								$cronograma->data_atividade = $novaHora->toDateTimeString();
								$lastDate[$cronograma->Id_usuario_analista] = $cronograma->data_atividade;
								// var_dump('GOMU GOMU NO...Gatling', $lastDate, $cronograma->regra_id, $cronograma->limite);
								// TODO: aqui já calcula a data_atividade errado (+30min diff) - apenas normaliza tirando os microseconds
							}
						}

						$cronograma->save();
					}
					$qtd_priority++;
				}
			}
		}
	}

	private function loadData($array)
	{
		foreach ($array as $key => $one) {

			$Regra = Regra::findorFail($one['regra_id']);

			$data_carga = DB::Select('SELECT A.Data_prev_carga FROM previsaocarga A WHERE A.periodo_apuracao = "'.$one['periodo_apuracao'].'" AND A.Tributo_id = '.$Regra->tributo_id);
		}

		if (!empty($data_carga)) {
			return $data_carga[0]->Data_prev_carga;
		}
	}

	private function atualizarUltimaData($dataNova, $dataAtual) {
		if($dataAtual > $dataNova) return $dataAtual;
		return $dataNova;
	}

	private function dataHoraValida($data, $id_cronogramastatus) {
		$data = strtotime($data);
		$hora = date("H", $data);

		if($hora > 17) {

			$data = Carbon::create(
				date('Y', $data), date('m', $data), date('d', $data),
				"08", "00", "00",'GMT')->addDay();
		} else if($hora < 8) {
			$data = Carbon::create(
				date('Y', $data), date('m', $data), date('d', $data),
				"08", "00", "00",'GMT');
		}

		return $data;
	}

	public function calculaProximaDataAtividade(
			$Id_usuario_analista,
			$ultima_datas_por_analista,
			$tempo,
			$horaInicial, //hora do início do dia
			$horaFinal,   //hora do fim do dia
			$analistaDisponibilidadeInicio, //inicio da data disponivel para o analista (se houver)
			$analistaDisponibilidadeFim,	   //fim da data disponivel para o analista (se houver)
			$uf, $minutos_por_dia, $id_cronogramastatus) {
		if(isset($ultima_datas_por_analista[$Id_usuario_analista])) {
			$data_hota_inicio = $ultima_datas_por_analista[$Id_usuario_analista];
		} else {
			return false;
		}

		$minutosTrabalhadosNoDia = array();

		if(is_numeric($data_hota_inicio)) {
			$date = new DateTime();
			$date->setTimestamp($data_hota_inicio);
			$data_hota_inicio = $date->format('Y-m-d H:i:s');
		}

		$data_hota_inicio = explode(" ", $data_hota_inicio);

		$data = explode("-",$data_hota_inicio[0]);
		$hora = explode(":",$data_hota_inicio[1]);

		if( !isset($this->minutosTrabalhadosNoDia[$Id_usuario_analista]) )
			$this->minutosTrabalhadosNoDia[$Id_usuario_analista] = 0;

		$mutable = Carbon::create($data[0], $data[1], $data[2], $hora[0], $hora[1], $hora[2],'GMT');

		if($minutos_por_dia !== null && (($this->minutosTrabalhadosNoDia[$Id_usuario_analista] + $tempo) > $minutos_por_dia )) {
			$this->nextDay($mutable, $horaInicial, $Id_usuario_analista);
		}
		$fimDoDia = date_time_set(DateTime::createFromFormat('Y-m-d H:i:s',$mutable), $horaFinal[0], $horaFinal[1], $horaFinal[2]);

		$mutable->addMinutes($tempo);
		$minutosAcrescentados = $tempo;

		if($mutable > $fimDoDia) {
			$interval = $mutable->diff($fimDoDia);
			$minutosFaltantes = $interval->format('%i');

			$mutable = $this->nextDay($mutable, $horaInicial, $Id_usuario_analista);
			while(!$this->checkFeriados($mutable, $uf)) $mutable = $this->nextDay($mutable, $horaInicial, $Id_usuario_analista);

			$minutosAcrescentados = $minutosFaltantes;
			return $mutable->addMinutes($minutosFaltantes);
		}

		if(isset($analistaDisponibilidadeFim) && $mutable > $analistaDisponibilidadeFim) return false;

		$this->minutosTrabalhadosNoDia[$Id_usuario_analista] += $minutosAcrescentados;

		return $mutable;
	}

	private function nextDay($mutable, $horaInicial, $Id_usuario_analista) {
		$this->minutosTrabalhadosNoDia[$Id_usuario_analista] = 0;
		$mutable->addDay();
		$inicioDoDia = date_time_set($mutable, $horaInicial[0], $horaInicial[1], $horaInicial[2]);

		return $inicioDoDia;
	}

	private function checkFeriados($data, $uf) {
		$data_dia_completo = explode(" ", $data);

		$data_dia = explode("-", $data_dia_completo[0]);

		$feriadosNacionais = $this->getFeriadosNacionais($data_dia[0]);

		foreach($feriadosNacionais as $feriado) {

			$dia = $data_dia[2];
			$mes = $data_dia[1];
			$ano = $data_dia[0];

			$data_feriado_nacional = $ano."-".$mes."-".$dia;

			if($data_feriado_nacional == $data_dia_completo[0]) return true;
		}


		$feriadosEstaduais = FeriadoEstadual::where("uf",$uf)->first();
		$feriadosEstaduais = $feriadosEstaduais->datas;

		$feriadosEstaduais = explode(";", $feriadosEstaduais);

		foreach($feriadosEstaduais as $feriado) {
			$feriado = explode("-", $feriados);

			$dia = $feriado[0];
			$mes = $feriado[1];
			$ano = $data_dia[0];

			$data_feriado_estadual = $ano."-".$mes."-".$dia;

			if($data_feriado_estadual == $data_dia_completo[0]) return true;
		}
		return false;
	}

	private function checkGeneration($data, $freq_entrega)
	{
		$mes = date('m/Y');

		$data1 = $data;
		$data2 = str_replace('-', '/', $data1);
		$data = date('m/Y', strtotime($data2));

		if ($freq_entrega == 'M') {
			return true;
		}

		if ($freq_entrega == 'A') {
			if ($data == $mes) {
				return true;
			}
		}

		if ($freq_entrega == 'S') {
			if ($data == $mes) {
				return true;
			}
		}

		return false;
	}

	public function generateNotifications($user) {
		return false;
		/*
		// Activate auto notification generation
		$active = true;

		if (!$active) return true;

		$param_id = $user->id;
		$with_user = function ($query) use ( $param_id ) {
			$query->where('user_id',$param_id );
		};

		$atividades = Atividade::select('atividades.descricao','atividades.limite','regras.nome_especifico')
			->join('regras', 'atividades.regra_id', '=', 'regras.id')
			//->whereHas('users',$with_user)
			->where('inicio_aviso','<',date("Y-m-d H:i:s"))->where('status',1)
			->groupBy('atividades.descricao','atividades.limite')
			->orderBy('atividades.limite')->get();

		$subject = "BravoTaxCalendar - Avisos";
		$data = array('subject'=>$subject,'messageLines'=>array());

		foreach($atividades as $atividade) {
			$descricao = $atividade->descricao;
			if ($atividade->nome_especifico != '') {
				$descricao = 'Entrega '.$atividade->nome_especifico;
			}
			$date = date_create($atividade->limite);
			$data['messageLines'][] = $descricao.' - '.date_format($date,"d/m/Y");
		}

		if (sizeof($atividades)>0) {
			$this->sendMail($user, $data, 'emails.notification');
		}

		return sizeof($atividades);
		*/
	}

	private function checkDuplicidade($atividade)
	{
		$atividades = DB::table('atividades')
			->select('atividades.id');

		if (isset($atividade['estemp_id'])) {
			$atividades = $atividades->where('estemp_id', $atividade['estemp_id']);
		}

		if (isset($atividade['emp_id'])) {
			$atividades = $atividades->where('emp_id', $atividade['emp_id']);
		}

		if (isset($atividade['periodo_apuracao'])) {
			$atividades = $atividades->where('periodo_apuracao', $atividade['periodo_apuracao']);
		}

		if (isset($atividade['regra_id'])) {
			$atividades = $atividades->where('regra_id', $atividade['regra_id']);
		}

		$atividades = $atividades->get();
		if (!empty($atividades)) {
			return false;
		}
		return true;
	}

	private function checkDuplicidadeCronograma($atividade)
	{
		$atividades = DB::table('cronogramaatividades')
			->select('cronogramaatividades.id');

		if (isset($atividade['estemp_id'])) {
			$atividades = $atividades->where('estemp_id', $atividade['estemp_id']);
		}

		if (isset($atividade['emp_id'])) {
			$atividades = $atividades->where('emp_id', $atividade['emp_id']);
		}

		if (isset($atividade['periodo_apuracao'])) {
			$atividades = $atividades->where('periodo_apuracao', $atividade['periodo_apuracao']);
		}

		if (isset($atividade['regra_id'])) {
			$atividades = $atividades->where('regra_id', $atividade['regra_id']);
		}

		$atividades = $atividades->get();
		if (!empty($atividades)) {
			return false;
		}
		return true;
	}

	public function generateAdminNotifications($user) {
		// Activate auto notification generation
		$active = true;

		if (!$active) return true;

		$param_id = $user->id;
		$with_user = function ($query) use ( $param_id ) {
			$query->where('user_id',$param_id );
		};

		$atividades = Atividade::select('atividades.descricao','atividades.limite','regras.nome_especifico')
			->join('regras', 'atividades.regra_id', '=', 'regras.id')
			//->whereHas('users',$with_user)
			->where('status',2)
			->groupBy('atividades.descricao','atividades.limite')
			->orderBy('atividades.limite')->get();

		$subject = "BravoTaxCalendar - Aviso atividades em aprovação";
		$data = array('subject'=>$subject,'messageLines'=>array());

		foreach($atividades as $atividade) {
			$descricao = $atividade->descricao;
			if ($atividade->nome_especifico != '') {
				$descricao = 'Entrega '.$atividade->nome_especifico;
			}
			$date = date_create($atividade->limite);
			$data['messageLines'][] = $descricao.' - '.date_format($date,"d/m/Y");
		}

		if (sizeof($atividades)>0) {
			$this->sendMail($user, $data, 'emails.notification-em-aprovacao');
		}

		return sizeof($atividades);

	}

	public function generateSupervisorNotifications($user) {
		// Activate auto notification generation
		$active = true;

		if (!$active) return true;

		$with_user = function ($query) {
			$query->where('user_id', Auth::user()->id);
		};
		$tributos_granted = Tributo::select('id')->whereHas('users',$with_user)->get();
		$granted_array = array();
		foreach($tributos_granted as $el) {
			$granted_array[] = $el->id;
		}

		$atividades = Atividade::select('atividades.descricao','atividades.limite','regras.nome_especifico')
			->join('regras', 'atividades.regra_id', '=', 'regras.id')
			//->whereHas('users',$with_user)
			->where('status',2)
			->groupBy('atividades.descricao','atividades.limite')
			->orderBy('atividades.limite')->get();

		$atividades->whereHas('regra.tributo', function ($query) use ($granted_array) {
			$query->whereIn('id', $granted_array);
		});

		$subject = "BravoTaxCalendar - Aviso atividades em aprovação";
		$data = array('subject'=>$subject,'messageLines'=>array());

		foreach($atividades as $atividade) {
			$descricao = $atividade->descricao;
			if ($atividade->nome_especifico != '') {
				$descricao = 'Entrega '.$atividade->nome_especifico;
			}
			$date = date_create($atividade->limite);
			$data['messageLines'][] = $descricao.' - '.date_format($date,"d/m/Y");
		}

		if (sizeof($atividades)>0) {
			$this->sendMail($user, $data, 'emails.notification-em-aprovacao');
		}

		return sizeof($atividades);

	}

	public function getFeriadosCarbon($uf,$ano=null) {

		$retCarb = array();
		if ($ano==null) $ano = date('Y');

		$fer_nac = $this->getFeriadosNacionais($ano);
		foreach($fer_nac as $el) {
			$exploded = explode('-',$el);
			$retCarb[] = Carbon::create($ano, intval($exploded[1]), intval($exploded[0]), 0);
			//
		}

		$retval = FeriadoEstadual::select('*')->where('uf',$uf);
		$fer_est = explode(';',$retval->first()->datas);

		foreach($fer_est as $el) {
			$exploded = explode('-',$el);
			$retCarb[] = Carbon::create($ano, intval($exploded[1]), intval($exploded[0]), 0);
			//
		}

		return $retCarb;
	}

	/**
	 * @desc checa se um analista possui atividade (por UF e tributo) (Card 422)
	 * @return boolean
	 * @param int $analista_id
	 * @param int $uf
	 * @param int $tributo_id
	 * */
	public function existeAtividadeAnalista($analista_id, $uf, $tributo_id) {

		$query = sprintf("
				SELECT
				    A.id
				FROM agenda.atividadeanalista A
				INNER JOIN agenda.empresas B ON A.Emp_id = B.id
				INNER JOIN agenda.users C ON A.Id_usuario_analista = C.id
				LEFT JOIN agenda.atividadeanalistafilial D ON (D.Id_atividadeanalista = A.id)
				INNER JOIN agenda.tributos G ON A.Tributo_id = G.id
				WHERE A.Id_usuario_analista = '%s'
				AND A.uf                    = \"%s\"
				AND A.Tributo_id            = '%s'
				GROUP BY C.name , B.razao_social , A.id, G.nome", $analista_id, $uf, $tributo_id);

		$table = DB::select($query);

		return (!empty($table));
	}

	public function doCriarDisponibilidadeAnalista($periodo_apuracao) {

		$mes  = intval(substr($periodo_apuracao, 0, 2));
		$ano  = intval(substr($periodo_apuracao, 2, 4));
		$days = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

		if ($mes == 12) {
			$mes = 1;
			$ano++;
		}
		else {
			$mes++;
		}

		$smes  = str_pad($mes, 2, '0', STR_PAD_LEFT);
		$start = sprintf('%s-%s-%s', $ano, $smes, '01');
		$end   = sprintf('%s-%s-%s', $ano, $smes, $days[$mes]);
//		$end   = sprintf('%s-%s-%s', ($ano + 3), $smes, $days[$mes]);

		return [$start, $end];
	}

}
