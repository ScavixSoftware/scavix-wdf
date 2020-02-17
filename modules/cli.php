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
 * php index.php clear logs
 * </code>
 * 
 * See <Task>, <WdfTaskModel>, <ClearTask>, <DbTask>
 * 
 * @return void
 */
function cli_init()
{
    if( !function_exists('posix_isatty') )
        ScavixWDF\WdfException::Raise("CLI module cannot run on windows");
    
    if( defined('STDOUT') && posix_isatty(STDOUT) )
    {
        classpath_add(__DIR__.'/cli');
        logging_add_logger('cli',['class' => \ScavixWDF\CLI\CliLogger::class]);
        register_hook_function(HOOK_SYSTEM_DIE, function($args){ die("\n"); });
    }
    
    ScavixWDF\Tasks\WdfTaskModel::$PROCESS_FILTER = __FILE__;
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
function cli_run_script($php_script_path, $args=[])
{
    $ini = system_app_temp_dir()."php_cli.ini";
    $out = ini_get('error_log');

    if( !file_exists($ini) || filemtime($ini)<time()-3600 ) // TTL for INI files is 1 hour
    {
        $inidata = file_get_contents(php_ini_loaded_file());
        $inidata = preg_replace('/^disable_functions/m', ';disable_functions', $inidata);
        file_put_contents($ini, $inidata);
    }
    $sdata = $_SERVER;
    $sdata['cli_default_datasource'] = model_datasource_name(\ScavixWDF\Model\DataSource::Get());
    $data = tempnam(system_app_temp_dir(),"cli_script_data_");
    file_put_contents($data,serialize($sdata));
    chmod($data,0777);
    
    $cmd = "$php_script_path -D{$data}";
    if( isset($GLOBALS['wdf_loaded_config_file']) && realpath($GLOBALS['wdf_loaded_config_file']) )
        $cmd .= " -C".realpath($GLOBALS['wdf_loaded_config_file']);
    if( isset($GLOBALS['CONFIG']['system']['application_name']) )
        $cmd .= " -A{$GLOBALS['CONFIG']['system']['application_name']}";
    if( count($args)>0 )
        $cmd .= " ".implode(" ",$args);
        
    log_debug("Starting $cmd");
    log_debug("INI is $ini");
    log_debug("Log is $out");
    log_debug("CFG is ".$GLOBALS['wdf_loaded_config_file']);
    
    exec("nohup php -c $ini $cmd >>$out 2>&1 &");
}

/**
 * @internal Runs a PHP process for background task processing
 */
function cli_run_taskprocessor()
{
    $self = realpath(explode("index.php",$_SERVER['SCRIPT_FILENAME'])[0]."index.php");
    if( !$self )
        $self = realpath(explode("index.php",$_SERVER['PHP_SELF'])[0]."index.php");
    if( !$self && $GLOBALS['argv'] && is_array($GLOBALS['argv']) && count($GLOBALS['argv'])>0 )
        $self = $GLOBALS['argv'][0];
    if( !$self )
        ScavixWDF\WdfException::Raise("Cannot run task processor");
    cli_run_script($self,['dbtask:processwdftasks']);
}

/**
 * @internal Processes CLI arguments including doing work on detected Tasks
 * 
 * @return void
 */
function cli_execute()
{
    global $argv;
    array_shift($argv);
    logging_add_category('CLI');

    // check for special args given from start by cli_run_script
    foreach( $argv as $i=>$a )
    {
        if( starts_iwith($a,'-D') )
        {
            $datafile = substr($a,2);
            $data = @unserialize(@file_get_contents($datafile));
            if( is_array($data) )
                $_SERVER = array_merge($data,$_SERVER);
            @unlink($datafile);
            log_debug("Loaded datafile $datafile");
            unset($argv[$i]);
        }
        elseif( starts_iwith($a,'-C') ) 
        {
            system_config(substr($a,2),false);
            log_debug("Loaded config ".substr($a,2));
            unset($argv[$i]);
        }
    }
    
    $task = array_shift($argv);
    if( !$task )
        \ScavixWDF\WdfException::Raise("Missing arguments");
    
    $task = str_replace(['::',':','/','.','--'],['-','-','-','-','-'],$task);
    
    list($simpleclass,$method) = explode("-","{$task}-run");
    if( !$method ) $method = 'run';
    $class = fq_class_name($simpleclass);
    if( !class_exists($class) )
        $class = fq_class_name("{$simpleclass}task");
    
    //log_debug("Task '$task' resolved to '$class::$method'");
    if( class_exists($class) )
    {
        $task = new $class();
        if( !($task instanceof \ScavixWDF\Tasks\Task) )
            \ScavixWDF\WdfException::Raise("Invalid a valid task processor");
        
        $ref = new ReflectionMethod($task, $method);
        if( !$ref )
            \ScavixWDF\WdfException::Raise("Unreflectable class '$class'");
        $ref = $ref->getDeclaringClass();
        if( strcasecmp($ref->getName(),$class)!=0 )
            \ScavixWDF\WdfException::Raise("Invalid task method '$method'");
        
        $task->$method($argv);
        die("\n");
    }
    else
        \ScavixWDF\WdfException::Raise("Unknown task '$task'");
}
