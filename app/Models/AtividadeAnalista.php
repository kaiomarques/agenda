<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtividadeAnalista extends Model
{
    protected $table = 'atividadeanalista';
    public $timestamps = false;

    protected $fillable = [
        'Emp_id',
        'Tributo_id',
        'Id_usuario_analista',
        'Regra_geral',
        'uf'
    ];
}