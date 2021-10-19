<?php
namespace App\Utils;

use stdClass;

/**
 * @method Model current()
 * @method Model find($index)
 * @method Model first()
 * @method Model last()
 * @method Model next()
 * @method Model pop()
 * @method Model shift()
 * */
class CollectionModel extends Collection {

/** @param Model[] $haystack */
    function __construct(array $haystack = []) {

        $this->val    = [];
        $this->needle = -1;
        $this->typing = 'App\Utils\Model';

        foreach ($haystack as $model) {
        	$this->add($model);
        }

    }

    /**
     * @param Model $object
     * @return boolean */
    public function isModel($object) {

    	if (is_object($object)) {

    		$parents = class_parents($object);

    		if (is_array($parents) && in_array($this->typing, $parents)) {
				return true;
    		}

    	}

    	return false;
    }

/**
 * @desc adiciona um elemento na collection.
 * @param Model $object
 * @param callable $callback (opcional)
 * @return void */
    public function add($object, $callback = null) {

    	$type = gettype($object);

    	if (!$this->isModel($object)) {
    		throw new \Exception(sprintf('%s expects parameter 1 to be %s, %s given', __METHOD__, $this->typing, $type));
    	}

    	$add = true;

    	if (is_callable($callback)) {
    		$add = call_user_func_array($callback, [$object, $this]);
    	}

    	if ($add) {
    		array_push($this->val, $object);
    	}

    }

    /**
     * @param array $cols
     * @return CollectionModel */
    public function filter($cols = []) {

    	$novo  = new CollectionModel();
   		$total = count($cols);

    	foreach ($this->val as $linha) {

    		$qt = 0;

    		foreach ($cols as $name => $value) {

    			if ($this->getTupleValue($linha, $name) == $value) {
    				$qt++;
    			}

    		}

    		if ($qt == $total) {
    			$novo->add($linha);
    		}

    	}

    	return $novo;
    }

/**
 * @desc substitui um elemento na collection.
 * @param int $index posição na collection a ser substituida.
 * @param Model $object elemento a ser substituido na posição $index
 * @return void */
    public function replace($index, $object) {

        if ($this->exists($index)) {

        	$type = gettype($object);

        	if ($this->isModel($object)) {
            	$this->val[$index] = $object;
        	}
        	else {
        		throw new \Exception(sprintf('%s expects parameter 2 to be %s, %s given', __METHOD__, $this->typing, $type));
        	}

        }

    }

/** @return stdClass[] */
    public function toArray() {

    	$collection = $this->val;
		$array      = [];
		$index      = 0;

		/** @var Model $model */

		foreach ($collection as $model) {

			$array[$index] = new stdClass();

			foreach ($model->getAttributes() as $name) {
				$array[$index]->$name = $model->__get($name);
			}

			$index++;
		}

        return $array;
    }

}
