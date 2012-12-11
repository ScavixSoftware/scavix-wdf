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
 
class Form extends Control
{
    function __initialize()
	{
		parent::__initialize("form");
	}
	
	function AddInput($type,$name,$value='')
	{
		switch($type)
		{
			case "text":
				$inp = new TextInput($value, $name);
				break;
			case "password":
				$inp = new PasswordInput($value, $name);
				break;
			case "hidden":
				$inp = new HiddenInput($value, $name);
				break;
			case "file":
				$inp = new FileInput($name);
				break;
		}
		$this->content($inp);
		return $inp;
	}
	
	function AddText($name, $value){ return $this->AddInput('text', $name, $value); }
	function AddPassword($name, $value){ return $this->AddInput('password', $name, $value); }
	function AddHidden($name, $value){ return $this->AddInput('hidden', $name, $value); }
	function AddFile($name){ return $this->AddInput('file', $name); }
    
    function AddSubmit($label){ return $this->content( new SubmitButton($label) ); }
}
?>
