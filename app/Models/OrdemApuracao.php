<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdemApuracao extends Model
{
    protected $table = 'ordemapuracao';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'Tributo_id',
        'Prioridade'
    ];

    public function tributo()
    {
        return $this->belongsTo('App\Models\Tributo','id');
    }

}
