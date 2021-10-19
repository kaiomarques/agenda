<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Empresa;
use App\Models\Justificativa;
use App\Models\Tributo;
use App\Models\User;
use App\Utils\Variavel;

use Illuminate\Support\Facades\Auth;

class JustificativaController extends Controller {

	/** @property Empresa $s_emp */
	public $s_emp = null;

	public function __construct() {

		if (!session()->get('seid')) {
			Session::flash('warning', 'Nenhuma empresa selecionada, favor selecionar uma!');
			return redirect()->route('home', ['selecionar_empresa' => 1])->send();
		}

		$this->middleware('auth');

		if (!Auth::guest() && $this->s_emp == null && !empty(session()->get('seid'))) {
			$this->s_emp = Empresa::findOrFail(session()->get('seid'));
		}

	}

	/** @return Justificativa */
	public function getModel() {
		return new Justificativa();
	}

	/**
	 * Display a listing of the resource.
	 * @return \Illuminate\Http\Response
	 */
	public function index() {
		$user = User::findOrFail(Auth::user()->id);
		return view('justificativa.index')->with('table', $this->getModel()->retrieve($this->s_emp->id));
	}

	/** @return \Illuminate\Http\Response */
	public function create() {
		$tributos = Tributo::selectRaw("nome, id")->pluck('nome', 'id');
		return view('justificativa.adicionar')->with('tributos', $tributos);
	}

	/**
	 * Store a newly created resource in storage.
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request) { // qdo clica em adicionar, cai aqui

		$situation = 'status';
		$message   = 'Registro inserido com sucesso';
		$tributos  = Tributo::selectRaw("nome, id")->pluck('nome', 'id');
		$var       = array();
		$input     = $request->all();
		$error     = [];

		$id_tributo       = Variavel::getId($input['id_tributo'], 'tributos');
		$periodo_apuracao = Variavel::getPeriodo($input['periodo_apuracao']);
		$justificativa    = Variavel::getString($input['justificativa'], 60, 3);

		if ($id_tributo->isFalse()) {
			array_push($error, $id_tributo->getError());
		}

		if ($periodo_apuracao->isFalse()) {
			array_push($error, $periodo_apuracao->getError());
		}
		else {

			$var['id_empresa']       = $this->s_emp->id;
			$var['id_tributo']       = $id_tributo->toInt();
			$var['periodo_apuracao'] = $periodo_apuracao->clean();
			$var['justificativa']    = $input['justificativa'];

			if ($this->getModel()->isInLimit($var['id_empresa'], $var['id_tributo'], $var['periodo_apuracao']) == false) {
				array_push($error, sprintf('Já está cadastrado mais de %s justificativas para esta empresa (%s), tributo (%s) e periodo (%s).', Justificativa::$cadLimit, $var['id_empresa'], $var['id_tributo'], "{$periodo_apuracao}"));
			}

		}

		if ($justificativa->isFalse()) {
			array_push($error, 'Justificativa deve conter entre 3 e 60 caracteres.');
		}

		if (!empty($error)) {
			return redirect()->back()->with('alert', implode('<br />', $error));
		}

		Justificativa::create($var);

		return view('justificativa.adicionar')->with('tributos', $tributos)->with('message', $message)->with('status', $situation);
	}
	/**
	 * @param int $id
	 * @return \Illuminate\Http\Response */
	public function edit($id) {

		$model    = Justificativa::findOrFail($id);
		$tributos = Tributo::selectRaw("nome, id")->pluck('nome', 'id');

		return view('justificativa.editar')->with('tributos', $tributos)->with('model', $model);
	}

	public function update(Request $request) { // qdo clica em salvar, cai aqui

 		$input     = $request->all();
 		$id        = Variavel::getId($input['id']);
 		$model     = Justificativa::findOrFail($id->toInt());
 		$tributos  = Tributo::selectRaw("nome, id")->pluck('nome', 'id');
		$situation = 'status';
		$message   = "Justificativa ({$id}) atualizada com sucesso";
		$error     = [];

		$id_tributo       = Variavel::getId($input['id_tributo'], 'tributos');
		$periodo_apuracao = Variavel::getPeriodo($input['periodo_apuracao']);
		$justificativa    = Variavel::getString($input['justificativa'], 60, 3);

		if ($id_tributo->isFalse()) {
			array_push($error, $id_tributo->getError());
		}

		if ($periodo_apuracao->isFalse()) {
			array_push($error, $periodo_apuracao->getError());
		}
		else {

			$input['periodo_apuracao'] = $periodo_apuracao->clean();

			if (($model->id_tributo != $input['id_tributo']) || ($model->periodo_apuracao != $input['periodo_apuracao'])) {

				if ($this->getModel()->isInLimit($model->id_empresa, $id_tributo->toInt(), $input['periodo_apuracao']) == false) {
					array_push($error, sprintf('Já está cadastrado mais de %s justificativas para esta empresa (%s), tributo (%s) e periodo (%s).', Justificativa::$cadLimit, $model->id_empresa, $id_tributo->toInt(), "{$periodo_apuracao}"));
				}

			}

		}

		if ($justificativa->isFalse()) {
			array_push($error, 'Justificativa deve conter entre 3 e 60 caracteres.');
		}

		if (!empty($error)) {
			return redirect()->back()->with('alert', implode('<br />', $error));
		}

 		$model->fill($input)->save();

 		return view('justificativa.editar')->with('model', $model)->with('tributos', $tributos)->with('message', $message)->with('status', $situation);
	}

	/**
	 * Remove the specified resource from storage.
	 * @param  int  $id
	 * @return \Illuminate\Http\Response */
	public function destroy($id) {

		if (isset($id) && is_numeric($id) && $id >= 0) {

			$Justificativa = Justificativa::where('id', $id)->get();

			if ($Justificativa->count() > 0) {
				Justificativa::where('id', $id)->delete();
				return redirect()->back()->with('status', 'Justificativa excluida com sucesso.');
			}
			else {
				return redirect()->back()->with('error', 'Ocorreu um erro. Cadastro não existe. ');
			}

		}
		else {
			return redirect()->back()->with('error', 'Ocorreu um erro ao tentar excluir a Justificativa. Não recebemos a sua identificação');
		}

		return redirect()->back()->with('error', 'Erro não especificado.');
	}

}
