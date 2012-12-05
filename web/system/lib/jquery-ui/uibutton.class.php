<?php

class uiButton extends Control
{
	private $_icon;
	function __initialize($text,$icon=false,$css = array())
	{
		parent::__initialize("button");
		if( $icon )
		{
			if( !isset($this->_icons[$icon]) )
				system_die("Invalid Icon. See uiButton class for allowed icons.");

			$this->_icon = $icon;
		}
		
		foreach($css as $property=>$value)
			$this->css($property,$value);
		
		$this->type = "button";
		$this->content($text);
//		$this->script("$('#".$this->id."').button(".system_to_json($opts).");");
	}
	
	static function Make($label,$onclick=false)
	{
		$res = new uiButton($label);
		if( $onclick ) $res->onclick = $onclick;
		return $res;
	}

	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'));
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'));
	}

	function PreRender($args=array())
	{
		if( count($args) > 0 )
		{
			$page = &$args[0];
			
			$opts = array();
			if(isset($this->_icon))
				$opts['icons'] = array('primary'=>"ui-icon-".$this->_icon);
			$page->addDocReady("$('#".$this->id."').button(".system_to_json($opts).");");
		}
		return parent::PreRender($args);
	}

	private $_icons = array(
		'carat-1-n' => 1,'carat-1-ne' => 1,'carat-1-e' => 1,'carat-1-se' => 1,'carat-1-s' => 1,'carat-1-sw' => 1,'carat-1-w' => 1,'carat-1-nw' => 1,'carat-2-n-s' => 1,'carat-2-e-w' => 1,
		'triangle-1-n' => 1,'triangle-1-ne' => 1,'triangle-1-e' => 1,'triangle-1-se' => 1,'triangle-1-s' => 1,'triangle-1-sw' => 1,'triangle-1-w' => 1,'triangle-1-nw' => 1,'triangle-2-n-s' => 1,'triangle-2-e-w' => 1,
		'arrow-1-n' => 1,'arrow-1-ne' => 1,'arrow-1-e' => 1,'arrow-1-se' => 1,'arrow-1-s' => 1,'arrow-1-sw' => 1,'arrow-1-w' => 1,'arrow-1-nw' => 1,'arrow-2-n-s' => 1,'arrow-2-ne-sw' => 1,'arrow-2-e-w' => 1,'arrow-2-se-nw' => 1,
		'arrowstop-1-n' => 1,'arrowstop-1-e' => 1,'arrowstop-1-s' => 1,'arrowstop-1-w' => 1,
		'arrowthick-1-n' => 1,'arrowthick-1-ne' => 1,'arrowthick-1-e' => 1,'arrowthick-1-se' => 1,'arrowthick-1-s' => 1,'arrowthick-1-sw' => 1,'arrowthick-1-w' => 1,'arrowthick-1-nw' => 1,'arrowthick-2-n-s' => 1,'arrowthick-2-ne-sw' => 1,'arrowthick-2-e-w' => 1,'arrowthick-2-se-nw' => 1,
		'arrowthickstop-1-n' => 1,'arrowthickstop-1-e' => 1,'arrowthickstop-1-s' => 1,'arrowthickstop-1-w' => 1,
		'arrowreturnthick-1-w' => 1,'arrowreturnthick-1-n' => 1,'arrowreturnthick-1-e' => 1,'arrowreturnthick-1-s' => 1,
		'arrowreturn-1-w' => 1,'arrowreturn-1-n' => 1,'arrowreturn-1-e' => 1,'arrowreturn-1-s' => 1,
		'arrowrefresh-1-w' => 1,'arrowrefresh-1-n' => 1,'arrowrefresh-1-e' => 1,'arrowrefresh-1-s' => 1,
		'arrow-4' => 1,'arrow-4-diag' => 1,
		'extlink' => 1,
		'newwin' => 1,
		'refresh' => 1,
		'shuffle' => 1,
		'transfer-e-w' => 1,
		'transferthick-e-w' => 1,
		'folder-collapsed' => 1,
		'folder-open' => 1,
		'document' => 1,'document-b' => 1,
		'note' => 1,
		'mail-closed' => 1,'mail-open' => 1,
		'suitcase' => 1,
		'comment' => 1,
		'person' => 1,
		'print' => 1,
		'trash' => 1,
		'locked' => 1,'unlocked' => 1,
		'bookmark' => 1,
		'tag' => 1,
		'home' => 1,
		'flag' => 1,
		'calculator' => 1,
		'cart' => 1,
		'pencil' => 1,
		'clock' => 1,
		'disk' => 1,
		'calendar' => 1,
		'zoomin' => 1,
		'zoomout' => 1,
		'search' => 1,
		'wrench' => 1,
		'gear' => 1,
		'heart' => 1,
		'star' => 1,
		'link' => 1,
		'cancel' => 1,
		'plus' => 1,'plusthick' => 1,'minus' => 1,'minusthick' => 1,
		'close' => 1,'closethick' => 1,
		'key' => 1,
		'lightbulb' => 1,
		'scissors' => 1,
		'clipboard' => 1,
		'copy' => 1,
		'contact' => 1,
		'image' => 1,
		'video' => 1,
		'script' => 1,
		'alert' => 1,
		'info' => 1,
		'notice' => 1,
		'help' => 1,
		'check' => 1,
		'bullet' => 1,
		'radio-off' => 1,'radio-on' => 1,
		'pin-w' => 1,'pin-s' => 1,
		'play' => 1,'pause' => 1,
		'seek-next' => 1,'seek-prev' => 1,'seek-end' => 1,'seek-first' => 1,
		'stop' => 1,
		'eject' => 1,
		'volume-off' => 1,'volume-on' => 1,
		'power' => 1,
		'signal-diag' => 1,'signal' => 1,
		'battery-0' => 1,'battery-1' => 1,'battery-2' => 1,'battery-3' => 1,
		'circle-plus' => 1,'circle-minus' => 1,'circle-close' => 1,'circle-triangle-e' => 1,
		'circle-triangle-s' => 1,'circle-triangle-w' => 1,'circle-triangle-n' => 1,
		'circle-arrow-e' => 1,'circle-arrow-s' => 1,'circle-arrow-w' => 1,'circle-arrow-n' => 1,
		'circle-zoomin' => 1,'circle-zoomout' => 1,'circle-check' => 1,
		'circlesmall-plus' => 1,'circlesmall-minus' => 1,'circlesmall-close' => 1,
		'squaresmall-plus' => 1,'squaresmall-minus' => 1,'squaresmall-close' => 1,
		'grip-dotted-vertical' => 1,'grip-dotted-horizontal' => 1,
		'grip-solid-vertical' => 1,'grip-solid-horizontal' => 1,
		'gripsmall-diagonal-se' => 1,
		'grip-diagonal-se' => 1
	);
}
