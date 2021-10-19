<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassificacaoCND extends Model
{
    protected $table = 'classificacaocnd';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'descricao'
    ];

    const POSITIVA = 1;

    const POSITIVA_NEGATIVA = 2;

    const NEGATIVA = 3;
}