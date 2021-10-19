<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guiaicms extends Model
{
    protected $primaryKey = 'ID';
    protected $table = 'guiaicms';
    public $timestamps = false;

    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
        'TRIBUTO_ID',
        'CNPJ',
        'IE',
        'COD_RECEITA',
        'REFERENCIA',
        'DATA_VENCTO',
        'INSCR_DIVIDA',
        'N_AIM_ADI_PARC',
        'VLR_RECEITA',
        'JUROS_MORA',
        'MULTA_MORA_INFRA',
        'ACRESC_FINANC',
        'HONORARIOS_ADV',
        'VLR_TOTAL',
        'CONTRIBUINTE',
        'ENDERECO',
        'MUNICIPIO',
        'UF',
        'TELEFONE',
        'CNAE',
        'OBSERVACAO',
        'MULTA_PENAL_FORMAL',
        'CODBARRAS',
        'TAXA',
        'IMPOSTO',
        'USUARIO',
        'DATA',
		'ORDEM_TAXA',
		'CONFERENCIAGUIAS_ID'
    ];

}
