<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AtividadeAnalistaFilial extends Model
{
	protected $table = 'atividadeanalistafilial';
    public $timestamps = false;

    protected $fillable = [
        'Id_estabelecimento',
        'Id_atividadeanalista'
    ];
}

