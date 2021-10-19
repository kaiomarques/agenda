<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guiaiss extends Model
{
	protected $primaryKey = 'id';
	protected $table = 'guiaiss';
	public $timestamps = false;
    /**
     * Fillable fields
     *
     * @var array
     */
    protected $fillable = [
		'cnpj',
		'periodo_apuracao',
		'periodo_competencia',
		'cod_municipio',
		'vencimento',
		'valor_guia',
		'valor_juros',
		'valor_multa',
		'codigo_barras',
		'data_leitura_guia',
		'usuario_leitura_guia'
	];
}
