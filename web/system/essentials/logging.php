<?
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
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
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
$GLOBALS['LOGGING_ERROR_NAMES'] = array(
	'ERROR','WARNING','PARSE','NOTICE','CORE_ERROR','CORE_WARNING','COMPILE_ERROR',
	'COMPILE_WARNING','USER_ERROR','USER_WARNING','USER_NOTICE','STRICT',
	'RECOVERABLE_ERROR','DEPRECATED','USER_DEPRECATED','ALL');

/**
 * Initializes the logging mechanism
 * 
 * Will use the ini_get('error_log') setting to ensure working logger
 * functionality by default.
 * You may configure multiple loggers of different classes, default is 'Logger'.
 * Specify configuration in CONFIG variable as follows:
 * $CONFIG['system']['logging'][<alias>] = array(<key> => <value>);
 * <alias> is a meanful name for the logger (in fact it can be used to log to only 
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
 */
function logging_init()
{
	global $CONFIG;
	
	// remove error module from module-auto-load config and fake that it has been loaded
	$CONFIG['system']['modules'] = array_diff($CONFIG['system']['modules'],array('error'));
	$GLOBALS["loaded_modules"]['error'] = __FILE__;
	
	//$CONFIG['class_path']['system'][]  = dirname(__FILE__).'/logging/';
	require_once(dirname(__FILE__).'/logging/logentry.class.php');
	require_once(dirname(__FILE__).'/logging/logreport.class.php');
	require_once(dirname(__FILE__).'/logging/logger.class.php');
	require_once(dirname(__FILE__).'/logging/tracelogger.class.php');
	
	// default logger if nothing configured uses defined php error_log (see Logger constructor)
	// no further limits and/or features are enabled, so plain logging is active
	if( !isset($CONFIG['system']['logging']) )
		$CONFIG['system']['logging'] = array('default' => array());
	
	$GLOBALS['logging_logger'] = array();
	foreach( $CONFIG['system']['logging'] as $alias=>$conf )
		$GLOBALS['logging_logger'][$alias] = Logger::Get($conf);
	
	ini_set("display_errors", 0);
	ini_set("log_errors", 1);
	error_reporting(E_ALL);
	
	set_error_handler('global_error_handler');
	set_exception_handler('global_exception_handler');
}

/**
 * global error handler
 * 
 * see http://php.net/set_error_handler
 */
function global_error_handler($errno, $errstr, $errfile, $errline)
{
	global $LOGGING_ERROR_NAMES;
	
	// Use error_reporting() to check if @ operator is in use.
	// This works as we set error_reporting(E_ALL|E_STRICT) in logging_init().
	if ( error_reporting() == 0 )
        return;
	
	// As we skip E_STRICT check that too
	if ( ($errno & error_reporting()) == 0 )
        return;
	
	foreach( $LOGGING_ERROR_NAMES as $n )
		if( constant("E_$n") == $errno )
		{
			$sev = $n;
			$sev = explode("_",$sev); // to break *_* severity from global handler that uses PHP error codes like USER_NOTICE, CORE_ERROR,...
			$sev = $sev[count($sev)-1];
			break;
		}
	
	foreach( $GLOBALS['logging_logger'] as $l )
	{
		$l->addCategory("GLOBAL");
		$l->write($sev,true,"[$errno] $errstr in $errfile:$errline");
		$l->removeCategory("GLOBAL");
	}
}

/**
 * global exception handler
 * 
 * see http://php.net/set_exception_handler
 */
function global_exception_handler($ex)
{
	try
	{
		// system_die will handle logging itself. perhaps restructure that to
		// keep things in place and let that function only handle the exception
		system_die($ex);
	}
	catch(Exception $fatal)
	{
		foreach( $GLOBALS['logging_logger'] as $l )
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
 * logging_extend_logger(<alias>,'username','daniels') and the be 'error_192.168.1.123_daniels.log'.
 * 
 * Note that setting extensions is only supported on a per logger basis, so you'll need
 * a valid alias as set in initial configuration.
 */
function logging_extend_logger($alias,$key,$value)
{
	if( isset($GLOBALS['logging_logger'][$alias]) )
		$GLOBALS['logging_logger'][$alias]->extend($key,$value);
}

/**
 * Adds a category to all loggers
 */
function logging_add_category($name)
{
    foreach( $GLOBALS['logging_logger'] as $l )
		$l->addCategory($name);
}

/**
 * Removes a category from all loggers
 */
function logging_remove_category($name)
{
    foreach( $GLOBALS['logging_logger'] as $l )
		$l->removeCategory($name);
}

/**
 * Sets the minimum severity to log.
 */
function logging_set_level($min_severity = "INFO")
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->min_severity = $min_severity;
}

/**
 * Checks the object store for an object with id $object_storage_id 
 * that contains a field $fieldname. Then adds contant of that field as category to all loggers.
 * 
 * Note: This will NOT extend the logger with information as logging_extend_logger does!
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
 * Logs to severity TRACE
 */
function log_trace($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->trace($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * Logs to severity DEBUG
 */
function log_debug($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->debug($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * Logs to severity INFO
 */
function log_info($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->info($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * Logs to severity WARN
 */
function log_warn($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->warn($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * Logs to severity ERROR
 */
function log_error($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->error($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * Logs to severity FATAL
 */
function log_fatal($a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->fatal($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
}

/**
 * Logs the $label and $value arguments and then returns the $value argument.
 * Use case:
 * function x($a){ return log_return("this is a",$a); }
 */
function log_return($label,$value)
{
	log_debug($label,$value);
	return $value;
}

/**
 * Calls log_debug if the condition is TRUE and then returns the condition
 * Use case:
 * log_if( !isset($some_var), "Missing data");
 */
function log_if($condition,$a1=null,$a2=null,$a3=null,$a4=null,$a5=null,$a6=null,$a7=null,$a8=null,$a9=null,$a10=null)
{
	if( $condition )
		log_debug($a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10);
	return $condition;
}

/**
 * Calls log_debug if the condition is FALSE and then returns the condition
 * Use case:
 * if( log_if_not( isset($some_var), "Missing data") )
 * {
 *    do_something_with($some_var);
 * }
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
 * Returns an object of type LogReport, see doc there.
 * Use log_report to finally write the report to logs.
 */
function log_start_report($name)
{
	$res = new LogReport($name);
	return $res;
}

/**
 * Writes a log-report to the logs.
 * 
 * Use log_start_report to generate a report.
 */
function log_report(LogReport $report, $severity="TRACE")
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->report($report,$severity);
}

/**
 * Renders a variable into a string representation.
 * 
 * Feel free to use alias function render_var instead as it is shorter
 */
function logging_render_var($content,&$stack=array(),$indent="")
{
	foreach( $stack as $s )
	{
		if( $s === $content )
			return "*RECURSION*";
	}
	$res = array();
	if( is_array($content) )
	{
		if( count($content) == 0 )
			return "*EmptyArray*";
		$res[] = "Array\n$indent(";
		$stack[] = $content;
		foreach( $content as $i=>$val )
			$res[] = $indent."\t[$i]: ".logging_render_var($val,$stack,$indent."\t");
		$res[] = $indent.")";
	}
	elseif( is_object($content) )
	{
		$res[] = "Object(".get_class($content).")\n$indent{";
		$stack[] = $content;
		foreach( get_object_vars($content) as $name=>$val )
		{
			if( $val === $content )
				$res[] = $indent."\t->$name: *RECURSION*";
			else
				$res[] = $indent."\t->$name: ".logging_render_var($val,$stack,$indent."\t");
		}
		$res[] = $indent."}";
	}
	elseif( is_bool($content) )
		return (count($stack)>0?"(bool)":"").($content?"true":"false");
	else
		return (count($stack)>0?"(".gettype($content).")":"").strval($content);
	return implode("\n",$res);
}

/**
 * Alias function for logging_render_var
 */
function render_var($content)
{
	return logging_render_var($content);
}

/**
 * now the old error-module functions to have this compatible to old code.
 * note: will not add the following functions
 * - function number()
 * - function relative_path($path)
 * - function write_log_line($severity,$msg,$stacktrace="",$call_debugger = true)
 */

function dump($val,$severity="DEBUG",$stacktrace = "")
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->write($severity,$stacktrace!="",$val);
}

function trace($val,$severity="TRACE")
{
	foreach( $GLOBALS['logging_logger'] as $l )
		$l->write($severity,true,$val);
}

function error($val,$stacktrace = "")
{
	log_error($val);
}

function dumpif($condition,$val,$severity="DEBUG",$stacktrace = "")
{
	if( $condition )
		dump($val,$severity,$stacktrace);
}

function my_var_export($subject, $ignored_bool=true)
{
	return logging_render_var($subject);
}

function u_print_r(&$subject, $ignore = array(), $depth = 1, $refChain = array())
{
	return logging_render_var($subject);
}

function set_db_log_category($category)
{
	logging_add_category($category);
}