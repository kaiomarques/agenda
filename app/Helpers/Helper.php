<?php
namespace App\Helpers;

use DateTime;
use Carbon\Carbon;

class Helper
{
    public static function getStorageBravoAzure() {
		// $pathStorage = "rgbravodiag.file.core.windows.net";
		// $pathFolder = "fileserver-prd";
		// $userDomain = "Azure";
		// $user = "Azure\\rgbravodiag";
		// $pass = "ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw==";
		$drive_letter = "T";
		
		shell_exec('Invoke-Expression -Command "cmdkey /add:rgbravodiag.file.core.windows.net /user:Azure\rgbravodiag /pass:ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw==" ');

		// shell_exec('cmdkey /add:rgbravodiag.file.core.windows.net /user:Azure\\rgbravodiag /pass:ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw== ');
		
		shell_exec('net use '.$drive_letter.': \\\\rgbravodiag.file.core.windows.net\\fileserver-prd ehMSP6Q5YzXVXwXG/JHPHbrplhP3Uc29Z60uWfo/DHCVZ3buvBiqE/82b1yZbnYNISEneptLselj0Zq5GkeKiw== /user:Azure\rgbravodiag /persistent:no ');

		$location = $drive_letter.":/storagebravobpo";
		
		return $location;
	}
	
	public static function dateFormatFromUTC($date, $tz) {
		$carbon = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');   // specify UTC otherwise defaults to locale time zone as per ini setting
		$carbon->tz = $tz;   // ... set to the current users timezone

		return $carbon->format('d/m/Y H:i:s');
	}
	/**
	 * function to add a $time in a $date
	 *
	 * @param time $time
	 * @param datetime $date
	 * @return datetime
	 */
	public static function addTimeToUTCDate($time, $date) {
		$arrTime = explode(":", $time);
		
		$hours = $arrTime[0];
		$minutes = $arrTime[1];
		$seconds = $arrTime[2];

		$totalSeconds = (($hours*3600) + ($minutes*60) + $seconds);

		$curTimestamp = strtotime($date);
		$newTimestamp = strtotime($date)+$totalSeconds;
		
		return date('Y-m-d H:i:s', $newTimestamp);
	}
	/**
	 * function to calculate Easter Date
	 * 
	 * The PHP 4+ have this function natively https://www.php.net/manual/pt_BR/function.easter-date.php
	 * but, I prefer a form of poetic license than a native function to perform the calculation of Easter Date
	 *
	 * @param integer $year
	 * @return date $easterDate (Y-m-d)
	 */
	public static function findEasterDate($year)
	{
		// In honor of Carl Friedrich Gauss
		if ($year >= 1582 && $year <= 1699) {
			$X = 22;
			$Y = 2;
		}
		if ($year >= 1700 && $year <= 1799) {
			$X = 23;
			$Y = 3;
		}
		if ($year >= 1800 && $year <= 1899) {
			$X = 23;
			$Y = 4;
		}
		if ($year >= 1900 && $year <= 2099) {
			$X = 24;
			$Y = 5;
		}
		if ($year >= 2100 && $year <= 2199) {
			$X = 24;
			$Y = 6;
		}
		if ($year >= 2200 && $year <= 2299) {
			$X = 25;
			$Y = 7;
		}

		$a = ($year % 19);
		$b = ($year % 4);
		$c = ($year % 7);
		$d = ((19 * $a + $X) % 30);
		$e = ((2 * $b + 4 * $c + 6 * $d + $Y) % 7);

		if (($d + $e) > 9) {
			$day = ($d + $e - 9);
			$month = 4;
		} else {
			$day = ($d + $e + 22);
			$month = 3;
		}
		if (($day == 26) AND ($month == 4)) {
			$day = 19;
		}
		if (($day == 25) AND ($month == 4) AND ($d == 28) AND ($a > 10)) {
			$day = 18;
		}

		$date = sprintf("%04d-%02d-%02d", $year, $month, $day);

		// $dt = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
		$dt = DateTime::createFromFormat('Y-m-d', $date, new DateTimeZone('UTC'));

		return $dt->format('Y-m-d');
	}

	/**
	 * get an array based on Laravel stdClass filtered by collumn
	 *
	 * @param object $stdObjectClass
	 * @param string $collumn
	 *
	 * @return array $arrReturn
	 */
	public static function arrayByCollumnFromStdClass($stdObjectClass, $collumn)
	{
		$arrReturn = array();

		foreach ($stdObjectClass as $key => $value) {
			$arrReturn[] = $value->{$collumn};
		}

		return $arrReturn;
	}
	/**
	 * get day interval between four dates
	 *
	 * @param date $firstAvailableActivityDate
	 * @param date $lastAvailableActivityDate
	 * @param date $firstDayOfAnalystAvailability
	 * @param date $lastDayOfAnalystAvailability
	 *
	 * @return array
	 */
	public static function getDayInterval (
		$firstAvailableActivityDate, 
		$lastAvailableActivityDate, 
		$firstDayOfAnalystAvailability, 
		$lastDayOfAnalystAvailability
	) {
		$arrReturn = array();
		// date interval for activity
		$d1 = $firstAvailableActivityDate;
		$d2 = $lastAvailableActivityDate;
		// date interval of analyst availability
		$a1 = $firstDayOfAnalystAvailability;
		$a2 = $lastDayOfAnalystAvailability;
		// A1 and A2 inside of D1 and D2
		if ($d1 <= $a1 && $a1 <= $a2 && $a2 <= $d2) {
			$arrReturn['status'] = 'OK';
			$arrReturn['msg'] = "Intervalo compatível, use {$a1} e {$a2}";
			$arrReturn['startDate'] = $a1;
			$arrReturn['endDate'] = $a2;
		} elseif ($d1 == $a1 && $a1 <= $a2 && $a2 < $d2) {
			$arrReturn['status'] = 'OK';
			$arrReturn['msg'] = "Intervalo compatível, use {$a1} e {$a2}";
			$arrReturn['startDate'] = $a1;
			$arrReturn['endDate'] = $a2;
		} elseif ($a1 < $d1 && $d1 <= $a2 && $a2 < $d2) {
			$arrReturn['status'] = 'OK';
			$arrReturn['msg'] = "Intervalo compatível, use {$d1} e {$a2}";
			$arrReturn['startDate'] = $d1;
			$arrReturn['endDate'] = $a2;
		} elseif ($a1 < $d1 && $d1 == $a2) {
			$arrReturn['status'] = 'OK';
			$arrReturn['msg'] = "Intervalo compatível, use {$d1} e {$a2}";
			$arrReturn['startDate'] = $d1;
			$arrReturn['endDate'] = $a2;
		} elseif ($d1 < $a1 && $a1 <= $a2 && $a2 == $d2) {
			$arrReturn['status'] = 'OK';
			$arrReturn['msg'] = "Intervalo compatível, use {$a1} e {$a2}";
			$arrReturn['startDate'] = $a1;
			$arrReturn['endDate'] = $a2;
		} elseif ($d1 < $a1 && $a1 <= $d2 && $d2 < $a2) {
			$arrReturn['status'] = 'OK';
			$arrReturn['msg'] = "Intervalo compatível, use {$a1} e {$d2}";
			$arrReturn['startDate'] = $a1;
			$arrReturn['endDate'] = $d2;
		} elseif ($d1 < $a1 && $a1 == $d2 && $d2 < $a2) {
			$arrReturn['status'] = 'OK';
			$arrReturn['msg'] = "Intervalo compatível, use {$a1} e {$d2}";
			$arrReturn['startDate'] = $a1;
			$arrReturn['endDate'] = $d2;
		} else {
			$arrReturn['status'] = 'ERR';
			$arrReturn['msg'] = "O tempo de atividade disponivel do analista não é compatível com o intervalo de datas de atividade informadas para esta atividade e período de apuracao";
			$arrReturn['startDate'] = '0000-00-00';
			$arrReturn['endDate'] = '0000-00-00';
		}
		return $arrReturn;
	}
	/**
	 * function to get next business day
	 *
	 * @param date $date
	 * @param array $arrNationalHolidays
	 * @return void
	 */
	public static function getNextBusinessDay($date, $arrHolidayDates) {
		// get next date and convert it to UNIX TIMESTAMP
		$timestamp = strtotime($date);
		$nextTimestamp = $timestamp + 86400;
		// check if $nextTimestamp is a day of weekend
		$nextTimestamp = Helper::checkDayOfWeekend($nextTimestamp);
		// check if $nextTimestamp is a Holiday
		if (in_array(date('Y-m-d', $nextTimestamp), $arrHolidayDates)) {
			// If $nextTimestamp is a Holiday, add more 1 day to obtain next day and check again
			// if this day isn´t Saturday or Sunday
			$nextTimestamp = $nextTimestamp + 86400;
			$nextTimestamp = Helper::checkDayOfWeekend($nextTimestamp);

			return date('Y-m-d', $nextTimestamp);
		} else {
			return date('Y-m-d', $nextTimestamp);
		}
	}
	/**
	 * function to check if de $timestamp is one Saturday or Sunday
	 * and return de next Monday
	 *
	 * @param timestamp $timestamp
	 * @return timestamp
	 */
	public static function checkDayOfWeekend($timestamp) {
		// calculates which day of the week is the $date
		// The result 'll be a numeric value:
		// 1 -> Monday ... 7 -> Sunday
		$day = date('N', $timestamp);
		// If the day is Saturday (6) or Sunday (7), calculate the
		// next Monday
		if ($day >= 6) {
			return $timestamp + ((8 - $day) * 3600 * 24);
		} else {
			// If day is not Saturday or Sunday, keep the entry date
			return $timestamp;
		}
	}
}