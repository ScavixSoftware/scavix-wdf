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
 
class SuperfishMenu extends Control
{
    function __initialize()
	{
		parent::__initialize("ul");
		$this->class = "sf-menu sf-navbar";
		$this->script('$("#'.$this->id.'").superfish();');
	}
	
	function AddItem($label,$page,$event="",$data=array())
	{
		$item = new SuperfishMenuItem($label,$page,$event,$data);
		$this->content($item);
		return $item;
	}
	
	function GetItems()
	{
		return $this->_content;
	}
	
	function AddExternalItem($label,$href)
	{
		return $this->AddItem($label,$href,'$is_link$');
	}
	
	static function __js()
	{
		return array(jsFile('superfish.js'));
	}

	static function __css()
	{
		return array(skinFile("superfish.css"));
	}
	
	function do_the_execution()
	{
		if( count($this->_content) > 0 )
			$this->content('<li class="closer"></li>');
		return parent::do_the_execution();
	}
	
	function DetectSelected()
	{
		$page = strtolower(current_page_class());
		$event = current_event(true);
		$data = md5(my_var_export($_GET));
		
		for( $level=1; $level<=6; $level++ )
		{
			foreach( $this->GetItems() as $item )
			{
				if( $level < 4 )
					foreach( $item->GetItems() as $sub )
					{
						if( $sub->page == $page && 
							($level > 2 || $sub->event == $event) && 
							($level > 1 || $sub->data == $data) )
						{
							$sub->SetSelected();
							$item->SetSelected();
							return;
						}
					}
				else
					if( $item->page == $page && 
						($level > 5 || $item->event == $event) && 
						($level > 4 || $item->data == $data) )
					{
						$item->SetSelected();
						return;
					}
			}
		}
	}
}

class SuperfishMenuItem extends Control
{
	var $is_parent = true;
	var $sub = false;
	var $page = false;
	var $event = false;
	var $data = false;
	
	function __initialize($label,$page,$event="",$data=array())
	{
		parent::__initialize("li");
		$this->class = "";
		if( $event == '$is_link$' )
			$this->content(new Anchor($page, $label));
		else
			$this->content(new Anchor(buildQuery($page,$event,$data), $label));
		
		$this->page = strtolower($page);
		if( $event ) $this->event = strtolower($event);
		$this->data = md5(my_var_export($data));
	}
	
	function AddItem($label,$page,$event="",$data=array())
	{
		if( !$this->sub )
		{
			$this->sub = $this->content(new Control('ul'));
			$this->sub->class = "subnavi";
		}
		$item = new SuperfishMenuItem($label,$page,$event,$data);
		$item->is_parent = false;
		$this->sub->content($item);
		return $item;
	}
	
	function AddExternalItem($label,$href)
	{
		return $this->AddItem($label,$href,'$is_link$');
	}
	
	function GetItems()
	{
		if( $this->sub )
			return $this->sub->_content;
		return array();
	}
	
	function SetSelected()
	{
		if( $this->is_parent )
			$this->addClass('current');
		else
			$this->addClass('current_page_item');
	}
	
	function do_the_execution()
	{
		if( $this->sub )
			$this->sub->content('<li class="page_item"><span class="subclsr">&nbsp;</span></li>');
		return parent::do_the_execution();
	}
}
?>
