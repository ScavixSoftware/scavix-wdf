<?php

// see http://www.filamentgroup.com/lab/jquery_ui_selectmenu_an_aria_accessible_plugin_for_styling_a_html_select/
// this component is only very few tested
// known issue: weird effects in uiDialog
class uiSelectMenu extends Control
{
	var $_icons = array();

    function __initialize($options = array())
	{
		parent::__initialize("select");
		$this->script("$('#{$this->id}').selectmenu();");
	}

	static function  __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'),skinFile('jquery-ui/ui.selectmenu.css'));
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery.ui.all.js'),jsFile('jquery-ui/ui.selectmenu.js'));
	}

	private function addIcon($path)
	{
		$key = "sm_icon_".count($this->_icons);
		$this->_icons[$key] = $path;
		return $key;
	}

	public function AddOption($name,$value,$icon=false)
	{
		$opt = new Control("option");
		$opt->value = $value;
		$opt->content($name);
		if( $icon )
			$opt->class = $this->addIcon($icon);
		$this->content($opt);
	}

	public function SetSelected($value)
	{
		foreach( $this->_content as &$opt )
			if( $opt->value == $value )
			{
				$opt->selected = "selected";
				break;
			}
	}
}
?>
