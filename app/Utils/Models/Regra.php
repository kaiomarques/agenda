<?php
namespace App\Utils\Models;

use App\Utils\Model;

/**
 * @property string $nome_especifico
 * @property int $tributo_id
 * @property string $ref
 * @property string $regra_entrega
 * @property string $freq_entrega
 * @property string $legislacao
 * @property string $obs
 * @property string $afds
 * @property string $ativo
 * @property int $qtd_atividade
 * */
class Regra extends Model {

	protected $table    = 'regras';
	protected $fillable = [
		'nome_especifico' => 'STRING',
		'tributo_id'      => 'ID',
		'ref'             => 'STRING',
		'regra_entrega'   => 'STRING',
		'freq_entrega'    => 'STRING',
		'legislacao'      => 'STRING',
		'obs'             => 'STRING',
		'afds'            => 'STRING',
		'ativo'           => 'STRING',
		'qtd_atividade'   => 'ID'
	];

	public function onAfterInsert() {

	}

	public function onAfterUpdate() {

	}


}