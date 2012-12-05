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
		$this->content("<li>".$img->Execute()."</li>");
	}

//	/**
//	 * Sets a method to be the loading handler.
//	 * This function has to return JSON data in
//	 * format [{"src":"images/pic.jpg","title":"title","link":"http://"},{etc..}]
//	 * @param <type> $object
//	 * @param <type> $method
//	 */
//	function SetLoader(&$object,$method)
//	{
////		$this->Options['jsonSource'] = "?load=".$object->_storage_id."&event=".$method;
//		$options = jsArray2JSON($this->Options);
////		$code = "$('#$id').simplyScroll($options);";
//		$this->_script = array();
////		$this->script($code);
////
////		$this->_content = array();
////		$this->Tag = "div";
//
//		$loop  = "for(var i=0; i<d.length; i++) {";
//		$loop .= " var img = $('<img/>').attr('src',d[i].src).attr('title',d[i].title);";
//		$loop .= " if( d[i].link ) img.click(function(){document.location.href=d[i].link;}).css({cursor:'pointer'});";
//		$loop .= " var li = $('<li/>');";
//		$loop .= " li.append(img);";
//		$loop .= " $('#{$this->_storage_id}').append(li);";
//		$loop .= "}";
//
//		$code = "$.get('?load={$object->_storage_id}&event=$method',function(d){ eval('d = '+d); $loop $('#{$this->_storage_id}').simplyScroll($options); })";
//		$this->script($code);
//	}
}

?>