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

use ScavixWDF\Model\DataSource;
use ScavixWDF\WdfDbException;

/**
 * Initializes the model essential.
 * 
 * @return void
 */
function model_init()
{
	global $CONFIG;
	
	$CONFIG['class_path']['model'][]   = __DIR__.'/model/';
	$CONFIG['class_path']['model'][]   = __DIR__.'/model/driver/';
    
    // trick out the autoloader as it consults the cache which needs a model thus circular...
    require_once(__DIR__.'/model/pdolayer.class.php');
    require_once(__DIR__.'/model/resultset.class.php');
    require_once(__DIR__.'/model/driver/idatabasedriver.class.php');
    require_once(__DIR__.'/model/datasource.class.php');

	$GLOBALS['MODEL_DATABASES'] = array();
	$GLOBALS['MODEL_REGISTER'] = array();

	if( !is_array($CONFIG['model']) )
		WdfDbException::Raise("Please configure at least one DB in CONFIG['model']");

	foreach( $CONFIG['model'] as $name=>$mod )
	{
		if( !is_array($mod) )
			continue;

		if( isset($mod['connection_string']) )
		{
			model_init_db(
				$name,
				$mod['connection_string'],
				isset($mod['datasource_type'])?$mod['datasource_type']:"DataSource"
			);
		}
		else
			WdfDbException::Raise("Unable to initialize database '$name'! Missing CONFIG information.");
	}
}

/**
 * Initializes a database connection.
 * 
 * @param string $name Alias name (like system, internal, data, mydb,...)
 * @param string $constr Connection string
 * @param string $dstype Datasource type
 * @return void
 */
function model_init_db($name,$constr,$dstype='DataSource')
{
	global $MODEL_DATABASES;
	
	$MODEL_DATABASES[$name] = array($dstype,$constr);
}

/**
 * @internal Stores all connections states
 */
function model_store()
{
	global $MODEL_DATABASES;
	foreach( $MODEL_DATABASES as $dbname=>$db )
		if( !is_array($db) )
			store_object($db,$dbname);
}

/**
 * Get a database connection.
 * 
 * @param string $name The datasource alias.
 * @return DataSource The database connection
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
		list($dstype,$constr) = $MODEL_DATABASES[$name];
		$dstype = fq_class_name($dstype);
		$model_db = new $dstype($name,$constr);
		if( !$model_db )
			WdfDbException::Raise("Unable to connect to database '$name'.");
		$MODEL_DATABASES[$name] = $model_db;
	}

	return $MODEL_DATABASES[$name];
}

/**
 * Get the name/alias of a given DataSource.
 * 
 * @param DataSource $ds The datasource
 * @return string the name/alias
 */
function model_datasource_name($ds)
{
	return $ds->_storage_id;
}

/**
 * Creates a valid connection string.
 * 
 * @param string $type Shouls be 'DataSource'
 * @param string $server The Db server
 * @param string $username The DB username
 * @param string $password The DB password
 * @param string $database The database name
 * @return string A valid connection string
 */
function model_build_connection_string($type,$server,$username,$password,$database)
{
	return sprintf("%s://%s:%s@%s/%s",$type,$username,$password,$server,$database);
}

/**
 * Updates the given datasource to a given version.
 *
 * To use the DB versioning you must:
 * 1. Create a folder containing SQL scripts '<dbversion>.sql', <dbversion> must be 0-padded to a length of 4 chars ('0001.sql')
 * 2. Call this function like this model_update_db('system',1,'/path/to/sql/scripts');
 * 
 * SQL scripts are executed using <model_run_script>, please see documentation there for features.
 * 
 * Update will stop on error which will be written to the wdf_versions table. You'll then have
 * to correct the script and remove the versions dataset from wdf_versions to let it run again.
 * 
 * @param mixed $datasource The datasource to be used
 * @param string|int $version Target version
 * @param string $script_folder Folder with the SQL update scripts
 * @return array Array of results
 */
function model_update_db($datasource,$version,$script_folder,callable $after_sql_callback = null)
{
    $ds = ($datasource instanceof DataSource)?$datasource:model_datasource($datasource);
    $ds->ExecuteSql(
        "CREATE TABLE IF NOT EXISTS `wdf_versions` (
            `version` int(11) NOT NULL,
            `started` datetime DEFAULT NULL,
            `finished` datetime DEFAULT NULL,
            `error` text COLLATE utf8_unicode_ci,
            PRIMARY KEY (`version`)
          ) CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );
    
    $current = $ds->ExecuteScalar("SELECT version FROM wdf_versions ORDER BY version DESC");
    if( $current == $version )
        return [];
    if( !$current )
        $current = 0;
    
    $res = [];
    $files = glob("$script_folder/*.sql");
    sort($files);
    foreach( $files as $file )
    {
        if( !preg_match('/(\d+)\.sql/',$file,$m) )
        {
            //log_debug("Ignoring file '$file': pattern mismatch");
            continue;
        }
        //log_debug("Cheking '$file': ",$m);
        $v = intval($m[1]);
        
        // skip past and future updates
        if( $v < $current || $v > $version )
            continue;
        
        // 'reserve' the update 
        $ds->ExecuteSql("INSERT IGNORE INTO wdf_versions(version,started)VALUES(?,now())",$v);
        if( $ds->getAffectedRowsCount() == 0 )
            continue;
        
        try
        {
            log_debug("Upgrading DB to version '$v'");
            model_run_script($file,$ds,true);
            if(!is_null($after_sql_callback))
            {
                log_debug("Executing callback function");
                $after_sql_callback($v);
            }
            $ds->ExecuteSql("UPDATE wdf_versions SET finished=now() WHERE version=?",$v);
            $res[$v] = 'success';
        }
        catch(Exception $ex)
        {
            $ds->ExecuteSql("UPDATE wdf_versions SET error=? WHERE version=?",array($ex->getMessage(),$v)); 
            log_error("Error upgrading DB to version '$v'",$ex);
            $res[$v] = $ex->getMessage();
        }
    }
    return $res;
}

/**
 * Run an SQL script from a file.
 *
 * SQL scripts can contain line-comments, that may start with '#' or '--' but must not contain leading white-spaces.
 * Script files will be executed directly, so each statement must be terminated with semi-colon (;).
 * Scripts my inlcude other files (like view create/update statements) like this: @include(views/my_view.sql);
 * These include statements must be one-per-line and they must be terminated by semi-colon (;).
 * The path must be relative to the including SQL file.
 * 
 * Scripts should always ensure that statements can be executed without erroring out, to
 * help you with that, you can prepend an @-symbol to each statement. Errors will be ignored and only logged
 * to the error.log. This way you can for example 'ALTER TABLE's with columns that alread exist.
 *
 * If @ is not used, an Exception will be thrown on SQL errors.
 * 
 * @param string $file Script filename
 * @param mixed $datasource The datasource to be used
 * @param bool $verbose If true writes some information to the log.
 * @return void
 */
function model_run_script($file,$datasource=false,$verbose=false)
{
    //log_debug(__FUNCTION__,$file);
    $ds = ($datasource instanceof DataSource)
        ?$datasource
        :($datasource?model_datasource($datasource):DataSource::Get());
    
    $sql = file_get_contents($file);
            
    $sql = preg_replace_callback('/@include\((.*)\);/',function($m)use($file)
    {
        $inc = @file_get_contents(dirname($file)."/".$m[1]);
        if( !$inc )
        {
            log_error("SQL include not found: {$m[1]}");
            return '';
        }
        return preg_replace_callback('/@include\((.*)\);/',function($circ)use($m)
        {
            log_error("Deep level SQL include detected, ignoring: {$circ[1]} in file {$m[1]}");
            return '';
        },"$inc;");
    },$sql);

    $sql = preg_replace("/^(#|--).*$/m", "", $sql);

    foreach( preg_split('/;[\r\n]+/', $sql) as $statement )
    {
        $statement = trim($statement);
        $ignore = isset($statement[0]) && $statement[0]=='@';
        $statement = trim($statement,";@ \t\r\n");
        if( $statement )
        {
            try
            {
                if( !ends_with($statement,';') )
                    $statement .= ";";
                if( $verbose )
                    log_debug("Running SQL: '$statement'");
                $ds->ExecuteSql($statement);
            }
            catch(Exception $first)
            {
                if( !$ignore )
                    throw $first;
                if( $verbose )
                    log_debug("Expected (handled) error: ",$first->getMessage());
            }
        }
    }
}