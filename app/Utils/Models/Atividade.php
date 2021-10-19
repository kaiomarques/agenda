<?php
namespace App\Utils\Models;

use App\Utils\Model;

/**
 * @property string $descricao
 * @property string $recibo
 * @property int $status
 * @property string $periodo_apuracao
 * @property string $inicio_aviso
 * @property string $limite
 * @property string $tipo_geracao
 * @property int $regra_id
 * @property int $emp_id
 * @property int $estemp_id
 * @property string $estemp_type
 * @property int $retificacao_id
 * @property string $data_aprovacao
 * @property int $usuario_aprovador
 * @property int $usuario_entregador
 * @property string $vlr_recibo_1
 * @property string $vlr_recibo_2
 * @property string $vlr_recibo_3
 * @property string $vlr_recibo_4
 * @property string $vlr_recibo_5
 * @property string $status_cliente
 * @property int $cliente_aprovador
 * @property string $data_aprovacao_cliente
 * */
class Atividade extends Model {

	protected $table    = 'atividades';
	protected $fillable = [
		'descricao' => 'STRING',
		'recibo' => 'STRING',
		'status' => 'STRING',
		'periodo_apuracao' => 'PERIODO',
		'inicio_aviso' => 'STRING',
		'limite' => 'STRING',
		'tipo_geracao' => 'STRING',
		'regra_id' => 'ID',
		'emp_id' => 'ID',
		'estemp_id' => 'ID',
		'estemp_type' => 'STRING',
		'retificacao_id' => 'ID',
		'data_aprovacao' => 'DATE',
		'usuario_aprovador' => 'ID',
		'usuario_entregador' => 'ID',
		'vlr_recibo_1' => 'MOEDA',
		'vlr_recibo_2' => 'MOEDA',
		'vlr_recibo_3' => 'MOEDA',
		'vlr_recibo_4' => 'MOEDA',
		'vlr_recibo_5' => 'MOEDA',
		'status_cliente' => 'STRING',
		'cliente_aprovador' => 'ID',
		'data_aprovacao_cliente' => 'STRING'
	];

	public function onAfterInsert() {

	}

	public function onAfterUpdate() {

	}


}