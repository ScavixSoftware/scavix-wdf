<?php

class uiAutocomplete extends Control
{
	protected $hidden;
	protected $ui;
	protected $Options;

	function __initialize($options=array())
	{		
		parent::__initialize("");
		
		$this->hidden = new Control('input');
		$this->hidden->setData('role','autocomplete_value')->type = "hidden";
		$this->ui = new Control('input');
		$this->ui->setData('role','autocomplete_ui')->type = "text";
		
		$options['focus'] = "function(e,d){ $('#{$this->ui->id}').val(d.item.label); return false; }";
		if( !isset($options['select']) )
			$options['select'] = str_replace("return false","$('#{$this->hidden->id}').val(d.item.value).change(); return false",$options['focus']);

		$this->Options = $options;
	}
	
	function setOnChange($function)
	{
		$this->hidden->onchange = $function;
		return $this;
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'));
	}

	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'));
	}

	function PreRender($args = array())
	{
		$this->content(array($this->hidden,$this->ui), true);
		$this->script("$('#{$this->ui->id}').autocomplete(".system_to_json($this->Options).");");
		parent::PreRender($args);
	}
	
	function opt($name,$value)
	{
		$this->Options[$name] = $value;
		return $this;
	}
}

?>