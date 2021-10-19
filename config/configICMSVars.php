<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Default Filesystem Disk for storagebravobpo
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default drive letter disk that should be used
	| by the GuiaicmsController.php script. The default production value is "F:" for driver
	| letter
	|
	| Supported: "F" for production environment, "W:" for homolog and "D:" for development
	|
	*/
	
	'driver_letter' => env('D:'),
		
	/*
	|--------------------------------------------------------------------------
	| Default Filesystem Disk for storagebravobpo
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default drive letter disk that should be used
	| by the GuiaicmsController.php script. The default production value is "F:" for driver
	| letter
	|
	| Supported: "F" for production environment, "W:" for homolog and "D:" for development
	|
	*/
	
	'wamp' => [
		'php_cwd' => 'D:\wamp64\bin\php\php5.6.40\php.exe',
		'path_root'   => 'D:\\BravoBPO\\Projetos\\www\\prd\\',
		'timezone_brt'   => 'America/Belem',
		'timezone_brst'   => 'America/Sao_Paulo',
	],
];