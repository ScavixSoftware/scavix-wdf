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
default_string("TXT_UNKNOWN", 'Unknown');
	
/**
 * @attribute[Resource('jquery-ui/ui.container.js')]
 * @attribute[Resource('jquery-ui/ui.container.css')]
 */
class uiContainer extends uiControl
{
	private $_options = array();

	function __initialize($title="TXT_UNKNOWN",$options=array())
	{
		parent::__initialize("div");
		$this->_options = $options;
		$this->title = $title;
		$this->_script[0] = "$('#".$this->id."').container(".system_to_json($options).");";
	}

	function SetOption($name, $value)
	{
		$this->_options[$name] = $value;
		$this->_script[0] = "$('#".$this->id."').container(".system_to_json($this->_options).");";
	}
	
	function SetOptions($options)
	{
		$this->_options = array_merge($this->_options,$options);
		$this->_script[0] = "$('#".$this->id."').container(".system_to_json($this->_options).");";
	}

	function GetOption($name,$default)
	{
		if( isset($this->_options[$name]) )
			return $this->_options[$name];
		return $default;
	}

	function AddButton($icon,$function)
	{
		if( isset($this->_options['buttons']) )
			$buttons = $this->_options['buttons'];
		else
			$buttons = array();

		$icon = self::Icon($icon);
		if( is_array($function))
			$buttons[$icon] = $function;
		else
			$buttons[$icon] = "[jscode]".$function;

		$this->_options['buttons'] = $buttons;
		$this->_script[0] = "$('#".$this->id."').container(".system_to_json($this->_options).");";
	}
}
