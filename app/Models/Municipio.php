<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $primaryKey = 'codigo';
    //public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'codigo',
        'nome',
        'uf'
    ];

    /**
     * Get the empresas for this municipio.
     */
    public function empresas()
    {
        return $this->hasMany('App\Models\Empresa');
    }

    /**
     * Get the empresas for this municipio.
     */
    public function estabelecimentos()
    {
        return $this->hasMany('App\Models\Estabelecimentos');
    }

}
