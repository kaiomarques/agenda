<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\Estabelecimento;
use App\Models\Municipio;
use App\Models\Atividade;
use App\Models\Empresa;

use App\Services\ScriptService;

class UploadAtividadesScriptsController extends Controller
{
	public $s_emp = null;
	
	private $drive = null;
	
	private $scriptService = null;
	
	public function __construct()
	{
		
		
		if (!Auth::guest() && !empty(session()->get('seid')))
			$this->s_emp = Empresa::findOrFail(session('seid'));
	}
	
	public function spedScript() {
		
		$this->scriptService = new ScriptService;
		
		$this->scriptService->spedSort();
		
	}
	
	public function cleanUpUploadedFolder() {
		$this->scriptService = new ScriptService;
		
		$this->scriptService->cleanUpFolderUploaded();		
	}
}
?>