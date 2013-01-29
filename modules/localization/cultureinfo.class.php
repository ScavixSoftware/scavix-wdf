<?php
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
class CultureInfo
{
	var $Code;
	var $ParentCode;
	var $Iso2;
	var $EnglishName;
	var $NativeName;

	var $DateTimeFormat;
	var $CurrencyFormat;
	var $NumberFormat;
	var $IsRTL;

	var $Region;
	var $Parent;

	var $DefaultDateFormat = DateTimeFormat::DF_SHORTDATE;
	var $DefaultTimeFormat = DateTimeFormat::DF_SHORTTIME;
	
	private $_alwaysConvertTimesToTimezone = false;
	
	public $CurrenyConversionFunction = false;
	
	function  __construct($code="",$parent="",$iso="",$english="",$native="",$rtl="")
	{
		$this->Code = $code;
		$this->ParentCode = $parent;
		$this->Iso2 = $iso;
		$this->EnglishName = $english;
		$this->NativeName = $native;
		$this->IsRTL = $rtl == "1";

//		$this->TimeZone = getTimeZone();
	}
	
	function __sleep()
	{
		$res = array();		
		foreach( get_object_vars($this) as $name=>$val)
		{
			switch( $name )
			{
				case '_alwaysConvertTimesToTimezone':
				case 'CurrenyConversionFunction':
					break;
				default:
					$res[] = $name;
					break;
			}
		}
		return $res;
	}

	protected function _ensureTimeStamp($date)
	{
		if( is_string($date) )
		{
			// check if a valid int is given (ex: '123123123')
			if( !preg_match('/[^0-9]+/',$date) )
				$date = $date + 0;
			else
				$date = strtotime($date);
		}
		elseif( $date instanceof DateTime )
			$date = intval($date->format('U'));
		return $date;
	}

	private function _ensureDateTimeFormat()
	{
		if( $this->DateTimeFormat )
			return $this->DateTimeFormat;

		$reg = $this->DefaultRegion();
		$ci = $reg->DefaultCulture();
		if( $ci->DateTimeFormat )
			return $ci->DateTimeFormat;
		return false;
	}

	function DefaultRegion()
	{
		if( isset($this->Region) )
			return $this->Region;
		$reg = internal_getRegionsForLanguage($this->Code);
		return $reg[0];
	}

	function GetRegions($only_codes=false)
	{
		$ci = $this->ResolveToLanguage();
		$regions = internal_getRegionsForLanguage($ci->Code);
		if( !$only_codes )
			return $regions;
		$res = array();
		foreach( $regions as $r )
			$res[] = $r->Code;
		return $res;
	}
	
	function OtherRegion($region_code)
	{
        if( $region_code instanceof RegionInfo )
        {
            $res = clone $this;
            $res->Region = $region_code;
            return $res;
        }
		$region_code = strtoupper($region_code);
		foreach( internal_getRegionsForLanguage($this->Code) as $r )
			if( $r->Code == $region_code )
			{
				$res = clone $this;
				$res->Region = $r;
				return $res;
			}
		return false;
	}

	function ResolveToLanguage()
	{
		$res = clone $this;
		while( isset($res->Parent) && !$res->IsNeutral() )
			$res = $res->Parent;
		return $res;
	}
    
    function EnsureRegion()
	{
        $res = clone $this;
		return $res->OtherRegion($res->DefaultRegion());
    }
    
	function IsNeutral()
	{
		return !isset($this->Region);
	}

	function IsChildOf($parent)
	{
		if( $this->IsNeutral() )
			return false;
		if( $parent instanceof CultureInfo )
			$parent = $parent->Code;
		if( isset($this->Parent->Code) )
			return $this->Parent->Code == $parent;
	}

	function IsParentOf($child)
	{
		if( is_string($child) )
			$child = Localization::getCultureInfo($child);
		if( !($child instanceof CultureInfo))
			return false; //system_die("Invalid Culture!");

		return $child->IsChildOf($this);
	}

	function SetTimezone($timezone, $alwaysConvertTimesToTimezone = false)
	{
		$this->TimeZone = $timezone;
		$this->_alwaysConvertTimesToTimezone = $alwaysConvertTimesToTimezone;
	}

	function FormatNumber($number, $decimals=false, $use_plain=false)
	{
		if( !$this->NumberFormat )
			return "No NumberFormat for {$this->Code}";
		return $this->NumberFormat->Format($number, $decimals, $use_plain);
	}

	function FormatInt($number)
	{
		return $this->FormatNumber($number, 0);
	}

	function FormatCurrency($amount, $use_plain=false, $only_value=false, $escape_group_separator=true)
	{
		if( !$this->CurrencyFormat )
			return "No CurrencyFormat for {$this->Code}";
			
		if( $this->CurrenyConversionFunction instanceof Closure )
		{
			$conv = $this->CurrenyConversionFunction;
			$amount = $conv($this,$amount);
		}

		return $this->CurrencyFormat->Format($amount, $use_plain, $only_value, $escape_group_separator);
	}

	function FormatDate($date, $format_id=false, $convert_to_timezone='default')
	{
		if( $convert_to_timezone==='default' ) $convert_to_timezone = $this->_alwaysConvertTimesToTimezone;
		$date = $convert_to_timezone?$this->GetTimezoneDate($date):$this->_ensureTimeStamp($date);
		if( $format_id === false ) $format_id = $this->DefaultDateFormat;
		$dtf = $this->_ensureDateTimeFormat();

		if( !$dtf )
			return "No DateTimeFormat for {$this->Code}";
		
		if( !($dtf instanceof DateTimeFormat) )
		{
			log_error("No DateTimeFormat instance: {$this->Code}",$dtf);
			return "No DateTimeFormat instance: {$this->Code}";
		}
		
		return $dtf->Format($date, $format_id);
	}

	function FormatTime($date, $format_id=false, $convert_to_timezone='default')
	{
		if( $convert_to_timezone==='default' ) $convert_to_timezone = $this->_alwaysConvertTimesToTimezone;
		$date = $convert_to_timezone?$this->GetTimezoneDate($date):$this->_ensureTimeStamp($date);
		if( $format_id === false ) $format_id = $this->DefaultTimeFormat;
		$dtf = $this->_ensureDateTimeFormat();
		
		if( !$dtf )
			return "No DateTimeFormat for {$this->Code}";
		
		if( !($dtf instanceof DateTimeFormat) )
		{
			log_error("No DateTimeFormat instance: {$this->Code}",$dtf);
			return "No DateTimeFormat instance: {$this->Code}";
		}
		
		$res = $dtf->Format($date, $format_id);
		if( isset($this->TimeZone) && isset($this->AppendTimeZone) && $this->AppendTimeZone )
			$res .= " {$this->TimeZone}";
		return $res;
	}

	function FormatDateTime($date, $use_long = false, $convert_to_timezone='default')
	{
		if( $use_long )
			return $this->FormatDate($date,DateTimeFormat::DF_LONGDATE,$convert_to_timezone)." ".$this->FormatTime($date,DateTimeFormat::DF_LONGTIME,$convert_to_timezone);
		return $this->FormatDate($date,false,$convert_to_timezone)." ".$this->FormatTime($date,false,$convert_to_timezone);
	}

	function GetTimezoneDate($date)
	{
		$date = $this->_ensureTimeStamp($date);
		if( !isset($this->TimeZone) || !$this->TimeZone )
			return $date;
		
		$dt = new DateTime(date('Y-m-d H:i:s', $date));
		try{
			$tz = new DateTimeZone($this->TimeZone);
			$dt->setTimezone($tz);
		}catch(Exception $ex){ 
			$this->TimeZone = "";
			log_error($ex->getMessage(),$ex); 
		}
		return strtotime($dt->format('Y-m-d H:i:s'));
	}
	
	/**
	 * Returns the given date/time in server's timezone
	 */
	function GetServerDate($date)
	{
		$dtz = date_default_timezone_get();
		$date = $this->_ensureTimeStamp($date);
		if( !isset($this->TimeZone) || !$this->TimeZone || ($this->TimeZone == $dtz) )
			return $date;
		
		$dt = new DateTime(date('Y-m-d H:i:s', $date), new DateTimeZone($this->TimeZone));
		$dt->setTimezone(new DateTimeZone($dtz));
		return strtotime($dt->format('Y-m-d H:i:s'));
	}
}

