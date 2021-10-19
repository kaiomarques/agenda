<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cron extends Model
{
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'periodo_apuracao',
        'tipo_periodo',
        'qtd'
    ];
}
