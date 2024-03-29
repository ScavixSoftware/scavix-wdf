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

use ScavixWDF\Base\AjaxAction;
use ScavixWDF\Base\Control;

/**
 * Wraps an HTML &ltform&gt; element.
 *
 */
class Form extends Control
{
    function __construct()
	{
		parent::__construct("form");
		$this->method = 'post';
	}

	/**
	 * Creates and adds an input control
	 *
	 * $type may be one of: 'text' 'password' 'hidden' 'file' 'checkbox'
	 * returns objects of type <TextInput> <PasswordInput> <HiddenInput> <FileInput> <CheckBox>
	 * @param string $type See above for valid values
	 * @param string $name name for the element created
	 * @param string $value Value to be assigned (may be any valuetype)
	 * @return Input|Control The created control
	 */
	function AddInput($type,$name,$value='')
	{
		switch($type)
		{
			case "text":
				$inp = new TextInput($value, $name);
				break;
			case "textarea":
				$inp = new TextArea($value, $name);
				break;
			case "password":
				$inp = new PasswordInput($name);
				break;
			case "hidden":
                if( is_array($value) )
                {
                    if(count($value))
                    {
                        foreach( $value as $i=>$v )
                            $inp = HiddenInput::Make($v, "{$name}[{$i}]")->appendTo($this);
                    }
                    else
                        $inp = new HiddenInput('', $name);
                    return $inp;
                }
				$inp = new HiddenInput($value, $name);
				break;
			case "select":
				$inp = new Select($name);
				break;
			case "file":
				$this->enctype = "multipart/form-data";
				$inp = new FileInput($name);
				break;
			case "checkbox":
				$inp = new CheckBox($name);
				if( $value )
					$inp->value = $value;
				break;
			default:
				$inp = Input::Make()->setType($type)->setName($name)->setValue($value);
				break;
		}
		return $this->content($inp);
	}

	/**
	 * @shortcut <Form::AddInput>('text',$name,$value)
	 */
	function AddText($name, $value=''){ return $this->AddInput('text', $name, $value); }

	/**
	 * @shortcut <Form::AddInput>('textarea',$name,$value)
	 */
	function AddTextArea($name, $value=''){ return $this->AddInput('textarea', $name, $value); }

	/**
	 * @shortcut <Form::AddInput>('password',$name,$value)
	 */
	function AddPassword($name, $value=''){ return $this->AddInput('password', $name, $value); }

	/**
	 * @shortcut <Form::AddInput>('hidden',$name,$value)
	 */
	function AddHidden($name, $value=''){ return $this->AddInput('hidden', $name, $value); }

	/**
	 * @shortcut <Form::AddInput>('file',$name,$value)
	 */
	function AddFile($name){ return $this->AddInput('file', $name); }

	/**
	 * @shortcut <Form::AddInput>('checkbox',$name,$value)
	 * @return Checkbox
	 */
	function AddCheckbox($name,$label=false)
	{
		/** @var Checkbox $res */
		$res = $this->AddInput('checkbox', $name);
		if( $label )
			$this->content($res->CreateLabel($label));
		return $res;
	}

	/**
	 * @shortcut <Form::AddInput>('file',$name,$value)
	 * @return Select
	 */
	function AddSelect($name){ return $this->AddInput('select', $name); }

	/**
	 * Creates and adds a <SubmitButton>.
	 *
	 * @param string $label Label of the button
	 * @return SubmitButton The created button
	 */
    function AddSubmit($label)
	{
		return $this->content( new SubmitButton($label) );
	}

	/**
	 * Creates a standard AJAX submit action
	 *
	 * Will create everything needed to post this form via AJAX to a PHP-side handler.
	 * @param mixed $controller Handler object
	 * @param string $event Handler method name
	 * @return static
	 */
	function AjaxSubmitTo($controller,$event)
	{
		$s = AjaxAction::Post($controller,$event,"$(this).serializeArray()");
		$this->script("$('#{self}').submit( function(){ $s return false; } );");
		return $this;
	}

    /**
     * Creates a set of hidden inputs from data.
     *
     * @param array $data name-value pairs of data
     * @return static
     */
    function addHiddenData($data)
    {
        foreach( $data as $k=>$v )
            $this->AddHidden($k, $v);
        return $this;
    }

    /**
     * @shortcut <Control::attr>
     */
    function setAction($controller,$event='',$data='')
	{
		return $this->attr('action', buildQuery($controller,$event,$data));
	}
}
