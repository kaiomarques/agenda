<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalistaDisponibilidade extends Model
{
    protected $table = 'analistadisponibilidade';
    public $timestamps = false;

    protected $fillable = [
        'id_usuarioanalista',
        'empresa_id',
        'qtd_min_disp_dia',
        'data_ini_disp',
        'data_fim_disp',
		'periodo_apuracao'
    ];
}