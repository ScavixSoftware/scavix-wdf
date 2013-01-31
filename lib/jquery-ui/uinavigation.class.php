<?php

/**
 * @attribute[Resource('jquery-ui/ui.navigation.js')]
 * @attribute[Resource('jquery-ui/ui.navigation.css')]
 */
class uiNavigation extends uiControl
{
	function __initialize($is_sub_navigation=false)
	{
		global $CONFIG;
		parent::__initialize("ul");
		if( !$is_sub_navigation )
			$this->script("$('#".$this->id."').navigation({root_uri:'".$CONFIG['system']['console_uri']."',item_width:130});");
	}

	function &AddItem($label, $href=false)
	{
		$item = new uiNavigationItem();
		$item->content( new Anchor($href,$label) );
		$this->content($item);
		return $item;
	}
}
?>
