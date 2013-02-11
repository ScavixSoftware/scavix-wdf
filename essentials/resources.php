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
	
	if( !isset($CONFIG['resources_system_url_root']) || !$CONFIG['resources_system_url_root'] )
		$CONFIG['resources_system_url_root'] = $CONFIG['system']['url_root'].'WdfResource/';

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

//	$key = (isSSL()?"Resource_SSL_$filename":"Resource_$filename").($as_local_path?"_l":"");
//	if( ($res = cache_get($key)) !== false )
//		return $return_url?$res:($res != "0");

	$cnc = substr(appendVersion('/'),1);
	$key = (isSSL()?"resource_ssl_$filename":"resource_$filename")."_{$cnc}".($as_local_path?"_l":"");
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

class WdfResource implements ICallable
{
	function __construct()
	{
		$res = explode("WdfResource",$_SERVER['PHP_SELF']);
		$res = realpath(__DIR__.'/../'.$res[1]);
		readfile($res);
		die();
	}
}