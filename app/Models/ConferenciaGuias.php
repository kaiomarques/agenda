<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConferenciaGuias extends Model
{
	public $timestamps = false;
	
	protected $table = "conferenciaguias";
	
	protected $fillable = [
		'tributo_id',
		'periodo_apuracao',
		'uf',
		'estemp_id',
		'atividade_id',
		'usuario_analista_id',
		'nome_arquivo',
		'data_importacao',
		'statusconferencia_id',
		'usuario_conferente_id',
		'data_conferencia',
		'observacao'
	];
	
	public function tributos()
	{
		return $this->belongsTo('App\Models\Tributo','tributo_id');
	}
	
	public function estabelecimentos()
	{
		return $this->belongsTo('App\Models\Estabelecimento','estemp_id');
	}
	
	public function atividades()
	{
		return $this->belongsTo('App\Models\Atividade','atividade_id');
	}
	
	public function usuarios()
	{
		return $this->belongsTo('App\Models\User','usuario_analista_id');
	}
	
	public function statusconferenciaguias()
	{
		return $this->belongsTo('App\Models\StatusConferenciaGuias', 'statusconferencia_id', 'id');
	}
	
	public function usuariosconferente()
	{
		return $this->belongsTo('App\Models\User','usuario_conferente_id');
	}
}
