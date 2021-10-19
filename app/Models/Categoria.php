<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'nome',
        'descricao'
    ];

    /**
     * Get the tributos for this categoria.
     */
    public function tributos()
    {
        return $this->hasMany('App\Models\Tributo');
    }

}
