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
 
class ImageFlow extends Control
{
	protected $_isEmpty = true;
	protected $_options = array();

	private $_pagerHandler = false;
	private $_pagerMethod = false;

	function __initialize($id,$options=array(),$text_if_empty = "ERR_NO_DATA_AVAILABLE")
	{
		parent::__initialize("div");
		$this->id = $id;
		$this->content($text_if_empty);

		$this->_options = $options;
		$this->_options['ImageFlowID'] = $id;

		// default is PNG output
		if( !isset($this->_options['reflectionPNG']) )
			$this->_options['reflectionPNG'] = true;

		// default onClick override
		if( !isset($this->_options['onClick']) )
			$add_click = true;

		// default height
		if( !isset($this->_options['imagesHeight']) )
			$this->_options['imagesHeight'] = 0.67;

		//$this->_options = jsArray2JSON($this->_options);
		if( $add_click )
		{
			//$this->_options = substr($this->_options,1);
			$click  = "function() { Debug($(this).attr('original'));";
			$click .= " if( this.url ) { if( this.url.match(/^javascript:/) ) eval(this.url); else document.location = this.url;}";
//			$click .= " if( this.url ) { if( !this.url.match(/^javascript:/) ) document.location = this.url;}";
			$click .= " else tb_show($(this).attr('alt'),$(this).attr('original'));";
			$click .= "}";
			//$this->_options = "{onClick:$click,".$this->_options;
			$this->_options['onClick'] = $click;
		}

		$this->script("$('#{$this->id}').show();");
	}

	public function do_the_execution($generate_script_code=true)
	{
		if( !isset($this->_options['startID']) )
			$this->_options['startID'] = floor(count($this->_content) / 2);

		$this->_options = jsArray2JSON($this->_options);

		$code  = "if( !window.global_image_flows )\n\twindow.global_image_flows = new Array();";
		$code .= "var flow = new ImageFlow();";
		$code .= "flow.init($this->_options);";
		$code .= "\nwindow.global_image_flows.push(flow);\n";
		$code .= "tb_pathToImage = '".skinFile('loading.gif')."';";
		$this->script($code);
		$this->script("$('#{$this->id}').show();");
		return parent::do_the_execution($generate_script_code);
	}

	static function __js()
	{
		$res = parent::__js();
		$res[] = jsFile("thickbox.js");
		return $res;
	}

	static function __css()
	{
		$res = parent::__css();
		$res[] = skinFile("thickbox.css");
		return $res;
	}

	function Reflect2()
	{
		require_once(dirname(__FILE__)."/imageflow/reflect2.php");
	}

	function Reflect3()
	{
		require_once(dirname(__FILE__)."/imageflow/reflect3.php");
	}

	private function _createImage($filename,$clicktarget=false,$title=false,$maxHeight = false)
	{
		$size = getimagesize($filename);
		$img = new Image($filename);

		if( $maxHeight )
		{
			$w = $size[0]; $h = $size[1];
			$r = $w / $h;
			$img->width  = $r * min($maxHeight,$h);
			$img->height = $img->width / $r;
			$img->width = intval($img->width);
			$img->height = intval($img->height);
		}
		$img->class = 'thickbox';
		if( $title )
			$img->alt = $title;
		if( $clicktarget )
			$img->longdesc = $clicktarget;

		return $img;
	}

	function AddImage($filename,$clicktarget=false,$title=false,$maxHeight = false)
	{
		if( $this->_isEmpty )
		{
			$this->_isEmpty = false;
			$this->_content = array();
			$this->_script = array();

//			$code  = "var flow = new ImageFlow();";
//			$code .= "flow.init($this->_options);";
//			$code .= "tb_pathToImage = '".skinFile('loading.gif')."';";
//			$this->script($code);
//			$this->script("$('#{$this->id}').show();");
		}
//		$size = getimagesize($filename);
//		$img = new Image($filename);
//
//		if( $maxHeight )
//		{
//			$w = $size[0]; $h = $size[1];
//			$r = $w / $h;
//			$img->width  = $r * min($maxHeight,$h);
//			$img->height = $img->width / $r;
//			$img->width = intval($img->width);
//			$img->height = intval($img->height);
//		}
//		$img->class = 'thickbox';
//		if( $title )
//			$img->alt = $title;
//		if( $clicktarget )
//			$img->longdesc = $clicktarget;
//
		$img = $this->_createImage($filename,$clicktarget,$title,$maxHeight);

		if( $this->_pagerHandler )
		{
			$this->_content = array_merge(
				array_slice($this->_content,0,count($this->_content)-1),
				array($img),
				array_slice($this->_content,count($this->_content)-1,1)
			);
		}
		else
			$this->content($img);
		store_object($this);
	}

	function EnablePager(&$handler,$method,$prevpage,$nextpage)
	{
		$this->_pagerHandler = $handler;
		$this->_pagerMethod = $method;

		if( $nextpage != -1 && $prevpage != -1)
		{
			$load_next = "javascript: ImageFlowLoadData('{$handler->_storage_id}','$method','{$this->id}','{$prevpage}','{$nextpage}')";
			$next = $this->_createImage(skinFile('imageflow/next.png',false),$load_next,"TXT_IMAGEFLOW_NEXT");
			$this->_content[] = $next;

			$p = $prevpage-2;
			$n = $p+2;

			$load_prev = "javascript: ImageFlowLoadData('{$handler->_storage_id}','$method','{$this->id}','{$p}','{$n}')";
			$prev = $this->_createImage(skinFile('imageflow/prev.png',false),$load_prev,"TXT_IMAGEFLOW_PREV");
			array_unshift($this->_content,$prev);
		}
		elseif( $prevpage == -1 && $nextpage != -1 )
		{
			$p =$nextpage-2;
			$load_next = "javascript: ImageFlowLoadData('{$handler->_storage_id}','$method','{$this->id}','{$p}','{$nextpage}')";
			$next = $this->_createImage(skinFile('imageflow/next.png',false),$load_next,"TXT_IMAGEFLOW_NEXT");
			$this->_content[] = $next;

		}
		elseif( $prevpage != -1 && $nextpage == -1 )
		{
			$p = $prevpage-2;
			$n = $p+2;
			log_debug("last page: n:$n, p:$p");
			$load_prev = "javascript: ImageFlowLoadData('{$handler->_storage_id}','$method','{$this->id}','{$p}','{$n}')";
			$prev = $this->_createImage(skinFile('imageflow/prev.png',false),$load_prev,"TXT_IMAGEFLOW_PREV");
			array_unshift($this->_content,$prev);
		}
	}
}

?>