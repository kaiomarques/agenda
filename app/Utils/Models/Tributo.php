<?php
namespace App\Utils\Models;

use App\Utils\Model;

/**
 * @property string $nome
 * @property string $descricao
 * @property int $categoria_id
 * @property string $tipo
 * @property string $recibo
 * @property int $alerta
 * @property string $pasta_arquivos
 * */
class Tributo extends Model {

	protected $table    = 'tributos';
	protected $fillable = [
		'nome'           => 'STRING',
		'descricao'      => 'STRING',
		'categoria_id'   => 'ID',
		'tipo'           => 'STRING',
		'recibo'         => 'STRING',
		'alerta'         => 'ID',
		'pasta_arquivos' => 'STRING'
	];

	public function onAfterInsert() {

	}

	public function onAfterUpdate() {

	}


}