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
 * @internal CLI only Task run `php index.php clear` for info
 */
class ClearTask extends Task
{
    function Run($args)
    {
        log_warn("Syntax: clear-(all|logs|cache|requests)");
    }
    
    function All()
    {
        $this->Logs();
        $this->Cache();
    }
    
    function Logs()
    {
        foreach( \ScavixWDF\Wdf::$Logger as $l )
            $l->RotateNow();
        log_info("Logs rotated");
    }
    
    function Cache()
    {
        cache_clear();
        log_info("Cache cleared");
    }

    function CleanupGlobal()
    {
        if (system_is_module_loaded("globalcache"))
        {
            globalcache_clear(true);
            log_info("Globalcache cleaned up");
        }
        else log_info("Globalcache not loaded");
    }
    
    function Requests()
    {
        \ScavixWDF\Logging\RequestLogEntry::Cleanup("4 week");
        log_info("Old requests removed");
    }
}
