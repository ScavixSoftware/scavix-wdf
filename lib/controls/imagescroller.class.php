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
 
/**
 * @attribute[Resource('simplyscroll.js')]
 * @attribute[Resource('simplyscroll.css')]
 */
class ImageScroller extends Control
{
	var $Options = array();

	function __initialize($id,$options=array())
	{
		parent::__initialize("ul");
		$this->id = $id;
		if( !isset($options['autoMode']) ) $options['autoMode'] = 'loop';

		$this->Options = $options;
		$options = jsArray2JSON($this->Options);
		$code = "$('#$id').simplyScroll($options);";
		$this->script($code);
	}

	static function __js()
	{
		return array(jsFile('simplyscroll.js'));
	}

	static function __css()
	{
		return array(skinFile('simplyscroll.css'));
	}

	function AddImage($filename,$clicktarget=false,$title=false)
	{
		$img = new Image($filename);
		if( $title )
			$img->alt = $title;
		if( $clicktarget )
		{
			$img->onclick = "document.location.href='$clicktarget';";
			$img->css("cursor", 'pointer');
		}
		$this->content("<li>".$img->WdfRender()."</li>");
	}
}

?>