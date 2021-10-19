<?php
namespace App\Utils;

use ArrayObject;
use Exception;

/** @desc uma alternativa mais simples ao Eloquent */
abstract class Model extends ArrayObject {

	protected $table;
	protected $pkname   = 'id';
	protected $exists   = false;
	protected $fillable = []; // irá usar tipos e retornar objetos App\Utils\Variavel (nos metodos get?() )
	protected $tuple    = [];
// 	protected $parents  = []; // TODO:
// 	protected $childs   = []; // TODO:

	// TODO: mudar nome desta classe para Entity
	// TODO: [ usar classe Variavel nos campos e implementar validação (com esta classe sendo chamada Model)

	public function __construct() {

		$this->fillable[$this->pkname] = 'ID';

		$num = func_num_args();

		if ($num == 2) {
			$this->setBy(func_get_arg(0), func_get_arg(1));
		}
		else if ($num == 1) {

			$id   = func_get_arg(0);
			$type = gettype($id);

			if ($type == 'array' || $type == 'object') {
				$this->setBy($id);
			}
			else {
				$this->setById($id);
			}

		}

	}

	public function __get($name) {

		if (!$this->hasKey($name)) {
			throw new Exception(sprintf('%s:ERROR:Campo %s não existe no array fillable', __METHOD__, $name));
		}

		return (isset($this->tuple[$name]) ? $this->tuple[$name] : null);
	}

	public function __set($name, $value) {

		if (!$this->hasKey($name)) {
			throw new Exception(sprintf('%s:ERROR:Campo %s não existe no array fillable', __METHOD__, $name));
		}

		$this->tuple[$name] = $value;
		return $this;
	}

	public function __toString() {
		return json_encode($this->tuple);
	}

	/** ============================================================================================ **/

	/**
	 * @desc retorna true se o registro foi encontrado no BD
	 * @return boolean */
	public function exists() {
		return $this->exists;
	}

	/** @return int */
	public function getId() {
		return ($this->exists ? intval($this->__get($this->getPKName())) : 0);
	}

	/**
	 * @desc retorna o nome do campo PK da tabela
	 * @return string */
	public function getPKName() {
		return $this->pkname;
	}

	public function getSQL($where = [], $orderby = '') {
		$where = is_array($where) && !empty($where) ? $where : ['1=1'];
		return sprintf("SELECT %s FROM %s WHERE %s %s", implode(', ', $this->getAttributes()), $this->getTable(), implode(' AND ', $where), ($orderby ? "ORDER BY {$orderby}" : ''));
	}

	/**
	 * @desc retorna o nome da tabela do objeto
	 * @return string */
	public function getTable() {
		return $this->table;
	}

	/** @return array */
	public function getAttributes() {
		return array_keys($this->fillable);
	}

	protected function setId($id) {
		$this->__set($this->pkname, $id);
	}

	/** ============================================================================================ **/

	/**
	 * @example setBy(array $fields)
	 * @example setBy(stdClass $bdTuple)
	 * @example setBy(string $field, string $value)
	 * @return Model */
	public function setBy() {

		$this->exists = false;

		$num   = func_num_args();
		$where = [];

		if ($num >= 2) {

			$field = func_get_arg(0);
			$value = func_get_arg(1);

			if ($this->hasKey($field) && !is_array($value)) {
				array_push($where, sprintf("%s = '%s'", $field, Database::auto_set($value)));
			}

		}
		else if ($num == 1) {

			$arg0  = func_get_arg(0);
			$type0 = gettype($arg0);

			if ($type0 == 'object') {
				$this->exists = true;
				$this->tuple  = get_object_vars($arg0);
			}
			else if ($type0 == 'array') {

				foreach ($arg0 as $field => $value) {

					if ($this->hasKey($field) && !is_array($value)) {
						array_push($where, sprintf("%s = '%s'", $field, Database::auto_set($value)));
					}

				}

			}

		}

		if (!empty($where)) {
			$this->setBy(Database::fetchRow($this->getSQL($where)));
		}

		return $this;
	}

	/**
	 * @param int $id
	 * @return Model
	 * */
	public function setById($id) {
		return $this->setBy($this->getPKName(), $id);
	}

	public function unsetVars() {
		$this->tuple  = null;
		$this->exists = false;
	}

	/** ============================================================================================ **/

	/** @return boolean */
	public function hasKey($attName) {

		$atts = array_keys($this->fillable);

		if (in_array($attName, $atts)) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $classname (ex: App\Utils\Models\Produto, somente 'Produto')
	 * @return Model */
	public function getModel($classname) {

		$classname = implode('\\', ['App', 'Utils', 'Models', $classname]);

		if (!class_exists($classname)) {
			throw new \Exception(sprintf('ERROR in %s:: class %s not exists', __METHOD__, $classname));
		}

		return new $classname();
	}

	/**
	 * @desc an Alias for belongsTo
	 * @return Model */
	public function hasParent($parentClassName, $thisFKName) {

		$error = null;

		if ($this->exists) {

			if ($this->hasKey($thisFKName)) {
				$parent = $this->getModel($parentClassName)->setById($this->$thisFKName);
			}
			else {
				$error = "Este Model nao possui a FK ({$thisFKName})";
			}

		}
		else {
			$error = 'Este Model ainda nao foi buscado no BD';
		}

		if ($error) {
			throw new \Exception(sprintf('ERROR in %s::%s', __METHOD__, $error));
		}

		return $parent;
	}

	/**
	 * @desc an Alias for hasMany
	 * @return Model[] */
	public function hasChilds($childClassName, $childFKName, $orderby = null) {

		$error  = null;
		$models = [];

		if ($this->exists) {

			$child = $this->getModel($childClassName);

			if ($child->hasKey($childFKName)) {

				$dataset = Database::fetchAll($child->getSQL([sprintf("%s = '%s'", $childFKName, $this->getId())], $orderby));

				foreach ($dataset as $object) {
					array_push($models, $this->getModel($childClassName)->setBy($object));
				}

			}
			else {
				$error = "Este Model ({$childClassName}) nao possui a FK ({$childFKName})";
			}

		}
		else {
			$error = 'Este Model ainda nao foi buscado no BD';
		}

		if ($error) {
			throw new \Exception(sprintf('ERROR in %s::%s', __METHOD__, $error));
		}

		return $models;
	}

	/**
	 * @desc an Alias for hasOne
	 * @return Model */
	public function hasChild($childClassName, $childFKName) {

		$error = null;

		if ($this->exists) {

			$child = $this->getModel($childClassName);

			if ($child->hasKey($childFKName)) {
				$child = $child->setBy($childFKName, $this->getId());
			}
			else {
				$error = "Este Model ({$childClassName}) nao possui a FK ({$childFKName})";
			}

		}
		else {
			$error = 'Este Model ainda nao foi buscado no BD';
		}

		if ($error) {
			throw new \Exception(sprintf('ERROR in %s::%s', __METHOD__, $error));
		}

		return $child;
	}

	/**
	 * @param string[] $values
	 * @param callable $onInsert
	 * @return boolean */
	public function insert($values, $onInsert = null) {

		$ok = Database::table($this->table)->insert(Database::auto_set($values));

		if ($ok) {

			$tupla = Database::fetchRow($this->getSQL([], "{$this->pkname} DESC"));

			if ($tupla != null) {

				$this->setBy($tupla); // refresh para pegar o id do registro recem cadastrado

				if ($this->exists) {
					$this->callback($onInsert);
				}
				else {
					throw new Exception(sprintf('ERROR in %s #2::Ocorreu um erro ao atualizar os dados do registro', __METHOD__));
				}

			}
			else {
				throw new Exception(sprintf('ERROR in %s::Ocorreu um erro ao atualizar os dados do registro', __METHOD__));
			}

		}

		return $ok;
	}

	/**
	 * @param string[] $values
	 * @param callable $onUpdate
	 * @return boolean */
	public function update($values, $onUpdate = null) {

		if ($this->exists) {
			Database::table($this->table)->where($this->getPKName(), '=', $this->getId())->update(Database::auto_set($values));
			$this->callback($onUpdate);
			return true;
		}
		else {
			throw new Exception(sprintf('ERROR in %s::Este Model ainda nao foi buscado no BD', __METHOD__));
		}

		return false;
	}

	public function offsetExists($index) {
		return isset($this->tuple[$index]);
	}

	public function offsetGet($index) {
		return $this->__get($index);
	}

	public function offsetSet($index, $newval) {
		$this->__set($index, $newval);
	}

	public function offsetUnset($index) {
		unset($this->tuple[$index]);
	}

	/**
	 * @param callable $function
	 * @return void */
	public function callback($function) {

		if ($function != null && is_callable($function)) {
			call_user_func_array($function, [$this]);
		}

	}


}
