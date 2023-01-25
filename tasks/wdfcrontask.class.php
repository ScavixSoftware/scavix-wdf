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
 * Entrypoint for a central cron handler.
 * 
 * To use this implement you own 'cron' class extenting from <WdfCronTask> and
 * implement the abstract 'Process' method. It receives the interval in minutes
 * that triggered the current run.
 * Then `crontab -e` and add a minutley call to the task like this:
 * <code>
 * * * * * * cd /your/docroot && php index.php yourcronclassname
 * </code>
 * Note that in (difference to cron) this will not run each fill X minutes, but each X minutes.
 * Next run will be scheduled after current run is over, so running a long task hourly will result
 * in more and more shifting away from the complete hour.
 */
abstract class WdfCronTask extends Task
{
    private function mustRun($interval)
    {
        $fn = system_app_temp_dir('cron', false)."data.$interval";
        $next = intval(@file_get_contents($fn)?:'0');
        //log_debug("Next run $interval",date("Y-m-d H:i:s",$next),intval($next) < time());
        return $next <= time() || $next-time()<5;
    }
    
    private function done($interval)
    {
        $fn = system_app_temp_dir('cron', false)."data.$interval";
        file_put_contents($fn,time()+($interval*60));
        @chmod($fn, 0777);
    }
    
    /**
     * @internal WdfCronTask main loop, creates sub-tasks
     */
    function Run($args)
    {
        $tasks = [];
        foreach( [1,5,10,15,30,45,60] as $interval )
        {
            if( $this->mustRun($interval) )
                $tasks[] = static::Async('RunInternal')->SetArg('interval',$interval)->Go(false);
        }
        if( count($tasks) )
            WdfTaskModel::RunInstance();
    }
    
    /**
     * @internal WdfCronTask sub-tasks main loop
     */
    function RunInternal($args)
    {
        //log_debug(__METHOD__,$args);
        $interval = ifavail($args,'interval')?:60;
        if( !$this->mustRun($interval) )
            return;
        try
        {
            $this->Process($interval);
        }
        finally{ $this->done($interval); }
    }
    
    /**
     * Subclass must implement: Main-Loop
     * 
     * @param int $interval Interval this loop is called for (1,5,10,15,30,45,60 minutes)
     * @return void
     */
    abstract function Process($interval);
}
