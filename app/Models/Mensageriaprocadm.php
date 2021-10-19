<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensageriaprocadm extends Model
{
    protected $morphClass = 'MorphMensageria';
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'parametro_qt_dias',
        'role_id', 
        'usuario_update'
    ];
}
