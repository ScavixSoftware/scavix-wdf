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
}
