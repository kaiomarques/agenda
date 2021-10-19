<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronogramaStatus extends Model
{
    protected $table = "cronogramastatus";
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
