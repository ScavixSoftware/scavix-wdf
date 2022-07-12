<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls;

use ScavixWDF\Base\Control;

/**
 * HTML anchor element
 * 
 * Wraped as control to allow to inherit from this class and add code for AJAX handling in that derivered classes.
 */
class Anchor extends Control
{
	/**
	 * Create a new HTML anchor element.
	 * 
	 * @param string $href The href attribute
	 * @param string $label The anchor text
	 * @param string $class The CSS class
	 * @param string $target The target attribute
	 */
	function __construct($href="", $label="", $class="",$target="")
	{
		parent::__construct("a");
		$href = str_replace("&","&amp;",$href);
		$this->href = $href;
		if($target != "")
			$this->target = $target;
		$this->content($label);
        if($class != "")
            $this->class = $class;
		$this->CloseTagNeeded();
	}
    
    /**
     * Creates an <Anchor> with 'href="javascript:void(0)"'.
     * 
     * @param mixed $label Initial content
     * @return static
     */
    public static function Void($label)
    {
        return new Anchor("javascript:void(0)",$label);
    }
    
	/**
     * Shortcut to create textbased links.
     * 
     * @param string $label Link text
	 * @param string $controller Target controller
	 * @param string $event optional Target event
	 * @param string|array $data optional URL parameters
     * @return static
     */
    public static function Link($label,$controller,$event="",$data="")
    {
        return new Anchor(buildQuery($controller,$event,$data),$label);
    }
	
	/**
	 * @override Ensures that there's a valid href attribute, if not adds "#" to it.
	 */
	public function WdfRender()
	{
        if($this->href == "")
			$this->href = "#";

        return parent::WdfRender();
	}	
}
