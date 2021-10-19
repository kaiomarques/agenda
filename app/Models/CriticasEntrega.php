<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CriticasEntrega extends Model
{
    protected $table = 'criticasentrega';
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

    public static function NoDuplicity()
    {
        CriticasEntrega::whereRaw('DATE_FORMAT(criticasentrega.Data_critica, "%Y-%m-%d") = "'.date('Y-m-d').'"')->delete();
    }

}
