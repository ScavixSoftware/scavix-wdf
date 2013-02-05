<?
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2012 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

class uiButton extends uiControl
{
	private $_icon;
	function __initialize($text,$icon=false,$css = array())
	{
		parent::__initialize("button");
		if( $icon )
			$this->_icon = self::Icon($icon);
		
		foreach($css as $property=>$value)
			$this->css($property,$value);
		
		$this->type = "button";
		$this->content($text);
	}
	
	static function Make($label,$onclick=false)
	{
		$res = new uiButton($label);
		if( $onclick ) $res->onclick = $onclick;
		return $res;
	}

	function PreRender($args=array())
	{
		if( count($args) > 0 )
		{
			$controller = $args[0];
			
			$opts = array();
			if(isset($this->_icon))
				$opts['icons'] = array('primary'=>"ui-icon-".$this->_icon);
			$controller->addDocReady("$('#".$this->id."').button(".system_to_json($opts).");");
		}
		return parent::PreRender($args);
	}
}
