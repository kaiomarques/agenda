<?php

namespace App\Http\Controllers;

use App\Models\DocumentacaoCliente;
use App\Models\DocumentacaoSubcategoria;
use Illuminate\Http\Request;

class DocumentacaoSubcategoriaController extends Controller
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		$table = DocumentacaoSubcategoria::all();
			
		return view('documentacaocliente.subcategoria.index')->with(compact('table'));
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		$input = $request->all();

		if (!empty($input)) {
			$subcategoria['subcategoria_descricao'] = $input['subcategoria_descricao'];
			$subcategoria['subcategoria_status'] = $input['subcategoria_status'];

			DocumentacaoSubcategoria::create($subcategoria);
			$table = DocumentacaoSubcategoria::all();
			
			return redirect()->back()->with('status', 'Subcategoria adicionada com sucesso.')->with(compact('table'));
		} else {
			$table = DocumentacaoSubcategoria::all();
			
			return view('documentacaocliente.subcategoria.index')->with(compact('table'));
		}
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		return DocumentacaoSubcategoria::where('subcategoria_id', '=', $id)->firstOrFail();
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		$input = $request->all();

		if (!empty($input)) {
			DocumentacaoSubcategoria::where('subcategoria_id', '=', $id)
			->update(
				[
					'subcategoria_descricao' => $input['subcategoria_descricao'],
					'subcategoria_status' => $input['subcategoria_status']
				]
			);
			
			$table = DocumentacaoSubcategoria::all();
			
			return redirect()->back()->with('status', 'Subcategoria alterada com sucesso.')->with(compact('table'));
		} else {
			$table = DocumentacaoSubcategoria::all();
			
			return view('documentacaocliente.subcategoria.index')->with(compact('table'));
		}
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		if (!empty($id) || $id >= 0) {
			$documentacao = DocumentacaoCliente::where('subcategoria_id', '=', $id)->get();
			if ($documentacao->count() > 0) {
				$table = DocumentacaoSubcategoria::all();
				return redirect()->back()->with('warning', 'Não é possível excluir essa subcategoria, ela esta associada a '.$documentacao->count().' documento(s)')->with(compact('table'));
			} else {
				DocumentacaoSubcategoria::where('subcategoria_id', '=', $id)
				->delete();
				$table = DocumentacaoSubcategoria::all();
				
				return redirect()->back()->with('status', 'Subcategoria excluida com sucesso.')->with(compact('table'));
			}
		} else {
			$table = DocumentacaoSubcategoria::all();
			
			return redirect()->back()->with('error', 'Ocorreu um erro ao tentar excluir a Subcategoria. Não recebemos a sua identificação')->with(compact('table'));
		}
	}
}
