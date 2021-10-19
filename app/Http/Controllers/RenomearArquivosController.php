<?php

namespace App\Http\Controllers;

//use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use App\Services\RenameFilesService;
use App\Models\Empresa;
use App\Utils\Progress;
use App\Utils\Singleton;

class RenomearArquivosController extends Controller {

    protected $eService;
    protected static $progress = null;

    function __construct(RenameFilesService $service) {

    	if (!Auth::guest() && !empty(session()->get('seid'))) {
    		$this->s_emp = Empresa::findOrFail(session('seid'));
    	}

//      $this->middleware('auth');
        $this->eService = $service;
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {

    	$Empresa        = $request->session()->get('Empresa');
    	$id_empresa     = $request->session()->get('seid');
    	$tipos_tributos = self::getTiposTributos();

    	return view('renomeararquivos.index', compact('id_empresa', 'tipos_tributos'));
    }

    public function next(Request $request) {

    	$input = $request->all();

    	Artisan::call('renamefiles:all', ['id_empresa' => $input['id_empresa'], 'tipo_tributo' => $input['tipo_tributo']]);

    	return json_encode(['progress' => Singleton::getProgress()->getAll(), 'message' => Artisan::output()]);
    }

    /* ========================================================================================= */

    /** @return array */
    public static function getTiposTributos() {
    	return ['icms' => 'ICMS', 'icmsst' => 'ICMSST'];
    }

}
