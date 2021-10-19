<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransmitirSped extends Model
{
    protected $table = 'transmitirsped';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'id_atividade',
        'nome_arquivo',
        'data_copia',
        'usuario'
    ];

}
