<?php
namespace App\Utils;

/** classe usada para armazenar registros vindos do Banco de dados com paginação */
class Paginator {

	private $total, $from, $to, $pages, $page, $all;

	public function getTotal() {
		return $this->total;
	}

	public function getFrom() {
		return $this->from;
	}

	public function getTo() {
		return $this->to;
	}

	public function getPages() {
		return $this->pages;
	}

	public function getPage() {
		return $this->page;
	}

	/** @return CollectionModel */
	public function getAll() {
		return $this->all;
	}

	public function setTotal($total) {
		$this->total = $total;
	}

	public function setFrom($from) {
		$this->from = $from;
	}

	public function setTo($to) {
		$this->to = $to;
	}

	public function setPages($pages) {
		$this->pages = $pages;
	}

	public function setPage($page) {
		$this->page = $page;
	}

	/** @param CollectionModel $all */
	public function setAll($all) {
		$this->all = $all;
	}

}
