<?php
namespace App\Utils;

class Singleton {

	protected static $progress = null;

	/** @return Progress */
	public static function getProgress() {

		if (!is_object(self::$progress)) {
			self::$progress = new Progress();
		}

		return self::$progress;
	}

}
