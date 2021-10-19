<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Atividade;
use App\Http\Requests;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class DownloadsController extends Controller
{
    public function download($id) {

        $atividade = Atividade::findOrFail($id);
        if ($atividade->arquivo_entrega == '-') {
            Session::flash('message', "$atividade->descricao sem documentação. Abrir os detalhes para ler os comentários desta entrega.");
            return Redirect::back();

        } else {

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
            $headers = array(
                'Content-Type' => 'application/pdf',
            );

            $file_path = public_path('uploads/'.$destinationPath);
            return response()->download($file_path);
        }
    }

    public function download_comprovante($id)
    {
        $atividade = Atividade::findOrFail($id);
        if ($atividade->arquivo_comprovante == '-') {
            Session::flash('message', "$atividade->descricao sem comprovante.");
            return Redirect::back();

        } else {

            
            $destinationPath = $id.'/'.$atividade->arquivo_comprovante; // upload path
            $headers = array(
                'Content-Type' => 'application/pdf',
            );

            $file_path = public_path('uploads/'.$destinationPath);

            return response()->download($file_path);
        }
    }
}
