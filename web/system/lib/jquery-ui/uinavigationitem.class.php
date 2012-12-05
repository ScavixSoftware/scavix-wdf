<?php

class uiNavigationItem extends Control
{
	function __initialize($is_sub_item=false)
	{
		parent::__initialize("li");
	}

	function &AddItem($label, $href=false)
	{
		if( count($this->_content) < 1 || !($this->_content[count($this->_content)-1] instanceof uiNavigation))
			$this->content( new uiNavigation(true) );

		$item = new uiNavigationItem(true);
		$item->content(new Anchor($href,$label));

		$this->_content[count($this->_content)-1]->content($item);
		return $item;
	}

	function SetDefault()
	{
		$this->_content[0]->rel = "default";
	}
}
?>
