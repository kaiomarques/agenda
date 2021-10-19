<?php

// echo Shell_Exec ('powershell.exe -executionpolicy bypass -NoProfile -Command "Get-Process | ConvertTo-Html"');

// echo shell_exec('Invoke-Expression -Command "cmdkey /add:rgbravodiag.file.core.windows.net /user:Azure\rgbravodiag /pass:ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw==" ');

echo shell_exec('cmdkey /add:rgbravodiag.file.core.windows.net /user:Azure\\rgbravodiag /pass:ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw== ');
// $pathStorage = "rgbravodiag.file.core.windows.net";
// $pathFolder = "fileserver-prd";
// $userDomain = "Azure";
// $user = "Azure\\rgbravodiag";
// $pass = "ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw==";
$drive_letter = "w";


echo shell_exec('net use '.$drive_letter.': \\\\rgbravodiag.file.core.windows.net\\fileserver-prd\\storagebravobpo ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw== /user:Azure\\rgbravodiag /persistent:no ');

echo '<pre>';
echo shell_exec('dir '.$drive_letter.':\BK_13574594\results');
echo '</pre>';


// $location = $drive_letter.":\\storagebravobpo";

