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
 
class DateTimeFormatSelect extends Select
{
	var $culture_code;
	
	function __initialize($culture_code, $selected_date_format=false, $selected_time_format=false, $timezone=false)
	{
		parent::__initialize();
		$this->script("Locale_Settings_Init();");
		$this->setData('role', 'datetimeformat');
		$this->setData('controller', buildQuery($this->id));
		$this->culture_code = $culture_code;
		
		if( $selected_date_format || $selected_time_format )
			$this->SetCurrentValue(
				json_encode( array( 
					$selected_date_format?$selected_date_format:false,
					$selected_time_format?$selected_time_format:false) )
			);
		
		$df = array(DateTimeFormat::DF_LONGDATE, DateTimeFormat::DF_SHORTDATE, DateTimeFormat::DF_MONTHDAY, DateTimeFormat::DF_YEARMONTH);
		$tf = array(DateTimeFormat::DF_LONGTIME, DateTimeFormat::DF_SHORTTIME);
		
		$value = time();
		$ci = Localization::getCultureInfo($culture_code);
		if( $timezone )
		{
			$ci->SetTimezone($timezone);
			$value = $ci->GetTimezoneDate($value);
		}
		$dtf = $ci->DateTimeFormat;
		foreach( $df as $d )
		{
			foreach( $tf as $t )
			{
				$sv = $dtf->Format($value, $d)." ".$dtf->Format($value, $t);
				$this->AddOption(json_encode(array($d,$t)), $sv);
			}
		}
	}

	static function __js()
	{
		return array(jsFile('locale_settings.js'));
	}
	
	/**
	 * @attribute[RequestParam('culture_code','string')]
	 */
	public function ListOptions($culture_code)
	{
		$this->culture_code = $culture_code;
		
		$df = array(DateTimeFormat::DF_LONGDATE, DateTimeFormat::DF_SHORTDATE, DateTimeFormat::DF_MONTHDAY, DateTimeFormat::DF_YEARMONTH);
		$tf = array(DateTimeFormat::DF_LONGTIME, DateTimeFormat::DF_SHORTTIME);
		
		$value = time();
		$ci = Localization::getCultureInfo($culture_code);
		if(!$ci)
			$ci = Localization::getCultureInfo('en-US');
		$dtf = $ci->DateTimeFormat;
		foreach( $df as $d )
		{
			foreach( $tf as $t )
			{
				$sv = $dtf->Format($value, $d)." ".$dtf->Format($value, $t);
				$res[] = "<option value='".json_encode(array($d,$t))."'>$sv</option>";
			}
		}
		return implode("\n",$res);
	}
	
	function GetSampleOfCurrent()
	{
		$value = time();
		$ci = Localization::getCultureInfo($this->culture_code);
		$dtf = $ci->DateTimeFormat;
		list($d,$t) = json_decode($this->_current);
		return $dtf->Format($value, $d)." ".$dtf->Format($value, $t);
	}
}

