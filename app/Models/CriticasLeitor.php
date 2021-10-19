<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriticasLeitor extends Model
{
    protected $table = 'criticasleitor';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'ID',
        'Empresa_id',
        'Estemp_id',
        'Tributo_id',
        'arquivo',
        'Data_critica',
        'critica',
        'importado',
        'Enviado'
    ];

}
