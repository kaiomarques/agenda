<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gerazfic extends Model
{
	protected $table = "gerazfic";
	
	protected $fillable = [
		'tributo_id',
		'empresa_id'
	];
	
	public function tributos()
	{
		return $this->belongsTo('App\Models\Tributo','tributo_id');
	}
	
	public function empresas()
	{
		return $this->belongsTo('App\Models\Empresa','empresa_id');
	}
}
