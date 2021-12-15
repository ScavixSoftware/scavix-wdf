<?php
/**
 * Scavix Web Development Framework
 *
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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Tasks;

use ScavixWDF\Model\DataSource;

/**
 * @internal CLI only Task run `php index.php db` for info
 */
class DbTask extends Task
{
    function Run($args)
    {
        log_warn("Syntax: db-(info|list|update)");
    }
        
    private function ensureArgs($args)
    {
        if( count($args) == 0 )
        {
            log_info("Please specify datasource: ".implode(", ",array_keys($GLOBALS['CONFIG']['model'])));
            return false;
        }
        $alias = array_shift($args);
        $ds = DataSource::Get($alias);
        if( !$ds ) return false;

        $tab = array_shift($args);
        if( !$tab )
        {
            if( defined("DATABASE_VERSION") && defined("DATABASE_FOLDER") )
            {
                log_info("DB Versioning enabled:");
                log_info("\tSQL folder: ",DATABASE_FOLDER);
                log_info("\tTarget version: ",DATABASE_VERSION);
                if( $ds->Driver->tableExists('wdf_versions') )
                    log_info("\tDatabase version: ",$ds->ExecuteScalar("SELECT max(version) FROM wdf_versions"));
                else
                    log_info("\tDatabase version: <none>");
            }
            
            $tabs = $ds->Driver->listTables();
            log_info("Tables:\n\t".implode("\n\t",$tabs));
            return false;
        }
        if( !$ds->Driver->tableExists($tab) )
        {
            log_error("Table not found: $alias/$tab");
            return false;
        }
        return [$ds,$tab,$args];
    }
    
    function Info($args)
    {
        $args = $this->ensureArgs($args);
        if( !$args ) return;
        list($ds,$tab,$args) = $args;
        $schema = $ds->Driver->getTableSchema($tab);
        //log_info("Columns:\n\t".implode("\n\t",$cols));
        log_info("Schema:",$schema);
    }
    
    function List($args)
    {
        $args = $this->ensureArgs($args);
        if( !$args ) return;
        list($ds,$tab,$args) = $args;
        
        if( count($args) > 1 )
        {
            $limit = min(1000,abs(intval(array_shift($args))));
            $offset = abs(intval(array_shift($args)));
        }        
        elseif( count($args) > 0 )
        {
            $limit = min(1000,abs(intval(array_shift($args))));
            $offset = 0;
        }        
        else
        {
            $limit = 20;
            $offset = 0;
        }
        $lines = [];
        foreach( $ds->Query($tab)->page($offset,$limit) as $row )
            $lines[] = $row->AsArray();
        
        $format = array_shift($args)?:'json';
        switch( $format )
        {
            case 'json': 
                log_debug("JSON:\n".json_encode($lines,JSON_PRETTY_PRINT)); 
                return;
        }
        log_debug("Items =",$lines);
    }
    
    function Update($args)
    {
        if( !defined("DATABASE_VERSION") || !defined("DATABASE_FOLDER") )
            \ScavixWDF\WdfException::Raise("You need to define DATABASE_VERSION and DATABASE_FOLDER");
        $ds = DataSource::Get();
        
        $v = intval(array_shift($args));
        if( $v )
        {
            log_info(__METHOD__,"Preparing to replay version $v");
            $ds->ExecuteSql("DELETE FROM wdf_versions WHERE `version`=$v");
        }
        
        model_update_db($ds, DATABASE_VERSION, DATABASE_FOLDER, function($v)
        {
            log_info(__METHOD__,"Updated to version $v");
        });
    }
    
    function Vars($args)
    {
        $alias = array_shift($args);
        $ds = DataSource::Get($alias);
        if( !$ds )
        {
            log_warn("Please specify valid datasource as first argument");
            return;
        }
        $lvars = $ds->ExecuteSql("SHOW VARIABLES")->Enumerate('Value',false,'Variable_name');
        $gvars = $ds->ExecuteSql("SHOW GLOBAL VARIABLES")->Enumerate('Value',false,'Variable_name');
        $lines = [];
        foreach( $lvars as $n=>$v )
            $lines[] = "$n\t= $v".(isset($gvars[$n])&&$v!=$gvars[$n]?"\tGLOBAL: {$gvars[$n]}":"");
            
        if( PHP_SAPI == 'cli' )
            echo "Variables:\n".implode("\n",$lines)."\n";
        else
            log_info("Variables:\n".implode("\n",$lines)."\n");
    }
    
    function Exec($args)
    {
        $alias = array_shift($args);
        $ds = DataSource::Get($alias);
        if( !$ds )
        {
            log_warn("Please specify valid datasource as first argument");
            return;
        }
        
        $sql = implode(" ",$args);
        log_debug("Executing SQL: $sql");
        log_info("Result: ".json_encode($ds->ExecuteSql($sql)->results(),JSON_PRETTY_PRINT));
    }
    
    function ProcessWdfTasks($args)
    {
        logging_add_category(getmypid());
        $ttl = intval(array_shift($args)?:0)?:0;
        $eol = time() + $ttl;
        WdfTaskModel::FreeOrphans();
        $task = WdfTaskModel::Reserve();
        set_time_limit(0);
        while( $task || time()<$eol )
        {
            if( $task )
            {
                logging_add_category("{$task->name}");
                $task->Run();
                logging_remove_category("{$task->name}");
            }
            else
                usleep(100000);
            $task = WdfTaskModel::Reserve();
        }
    }
}
