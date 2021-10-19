<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statusprocadm extends Model
{
    //
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'descricao'
    ];


    /**
     * Get the estabelecimentos for this empresa.
     */
    public function processosadm()
    {
        return $this->hasMany('App\Models\Processosadm');
    }

    /**
     * Get the estabelecimentos for this empresa.
     */
    public function movtocontacorrente()
    {
        return $this->hasMany('App\Models\Movtocontacorrente');
    }
}
