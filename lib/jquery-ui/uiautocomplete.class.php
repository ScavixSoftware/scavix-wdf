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
 * Wraps a jQueryUI Autocomplete
 * 
 * See http://jqueryui.com/autocomplete/
 * 
 * @attribute[Resource('jquery-ui/ui.autocomplete.ex.js')] 
 */
class uiAutocomplete extends uiControl
{
	protected $hidden;
	protected $ui;

	/**
	 * @param array $options See http://api.jqueryui.com/autocomplete/
	 */
	function __construct($options=[])
	{		
		parent::__construct("input");
		$this->type = "text";
		$this->Options = $options;
	}
	
	/**
	 * Sets an on change handler.
	 * 
	 * @param string $function JS Handler function
	 * @return static
	 */
	function setOnChange($function)
	{
        if( !starts_iwith($function,'function'))
            $function = "function(event,ui){ $function }";
		//$this->hidden->onchange = $function;
        $this->opt('change',$function);
		return $this;
	}
    
    public function __sleep() 
    {
        return array('_storage_id');
    }
}
