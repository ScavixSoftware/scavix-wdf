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

use ScavixWDF\Logging\Logger;
use ScavixWDF\Logging\LogReport;
use ScavixWDF\Wdf;
use ScavixWDF\WdfException;

/**
 * Initializes the logging mechanism.
 * 
 * Will use the ini_get('error_log') setting to ensure working logger
 * functionality by default.
 * You may configure multiple loggers of different classes, default is 'Logger'.
 * Specify configuration in CONFIG variable as follows:
 * $CONFIG['system']['logging'][&lt;alias&gt;] = array(&lt;key&gt; => &lt;value&gt;);
 * &lt;alias&gt; is a meanful name for the logger (in fact it can be used to log to only 
 * one logger instead of logging to all).
 * Rest is an array of key-value pairs.
 * Following keys are supported:
 *   'path' := absolute path in filesystem where to log
 *   'filename_pattern' := pattern of filename. see logging_extend_logger for details
 *   'log_severity' := true|false defines if severity shall we written to logs
 *   'max_filesize' := maximum filesize of logs in bytes (will start rotation if hit)
 *   'keep_for_days' := when rotated (max_filesize is set) specifies how many days rotated logs will be kept
 *   'min_severity' := minimum severity. see Logger class for constants, but define as string like so: "WARNING"
 *   'max_trace_depth' := maximum depth of stacktraces
 *   'class' := Class to be used as logger (when other that 'Logger', see TraceLogger as example)
 * @return void
 */
function logging_init()
{
	global $CONFIG;
	
	// remove error module from module-auto-load config and fake that it has been loaded
        if( isset($CONFIG['system']['modules']) && is_array($CONFIG['system']['modules']) )
            $CONFIG['system']['modules'] = array_diff($CONFIG['system']['modules'],array('error'));
	
	require_once(__DIR__.'/logging/logentry.class.php');
	require_once(__DIR__.'/logging/logreport.class.php');
	require_once(__DIR__.'/logging/logger.class.php');
	require_once(__DIR__.'/logging/tracelogger.class.php');
    classpath_add(__DIR__.'/logging',true,'system');
	
	// default logger if nothing configured uses defined php error_log (see Logger constructor)
	// no further limits and/or features are enabled, so plain logging is active
	if( !isset($CONFIG['system']['logging']) )
		$CONFIG['system']['logging'] = array('default' => []);
	
	foreach( $CONFIG['system']['logging'] as $alias=>$conf )
		Wdf::$Logger[$alias] = Logger::Get($conf);
	
	ini_set("display_errors", 0);
	ini_set("log_errors", 1);
	error_reporting(E_ALL);
	
	set_error_handler('global_error_handler');
	set_exception_handler('global_exception_handler');
	register_shutdown_function('global_fatal_handler');
}

/**
 * Add a logger.
 * 
 * @param string $alias Name for the logger
 * @param array $conf Configuration as described in <loggin_init>
 * @return void
 */
function logging_add_logger($alias,$conf)
{
    Wdf::$Logger[$alias] = Logger::Get($conf);
}

/**
 * Remove a logger.
 * 
 * @param string $alias Name for the logger
 * @return void
 */
function logging_remove_logger($alias)
{
    if (isset(Wdf::$Logger[$alias]))
        unset(Wdf::$Logger[$alias]);
}

/**
 * Returns a logger.
 * 
 * @param string $alias Name of the logger to get
 * @return Logger
 */
function logging_get_logger($alias)
{
    return Wdf::$Logger[$alias];
}

/**
 * Registers a class to act as request logger.
 * 
 * @see <RequestLogEntry>
 * @param string $classname Classname of the handler, must be subclass of <RequestLogEntry>
 * @return void
 */
function register_request_logger($classname)
{
    register_hook_function(HOOK_PRE_CONSTRUCT,"{$classname}::Start");
}

/**
 * Checks if there's enough memory.
 * 
 * @return bool true if ok, else false
 */
function logging_mem_ok()
{
    $val = Wdf::GetBuffer(__FUNCTION__)->get('mem_total',function()
    {
        $val = trim(ini_get('memory_limit'));
        if( $val === '-1' )
            return -1;

        $last = strtolower($val[strlen($val)-1]);
        $val = preg_replace('/[^0-9]/', '', $val);
        switch($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024 * 1024 * 1024;
            case 'm':
                $val *= 1024 * 1024;
            case 'k':
                $val *= 1024;
        };
        return $val;
    });
    return ($val<0) || (($val-memory_get_usage()) > 1048576);
}

/**
 * @internal Global error handler. See <set_error_handler>
 */
function global_error_handler($errno, $errstr, $errfile, $errline)
{
    static $error_names = [
        'E_ERROR','E_WARNING','E_PARSE','E_NOTICE','E_CORE_ERROR','E_CORE_WARNING','E_COMPILE_ERROR',
        'E_COMPILE_WARNING','E_USER_ERROR','E_USER_WARNING','E_USER_NOTICE','E_STRICT',
        'E_RECOVERABLE_ERROR','E_DEPRECATED','E_USER_DEPRECATED','E_ALL'
    ];

	// Use error_reporting() to check if @ operator is in use.
	// This works as we set error_reporting(E_ALL) in logging_init().
    // Note: Since PHP 8.0 <set_error_handler>-defined functions will be called even if @ is used.
    //       See code below for handling this
	if ( error_reporting() == 0 )
        return;
	
	// As we skip E_STRICT check that too. 
    // E_STRICT has been changed to E_WARNING in php7 (see https://www.php.net/manual/de/migration70.incompatible.php)
    // so we ignore the "Declaration of ... should be compatible with" warnings in live systems
    if ((($errno & error_reporting()) == 0) || ($errno == E_STRICT) || (($errno == E_WARNING) && (strpos($errstr, 'Declaration of ') === 0) && (strpos($errstr, ' should be compatible with ') !== false)))
    {
        if( !isDev() ) // Completely ignore in LIVE env
            return;

        if( stripos($errstr,'ScavixWDF\Model\PdoLayer::prepare') > 0 ) // known an handled, ignore savely
            return;

        // load line that triggered the error and check it for @-operator
        if( $errfile && $errline )
        {
            $errline--;
            $file = new SplFileObject($errfile);
            if (version_compare(PHP_VERSION, '8.0.1', '>=') || $errline == 0)
                $file->seek($errline);
            else
            {
                if ($errline == 1)
                {
                    $file->rewind();
                    $file->fgets();
                }
                else
                    $file->seek($errline - 1);
            }
            $line = $file->fgets();
            if (strpos($line, '@'.substr($errstr, 0, 6)) !== false) // @ is used, ignore
                return;
        }
    }
        
    $sev = 'NOTICE';
	foreach( $error_names as $n )
    {
		if( constant($n) == $errno )
		{
			$sev = explode("_",$n); // to break *_* severity from global handler that uses PHP error codes like USER_NOTICE, CORE_ERROR,...
			$sev = $sev[count($sev)-1];
			break;
		}
    }
	foreach( Wdf::$Logger as $l )
	{
		$l->addCategory("GLOBAL");
		$l->write($sev,true,"[$errno] $errstr in $errfile:$errline");
		$l->removeCategory("GLOBAL");
	}
}

/**
 * @internal Global exception handler. See <set_exception_handler>
 */
function global_exception_handler($ex)
{
	try
	{
		// system_die will handle logging itself. perhaps restructure that to
		// keep things in place and let that function only handle the exception
		// foreach( Wdf::$Logger as $l )
		// 	$l->fatal($ex);
		// system_die($ex,'',false);
		system_die($ex);
	}
	catch(Exception $fatal)
	{
		foreach( Wdf::$Logger as $l )
		{
			$l->addCategory("NESTED_EXCEPTION");
			$l->fatal($fatal);
			$l->removeCategory("NESTED_EXCEPTION");
		}
	}
}

/**
 * @internal Global shutdown handler. See <register-shutdown-function>
 */
function global_fatal_handler()
{
    if( !system_is_ajax_call() && function_exists('session_update') )
        session_update();
    
	$error = error_get_last();
	if(($error === NULL) || ($error['type'] !== E_ERROR))
		return;
	$ex = new WdfException($error["message"]."\n".logging_render_var($error));
	try
	{
		// system_die will handle logging itself. perhaps restructure that to
		// keep things in place and let that function only handle the exception
		// foreach( Wdf::$Logger as $l )
		// 	$l->fatal($ex);
		system_die($ex, var_export($error, true));
	}
	catch(Exception $fatal)
	{
		foreach( Wdf::$Logger as $l )
		{
			$l->addCategory("NESTED_EXCEPTION");
			$l->fatal($fatal);
			$l->removeCategory("NESTED_EXCEPTION");
		}
	}
}

/**
 * Extends a logger with a named variable.
 * 
 * You may use this to recreate the logfile name. 
 * Variables used here will match placeholders in the logfile name (see filename_pattern config key).
 * Currently all classes derivered from Logger know about the SERVER variable, so
 * all keys in there will work without the need to call logging_extend_logger.
 * 
 * Samples:
 * 'error{REMOTE_ADDR}.log' will become 'error_192.168.1.123.log'
 * 'error{REMOTE_ADDR}{username}.log' will become 'error_192.168.1.123.log' until you call
 * logging_extend_logger(&lt;alias&gt;,'username','daniels') and the be 'error_192.168.1.123_daniels.log'.
 * 
 * Note that setting extensions is only supported on a per logger basis, so you'll need
 * a valid alias as set in initial configuration.
 * @param string $alias The loggers alias name
 * @param string $key Key to use
 * @param string $value Value to use
 * @return void
 */
function logging_extend_logger($alias,$key,$value)
{
	if( isset(Wdf::$Logger[$alias]) )
		Wdf::$Logger[$alias]->extend($key,$value);
}

/**
 * Adds a category to all loggers.
 * 
 * @param string $name Category to add
 * @return void
 */
function logging_add_category($name)
{
    foreach( Wdf::$Logger as $l )
		$l->addCategory($name);
}

/**
 * Removes a category from all loggers.
 * 
 * @param string $name Category to remove
 * @return void
 */
function logging_remove_category($name)
{
    foreach( Wdf::$Logger as $l )
		$l->removeCategory($name);
}

/**
 * Sets the minimum severity to log.
 * 
 * @param string $min_severity A valid severity string
 * @return bool
 */
function logging_set_level($min_severity = "INFO")
{
    if(is_string($min_severity))
        $min_severity = @constant("\\ScavixWDF\\Logging\\Logger::".$min_severity);
    if(!$min_severity)
        return false;
	foreach( Wdf::$Logger as $l )
		$l->min_severity = $min_severity;
    return true;
}

/**
 * Tries to set up a category for a logged in user.
 * 
 * Checks the object store for an object with id $object_storage_id 
 * that contains a field $fieldname. Then adds content of that field as category to all loggers.
 * 
 * Note: This will NOT extend the logger with information as logging_extend_logger does!
 * @param string $object_storage_id Storage ID of the object to check for
 * @param string $fieldname Name of field/property to use as category ('name' will use $obj->name as category)
 * @return void
 */
function logging_set_user($object_storage_id='user',$fieldname='username')
{
	if( in_object_storage('user') )
	{
		$lu = restore_object('user');
		if( $lu && isset($lu->username) && $lu->username )
			logging_add_category($lu->username);
	}
}

/**
 * @shortcut Logs to specified severity
 */
function log_write($severity,$a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( Wdf::$Logger as $l )
		$l->write(strtoupper($severity),false,$a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * @shortcut Logs to severity TRACE
 */
function log_trace($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( Wdf::$Logger as $l )
		$l->trace($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * @shortcut Logs to severity DEBUG
 */
function log_debug($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( Wdf::$Logger as $l )
		$l->debug($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * @shortcut Logs to severity INFO
 */
function log_info($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( Wdf::$Logger as $l )
		$l->info($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * @shortcut Logs to severity WARN
 */
function log_warn($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( Wdf::$Logger as $l )
		$l->warn($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * @shortcut Logs to severity ERROR
 */
function log_error($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( Wdf::$Logger as $l )
		$l->error($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * @shortcut Logs to severity FATAL
 */
function log_fatal($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( Wdf::$Logger as $l )
		$l->fatal($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * Logs the $label and $value arguments and then returns the $value argument.
 * 
 * Use case:
 * <code php>
 * function x($a){ return log_return("this is a",$a); }
 * </code>
 * @param string $label Label to log
 * @param mixed $value Value to log
 * @return mixed $value
 */
function log_return($label,$value)
{
	log_debug($label,$value);
	return $value;
}

/**
 * Calls log_debug if the condition is TRUE and then returns the condition.
 * 
 * Use case:
 * <code php>
 * log_if( !isset($some_var), "Missing data");
 * </code>
 * @param bool $condition true or false
 * @param_array mixed $a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10 Values to be logged
 * @return bool Returns the $condition itself (true|false)
 */
function log_if($condition,$a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	if( $condition )
		log_debug($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
	return $condition;
}

/**
 * Calls log_debug if the condition is FALSE and then returns the condition.
 * 
 * Use case:
 * <code php>
 * if( log_if_not( isset($some_var), "Missing data") )
 * {
 *    do_something_with($some_var);
 * }
 * </code>
 * @param bool $condition true or false
 * @param_array mixed $a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10 Values to be logged
 * @return bool
 */
function log_if_not($condition,$a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	if( !$condition )
		log_debug($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
	return $condition;
}

/**
 * Starts a report named $name
 * 
 * Returns an object of type <LogReport>, see doc there.
 * Use log_report to finally write the report to logs.
 * @param string $name Report name
 * @return LogReport The new report
 */
function log_start_report($name)
{
	$res = new LogReport($name);
	return $res;
}

/**
 * Writes a log-report to the logs.
 * 
 * Use <log_start_report> to generate a report.
 * @param LogReport $report The report to log
 * @param string $severity Severity to log to
 * @return void
 */
function log_report(LogReport $report, $severity="TRACE")
{
	foreach( Wdf::$Logger as $l )
		$l->report($report,$severity);
}

/**
 * Renders a variable into a string representation.
 * 
 * Feel free to use alias function <render_var> instead as it is shorter
 * @param mixed $content Content to be rendered
 * @param array $stack IGNORE (just to detect circular references)
 * @param string $indent IGNORE (just to have nice readable output)
 * @return string The content rendered as string
 */
function logging_render_var($content,&$stack=[],$indent="")
{
    if( !logging_mem_ok() )
        return "*OUTOFMEM*";
    
	foreach( $stack as $s )
	{
		if( $s === $content )
			return "*RECURSION".(is_object($content)?"[".get_class($content)."]*":"*");
	}
	$res = [];
	if( is_array($content) )
	{
		if( count($content) == 0 )
			return "*EmptyArray*";
		$res[] = "Array(".count($content).")\n$indent(";
//		$stack[] = $content; // trying to ignore recursion as i'm not sure if this may happen with arrays-only
		foreach( $content as $i=>$val )
			$res[] = $indent."\t[$i]: ".logging_render_var($val,$stack,$indent."\t");
		$res[] = $indent.")";
	}
	elseif( is_object($content) )
	{
		$stack[] = $content;
		if( $content instanceof WdfException )
		{
			$res[] = get_class($content).": ".$content->getMessageEx();
            if( isDev() )
				$res[] = "in " . $content->getFileEx() . ":" . $content->getLineEx();
            if( isset($GLOBALS['logging_render_var_for_logger']) )
			{
				if(avail($content, 'details'))
					$res[] = $content->details;
				$res[] = $content->getTraceAsString();
			}
		}
		elseif( ($content instanceof Exception) || ($content instanceof Error) )
		{
			$res[] = get_class($content).": ".$content->getMessage();
            if( isDev() )
                $res[] = "in ".$content->getFile().":".$content->getLine();
            if( isset($GLOBALS['logging_render_var_for_logger']) )
                $res[] = $content->getTraceAsString();
		}
        elseif($content instanceof ScavixWDF\Base\DateTimeEx)
        {
            $res[] = get_class($content).": ".(var_export((array) $content, true));
        }
		else
		{
			$is_renderable = $content instanceof ScavixWDF\Base\Renderable;
			$res[] = "Object(".get_class($content).")\n$indent{";
			foreach( get_object_vars($content) as $name=>$val )
			{
				if( $is_renderable )
				{
					if( $name == '_parent' )
					{
						$res[] = $indent."\t->$name: *PARENT*";
						continue;
					}
				}
				if( $val === $content )
					$res[] = $indent."\t->$name: *RECURSION*";
				else
					$res[] = $indent."\t->$name: ".logging_render_var($val,$stack,$indent."\t");
			}
			$res[] = $indent."}";
		}
	}
	elseif( is_bool($content) )
		return (count($stack)>0?"(bool)":"").($content?"true":"false");
	else
		return (count($stack)>0?"(".gettype($content).")":"").strval($content);
	return substr(implode("\n",$res),0,10240);
}

/**
 * @shortcut <logging_render_var>
 */
function render_var($content)
{
	return logging_render_var($content);
}

/**
 * Starts a named timer.
 * 
 * @param string $name Name of the timer
 * @return string Timer-Identifier
 */
function start_timer($name)
{
    $id = uniqid();
    Wdf::$Timer[$id] = [ [$name,microtime(true),0] ];
    return $id;
}

/**
 * Set a marker in a named timer.
 * 
 * @param string $id Timer-Identifier
 * @param string $label Label to be written
 * @return void
 */
function hit_timer($id,$label='(no label)')
{
    if( !isset(Wdf::$Timer[$id]) )
        return;
    list($name,$start,$dur) = array_last(Wdf::$Timer[$id]);
    Wdf::$Timer[$id][] = [$label,microtime(true),round((microtime(true)-$start)*1000)];
}

/**
 * Finishes a timer and writes it to log.
 * 
 * @param string $id Timer-Identifier
 * @param int $min_ms Minimum milliseconds that must be reached for the timer to be written to log
 * @return void
 */
function finish_timer($id,$min_ms = false)
{
    if( !isset(Wdf::$Timer[$id]) )
        return;
    $trace = Wdf::$Timer[$id];
    list($name,$start,$dur) = array_shift($trace);
    unset(Wdf::$Timer[$id]);
    
    $ms = round((microtime(true)-$start)*1000);
    if( !$min_ms || $ms >= $min_ms )
    {
        $trace = array_map(function($a){ return "{$a[2]}ms for {$a[0]}"; },$trace);
        array_unshift($trace, "started ".date("H:i:s",(int)$start));
        $trace[] = "{$ms}ms total";
        log_debug("Timer finish:\t$name\n\t".implode("\n\t",$trace));
    }
}
