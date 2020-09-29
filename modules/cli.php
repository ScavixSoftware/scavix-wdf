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
//        log_debug("setting ".realpath($self));
    }
//    else
//        log_debug('is set: '.CLI_SELF);
    
    if( defined('STDOUT') && (!function_exists('posix_isatty') || posix_isatty(STDOUT)) )
    {
        classpath_add(__DIR__.'/cli');
        logging_add_logger('cli',['class' => \ScavixWDF\CLI\CliLogger::class]);
        register_hook_function(HOOK_SYSTEM_DIE, function($args){ die("\n"); });
    }
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
 * @param type $php_script_path The script path
 * @param type $args All arguments
 * @return void
 */
function cli_run_script($php_script_path, $args=[], $extended_data=false, $return_cmdline=false)
{
    if( !function_exists('posix_isatty') )
        ScavixWDF\WdfException::Raise("CLI module cannot run on windows");
    
    $ini = system_app_temp_dir()."php_cli.ini";
    $out = ini_get('error_log');

    if( php_ini_loaded_file() != $ini && !file_exists($ini) || filemtime($ini)<time()-3600 ) // TTL for INI files is 1 hour
    {
        $inidata = file_get_contents(php_ini_loaded_file());
        $inidata = preg_replace('/^disable_functions/m', ';disable_functions', $inidata);
        file_put_contents($ini, $inidata);
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
        $cmd .= " ".implode(" ",$args);
    
    if( file_exists($out) && !is_writable($out) )
        $out = system_app_temp_dir()."cli-bash.log";
    
    $grep = shell_exec("which grep");
    if( $grep ) $grep = "| ".trim($grep)." . ";
        
    $cmdline = "nohup php -c $ini $cmd $grep>>$out 2>&1 &";
    
    if( $return_cmdline )
        return $cmdline;
    exec($cmdline);
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
        $runtime_seconds = intval(cfg_getd('system','cli','taskprocessor_runtime',30));
    
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

function cli_get_processes($filter=false, $test_myself=false)
{
    if( !function_exists('posix_isatty') )
        ScavixWDF\WdfException::Raise("CLI module cannot run on windows");
    
    $ini = preg_quote(system_app_temp_dir('',false),"/");
    $filter = "\-c\s+{$ini}".($filter?(".*".preg_quote($filter,"/").".*"):'');
    
    $res = array();
    $out = shell_exec("ps -Af");
    //log_debug("$filter",$out);
    if( preg_match_all('/\n[^\s+]*\s+(\d+)\s+(\d+)\s+.*'.$filter.'/i',$out,$m) )
    {
        foreach( $m[1] as $p )
        {
            if( !in_array($p,$m[2]) )
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
    logging_add_category(getmypid());
    logging_add_category(get_current_user());
    
    $task = array_shift($argv);
    if( !$task )
        \ScavixWDF\WdfException::Raise("Missing arguments");
    
    $task = str_replace(['::',':','/','.','--'],['-','-','-','-','-'],$task);
    
    list($simpleclass,$method) = explode("-","{$task}-run");
    if( !$method ) $method = 'run';
    $class = fq_class_name($simpleclass);
    if( !class_exists($class) )
    {
        $class = fq_class_name("{$simpleclass}task");
        // hardcoded wdf task shortcuts
        switch( strtolower($class) )
        {
            case 'task':         $class = \ScavixWDF\Tasks\Task::class; break;
            case 'dbtask':       $class = \ScavixWDF\Tasks\DbTask::class; break;
            case 'cleartask':    $class = \ScavixWDF\Tasks\ClearTask::class; break;
            case 'checktask':    $class = \ScavixWDF\Tasks\CheckTask::class; break;
            case 'wdftaskmodel': $class = \ScavixWDF\Tasks\WdfTaskModel::class; break;
        }
    }
    
    //log_debug("Task '$task' resolved to '$class::$method'");
    if( class_exists($class) )
    {
        $task = new $class();
        if( !($task instanceof \ScavixWDF\Tasks\Task) )
            \ScavixWDF\WdfException::Raise("Invalid task processor");
        
        $ref = new ReflectionMethod($task, $method);
        if( !$ref )
            \ScavixWDF\WdfException::Raise("Unreflectable class '$class'");
        $ref = $ref->getDeclaringClass();
        if( strcasecmp($method,'run')!=0 && strcasecmp($ref->getName(),$class)!=0 )
            \ScavixWDF\WdfException::Raise("Invalid task method '$method' ".$ref->getName()."?=$class");
        
        foreach( $argv as $i=>$arg )
        {
            if( is_numeric($i) && strpos($arg,"=") )
            {
                list($k,$v) = explode("=",$arg,2);
                if( !isset($argv[$k]) )
                    $argv[$k] = $v;
            }
        }
        
        $exectime = microtime(true);
        $task->$method($argv);
        $exectime = round((microtime(true) - $exectime) * 1000);
        $task->Finished($method,$exectime,$exectime);
        
        die("\n");
    }
    else
        \ScavixWDF\WdfException::Raise("Unknown task '$task'");
}
