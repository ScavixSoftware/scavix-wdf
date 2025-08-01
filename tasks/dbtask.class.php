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
use ScavixWDF\TerminationException;
use ScavixWDF\Wdf;

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
                log_info("\tSQL folder: ",constant("DATABASE_FOLDER"));
                log_info("\tTarget version: ",constant("DATABASE_VERSION"));
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

        model_update_db($ds, constant("DATABASE_VERSION"), constant("DATABASE_FOLDER"), function($v)
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
        if (count(Wdf::$Logger) > 1)
            logging_remove_logger('cli');

        register_shutdown_function(function ()
        {
            WdfTaskModel::SetState('done');
        });
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function ()
        {
            TerminationException::Verbose("SIGTERM received, terminating");
        });
        Wdf::SetTimeout(1, function ()
        {
            static $shm = false;
            if ($shm === false)
                $shm = "/run/shm/".(WdfTaskModel::$SHMSUBFOLDER ?: $GLOBALS['CONFIG']['system']['application_name'])."/".getmypid().'.cmd';

            if (file_exists($shm) && ($cmd = @file_get_contents($shm)) !== false)
            {
                @unlink($shm);
                $cmd = trim($cmd);
                switch ($cmd)
                {
                    case 'stop':
                    case 'exit':
                        log_debug("'$cmd' command received, forcing stop");
                        die();
                    case 'terminate':
                        TerminationException::Verbose("'$cmd' command received, terminating");
                        break;
                    default:
                        log_debug("Unknown command: '$cmd'");
                        break;
                }
            }
            return 1;
        });

        WdfTaskModel::SetState('idle');
        $ttl = intval(array_shift($args) ?: -1) ?: -1;
        if ($ttl < 0)
            $ttl = WdfTaskModel::$MIN_RUNTIME;
        $eol = time() + $ttl;
        WdfTaskModel::FreeOrphans();
        $task = WdfTaskModel::Reserve();
        set_time_limit(0);

        Wdf::SetTimeout(1, function ()
        {
            if ($f = $this->codeHasChanged())
            {
                log_debug(__CLASS__, "Code has changed, stopping ($f)");
                die();
            }
            return 10;
        });

        while ($task || time() < $eol)
        {
            if ($task)
            {
                WdfTaskModel::SetState('running');
                $cat = implode("-", array_slice(explode("-", $task->name), 0, 2));
                logging_set_categories([getmypid(), $task->id, $cat]);

                $exectime = microtime(true);
                $startUsage = getrusage();

                $task->Run();

                $endUsage = getrusage();
                $cputime = 0;
                foreach (['ru_utime', 'ru_stime'] as $k)
                    $cputime += ($endUsage["$k.tv_sec"] - $startUsage["$k.tv_sec"]) + ($endUsage["$k.tv_usec"] - $startUsage["$k.tv_usec"]) / 1e6;
                $exectime = microtime(true) - $exectime;
                $cpuUsage = ($cputime / $exectime) * 100;

                if (isDev() && $exectime>100 && $cpuUsage > 90)
                    log_debug("High CPU-Usage: " . round($cpuUsage, 2) . "% for ".$exectime, ifavail($task, 'arguments'));

                WdfTaskModel::SetState('idle');
                usleep(10000);
            }
            else
                usleep(100000);
            if ($task = WdfTaskModel::Reserve(TASK_PRIORITY_HIGHEST))
                continue;

            $running = WdfTaskModel::CountRunningInstances();
            if ($running >= WdfTaskModel::$MAX_PROCESSES)
                break;

            $task = WdfTaskModel::Reserve();
        }
    }

    function ListWdfTasks($args)
    {
        foreach (Wdf::$Logger as $name => $l)
            if ($name != 'cli')
                logging_remove_logger($name);

        $tasks = [];
        foreach( WdfTaskModel::Make()->notNull('worker_pid') as $dbt )
            $tasks[$dbt->worker_pid] = $dbt;

        $pids = WdfTaskModel::GetRunningInstances();
        $running = $idle = 0;
        sort($pids);
        $tab = [];
        if (count($pids) > 0)
            $tab[] = ['NO', 'PID', 'RUNTIME', 'PRIO', 'NAME', 'ARGUMENTS'];
        foreach ($pids as $i => $pid)
        {
            // $zombie = @file_exists("/proc/$pid/cmdline") ? false : '<zombie>';
            $zombie = system_process_running($pid) ? false : '<zombie>';
            $info = [
                $i+1,
                $pid
            ];
            if( isset($tasks[$pid]) )
            {
                $info[] = $zombie ?: (avail($tasks[$pid], 'assigned') ? $tasks[$pid]->assigned->age_secs() . "s" : '');
                $info[] = $tasks[$pid]->priority;
                $info[] = implode("-", array_slice(explode("-", $tasks[$pid]->name), 0, 2));
                $info[] = json_encode($tasks[$pid]->GetArgs());
                $running++;
            }
            else
            {
                $info[] = $zombie ?: '<idle>';
                $idle++;
            }
            $tab[] = $info;
        }
        $sizes = array_fill(0,6,0);
        foreach( $tab as $row )
        {
            foreach (array_map(function ($v){ return strlen($v); }, $row) as $i => $len)
                $sizes[$i] = max($sizes[$i], $len);
        }
        $width = trim(shell_exec("tput cols"));
        foreach( $tab as $row )
        {
            foreach ($row as $i => $cell)
                $row[$i] = str_pad($cell, $sizes[$i], ' ');
            $line = substr(implode(' ', $row), 0, $width - 1);
            echo "{$line}\n";
        }
        if (avail($args, 'expand') || array_shift($args)=='expand' )
            for ($i = count($pids); $i <= WdfTaskModel::$MAX_PROCESSES; $i++)
                echo str_pad($i, $sizes[0], ' ')."\n";

        echo "\nRunning: $running, Idle: $idle, Max: ".WdfTaskModel::$MAX_PROCESSES.", Free: ".(WdfTaskModel::$MAX_PROCESSES-count($pids));
    }

    function StopWdfTasks($args)
    {
        foreach (Wdf::$Logger as $name => $l)
            if ($name != 'cli')
                logging_remove_logger($name);

        $sig = is_in(strtolower(array_first($args) . ''), 'k', 'kill', '9') ? SIGKILL : SIGTERM;
        if( $sig == SIGKILL )
        {
            log_warn("Deprecated syntax used. Arguments is a list of PIDs now.");
            return;
        }
        if (count($args) == 1 && is_in($args[0],'a','all'))
            $pids = WdfTaskModel::GetRunningInstances();
        else
            $pids = array_filter($args, 'is_numeric');

        if( !count($pids) )
        {
            log_info("Syntax: db-stopwdftasks pid [pid...]",$args);
            return;
        }

        foreach ($pids as $pid)
            WdfTaskModel::Stop($pid);
    }
}
