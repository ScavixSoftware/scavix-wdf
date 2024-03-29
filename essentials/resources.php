<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
/**
 * Initializes the resources essential.
 * 
 * @return void
 */
function resources_init()
{
	global $CONFIG;

	if( !isset($CONFIG['resources']) )
		$CONFIG['resources'] = [];
	
	if( !isset($CONFIG['resources_system_url_root']) || !$CONFIG['resources_system_url_root'] )
		$CONFIG['resources_system_url_root'] = can_rewrite()
			?$CONFIG['system']['url_root'].'WdfResource/'
			:$CONFIG['system']['url_root'].'?wdf_route=WdfResource/';

	
	foreach( $CONFIG['resources'] as $i=>$conf )
	{
		if( substr($conf['url'],0,4) == 'http' )
			continue;
		if( substr($conf['url'],0,2) == '//' )
			continue;
		if( substr($conf['url'],0,2) == './' )
			continue;
		$CONFIG['resources'][$i]['url'] = $CONFIG['system']['url_root'].$conf['url'];
	}
    
    if(class_exists('Phar'))
        $path = Phar::running()?:realpath(__DIR__."/../");
    else
        $path = realpath(__DIR__."/../");
	
	$CONFIG['resources'][] = array
	(
		'ext' => 'js|css|png|jpg|jpeg|gif|svg|htc|ico|less',
		'path' => $path.'/res',
		'url' => $CONFIG['resources_system_url_root'].'res/',
		'append_nc' => true,
	);
	
	$CONFIG['class_path']['system'][] = __DIR__.'/resources/';
}

/**
 * Checks if a resource exists and returns it if so.
 * 
 * @param string $filename The resource name
 * @param bool $return_url If true returns an URL, else returns true or false depending on if the resource exists
 * @param bool $as_local_path If true returns not URL, but a filepath in local filesystem. Needs $return_url=true.
 * @param bool $nocache If true skips all internal caches and peforms a search now
 * @return string Depending on $return_url returns: (the resource URL or false on error) OR (true or false)
 */
function resourceExists($filename, $return_url = false, $as_local_path = false, $nocache = false)
{
	global $CONFIG;
    
	$cnc = substr(appendVersion('/'),1);
	$key = md5((isSSL()?"resource_ssl_$filename":"resource_$filename")."_{$cnc}".($as_local_path?"_l":"").$CONFIG['system']['url_root']);
	if( !$nocache && (($res = cache_get($key)) !== false) )
		return $return_url?$res:($res != "0");

	$ext = pathinfo($filename,PATHINFO_EXTENSION);
	foreach( $CONFIG['resources'] as $conf )
	{	
		if( strpos("|".$conf['ext']."|", "|".$ext."|") === false )
			continue;
		
        if( !file_exists($conf['path'].'/'.$filename) )
			continue;
		
		if( $as_local_path )
			return $conf['path'].'/'.$filename;

        if ($ext == 'less')
            $conf['url'] = $CONFIG['resources_system_url_root'] . 'res/';
			
		$nc = $conf['append_nc']?$cnc:'';
		$res = can_nocache()
			?$conf['url'].$nc.$filename
			:$conf['url'].$filename.(strpos($conf['url'],'?')===false?'?':'&')."_nc=".substr($nc,2,-1);
		if( !$nocache )
			cache_set($key, $res);
		return $return_url?$res:true;
	}
    if( !$nocache )
        cache_set($key, "0");
	return false;
}

/**
 * Returns aresource file, as local path or as URI.
 * 
 * @param string $filename The resource filename (relative or name only)
 * @param bool $as_local_path If true returns no URL, but a local path
 * @return string An URL to the resource or the local file path. FALSE on error.
 */
function resFile($filename, $as_local_path = false)
{
	if( $conf = resourceExists($filename,true,$as_local_path) )
		return $conf;
	return "";
}

/**
 * Registers a variable for use in LESS files.
 * 
 * @param string $name Variable name
 * @param string $value Variable value
 * @return void
 */
function register_less_variable($name,$value)
{
    if( !isset($_SESSION['resources_less_variables']) )
        $_SESSION['resources_less_variables'] = [];
	$_SESSION['resources_less_variables'][$name] = $value;
}

/**
 * Adds a folder to the LESS search path.
 * 
 * @param string $dir Folder to be added
 * @param mixed $key If given names this folder, so that it can be overwritten by another call to <add_less_import_dir>
 * @return void
 */
function add_less_import_dir($dir,$key=false)
{
    if(!$dir)
        return;
    if( !isset($_SESSION['resources_less_dirs']) )
        $_SESSION['resources_less_dirs'] = [];
	if( $key )
        $_SESSION['resources_less_dirs'][$key] = $dir;
    else
        $_SESSION['resources_less_dirs'][] = $dir;
    $_SESSION['resources_less_dirs'] = array_unique($_SESSION['resources_less_dirs']);
}

/**
 * Clears the LESS cache.
 * 
 * @return void
 */
function clear_less_cache()
{
    $tmpfolder = system_app_temp_dir('less',false);
    foreach( glob($tmpfolder."*.css") as $c )
        @unlink($c);
    foreach( glob($tmpfolder."*.cache") as $c )
        @unlink($c);
}

/**
 * Compiles LESS code to CSS.
 * 
 * @param string $less The LESS code
 * @param bool $use_vars Switch if defined variables should be used (default: true).
 * @return string The compiled CSS code
 */
function compile_less_code($less,$use_vars=false)
{
//    require_once(__DIR__.'/resources/lessphp/lessc.inc.php');
    $compiler = new ScavixWDF\LessCompiler();
    
    if( $use_vars && isset($_SESSION['resources_less_variables']) )
        $compiler->setVariables($_SESSION['resources_less_variables']);
    
    return $compiler->compile($less,__FUNCTION__);
}