<?php

class uiSlider extends uiControl
{
	var $min = 1;
	var $max = 100;
	var $value = 10;
	var $range = false;
	var $onslide = false;
	var $values = false;

	function __initialize($id)
	{
		parent::__initialize("div");
		$this->id = $id;		
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'));
	}

	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'));
	}

	function WdfRender()
	{
		$opts = array();
		if( $this->min !== false )
			$opts['min'] = $this->min;
		if( $this->max !== false )
			$opts['max'] = $this->max;
		if( $this->value !== false )
			$opts['value'] = $this->value;
		if( $this->range !== false )
			$opts['range'] = $this->range;
		if( $this->onslide !== false )
			$opts['slide'] = $this->onslide;
		if( $this->values !== false )
		{
			if( !is_array( $this->values ) )
				$this->values = array($this->values);

			$opts['values'] = "[".implode(",",$this->values)."]";

		}

		$opts = jsArray2JSON($opts);
		$this->script("$('#{$this->id}').slider($opts);");
		return parent::WdfRender();
	}
}

?>