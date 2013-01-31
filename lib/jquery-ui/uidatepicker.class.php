<?php

class uiDatePicker extends uiControl
{
	// all possible settings at http://jqueryui.com/demos/datepicker/#option
	protected $Options = array(
		'nextText' => 'BTN_NEXT',
		'prevText' => 'BTN_PREV',
		'buttonText' => '...', //BTN_BUTTON_TEXT',
		'closeText' => 'TXT_CLOSE_TEXT',
		'currentText' => 'TXT_CURRENT_TEXT',
	);

	protected $CultureInfo = false;
	protected $init_code = "datepicker";

	function __initialize($value = false, $inline = false)
	{		
		parent::__initialize($inline?"div":"input");

		if( $value )
		{
			if( !$inline )
				$this->value = $value;
			else
				$this->Options['defaultDate'] = $value;
		}
	}

	function PreRender($args = array())
	{
		if( !$this->CultureInfo )
			$this->SetCulture(Localization::detectCulture());

		if( isset($this->value) )
			$this->value = get_class($this)=="uiDatePicker"
				?$this->CultureInfo->FormatDate($this->value,DateTimeFormat::DF_SHORTDATE)
				:$this->CultureInfo->FormatDateTime($this->value);
		if( isset($this->Options['defaultDate']) )
			$this->Options['defaultDate'] = get_class($this)=="uiDatePicker"
				?$this->CultureInfo->FormatDate($this->Options['defaultDate'],DateTimeFormat::DF_SHORTDATE)
				:$this->CultureInfo->FormatDateTime($this->Options['defaultDate']);

		$this->script("$('#{$this->id}').{$this->init_code}(".system_to_json($this->Options).");");
		parent::PreRender($args);
	}

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

		$format = str_replace("yyyy", "y4", $format);
		$format = str_replace("y1", "y", $format);
		$format = str_replace("y2", "y", $format);
		$format = str_replace("y3", "yy", $format);
		$format = str_replace("y4", "yy", $format);
		
		$this->Options['dayNames'] = $cultureInfo->DateTimeFormat->DayNames;
		$this->Options['dayNamesMin'] = $cultureInfo->DateTimeFormat->ShortDayNames;
		$this->Options['dayNamesShort'] = $cultureInfo->DateTimeFormat->ShortDayNames;

		$this->Options['monthNames'] = $cultureInfo->DateTimeFormat->MonthNames;
		$this->Options['monthNamesShort'] = $cultureInfo->DateTimeFormat->ShortMonthNames;
//log_debug("Using format {$cultureInfo->DateTimeFormat->ShortDatePattern} -> $format");
		$this->Options['dateFormat'] = $format;
		
		return $this;
	}
	
	function SetOption($name,$value)
	{
		$this->Options[$name] = $value;
	}
}

?>