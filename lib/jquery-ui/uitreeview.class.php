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
namespace ScavixWDF\JQueryUI;

use ScavixWDF\Base\Control;

/**
 * A TreeView control.
 * 
 * Nodes are represented by <uiTreeNode> objects
 * @attribute[Resource('jquery-ui/ui.treeview.js')]
 * @attribute[Resource('jquery-ui/ui.treeview.js')]
 */
class uiTreeView extends uiControl
{
	public $Url = false;
	public $NodeSelected = false;

	/**
	 * @param string $url Optional URL to load data from
	 * @param string $nodeSelected JS callback function to call when a node has been selected
	 */
    function __construct($url=false,$nodeSelected=false)
	{
		parent::__construct("");
		$this->Url = $url;
		$this->NodeSelected = $nodeSelected;
	}

	/**
	 * @override
	 */
	function  PreRender($args=[])
	{
		if( $this->Url )
			$this->opt('url',$this->Url);
		if( $this->NodeSelected )
			$this->opt('nodeSelected',$this->NodeSelected);
		return parent::PreRender($args);
	}

	/**
	 * Adds a root node to the tree
	 * 
	 * Returns the node object so you may chain adding subnodes:
	 * <code php>
	 * $sub = uiTreeView::Make()->AddRootNode("Root1")->AddNode("Subnode 1");
	 * $sub->AddNode("Subnode 2);
	 * </code>
	 * @param string $text Label of the node
	 * @param string $class Optional css class
	 * @return uiTreeNode The new node
	 */
	function &AddRootNode($text,$class="folder")
	{
		$this->Tag = "ul";
		$res = new uiTreeNode($text);
		$this->content($res);
		return $res;
	}
}

/**
 * Represents a tree node in a <uiTreeView>.
 * 
 */
class uiTreeNode extends Control
{
	public $tree = false;
	public $text = false;
	public $hasChildren = false;
	public $expanded = "closed";
	public $children = false;

	/**
	 * No need to call this manually, use <uiTreeView::AddRootNode>() instead.
	 * @param string $text Node label text
	 */
	function __construct($text)
	{
		parent::__construct("li");
		$this->class = "ui-treeview-node";
		$this->content($text);
	}

	/**
	 * Adds a subnode.
	 * 
	 * This creates a new <uiTreeNode> object and returns it for method chaining:
	 * <code php>
	 * $sub = uiTreeView::Make()->AddRootNode("Root1")->AddNode("Subnode 1");
	 * $sub->AddNode("Subnode 2);
	 * </code>
	 * @param string $text Subnode label text
	 * @return uiTreeNode The created node
	 */
	function &AddNode($text)
	{
		if( !$this->tree )
		{
			$this->tree = new Control("ul");
			$this->content( $this->tree );
		}

		$res = new uiTreeNode($text);
		$this->tree->content($res);
		return $res;
	}
}
