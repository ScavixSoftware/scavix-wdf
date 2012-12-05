<?php
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
class Dialog extends Template
{
	var $trigger = array();
	var $closer = array();
	var $buttons = array();
	var $content = array();
    var $classes = array("dialog");

	function __initialize($id=false,$title=false,$content=false,$trigger=array())
	{
		parent::__initialize();
//        trace("Dialog::__initialize($id,$title,$content)");
		$this->set("id",$id);
		$this->_storage_id = $id;

		$this->set("title", $title);

		if( $content )
		{
			if( is_array($content) )
				$this->content = $content;
			else
				$this->content = array($content);
		}
		else
			$this->content = array();

		$this->set("content",$this->content);

		if( !is_array($trigger) )
			$trigger = array($trigger);
		$this->trigger = $trigger;
		$this->set("trigger",$this->trigger);

		$this->set("closer",$this->closer);
		$this->set("buttons",$this->buttons);
        $this->set("classes",$this->classes);
	}

	static function __js()
	{
		return array(jsFile('jquery/jqModal.js'));
	}

	static function __css()
	{
		return array(skinFile('dialog.css'));
	}

	function CloseAction()
	{
		return "$('#".$this->vars['id']."').jqmHide();";
	}

	function CallMethodAction($method)
	{
		return "Dialog_CallMethod('".$this->_storage_id."','$method')";
	}

	function RedirectAction($href)
	{
		if( starts_with($href,'javascript:') )
			return $href;
		return "document.location.href = '$href';";
	}

	function addTrigger($selector)
	{
		$this->trigger[] = $selector;
		$this->set("trigger",$this->trigger);
	}

    function addClass($class)
	{
		$this->classes[] = $class;
		$this->set("classes",$this->classes);
	}

	function addCloser($selector)
	{
		$this->closer[] = $selector;
		$this->set("closer",$this->closer);
	}

	function addButton($label,$action,$css=array())
	{
		$but = new Button("$label");
		$but->onclick = $action;

		foreach( $css as $k=>$v )
			$but->css($k,$v);
			
		$this->buttons[] = $but;
		$this->set("buttons",$this->buttons);
	}

	function addContent($content)
	{
		$this->content[] = $content;
		$this->set("content",$this->content);
	}

	function encodeForJS($plain=false,$load_js_code=false)
	{
		$this->set("no_js_code",!$load_js_code);

		$dlg = jsEscape($this->do_the_execution());
		$dlg = "unescape('$dlg')";

		$res = "$($dlg).appendTo('body');dialog_show('{$this->_storage_id}');";
		//$res = "$($dlg).jqm({overlay:60,overlayClass:'whiteOverlay',toTop:true}).jqmShow();";

		if( $load_js_code )
		{
			$css = system_preload_css(array(skinFile('dialog.css')));
			$res = $css."\n".system_preload_js($res,array(jsFile('dialog.js'),jsFile('jquery/jqModal.js')));
		}

		if( $plain )
			return $res;
		return array($res);
	}

    function addShowCallBackJS($script)
    {
        $this->set("showcallback", $script);
    }
}

?>