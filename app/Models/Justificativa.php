<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Utils\Database;
use stdClass;

class Justificativa extends Model {

	public static $cadLimit = 5;
	public $timestamps      = false;
	protected $table        = 'justificativaentregra';
    protected $fillable     = [
        'id_empresa',
		'id_tributo',
        'periodo_apuracao',
		'justificativa'
    ];

    public function getTableName() {
    	return $this->table;
    }

    public function retrieve($id_empresa) {
    	return Database::fetchAll(sprintf("
    		SELECT
    			j.*, t.nome tributo_nome
    		FROM %s j
    		LEFT JOIN tributos t ON (t.id = j.id_tributo)
    		WHERE id_empresa = '%s'
    		ORDER BY t.nome, periodo_apuracao ASC", $this->getTableName(), $id_empresa));
    }

    /**
     * @param int $id_empresa
     * @param int $id_tributo
     * @param int $periodo_apuracao (012021)
     * @return stdClass (array) */
    public function getJustificativas($id_empresa, $id_tributo, $periodo_apuracao) {

    	$sql = sprintf("
    		SELECT * FROM %s
    		WHERE id_empresa     = '%s'
    		AND id_tributo       = '%s'
    		AND periodo_apuracao = '%s'
    		ORDER BY id ASC", $this->getTableName(), $id_empresa, $id_tributo, $periodo_apuracao);

    	return Database::fetchPairs($sql, null, 'justificativa');
    }

    public function isInLimit($id_empresa, $id_tributo, $periodo_apuracao) {
    	$qt = count($this->getJustificativas($id_empresa, $id_tributo, $periodo_apuracao));
    	return ($qt < Justificativa::$cadLimit);
    }

	public function empresa() {
		return $this->belongsTo('App\Models\Empresa', 'id_empresa');
	}

	public function tributo() {
		return $this->belongsTo('App\Models\Empresa', 'id_tributo');
	}
/*
    public function municipio() {
		return $this->belongsToMany('App\Models\Tributo');
        return $this->belongsTo('App\Models\Municipio','cod_municipio');
		return $this->hasMany('App\Models\Estabelecimento');
    }
*/
}
