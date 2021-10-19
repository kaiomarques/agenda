<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCND extends Model
{
    protected $table = 'tipocnd';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'descricao'
    ];

    const MUNICIPAL = 1;

    const ESTADUAL = 2;

    const FEDERAL = 3;
}