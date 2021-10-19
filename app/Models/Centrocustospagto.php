<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Centrocustospagto extends Model
{
    protected $table = 'centrocustospagto';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'Empresa_id',
        'Estemp_id',
        'centrocusto',
        'descricao'
    ];

}
