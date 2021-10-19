<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observacaoprocadm extends Model
{
    //
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'descricao',
        'usuario_update',
		'processoadm_id',
		'tipo_processamento'
    ];

    public function processoadm()
    {
        return $this->belongsTo('App\Models\Processoadm','processoadm_id');
    }
}
