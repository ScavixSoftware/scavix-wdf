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

default_string("TXT_UNKNOWN", 'Unknown');
	
/**
 * This is a container for UI elements.
 * 
 * May be deprecated, we used it in the past for widget based UI designs. 
 * It's kind of a dialog, but not exactly and contain clickable icons in the header.
 * @attribute[Resource('jquery-ui/ui.container.js')]
 * @attribute[Resource('jquery-ui/ui.container.css')]
 */
class uiContainer extends uiControl
{
	/**
	 * @param string $title Title for header section
	 * @param array $options
	 */
	function __construct($title="TXT_UNKNOWN",$options=[])
	{
		parent::__construct("div");
		$this->Options = $options;
		$this->title = $title;
	}

	/**
	 * Adds a button to the header section.
	 * 
	 * @param string $icon A valid <uiControl::Icon>
	 * @param string $function JS code to be executed on click
	 * @return static
	 */
	function AddButton($icon,$function)
	{
		if( isset($this->Options['buttons']) )
			$buttons = $this->Options['buttons'];
		else
			$buttons = [];

		$icon = self::Icon($icon);
		if( is_array($function))
			$buttons[$icon] = $function;
		else
			$buttons[$icon] = "[jscode]".$function;

		$this->Options['buttons'] = $buttons;
		return $this;
	}
}
