<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Processosadm extends Model
{
    //
    protected $morphClass = 'ProcessosadmMorph';

    protected $fillable = [
        'periodo_apuracao',
        'estabelecimento_id', 
        'nro_processo',
        'resp_financeiro_id',
        'resp_acompanhamento',
        'status_id',
		'usuario_last_update',
		'tipo_processamento'
    ];

    /**
     * Get the estabelecimentos for this empresa.
     */
    public function estabelecimentos()
    {
        return $this->belongsTo('App\Models\Estabelecimento', 'estabelecimento_id');
    }

    /**
     * Get the empresa record associated with the estabelecimento.
     */
    public function statusprocadm()
    {
        return $this->belongsTo('App\Models\Statusprocadm','status_id');
    }

    /**
     * Get the empresa record associated with the estabelecimento.
     */
    public function respfinanceiro()
    {
        return $this->belongsTo('App\Models\Respfinanceiro','resp_financeiro_id');
    }

    /**
     * Get the comentarios for the atividade.
     */
    public function observacoes()
    {
        return $this->hasMany('App\Models\Observacaoprocadm', 'processoadm_id');
    }
}
