<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoContaCorrente extends Model
{
    protected $table = 'historicocontacorrente';
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'Id_contacorrente',
        'Alteracao_realizada',
        'Id_usuario_alteracao',
        'Data_alteracao'
    ];
}
