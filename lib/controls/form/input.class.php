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
namespace ScavixWDF\Controls\Form;

use ScavixWDF\Base\Control;

/**
 * This is a basic &lt;input/&gt;.
 * 
 * Used as base class for all kind of inputs.
 */
class Input extends Control
{
	public $Label = false;
	
	function __construct()
	{
		parent::__construct("input");
	}
	
	/**
	 * Sets the type attribute.
	 * 
	 * @param string $type The type
	 * @return static
	 */
	function setType($type)
	{
		if( $type )
			$this->type = $type;
		return $this;
	}
	
	/**
	 * Sets the name attribute.
	 * 
	 * @param string $name The type
	 * @return static
	 */
	function setName($name)
	{
		if( $name )
			$this->name = $name;
		return $this;
	}
	
	/**
	 * Sets the value attribute.
	 * 
	 * @param string $value The value
	 * @return static
	 */
	function setValue($value)
	{
		if( $value !== false )
			$this->value = $value;
		return $this;
	}
	
	/**
	 * Creates a label element for this input.
	 * 
	 * Note that this only ensures that the label is correctly assigned to this input.
	 * It will not add it somewhere!
	 * @param string $text Text for the label
	 * @return Label The created label element
	 */
	function CreateLabel($text)
	{
		$this->Label = new Label($text,$this->id);
		return $this->Label;
	}
}
