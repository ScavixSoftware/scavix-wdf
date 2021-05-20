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

use ScavixWDF\Controls\Anchor;

/**
 * This is a MenuBar like control.
 * 
 * Note: This is work in progress
 * @attribute[Resource('jquery-ui/ui.navigation.js')]
 * @attribute[Resource('jquery-ui/ui.navigation.css')]
 */
class uiNavigation extends uiControl
{
	/**
	 * @param bool $is_sub_navigation If true acts as submenu
	 */
	function __construct($is_sub_navigation=false)
	{
		global $CONFIG;
		parent::__construct("ul");
		$this->InitFunctionName = false;
		if( !$is_sub_navigation )
			$this->script("$('#".$this->id."').navigation({root_uri:'".$CONFIG['system']['console_uri']."',item_width:130});");
	}

	/**
	 * Adds an item to the menu.
	 * 
	 * @param string $label Item label
	 * @param string $href Link to open if clicked
	 * @return uiNavigationItem The created item
	 */
	function &AddItem($label, $href=false)
	{
		$item = new uiNavigationItem();
		$item->content( new Anchor($href,$label) );
		$this->content($item);
		return $item;
	}
}
