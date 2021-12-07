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

/**
 * Represents a checkbox.
 * 
 */
class CheckBox extends Input
{
	/**
	 * @param string $name The name
	 */
    function __construct($name=false)
	{
		parent::__construct();
		$this->setType("checkbox")->setName($name)->setValue(1);
	}
	
    /**
     * Sets if checked or not.
     * 
     * @param bool $on true for checked, false for unchecked
     * @return $this
     */
	function setChecked($on)
	{
		if( $on )
			return $this->attr('checked','checked');
		if( isset($this->_attributes['checked']) )
			unset($this->_attributes['checked']);
		return $this;
	}
    
    /**
     * Enables form auto-submission on value change.
     * 
     * This is done by submitting `$(this).closest('form')`
     * 
     * @param bool $on if true, activates, else stops
     * @return Select $this
     */
    function setAutoSubmit($on=true)
    {
        if( $on )
            $this->attr("onchange","$(this).closest('form').submit()");
        else
            $this->attr("onchange","");
        return $this;
    }
}
