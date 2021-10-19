<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use App\Services\IntegracaoContaCorrenteSefazService;

use App\Models\Empresa;

class IntegracaoContaCorrenteSefazController extends Controller {

	/** @var IntegracaoContaCorrenteSefazService $eService */
	protected $eService;

	/** @var Empresa $s_emp */
	public $s_emp = null;

	public function __construct(IntegracaoContaCorrenteSefazService $service) {

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

	public function processar(Request $request) {

		$input  = $request->all();
		$params = [
			'id_empresa' => $input['id_empresa'],
			'uf'         => $input['uf']
		];

		Artisan::call('integracaocontacorrentesefaz:all', $params);

		$error = Artisan::output();

		if ($error) {
			echo $error;
		}

		die('');
	}

	public function index(Request $request) {
		return view('integracaocontacorrentesefaz.index')->with('id_empresa', $this->s_emp->id)->with('estados', IntegracaoContaCorrenteSefazController::getEstados());
	}

	public static function getEstados() {
		return ['SP' => 'SP'];
	}

}
