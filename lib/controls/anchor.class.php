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
 
/**
 * HTML anchor element.
 */
class Anchor extends Control
{
	/**
	 * Create a new HTML anchor element.
	 * @param string $href The href attribute. Defaults to "".
	 * @param string $label The anchor text. Defaults to "".
	 * @param string $class The CSS class. Defaults to "".
	 */
	function __initialize($href="", $label="", $class="",$target="")
	{
		parent::__initialize("a");
		$href = str_replace("&","&amp;",$href);
		$this->href = $href;
		if($target != "")
			$this->target = $target;
		$this->content($label);
        if($class != "")
            $this->class = $class;
		$this->CloseTagNeeded();
	}
	
	public function WdfRender()
	{
        if($this->href == "")
			$this->href = "javascript:{}";

        return parent::WdfRender();
	}	
}

?>