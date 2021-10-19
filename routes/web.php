<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Atividade;
use App\Models\Tributo;
use App\Models\Municipio;
use App\Models\AnalistaDisponibilidade;
use Carbon\Carbon;
use App\Http\Controllers\Auth\LoginController; 
use App\Http\Controllers;
use Illuminate\Routing\Router;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/
//

//var_dump("Agora aqui 1");

//Everyone
Route::group(['middleware' => ['web']], function () {
	// Authentication Routes...

	Auth::routes();	

	Route::get('/', [App\Http\Controllers\PagesController::class, 'home']);
	Route::get('/home', [App\Http\Controllers\PagesController::class, 'home']);
	Route::get('/aprovacao', [App\Http\Controllers\PagesController::class, 'aprovacao']);
	Route::get('/graficos', [App\Http\Controllers\PagesController::class, 'graficos']);
	Route::get('/desempenho_entregas', [App\Http\Controllers\PagesController::class, 'desempenho_entregas']);
	Route::post('atualizarsenha', [App\Http\Controllers\UsuariosController::class, 'atualizarsenha']);
	Route::get('/grafico1', [App\Http\Controllers\PagesController::class, 'imgGrafico1']);
	Route::get('/grafico2', [App\Http\Controllers\PagesController::class, 'grafico2']);
});

// Just the Owner, Admin, Manager, Supervisor and the Analyst
Route::group(['middleware' => ['web','auth','role:supervisor|manager|admin|owner|gbravo|gcliente|analyst']], function () {
	// BEGIN Pagamento ISS #task 415
	Route::get('guia_iss/lerGuiaISS', [App\Http\Controllers\GuiaissController::class,'lerGuiaISS'])->name('guiaiss.lerGuiaISS');
	Route::get('guia_iss/verificaCNPJ/{cnpj}', [App\Http\Controllers\GuiaissController::class,'verificaCNPJ'])->name('guiaiss.verificaCNPJ');
	Route::get('guia_iss/verificaCCM/{ccm}', [App\Http\Controllers\GuiaissController::class, 'verificaCCM'])->name('guiaiss.verificaCCM');
	Route::get('guia_iss/verificaCodigo/{codigo}', [App\Http\Controllers\GuiaissController::class,'verificaCodigo'])->name('guiaiss.verificaCodigo');
	Route::post('guia_iss/processaGuiaISS', [App\Http\Controllers\GuiaissController::class, 'processaGuiaISS'])->name('guiaiss.processaGuiaISS');
	Route::get('guia_iss/gerarLotePagamento', [App\Http\Controllers\GuiaissController::class,'gerarLotePagamento'])->name('guiaiss.gerarLotePagamento');
	Route::post('guia_iss/gerarLoteArquivoCSV', [App\Http\Controllers\GuiaissController::class, 'gerarLoteArquivoCSV'])->name('guiaiss.gerarLoteArquivoCSV');
	Route::get('guia_iss/conciliacaoMemoriaGuias', [App\Http\Controllers\GuiaissController::class, 'conciliacaoMemoriaGuias'])->name('guiaiss.conciliacaoMemoriaGuias');
	// END Pagamento ISS
	Route::post('home', array('as'=>'home', 'uses'=> [App\Http\Controllers\PagesController::class, 'home' ] ));
	Route::post('aprovacao', array('as'=>'aprovacao', 'uses'=>'PagesController@aprovacao'));
	Route::post('dashboard_analista', array('as'=>'dashboard_analista', 'uses'=>'PagesController@dashboard_analista'));
	Route::get('dashboard_analista', array('as'=>'dashboard_analista', 'uses'=>'PagesController@dashboard_analista'));

	Route::get('/download/{file}', 'DownloadsController@download');
	Route::get('/download_comprovante/{file}', 'DownloadsController@download_comprovante');

	Route::resource('entregas', 'EntregasController');
	Route::get('entrega/data', array('as'=>'entregas.data', 'uses'=>'EntregasController@anyData'));

	Route::get('conciliacaovendas.index', array('as'=>'conciliacaovendas.index', 'uses'=>'ConciliacaoVendasController@index'));
	Route::post('conciliacaovendas.validar', array('as'=>'conciliacaovendas.validar', 'uses'=>'ConciliacaoVendasController@validar'));

	Route::resource('arquivos', 'ArquivosController');
	Route::get('arquivo/data', array('as'=>'arquivos.data', 'uses'=>'ArquivosController@anyData'));
	Route::get('arquivo/downloads', array('as'=>'arquivos.downloads', 'uses'=>'ArquivosController@Downloads'));
	Route::post('arquivo/downloads', array('as'=>'arquivos.downloads', 'uses'=>'ArquivosController@Downloads'));

	Route::post('atividade/storeComentario', array('as'=>'atividades.storeComentario', 'uses'=>'AtividadesController@storeComentario'));

	Route::get('upload/{user}/entrega', array('as'=>'upload.entrega', 'uses'=>'UploadsController@entrega'));
	Route::post('upload/sendUpload', 'UploadsController@upload');

	Route::get('uploadCron/{user}/entrega', array('as'=>'upload.entregaCron', 'uses'=>'UploadsController@entregaCronograma'));
	Route::get('uploadCron/{data_atividade}/entrega/data', array('as'=>'upload.entregaCron', 'uses'=>'UploadsController@entregaCronogramaData'));
	Route::post('upload/sendUploadCron', 'UploadsController@uploadCron');

	Route::post('about', array('as'=>'about', 'uses'=>'PagesController@about'));
	Route::get('/about', [
		'as' => 'about',
		'uses' => 'PagesController@about'
	]);

	Route::get('/dropdown-municipios', function(Request $request){

		$input = $request->input('option');
		$municipios = Municipio::where('uf',$input);

		return \Response::make($municipios->get(['codigo','nome']));
	});
});

//Everyone registered


Route::group(['middleware' => ['web','auth','role:user|analyst|supervisor|manager|admin|owner']], function () {

	Route::get('/find-activities-detail', function(Request $request){

		Carbon::setTestNow();  //reset time
		$today = Carbon::today()->toDateString();

		$input_tributo = $request->input('option_tributo');  //Tributo
		$input_periodo = $request->input('option_periodo');  //Periodo Apuração
		$input_data = $request->input('option_data');        //Data limite entrega
		$input_serie_id = $request->input('option_serie_id');//Serie ID
		//$input_serie_nome = $request->input('option_serie_nome');

		$tributo = Tributo::where('nome',$input_tributo)->first();
		$tributo_id = $tributo->id;
		$data_limite = substr($input_periodo,-4,4).'-'.substr($input_data,-2,2).'-'.substr($input_data,0,2);

		$atividades = Atividade::select('*')
			->with('estemp')
			->whereHas('regra' , function ($query) use ($tributo_id) {
				$query->where('tributo_id', $tributo_id);
			})
			->where('periodo_apuracao',str_replace('-','',$input_periodo))
			->where('limite','like',"$data_limite%");

		switch ($input_serie_id) {
			case 0:
				$atividades->where('status',1)->where('limite','>',$today);
				break;
			case 1:
				$atividades->where('status',1)->where('limite','<=',$today);
				break;
			case 2:
				$atividades->where('status',2)->where('limite','>',$today);
				break;
			case 3:
				$atividades->where('status',2)->where('limite','<=',$today);
				break;
			case 4:
				$atividades->where('status',3)->whereRaw('data_aprovacao <= limite');
				break;
			case 5:
				$atividades->where('status',3)->whereRaw('data_aprovacao > limite');
				break;
			default:
				break;
		}
		return \Response::make($atividades->get(['id','descricao','estemp.codigo']));
	});

	Route::get('/dropdown-regras', function(Request $request){

		$input = $request->input('option');
		$tributo = Tributo::find($input);
		$regras = $tributo->regras();

		return \Response::make($regras->get(['id','nome_especifico','ref','regra_entrega']));
	});

	Route::get('/calendar', [
		'as' => 'calendar',
		'uses' => 'PagesController@calendar'
	]);

	Route::resource('calendarios', 'CalendariosController');
	Route::get('/calendario', array('as'=>'calendario', 'uses'=>'CalendariosController@index'));
	Route::get('/monitorcnd_criar', array('as'=>'monitorcnd.create', 'uses'=>'MonitorCndController@create'));
	Route::get('/monitorcnd_listar', array('as'=>'monitorcnd.search', 'uses'=>'MonitorCndController@search'));
	Route::post('monitorcnd/store', array('as'=>'monitorcnd.store', 'uses'=>'MonitorCndController@store'));
	Route::get('monitorcnd/data', array('as'=>'monitorcnd.data', 'uses'=>'MonitorCndController@anyData'));
	Route::get('monitorcnd/edit/{id}', array('as'=>'monitorcnd.edit', 'uses'=>'MonitorCndController@edit'));
	Route::post('monitorcnd/edit/{id}', array('as'=>'monitorcnd.edit', 'uses'=>'MonitorCndController@update'));
	Route::get('monitorcnd/delete/{id}', array('as'=>'monitorcnd.delete', 'uses'=>'MonitorCndController@destroy'));
	Route::get('monitorcnd/graficos/', array('as'=>'monitorcnd.graficos', 'uses'=>'MonitorCndController@dashboard'));
	Route::get('monitorcnd/dashboardRLT', array('as'=>'monitorcnd.dashboardRLT', 'uses'=>'MonitorCndController@dashboardRLT'));
	Route::get('monitorcnd/dashboardDData/', array('as'=>'monitorcnd.dashboardData', 'uses'=>'MonitorCndController@dashboardAnyData'));

	Route::get('monitorcnd/job/', array('as'=>'monitorcnd.job', 'uses'=>'MonitorCndController@job'));

	Route::get('monitorcnd/getCNPJ/{filial_id}', array('as'=>'monitorcnd.cnpj', 'uses'=>'MonitorCndController@getCNPJ'));

	Route::get('/feriados', array('as'=>'feriados', 'uses'=>'CalendariosController@showFeriados'));

});

// Just the Owner, Admin, Manager, Supervisor and the Analyst
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|manager|admin|owner|gbravo|gcliente|msaf']], function () {

	Route::post('home', array('as'=>'home', 'uses'=> [App\Http\Controllers\PagesController::class,'home'] ));
	Route::post('graficos', array('as'=>'graficos', 'uses'=>'PagesController@graficos'));
	Route::post('desempenho_entregas', array('as'=>'desempenho_entregas', 'uses'=>'PagesController@desempenho_entregas'));
	Route::post('dashboard_analista', array('as'=>'dashboard_analista', 'uses'=>'PagesController@dashboard_analista'));
	Route::get('dashboard_analista', array('as'=>'dashboard_analista', 'uses'=>'PagesController@dashboard_analista'));

	Route::get('forcelogout', array('as'=>'forcelogout', 'uses'=>'PagesController@forcelogout'));
	Route::get('consulta_conta_corrente', array('as' => 'consulta_conta_corrente', 'uses' => 'PagesController@consulta_conta_corrente'));

	Route::get('consulta_conta_corrente_rlt_1', array('as' => 'consulta_conta_corrente_rlt_1', 'uses' => 'PagesController@relatorio_1'));

	Route::get('sped_fiscal', array('as' => 'sped_fiscal', 'uses' => 'SpedFiscalController@index'));
	Route::get('sped_fiscal/transmitir', array('as' => 'sped_fiscal.transmitirlistar', 'uses' => 'SpedFiscalController@TransmissionIndex'));
	Route::post('sped_fiscal/transmitir', array('as' => 'spedfiscal.transmitir', 'uses' => 'SpedFiscalController@transmitir'));

	Route::get('Justificativa/adicionar', array('as'=>'justificativa.adicionar', 'uses'=>'JustificativaController@create'));
	Route::post('Justificativa/store', array('as'=>'justificativa.store', 'uses'=>'JustificativaController@store'));
	Route::post('Justificativa/save', array('as'=>'justificativa.save', 'uses'=>'JustificativaController@update'));
	Route::get('Justificativa/edit/{id}', array('as'=>'justificativa.edit', 'uses'=>'JustificativaController@edit'));
	Route::get('Justificativa/', array('as'=>'justificativa.index', 'uses'=>'JustificativaController@index'));
	Route::get('Justificativa/excluir/{id}', array('as'=>'justificativa.destroy', 'uses'=>'JustificativaController@destroy'));

	// renomear_arquivos

	Route::get('renomear_arquivos', array('as' => 'renomear_arquivos', 'uses' => 'RenomearArquivosController@index'));
	Route::post('renomear_arquivos', array('as' => 'renomear_arquivos', 'uses' => 'RenomearArquivosController@next'));
//	Route::post('renomear_arquivos/progresso', array('as' => 'renomear_arquivos.progresso', 'uses' => 'RenomearArquivosController@progresso'));

	// renomear_arquivos

	Route::get('sped_fiscal/download/{id}', array('as' => 'download_sped', 'uses' => 'SpedFiscalController@DownloadPath'));

	Route::get('/download/{file}', 'DownloadsController@download');

	Route::resource('entregas', 'EntregasController');
	Route::get('entrega/data', array('as'=>'entregas.data', 'uses'=>'EntregasController@anyData'));

#created André get for post 29/01/2019
	Route::get('entrega/downloadCSV', array('as'=>'entregas.csv', 'uses'=>'EntregasController@DownloadCSV'));


	Route::resource('arquivos', 'ArquivosController');
	Route::get('arquivo/data', array('as'=>'arquivos.data', 'uses'=>'ArquivosController@anyData'));

	Route::post('atividade/storeComentario', array('as'=>'atividades.storeComentario', 'uses'=>'AtividadesController@storeComentario'));
	Route::post('arquivos/upload', 'ArquivosController@upload');

	Route::get('upload/{user}/entrega', array('as'=>'upload.entrega', 'uses'=>'UploadsController@entrega'));
	Route::get('entrega/{user}/comentario', array('as'=>'entrega.comentarios', 'uses'=>'UploadsController@comentarios'));
	Route::post('upload/sendUpload', 'UploadsController@upload');

	Route::get('uploadCron/{user}/entrega', array('as'=>'upload.entregaCron', 'uses'=>'UploadsController@entregaCronograma'));
	Route::get('uploadCron/{data_atividade}/entrega/data', array('as'=>'upload.entregaCron', 'uses'=>'UploadsController@entregaCronogramaData'));
	Route::post('upload/sendUploadCron', 'UploadsController@uploadCron');

});

Route::group(['middleware' => ['web']], function () {
	Route::get('mensageriaprocadms/jobprocadms', array('as'=>'mensageriaprocadms.Job', 'uses'=>'MensageriaprocadmsController@Job'));
	Route::get('regra/job_envio_email', array('as'=>'regraslotes.Job', 'uses'=>'RegrasenviolotesController@Job'));
	Route::get('guiaicms/job', array('as'=>'guiaicms.Job', 'uses'=>'GuiaicmsController@Job'));
	Route::get('atividades/job', array('as'=>'guiaicms.jobAtividades', 'uses'=>'GuiaicmsController@jobAtividades'));
	Route::get('atividadeanalista/job', array('as'=>'atividadeanalista.job', 'uses'=>'AtividadeanalistaController@job'));

	Route::get('spedfiscal/job', array('as'=>'spedfiscal.job', 'uses'=>'SpedFiscalController@job'));
	Route::get('spedfiscal/{file}', array('as'=>'spedfiscal.download', 'uses'=>'SpedFiscalController@ForceZipDownload'));
	Route::get('upload/job', array('as'=>'upload.job', 'uses'=>'MailsController@UploadFiles'));
	Route::get('leitor/job', array('as'=>'leitor.job', 'uses'=>'MailsController@Guiaimcs'));

	Route::get('uploadscripts/spedScript', array('as'=>'UploadAtividadesScripts.spedScript', 'uses'=>'UploadAtividadesScriptsController@spedScript'));
	Route::get('uploadscripts/cleanUpUploadedFolder', array('as'=>'UploadAtividadesScripts.cleanUpUploadedFolder', 'uses'=>'UploadAtividadesScriptsController@cleanUpUploadedFolder'));

});

// Just the Owner, Admin, Manager, MSAF, Supervisor and the Analyst
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|msaf|admin|owner']], function () {
	Route::get('cargas', array('as'=>'cargas', 'uses'=>'CargasController@index'));
	Route::get('cargas/getUser', array('as'=>'cargas.getUser', 'uses'=>'CargasController@getUser'));
	Route::get('cargas_grafico', array('as'=>'cargas_grafico', 'uses'=>'CargasController@grafico'));
	Route::post('cargas', array('as'=>'cargas', 'uses'=>'CargasController@index'));
	Route::post('cargas/reset', array('as'=>'cargas.reset', 'uses'=>'CargasController@resetData'));
	Route::post('cargas/atualizar_entrada', array('as'=>'cargas.atualizar_entrada', 'uses'=>'CargasController@atualizarEntrada'));
	Route::get('cargas/data', array('as'=>'cargas.data', 'uses'=>'CargasController@anyData'));
	Route::get('carga/{state}/{estab}/changeStateEntrada', array('as'=>'cargas.changeStateEntrada', 'uses'=>'CargasController@changeStateEntrada'));
	Route::get('carga/{state}/{estab}/changeStateSaida', array('as'=>'cargas.changeStateSaida', 'uses'=>'CargasController@changeStateSaida'));
});

// Just the Owner, Admin, Manager, MSAF, Supervisor and the Analyst
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|msaf|admin|owner|gbravo']], function () {
	Route::get('integracao', array('as'=>'cargas', 'uses'=>'CargasController@index'));
	Route::get('integracao_grafico', array('as'=>'cargas_grafico', 'uses'=>'CargasController@grafico'));
	Route::post('integracao', array('as'=>'cargas', 'uses'=>'CargasController@index'));
	Route::post('integracao/reset', array('as'=>'cargas.reset', 'uses'=>'CargasController@resetData'));
	Route::get('integracao/data', array('as'=>'cargas.data', 'uses'=>'CargasController@anyData'));
	Route::get('integracao/{state}/{estab}/changeStateEntrada', array('as'=>'cargas.changeStateEntrada', 'uses'=>'CargasController@changeStateEntrada'));
	Route::get('carga/{state}/{estab}/changeStateSaida', array('as'=>'cargas.changeStateSaida', 'uses'=>'CargasController@changeStateSaida'));

});

Route::group(['middleware' => ['web','auth','role:supervisor|admin|owner|analyst']], function () {

	Route::get('Atividade_Analista/adicionar', array('as'=>'atividadesanalista.adicionar', 'uses'=>'AtividadeanalistaController@create'));
	Route::post('Atividade_Analista/store', array('as'=>'atividadesanalista.store', 'uses'=>'AtividadeanalistaController@store'));
	Route::get('Atividade_Analista/store', array('as'=>'atividadesanalista.store', 'uses'=>'AtividadeanalistaController@store'));
	Route::post('Atividade_Analista/edit', array('as'=>'atividadesanalista.edit', 'uses'=>'AtividadeanalistaController@edit'));
	Route::get('Atividade_Analista/', array('as'=>'atividadesanalista.index', 'uses'=>'AtividadeanalistaController@index'));
	Route::get('Atividade_Analista/editRLT', array('as'=>'atividadesanalista.editRLT', 'uses'=>'AtividadeanalistaController@editRLT'));
	Route::post('Atividade_Analista/filial', array('as'=>'atividadesanalistafilial.store', 'uses'=>'AtividadeanalistafilialController@store'));
	Route::get('Atividade_Analista/excluirFilial', array('as'=>'atividadesanalistafilial.excluirFilial', 'uses'=>'AtividadeanalistafilialController@excluirFilial'));
	Route::get('Atividade_Analista/excluir/{id}', array('as'=>'atividadesanalista.destroy', 'uses'=>'AtividadeanalistaController@destroy'));

	Route::get('Tempo_Atividade/adicionar', array('as'=>'tempoatividade.adicionar', 'uses'=>'TempoAtividadeController@create'));
	Route::post('Tempo_Atividade/store', array('as'=>'tempoatividade.store', 'uses'=>'TempoAtividadeController@store'));
	Route::get('Tempo_Atividade/store', array('as'=>'tempoatividade.store', 'uses'=>'TempoAtividadeController@store'));
	Route::post('Tempo_Atividade/edit', array('as'=>'tempoatividade.edit', 'uses'=>'TempoAtividadeController@edit'));
	Route::get('Tempo_Atividade/', array('as'=>'tempoatividade.index', 'uses'=>'TempoAtividadeController@index'));
	Route::get('Tempo_Atividade/editRLT', array('as'=>'tempoatividade.editRLT', 'uses'=>'TempoAtividadeController@editRLT'));
	Route::get('Tempo_Atividade/excluir/{id}', array('as'=>'tempoatividade.destroy', 'uses'=>'TempoAtividadeController@destroy'));

	Route::get('Analista_Disponibilidade/adicionar', array('as'=>'analistadisponibilidade.adicionar', 'uses'=>'AnalistaDisponibilidadeController@create'));
	Route::post('Analista_Disponibilidade/store', array('as'=>'analistadisponibilidade.store', 'uses'=>'AnalistaDisponibilidadeController@store'));
	Route::get('Analista_Disponibilidade/store', array('as'=>'analistadisponibilidade.store', 'uses'=>'AnalistaDisponibilidadeController@store'));
	Route::post('Analista_Disponibilidade/edit', array('as'=>'analistadisponibilidade.edit', 'uses'=>'AnalistaDisponibilidadeController@edit'));
	Route::get('Analista_Disponibilidade/', array('as'=>'analistadisponibilidade.index', 'uses'=>'AnalistaDisponibilidadeController@index'));
	Route::get('Analista_Disponibilidade/editRLT', array('as'=>'analistadisponibilidade.editRLT', 'uses'=>'AnalistaDisponibilidadeController@editRLT'));
	Route::get('Analista_Disponibilidade/excluir/{id}', array('as'=>'analistadisponibilidade.destroy', 'uses'=>'AnalistaDisponibilidadeController@destroy'));
	Route::get('/analista_disponibilidade_ajax', function(Request $request){
		$input = $request->all();
		$analistaDisponibilidade = AnalistaDisponibilidade::where('id_usuarioanalista', 	$input['id_usuarioanalista'])
			->where('periodo_apuracao', 	str_replace('/', "", $input['periodo_apuracao']))
			->where('empresa_id', 			$input['empresa_id']);

		return \Response::make($analistaDisponibilidade->first(['data_ini_disp','data_fim_disp','qtd_min_disp_dia']));
	});

	Route::get('Previsao_Carga/adicionar', array('as'=>'previsaocarga.adicionar', 'uses'=>'PrevisaoCargaController@create'));
	Route::post('Previsao_Carga/store', array('as'=>'previsaocarga.store', 'uses'=>'PrevisaoCargaController@store'));
	Route::get('Previsao_Carga/store', array('as'=>'previsaocarga.store', 'uses'=>'PrevisaoCargaController@store'));
	Route::post('Previsao_Carga/edit', array('as'=>'previsaocarga.edit', 'uses'=>'PrevisaoCargaController@edit'));
	Route::get('Previsao_Carga/', array('as'=>'previsaocarga.index', 'uses'=>'PrevisaoCargaController@index'));
	Route::get('Previsao_Carga/editRLT', array('as'=>'previsaocarga.editRLT', 'uses'=>'PrevisaoCargaController@editRLT'));
	Route::get('Previsao_Carga/excluir/{id}', array('as'=>'previsaocarga.destroy', 'uses'=>'PrevisaoCargaController@destroy'));

	Route::resource('cronogramaatividades', 'CronogramaatividadesController');

	Route::get('cronogramaatividades', array('as'=>'cronogramaatividades.index', 'uses'=>'CronogramaatividadesController@anyData'));
	Route::get('mensal', array('as'=>'cronogramaatividades.mensal', 'uses'=>'CronogramaatividadesController@Gerarmensal'));
	Route::post('mensal', array('as'=>'mensal', 'uses'=>'CronogramaatividadesController@mensal'));
	Route::get('cronogramaprogresso', array('as'=>'cronogramaatividades.checarCronogramaEmProgresso', 'uses'=>'CronogramaatividadesController@checarCronogramaEmProgresso'));

	Route::get('semanal', array('as'=>'cronogramaatividades.semanal', 'uses'=>'CronogramaatividadesController@Gerarsemanal'));
	Route::post('semanal', array('as'=>'semanal', 'uses'=>'CronogramaatividadesController@semanal'));

	Route::get('Planejamento', array('as'=>'cronogramaatividades.Loadplanejamento', 'uses'=>'CronogramaatividadesController@Loadplanejamento'));
	Route::post('Planejamento', array('as'=>'cronogramaatividades.planejamento', 'uses'=>'CronogramaatividadesController@planejamento'));
	Route::post('AlterAnalista', array('as'=>'cronogramaatividades.alterAnalista', 'uses'=>'CronogramaatividadesController@alterarAnalistas'));


	Route::get('GerarchecklistCron', array('as'=>'cronogramaatividades.GerarchecklistCron', 'uses'=>'CronogramaatividadesController@GerarchecklistCron'));
	Route::post('ChecklistCron', array('as'=>'ChecklistCron', 'uses'=>'CronogramaatividadesController@ChecklistCron'));

	Route::get('GerarConsulta', array('as'=>'cronogramaatividades.GerarConsulta', 'uses'=>'CronogramaatividadesController@GerarConsulta'));
	Route::post('ConsultaCronograma', array('as'=>'ConsultaCronograma', 'uses'=>'CronogramaatividadesController@ConsultaCronograma'));
	#task 416
	Route::get('GerarConsultaCalendario', array('as'=>'cronogramaatividades.GerarConsultaCalendario', 'uses'=>'CronogramaatividadesController@GerarConsultaCalendario'));
	Route::post('ConsultaPeriodoTabela', array('as'=>'ConsultaPeriodoTabela', 'uses'=>'CronogramaatividadesController@ConsultaPeriodoTabela'));


	Route::post('cronogramaatividades/excluir', array('as'=>'cronogramaatividades.excluir', 'uses'=>'CronogramaatividadesController@excluir'));
	Route::post('cronogramaatividades/alterar', array('as'=>'cronogramaatividades.alterar', 'uses'=>'CronogramaatividadesController@alterar'));
	Route::post('cronogramaatividades/storeEstab', array('as'=>'cronogramaatividades.storeEstabelecimento', 'uses'=>'CronogramaatividadesController@storeEstabelecimento'));
	Route::post('cronogramaatividades/storeEmp', array('as'=>'cronogramaatividades.storeEmpresa', 'uses'=>'CronogramaatividadesController@storeEmpresa'));
	Route::post('cronogramaatividades/clearActivitiesSchedule', array('as'=>'cronogramaatividades.clearActivitiesSchedule', 'uses'=>'CronogramaatividadesController@clearActivitiesSchedule'));

	Route::get('guiaicms', array('as'=>'guiaicms.icms', 'uses'=>'GuiaicmsController@icms'));
	Route::post('guiaicms/planilha', array('as'=>'guiaicms.planilha', 'uses'=>'GuiaicmsController@planilha'));
	Route::post('guiaicms/criticas', array('as'=>'guiaicms.criticas', 'uses'=>'GuiaicmsController@criticas'));
	Route::get('guiaicms/search_criticas', array('as'=>'guiaicms.search_criticas', 'uses'=>'GuiaicmsController@search_criticas'));
	Route::get('guiaicms/search_criticas_entrega', array('as'=>'guiaicms.search_criticas_entrega', 'uses'=>'GuiaicmsController@search_criticas_entrega'));
	Route::post('guiaicms/criticas_entrega', array('as'=>'guiaicms.criticas_entrega', 'uses'=>'GuiaicmsController@criticas_entrega'));

	Route::get('guiaicms/conferencia', array('as'=>'guiaicms.conferencia', 'uses'=>'GuiaicmsController@conferencia'));
	Route::post('guiaicms/conferencia', array('as'=>'guiaicms.conferencia', 'uses'=>'GuiaicmsController@conferencia'));

	//icms inicio crud
	Route::get('guiaicms/listar', array('as'=>'guiaicms.listar', 'uses'=>'GuiaicmsController@listar'));
	Route::get('guiaicms/anyData', array('as'=>'guiaicms.anyData', 'uses'=>'GuiaicmsController@anyData'));

	Route::post('guiaicms/novo', array('as'=>'guiaicms.create', 'uses'=>'GuiaicmsController@create'));
	Route::get('guiaicms/novo', array('as'=>'guiaicms.cadastrar', 'uses'=>'GuiaicmsController@create'));

	Route::get('guiaicms/editar/{id}', array('as'=>'guiaicms.editar', 'uses'=>'GuiaicmsController@editar'));
	Route::post('guiaicms/editar/{id}', array('as'=>'guiaicms.editar', 'uses'=>'GuiaicmsController@editar'));

	Route::get('guiaicms/excluir/{id}', array('as'=>'guiaicms.excluir', 'uses'=>'GuiaicmsController@excluir'));
	//ICMS fim de crud

	Route::get('centrocustos/', array('as'=>'centrocustos.create', 'uses'=>'CentrocustospgtoController@create'));
	Route::post('centrocustos/', array('as'=>'centrocustos.create', 'uses'=>'CentrocustospgtoController@create'));

	Route::get('codigosap/', array('as'=>'codigosap.create', 'uses'=>'CentrocustospgtoController@createsap'));
	Route::post('codigosap/', array('as'=>'codigosap.create', 'uses'=>'CentrocustospgtoController@createsap'));
});

Route::group(['middleware' => ['web','auth','role:supervisor|admin|owner|analyst|gcliente']], function () {
	Route::get('guiaicms', array('as'=>'guiaicms.icms', 'uses'=>'GuiaicmsController@icms'));
	Route::post('guiaicms/planilha', array('as'=>'guiaicms.planilha', 'uses'=>'GuiaicmsController@planilha'));
});

// Just the Owner, Admin, Manager and the Supervisor
Route::group(['middleware' => ['web','auth','role:supervisor|manager|admin|owner|gbravo|gcliente|analyst']], function () {

	Route::resource('atividades', 'AtividadesController');
	Route::get('atividade/data', array('as'=>'atividades.data', 'uses'=>'AtividadesController@anyData'));
	Route::get('atividade/{atividade}/aprovar', array('as'=>'atividades.aprovar', 'uses'=>'AtividadesController@aprovar'));
	Route::get('atividade/{atividade}/reprovar', array('as'=>'atividades.reprovar', 'uses'=>'AtividadesController@reprovar'));
	Route::get('atividade/{atividade}/retificar', array('as'=>'atividades.retificar', 'uses'=>'AtividadesController@retificar'));
	Route::get('atividade/{atividade}/cancelar', array('as'=>'atividades.cancelar', 'uses'=>'AtividadesController@cancelar'));
	Route::get('atividade/{atividade}/aprovar_cliente', array('as'=>'atividades.aprovar_cliente', 'uses'=>'AtividadesController@aprovar_cliente'));
	Route::get('atividade/{atividade}/reprovar_cliente', array('as'=>'atividades.reprovar_cliente', 'uses'=>'AtividadesController@reprovar_cliente'));

	Route::post('dashboard_tributo', array('as'=>'dashboard_tributo', 'uses'=>'PagesController@dashboard_tributo'));
	Route::get('dashboard_tributo', array('as'=>'dashboard_tributo', 'uses'=>'PagesController@dashboard_tributo'));
	Route::get('dashboardRLT', array('as'=>'dashboardRLT', 'uses'=>'PagesController@dashboardRLT'));

	Route::post('AnalistaCronograma', array('as'=>'cronograma.analistas', 'uses'=>'CronogramaatividadesController@AlterAnalistas'));

	Route::get('consulta_procadm', array('as'=>'consulta_procadm', 'uses'=>'ProcessosAdmsController@consulta_procadm'));
	Route::get('consulta_procadm/rpt', array('as'=>'consulta_procadm/rpt', 'uses'=>'ProcessosAdmsController@rlt_processos'));
	Route::get('rlt_detalhado', array('as'=>'rlt_detalhado', 'uses'=>'ProcessosAdmsController@rlt_detalhado'));
	Route::get('processosadms/dataRLT', array('as'=>'processosadms.dataRLT', 'uses'=>'ProcessosAdmsController@anyDataRLT'));
	Route::get('processosadms/search_observacao', array('as'=>'processosadms.searchObservacao', 'uses'=>'ProcessosAdmsController@searchObservacao'));

	Route::post('processosadms/action_valid_import', array('as'=>'processosadms.action_valid_import', 'uses'=>'ProcessosAdmsController@action_valid_import'));
	Route::post('processosadms/action_import', array('as'=>'processosadms.action_import', 'uses'=>'ProcessosAdmsController@action_import'));
	Route::get('processosadms/delete/{processosadms}', array('as'=>'processosadms.delete', 'uses'=>'ProcessosAdmsController@delete'));
	Route::get('estabelecimento/search_area', array('as'=>'estabelecimentos.searchArea', 'uses'=>'EstabelecimentosController@searchArea'));
	Route::get('processosadms/data', array('as'=>'processosadms.data', 'uses'=>'ProcessosAdmsController@anyData'));
	Route::get('processosadms/import', array('as'=>'processosadms.import', 'uses'=>'ProcessosAdmsController@import'));
	Route::get('processosadms/search', array('as'=>'processosadms.search', 'uses'=>'ProcessosAdmsController@search'));
	Route::resource('processosadms', 'ProcessosAdmsController');

	Route::post('status_empresas', array('as'=>'status_empresas', 'uses'=>'PagesController@status_empresas'));
	Route::get('status_empresas', array('as'=>'status_empresas', 'uses'=>'PagesController@status_empresas'));

	Route::post('sendEmailExport', array('as'=>'sendEmailExport', 'uses'=>'UsuariosController@sendEmailExport'));
});

Route::group(['middleware' => ['web','auth','role:supervisor|manager|admin|owner|gbravo|analyst']], function () {
	Route::post('dashboard', array('as'=>'dashboard', 'uses'=>'PagesController@dashboard'));
	Route::get('dashboard', array('as'=>'dashboard', 'uses'=>'PagesController@dashboard'));
});

Route::group(['middleware' => ['web','auth','role:gcliente|admin']], function () {
	Route::post('dashboard2', array('as'=>'dashboard2', 'uses'=>'PagesController@dashboard2'));
	Route::get('dashboard2', array('as'=>'dashboard2', 'uses'=>'PagesController@dashboard2'));
});

// Just Admin, Owner, Supervisor
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|msaf|admin|owner']], function () {

	Route::get('movtocontacorrente/layout', array('as'=>'movtocontacorrentes.layout', 'uses'=>'MovtocontacorrentesController@downloadLayout'));
	Route::get('movtocontacorrente/pdf', array('as'=>'movtocontacorrentes.pdf', 'uses'=>'MovtocontacorrentesController@downloadPDF'));
	Route::post('movtocontacorrentes/commit', array('as'=>'movtocontacorrentes.commit', 'uses'=>'MovtocontacorrentesController@commit'));
	Route::get('movtocontacorrentes/consultagerencial', array('as'=>'movtocontacorrentes.consultagerencial', 'uses'=>'MovtocontacorrentesController@consultagerencial'));
	Route::post('movtocontacorrentes/consultagerencial', array('as'=>'movtocontacorrentes.consultagerencial', 'uses'=>'MovtocontacorrentesController@consultagerencial'));
	Route::get('movtocontacorrentes/search', array('as'=>'movtocontacorrentes.search', 'uses'=>'MovtocontacorrentesController@search'));
	Route::post('movtocontacorrentes/search', array('as'=>'movtocontacorrentes.search', 'uses'=>'MovtocontacorrentesController@search'));
	Route::get('movtocontacorrentes/confirm/{movtocontacorrente}', array('as'=>'movtocontacorrentes.confirm', 'uses'=>'MovtocontacorrentesController@confirm'));
	Route::get('movtocontacorrentes/import', array('as'=>'movtocontacorrentes.import', 'uses'=>'MovtocontacorrentesController@import'));
	Route::post('movtocontacorrentes/importCommit', array('as'=>'movtocontacorrentes.importCommit', 'uses'=>'MovtocontacorrentesController@importCommit'));
	Route::get('movtocontacorrente/data', array('as'=>'movtocontacorrentes.data', 'uses'=>'MovtocontacorrentesController@anyData'));
	Route::get('movtocontacorrentes/delete/{movtocontacorrente}', array('as'=>'movtocontacorrentes.delete', 'uses'=>'MovtocontacorrentesController@delete'));
	Route::post('movtocontacorrentes/action_import', array('as'=>'movtocontacorrentes.action_import', 'uses'=>'MovtocontacorrentesController@action_import'));
	Route::post('movtocontacorrentes/action_valid_import', array('as'=>'movtocontacorrentes.action_valid_import', 'uses'=>'MovtocontacorrentesController@action_valid_import'));
	Route::get('movtocontacorrente', array('as'=>'movtocontacorrente', 'uses'=>'MovtocontacorrentesController@index'));
	Route::get('movtocontacorrente/historico/{id}', array('as'=>'movtocontacorrentes.historic', 'uses'=>'HistoricoContaCorrenteController@index'));
	Route::resource('movtocontacorrentes', 'MovtocontacorrentesController');
});

// Just Admin, Owner, Supervisor
// Route::group(['middleware' => ['web','auth','role:analyst|supervisor|msaf|admin|owner']], function () {


Route::get('movtocontacorrentes/confirm/{movtocontacorrente}', array('as'=>'movtocontacorrentes.confirm', 'uses'=>'MovtocontacorrentesController@confirm'));
Route::get('movtocontacorrente/data', array('as'=>'movtocontacorrentes.data', 'uses'=>'MovtocontacorrentesController@anyData'));


Route::get('movtocontacorrente', array('as'=>'movtocontacorrente', 'uses'=>'MovtocontacorrentesController@index'));



// });

// Just Admin, Owner, Supervisor
Route::group(['middleware' => ['web','auth','role:admin|owner|supervisor']], function () {

	Route::resource('empresas', 'EmpresasController');
	Route::get('empresa/ajax', array('as'=>'empresas.ajax', 'uses'=>'EmpresasController@ajax'));
	Route::get('empresa/data', array('as'=>'empresas.data', 'uses'=>'EmpresasController@anyData'));

	Route::resource('estabelecimentos', 'EstabelecimentosController');
	Route::get('estabelecimento/data', array('as'=>'estabelecimentos.data', 'uses'=>'EstabelecimentosController@anyData'));

	Route::resource('municipios', 'MunicipiosController');
	Route::get('municipio/data', array('as'=>'municipios.data', 'uses'=>'MunicipiosController@anyData'));

});

// Just Admin, Owner
Route::group(['middleware' => ['web','auth','role:admin|owner']], function () {

	Route::get('mensageriaprocadms/search_role', array('as'=>'mensageriaprocadms.searchRole', 'uses'=>'MensageriaprocadmsController@searchRole'));
	Route::resource('mensageriaprocadms', 'MensageriaprocadmsController');

	Route::resource('categorias', CategoriasController::class);
	Route::get('categoria/data', array('as'=>'categorias.data', 'uses'=>'CategoriasController@anyData'));

	Route::resource('tributos', 'TributosController');
	Route::get('tributo/data', array('as'=>'tributos.data', 'uses'=>'TributosController@anyData'));

	Route::resource('regras', 'RegrasController');
	Route::get('regra/data', array('as'=>'regras.data', 'uses'=>'RegrasController@anyData'));
	Route::get('regra/{regra}/{estabelecimento}/{enable}/setBlacklist', array('as'=>'regras.setBlacklist', 'uses'=>'RegrasController@setBlacklist'));
	Route::get('regras/ruleEnableDisable/{regra}/{estabelecimento}/{active}', array('as'=>'regras.ruleEnableDisable', 'uses'=>'RegrasController@ruleEnableDisable'));

	Route::resource('grupoempresas', 'GrupoEmpresasController');
	Route::post('grupoempresas/', array('as'=>'grupoempresas', 'uses'=>'GrupoEmpresasController@index'));
	Route::get('grupoempresas/create', array('as'=>'grupoempresas.create', 'uses'=>'GrupoEmpresasController@adicionar'));
	Route::post('grupoempresas/store', array('as'=>'grupoempresas.store', 'uses'=>'GrupoEmpresasController@store'));
	Route::get('grupoempresas/destroy/{id}', array('as'=>'grupoempresas.destroy', 'uses'=>'GrupoEmpresasController@destroy'));
	Route::get('grupoempresas/destroyRLT/{id}', array('as'=>'grupoempresas.destroyRLT', 'uses'=>'GrupoEmpresasController@destroyRLT'));
	Route::get('grupoempresas/edit/{nomeGrupo}', array('as'=>'grupoempresas.anyData', 'uses'=>'GrupoEmpresasController@anyData'));

	Route::resource('regraslotes', 'RegrasenviolotesController');
	Route::get('regra/envio_lote', array('as'=>'regraslotes.envio_lote', 'uses'=>'RegrasenviolotesController@envio_lote'));
	Route::get('regra/edit_lote', array('as'=>'regraslotes.edit_lote', 'uses'=>'RegrasenviolotesController@edit_lote'));
	Route::get('regra/lote_consulta', array('as'=>'regraslotes.lote_consulta', 'uses'=>'RegrasenviolotesController@lote_consulta'));
	Route::get('regra/excluir', array('as'=>'regraslotes.excluir', 'uses'=>'RegrasenviolotesController@excluir'));
	Route::get('regra/excluirFilial', array('as'=>'regraslotes.excluirFilial', 'uses'=>'RegrasenviolotesController@excluirFilial'));

	Route::resource('usuarios', 'UsuariosController');
	Route::get('usuario/data', array('as'=>'usuarios.data', 'uses'=>'UsuariosController@anyData'));
	Route::get('usuario/{user}/sendEmailReminder', array('as'=>'usuarios.sendEmailReminder', 'uses'=>'UsuariosController@sendEmailReminder'));

	Route::get('empresa/{periodo}/{empresa}/geracao', array('as'=>'empresas.geracao', 'uses'=>'EmpresasController@geracao'));
	Route::get('empresa/{periodo}/{empresa}/{tributo}/{ref}/geracao', array('as'=>'empresas.geracao', 'uses'=>'EmpresasController@geracao'));
	Route::get('estabelecimento/{tributo}/{estabelecimento}/{periodo_ini}/{periodo_fin}/geracao', array('as'=>'estabelecimentos.geracao', 'uses'=>'EstabelecimentosController@geracao'));

	Route::get('cronogramaatividades/empresa/{periodo}/{empresa}', array('as'=>'empresas.cronogramageracao', 'uses'=>'EmpresasController@cronogramageracao'));
	Route::get('cronogramaatividades/estabelecimento/{tributo}/{estabelecimento}/{periodo_ini}/{periodo_fin}', array('as'=>'estabelecimentos.cronogramageracao', 'uses'=>'EstabelecimentosController@cronogramageracao'));

});

// Just the Owner
Route::group(['middleware' => ['web','auth','role:admin|owner']], function () {

	Route::get('usuario/{user}/elevateRole', array('as'=>'usuarios.elevateRole', 'uses'=>'UsuariosController@elevateRole'));
	Route::get('usuario/{user}/decreaseRole', array('as'=>'usuarios.decreaseRole', 'uses'=>'UsuariosController@decreaseRole'));

});

// Just Admin and Supervisor
Route::group(['middleware' => ['web','auth','role:admin|supervisor']], function () {
	Route::get('documentacao/consultar', array('as'=>'documentacao.consultar', 'uses'=>'DocumentacaoClienteController@index'));
	Route::get('documentacao/adicionar', array('as'=>'documentacao.adicionar', 'uses'=>'DocumentacaoClienteController@create'));
	Route::post('documentacao/adicionar', array('as'=>'documentacao.adicionar', 'uses'=>'DocumentacaoClienteController@create'));
	Route::get('documentacao/editar/{id}', array('as'=>'documentacao.editar', 'uses'=>'DocumentacaoClienteController@update'));
	Route::post('documentacao/editar/{id}', array('as'=>'documentacao.editar', 'uses'=>'DocumentacaoClienteController@update'));
	Route::post('documentacao/upload', array('as'=>'documentacao.upload', 'uses'=>'DocumentacaoClienteController@uploadSingle'));
	Route::get('documentacao/excluir/{id}', array('as'=>'documentacao.excluir', 'uses'=>'DocumentacaoClienteController@destroy'));
});

Route::group(['middleware' => ['web','auth','role:supervisor|manager|admin|owner|gbravo|analyst']], function () {
	Route::get('documentacao/listar', array('as'=>'documentacao.listar', 'uses'=>'DocumentacaoClienteController@show'));
});
Route::group(['middleware' => ['web','auth','role:gcliente']], function () {
	Route::get('documentacao/listar/cliente', array('as'=>'documentacao.cliente', 'uses'=>'DocumentacaoClienteController@showclient'));
});
Route::group(['middleware' => ['web','auth','role:supervisor|manager|admin|owner|gbravo|gcliente|analyst']], function () {
	Route::get('documentacao/download/{id}', array('as'=>'documentacao.download', 'uses'=>'DocumentacaoClienteController@download'));
});

Route::group(['middleware' => ['web','auth','role:supervisor|admin']], function () {
	Route::get('validador', array('as'=>'validador.index', 'uses'=>'ValidadorController@index'));
	Route::post('validador', array('as'=>'validador.validaDados', 'uses'=>'ValidadorController@validateData'));
});

Route::group(['prefix' => '/documentacaocliente', 'middleware' => ['web','auth','role:admin']], function () {
	Route::get('subcategoria/listar', array('as'=>'documentacao.subcategoria.listar', 'uses'=>'DocumentacaoSubcategoriaController@index'));
	Route::get('subcategoria/editar/{id}', array('as'=>'documentacao.subcategoria.editar', 'uses'=>'DocumentacaoSubcategoriaController@show'));
	Route::post('subcategoria/adicionar', array('as'=>'documentacao.subcategoria.adicionar', 'uses'=>'DocumentacaoSubcategoriaController@store'));
	Route::post('subcategoria/editar/{id}', array('as'=>'documentacao.subcategoria.editar', 'uses'=>'DocumentacaoSubcategoriaController@update'));
	Route::delete('subcategoria/excluir/{id}', array('as'=>'documentacao.subcategoria.excluir', 'uses'=>'DocumentacaoSubcategoriaController@destroy'));
});
Route::group(['middleware' => ['web','auth','role:analyst|supervisor|msaf|admin|owner|gbravo']], function () {
	Route::get('impostos/selecionarguias', 'ImpostosController@selecionarGuias')->name('impostos.selecionarguias');
	Route::post('impostos/validarguias', ['as' => 'impostos.validarguias', 'uses' => 'ImpostosController@validarGuias']);
	Route::post('impostos/importarguias', ['as' => 'impostos.importarguias', 'uses' => 'ImpostosController@importarGuias']);

	Route::post('impostos/aprovarguias', ['as' => 'impostos.aprovarguias', 'uses' => 'ImpostosController@aprovarGuias']);
	Route::post('impostos/reprovarguias', ['as' => 'impostos.reprovarguias', 'uses' => 'ImpostosController@reprovarGuias']);
	Route::get('impostos/conferenciaguias', 'ImpostosController@conferenciaGuias')->name('impostos.conferenciaguias');
	Route::get('impostos/consultar', 'ImpostosController@consultar')->name('impostos.consultar');
	Route::post('impostos/conferenciaguiasdownload', ['as' => 'impostos.conferenciaguiasdownload', 'uses' => 'ImpostosController@conferenciaGuiasDownload']);
	Route::get('impostos/conferenciaguiasdownloaddelete/{arquivo}', array('as'=>'impostos.conferenciaguiasdownloaddelete', 'uses'=>'ImpostosController@deleteArquivoZip'));
	Route::get('impostos/liberarclientesemzfic', 'ImpostosController@liberarClienteSemZfic')->name('impostos.liberarclientesemzfic');
	Route::post('impostos/enviarclientesemzfic', ['as' => 'impostos.enviarclientesemzfic', 'uses' => 'ImpostosController@enviarClienteSemZfic']);
	Route::get('impostos/rodaratividadesjob', array('as'=>'impostos.rodaratividadesjob', 'uses'=>'ImpostosController@rodarAtividadesJob'));

//	Route::post('impostos/importar', array('as'=>'impostos.importar', 'uses'=>'ImpostosController@importar'));
//	Route::get('impostos/conferenciaguias', 'ImpostosController@conferenciaGuias')->name('impostos.conferenciaguias');
//	Route::get('impostos/liberarcliente', 'ImpostosController@liberarCliente')->name('impostos.liberarcliente');

});