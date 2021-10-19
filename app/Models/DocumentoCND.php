<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoCND extends Model
{
    protected $table = 'documentocnd';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'estemp_id',
        'tipocnd_id',
        'classificacaocnd_id',
        'numero_cnd',
        'descricao',
        'validade_cnd',
        'arquivo_cnd'
    ];
}