<?php

/**
 * @attribute[Resource('jquery-ui/ui.container.js')]
 * @attribute[Resource('jquery-ui/ui.container.css')]
 */
class uiContainer extends uiControl
{
	private $_options = array();

	function __initialize($title="TXT_UNKNOWN",$options=array())
	{
		parent::__initialize("div");
		$this->_options = $options;
		$this->title = $title;
		$this->_script[0] = "$('#".$this->id."').container(".system_to_json($options).");";
	}

	function SetOption($name, $value)
	{
		$this->_options[$name] = $value;
		$this->_script[0] = "$('#".$this->id."').container(".system_to_json($this->_options).");";
	}
	
	function SetOptions($options)
	{
		$this->_options = array_merge($this->_options,$options);
		$this->_script[0] = "$('#".$this->id."').container(".system_to_json($this->_options).");";
	}

	function GetOption($name,$default)
	{
		if( isset($this->_options[$name]) )
			return $this->_options[$name];
		return $default;
	}

	function AddButton($icon,$function)
	{
		if( isset($this->_options['buttons']) )
			$buttons = $this->_options['buttons'];
		else
			$buttons = array();

		if( is_array($function))
			$buttons[$icon] = $function;
		else
			$buttons[$icon] = "[jscode]".$function;

		$this->_options['buttons'] = $buttons;
		$this->_script[0] = "$('#".$this->id."').container(".system_to_json($this->_options).");";
	}
}

?>