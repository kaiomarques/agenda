<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntregaExtensao extends Model
{
    protected $table = 'entregaextensao';

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'tributo_id',
        'arquivo',
        'extensao',
    ];

}
