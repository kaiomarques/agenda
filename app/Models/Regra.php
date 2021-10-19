<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regra extends Model
{
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'nome_especifico',
        'tributo_id',
        'ref',
        'regra_entrega',
        'freq_entrega',
        'legislacao',
        'obs',
        'afds',
        'ativo'
    ];

    /**
        * Get the tributo that owns the regra.
    */
    public function tributo()
    {
        return $this->belongsTo('App\Models\Tributo');
    }

    /**
     * The estabelecimentos that are inactive for the regra.
     */
    public function estabelecimentos()
    {
        return $this->belongsToMany('App\Models\Estabelecimento');
    }

}
