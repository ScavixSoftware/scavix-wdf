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
 
class DataSet extends Template
{
	var $Type = null;

	function __initialize(&$ds,$type,$title=false,$where="",$prms=array())
	{
		$this->Type = $type;

		$obj = $ds->CreateInstance($type,$where,$prms);
		parent::__initialize();
		foreach( $obj->GetAttributeNames() as $attr )
		{
			if( $obj->$attr == "" )
				continue;
			$obj->$attr = htmlspecialchars($obj->$attr);
		}
				
		$this->set("data",$this->ProcessDatasetProperties($obj));
		if( $title )
			$this->set("title",$title);
	}

	function ProcessDatasetProperties($obj)
	{
		return $obj;
	}

	function IsDateTimeString($str)
	{
		$pattern = '/(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2}):(\d{2})/';
		$is_date = preg_match($pattern, $str);

		if( $is_date == false )
			return false;
		else
			return true;
	}

	function IsDateString($str)
	{
		$pattern = '/(\d{4})-(\d{2})-(\d{2})/';
		$is_date = preg_match($pattern, $str);

		if( $is_date == false )
			return false;
		else
			return true;
	}
}

?>