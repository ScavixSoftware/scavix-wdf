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

use ScavixWDF\Base\Control;
use ScavixWDF\Controls\Anchor;

/**
 * @internal Item for the <uiNavigation> control
 */
class uiNavigationItem extends Control
{
	function __construct($is_sub_item=false)
	{
		parent::__construct("li");
	}

	function &AddItem($label, $href=false)
	{
		if( count($this->_content) < 1 || !($this->_content[count($this->_content)-1] instanceof uiNavigation))
			$this->content( new uiNavigation(true) );

		$item = new uiNavigationItem(true);
		$item->content(new Anchor($href,$label));

		$this->_content[count($this->_content)-1]->content($item);
		return $item;
	}

	function SetDefault()
	{
		$this->_content[0]->rel = "default";
	}
}
