<?php

class uiTimeInput extends uiControl
{
	function __initialize($id, $defvalue=0, $onchange = "")
	{
		parent::__initialize("div");

		$defvalue = intval($defvalue);

		$m = floor($defvalue / 60);
		$s = $defvalue % 60;
		log_debug("TimeInput($id): $defvalue $m $s");

		$this->id = $id;
		$this->class = "timeinput ui-widget-content ui-widget ui-corner-all";
		$this->css("border","1px solid transparent");
		$this->onmouseover = "$(this).css({border:''});";
		$this->onmouseout = "$(this).css({border:'1px solid transparent'});";

		$minutes = new uiSlider("{$id}_euro");
		$minutes->range = 'min';
		$minutes->min = 0;
		$minutes->max = 120;
		$minutes->value = $m;
		$minutes->css("margin-bottom","8px");
		$minutes->onslide  = "function(event, ui){ $('#{$id}_euro_value').text(ui.value<10?'0'+ui.value:ui.value);";
		$minutes->onslide .= "$('#{$id}_hidden').val( parseInt($('#{$id}_euro_value').text())*60 + parseInt($('#{$id}_cent_value').text()) ).change(); }";
		$minutes->onmouseover = "$('#{$id}_euro_value').css({color:'red'});";
		$minutes->onmouseout = "$('#{$id}_euro_value').css({color:'black'});";

		$seconds = new uiSlider("{$id}_cent");
		$seconds->range = 'min';
		$seconds->min = 0;
		$seconds->max = 59;
		$seconds->value = $s;
		$seconds->onslide = "function(event, ui){ $('#{$id}_cent_value').text(ui.value<10?'0'+ui.value:ui.value); ";
		$seconds->onslide .= "$('#{$id}_hidden').val( parseInt($('#{$id}_euro_value').text())*60 + parseInt($('#{$id}_cent_value').text()) ).change(); }";
		$seconds->onmouseover = "$('#{$id}_cent_value').css({color:'red'});";
		$seconds->onmouseout = "$('#{$id}_cent_value').css({color:'black'});";

		$container = new Control("div");
		$container->class = "container";
		$container->content($minutes);
		$container->content($seconds);

		$value = new Control("div");
		$value->class = "value";

		$minuteval = new Control("div");
		$minuteval->id = "{$id}_euro_value";
		$minuteval->css("float","left");
		$minuteval->content($m<9?"0$m":$m);

		$secval = new Control("div");
		$secval->id = "{$id}_cent_value";
		$secval->css("float","left");
		$secval->content($s<9?"0$s":$s);

		//$value->content("<div style='float:left'>€</div>");
		$value->content($minuteval);
		$value->content("<div style='float:left'>:</div>");
		$value->content($secval);
		
		$this->content($container);
		$this->content($value);
		$this->content("<input type='hidden' id='{$id}_hidden' name='{$id}' value='$defvalue' onchange='$onchange'/>");
		$this->content("<br style='clear:both; line-height:0'/>");
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'));
	}

	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'));
	}
}

?>