<?php
namespace App\Utils\Models;

use App\Utils\Model;

/**
 * @property string $nome
 * @property string $uf
 * @property string $codigo_sap
 * */
class Municipio extends Model {

	protected $table    = 'municipios';
	protected $pkname   = 'codigo';
	protected $fillable = [
		'nome'       => 'STRING',
		'uf'         => 'STRING',
		'codigo_sap' => 'STRING'
	];

	public function onAfterInsert() {

	}

	public function onAfterUpdate() {

	}


}