<?php
// phpinfo(1);

/* Informa o nível dos erros que serão exibidos */
error_reporting(E_ALL);
 
/* Habilita a exibição de erros */
ini_set("display_errors", 1);

shell_exec('Invoke-Expression -Command "cmdkey /add:rgbravodiag.file.core.windows.net /user:Azure\rgbravodiag /pass:ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw=="');

// system("cmdkey /add:rgbravodiag.file.core.windows.net /user:Azure\rgbravodiag /pass:ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw== >nul 2>&1");

$path = '\\\rgbravodiag.file.core.windows.net\\fileserver-prd';

$user = "Azure\\rgbravodiag";
$pass = "ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw==";
$drive_letter = "S";

system("net use ".$drive_letter.": \"".$path."\" ".$pass." /user:".$user." /persistent:no>nul 2>&1");

$location = $drive_letter.":/storagebravobpo";



echo "<pre>";
print_r($location);
if ($handle = opendir($location)) {
	while (false !== ($entry = readdir($handle))) {
		echo "<br> $entry";
	}
	closedir($handle);
}
echo "</pre>";

phpinfo();