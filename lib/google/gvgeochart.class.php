<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Google;

/**
 * A geo chart.
 * 
 * See https://developers.google.com/chart/interactive/docs/gallery
 */
class gvGeoChart extends GoogleVisualization
{
	/**
	 * @override
	 */
	function __construct($options=[],$query=false,$ds=false)
	{
		parent::__construct('GeoChart',$options,$query,$ds);
		$this->_loadPackage('geochart');
	}
	
	/**
	 * @shortcut <GoogleVisualization::opt>('displayMode',$mode)
	 */
	function setDisplayMode($mode)
	{
		return $this->opt('displayMode',$mode);
	}
	
	/**
	 * @shortcut <GoogleVisualization::opt>('colorAxis',array('minValue'=>$min,'maxValue'=>$max,'colors'=>$colors))
	 */
	function setColorAxis($min,$max,$colors)
	{
		return $this->opt('colorAxis',array('minValue'=>$min,'maxValue'=>$max,'colors'=>$colors));
	}
	
	/**
	 * @shortcut <GoogleVisualization::opt>('region',$region)
	 */
	function setRegion($region)
	{
		return $this->opt('region',$region);
	}
}