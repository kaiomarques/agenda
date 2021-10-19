<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Liberarguias extends Model
{
	public $timestamps = false;
	
	protected $table = "liberaguias";
	
	protected $fillable = [
		'assunto',
		'emails',
		'data_liberada',
		'usuario_id'
	];
	
	public function usuarios()
	{
		return $this->belongsTo('App\Models\User','usuario_id');
	}
}