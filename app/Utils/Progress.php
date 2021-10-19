<?php
namespace App\Utils;

class Progress {

	private $todos, $success, $failed;

	public function __construct() {
		$this->todos   = 0;
		$this->success = 0;
		$this->failed  = 0;
	}

	public function getAll() {
		return [
			'todos'   => $this->getTodos(),
			'success' => $this->getSuccess(),
			'failed'  => $this->getFailed()
		];
	}

	public function getTodos() {
		return $this->todos;
	}

	public function getSuccess() {
		return $this->success;
	}

	public function getFailed() {
		return $this->failed;
	}

	public function incTodos($count) {
		$this->todos += $count;
	}

	public function incSuccess($count) {
		$this->success += $count;
	}

	public function incFailed($count) {
		$this->failed += $count;
	}

}
