<?php
namespace App\Utils;

class Folder {

	/** @var string $fullpath */
	public $fullpath;

	/** @var string $path */
	public $path;

	/** @var string $filename */
	public $filename;

	/** @var string $name */
	public $name;

	/** @var string $extension */
	public $extension;

	/** @var string $type ('dir' ou 'file') */
	public $type;

	/** @var string $glue (a barra que consta no $path lido (/ ou \) */
	public $glue;

	public function getNewName($name, $extension) {
		return implode($this->glue, [$this->path, implode('', [$name, $extension])]);
	}
	
	/** 
	 * @desc apaga um arquivo ou pasta caso exista
	 * @return void */
	public function delete() {
		
		if (file_exists($this->fullpath)) {
			unlink($this->fullpath);
		}
		
	}

	/**
	 * @desc lê um diretorio ($onEach pode ser usado para filtrar os arquivos e/ou pastas que deseja)
	 * @param string $path
	 * @param callable $onEach
	 * @return Folder[] */
	public static function readDir($path, $onEach = null) {

		$files = [];

		foreach (new \DirectoryIterator($path) as $f) {

			if (!$f->isDot()) {

				$type      = null;
				$extension = null;
				$name      = $f->getFilename();

				if ($f->isDir()) {
					$type = 'dir';
				}
				else if ($f->isFile()) {

					$type = 'file';

					if (strpos($name, '.') !== false) {
						$aux       = explode('.', $name);
						$extension = strtolower(array_pop($aux));
						$name      = implode('.', $aux);
					}

				}

				$glue   = strpos($path, '\\') !== false ? '\\' : '/';
				$params = new Folder();
				$params->glue      = $glue;
				$params->fullpath  = implode($glue, [$path, $f->getFilename()]);
				$params->path      = $path;
				$params->filename  = $f->getFilename();
				$params->name      = $name;
				$params->extension = $extension;
				$params->type      = $type;

				$add = ($onEach != null && is_callable($onEach) ? call_user_func_array($onEach, [$f, $params]) : true);

				if ($add) {
					array_push($files, $params);
				}

			}

		}

		return $files;
	}

	public static function dowloadFilePDF($filename) {

		$name = explode('/', $filename);
		$name = array_pop($name);

// 		header("Content-Disposition: attachment; filename=" . urlencode($file));
// 		header("Content-Type: application/pdf");
// 		header("Content-Type: application/download");
// 		header("Content-Description: File Transfer");
// 		header("Content-Length: " . filesize($file));

		header("Content-type:application/pdf");
		header("Content-Disposition:attachment;filename={$name}");

		readfile($filename);
	}

}
