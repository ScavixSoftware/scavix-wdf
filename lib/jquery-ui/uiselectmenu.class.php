<?php

/**
 * see http://www.filamentgroup.com/lab/jquery_ui_selectmenu_an_aria_accessible_plugin_for_styling_a_html_select/
 * this component is only very few tested
 * known issue: weird effects in uiDialog
 * 
 * @attribute[Resource('jquery-ui/ui.selectmenu.js')]
 * @attribute[Resource('jquery-ui/ui.selectmenu.css')]
 */
class uiSelectMenu extends uiControl
{
	var $_icons = array();

    function __initialize($options = array())
	{
		parent::__initialize("select");
		$this->script("$('#{$this->id}').selectmenu();");
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
