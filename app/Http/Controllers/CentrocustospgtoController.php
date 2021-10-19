<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\User;
use App\Models\Centrocustospagto;
use App\Http\Requests;
use App\Services\EntregaService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Yajra\Datatables\Datatables;
use Carbon\Carbon;



class CentrocustospgtoController extends Controller
{
	public $msg;
	public $status = 0;

	function __construct(EntregaService $service)
    {
        $this->eService = $service;
        if (!Auth::guest() && !empty(session()->get('seid')))
        $this->s_emp = Empresa::findOrFail(session('seid'));
    }

    public function create(Request $request)
    {	
    	$response = array('codigo'=>'', 'centrocusto'=>'', 'descricao'=>'');
     	$input = $request->all();
     	if (!empty($input)) {
     		if (!empty($input['codigo'])) {
     			
     			$estabelecimento = Estabelecimento::where('codigo', '=', $input['codigo'])->where('empresa_id', $this->s_emp->id)->where('ativo', '=', 1)->first();
     			
     			if (empty($estabelecimento)) {
     				$this->msg = 'Este código de estabelecimento não existe para a sua empresa';
	 				$this->status = 0;

    				return view('centrocustos.createcc')->with('response', $response)->with('msg', $this->msg)->with('status', $this->status);
     			}

     			$centrocustos = Centrocustospagto::where('Estemp_id', '=', $estabelecimento->id)->where('Empresa_id', $this->s_emp->id)->first();

				$response['codigo'] = $input['codigo'];
				if (!empty($centrocustos)) {
	 				$response['descricao'] = $centrocustos->descricao;
	 				$response['centrocusto'] = $centrocustos->centrocusto;
	 				$this->msg = 'Registro encontrado com sucesso';
	 				$this->status = 1;
				}

     			if ($input['create']) {
     				if (!empty($centrocustos)) {
     					DB::table('centrocustospagto')
			            ->where('Empresa_id', $this->s_emp->id)
			            ->where('Estemp_id', $estabelecimento->id)
			            ->update(['centrocusto' => $input['centrocusto'], 'descricao' => $input['descricao']]);	
     					$this->msg = 'Registro atualizado com sucesso';
     				}

     				if (empty($centrocustos)) {
     					$new = array('Estemp_id'=>$estabelecimento->id, 'centrocusto'=>$input['centrocusto'],  'descricao'=>$input['descricao'],  'Empresa_id'=>$this->s_emp->id);
     					Centrocustospagto::Create($new);
     					$this->msg = 'Registro criado com sucesso';
     				}

     				$response['descricao'] = $input['descricao'];
	 				$response['centrocusto'] = $input['centrocusto'];
     				$this->status = 1;
     			}
     		} else {
	     		$this->msg = 'O código é obrigatório';
	     		$this->status = 0;
     		}
     	}

    return view('centrocustos.createcc')->with('response', $response)->with('msg', $this->msg)->with('status', $this->status);
    }

    public function createsap(Request $request)
    {	
    	$response = array('uf'=>'', 'municipios'=>array());
     	$input = $request->all();
     	if (!empty($input)) {
     		if (!empty($input['uf'])) {
				$response['uf'] = $input['uf'];
     			$municipios = Municipio::where('uf', '=', $input['uf'])->get();

     			if ($input['create']) {
					$formValues = $this->formatValues($input);

	     			if (empty($formValues)) {
	     				$this->msg = 'É necessário ter ao menos um município com o código SAP preenchido para realizar atualização';
		 				$this->status = 0;
	     			}

					if (!empty($formValues)) {
						foreach ($formValues as $key => $value) {
		 					DB::table('municipios')
				            ->where('codigo', $value['codigo'])
				            ->update(['codigo_sap' => $value['codigosap']]);

		 					$this->msg = 'Registro(s) atualizado(s) com sucesso';
		     				$this->status = 1;					
						}
					}
     			}
     		} else {
	     		$this->msg = 'A UF é obrigatória';
	     		$this->status = 0;
     		}
     	}

    if (!empty($response['uf'])) {
    	$municipios = Municipio::where('uf', '=', $response['uf'])->get();
    	$response['municipios'] = $municipios;
    }

    return view('centrocustos.createsap')->with('response', $response)->with('msg', $this->msg)->with('status', $this->status);
    }

    private function formatValues($input)
    {
    	$retorno = array();
    	if (!empty($input['codigosap'])) {
    		foreach ($input['codigosap'] as $key => $codigo) {
    			if (!empty($codigo)) {
    				$retorno[$key]['codigosap'] = $codigo;
    				$retorno[$key]['codigo'] = $key;
    			}
    		}
    	}
    	return $retorno;
    }
}
