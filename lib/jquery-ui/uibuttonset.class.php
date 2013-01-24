<?php

class uiButtonSet extends Control
{
	var $buttons = array();
	var $sortable;
	private $icons = array(
		'carat-1-n','carat-1-ne','carat-1-e','carat-1-se','carat-1-s','carat-1-sw','carat-1-w','carat-1-nw','carat-2-n-s','carat-2-e-w',
		'triangle-1-n','triangle-1-ne','triangle-1-e','triangle-1-se','triangle-1-s','triangle-1-sw','triangle-1-w','triangle-1-nw','triangle-2-n-s','triangle-2-e-w',
		'arrow-1-n','arrow-1-ne','arrow-1-e','arrow-1-se','arrow-1-s','arrow-1-sw','arrow-1-w','arrow-1-nw','arrow-2-n-s','arrow-2-ne-sw','arrow-2-e-w','arrow-2-se-nw',
		'arrowstop-1-n','arrowstop-1-e','arrowstop-1-s','arrowstop-1-w',
		'arrowthick-1-n','arrowthick-1-ne','arrowthick-1-e','arrowthick-1-se','arrowthick-1-s','arrowthick-1-sw','arrowthick-1-w','arrowthick-1-nw','arrowthick-2-n-s','arrowthick-2-ne-sw','arrowthick-2-e-w','arrowthick-2-se-nw',
		'arrowthickstop-1-n','arrowthickstop-1-e','arrowthickstop-1-s','arrowthickstop-1-w',
		'arrowreturnthick-1-w','arrowreturnthick-1-n','arrowreturnthick-1-e','arrowreturnthick-1-s',
		'arrowreturn-1-w','arrowreturn-1-n','arrowreturn-1-e','arrowreturn-1-s',
		'arrowrefresh-1-w','arrowrefresh-1-n','arrowrefresh-1-e','arrowrefresh-1-s',
		'arrow-4','arrow-4-diag',
		'extlink',
		'newwin',
		'refresh',
		'shuffle',
		'transfer-e-w',
		'transferthick-e-w',
		'folder-collapsed',
		'folder-open',
		'document','document-b',
		'note',
		'mail-closed','mail-open',
		'suitcase',
		'comment',
		'person',
		'print',
		'trash',
		'locked','unlocked',
		'bookmark',
		'tag',
		'home',
		'flag',
		'calculator',
		'cart',
		'pencil',
		'clock',
		'disk',
		'calendar',
		'zoomin',
		'zoomout',
		'search',
		'wrench',
		'gear',
		'heart',
		'star',
		'link',
		'cancel',
		'plus','plusthick','minus','minusthick',
		'close','closethick',
		'key',
		'lightbulb',
		'scissors',
		'clipboard',
		'copy',
		'contact',
		'image',
		'video',
		'script',
		'alert',
		'info',
		'notice',
		'help',
		'check',
		'bullet',
		'radio-off','radio-on',
		'pin-w','pin-s',
		'play','pause',
		'seek-next','seek-prev','seek-end','seek-first',
		'stop',
		'eject',
		'volume-off','volume-on',
		'power',
		'signal-diag','signal',
		'battery-0','battery-1','battery-2','battery-3',
		'circle-plus','circle-minus','circle-close','circle-triangle-e',
		'circle-triangle-s','circle-triangle-w','circle-triangle-n',
		'circle-arrow-e','circle-arrow-s','circle-arrow-w','circle-arrow-n',
		'circle-zoomin','circle-zoomout','circle-check',
		'circlesmall-plus','circlesmall-minus','circlesmall-close',
		'squaresmall-plus','squaresmall-minus','squaresmall-close',
		'grip-dotted-vertical','grip-dotted-horizontal',
		'grip-solid-vertical','grip-solid-horizontal',
		'gripsmall-diagonal-se',
		'grip-diagonal-se'
	);

	function __initialize($sortable = false)
	{
		parent::__initialize("div");
		$this->sortable = $sortable;
	}

	function PreRender($args = array())
	{
		$btncnt = count($this->buttons);
		if($btncnt != 0)
		{
			for($index = 0; $index < $btncnt;$index++)
			{
				$label = $this->buttons[$index]['label'];
				$events = $this->buttons[$index]['events'];
				$this->content($this->buttons[$index]['button']);
//				$this->content("<label for='".$this->buttons[$index]['button']->id."'>".$label."</label>");
				$this->content($this->buttons[$index]['label']);
				if(isset($this->buttons[$index]['icon']))
					$this->script("$('#{$this->buttons[$index]['button']->id}').button({icons:{primary:'ui-icon-".$this->buttons[$index]['icon']."'}});");
				foreach($events as $event=>$function)
				{
					$this->script("$('#{$this->buttons[$index]['button']->id}').$event(function(){ $function }); ");
				}
//				if(array_key_exists("icon",$this->buttons[$index]))
//					$this->script("$('#{$this->buttons[$index]['button']->id}').button({icons:{primary:'{$this->buttons[$index]['icon']}',secondary:null}});");
			}
			$this->script("$(function(){ $('#".$this->id."').buttonset(); });");
			if($this->sortable)
				$this->script("$(function(){ $('#".$this->id."').sortable(); })");
		}
		parent::PreRender($args);
	}

	static function  __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'));
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'));
	}

	function &AddButton($label,$click_event = false,$icon = false,$tab_id = "")
	{
		$ctn = count($this->buttons);
		$btn = new RadioButton("radio",$tab_id);
		$this->buttons[$ctn]["label"]  = new Label($label,false,$btn->id);
		$this->buttons[$ctn]['button'] = $btn;
		$this->buttons[$ctn]['events']['click'] = $click_event;
//		$this->AddEventToButton('click', $click_event, $btn->id);
		
		if($icon && in_array($icon,$this->icons))
			$this->buttons[$ctn]["icon"] = $icon;
			
		return $btn;
	}

	/**
	 *
	 * @param <type> $button_id the target button id
	 * @param <type> $event a jQuery event e.g click
	 * @param <type> $function
	 * 
	 */
	function AddEventToButton($button_id,$event,$function)
	{
		$btncnt = count($this->buttons);
		for($i = 0; $i < $btncnt; $i++)
		{
			if($this->buttons[$i]['button']->id == $button_id)
			{
				$this->buttons[$i]['events'][$event] = $function;
				break;
			}
		}
	}

	function ChangeLabel($button_id,$label)
	{
		$btncnt = count($this->buttons);
		for($i = 0; $i < $btncnt;$i++)
		{
			if($this->buttons[$i]['button']->id == $button_id)
				$this->buttons[$i]['label'] = $label;
		}
	}
}
?>