<?php
namespace App\Utils;

use stdClass;

class Collection {

/** @var array $val Array contendo os elementos do tipo object genéricos. */
    protected $val;
/** @var int $needle ponteiro para percorrer o array. */
    protected $needle;

    protected $typing;

/** @param stdClass[] $haystack */
    function __construct(array $haystack = []) {

        $this->val    = [];
        $this->needle = -1;
        $this->typing = 'array';

        foreach ($haystack as $object) {
            $this->add($object);
        }

    }

// ====================================================================================================================

    public function getTupleValue($tupla, $columnName) {
    	return (isset($tupla[$columnName]) && $tupla[$columnName] !== '' ? $tupla[$columnName] : null);
    }

    public function getTupleNumber($tupla, $columnName) {
    	$value = $this->getTupleValue($tupla, $columnName);
    	return ($value !== null ? number_format($value, 2, ',', '.') : 0);
    }

// ====================================================================================================================

/**
 * @desc adiciona um elemento na collection.
 * @param array $object
 * @param callable $callback (opcional)
 * @return void */
    public function add($object, $callback = null) {

    	$type   = gettype($object);
    	$typing = $this->typing;

    	if ($type == 'object') {
    		$object = get_object_vars($object);
    	}

    	$type = gettype($object);

    	if ($type != $typing) {
    		throw new \Exception(sprintf('%s expects parameter 1 to be %s, %s given', __METHOD__, $typing, $type));
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
 * @desc retorna o elemento atual ou nulo.
 * @return array */
    public function current() {
        return $this->find($this->needle);
    }

/**
 * @desc itera a collection e executa uma função callback em cada elemento.
 * @param callable $callback
 * @return void */
    public function each($callback) {

        foreach ($this->val as $index => $object) {
            call_user_func_array($callback, [$object, $index, $this]);
        }

    }

/**
 * @desc checa se um elemento existe na collection.
 * @param int $index chave a ser checada.
 * @return bool */
    public function exists($index) {
        return isset($this->val[$index]);
    }

/**
 * @desc retorna o elemento da collection especificado em $index ou nulo se não encontrado.
 * @param int $index
 * @return array */
    public function find($index) {
        return ($this->exists($index) ? $this->val[$index] : null);
    }

/**
 * @desc retorna o primeiro elemento ou nulo se collection estiver vazia.
 * @return array */
    public function first() {
        return $this->find(0);
    }

    /**
     * @param array $cols
     * @return Collection */
    public function filter($cols = []) {

    	$novo  = new Collection();
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
 * @desc retorna os elementos de uma coluna especificada em $columnName. retorna um array vazio se a coluna não existir no elemento.
 * @param string $columnName
 * @param bool $preserveKeys retorna o array com as chaves preservadas.
 * @return array */
    public function getColumn($columnName, $preserveKeys = false) {

        $array = array();

        foreach ($this->val as $index => $linha) {

        	$el = $this->getTupleValue($linha, $columnName);

            if ($preserveKeys) {
                $array[$index] = $el;
            }
            else {
                array_push($array, $el);
            }

        }

        return $array;
    }

/**
 * @desc retorna o maior inteiro entre as colunas específicas em $attName (a coluna $attName deve ser do tipo integer ou retorna 0)
 * @return int */
    public function getMax($columnName) {

        $values = [];
        $id     = 0;

        foreach ($this->val as $linha) {

            $id = $this->getTupleValue($linha, $columnName);

            if (is_numeric($id)) {
                array_push($values, $id);
            }

        }

        rsort($values);

        return (isset($values[0]) ? ($values[0]) : 0);
    }

/**
 * @desc retorna o ultimo elemento ou nulo se collection estiver vazia.
 * @return array */
    public function last() {
        return $this->find(($this->length() - 1));
    }

/**
 * @desc retorna a quantidade de elementos da collection.
 * @return int */
    public function length() {
        return count($this->val);
    }

/**
 * @desc retorna o proximo elemento ou nulo se não for encontrado ou collection estiver vazia.
 * @return array */
    public function next() {
        $this->needle++;
        return $this->current();
    }

/**
 * @desc ordena os elementos da collection pelas colunas especificadas em $columnName.
 * @param string|string[] $columns
 * @example orderby(string $columnName)
 * @example orderby(string[] $columns)
 * @return void */
    public function orderby($columns) {

        $type = gettype($columns);

        if ($type == 'array') {

            foreach ($columns as $columnName) {
                $this->orderby($columnName);
            }

        }
        elseif ($type == 'string') {

            $split = explode(' ', $columns);

            if (isset($split[1])) {
                $orderby    = strtolower($split[1]);
                $columnName = $split[0];
            }
            else {
                $orderby    = 'asc';
                $columnName = $columns;
            }

            $aux  = array();
            $caux = array_keys($this->getColumn($columnName, true));

            // TODO: implementar ordenação por string data

            if ($orderby == 'desc') {
                arsort($caux);
            }
            else {
                asort($caux);
            }

            foreach ($caux as $index) {
                array_push($aux, $this->val[$index]);
            }

            $this->val = $aux;
        }

    }

/**
 * @desc remove o ultimo elemento e retorna-o. retorna null se collection estiver vazia.
 * @return array */
    public function pop() {
        return array_pop($this->val);
    }

/**
 * @desc remove um elemento da collection.
 * @param int $index indice do elemento a ser removido.
 * @return void */
    public function remove($index) {

        if ($this->exists($index)) {
            unset($this->val[$index]);
            $this->update();
        }

    }

/**
 * @desc substitui um elemento na collection.
 * @param int $index posição na collection a ser substituida.
 * @param array $object elemento a ser substituido na posição $index
 * @return void */
    public function replace($index, $object) {

        if ($this->exists($index)) {

        	$type = gettype($object);

        	if ($type == $this->typing) {
            	$this->val[$index] = $object;
        	}
        	else {
        		throw new \Exception(sprintf('%s expects parameter 2 to be %s, %s given', __METHOD__, $this->typing, $type));
        	}

        }

    }

/**
 * @desc retorna o indice de um elemento na collection pesquisando por $attName e $attValue (retorna -1 se a pesquisa não encontrar registro)
 * @param string $columnName
 * @param string $columnValue
 * @return int */
    public function searchIndex($columnName, $columnValue) {

        $index = -1;

       	foreach ($this->val as $x => $linha) {

       		if ($this->getTupleValue($linha, $columnName) == $columnValue) {
                $index = $x;
                break;
            }

        }

        return $index;
    }

/**
 * @desc remove o primeiro elemento e retorna-o. retorna null se collection estiver vazia.
 * @return array */
    public function shift() {
        return array_shift($this->val);
    }

/** @return stdClass[] */
    public function toArray() {
        return $this->val;
    }

/**
 * @desc atualiza as chaves do array.
 * @return void */
    protected function update() {

        $novo = [];

        foreach ($this->val as $object) {
            array_push($novo, $object);
        }

        $this->val = $novo;
    }

}
