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
 
class jqTreeView extends Control
{
	var $Url = false;
	var $NodeSelected = false;

    function __initialize($url=false,$nodeSelected=false)
	{
		parent::__initialize("");
		$this->Url = $url;
		$this->NodeSelected = $nodeSelected;
	}

	function  PreRender($args=array())
	{
		if( $this->Url || $this->NodeSelected )
		{
			$options = new StdClass();
			if( $this->Url )
				$options->url = $this->Url;
			if( $this->NodeSelected )
				$options->nodeSelected = $this->NodeSelected;
			$this->script("$('#".$this->id."').treeview(".system_to_json($options).");");
		}
		else
			$this->script("$('#".$this->id."').treeview({});");
//		log_debug($args);
//		log_debug($this->_script);
		return parent::PreRender($args);
	}

	function &AddRootNode($text,$class="folder")
	{
		$this->Tag = "ul";
		$res = new jqTreeNode($text);
		$this->content($res);
		return $res;
	}
}

class jqTreeNode extends Control
{
	var $tree = false;
	var $text = false;
	var $hasChildren = false;
	var $expanded = "closed";
	var $children = false;

	function __initialize($text)
	{
		parent::__initialize("li");
		$this->class = "ui-treeview-node";
		$this->content($text);
	}

	function &AddNode($text)
	{
		if( !$this->tree )
		{
			$this->tree = new Control("ul");
			$this->content( $this->tree );
		}

		$res = new jqTreeNode($text);
		$this->tree->content($res);
		return $res;
	}
}
