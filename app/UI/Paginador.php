<?php
namespace App\UI;

use App\Utils\Database;
use App\Utils\Variavel;

/**
 * @desc uma classe generica para geração de grids e uso do datatables JS no final para funcionar o Excel
 * (semelhante a classe de Felipe Gregorio do SECSP [muito legal, inclusive])
 * */
class Paginador {

	private $sql;
	private $columns; // nomes de campo e valores dos titulos dos ths
	private $id; // id do grid (para o datatable)

	public function __construct($id, $sql, $columns) {



	}

	public function create() {


		// TODO: usar o JS databales no fim da geracao do grid (html)
	}

}
