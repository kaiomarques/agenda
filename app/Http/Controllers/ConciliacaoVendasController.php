<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Services\ConciliacaoVendasService;

use App\Models\Empresa;

class ConciliacaoVendasController extends Controller {

	/** @var ConciliacaoVendasService $eService */
	protected $eService;

	/** @var Empresa $s_emp */
	public $s_emp = null;

	public function __construct(ConciliacaoVendasService $service) {

		if (!session()->get('seid')) {
			Session::flash('warning', 'Nenhuma empresa selecionada, favor selecionar uma!');
			return redirect()->route('home', ['selecionar_empresa' => 1])->send();
		}

		$this->middleware('auth');

		if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
			$this->s_emp = Empresa::findOrFail(session()->get('seid'));
		}

		$this->eService = $service;
	}

	public function validar(Request $request) {

		$input  = $request->all();
		$params = [
			'id_empresa'       => $input['id_empresa'],
			'periodo_apuracao' => $input['periodo_apuracao'],
			'linhaInicioJDE'   => $input['linhaInicioJDE'],
			'linhaInicioLINX'  => $input['linhaInicioLINX']
		];

		Artisan::call('conciliacaovendas:all', $params);
		echo Artisan::output();
		die('');
	}

	public function index(Request $request) {
		return view('conciliacaovendas.index')->with('id_empresa', $this->s_emp->id);
	}

}
