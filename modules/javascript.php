<?
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
 
function javascript_init()
{
	global $CONFIG;

	if( !isset($CONFIG['javascript']['dirs']) )
		$CONFIG['javascript']['dirs'] = array("js/");

	if( !isset($CONFIG['javascript']['absolute_uri']) )
		$CONFIG['javascript']['absolute_uri'] = array();

	if( !is_array($CONFIG['javascript']['dirs']) )
		$CONFIG['javascript']['dirs'] = array($CONFIG['javascript']['dirs']);

	$useglobalcache = in_array("globalcache", $CONFIG['system']['modules']);
	if(!$useglobalcache)
	{
		if(!isset($_SESSION["mod_javascript_buffer"]))
			$_SESSION["mod_javascript_buffer"] = array();
	}

	// buffer replacement arrays:
	$GLOBALS["javascript_jsescape_find"] = array("'", "\"", "\r", "\n");
	$GLOBALS["javascript_jsescape_replace"] = array("\\'", "\\\"", "\\r", "\\n");
}

function jsFileExists($filename)
{
	global $CONFIG;
	$key = "e.".$filename.(isSSL() ? "_ssl" : "")."_".$_SERVER['SERVER_PORT'].(defined("_nc") ? _nc."_" : "");

	$useglobalcache = system_is_module_loaded("globalcache");
	$insession = (session_id() != "");
	if($useglobalcache)
	{
		// look for file in global cache
		$ret = globalcache_get("mod_javascript_buffer_".$key);
		if($ret != false)
			return $ret;
	}
	elseif($insession && isset($_SESSION["mod_javascript_buffer"][$key]))
		return $_SESSION["mod_javascript_buffer"][$key];
	foreach( $CONFIG['javascript']['dirs'] as $jsd )
	{
		if( file_exists($jsd.$filename) )
		{
			if($useglobalcache)
				globalcache_set("mod_javascript_buffer_".$key, $true = true, $CONFIG['system']['cache_ttl']);
			elseif($insession)
				$_SESSION["mod_javascript_buffer"][$key] = true;
			return true;
		}
	}

	if( file_exists($CONFIG['system']['path_root']."/js/".$filename) )
	{
		if($useglobalcache)
			globalcache_set("mod_javascript_buffer_".$key, $true = true, $CONFIG['system']['cache_ttl']);
		elseif($insession)
			$_SESSION["mod_javascript_buffer"][$key] = true;
		return true;
	}

//	$_SESSION["mod_javascript_buffer"]["e.".$filename] = false;
	return false;
}

function jsFile($filename)
{
	global $CONFIG, $IS_DEVELOPSERVER;

	if(($filename == "jquery.js") && isset($IS_DEVELOPSERVER) && !$IS_DEVELOPSERVER)
		$filename = "jquery.min.js";

	$fnversion = appendVersion($filename);
	$key = "f.".$fnversion.(isSSL() ? "_ssl" : "")."_".$_SERVER['SERVER_PORT'].(defined("_nc") ? _nc."_" : "");
	$useglobalcache = system_is_module_loaded("globalcache");
	$insession = (session_id() != "");

	if($useglobalcache)
	{
		// look for file in global cache
		$ret = globalcache_get("mod_javascript_buffer_".$key);
		if($ret != false)
			return $ret;
	}
	elseif($insession && isset($_SESSION["mod_javascript_buffer"][$key]))
		return $_SESSION["mod_javascript_buffer"][$key];
	
	// if amazon module is active and file exists in static folder user file from cloud
//	if( function_exists("amazonFile") && amazonFileExists($filename) )
//		return amazonFile($filename);
//	trace($filename." ".amazonFileExists($filename));
	foreach( $CONFIG['javascript']['dirs'] as $jsd )
	{
		if( file_exists($jsd.$filename) )
		{
			if( isset($CONFIG['javascript']['absolute_uri'][$jsd]) )
				$ret = $CONFIG['javascript']['absolute_uri'][$jsd].$fnversion;
			else
				$ret = $CONFIG['system']['url_root'].$jsd.$fnversion;
			if($useglobalcache)
			{
				globalcache_set("mod_javascript_buffer_".$key, $ret, $CONFIG['system']['cache_ttl']);
				$file = $jsd.$filename;
				globalcache_set("mod_javascript_buffer_u.".$ret, $file, $CONFIG['system']['cache_ttl']);
			}
			elseif($insession)
			{
				$_SESSION["mod_javascript_buffer"][$key] = $ret;
				$_SESSION["mod_javascript_buffer"]["u.".$ret] = $jsd.$filename;
			}
			return $ret;
		}
	}

	if( isset($CONFIG['system']['system_uri']) )
		$ret = $CONFIG['system']['system_uri']."js/".$fnversion;
	else
		$ret = makerelative($CONFIG['system']['path_root']."/js/".$fnversion);
	if($useglobalcache)
	{
		globalcache_set("mod_javascript_buffer_".$key, $ret, $CONFIG['system']['cache_ttl']);
		globalcache_set("mod_javascript_buffer_u.".$ret, $ret, $CONFIG['system']['cache_ttl']);
	}
	elseif($insession)
	{
		$_SESSION["mod_javascript_buffer"]["u.".$ret] = $ret;
		$_SESSION["mod_javascript_buffer"][$key] = $ret;
	}
	return $ret;
}

function jsEscape($text)
{
	return str_replace($GLOBALS["javascript_jsescape_find"], $GLOBALS["javascript_jsescape_replace"], $text);
}

function jsArray2JSON($data,$encode=true,$keystoignore=array())
{
	$res = array();
	$chkignore = (count($keystoignore) > 0);
	foreach($data as $k=>$v)
	{
		if( $k[0] == "\0" || ($chkignore && in_array($k,$keystoignore) ))
			continue;

		if( is_integer($v) )
			$res[] = "$k:$v";
		elseif( is_bool($v) )
			$res[] = "$k:".($v===true?"true":"false");
        elseif( is_array($v) )
            $res[] = "$k:".jsArray2JSON($v,$encode,$keystoignore);
        elseif( is_object($v) )
            $res[] = "$k:".jsArray2JSON($v,$encode,$keystoignore);
        elseif( is_string($v) && starts_with($v,'function') )
            $res[] = "$k:$v";
		elseif( $encode )
			$res[] = "$k:'".jsEscape($v)."'";
		else
			$res[] = "$k:".jsEscape($v);
	}
	return "{".implode(",",$res)."}";
}

function jsObject2JSON(&$obj,$keystoignore=array())
{
	return jsArray2JSON((array)$obj,true,$keystoignore);
}

?>