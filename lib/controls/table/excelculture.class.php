<?php
namespace ScavixWDF\Controls\Table;

use DateTime;
use ScavixWDF\Localization\CultureInfo;
use ScavixWDF\Localization\Localization;

/**
 * @internal Overrides some methods for Excel compatibility.
 * @suppress PHP0413
 */
class ExcelCulture extends CultureInfo
{
	private static $FORMAT_MAP = [];

    var $LanguageCode;
	
	static function FromCode($code)
	{
		$res = new ExcelCulture();
		$ci = Localization::getCultureInfo($code);
		
		foreach( get_object_vars($ci) as $prop=>$value )
			$res->$prop = $value;
	
        $res->LanguageCode = $ci->ResolveToLanguage()->Code;
        
		return $res;
	}
	
	function FormatDate($date, $format_id = false, $convert_to_timezone = 'default')
	{
		$date = $this->_ensureTimeStamp($date);
        $timeStart = new DateTime();
		return \PhpOffice\PhpSpreadsheet\Shared\Date::formattedPHPToExcel(date("Y",$date),date("m",$date),date("d",$date));
	}
	
	function FormatTime($date, $format_id = false, $convert_to_timezone = 'default')
	{
		$date = $this->_ensureTimeStamp($date);
        $timeStart = new DateTime();
		return fmod(\PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($timeStart->setTimestamp($date)),1);
	}
	
	function FormatDateTime($date, $format_id = false, $convert_to_timezone = 'default')
	{
		$date = $this->_ensureTimeStamp($date);
        $timeStart = new DateTime();
		return \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($timeStart->setTimestamp($date));
	}
	
	function FormatInt($number)
	{
		return intval($number);
	}
	
	function FormatNumber($number, $decimals = false, $use_plain = false)
	{
		return doubleval($number);
	}
	
	function FormatCurrency($amount, $use_plain = false, $only_value = false, $escape_group_separator = true)
	{
		return doubleval($amount);
	}
	
	function GetExcelFormat($cellformat)
	{
		$f = strtolower($cellformat->GetFormat());
		if( isset(self::$FORMAT_MAP[$f]) )
			return self::$FORMAT_MAP[$f];
		switch( $f )
		{
			case 'time':
			case 'duration':
				self::$FORMAT_MAP[$f] = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_TIME4;
				break;
			case 'date':
				self::$FORMAT_MAP[$f] = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DDMMYYYY;
				break;
			case 'datetime':
				self::$FORMAT_MAP[$f] = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME;
				break;
			case 'currency':
				$res = '#'.
					$this->CurrencyFormat->GroupSeparator.'##0'.
					$this->CurrencyFormat->DecimalSeparator.
					str_repeat('0', $this->CurrencyFormat->DecimalDigits);
				$pos = str_replace('%v', $res, $this->CurrencyFormat->PositiveFormat);
				$neg = str_replace('%v', $res, $this->CurrencyFormat->NegativeFormat);
				self::$FORMAT_MAP[$f] = "$pos;$neg";
				break;
			case 'int':
			case 'integer':
				$res = '#'.
					$this->NumberFormat->GroupSeparator.'##0';
				$pos = $res;
				$neg = str_replace('%v', $res, $this->NumberFormat->NegativeFormat);
				self::$FORMAT_MAP[$f] = "$pos;$neg";
				break;
			case 'float':
			case 'double':
				$res = '#'.
					$this->NumberFormat->GroupSeparator.'##0'.
					$this->NumberFormat->DecimalSeparator.
					str_repeat('0', $this->NumberFormat->DecimalDigits);
				$pos = $res;
				$neg = str_replace('%v', $res, $this->NumberFormat->NegativeFormat);
				self::$FORMAT_MAP[$f] = "$pos;$neg";
				break;
            case 'text':
				self::$FORMAT_MAP[$f] = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT;
                break;
			default:
                if( is_callable($f) )
                {
                    self::$FORMAT_MAP[$f] = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT;
                    break;
                }
    			log_warn("Unknown column format: $f");
				self::$FORMAT_MAP[$f] = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL;
				break; 
		}
		return self::$FORMAT_MAP[$f];
	}
}