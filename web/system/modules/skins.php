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
 
function skins_init()
{
	global $CONFIG;

	if( !isset($CONFIG['skins']['base_dir']) )
		$CONFIG['skins']['base_dir'] = "skins/";

	if( !isset($CONFIG['skins']['default_skin']) )
		$CONFIG['skins']['default_skin'] = "default";

	if( !isset($CONFIG['skins']['absolute_uri']) )
		$CONFIG['skins']['absolute_uri'] = array();

	if( !is_array($CONFIG['skins']['base_dir']) )
		$CONFIG['skins']['base_dir'] = array($CONFIG['skins']['base_dir']);

	$useglobalcache = in_array("globalcache", $CONFIG['system']['modules']);
	if(!$useglobalcache)
	{
		if(!isset($_SESSION["skin_buffer"]))
			$_SESSION["skin_buffer"] = array();
	}
}

function skinFileExists($filename)
{
	global $CONFIG;
	$key = "e.".$filename.(isSSL() ? "_ssl" : "")."_".$_SERVER['SERVER_PORT'].(defined("_nc") ? _nc."_" : "");

	$useglobalcache = system_is_module_loaded("globalcache");
	$insession = (session_id() != "");

	if($useglobalcache)
	{
		// look for file in global cache
		$ret = globalcache_get("skin_buffer_".$key);
		if($ret != false)
			return $ret;
	}
	elseif($insession && isset($_SESSION["skin_buffer"][$key]))
		return $_SESSION["skin_buffer"][$key];
	foreach( $CONFIG['skins']['base_dir'] as $sbd )
	{
		if( file_exists($sbd.$CONFIG['skins']['default_skin']."/$filename") )
		{
			if($useglobalcache)
				globalcache_set("skin_buffer_".$key, $true = true, $CONFIG['system']['cache_ttl']);
			elseif($insession)
				$_SESSION["skin_buffer"][$key] = true;
			return true;
		}
	}
	if( file_exists(makerelative($CONFIG['system']['path_root']."/skin/".$filename)) )
	{
		if($useglobalcache)
			globalcache_set("skin_buffer_".$key, $true = true, $CONFIG['system']['cache_ttl']);
		elseif($insession)
			$_SESSION["skin_buffer"][$key] = true;
		return true;
	}
//	$_SESSION["skin_buffer"][$key] = false;
	return false;
}

/**
 * If Amazon Module is activated this function returns the amazon path + default skin path
 * else it returns the projectspecific skin path + default skin path
 *
 * @global <type> $CONFIG
 * @return <type>
 */
function skinPath($for_file=false)
{
	global $CONFIG;
	$key = "p.".$for_file.(isSSL() ? "_ssl" : "")."_".$_SERVER['SERVER_PORT'].(defined("_nc") ? _nc."_" : "");
	$useglobalcache = system_is_module_loaded("globalcache");
	$insession = (session_id() != "");

	if($useglobalcache)
	{
		// look for file in global cache
		$ret = globalcache_get("skin_buffer_".$key);
		if($ret != false)
			return $ret;
	}
	elseif($insession && isset($_SESSION["skin_buffer"][$key]))
		return $_SESSION["skin_buffer"][$key];

	if( system_is_module_loaded("amazon") )
    {
		 $p = amazonSkinPath();
         if($p !== false)
		 {
			if($useglobalcache)
				globalcache_set("skin_buffer_".$key, $p, $CONFIG['system']['cache_ttl']);
			elseif($insession)
				$_SESSION["skin_buffer"][$key] = $p;
            return $p;
		 }
    }

	if( count($CONFIG['skins']['base_dir']) > 1 )
	{
		foreach( $CONFIG['skins']['base_dir'] as $sbd )
		{
			if( file_exists($sbd.$CONFIG['skins']['default_skin']."/$filename") )
			{
				if($useglobalcache)
					globalcache_set("skin_buffer_".$key, $sbd, $CONFIG['system']['cache_ttl']);
				elseif($insession)
					$_SESSION["skin_buffer"][$key] = $sbd;
				return $sbd;
			}
		}
	}
	
	$ret = $CONFIG['skins']['base_dir'][0].$CONFIG['skins']['default_skin']."/";
	if($useglobalcache)
		globalcache_set("skin_buffer_".$key, $ret, $CONFIG['system']['cache_ttl']);
	elseif($insession)
		$_SESSION["skin_buffer"][$key] = $ret;
	return $ret;
}
/**
 * Returns the imagepath for any skinfile
 *
 * @global <type> $CONFIG
 * @param <type> $filename
 * @param <type> $append_version => to avoid trouble with functions like getimagesize() which cant handle the apended version 
 * @return <type>
 */
function skinFile($filename, $append_version=true)
{
	global $CONFIG;

	if( $append_version )
		$fnversion = appendVersion($filename);
	else
		$fnversion = $filename;

	$key = "f.".$fnversion.(isSSL() ? "_ssl" : "")."_".$_SERVER['SERVER_PORT'].(defined("_nc") ? _nc."_" : "");
	$useglobalcache = system_is_module_loaded("globalcache");
	$insession = (session_id() != "");

	if($useglobalcache)
	{
		// look for file in global cache
		$ret = globalcache_get("skin_buffer_".$key);
		if($ret != false)
			return $ret;
	}
	elseif($insession && isset($_SESSION["skin_buffer"][$key]))
		return $_SESSION["skin_buffer"][$key];


//	// if amazon module is active and file exists in static folder user file from cloud
//	if( function_exists("amazonFile") && amazonFileExists($CONFIG['skins']['default_skin']."/".$filename) )
//		return amazonFile($CONFIG['skins']['default_skin']."/".$filename);
//	if( $is_static )
//		return $relative_cloud_path;

	foreach( $CONFIG['skins']['base_dir'] as $sbd )
	{
		$path = $sbd.$CONFIG['skins']['default_skin'];
		$realpath = realpath($path."/".$filename);
//		log_debug($realpath);
		if( $realpath != "" && file_exists($realpath) )
		{
//	        log_debug("y: $path/$fnversion -> ".makerelative("$path/$fnversion"));
			if( isset($CONFIG['skins']['absolute_uri'][$sbd]) )
				$ret = $CONFIG['skins']['absolute_uri'][$sbd].$CONFIG['skins']['default_skin']."/".$fnversion;
			else
				$ret = $CONFIG['system']['url_root'].$path."/".$fnversion;
			if($useglobalcache)
			{
				globalcache_set("skin_buffer_".$key, $ret, $CONFIG['system']['cache_ttl'], $CONFIG['system']['cache_ttl']);
				globalcache_set("skin_buffer_u.".$ret, $realpath, $CONFIG['system']['cache_ttl'], $CONFIG['system']['cache_ttl']);
			}
			elseif($insession)
			{
				$_SESSION["skin_buffer"][$key] = $ret;
				$_SESSION["skin_buffer"]["u.".$ret] = $realpath;
			}
			return $ret;
		}
	}

	if( isset($CONFIG['system']['system_uri']) )
		$ret = $CONFIG['system']['system_uri']."skin/".$fnversion;
	else
		$ret = makerelative($CONFIG['system']['path_root']."/skin/".$fnversion);
//    log_debug("n: $p $realpath -> ".makerelative($CONFIG['system']['path_root']."/skin/".$fnversion));
	if($useglobalcache)
	{
		globalcache_set("skin_buffer_".$key, $ret, $CONFIG['system']['cache_ttl']);
		globalcache_set("skin_buffer_u.".$ret, $ret, $CONFIG['system']['cache_ttl']);
	}
	elseif($insession)
	{
		$_SESSION["skin_buffer"]["u.".$ret] = $ret;
		$_SESSION["skin_buffer"][$key] = $ret;
	}
    return $ret;
}

function skinFileFS($filename)
{
	global $CONFIG;
	foreach( $CONFIG['skins']['base_dir'] as $sbd )
	{
		if( file_exists($sbd.$CONFIG['skins']['default_skin']."/$filename") )
		{
			$path = $CONFIG['system']['path_root']."/".$sbd.$CONFIG['skins']['default_skin'];
			return realpath($path."/".$filename);
		}
	}

	$path = $CONFIG['system']['path_root']."/".$CONFIG['skins']['base_dir'][0].$CONFIG['skins']['default_skin'];
	return realpath($path."/".$filename);
}

?>