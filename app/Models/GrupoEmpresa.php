<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoEmpresa extends Model
{
    /**
     * Fillable fields
     *
     * @var array
     */

    protected $table = 'grupoempresas';
    
    public $timestamps = false;

    protected $fillable = [
        'Nome_grupo',
        'id_empresa',
        'Logo_grupo'
    ];

    /**
     * The empresas that belong to the grupo.
     */
    public function empresas()
    {
        return $this->belongsToMany('App\Models\Empresa');
    }

}
