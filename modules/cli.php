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
use ScavixWDF\Session\CliObjectStore;
use ScavixWDF\Session\CliSession;

/**
 * Initializes the CLI module.
 *
 * This is a semi-automatic module that provides you with the ability to process
 * application logik defined in <Task> subclasses from the commandline.
 * You may even delay them to be processed in the background later.
 *
 * <code>
 * // sample:
 * php index.php clear-logs
 * </code>
 *
 * See <Task>, <WdfTaskModel>, <ClearTask>, <DbTask>
 *
 * @return void
 */
function cli_init()
{
    if(!defined("CLI_SELF"))
    {
        $self = realpath(explode("index.php",$_SERVER['SCRIPT_FILENAME'])[0]."index.php");
        if( !$self )
            $self = realpath(explode("index.php",$_SERVER['PHP_SELF'])[0]."index.php");
        if( !$self && $GLOBALS['argv'] && is_array($GLOBALS['argv']) && count($GLOBALS['argv'])>0 )
            $self = $GLOBALS['argv'][0];
        define("CLI_SELF",realpath($self));
    }

    if( defined('STDOUT') )
    {
        classpath_add(__DIR__ . '/cli');
        if (!function_exists('posix_isatty') || posix_isatty(STDOUT))
        {
            logging_add_logger('cli', [
                'class' => \ScavixWDF\CLI\CliLogger::class,
                'log_date' => false,
                'log_categories' => false,
            ]);
            register_hook_function(HOOK_SYSTEM_DIE, function ($args)
            {
                die("\n");
            });
        }

    }

    create_class_alias(\ScavixWDF\Tasks\CheckTask::class,'checktask');
    create_class_alias(\ScavixWDF\Tasks\ClearTask::class,'cleartask');
    create_class_alias(\ScavixWDF\Tasks\CreateTask::class,'createtask');
    create_class_alias(\ScavixWDF\Tasks\DbTask::class,'dbtask');
    create_class_alias(\ScavixWDF\Tasks\TaskPool::class,'taskpool');

    global $CONFIG;
    $CONFIG['session']['handler'] = CliSession::class;
    $CONFIG['session']['object_store'] = CliObjectStore::class;
}

/**
 * Runs a PHP script with given arguments in CLI.
 *
 * Note that WDF generates a temporary php.ini file with the current loaded
 * INI settings and uses it for the php commandline (php -c). The script
 * will be run in background using `nohup` and stdout/stderr is redirected to current
 * error_log. There will be some special arguments generated and passed to the script:
 * -D&lt;path_to_datafile&gt; Path to a file containing the serialized $_SERVER vairable
 * -C&lt;path_to_configfile&gt; Path to the currently loaded WDF config.php file
 * -A&lt;appname&gt; The name of the current WDF application (see <system_init>).
 *
 * @param string $php_script_path The script path
 * @param array $args All arguments
 * @param array $extended_data Optional additional data to pass to the process
 * @param bool $return_cmdline If true will not execute, but just return the commandline
 * @return void|string
 */
function cli_run_script($php_script_path, $args=[], $extended_data=false, $return_cmdline=false)
{
    if( !function_exists('posix_isatty') )
        ScavixWDF\WdfException::Raise("CLI module cannot run on windows");

    $ini = system_app_temp_dir()."php_cli.ini";
    $out = ini_get('error_log');

    if( ($loadedini = php_ini_loaded_file()) && ($loadedini != $ini) && (!file_exists($ini) || (filemtime($ini)<time()-3600))) // TTL for INI files is 1 hour
    {
        $inidata = ini_get_all(null, false);
        foreach (['disable_functions', 'mbstring.http_input', 'mbstring.http_output', 'mbstring.internal_encoding'] as $s)
        {
            if (array_key_exists($s, $inidata))
                unset($inidata[$s]);
        }
        foreach(array_unique(array_filter([ifavail($_SERVER, 'DOCUMENT_ROOT'), dirname($php_script_path), dirname($php_script_path).'/..', dirname($php_script_path).'/../..', dirname($php_script_path).'/../../..'])) as $dir)
        {
            if(@file_exists($ini_extrafile = $dir.'/.cli_php_extra.ini') && ($extra = parse_ini_file($ini_extrafile)) && ($extra !== false))
            {
                foreach ($extra as $k => $v)
                    $inidata[$k] = $v;
                break;
            }
        }
        $inidata = array_map(function ($k, $v)
        {
            if(($v != '') && !is_numeric($v))
                $v = '"'.$v.'"';
            return "{$k}={$v}";
        }, array_keys($inidata), $inidata);

        file_put_contents($ini, implode("\n", $inidata));
        @chmod($ini, 0777);
    }

    $cmd = "$php_script_path";
    if( $extended_data )
    {
        $data = tempnam(system_app_temp_dir(),"cli_script_data_");
        file_put_contents($data,json_encode($extended_data,JSON_PRETTY_PRINT));
        @chmod($data,0777);
        $cmd .= " --wdf-extended-data{$data}";
    }

    if( count($args)>0 )
    {
        $merged = [];
        foreach( $args as $k=>$v )
        {
            if( is_numeric($k) )
                $merged[] = $v;
            else
                $merged[] = "{$k}={$v}";
        }

        $cmd .= " ".implode(" ", array_unique($merged));
    }

    if( file_exists($out) && !is_writable($out) )
        $out = system_app_temp_dir()."cli-bash.log";

    $grep = shell_exec("which grep");
    if( $grep ) $grep = "| ".trim($grep)." . ";

    $cmdline = "nohup php -c $ini $cmd $grep>>$out 2>&1 &";

    if (cli_running_as_sudo())
        $cmdline = str_replace("nohup php -c", "nohup sudo php -c", $cmdline);

    if ($return_cmdline)
        log_debug($cmdline);

    exec($cmdline);
}

/**
 * @internal Lists all processes.
 * @return array Array of processes, key is process id, value is array of ppid, pid and cmd
 */
function cli_list_processes()
{
    $res = [];
    $out = shell_exec("ps -A -o ppid,pid,cmd");
    if( preg_match_all('/(\d+)\s+(\d+)\s+(.*)/m',$out,$m) )
    {
        foreach ($m[2] as $i => $pid)
            $res[$pid] = ['pid' => $pid, 'ppid' => $m[1][$i], 'cmd' => $m[3][$i]];
    }
    return $res;
}

/**
 * @internal Detects if script is running as root.
 *
 * @param mixed $pid Optional pid of process to check
 * @return bool True or false
 */
function cli_running_as_sudo($pid=false)
{
    static $depth = 0;
    static $procs = [];

    if( $pid === false )
        $pid = getmypid();

    if ( $pid == getmypid() && posix_getuid() !== 0)
        return false;

    if( $depth == 0)
        $procs = cli_list_processes();

    if (!isset($procs[$pid]))
        return false;

    if (isset($procs[$pid]) && starts_iwith($procs[$pid]['cmd'], 'sudo '))
        return true;

    $depth++;
    $res = cli_running_as_sudo($procs[$pid]['ppid']);
    $depth--;
    return $res;
}

/**
 * @internal Runs a PHP process for background task processing
 */
function cli_run_taskprocessor($runtime_seconds=null)
{
    if( !function_exists('posix_isatty') )
        ScavixWDF\WdfException::Raise("CLI module cannot run on windows");

    if( !defined("CLI_SELF") || !CLI_SELF )
        ScavixWDF\WdfException::Raise("Cannot run task processor");

    if( !$runtime_seconds )
        $runtime_seconds = intval(cfg_getd('system','cli','taskprocessor_runtime',0));

    if( PHP_SAPI == 'cli' )
    {
        $ex = [];
        if( can_rewrite() ) $ex['WDF_FEATURES_REWRITE'] = 'on';
        if( can_nocache() ) $ex['WDF_FEATURES_NOCACHE'] = 'on';
        cli_run_script(CLI_SELF,['db-processwdftasks',$runtime_seconds],count($ex)?$ex:false);
    }
    else
        cli_run_script(CLI_SELF,['db-processwdftasks',$runtime_seconds],$_SERVER);
}

/**
 * @internal Used to check if processes are already active
 */
function cli_get_processes($filter=false, $test_myself=false, $use_extended_filter=true, $skip_parents=true)
{
    if( !function_exists('posix_isatty') )
        ScavixWDF\WdfException::Raise("CLI module cannot run on windows");

    if ($use_extended_filter)
    {
        $ini = preg_quote(system_app_temp_dir('', false), "/");
        $filter = "\-c\s+{$ini}" . ($filter ? (".*" . preg_quote($filter, "/") . ".*") : '');
    }
    else
        $filter = ($filter ? (".*" . preg_quote($filter, "/") . ".*") : '');

    $res = [];
    $out = shell_exec("ps -Af");
    // log_debug("$filter",$out);
    if( preg_match_all('/\n[^\s+]*\s+(\d+)\s+(\d+)\s+.*'.$filter.'/i',$out,$m) )
    {
        foreach( $m[1] as $p )
        {
            if( !$skip_parents || !in_array($p,$m[2]) )
                $res[] = $p;
        }
    }
    if( $test_myself )
        return in_array(getmypid(), $res);
    return $res;
}

/**
 * @internal Processes CLI arguments including doing work on detected Tasks
 *
 * @return void
 */
function cli_execute()
{
    $argv = isset($GLOBALS['argv'])?$GLOBALS['argv']:(isset($_SERVER['argv'])?$_SERVER['argv']:false);
    if( !$argv )
        \ScavixWDF\WdfException::Raise("Missing CLI arguments");

    array_shift($argv);
    logging_add_category('CLI');
    // logging_add_category(getmypid());
    // logging_add_category(get_current_user());

    $task = array_shift($argv);
    if( !$task )
        \ScavixWDF\WdfException::Raise("Missing arguments");

    $task = str_replace(['::',':','/','.','--'],['-','-','-','-','-'],$task);

    list($simpleclass,$method) = explode("-","{$task}-run");
    if( !$method ) $method = 'run';
    $class = fq_class_name($simpleclass);
    if( !class_exists($class) )
    {
        $class = fq_class_name(ends_iwith($simpleclass, "task") ? $simpleclass : "{$simpleclass}task");
        // hardcoded wdf task shortcuts
        switch( strtolower($class) )
        {
            case 'checktask':    $class = \ScavixWDF\Tasks\CheckTask::class; break;
            case 'cleartask':    $class = \ScavixWDF\Tasks\ClearTask::class; break;
            case 'createtask':   $class = \ScavixWDF\Tasks\CreateTask::class; break;
            case 'dbtask':       $class = \ScavixWDF\Tasks\DbTask::class; break;
            case 'pdfprinttask': $class = \ScavixWDF\Tasks\PdfPrintTask::class; break;
            case 'task':         $class = \ScavixWDF\Tasks\Task::class; break;
            case 'wdfcrontask':  $class = \ScavixWDF\Tasks\WdfCronTask::class; break;
            case 'wdftaskmodel': $class = \ScavixWDF\Tasks\WdfTaskModel::class; break;
            case 'folderarchivetask': $class = \ScavixWDF\Uploads\FolderArchiveTask::class; break;
        }
    }

    //log_debug("Task '$task' resolved to '$class::$method'");
    if( class_exists($class) )
    {
		$ref = new ReflectionClass($class);
        if( !$ref->isSubclassOf(\ScavixWDF\Tasks\Task::class) )
            \ScavixWDF\WdfException::Raise("Invalid task processor '$class' found in file '".$ref->getFilename()."'");

        $task = new $class();

        $ref = new ReflectionMethod($task, $method);
        if( !$ref )
            \ScavixWDF\WdfException::Raise("Unreflectable class '$class'");
        if( !$ref->isFinal() )
        {
            $ref = $ref->getDeclaringClass();
            if( strcasecmp($method,'run')!=0 && strcasecmp($ref->getName(),$class)!=0 )
                \ScavixWDF\WdfException::Raise("Invalid task method '$method' ".$ref->getName()."?=$class");
        }
        foreach( $argv as $i=>$arg )
        {
            if( is_numeric($i) && strpos($arg,"=") )
            {
                list($k,$v) = explode("=",$arg,2);
                if( !isset($argv[$k]) )
                    $argv[$k] = $v;
                else
                {
                    if( !is_array($argv[$k]) )
                        $argv[$k] = [$argv[$k]];
                    $argv[$k][] = $v;
                }
            }
        }

        $exectime = microtime(true);
        $argv = $task->PreprocessArguments($argv);
        $task->$method($argv);
        $exectime = round((microtime(true) - $exectime) * 1000);
        $task->Finished($method,$exectime,$exectime);

        die("\n");
    }
    else
        \ScavixWDF\WdfException::Raise("Unknown task '$task'");
}
