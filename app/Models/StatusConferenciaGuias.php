<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusConferenciaGuias extends Model
{
	public $timestamps = false;
	
	protected $table = "statusconferenciaguias";
	
	/**
	 * Fillable fields
	 *
	 * @var array
	 */
	protected $fillable = [
		'nome'
	];
	
	public function conferenciaguias()
	{
		return $this->belongsToMany('App\Models\ConferenciaGuias');
	}
}