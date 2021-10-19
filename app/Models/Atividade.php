<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atividade extends Model
{

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
        'data_aprovacao',
        'usuario_aprovador',
        'usuario_entregador',
        'vlr_recibo_1',
        'vlr_recibo_2',
        'vlr_recibo_3',
        'vlr_recibo_4',
        'vlr_recibo_5',
        'status_cliente',
        'cliente_aprovador',
        'data_aprovacao_cliente'
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
     * Get the empresa record
     */
    public function empresa()
    {
        return $this->belongsTo('App\Models\Empresa','emp_id');
    }

    /**
     * Get the usuario entregador
     */
    public function entregador()
    {
        return $this->belongsTo('App\Models\User','usuario_entregador');
    }

    /**
     * Get the usuario aprovador
     */
    public function aprovador()
    {
        return $this->belongsTo('App\Models\User','usuario_aprovador', 'id');
    }

    /**
     * Get the cliente aprovador
     */
    public function clienteaprovador()
    {
        return $this->belongsTo('App\Models\User','cliente_aprovador', 'id');
    }

    /**
     * Get the users assigned for this atividade.
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    /**
     * Get the comentarios for the atividade.
     */
    public function comentarios()
    {
        return $this->hasMany('App\Models\Comentario');
    }

    /**
     * Get the retificacoes for the atividade.
     */
    public function retificacoes()
    {
        return $this->hasMany('App\Models\Atividade','retificacao_id');
    }

    /**
     * Get the main atividade that owns the retificacao.
     */
    public function primat()
    {
        return $this->belongsTo('App\Models\Atividade','retificacao_id');
    }

	/**
     * Get the estabelecimentos for this empresa.
     */
    public function estabelecimento()
    {
        return $this->belongsTo("App\Models\Estabelecimento", 'estemp_id');
    }
	
		public function conferenciaguias()
		{
			return $this->hasMany('App\Models\ConferenciaGuias');
		}
}
