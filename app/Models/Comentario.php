<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'obs',
        'user_id',
        'atividade_id'
    ];

    /**
     * Get the atividade
     */
    public function atividade()
    {
        return $this->belongsTo('App\Models\Atividade','atividade_id');
    }

    /**
     * Get the usuario
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }
}
