<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoCNDObservacao extends Model
{
    protected $table = 'documentocnd_observacao';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'usuario_id',
        'documentocnd_id',
        'texto',
        'data'
    ];
}