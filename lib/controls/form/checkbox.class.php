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
 
/*
    Document   : checkbox.class.php
    Created on : Feb 24, 2009, 11:55:13 AM
    Author     : Florian A. Talg
    Description:
	
*/
class CheckBox extends Control
{
	var $Label = false;
	
    function __initialize($name=false,$cid=false)
	{
		parent::__initialize("input");
		$this->type = "checkbox";
		$this->class = "checkbox";
		$this->value = 1;
		if( $name )
			$this->name = $name;
		if( $cid )
			$this->id = $cid;
	}
	
	function CreateLabel($text)
	{
		$this->Label = new Label($text,false,$this->id);
		return $this->Label;
	}
}
?>