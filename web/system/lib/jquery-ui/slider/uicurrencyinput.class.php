<?php

class uiCurrencyInput extends Control
{
	function __initialize($id, $defvalue=0, $onchange="")
	{
		parent::__initialize("div");

		$defvalue = floatval(str_replace(",",".",$defvalue));

		$e = floor($defvalue);
		$c = round(($defvalue-$e),2) * 100;
		log_debug("CurrencyInput($id): $defvalue $e $c");

		$this->id = $id;
		$this->class = "currencyinput ui-widget-content ui-widget ui-corner-all";
		$this->css("border","1px solid transparent");
		$this->onmouseover = "$(this).css({border:''});";
		$this->onmouseout = "$(this).css({border:'1px solid transparent'});";

		$euro = new uiSlider("{$id}_euro");
		$euro->range = 'min';
		$euro->min = 0;
		$euro->max = 100;
		$euro->value = $e;
		$euro->css("margin-bottom","8px");
		$euro->onslide  = "function(event, ui){ $('#{$id}_euro_value').text(ui.value); ";
		$euro->onslide .= "$('#{$id}_hidden').val( $('#{$id}_euro_value').text()+'.'+$('#{$id}_cent_value').text() ).change(); }";
		$euro->onmouseover = "$('#{$id}_euro_value').css({color:'red'});";
		$euro->onmouseout = "$('#{$id}_euro_value').css({color:'black'});";

		$cent = new uiSlider("{$id}_cent");
		$cent->range = 'min';
		$cent->min = 0;
		$cent->max = 99;
		$cent->value = $c;
		$cent->onslide  = "function(event, ui){ $('#{$id}_cent_value').text(ui.value<10?'0'+ui.value:ui.value); ";
		$cent->onslide .= "$('#{$id}_hidden').val( $('#{$id}_euro_value').text()+'.'+$('#{$id}_cent_value').text() ).change(); }";
		$cent->onmouseover = "$('#{$id}_cent_value').css({color:'red'});";
		$cent->onmouseout = "$('#{$id}_cent_value').css({color:'black'});";

		$container = new Control("div");
		$container->class = "container";
		$container->content($euro);
		$container->content($cent);

		$value = new Control("div");
		$value->class = "value";

		$euroval = new Control("div");
		$euroval->id = "{$id}_euro_value";
		$euroval->css("float","left");
		$euroval->content($e);

		$centval = new Control("div");
		$centval->id = "{$id}_cent_value";
		$centval->css("float","left");
		$centval->content($c<9?"0$c":$c);

		$value->content("<div style='float:left'>€</div>");
		$value->content($euroval);
		$value->content("<div style='float:left'>,</div>");
		$value->content($centval);
		
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