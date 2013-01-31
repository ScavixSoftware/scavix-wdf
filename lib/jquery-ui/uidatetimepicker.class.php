<?php

/**
 * @attribute[Resource('jquery-ui/ui.datetimepicker.js')]
 * @attribute[Resource('jquery-ui/ui.datetimepicker.css')]
 */
class uiDateTimePicker extends uiDatePicker
{
	function __initialize($value = false, $inline = false)
	{		
		parent::__initialize($value,$inline);
		$this->init_code = "datetimepicker";
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'),jsFile("jquery-ui/ui.datetimepicker.js"));
	}

	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'),skinFile('jquery-ui/ui.datetimepicker.css'));
	}
}

?>