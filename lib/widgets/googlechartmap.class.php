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
 * Class to add a GoogleChartMap
 *
 * There are 2 different ways for use:
 *
 * 1. Example - One gradient colored map
 * In this case you have to define your map like this:
 *
 *	$map = new GoogleChartMap("Europe","europe");
 *	$map->SetColors("FFFFFF","EAF7FE","FFFFFF");
 *	$map->AddRange('FF0000');
 *	$map->AddCountry("DE",100);
 *	$map->AddCountry("AT",50);
 *	$this->content($map);
 *
 * The colorrange is between FFFFFF (=> 0%) and FF0000 (=> 100%)
 * In AddCountry() you give the countrycode and a total percent value
 * Germany will be FF0000 and Autria will be at 50% between FFFFFF and FF0000
 *
 * 2. Example - Different colored gradient maps
 * In this case you have to define your map this way:
 *
 *	$map = new GoogleChartMap("Europe","europe");
 *	$map->SetColors("FFFFFF","EAF7FE","FFFFFF");
 * 
 *	$map->AddRange('#FF0000');
 *	$map->AddRange('FFFFFF');
 *	$map->AddRange('00FF00');
 *	$map->AddRange('FFFFFF');
 *	$map->AddRange('0000FF');
 *	$map->AddRange('FFFFFF');
 *	$map->AddRange('00FFFF',7,"Polen");
 *
 *	$map->SetShades(80);
 *		
 *	$map->AddCountry("FR",50,1);
 *	$map->AddCountry("DE",50,3);
 *	$map->AddCountry("ES",100,5);
 *	$map->AddCountry("PL",100,7);
 *	$map->AddCountry("AT",20);
 *		
 *	$map->GetLegend(1,"Frankreich");
 *	$map->GetLegend(3,"Deutschland");
 *	$map->GetLegend(5,"Spanien");
 *	$this->content($map);
 *
 * As you can see here are 7 different color ranges. By adding white [FFFFFF] between the other colors you will 
 * get a colorrange which starts with white. If you leave white between other colors you will get colers between e.g.
 * red and blue
 *
 * There are two different wa 
 * 
 * AddCountry("FR",50,1) says display France with 50% between colorrange 1 which is [000000=>FF0000]
 * $map->AddCountry("AT",20) says display Austria with 20% of total range which is [000000=>00FFFF]
 */
class GoogleChartMap extends Template
{
	var $_width;
	var $_height;
	var $_title;
	var $_chtm;

	var $_empty = "FFFFFF";
	var $_water = "EAF7FE";

	private $_countries = array();
	private $_colors = array();
	private $_colorrange = array('FFFFFF','FF0000');
	private $_legends = array();
	private	$_shades = 80;

	function __initialize($title="",$chtm="world",$width=420,$height=220)
	{
		parent::__initialize();

		$this->_width  = $width;
		$this->_height = $height;
		$this->_title  = $title;
		$this->_chtm   = strtolower($chtm);

		$geographical_area = array(1=>"africa",2=>"asia",3=>"europe",4=>"middle_east",5=>"south_america",6=>"usa",7=>"world");

		if(!in_array($this->_chtm,$geographical_area))
			$this->_chtm = "world";

		if($this->_width>420)
			$this->_width = 420;

		if($this->_height>220)
			$this->_height = 220;

		$this->set("width",$this->_width);
		$this->set("height",$this->_height);
		$this->set("title",$this->_title);
	}

	function WdfRender()
	{
		$map  = "http://chart.apis.google.com/chart";
		$map .= "?chco=".$this->_empty.",".implode(",",$this->_colorrange);
		$map .= "&chd=e:".implode("",$this->_colors);
		$map .= "&chf=bg,s,".$this->_water;
		$map .= "&chtm=".$this->_chtm;
		$map .= "&chld=".implode("",$this->_countries);
		$map .= "&chs=".$this->_width."x".$this->_height;
		$map .= "&cht=t";

		$this->set("legends",$this->_legends);
		$this->set("map",$map);
		return parent::WdfRender();
	}
	
	/**
	 * Convert percent-values to GoogleCharts Values
	 * 100% are conform to 4095(.. as Symbol) in GoogleAPI
	 *
	 * @param percent-value $nValue
	 * @return mixed Google-Code
	 */
	protected function PercentToColor($nValue)
    {
    	$nValue = intval($nValue*40.95);

        if($nValue < 0 || $nValue > 4095)
 			return '__';

        $aMask = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
                        'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
                        'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j',
                        'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
                        'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7',
                        '8', '9', '-', '.');

		$res = $aMask[intval($nValue/64)].$aMask[$nValue%64];
        return $res;
	}

	/**
	 * Set Basecolors for Map
	 *
	 * @param str $range_start
	 * @param str $water
	 * @param str $empty
	 */
	function SetColors($range_start="FFFFFF",$water="EAF7FE",$empty="FFFFFF")
	{
		$range_start 	= str_replace("#","",$range_start);
		$water 			= str_replace("#","",$water);
		$empty			= str_replace("#","",$empty);

		$this->_colorrange = array($range_start);
		$this->_water = $water;
		$this->_empty = $empty;
	}

	/**
	 * Add new Color Range which extends $start_range
	 *
	 * @param string $color
	 * @param int $range_index
	 * @param string $legend_title
	 */
	function AddRange($color="FFFFFF",$range_index=false,$legend_title="")
	{
		$color 	= str_replace("#","",$color);
		$this->_colorrange[] = $color;
		
		if($range_index != false && $legend_title != "")
			$this->GetLegend($range_index,$legend_title,$shades);
	}

	/**
	 * Set number of legendshades
	 *
	 * @param int $shades
	 */
	function SetShades($shades)
	{
		$this->_shades = $shades;
	}
	
	/**
	 * Get Legend for specified colorrange index
	 *
	 * @param int $range_index
	 * @param string $legend_title
	 */
	function GetLegend($range_index=false,$legend_title)
	{
		if( $shades == false )
			$this->_legends[$legend_title] = $this->GetMapColors($this->_colorrange[$range_index-1],$this->_colorrange[$range_index]);
		else
			$this->_legends[$legend_title] = $this->GetMapColors($this->_colorrange[$range_index-1],$this->_colorrange[$range_index],$shades);
	}

	/**
	 * Add a Colored Country to Map
	 *
	 * @param str $country
	 * @param int $percent
	 * @param int $range_index
	 */
	function AddCountry($country,$percent=-1,$range_index=0)
	{
		$percent = $percent<=100?$percent:100;

		if( $range_index > 0 )
		{
			$x = 100/(count($this->_colorrange)-1);
			$y = $percent / (count($this->_colorrange)-1);
			$percent = $x * ($range_index-1) + $y;
		}
		$this->_countries[] = $country;
		$this->_colors[] = $this->PercentToColor($percent);
	}

	/**
	 * Generate legend colors
	 *
	 * @param string $color1
	 * @param string $color2
	 * @return array $color_shades
	 */
	function GetMapColors($color1,$color2)
	{
		$shades = $this->_shades;
		
		if($shades<3)
		{
			$color_shades = array(1=>$hex_color1,2=>$hex_color2);
			return $color_shades;
		}

		if(hexdec($color1)>hexdec($color2))
		{
			$tmp = $color1;
			$color1 = $color2;
			$color2 = $tmp;
		}

		$color1 = str_replace("#","",$color1);
		$color2 = str_replace("#","",$color2);

		$color1_array = str_split($color1,2);
		$color2_array = str_split($color2,2);

		$color_range_red 	= abs(hexdec($color1_array[0])-hexdec($color2_array[0]));
		$color_range_green 	= abs(hexdec($color1_array[1])-hexdec($color2_array[1]));
		$color_range_blue 	= abs(hexdec($color1_array[2])-hexdec($color2_array[2]));

		if($color_range_red==0 && $color_range_green==0 && $color_range_blue==0)
		{
			$color_shades = array(1=>$hex_color1,2=>$hex_color2);
			return $color_shades;
		}
		else
		{
			$color_step_red = floatval($color_range_red)/($shades-1);
			$color_step_green = floatval($color_range_green)/($shades-1);
			$color_step_blue = floatval($color_range_blue)/($shades-1);
			$color_shades = array();

			for($i=0;$i<$shades;$i++)
			{
				if($i==($shades-1))
				{
					$red = $color2_array[0];
					$green = $color2_array[1];
					$blue = $color2_array[2];
				}
				else
				{
					$red = sprintf("%02X",(hexdec($color1_array[0]) + intval($i*$color_step_red)));
					$green = sprintf("%02X",(hexdec($color1_array[1]) + intval($i*$color_step_green)));
					$blue = sprintf("%02X",(hexdec($color1_array[2]) + intval($i*$color_step_blue)));
				}
				$color_shades[$i]=$red.$green.$blue;
			}
		}
		return $color_shades;
	}
}
