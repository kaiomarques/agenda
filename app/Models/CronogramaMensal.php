<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronogramaMensal extends Model
{
    protected $table = 'cronogramamensal';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'Empresa_id',
        'Tributo_id',
        'DATA_SLA',
        'periodo_apuracao',
        'uf',        
        'Qtde_estab',
        'Tempo_estab',
        'Tempo_total',
        'Qtd_dias',
        'Tempo_geracao',
        'Qtd_analistas'
    ];

}
