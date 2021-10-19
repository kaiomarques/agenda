<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\FeriadoEstadual;
use App\Models\FeriadoMunicipal;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;


class FeriadosController extends Controller
{
    public function index()
    {
        $feriados = $this->_verificarFeriadosNacionais();
        $feriados_estaduais = $this->_getFeriadosEstaduais();

        return view('feriados.index')->with('feriados',$feriados)->with('estaduais',$feriados_estaduais);
    }


    private function _verificarDiaDaSemana($data){
        $diaDaSemana = date("D",strtotime($data));
        if($diaDaSemana=='Sat'){
            return 2;
        }elseif($diaDaSemana=='Sun' ){
            return 1;
        }else{
            return 0;
        }
    }

    private function _verificarFeriadosNacionais()
    {
        $formatoDataDeComparacao    =  "d-m"; // Dia / Mês
        //$diaDeComparacao            = date("d-m",strtotime($data));
        //$ano = intval(date('Y',strtotime($data)));
        $ano = date('Y');

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
            '2ºferia Carnaval'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 48, $ano_pascoa)),//2ºferia Carnaval
            '3ºferia Carnaval'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 47, $ano_pascoa)),//3ºferia Carnaval
            '6ºfeira Santa'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 2, $ano_pascoa)),//6ºfeira Santa
            'Pascoa'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa, $ano_pascoa)),//Pascoa
            'Corpus Christ'=>date($formatoDataDeComparacao ,mktime(0, 0, 0, $mes_pascoa, $dia_pascoa + 60, $ano_pascoa)),//Corpus Christ
        );
/*
        if(in_array($diaDeComparacao,$feriados)){
            ++$quantidadeDeFeriados;
            $dataMaisUmDia  = date("Y-m-d",strtotime("+ 1 day" ,strtotime($data)));
            return  $this->_verificarFeriadosNacionais($dataMaisUmDia,$quantidadeDeFeriados);
        }
        return $quantidadeDeFeriados;
*/
        return $feriados;
    }

    private function _getFeriadosEstaduais()
    {
        $retval = FeriadoEstadual::all();

        //$feriados_estaduais = explode(';',$retval->first()->datas);

        return $retval;

    }

}
