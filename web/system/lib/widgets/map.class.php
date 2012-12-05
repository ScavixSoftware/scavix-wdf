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
 
class Map extends Control
{
	var $Width;
	var $Height;
	var $Options = array();
	private $Markers = array();
	private $Addresses = array();
	private $Language = "en";

	function __initialize($width = "100%", $height = "100%", $language = "en")
	{
		parent::__initialize("div");

		$this->css("width",$width);
		$this->css("height",$height);

		$this->Language = $language;
	}

	static function __js()
	{
		return array("https://maps.googleapis.com/maps/api/js?v=3&sensor=false");
	}

	function PreRender($args=array())
	{
		if( count($args) > 0 )
		{
			$page = &$args[0];

//			if(count($Options) > 0)
				$page->addDocReady( "map_initialize('{$this->id}');" );
			$script   = array();
			foreach( $this->Markers as $m )
				$script[] = $m->ToJavascript($this->id);
			foreach( $this->Addresses as $a )
				$script[] = "map_add_address('{$this->id}','$a');";

			foreach( $script as $s )
				$page->addDocReady($s);
			$page->addDocReady( "map_show_all('{$this->id}');" );
		}
		return parent::PreRender($args);
	}

	function &AddMarker($long, $lat, $options = array())
	{
		$marker = new MapMarker($long, $lat, $options);
		$this->Markers[] = $marker;
		return $marker;
	}

	function AddAddress($address)
	{
		$this->Addresses[] = $address;
	}
}

?>