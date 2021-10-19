<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Validador extends Model
{
    protected $table = 'criticasvalores';
	
	/**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'criticasvalores_id',
		'empresa_id',
		'estemp_id',
		'periodo_apuracao',
		'critica',
		'data_critica',
		'user_id'
    ];

	public function empresa()
    {
        return $this->belongsTo('App\Models\Empresa', 'emp_id');
	}
	/**
     * Get the estabelecimentos for this empresa.
     */
    public function estabelecimento()
    {
        return $this->belongsTo('App\Models\Estabelecimento', 'estemp_id');
    }
}
