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
 
class Toolbar extends Template
{
	var $controls;

	function __initialize()
	{
		parent::__initialize();
		$this->set("id",$this->_storage_id);
		$this->controls = array();
		$this->set("controls",$this->controls);
	}

	static function __css()
	{
		return array("js/tiny_mce/themes/advanced/skins/default/content.css");
	}

//	function PreparePage(&$page)
//	{
//		$page->addJs(jsFile('jquery.js'));
//		$page->addCss("js/tiny_mce/themes/advanced/skins/default/content.css");
//	}

	function addControl(&$control)
	{
		$this->controls[] = $control;
		$this->set("controls",$this->controls);
	}

	function addButton($icon_x,$icon_y,$action="")
	{
		$img = new ToolbarButton($icon_x,$icon_y);
		$img->setAction($action);
		$this->addControl( $img );
	}

	function addSeparator()
	{
		$sep = new ToolbarButton(9,0);
		$sep->css("width","1px");
		$this->addControl( $sep );
	}
}

?>