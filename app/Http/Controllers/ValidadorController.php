<?php

namespace App\Http\Controllers;

use session;

use App\Http\Requests;
use App\Models\Empresa;
use App\Models\Atividade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ValidadorController extends Controller {

    public function __construct() {

        $this->empresa = null;
        $this->tributosA = DB::table('tributos')->select('nome', 'id')->where(['tributos.id' => 1])->pluck('nome', 'id');
		$this->tributosB = DB::table('tributos')->select('nome', 'id')->where(['tributos.id' => 8])->pluck('nome', 'id');
        $this->ufs = array('AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO');

        if (!session()->get('seid')) {
            redirect()->back()->with('warning', 'Você não selecionou nenhuma empresa! Por favor volte para a página Home e selecione uma...');
        }

        $this->middleware('auth');

        if (!Auth::guest() && $this->empresa == null && !empty(session()->get('seid'))) {
            $this->empresa = Empresa::findOrFail(session()->get('seid'));
        }
    }

    public function index() {

        $tributosA = $this->tributosA;
        $tributosB = $this->tributosB;

        return view('validador.index')->with(compact('tributosA', 'tributosB'));
    }

    public function validateData(Request $request) {

        $input     = $request->all();
        $validator = Validator::make($input, [
        	'tributo_id_a' => 'required',
        	'tributo_id_b' => 'required',
        	'periodo_apuracao' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()
                        ->back()
                        ->withErrors($validator)
                        ->withInput();
        }

        $tributoA        = $input['tributo_id_a'];
        $tributoB        = $input['tributo_id_b'];
        $periodoApuracao = str_replace('/', '', $input['periodo_apuracao']);
        $activities      = $this->listActivities($tributoA, $periodoApuracao, $this->ufs, $this->empresa->id);

        if (count($activities) > 0) {
            DB::table('criticasvalores')
                ->where('empresa_id', '=', $this->empresa->id)
                ->where('periodo_apuracao', '=', $periodoApuracao)
                ->delete();

            foreach ($activities as $i => $activityA) {
                $activity                = $this->getActivity($tributoB, $periodoApuracao, array($activityA->uf), $this->empresa->id, $activityA->estemp_id);
                $arrAtividadesAntecipado = [];
                $temAntecipado           = false;

                if (count($activity) < 1) {
                    /*
                    * exceção a regra:
                    * Sped Fiscal[tributo_id=1] (Valor Total do ICMS)->atividadesA-vlr_recibo_3 não possui Guia de ICMS
                    */
                    if ($activityA->vlr_recibo_3 > 0) {
                        DB::table('criticasvalores')->insert(
                            [
                            'empresa_id' => $this->empresa->id,
                            'estemp_id' => $activityA->estemp_id,
                            'periodo_apuracao' => $periodoApuracao,
                            'critica' => 'Valor Sped - id atividades: '.$activityA->id.' não possui Guia de ICMS'
                            ]
                        );
                    }
                } else {
                    $activityB = $activity[0];

                    /*
                    * 3a regra:
                    * Sped Fiscal[tributo_id=1] (Valor Recolhido ou a Recolher, extra-apuração)->atividadesA-vlr_recibo_5+vlr_recibo_3 != ICMS[tributo_id=8] (Valor ICMS/DIFAL por Fora)->atividadesB-vlr_recibo_4+vlr_recibo_1
                    */

                    // INICIO CARD Nº 412 Regra 1 19/01/2021

                    if ($activityA->uf == 'RN' || $activityA->uf == 'CE') {
                    	$valorSped = number_format($activityA->vlr_recibo_3, 2);
                    }
                    else {
                    	$valorSped = number_format($activityA->vlr_recibo_5+$activityA->vlr_recibo_3, 2);
                    }

                    // FIM CARD Nº 412 Regra 1

                    if($activityA->uf == 'PB') {
//                      $valorICMSDIFAL = ($activityB->vlr_recibo_3 + $activityB->vlr_recibo_1);
                        $valorICMSDIFAL = $activityB->vlr_recibo_1;
                    } else {
                        $valorICMSDIFAL = ($activityB->vlr_recibo_4 + $activityB->vlr_recibo_3 + $activityB->vlr_recibo_1);
                    }

                    // INICIO CARD Nº 412 Regra 2 19/01/2021

                    if (in_array($activityA->uf, ['SE', 'AL', 'PA', 'MA'])) {

//                   	$activityAntecipado = $this->getActivity(28, $periodoApuracao, array($activityA->uf), $this->empresa->id, $activityA->estemp_id);
                    	$activitiesAntecipado = $this->getActivitiesAntecipado($periodoApuracao, array($activityA->uf), $this->empresa->id, $activityA->estemp_id);

                    	if (count($activitiesAntecipado) > 0) {

                    		$temAntecipado    = true;
                    		$arrSomaValor2Aux = []; // ANTECIPADO pode haver situações com + de 1 registro, então fazemos a soma de todos

                    		foreach ($activitiesAntecipado as $activityAntecipado) {
                    			array_push($arrAtividadesAntecipado, $activityAntecipado->id);
                    			array_push($arrSomaValor2Aux, $activityAntecipado->vlr_recibo_2);
                    		}

                    		$valorICMSDIFAL += array_sum($arrSomaValor2Aux);
                    	}
                    	else {
                    		$temAntecipado = false;
                    	}

                    	$checksAntecipado = true;
                    }
                    else {
                    	$checksAntecipado = false;
                    }

                    // FIM CARD Nº 412 Regra 2

                    $valorICMSDIFAL = number_format($valorICMSDIFAL, 2);

                    if ($valorSped !== $valorICMSDIFAL) {

                    	if ($checksAntecipado && !$temAntecipado) {
                    		DB::table('criticasvalores')->insert([
                    			'empresa_id'       => $this->empresa->id,
                    			'estemp_id'        => $activityA->estemp_id,
                    			'periodo_apuracao' => $periodoApuracao,
                    			'critica'          => sprintf('Nenhuma atividade ANTECIPADO foi encontrada - ids atividades: (%s e %s)', $activityA->id, $activityB->id)
                    		]);
                    	}

                    //     echo "<pre>";
                    //     echo 'UF SPED (Tributo A): '.$activityA->uf;
                    //     echo '<br>';
                    //     echo "
                    //     [id] => ".$activityA->id."
                    //     [emp_id] => ".$activityA->emp_id."
                    //     [estemp_id] => ".$activityA->estemp_id."
                    //     [codigo] => ".$activityA->codigo."
                    //     [periodo_apuracao] => ".$activityA->periodo_apuracao."
                    //     [uf] => ".$activityA->uf."
                    //     [regra_id] => ".$activityA->regra_id."
                    //     [tributo_id] => ".$activityA->tributo_id."
                    //     [vlr_recibo_1] => ".$activityA->vlr_recibo_1."
                    //     [vlr_recibo_3] => ".$activityA->vlr_recibo_3."
                    //     [vlr_recibo_4] => ".$activityA->vlr_recibo_4."
                    //     [vlr_recibo_5] => ".$activityA->vlr_recibo_5."
                    //     [retificacao_id] => ".$activityA->retificacao_id."
                    //     [tipo_geracao] => ".$activityA->tipo_geracao."
                    //     <br><br>";
                    //     echo 'UF ICMS (Tributo B): '.$activityB->uf;
                    //     echo '<br>';
                    //     echo 'ICMS (8) (Tributo B): ';
                    // echo '<br>';
                    // echo "
                    //     [id] => ".$activityB->id."
                    //     [emp_id] => ".$activityB->emp_id."
                    //     [estemp_id] => ".$activityB->estemp_id."
                    //     [codigo] => ".$activityB->codigo."
                    //     [periodo_apuracao] => ".$activityB->periodo_apuracao."
                    //     [uf] => ".$activityB->uf."
                    //     [regra_id] => ".$activityB->regra_id."
                    //     [tributo_id] => ".$activityB->tributo_id."
                    //     [vlr_recibo_1] => ".$activityB->vlr_recibo_1."
                    //     [vlr_recibo_3] => ".$activityB->vlr_recibo_3."
                    //     [vlr_recibo_4] => ".$activityB->vlr_recibo_4."
                    //     [vlr_recibo_5] => ".$activityB->vlr_recibo_5."
                    //     [retificacao_id] => ".$activityB->retificacao_id."
                    //     [tipo_geracao] => ".$activityB->tipo_geracao."
                    //     <br><br>";
                    //     echo 'Valor SPED != ICMSDIFAL? => ';
                    //     echo ($valorSped !== $valorICMSDIFAL) ? 'É DIFERENTE' : 'É IGUAL';
                    //     echo '<br>';
                    //     echo ' valorSped (tipo): '.gettype($valorSped);
                    //     echo '<br>';
                    //     echo ' valorICMSDIFAL (tipo): '.gettype($valorICMSDIFAL);
                    //     echo '<br>';
                    //     echo
                    //         " ### valorSped != valorICMSDIFAL ? ($valorSped != $valorICMSDIFAL) => {$valorSped} e {$valorICMSDIFAL}",
                    //         '<br> empresa_id: ', $this->empresa->id,
                    //         '<br> estemp_id: ', $activityA->estemp_id,
                    //         '<br> periodo_apuracao: ', str_replace('/', '', $input['periodo_apuracao']),
                    //         '<br> critica: ', 'Valor Sped Fiscal/Extra Apuração e ICMS/DIFAL estão diferentes - id atividades: '.$activityA->id.' e '.$activityB->id
                    //     ;
                    //     echo "<br><br>";
                    //     echo "</pre>";

                    	$arrParamsCritica = [
            	        	'empresa_id' => $this->empresa->id,
        	            	'estemp_id' => $activityA->estemp_id,
    	                	'periodo_apuracao' => $periodoApuracao,
	                    	'critica' => 'Valor Sped Fiscal/Extra Apuração e ICMS/DIFAL estão diferentes - id atividades: '.$activityA->id.' e '.$activityB->id
                    	];

                    	// INICIO CARD 412 AJUSTE

                    	if ($temAntecipado) {
                    		$arrParamsCritica['critica'] = sprintf('Valor Sped Fiscal/Extra Apuração e ICMS/DIFAL/ANTECIPADO estão diferentes - id atividades: %s, %s, %s', $activityA->id, $activityB->id, implode(', ', $arrAtividadesAntecipado));
                    	}

                    	// FIM CARD 412 Ajuste

                        DB::table('criticasvalores')->insert($arrParamsCritica);
                    }
                }
            }
            // Procura atividade com Guia ICMS sem Guia Sped
            $ICMSActivities = $this->listActivities($tributoB, $periodoApuracao, $this->ufs, $this->empresa->id);

            if (count($ICMSActivities) > 0) {
                foreach ($ICMSActivities as $key => $ICMSActivity) {
                    $SPEDActivity = $this->getActivity($tributoA, $periodoApuracao, array($ICMSActivity->uf), $this->empresa->id, $ICMSActivity->estemp_id);

                    if (count($SPEDActivity) < 1) {
                        /*
                        * exceção a  regra:
                        * Sped Fiscal[tributo_id=1] (Valor Total do ICMS)->atividadesA-vlr_recibo_3 não possui Guia de ICMS
                        */
                        if ($ICMSActivity->vlr_recibo_1 > 0) {
                            DB::table('criticasvalores')->insert(
                                [
                                'empresa_id' => $this->empresa->id,
                                'estemp_id' => $ICMSActivity->estemp_id,
                                'periodo_apuracao' => $periodoApuracao,
                                'critica' => 'Valor ICMS - id atividades: '.$ICMSActivity->id.' não possui Guia de Sped'
                                ]
                            );
                        }
                    }
                }
            }

            $tributosA = $this->tributosA;
            $tributosB = $this->tributosB;

            $query =
                "SELECT criticasvalores.criticasvalores_id,
                        criticasvalores.empresa_id,
                        empresas.razao_social,
                        criticasvalores.estemp_id,
                        estabelecimentos.cnpj,
                        estabelecimentos.codigo,
                        criticasvalores.periodo_apuracao,
                        criticasvalores.critica,
                        criticasvalores.data_critica
                FROM criticasvalores
                LEFT JOIN empresas ON empresas.id = criticasvalores.empresa_id
                LEFT JOIN estabelecimentos ON estabelecimentos.empresa_id = empresas.id
                WHERE criticasvalores.empresa_id = ".$this->empresa->id."
                AND estabelecimentos.id = criticasvalores.estemp_id
                AND criticasvalores.periodo_apuracao = ".$periodoApuracao."
                ";
            $table = DB::select($query);

            return view('validador.index')
                    ->with(compact('tributosA', 'tributosB', 'table'))
                    ->with('status', 'Processamento finalizado com sucesso.');
        } else {
            return redirect()->back()->with('status', 'Não foram encontradas atividades para essa validação.');
        }
    }

    private function getActivity($tributoB, $periodo, $uf, $emp_id, $estab_id) {

        $activities = Atividade::select(
			'atividades.id',
            'atividades.emp_id',
            'atividades.estemp_id',
            'estabelecimentos.codigo',
            'atividades.periodo_apuracao',
            'municipios.uf',
            'atividades.regra_id',
            'regras.tributo_id',
            'atividades.vlr_recibo_1',
            'atividades.vlr_recibo_3',
            'atividades.vlr_recibo_4',
            'atividades.vlr_recibo_5',
            'atividades.retificacao_id',
            'atividades.tipo_geracao'
		)
		->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
		->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
		->join('regras', 'atividades.regra_id', '=', 'regras.id')
		->where('atividades.periodo_apuracao', '=', $periodo)
		->where('regras.tributo_id', '=', $tributoB)
		->whereIn('municipios.uf', $uf)
		->where('atividades.emp_id', $emp_id)
		->where('atividades.estemp_id', $estab_id)
		->where('atividades.status', 3)
        ->orderBy('atividades.id', 'desc')
        ->limit(1);

        return $activities->get();
    }

    private function listActivities($tributoA, $periodo, $ufs, $emp_id) {

        $retIDs = Atividade::select('atividades.retificacao_id')
		->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
		->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
		->join('regras', 'atividades.regra_id', '=', 'regras.id')
		->where('atividades.periodo_apuracao', '=', $periodo)
		->where('regras.tributo_id', '=', $tributoA)
		->whereIn('municipios.uf', $ufs)
		->where('atividades.emp_id', $emp_id)
		->where('atividades.status', 3)
		->where('atividades.tipo_geracao', 'R')
        ->groupBy('estabelecimentos.codigo')
        ->orderBy('estabelecimentos.codigo', 'asc')
        ->orderBy('atividades.id', 'desc')
        ->get();

        $activities = Atividade::select(
			'atividades.id',
            'atividades.emp_id',
            'atividades.estemp_id',
            'estabelecimentos.codigo',
            'atividades.periodo_apuracao',
            'municipios.uf',
            'atividades.regra_id',
            'regras.tributo_id',
            'atividades.vlr_recibo_1',
            'atividades.vlr_recibo_3',
            'atividades.vlr_recibo_4',
            'atividades.vlr_recibo_5',
            'atividades.retificacao_id',
            'atividades.tipo_geracao'
		)
		->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
		->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
		->join('regras', 'atividades.regra_id', '=', 'regras.id')
		->where('atividades.periodo_apuracao', '=', $periodo)
		->where('regras.tributo_id', '=', $tributoA)
		->whereIn('municipios.uf', $ufs)
		->where('atividades.emp_id', $emp_id)
        ->where('atividades.status', 3)
        ->whereNotIn('atividades.id', $retIDs)
        ->orderBy('atividades.retificacao_id', 'asc')
        ->orderBy('atividades.id', 'desc')
        ->get();

       return $this->activitiesReclassification($activities, $tributoA, $periodo, $ufs, $emp_id);
    }

    private function activitiesReclassification($activities, $tributo, $periodo, $ufs, $emp_id) {

        $arrValidIDs = array();
        $arrActivities = array();
        $oldRetID = 0;
        $retIDs = Atividade::select(DB::raw('MAX(atividades.id) as id'))
		->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
		->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
		->join('regras', 'atividades.regra_id', '=', 'regras.id')
		->where('atividades.periodo_apuracao', '=', $periodo)
		->where('regras.tributo_id', '=', $tributo)
		->whereIn('municipios.uf', $ufs)
		->where('atividades.emp_id', $emp_id)
		->where('atividades.status', 3)
		->where('atividades.tipo_geracao', 'R')
        ->groupBy('estabelecimentos.codigo')
        ->orderBy('estabelecimentos.codigo', 'asc')
        ->orderBy('atividades.id', 'desc')
        ->get();

        foreach ($retIDs as $key => $value) {
            $arrValidIDs[] = $value->id;
        }

        foreach ($activities as $key => $activity) {
            // é igual, descarta pois já processou o maior id referente a retificacao_id
            if ($oldRetID == $activity->retificacao_id && $activity->tipo_geracao == 'R') {
                $oldRetID = $activity->retificacao_id;
            } else {
                // se for diferente, processa
                // reatribui a posicao em um novo array
                $arrActivities[] = $activity;
                $oldRetID = $activity->retificacao_id;
            }
        }

        return $arrActivities;
    }

    private function getActivitiesAntecipado($periodo, $uf, $emp_id, $estab_id) {
    	$tributo    = 28; // ANTECIPADO = tributo_id 28
    	$activities = Atividade::select(
    		'atividades.id',
    		'atividades.emp_id',
    		'atividades.estemp_id',
    		'estabelecimentos.codigo',
    		'atividades.periodo_apuracao',
    		'municipios.uf',
    		'atividades.regra_id',
    		'regras.tributo_id',
    		'atividades.vlr_recibo_1',
    		'atividades.vlr_recibo_2',
    		'atividades.vlr_recibo_3',
    		'atividades.vlr_recibo_4',
    		'atividades.vlr_recibo_5',
    		'atividades.retificacao_id',
    		'atividades.tipo_geracao'
    		)
    		->join('estabelecimentos', 'atividades.estemp_id', '=', 'estabelecimentos.id')
    		->join('municipios', 'estabelecimentos.cod_municipio', '=', 'municipios.codigo')
    		->join('regras', 'atividades.regra_id', '=', 'regras.id')
    		->where('atividades.periodo_apuracao', '=', $periodo)
    		->where('regras.tributo_id', '=', $tributo)
    		->whereIn('municipios.uf', $uf)
    		->where('atividades.emp_id', $emp_id)
    		->where('atividades.estemp_id', $estab_id)
    		->where('atividades.status', 3)
    		->orderBy('atividades.id', 'desc');

    		return $activities->get();
    }

}
