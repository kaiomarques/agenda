<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempoAtividade extends Model
{
    protected $table = 'tempoatividade';
    public $timestamps = false;

    protected $fillable = [
        'Empresa_id',
        'Tributo_id',
        'UF',
		'Qtd_minutos'
    ];
}