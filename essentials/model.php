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

function model_init()
{
	global $CONFIG;
	
	$CONFIG['class_path']['model'][]   = dirname(__FILE__).'/model/';
	$CONFIG['class_path']['model'][]   = dirname(__FILE__).'/model/driver/';
    
    // trick out the autoloader as it consults the cache which needs a model thus circular...
    require_once(__DIR__.'/model/system_datasource.class.php');
    require_once(__DIR__.'/model/pdolayer.class.php');
    require_once(__DIR__.'/model/resultset.class.php');
    require_once(__DIR__.'/model/driver/idatabasedriver.class.php');
    require_once(__DIR__.'/model/datasource.class.php');

	$GLOBALS['MODEL_DATABASES'] = array();
	$GLOBALS['MODEL_REGISTER'] = array();

	if( !is_array($CONFIG['model']) )
		system_die("Please configure at least one DB in CONFIG['model']");

	foreach( $CONFIG['model'] as $name=>$mod )
	{
		if( !is_array($mod) )
			continue;

		if( isset($mod['connection_string']) )
		{
			model_init_db(
				$name,
				isset($mod['datasource_type'])?$mod['datasource_type']:"System_DataSource",
				$mod['connection_string'],
				isset($mod['auto_create_tables'])?$mod['auto_create_tables']:false,
				(isset($mod['debug'])?$mod['debug']:false),
				(isset($mod['usememcache'])?$mod['usememcache']:true)
			);
		}
		else
			system_die("Unable to initialize database '$name'! Missing CONFIG information.");
	}
}

function model_init_db($name,$dstype,$constr,$autoct=false,$debug=false,$usememcache=true)
{
	global $MODEL_DATABASES;
	
	if( $dstype == "System_DataSource" ) $dstype = "DataSource";

	$MODEL_DATABASES[$name] = array($dstype,$constr,$autoct,$debug,$usememcache);
}

function model_store()
{
	global $MODEL_DATABASES;
	foreach( $MODEL_DATABASES as $dbname=>$db )
		if( !is_array($db) )
			store_object($db,$dbname,true);
}

/**
 * Return a ADOConnection instance.
 * @param string $name The datasource name.
 * @return ADOConnection
 */
function &model_datasource($name)
{
	global $MODEL_DATABASES;

	if( strpos($name,"DataSource::") !== false )
	{
		$name = explode("::",$name);
		$name = $name[1];
	}

	if( !isset($MODEL_DATABASES[$name]) )
	{
		if( function_exists('model_on_unknown_datasource') )
		{
			$res = model_on_unknown_datasource($name);
			return $res;
		}
		log_fatal("Unknown datasource '$name'!");
		$res = null;
		return $res;
	}

	if( is_array($MODEL_DATABASES[$name]) )
	{
		list($dstype,$constr,$autoct,$debug,$usememcache) = $MODEL_DATABASES[$name];
		$model_db = new $dstype($name,$constr);
		if( !$model_db )
			system_die("Unable to connect to database '$name'.");

		if( $usememcache && session_use_memcache() )
		{
			// todo: implement caching
		}
		$MODEL_DATABASES[$name] = $model_db;
	}

	return $MODEL_DATABASES[$name];
}

function model_datasource_name(&$ds)
{
	return $ds->_storage_id;
}

function model_build_connection_string($type,$server,$username,$password,$database)
{
	return sprintf("%s://%s:%s@%s/%s",$type,$username,$password,$server,$database);
}

?>