<?php
namespace App\Utils\Models;

use App\Utils\Model;

/**
 * @property int $empresa_id
 * @property string $cnpj
 * @property string $cod_municipio
 * @property string $codigo
 * @property string $razao_social
 * @property string $endereco
 * @property string $ativo
 * */
class Estabelecimento extends Model {

	protected $table    = 'estabelecimentos';
	protected $fillable = [
		'empresa_id'    => 'ID',
		'cnpj'          => 'CNPJ',
		'cod_municipio' => 'STRING',
		'codigo'        => 'STRING',
		'razao_social'  => 'STRING',
		'endereco'      => 'STRING',
		'ativo'         => 'STRING' // TODO: terminar
	];

	/** @return Municipio */
	public function municipio() {
		return $this->hasParent('Municipio', 'cod_municipio');
	}

}