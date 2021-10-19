<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronogramaAtividade extends Model
{
    protected $table = "cronogramaatividades";
    
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'descricao',
        'recibo',
        'status',
        'cnpj',
        'periodo_apuracao',
        'inicio_aviso',
        'limite',
        'tipo_geracao',
        'regra_id',
        'emp_id',
        'estemp_id',
        'estemp_type',
        'retificacao_id',
        'Id_usuario_analista',
        'Resp_cronograma',
        'Data_cronograma',
        'data_atividade',
        'tempo',
        'cronograma_mensal'
    ];

    /**
     * Get all of the owning estab/empresa models.
     */
    public function estemp()
    {
        return $this->morphTo();
    }

    /**
     * Get the regra record
     */
    public function regra()
    {
        return $this->belongsTo('App\Models\Regra','regra_id');
    }

    /**
     * Get the usuario entregador
''     */
    public function entregador()
    {
        return $this->belongsTo('App\Models\User','usuario_entregador');
    }

    /**
     * Get the usuario aprovador
     */
    public function aprovador()
    {
        return $this->belongsTo('App\Models\User','usuario_aprovador');
    }

    /**
     * Get the users assigned for this atividade.
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }


}
