<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\JQueryUI;

/**
 * Wrapper around jQueryUI Butto
 * 
 * See http://jqueryui.com/button/
 */
class uiButton extends uiControl
{
	private $_icon;
	
	/**
	 * @param string $text Label
	 * @param string $icon Valid <uiControl::Icon>
	 */
	function __construct($text,$icon=false)
	{
		parent::__construct("button");
		if( $icon )
			$this->_icon = self::Icon($icon);
		
		$this->type = "button";
		if( $text )
			$this->content($text);
	}
	
	/**
	 * Overrides <Control::Make> with own logic.
	 * 
	 * @deprecated Use <uiButton::Textual> instead
	 * @param string $label Label
	 * @param string $onclick OnClick JS code
	 * @return uiButton The new button
	 */
	static function Make(...$args)
	{
        log_debug(__METHOD__,"Deprecated. Use uiButton::Textual instead");
        $label = isset($args[0])?$args[0]:'';
        $onclick = isset($args[1])?$args[1]:'';
        
		$res = new uiButton($label);
		if( $onclick ) $res->onclick = $onclick;
		return $res;
	}
    
    /**
	 * Button creation shortcut
	 * 
	 * @param string $label Label
	 * @param string $onclick OnClick JS code
	 * @return uiButton The new button
	 */
	static function Textual($label, $onclick = false)
	{
		$res = new uiButton($label);
		if( $onclick ) $res->onclick = $onclick;
		return $res;
	}
	
	/**
	 * Sets the <uiButton>s icon.
	 * 
	 * @param string $icon Valid <uiControl::Icon>
	 * @return static
	 */
	function setIcon($icon)
	{
		$this->_icon = self::Icon($icon);
		return $this;
	}

	/**
	 * @override
	 */
	function PreRender($args=[])
	{
		if( count($args) > 0 )
		{
			if(isset($this->_icon))
				$this->opt('icons',array('primary'=>"ui-icon-".$this->_icon));
			
			if( count($this->_content)==0 )
				$this->opt('text',false);
		}
		return parent::PreRender($args);
	}
	
	/**
	 * Creates javascript code to redirect elsewhere on button click.
	 * 
 	 * @param mixed $controller The controller to be loaded (can be <Renderable> or string)
	 * @param string $method The method to be executed
	 * @param array|string $data Optional data to be passed
	 * @return static
	 */
	function LinkTo($controller,$method='',$data=[])
	{
		$q = buildQuery($controller,$method,$data);
		$this->onclick = "document.location.href = '$q';";
		return $this;
	}
}
