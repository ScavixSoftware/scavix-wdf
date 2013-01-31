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
}

?>