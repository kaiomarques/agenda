<?php

namespace App\Http\Controllers;


use App\Models\User;
use Auth;
use DB;
use App\Models\Regra;
use App\Models\Municipio;
use App\Services\EntregaService;
use App\Http\Requests;


class CalendariosController extends Controller
{
    protected $eService;

    function __construct(EntregaService $service)
    {
        $this->eService = $service;
    }

    public function index()
    {
        $user_id = Auth::user()->id;
        $events = [];

        $feriados = $this->eService->getFeriadosNacionais();
        $feriados_estaduais = $this->eService->getFeriadosEstaduais();

        //Carregando as atividades
        $user = User::findOrFail(Auth::user()->id);
        if ($user->hasRole('analyst') || $user->hasRole('supervisor')) {
            //ESTAB
            $atividades_estab = DB::table('atividades')
                ->join('estabelecimentos', 'estabelecimentos.id', '=', 'atividades.estemp_id')
                ->select('atividades.id', 'atividades.descricao', 'estabelecimentos.codigo','atividades.limite')
                //->where('atividade_user.user_id', $user_id)
                ->where('atividades.status','<', 3)
                ->where('atividades.estemp_type','estab')
                ->get();

            foreach($atividades_estab as $atividade) {

                $events[] = \Calendar::event(
                    str_replace('Entrega ','',$atividade->descricao).' ('.$atividade->codigo.')', //event title
                    true, //full day event?
                    $atividade->limite, //start time (you can also use Carbon instead of DateTime)
                    $atividade->limite, //end time (you can also use Carbon instead of DateTime)
                    $atividade->id, //optionally, you can specify an event ID
                    ['url' => url('/upload/'.$atividade->id.'/entrega'),'color'=> 'red', 'textColor'=>'white']
                );
            }
            //MATRIZ
            $atividades_emp = DB::table('atividades')
                ->join('empresas', 'empresas.id', '=', 'atividades.estemp_id')
                ->select('atividades.id', 'atividades.descricao', 'empresas.codigo','atividades.limite')
                //->where('atividade_user.user_id', $user_id)
                ->where('atividades.status','<', 3)
                ->where('atividades.estemp_type','emp')
                ->get();

            foreach($atividades_emp as $atividade) {

                $events[] = \Calendar::event(
                    str_replace('Entrega ','',$atividade->descricao).' ('.$atividade->codigo.')', //event title
                    true, //full day event?
                    $atividade->limite, //start time (you can also use Carbon instead of DateTime)
                    $atividade->limite, //end time (you can also use Carbon instead of DateTime)
                    $atividade->id, //optionally, you can specify an event ID
                    ['url' => url('/upload/'.$atividade->id.'/entrega'),'color'=> 'red', 'textColor'=>'white']
                );
            }
        } /*else {
            $dt_entrega_next = $this->_verificaProximasEntregas('032016');
            //$count=1;
            foreach($dt_entrega_next as $dt_entrega) {

                $events[] = \Calendar::event(
                    $dt_entrega['desc'], //event title
                    true, //full day event?
                    $dt_entrega['data']->toDateTimeString(), //start time (you can also use Carbon instead of DateTime)
                    null, //end time (you can also use Carbon instead of DateTime),
                    null,
                    ['url'=>'regras/'.$dt_entrega['regra_id'],'color'=> 'gray', 'textColor'=>'white']
                );
            }
        }*/



        //Carregando os feriados estaduais

        foreach ($feriados_estaduais as $val) {

            $feriados_estaduais_uf = explode(';', $val->datas);

            foreach ($feriados_estaduais_uf as $el) {
                $key = $val->uf;
                $fer_exploded = explode('-',$el);
                $day = $fer_exploded[0];
                $month = $fer_exploded[1];

                $events[] = \Calendar::event(
                    "FERIADO ESTAD. em $key", //event title
                    true, //full day event?
                    date('Y')."-{$month}-{$day}T0800", //start time (you can also use Carbon instead of DateTime)
                    date('Y')."-{$month}-{$day}T0800", //end time (you can also use Carbon instead of DateTime)
                    null,
                    ['url' => url('/feriados'),'textColor'=>'white']
                );
            }

        }

        //Carregando os feriados nacionais

        foreach ($feriados as $key=>$feriado) {
            //Add feriado to events
            $fer_exploded = explode('-',$feriado);
            $day = $fer_exploded[0];
            $month = $fer_exploded[1];

            $events[] = \Calendar::event(
                "FERIADO - $key", //event title
                true, //full day event?
                date('Y')."-{$month}-{$day}T0800", //start time (you can also use Carbon instead of DateTime)
                date('Y')."-{$month}-{$day}T0800", //end time (you can also use Carbon instead of DateTime)
                null,
                ['url' => url('/feriados'),'textColor'=>'white']
            );
        }

        //Geração do calendario

        $calendar = \Calendar::addEvents($events) //add an array with addEvents
        ->setOptions([ //set fullcalendar options
                'lang' => 'pt',
                'firstDay' => 1,
                'aspectRatio' => 2.3,
                'header' => [ 'left' => 'prev,next', 'center'=>'title'] //, 'right' => 'month,agendaWeek'
            ])
        ->setCallbacks([ //set fullcalendar callback options (will not be JSON encoded)
            'viewRender' => 'function() { }'
        ]);

        return view('pages.calendar', compact('calendar'));
    }

    public function showFeriados()
    {
        $feriados = $this->eService->getFeriadosNacionais();
        $feriados_estaduais = $this->eService->getFeriadosEstaduais();

        return view('feriados.index')->with('feriados',$feriados)->with('estaduais',$feriados_estaduais);
    }

    private function _verificaProximasEntregas($periodo_apuracao)
    {
        $retval = array();
        $regras = Regra::all();

        foreach ($regras as $regra) {

            // Regras Especiais têm mais de um resultado
            if (substr($regra->regra_entrega, 0, strlen('RE')) === 'RE') {

                $retval_array = $this->eService->calculaProximaDataRegrasEspeciais($regra->regra_entrega,null,$periodo_apuracao); //var_dump($retval_array);

                foreach ($retval_array as $el) {

                    $val = array('regra_id'=>$regra->id,'data'=>$el['data'],'desc'=>$el['desc']);
                    $retval[]=$val;

                }

            } else {  // Regra standard

                $ref = $regra->ref;
                if ($municipio = Municipio::find($regra->ref)) {
                    $ref = $municipio->nome.' ( '.$municipio->uf.' )';
                }
                $desc = $regra->tributo->nome.' '.$ref;

                $data = $this->eService->calculaProximaData($regra->regra_entrega,$periodo_apuracao);
                $val = array('regra_id'=>$regra->id,'data'=>$data,'desc'=>$desc);
                $retval[]=$val;

            }

        }

        return $retval;
    }

}
