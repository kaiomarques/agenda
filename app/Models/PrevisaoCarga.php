<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrevisaoCarga extends Model
{
    protected $table = 'previsaocarga';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'Tributo_id',
        'periodo_apuracao',
        'Data_prev_carga'
    ];
}