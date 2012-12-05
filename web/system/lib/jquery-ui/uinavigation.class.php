<?php

class uiNavigation extends Control
{
	function __initialize($is_sub_navigation=false)
	{
		global $CONFIG;
		parent::__initialize("ul");
		if( !$is_sub_navigation )
			$this->script("$('#".$this->id."').navigation({root_uri:'".$CONFIG['system']['console_uri']."',item_width:130});");
	}

	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'),skinFile('jquery-ui/ui.navigation.css'));
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'),jsFile('jquery-ui/ui.navigation.js'));
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
