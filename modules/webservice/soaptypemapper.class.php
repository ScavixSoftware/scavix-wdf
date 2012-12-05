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
 
class SoapTypeMapper
{
	function &GetClassMap($wrapper_classes_dir = false)
	{
		global $CONFIG;

		if( $wrapper_classes_dir === false )
			$wrapper_classes_dir = $CONFIG['webservice']['base_path']."current/";

		//log_debug($wrapper_classes_dir."*.class.php");
		$class_map = array();
		foreach( glob($wrapper_classes_dir."*.class.php") as $wrapper_classes_file )
		{

			$content = file_get_contents($wrapper_classes_file);

			$pattern = '/class\s([^\s]*)\sextends\s[^\{\s]+\s\{/';
			if( !preg_match_all($pattern, $content, $parts, PREG_SET_ORDER) )
				continue;

			foreach( $parts as $class )
				$class_map[$class[1]] = $class[1];
		}
		//log_debug($class_map);
		return $class_map;
	}

	function &GetTypeMap()
	{
		$type_map = array();
		$type_map = array(
			array(
				"type_name"=>"dateTime",
				"type_ns"=>"http://www.w3.org/2001/XMLSchema",
				"from_xml"=>array(&$this,'dateTime_from_xml')/*,
				"to_xml"=>array(&$this,'dateTime_to_xml')*/
			),
//			array(
//				"type_name"=>"short",
//				"type_ns"=>"http://www.w3.org/2001/XMLSchema",
//				"from_xml"=>array(&$this,'integer_from_xml'),
//				"to_xml"=>array(&$this,'integer_to_xml')
//			),
		);
		return $type_map;
	}

	function dateTime_from_xml($string)
	{
		$string = strip_tags($string);

		if( !$string )
			return null;

		$dt = explode("T",$string);
		$date = explode("-",$dt[0]);
		$time = explode(":",$dt[1]);

//        if( $date[0] == "1901" )
//            $date[0] = "1902";

		$res = mktime((int)$time[0],(int)$time[1],(int)$time[2],(int)$date[1],(int)$date[2],(int)$date[0]);
//        log_debug($date);
//		log_debug("dateTime_from_xml($string) -> $res");
		return $res;
	}

	function dateTime_to_xml($time)
	{
		$res = date("Y-m-d\\TH:i:s",$time);
//		log_debug("dateTime_to_xml($time) -> $res");
		return "$res";
	}
}

?>