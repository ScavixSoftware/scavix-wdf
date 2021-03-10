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

/**
 * @internal CLI only Task run `php index.php check` for info
 */
class CheckTask extends Task
{
    function Run($args)
    {
        $status = [];
        $status["short_open_tag"] = is_in(ini_get('short_open_tag'),'on','On','1','true','True','TRUE','ON','oN')
            ?'ok':"Needs to be enabled";
        $status["display_errors"] = !is_in(ini_get('display_errors'),'on','On','1','true','True','TRUE','ON','oN')
            ?'ok':"Should be disabled";
        
        $this->write("Settings",$status);
        
        $status = [];
        $status["php-curl"]    = function_exists('curl_init')?'ok':"CURL is missing, required for some features";
        $status["php-xml"]     = function_exists('utf8_encode')?'ok':"XML is missing, required for some features";
        $status["php-sqlite3"] = extension_loaded('pdo_sqlite')?'ok':"(optional) SQLite driver is missing";
        $status["php-mysql"]   = extension_loaded('pdo_mysql')?'ok':"(optional) MySQL driver is missing";
        
        $this->write("Dependencies",$status);
    }
    
    private function write($title, $status)
    {
        $log = logging_get_logger('cli');
        $log->debug("$title:");
        foreach( $status as $n=>$v )
            if( $v == 'ok' )
                $log->debug(" * ".str_pad("$n",20),$v);
            else
                $log->warn(" * ".str_pad("$n",20),$v);
    }
    
    function Strings($args)
    {
        $dir = realpath(ifavail($args,'dir'));
        if( !$dir )
            return log_info("Syntax: check-strings dir=<base-folder>");
        
        translation_known_constants(); // just to init everything
        
        $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
        $ids = $ds->ExecuteSql("SELECT DISTINCT id FROM wdf_translations")->Enumerate('id');
        
        $multifind = function($id,$content,$replace,$prefix,$suffix=['"',"'"])
        {
            foreach( $suffix as $s )
            {
                if( $replace )
                {
                    if( strpos($content,str_replace($replace,$prefix.$s,$id).$s) !== false ) 
                        return true;
                }
                else
                {
                    if( strpos($content,$prefix.$s.$id.$s) !== false ) 
                        return true;
                }
            }
            return false;
        };
        $processfile = function($file)use(&$ids,$multifind)
        {
            $c = file_get_contents($file);
            $used = [];
            foreach( $ids as $i )
            {
                if( strpos($c,$i) !== false )
                {
                    $used[] = $i;
                    continue;
                }
                $pre = array_first(explode("_",$i));
                if( is_in($pre,'TITLE','TXT') )
                {
                    if( $multifind($i,$c,"{$pre}_","::Confirm(") )
                    {
                        $used[] = $i;
                        log_debug("Found DLG $i");
                        continue;
                    }
                }
            }
            $ids = array_diff($ids,$used);
        };
        
        log_debug("Preprocessing");
        
        $used = [];
        foreach( $ids as $i )
            if( strpos("$i","TXT_COUNTRY_") === 0 )
                $used[] = $i;
        $ids = array_diff($ids,$used);
        log_debug("...found ".count($used)." IDs",$used);
        
        log_debug("Processing translation recursion");
        $used = [];
        foreach( $GLOBALS['translation']['strings'] as $id=>$value )
        foreach( $ids as $i )
            if( strpos($value,$i) )
                $used[] = $i;
        $ids = array_diff($ids,$used);
        log_debug("...found ".count($used)." IDs",$used);
        
        $datapath = realpath($GLOBALS['CONFIG']['translation']['data_path']);
        log_debug("Processing files in folder $dir");
        foreach( ['*.php','*.js'] as $pattern )
        foreach( system_glob_rec($dir, $pattern) as $file )
        {
            if( 0 === stripos(realpath($file), $datapath) )
                continue;
            $processfile($file);
            if( count($ids)==0 )
                break 2;
        }
        
        log_debug("Unused",array_values($ids));
        
        if( avail($args,'remove') || in_array('remove',$args) )
        {
            foreach( $ids as $i )
            {
                $ds->ExecuteSql("DELETE FROM wdf_translations WHERE id=?",$i);
                log_debug("Removed $i");
            }
        }
    }
}
