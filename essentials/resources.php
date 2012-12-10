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
 
function resources_init()
{
	global $CONFIG;

	$GLOBALS["loaded_modules"]['skins'] = __FILE__;
	$GLOBALS["loaded_modules"]['javascript'] = __FILE__;
	
	if( !isset($CONFIG['resources']) )
		$CONFIG['resources'] = array();
	
	if( !isset($CONFIG['resources_system_url_root']) )
		$CONFIG['resources_system_url_root'] = $CONFIG['system']['url_root'].'system/';

	$CONFIG['resources'][] = array
	(
		'ext' => 'js',
		'path' => realpath(__DIR__.'/../js/'),
		'url' => $CONFIG['resources_system_url_root'].'js/',
		'append_nc' => true,
	);
	$CONFIG['resources'][] = array
	(
		'ext' => 'css|png|jpg|jpeg|gif|htc|ico',
		'path' => realpath(__DIR__.'/../skin/'),
		'url' => $CONFIG['resources_system_url_root'].'skin/',
		'append_nc' => true,
	);
}

function resourceExists($filename, $return_url = false, $as_local_path = false)
{
	global $CONFIG;

	$key = (isSSL()?"ssl_resource_$filename":"resource_$filename").($as_local_path?"_l":"");
	if( ($res = cache_get($key)) !== false )
		return $return_url?$res:($res != "0");

	$cnc = str_replace(array("_", "="), "", _nc).'/';
	$key = "$cnc$key".($as_local_path?"_l":"");
	if( ($res = cache_get($key)) !== false )
		return $return_url?$res:($res != "0");

	$ext = pathinfo($filename,PATHINFO_EXTENSION);
	$reg = "/(^$ext$|^$ext\||\|$ext$)/i";
	foreach( $CONFIG['resources'] as $conf )
	{	
		if(strpos("|".$conf['ext']."|", "|".$ext."|") === false)
			continue;
		
		if( !file_exists($conf['path'].'/'.$filename) )
			continue;
		
		if( $as_local_path )
			return $conf['path'].'/'.$filename;
			
		$nc = $conf['append_nc']?$cnc:'';
		$res = $conf['url'].$nc.$filename;
		cache_set($key, $res);
		return $return_url?$res:true;
	}
	cache_set($key, "0");
	return false;
}

function resFile($filename, $as_local_path = false)
{
	if( $conf = resourceExists($filename,true,$as_local_path) )
		return $conf;
	return "";
}

/* compatibility to old skins module */
if( !function_exists('skinFileExists') )
{
	function skinFileExists($filename){ return resourceExists($filename); }
	function skinPath($for_file=false){ throw new Exception("skinPath function is not supported anymore"); }
	function skinFile($filename, $append_version=true){ return resFile($filename); }
	function skinFileFS($filename){ throw new Exception("skinFileFS function is not supported anymore"); }
}

/* compatibility to old javascript module */
if( !function_exists('jsFileExists') )
{
	function jsFileExists($filename){ return resourceExists($filename); }
	function jsFile($filename){ return resFile($filename); }
	
	/* just copy+pasted the following functions */
	
	function jsEscape($text)
	{
		/* well...this block has been added, not copy+pasted :) */
		if( !isset($GLOBALS["javascript_jsescape_find"]) )
		{
			$GLOBALS["javascript_jsescape_find"] = array("'", "\"", "\r", "\n");
			$GLOBALS["javascript_jsescape_replace"] = array("\\'", "\\\"", "\\r", "\\n");
		}
		
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
}
?>