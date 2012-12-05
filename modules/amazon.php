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
 
function amazon_init()
{
	global $CONFIG,$IS_DEVELOPSERVER;

	if( !isset($CONFIG['amazon']['cloud_path']) )
		$CONFIG['amazon']['cloud_path'] = "http://".$_SERVER['HTTP_HOST']."/portal2/cloud_pamfax";

	if( !isset($CONFIG['amazon']['local_cloud_path']) )
		$CONFIG['amazon']['local_cloud_path'] = "cloud_pamfax";

	// is used to test the amazon cloud from dev server
	if( !isset($CONFIG['amazon']['use_amazon_for_dev']) )
		$CONFIG['amazon']['use_amazon_for_dev'] = false;

	// set classpath for publish page on dev systems
	if( $IS_DEVELOPSERVER )
		$CONFIG['class_path']['system'][] = dirname(__FILE__).'/amazon/';
}

/**
 * Checks if file exists in static folder. Because this folder is synchronized with amazon cloud
 * this funtions is used to check the existance of the file in the cloud as well
 *
 * @global <type> $CONFIG
 * @param <string> $filename
 * @return <type>
 */
function amazonFileExists($filename)
{
	global $CONFIG,$IS_DEVELOPSERVER;

	if( $IS_DEVELOPSERVER && !$CONFIG['amazon']['use_amazon_for_dev'] )
		return false;
    $file = $CONFIG['amazon']['local_cloud_path']."/".$filename;
//log_debug($file." ".file_exists($file));
	if( file_exists($file) )
		return true;

	return false;
}

/**
 * Return the path to the skin- or js-file in amazon cloud
 *
 * @global <type> $CONFIG
 * @param <string> $filename
 * @return <type> path to file in cloud
 */
function amazonFile($filename)
{
	global $CONFIG;
	$fnversion = appendVersion($filename);

	if( amazonFileExists($filename) )
		return $CONFIG['amazon']['cloud_path']."/".$fnversion;
}

/**
 * Returns the amazon base path + currently used default skin as path
 * 
 * @return <type>
 */
function amazonSkinPath()
{
	global $CONFIG, $IS_DEVELOPSERVER;
	if( $IS_DEVELOPSERVER && !$CONFIG['amazon']['use_amazon_for_dev'] )
		return false;
	return $CONFIG['amazon']['cloud_path']."/".$BRANDINGFILESSUBDIR."/";
}
?>
