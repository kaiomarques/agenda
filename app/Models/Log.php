<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'descricao',
        'user_id',
        'type'
    ];
}
