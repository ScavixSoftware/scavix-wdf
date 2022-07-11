<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\JQueryUI;

use ScavixWDF\Localization\CultureInfo;
use ScavixWDF\Localization\DateTimeFormat;
use ScavixWDF\Localization\Localization;

default_string('BTN_DP_NEXT', 'Next');
default_string('BTN_DP_PREV', 'Prev');
default_string('TXT_DP_CLOSE', 'Close');
default_string('TXT_DP_CURRENT', 'Today');
default_string('TXT_DP_NOW', 'Now');
default_string('TXT_DP_TIME', 'Time');
default_string('TXT_DP_HOUR', 'Hour');
default_string('TXT_DP_MINUTE', 'Minute');
default_string('TXT_DP_SECOND', 'Second');
default_string('TXT_DP_CHOOSE_TIME', 'Choose Time');
default_string('TXT_DP_TIME_ZONE', 'Time Zone');

/**
 * Wraps a jQueryUI DatePicker
 * 
 * See http://jqueryui.com/datepicker/
 */
class uiDatePicker extends uiControl
{
    static $DefaultCI = false;
	protected $CultureInfo = false;

	/**
	 * @param mixed $value The default value
	 * @param bool $inline If true will be displayed inline
	 */
	function __construct($value = false, $inline = false)
	{		
		parent::__construct($inline?"div":"input");
		$this->Options = array(
			'nextText' => 'BTN_DP_NEXT',
			'prevText' => 'BTN_DP_PREV',
			'buttonText' => '...',
			'closeText' => 'TXT_DP_CLOSE',
			'currentText' => (get_class_simple($this)=="uiDateTimePicker" ? 'TXT_DP_NOW' : 'TXT_DP_CURRENT'),
		);
        if(get_class_simple($this)=="uiDateTimePicker")
        {
            $this->Options += 
                    [
                        'timeText' => 'TXT_DP_TIME',
                        'hourText' => 'TXT_DP_HOUR',
                        'minuteText' => 'TXT_DP_MINUTE',
                        'secondText' => 'TXT_DP_SECOND',
                        'timeOnlyTitle' => 'TXT_DP_CHOOSE_TIME',
                        'timezoneText' => 'TXT_DP_TIME_ZONE',
                    ];
        }
        if( !$inline )
        {
            $this->type = 'text';
            $this->attr('autocomplete', 'off');
        }
		if( $value )
		{
			if( !$inline )
				$this->value = $value;
			else
				$this->Options['defaultDate'] = $value;
		}
        
        if( self::$DefaultCI )
            $this->SetCulture(self::$DefaultCI);
	}

	/**
	 * @override
	 */
	function PreRender($args = [])
	{
		if( !$this->CultureInfo )
			$this->SetCulture(Localization::detectCulture());

		if( isset($this->value) )
			$this->value = get_class_simple($this)=="uiDatePicker"
				?$this->CultureInfo->FormatDate($this->value,DateTimeFormat::DF_SHORTDATE)
				:$this->CultureInfo->FormatDateTime($this->value);
		if( isset($this->Options['defaultDate']) )
			$this->Options['defaultDate'] = get_class_simple($this)=="uiDatePicker"
				?$this->CultureInfo->FormatDate($this->Options['defaultDate'],DateTimeFormat::DF_SHORTDATE)
				:$this->CultureInfo->FormatDateTime($this->Options['defaultDate']);

		parent::PreRender($args);
	}

	/**
	 * Sets the culture.
	 * 
	 * @param CultureInfo $cultureInfo The (new) culture
	 * @return static
	 */
	function SetCulture($cultureInfo)
	{
		while( $cultureInfo->IsNeutral() )
			$cultureInfo = $cultureInfo->DefaultRegion()->DefaultCulture();

		$this->CultureInfo = $cultureInfo;
        
        $format = $cultureInfo->DateTimeFormat->ShortDatePattern;
		$format = str_replace("d1", "d", $format);
		$format = str_replace("d2", "dd", $format);
		$format = str_replace("d3", "D", $format);
		$format = str_replace("d4", "DD", $format);
		
		$format = str_replace("M1", "m", $format);
		$format = str_replace("MM", "M2", $format);
		$format = str_replace("M2", "mm", $format);
		$format = str_replace("M3", "M", $format);
		$format = str_replace("M4", "MM", $format);
		$format = str_replace("M", "m", $format);

		$format = str_replace("yyyy", "yy", $format);
		$format = str_replace("y1", "y", $format);
		$format = str_replace("y2", "yy", $format);
		$format = str_replace("y3", "yy", $format);
		$format = str_replace("y4", "yy", $format);
		
        $this->Options['firstDay'] = $cultureInfo->DateTimeFormat->FirstDayOfWeek;
        
		$this->Options['dayNames'] = $cultureInfo->DateTimeFormat->DayNames;
		$this->Options['dayNamesMin'] = $cultureInfo->DateTimeFormat->ShortDayNames;
		$this->Options['dayNamesShort'] = $cultureInfo->DateTimeFormat->ShortDayNames;

		$this->Options['monthNames'] = $cultureInfo->DateTimeFormat->MonthNames;
		$this->Options['monthNamesShort'] = $cultureInfo->DateTimeFormat->ShortMonthNames;
		$this->Options['dateFormat'] = $format;
        
        if(get_class_simple($this) == "uiDateTimePicker")
        {
            $format = $cultureInfo->DateTimeFormat->ShortTimePattern;
            
            $format = str_replace(['h1', 'h2', 'h3', 'h4', 'H1', 'H2'], ['h', 'hh', 'hh', 'hh', 'H', 'HH'], $format);
            $format = str_replace(['m1', 'm2', 'm3', 'm4'], ['m', 'mm', 'mm', 'mm'], $format);
            $format = str_replace(['t2'], ['tt'], $format);
            
            $this->Options['timeFormat'] = $format;
            if(strpos($format, 'tt') !== false)
                $this->Options['ampm'] = true;
        }
		
		return $this;
	}
    
    /**
     * Add default uiDatepicker settings to the given <HtmlPage>s JavaScript code.
     * 
     * @param \ScavixWDF\Base\HtmlPage $page The HtmlPage
     * @param CultureInfo $cultureInfo The <CultureInfo> to be default
     * @param array $options Default options
     * @return void
     */
    public static function PromoteDefaults(\ScavixWDF\Base\HtmlPage $page, $cultureInfo, $options = [])
    {
        $cls = get_called_class();
        $temp = new $cls();
        $temp->SetCulture($cultureInfo);
        $def = json_encode(array_merge($temp->Options,$options));
        $page->addDocReady("$.datepicker.setDefaults($def);");
        self::$DefaultCI = $cultureInfo;
    }
    
	/**
	 * Sets the date value.
	 * 
	 * @param string $value Valid date string representation
	 */
    function setValue($value)
    {
        $value = ($value?:false);
        if( $this->type == 'text' )
            $this->value = $value;
        else
            $this->Options['defaultDate'] = $value;
    }
}
