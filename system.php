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

define('FRAMEWORK_LOADED','uSI7hcKMQgPaPKAQDXg5');
define('__SCAVIXWDF__',__DIR__);
date_default_timezone_set("Europe/Berlin");
require_once(__DIR__.'/system_objects.php');
require_once(__DIR__.'/system_functions.php');

use ScavixWDF\Base\AjaxResponse;
use ScavixWDF\Base\Args;
use ScavixWDF\Base\Renderable;
use ScavixWDF\ICallable;
use ScavixWDF\Model\DataSource;
use ScavixWDF\Reflection\WdfReflector;
use ScavixWDF\Wdf;
use ScavixWDF\WdfException;
use ScavixWDF\WdfResource;

// Homebrew CLI args if missing
// see https://www.php.net/manual/en/ini.core.php#ini.register-argc-argv
if( !isset($argv) && isset($_SERVER['argv']) )
{
    $argv = $_SERVER['argv'];
    $argc = max(1,count($argv));
}

// Moved here from cli module to make sure SERVER var is same as in calling (apache env) process.
// This is needed to make all dependent configs/modules/... work as expected.
if( PHP_SAPI == 'cli' )
{
    foreach( $argv as $i=>$a )
    {
        if( starts_iwith($a,'--wdf-extended-data') )
        {
            $datafile = substr($a,19);
            $data = json_decode(@file_get_contents($datafile),true);
            if( is_array($data) )
                $_SERVER = array_merge($_SERVER,$data);
            @unlink($datafile);
            unset($argv[$i]);
            break;
        }
    }
}

// Config handling
system_config_default( !defined("NO_DEFAULT_CONFIG") );
if( file_exists("config.php") )
	system_config("config.php",false);
elseif( file_exists(__DIR__."/config.php") )
	system_config(__DIR__."/config.php",false);
elseif( !defined("NO_CONFIG_NEEDED") )
	system_die("No valid configuration found!");

/**
 * Loads a config file.
 *
 * Should not be used if a config file is present in root path.
 * @param string $filename Full path to the config file
 * @param bool $reset_to_defaults If true resets the complete config to the one to read
 * @return void
 */
function system_config($filename,$reset_to_defaults=true)
{
	global $CONFIG;
	if( $reset_to_defaults )
		system_config_default();

    detectEnvironment(dirname($filename));
	require_once($filename);
}

/**
 * Resets the global $CONFIG variable to defauls values.
 *
 * Just sets some useful default values. This is also a good reference of the basic system variables.
 * @param bool $reset If true resets the config completely to default, extends/overwrites only if false
 * @return void
 * @suppress PHP0443
 */
function system_config_default($reset = true)
{
	global $CONFIG;

	# see http://www.php.net/manual/de/session.configuration.php
	ini_set('session.hash_function',1);
	ini_set('session.hash_bits_per_character',5);


	if( $reset )
		$CONFIG = [];

	$CONFIG['class_path']['system'][]  = __DIR__.'/reflection/';
	$CONFIG['class_path']['system'][]  = __DIR__.'/base/';
	$CONFIG['class_path']['system'][]  = __DIR__.'/tasks/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/listing/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/form/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/table/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/locale/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/jquery-ui/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/jquery-ui/dialog/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/jquery-ui/slider/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/widgets/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/google/';

	$CONFIG['class_path']['order'] = ['system','model','content'];

	$CONFIG['system']['path_root'] = __DIR__;

	$CONFIG['requestparam']['ignore_case'] = true;
	$CONFIG['requestparam']['tagstostrip'] = ['script'];

	$CONFIG['model']['internal']['auto_create_tables'] = true;
	$CONFIG['model']['internal']['datasource_type']    = 'DataSource';
	$CONFIG['model']['internal']['debug']			   = false;

	$CONFIG['system']['application_name'] = 'wdf_application';
	$CONFIG['system']['cache_datasource'] = 'internal';
	$CONFIG['system']['cache_ttl'] = 3600; // secs

	$CONFIG['system']['hook_logging'] = false;

	$CONFIG['system']['header']['Content-Type'] = "text/html; charset=utf-8";
	$CONFIG['system']['header']['X-XSS-Protection'] = "1; mode=block";
    $CONFIG['system']['header']["Referrer-Policy"] = "strict-origin-when-cross-origin";

    $path = explode("index.php",$_SERVER['PHP_SELF']);
    if(PHP_SAPI == 'cli')
        $path = ['/'];
	if( !isset($_SERVER['REQUEST_SCHEME']) )
		$_SERVER['REQUEST_SCHEME'] = urlScheme(false);
	if( !isset($_SERVER['HTTP_HOST']) )
		$_SERVER['HTTP_HOST'] = '127.0.0.1';
	if( !isset($_SERVER['REQUEST_URI']) )
		$_SERVER['REQUEST_URI'] = '';


    if(defined('IDNA_DEFAULT') && defined('INTL_IDNA_VARIANT_UTS46'))
    {
        /**
         * Bug in PHP 7.2: INTL_IDNA_VARIANT_2003 is deprecated but used as default value @see: http://php.net/manual/de/migration72.deprecated.php
         */
        $CONFIG['system']['url_root'] = idn_to_utf8("{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}", IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46)."{$path[0]}";
        $CONFIG['system']['same_page'] = idn_to_utf8("{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}", IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46)."{$_SERVER['REQUEST_URI']}";
    }
    else
    {
        $CONFIG['system']['url_root'] = idn_to_utf8("{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}")."{$path[0]}";
        $CONFIG['system']['same_page'] = idn_to_utf8("{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}")."{$_SERVER['REQUEST_URI']}";
    }
    $CONFIG['system']['modules'] = [];
    $CONFIG['system']['default_page'] = \ScavixWDF\Base\HtmlPage::class;
    $CONFIG['system']['default_event'] = false;
	$CONFIG['system']['tpl_ext'] = array("tpl.php");

	$CONFIG['system']['admin']['enabled']  = false;
	$CONFIG['system']['admin']['username'] = false;
	$CONFIG['system']['admin']['password'] = false;
}

/**
 * Loads a module.
 *
 * Use this to manually load a module. You can also add it to the config so that
 * system_init() loads it automatically.
 * @param string $path_to_module Complete path to module file
 * @return void
 */
function system_load_module($path_to_module)
{
	// prevent double-loading:
	$mod = basename($path_to_module,".php");

    if( $mod == "mod_phpexcel" )
        WdfException::Raise("PHPExcel module has bee removed. Use PhpSpreadsheet instead.");

	if(system_is_module_loaded($mod))
		return;

	require($path_to_module);

	$initfuncname = $mod."_init";
	if( function_exists($initfuncname) )
		$initfuncname();

	execute_hooks(HOOK_POST_MODULE_INIT,array($mod));

	// mark module loaded:
	Wdf::$Modules[$mod] = $path_to_module;
}

/**
 * Checks if a module is already loaded.
 *
 * Looks into `Wdf::$Modules` if there's a key named `$mod`.
 * @param string $mod The name of the module (not the path!)
 * @return bool true or false
 */
function system_is_module_loaded($mod)
{
	return isset(Wdf::$Modules[$mod]);
}

/**
 * Initializes the Scavix ScavixWDF.
 *
 * This is one of two essential functions you must know about.
 * Initializes the complete ScavixWDF, loads all essentials and defined modules and initializes them,
 * prepares the session and writes out some headers (from config too).
 * @param string $application_name Application name. This will become your session cookie name!
 * @param bool $skip_header Optional. If true, will not send headers.
 * @param bool $logging_category An initial category for logging. Very optional!
 * @return void
 */
function system_init($application_name, $skip_header = false, $logging_category=false)
{
	global $CONFIG;
	$thispath = __DIR__;

	$CONFIG['system']['application_name'] = $application_name;
	if(!isset($CONFIG['model']['internal']['connection_string']))
		$CONFIG['model']['internal']['connection_string']  = 'sqlite::memory:';

    // load essentials as if they were modules.
	system_load_module('essentials/logging.php');
	system_load_module('essentials/model.php');
	system_load_module('essentials/session.php');
	system_load_module('essentials/resources.php');
	system_load_module('essentials/admin.php');
	system_load_module('essentials/localization.php');
	system_load_module('essentials/translation.php');
	foreach( system_glob($thispath.'/essentials/*.php') as $essential ) // load all other essentials
		system_load_module($essential);

    // on posix systems: automatically load cli-module when we are actually in cli
    if( PHP_SAPI=='cli' && !function_exists('cli_init') )
        system_load_module('modules/cli.php');

	if( $logging_category )
		logging_add_category($logging_category);
	session_run();

    // auto-load all system-modules defined in $CONFIG['system']['modules']
	foreach( $CONFIG['system']['modules'] as $mod )
	{
		if( file_exists($thispath."/modules/$mod.php") )
			system_load_module($thispath."/modules/$mod.php");
		elseif( file_exists( "$mod.php") )
			system_load_module("$mod.php");
		elseif( file_exists( "$mod") )
			system_load_module("$mod");
	}

	if( isset($_REQUEST['request_id']) )
		session_keep_alive();

	// attach more headers here if required
	if( !$skip_header && PHP_SAPI!='cli' )
	{
		try {
			foreach( $CONFIG['system']['header'] as $k=>$v )
				header("$k: $v");
		} catch(Exception $ex) { log_debug($ex); }
	}

	// if $_SERVER['SCRIPT_URI'] is not set build from $_SERVER['SCRIPT_NAME'] and $_SERVER['SERVER_NAME'] Mantis #3477
	if( ( !isset($_SERVER['SCRIPT_URI']) || $_SERVER['SCRIPT_URI'] == '' ) && isset($_SERVER['SCRIPT_NAME']) && isset($_SERVER['SERVER_NAME']) )
	{
		$_SERVER['SCRIPT_URI'] = $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
	}

	execute_hooks(HOOK_POST_INIT);
}

/**
 * Parses the request and returns a controller/event pair (if present).
 *
 * Note that your .htaccess files must contain these lines:
 * <code>
 * SetEnv WDF_FEATURES_REWRITE on
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteCond %{REQUEST_FILENAME} !-d
 * RewriteCond %{REQUEST_URI} !index.php
 * RewriteRule (.*) index.php?wdf_route=$1 [L,QSA]
 * </code>
 * @return array
 */
function system_parse_request_path()
{
	if( isset($_REQUEST['wdf_route']) )
	{
		// test for *.less request -> need to compile that to css
		if( ends_iwith($_REQUEST['wdf_route'],".less") )
		{
            Wdf::$Request->RouteArgs = array($_REQUEST['wdf_route']);
			$GLOBALS['routing_args'] = array($_REQUEST['wdf_route']); // compat
			unset($_REQUEST['wdf_route']);
			unset($_GET['wdf_route']);
			return ['ScavixWDF\WdfResource','CompileLess'];
		}

		// now for the normal processing
		if( isset($GLOBALS['CONFIG']['wdf_route_parser']) )
        {
            $path = $GLOBALS['CONFIG']['wdf_route_parser'];
            $path = $path(explode("/",$_REQUEST['wdf_route']));
        }
        else
            $path = explode("/",$_REQUEST['wdf_route']);
        Wdf::$Request->Route = $path;
		$GLOBALS['wdf_route'] = $path; // compat
		unset($_REQUEST['wdf_route']);
		unset($_GET['wdf_route']);

		if( count($path)>0 )
		{
			if( $path[0]=='~' ) $path[0] = cfg_get('system','default_page');
			$path[0] = fq_class_name($path[0]);
			if( class_exists($path[0]) || in_object_storage($path[0]) )
			{
				$controller = $path[0];
				if( count($path)>1 )
				{
                    $offset = 2;
                    if( in_object_storage($path[0]) || system_method_exists($controller,$path[1]) )
                        $event = $path[1];
                    else
                        $offset = 1;

					if( count($path)>$offset )
					{
						foreach( array_slice($path,$offset) as $ra )
                            if( $ra !== '' )
                            {
                                Wdf::$Request->RouteArgs[] = $ra;
                                $GLOBALS['routing_args'][] = $ra; // compat
                            }
					}
				}
			}
		}
	}

	if( !isset($controller) || !$controller )
    {
		$controller = Args::request('page', cfg_get('system','default_page')); // really oldschool
        Wdf::$Request->UsingDefaultPage = true;
    }
	if( !isset($event) || !$event )
    {
		$event = Args::request('event', cfg_get('system','default_event')); // really oldschool
        Wdf::$Request->UsingDefaultEvent = true;
    }

	$pattern = '/[^A-Za-z0-9\-_\\\\]/';
	$controller = substr(preg_replace($pattern, "", $controller), 0, 256);
	$event = substr(preg_replace($pattern, "", $event), 0, 256);
	return [$controller,$event];
}

/**
 * Instanciates the previously chosen controller
 *
 * Checks what is requested: and object from the object-store, a controller via classname and loads/instaciates it.
 * Will also die in AJAX requests when something weird is called or throw an exception if in normal mode.
 * @param mixed $controller_id Whatever system_parse_request_path() returned
 * @return ICallable Fresh Instance of whatever is needed
 */
function system_instanciate_controller($controller_id)
{
	if( in_object_storage($controller_id) )
		$res = restore_object($controller_id);
	elseif( class_exists($controller_id) )
		$res = new $controller_id();
	else
		WdfException::Raise("ACCESS DENIED: Unknown controller '$controller_id'","REQ=",$_REQUEST);

	if( system_is_ajax_call() )
	{
		if( !($res instanceof Renderable) && !($res instanceof WdfResource) )
		{
			log_fatal("ACCESS DENIED: $controller_id is no Renderable");
			die("__SESSION_TIMEOUT__");
		}
	}
	else if( !($res instanceof ICallable) )
		WdfException::Raise("ACCESS DENIED: $controller_id is no ICallable");

	return $res;
}

/**
 * Executes the current request.
 *
 * This is the second of two essential functions.
 * It runs the actual execution. If fact it is the only place where you will
 * find an `echo` in the ScavixWDF code.
 * @return void
 */
function system_execute()
{
	session_sanitize();
	execute_hooks(HOOK_POST_INITSESSION);

    if( PHP_SAPI == 'cli' && function_exists('cli_execute') )
        cli_execute();

	// respond to PING requests that are sended to keep the session alive
	if( Args::request('ping',false) )
	{
        session_update(true);
		execute_hooks(HOOK_PING_RECIEVED);
		die("PONG");
	}

	Args::strip_tags();

    Wdf::$Request = new stdClass();
    Wdf::$Request->URL = $GLOBALS['CONFIG']['system']['same_page'];
	list($current_controller,$current_event) = system_parse_request_path();
    Wdf::$Request->CurrentController = $current_controller;
    Wdf::$Request->CurrentEvent = $current_event;

    execute_hooks(HOOK_PRE_CONSTRUCT,array($current_controller,$current_event));

	Wdf::$Request->CurrentController = $current_controller
        = system_instanciate_controller($current_controller);

    if( system_method_exists($current_controller,'__translate_event') )
		$current_event = call_user_func([$current_controller,'__translate_event'],$current_event);

	if( !(system_method_exists($current_controller,$current_event) ||
		(system_method_exists($current_controller,'__method_exists') && $current_controller->__method_exists($current_event) )) )
	{
		Wdf::$Request->CurrentEvent = $current_event
            = cfg_get('system','default_event');
	}

	if( !isset($GLOBALS['wdf_route']) ) // compat
		$GLOBALS['wdf_route'] = array($current_controller,$current_event); // compat

    if( !isset(Wdf::$Request->Route) )
        Wdf::$Request->Route = array($current_controller,$current_event);

	if( system_method_exists($current_controller,$current_event) ||
		(system_method_exists($current_controller,'__method_exists') && $current_controller->__method_exists($current_event) ) )
	{
		$content = system_invoke_request($current_controller,$current_event,HOOK_PRE_EXECUTE);
	}else $content = '';

	@set_time_limit(ini_get('max_execution_time'));
	system_exit($content,false);
}

/**
 * Executes the given request.
 *
 * Will parse the target class/method for required parameters
 * and prepare the data given in the $_REQUEST variable to match them.
 * @param string $target_class Name of the class
 * @param string $target_event Name of the method
 * @param int $pre_execute_hook_type Type of Hook to be executed pre call
 * @return mixed The result of the target-methods
 */
function system_invoke_request($target_class,$target_event,$pre_execute_hook_type)
{
	$ref = WdfReflector::GetInstance($target_class);
	$params = $ref->GetMethodAttributes($target_event,"RequestParam");
	$args = [];

    if( count($params) > 0 )
    {
        $argscheck = [];
        $failedargs = [];

        $req_data = array_merge($_FILES,$_GET,$_POST);
        $last = max(array_keys($params));
        foreach( $params as $i=>$prm )
        {
            $argscheck[$prm->Name] = $prm->UpdateArgs($req_data,$args,$i==$last);
            if( $argscheck[$prm->Name] !== true )
            {
                $failedargs[$prm->Name] = "ARGUMENT FAILED";
                $args[$prm->Name] = "ARGUMENT FAILED";
            }
        }

        if( count($failedargs) > 0 )
            execute_hooks(HOOK_ARGUMENTS_PARSED, $failedargs);
    }

	execute_hooks($pre_execute_hook_type,array($target_class,$target_event,$args));
	return call_user_func_array(array(&$target_class,$target_event), array_values($args));
}

/**
 * Terminats the current run and presents a result to the browser.
 *
 * @param mixed $result The result that shall be passed to the browser
 * @param bool $die If true uses <die>() for output, else uses <echo>()
 * @return void
 */
function system_exit($result=null,$die=true)
{
    execute_hooks(HOOK_POST_EXECUTE);

	if( !isset($result) || !$result )
		$result = current_controller(false);
	$response = '';

    if( PHP_SAPI == 'cli' )
        die("Missing CLI handling, cannot render HTML here\n");

	if( system_is_ajax_call() )
	{
		if( $result instanceof AjaxResponse )
			$response = $result->Render();
        elseif( $result instanceof ScavixWDF\Base\HtmlPage )
        {
            // use this to redirect if some previous AJAX redirect should have in fact been redirected to an HTML page.
            log_error("Cannot deliver HtmlPage via AJAX, redirecting instead:",system_current_request());
            $response = AjaxResponse::Redirect(system_current_request(true))->Render();
        }
		elseif( $result instanceof Renderable )
			$response = AjaxResponse::Renderable($result)->Render();
		elseif( !is_string($result) )
			WdfException::Raise("Unknown AJAX return value",$result);
        else
            $response = $result;
	}
	elseif( $result instanceof AjaxResponse ) // is system_is_ajax_call() failed to detect AJAX but response in fact IS for AJAX
		die("__SESSION_TIMEOUT__");
	else
	{
		$_SESSION['request_id'] = request_id();
        $_SESSION['latest_requests'][$_SESSION['request_id']] = [current_controller(),current_event(),$_GET,$_POST];
        while( count($_SESSION['latest_requests']) > 20 )
            array_shift($_SESSION['latest_requests']);

		if($result instanceof Renderable)
		{
			$response = $result->WdfRenderAsRoot();
			if( $result->_translate && system_is_module_loaded("translation") )
				$response = __translate($response);
		}
		elseif( system_is_module_loaded("translation") )
			$response = __translate($result);
	}

    if( function_exists('translation_add_unknown_strings') )
        translation_add_unknown_strings();
    if( function_exists('model_store') )
        model_store();
    if( function_exists('session_update') )
        session_update();

	execute_hooks(HOOK_PRE_FINISH, [$response]);

    if($response instanceof stdClass)
        $response = (array) $response;
    if(is_array($response))
        $response = json_encode($response);

	if( $die )
		die($response);
	echo $response;
}

/**
 * Terminats the current run.
 *
 * Will be called from exception and error handlers. You may, call this directly, but we
 * recommend to throw an exception instead. See the WdfException class and it's Raise() method
 * for more about this.
 * Note: This function will call `system_exit()`!
 * @param string $reason The reason as human readable and hopefully understandable text
 * @param string $details_internal More details to be logged
 * @param bool $log_error If true logs error to files, else just die
 * @return void
 */
function system_die($reason,$details_internal='',$log_error=true)
{
    $details_internal = '';
	if( $reason instanceof Exception )
	{
		$stacktrace = ($reason instanceof WdfException)?$reason->getTraceEx():$reason->getTrace();
		$details_internal = (($reason instanceof WdfException) && avail($reason, 'details'))?"\n".$reason->details:'';
		$reason = logging_render_var($reason);
	}

	if( !isset($stacktrace) )
		$stacktrace = debug_backtrace();

    $errid = uniqid();
    $logmsg = 'Fatal system error (ErrorID: '.$errid.')'."\n".$reason.$details_internal."\n".system_stacktrace_to_string($stacktrace);
    if( $log_error )
    {
        if(avail($_SERVER, 'REQUEST_URI'))
            $logmsg.= "\nRequest URI: ".$_SERVER['REQUEST_URI'];
        if(count($_POST))
            $logmsg.= "\nPOST: ".logging_render_var($_POST);
        if( function_exists('log_fatal') )
            log_fatal($logmsg);
        else
            error_log($logmsg);
    }
    if( isset(Wdf::$Hooks[HOOK_SYSTEM_DIE]) && count(Wdf::$Hooks[HOOK_SYSTEM_DIE]) > 0 )
	{
		execute_hooks(HOOK_SYSTEM_DIE,array(
			$reason,
			$stacktrace
		));
	}
    if( PHP_SAPI == 'cli' )
    {
        if(!isDev())
            $logmsg = 'Oh no! A fatal system error occured. Please try again. Contact our technical support if this problem occurs again (ErrorID: '.$errid.')';
		system_exit("$logmsg\n");
    }
    elseif( system_is_ajax_call() )
	{
        if(!isDev())
            $logmsg = 'Oh no! A fatal system error occured. Please try again. Contact our technical support if this problem occurs again (ErrorID: '.$errid.')';
        $res = AjaxResponse::Error($logmsg, true);
		system_exit($res->Render());
	}
	else
	{
		$res  = "<html><head><style> * { font-family: Arial,sans-serif; } body { margin: 20px; } </style><title>Fatal system error</title></head>";
		$res .= "<body>";
		$res .= "<h1>Oh no! A fatal system error occured... :-(</h1>";
		if(isDev())
			$res .= "Error ID: {$errid}<br/><pre>$reason</pre>".($details_internal ? "<pre>$details_internal</pre>" : '')."<pre>".system_stacktrace_to_string($stacktrace)."</pre>";
		else
            $res .= "<br/>Please try again.<br/>Contact our technical support if this problem occurs again (ErrorID: {$errid}).<br/><br/>Apologies for any inconveniences this may have caused you.";
		$res .= "</body></html>";
        system_exit($res);
	}
}

/**
 * Terminates script execution and delivers valid HTTP header information.
 *
 * @param mixed $code The HTTP status code to be delivered, @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
 * @return void
 */
function system_die_http($code)
{
    static $codes = [
        100 => "Continue",
        101 => "Switching Protocols",
        102 => "Processing",
        103 => "Early Hints",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        207 => "Multi-Status",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Moved Temporarily",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        307 => "Temporary Redirect",
        308 => "Permanent Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Entity Too Large",
        414 => "Request-URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        418 => "I'm a teapot",
        419 => "Insufficient Space on Resource",
        420 => "Method Failure",
        421 => "Misdirected Request",
        422 => "Unprocessable Entity",
        423 => "Locked",
        424 => "Failed Dependency",
        426 => "Upgrade Required",
        428 => "Precondition Required",
        429 => "Too Many Requests",
        431 => "Request Header Fields Too Large",
        451 => "Unavailable For Legal Reasons",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported",
        507 => "Insufficient Storage",
        511 => "Network Authentication Required",
    ];
    $protocol = ifavail($_SERVER, 'SERVER_PROTOCOL') ?: 'HTTP/1.1';
    header("$protocol $code " . (ifavail($codes, $code) ?: 'Unknown'), true, $code);
    die();
}

/**
 * Registers a function to be executed on a system hook.
 *
 * Note that this registers a function! If you want an objects method to be executed, see `register_hook()`.
 * @param int $type Valid hook type (see the HOOK_* constants)
 * @param string|\Closure $handler_method name of function to call
 * @param bool $prepend If true will prepend, else will append
 * @return void
 */
function register_hook_function($type,$handler_method,$prepend=false)
{
	$dummy = false;
	register_hook($type,$dummy,$handler_method,$prepend);
}

/**
 * Registers a method to be executed on a system hook.
 *
 * Note that this registers an objects method! If you want function to be executed, see `register_hook_function()`.
 * @param int $type Valid hook type (see the HOOK_* constants)
 * @param object $handler_obj The object containig the handler method
 * @param string $handler_method name of method to call
 * @param bool $prepend If true will prepend, else will append
 * @return void
 */
function register_hook($type,&$handler_obj,$handler_method,$prepend=false)
{
	if( !isset(Wdf::$Hooks[$type]) )
		Wdf::$Hooks[$type] = [];

	is_valid_hook_type($type);
    if( $prepend )
        array_unshift(Wdf::$Hooks[$type],[$handler_obj, $handler_method]);
	else
        Wdf::$Hooks[$type][] = [$handler_obj, $handler_method];
}

/**
 * Removes previously registered hook handler from all hook types.
 *
 * This is automatically called when content is removed from <Renderable> objects to avoid performing actions on objects that are not part
 * of the DOM anymore.
 * @param object $handler_obj The object taht shall be removed from the hanlder stack
 * @return void
 */
function release_hooks($handler_obj)
{
	foreach( Wdf::$Hooks as $type=>$stack )
		foreach( $stack as $i=>$def )
			if( is_array($def) && ($def[0] == $handler_obj) )
				unset( Wdf::$Hooks[$type][$i] );

	foreach( Wdf::$Hooks as $type=>$stack )
		Wdf::$Hooks[$type] = array_values($stack);
}

/**
 * Executes a system hook (calls all registered handlers).
 *
 * This is very internal, but no magic: just loops all registered handlers and calls them.
 * Arguments given vary from hook_type to hook_type.
 * @param int $type Valid hook type (see the HOOK_* constants)
 * @param array $arguments to be passed to the handler functions/methods
 * @return void
 */
function execute_hooks($type,$arguments = [])
{
	global $CONFIG;

	Wdf::$Hooks['fired'][$type] = $type;
	if( !isset(Wdf::$Hooks[$type]) )
    	return;

	is_valid_hook_type($type);

	$loghooks = $CONFIG['system']['hook_logging'];

	if( $loghooks )
		log_debug("BEGIN ".hook_type_to_string($type));

	// note: as hooks may be added to the chain do not remove the count(...) here: it may grow!
	for($i=0; $i<count(Wdf::$Hooks[$type]); $i++)
	{
		list($hook0,$hook1) = Wdf::$Hooks[$type][$i];
		if( is_object($hook0) )
		{
			if( $loghooks )
				log_debug( "Executing ".get_class($hook0)."->".$hook1."(...)",hook_type_to_string($type) );
            $res = $hook0->$hook1($arguments);
			if( $loghooks )
				log_debug( "result:",$res);
		}
		else
		{
			if( $loghooks )
                if( is_string($hook1) )
                    log_debug( "Executing '".$hook1."(...)'",hook_type_to_string($type) );
                else
                    log_debug( "Executing 'Closure(...)'",hook_type_to_string($type) );
            $res = $hook1($arguments);
		}

		if( $res === false )
		{
			if( $loghooks )
				log_debug("ABORT ".hook_type_to_string($type));
			break;
		}
	}
	if( $loghooks )
		log_debug("END ".hook_type_to_string($type));
}

/**
 * Checks if a given int is a valid hook type.
 *
 * Checks a given integer if it represents a valid hook_type.
 * @param int $type Value to be checked against valid hook type (see the HOOK_* constants)
 * @return bool true if valid
 */
function is_valid_hook_type($type)
{
	if( $type == HOOK_POST_INIT || $type == HOOK_POST_INITSESSION || $type == HOOK_PRE_CONSTRUCT ||
	    $type == HOOK_PRE_EXECUTE || $type == HOOK_POST_EXECUTE ||
		$type == HOOK_PRE_FINISH || $type == HOOK_POST_MODULE_INIT ||
		$type == HOOK_PING_RECIEVED || $type == HOOK_SYSTEM_DIE || $type == HOOK_PRE_RENDER ||
		$type == HOOK_ARGUMENTS_PARSED
		)
		return true;

	WdfException::Raise("Invalid hook type ($type)!");
    return false;
}

/**
 * Returns the string representation of an int hook type.
 *
 * In fact just returns the constant name as a string, so
 * <code php>
 * echo (hook_type_to_string(HOOK_POST_INIT) == 'HOOK_POST_INIT')?'true':'false';
 * // output: true
 * </code>
 * @param int $type Hook type
 * @return string Type as string or 'HOOK_UNDEFINED' if $type is not a valid hook type
 */
function hook_type_to_string($type)
{
	switch( $type )
	{
		case HOOK_POST_INIT: return 'HOOK_POST_INIT';
		case HOOK_POST_INITSESSION: return 'HOOK_POST_INITSESSION';
        case HOOK_PRE_CONSTRUCT: return 'HOOK_PRE_CONSTRUCT';
		case HOOK_PRE_EXECUTE: return 'HOOK_PRE_EXECUTE';
		case HOOK_POST_EXECUTE: return 'HOOK_POST_EXECUTE';
		case HOOK_PRE_FINISH: return 'HOOK_PRE_FINISH';
		case HOOK_POST_MODULE_INIT: return 'HOOK_POST_MODULE_INIT';
		case HOOK_PING_RECIEVED: return 'HOOK_PING_RECIEVED';
		case HOOK_SYSTEM_DIE: return 'HOOK_SYSTEM_DIE';
		case HOOK_PRE_RENDER: return "HOOK_PRE_RENDER";
		case HOOK_ARGUMENTS_PARSED: return 'HOOK_ARGUMENTS_PARSED';

	}
	return 'HOOK_UNDEFINED';
}

/**
 * Checks if the hook of the given type is already fired
 *
 * Sometimes you'll need to know the step of the current execution. You may use this function
 * to check which hooks have already been fired.
 * @param int $type Hook Type
 * @return bool true|bool
 */
function hook_already_fired($type)
{
	if( isset(Wdf::$Hooks['fired']) && isset(Wdf::$Hooks['fired'][$type]) )
		return true;
	return false;
}

/**
 * Checks if there is a handler bound to a HOOK
 *
 * Checks if there's at least one handler registered for the hook
 * @param int $type Hook Type
 * @return bool true|bool
 */
function hook_bound($type)
{
	return isset(Wdf::$Hooks[$type]) && count(Wdf::$Hooks[$type]) > 0;
}

/**
 * Returns a string representation of the given stacktrace
 *
 * This is kind of internal, but may be of use. We shift the stacktrace a bit to have more information
 * in each line that belong together.
 * @param array $stacktrace Use debug_backtrace() to get this
 * @return string The stacktrace-string
 */
function system_stacktrace_to_string($stacktrace)
{
	$stack = [];

	$stcnt = count($stacktrace);
	for($i=1; $i<=$stcnt; $i++)
	{
		$t0 = $stacktrace[$i-1];
		$t1 = isset($stacktrace[$i]) ? $stacktrace[$i] : array("function" => "");

		if( isset($t1['class']) && isset($t1['type']) )
			$function = $t1['class'].$t1['type'].$t1['function'];
		else
			$function = $t1['function'];

		if( isset($t0['file']) && isset($t0['line']) )
		{
			$rp_file = $t0['file'];
			$stack[] = sprintf("+ %s(...) [in %s:%s]",$function,$rp_file,$t0['line']);
		}
		elseif($function)
			$stack[] = sprintf("+ %s(...)",$function);
	}
	return implode("\n",$stack);
}

/**
 * Sets a specific key of the classpath array to be searched first.
 * @param string $key_to_priorize the key to be priorized
 * @return array The classpath array before reordering
 */
function __priorize_classpath($key_to_priorize)
{
	global $CONFIG;

	$cp = $CONFIG['class_path']['order'];
	$CONFIG['class_path']['order'] = array($key_to_priorize);
	foreach( $cp as &$cp_item )
		if( $CONFIG['class_path']['order'] != $key_to_priorize )
			$CONFIG['class_path']['order'][] = $cp_item;

	return $cp;
}

/**
 * Sets the classpath search order.
 * @param array The new classpath order.
 */
function __set_classpath_order($class_path_order)
{
	global $CONFIG;
	$CONFIG['class_path']['order'] = $class_path_order;
}

/**
 * Called whenever a class shall be instanciated but there's no definition found
 *
 * See http://www.php.net/manual/de/function.spl-autoload-register.php
 * @param string $class_name Name of the class to load
 * @return void
 */
function system_spl_autoload($class_name)
{
	if(($class_name == "") || ($class_name[0] == "<"))
		return;  // it's html
    try
    {
		if( strpos($class_name, '\\') !== false )
		{
			$orig = $class_name;
			$array = explode('\\',$class_name);
			$class_name = $array[count($array)-1]; ;
		}
        $file = __search_file_for_class($class_name) ?: __search_file_for_class($class_name, "trait.php");
        if( $file && is_readable($file) )
		{
			$pre = get_declared_classes();
            require_once($file);
			$post = array_unique(array_diff(get_declared_classes(), $pre));

			foreach( $post as $cd )
			{
				$d = explode("\\",$cd);
                if (count($d) > 1)
                {
                    $scn = array_pop($d);
                    create_class_alias($cd, $scn);
                    if( $cd == $scn )
                        $def = $scn;
                }
                elseif( $cd == $class_name )
                    $def = $cd;
			}

            if( !isset($def) )
                $def = array_pop($post);

			if( !isset($orig) && !$def ) // plain class requested AND file was already included, so search up the declared classes and alias
			{
				foreach( array_reverse($pre) as $c )
				{
					if( !ends_with($c,$class_name) )
						continue;
					log_trace("Aliasing previously included class '$c' to '$class_name'. To avoid this check the use statements or use a qualified classname.");
					create_class_alias($c,$class_name,true);
					break;
				}
			}
			else
			{
				$class_name = isset($orig)?$orig:$class_name;
				if( $def && (strtolower($def) != strtolower("$class_name")) && ends_iwith($def,$class_name) ) // no qualified classname requested but class was defined with namespace
				{
					log_trace("Aliasing class '$def' to '$class_name'. To avoid this check the use statements or use a qualified classname.");
					create_class_alias($def,$class_name,true);
				}
			}
		}
    }
    catch(Exception $ex)
    { WdfException::Log("system_spl_autoload",$ex); };
}
spl_autoload_register("system_spl_autoload",true,true);

/**
 * Tries to load the template for the calling class
 * @param object|string $controller Object or class to load template for
 * @param string $template_name Pass '' (empty string) for this.
 * @return bool|string Returns the filename if found, else false
 */
function __autoload__template($controller,$template_name)
{
	global $CONFIG;
	if( is_object($controller) )
		$class = strtolower(get_class($controller));
	else
		$class = $controller;

	if( $template_name != "" )
	{
        $key = "autoload_template-".session_name().'-'.getAppVersion('nc').'-'.$template_name;
        $r = cache_get($key);
        if( ($r != false) && file_exists($r) )
            return $r;

		if( file_exists($template_name) )
		{
			cache_set($key, $template_name, $CONFIG['system']['cache_ttl']);
			return $template_name;
		}

		$template_name2 = dirname(__search_file_for_class($class))."/".$template_name;
		if( file_exists($template_name2) )
		{
			cache_set($key, $template_name2, $CONFIG['system']['cache_ttl']);
			return $template_name2;
		}

        $template_name2 = dirname(__search_file_for_class($class))."/base/".$template_name;
		if( file_exists($template_name2) )
		{
			cache_set($key, $template_name2, $CONFIG['system']['cache_ttl']);
			return $template_name2;
		}
	}

    $key = "autoload_template_class-".session_name().'-'.getAppVersion('nc').'-'.$class;
    $r = cache_get($key);
    if( ($r != false) && file_exists($r) )
        return $r;

	$file = __search_file_for_class($class);
	foreach( array_reverse($CONFIG['system']['tpl_ext']) as $tpl_ext )
	{
		$tpl_file = str_replace("class.php",$tpl_ext,$file?$file:"");
		if( file_exists($tpl_file) )
		{
			cache_set($key, $tpl_file, $CONFIG['system']['cache_ttl']);
			return $tpl_file;
		}
	}

	$pclass = get_parent_class($class);
	if( $pclass !== false && strtolower($pclass) != "template" )
		return __autoload__template($pclass,"");

	return false;
}

/**
 * searches the $CLASS_PATH for the file that defines the class
 * @param string $class_name
 * @param string $extension
 * @param mixed $classpath_limit
 * @return mixed
 */
function __search_file_for_class($class_name,$extension="class.php",$classpath_limit=false)
{
	global $CONFIG;

    $key = "autoload_class-".session_name().'-'.getAppVersion('nc').'-'.$class_name.$extension.$classpath_limit;
    $r = cache_get($key);
    if( $r !== false )
        return $r;

	$class_name_lc = strtolower($class_name);

	$short_class_name = "";
	if( strpos($class_name,"_") !== false )
	{
		$short_class_name = array_last(explode("_",$class_name));
		$short_class_name_lc = strtolower($short_class_name);
	}
	elseif( strpos($class_name,"\\") !== false )
	{
		$short_class_name = array_last(explode("\\",$class_name));
		$short_class_name_lc = strtolower($short_class_name);
	}

	foreach( $CONFIG['class_path']['order'] as $cp_part )
	{
		if( !isset($CONFIG['class_path'][$cp_part]))
			continue; //WdfException::Raise("Invalid ClassPath! No entry for '$cp_part'.");

		if( $classpath_limit && $cp_part != $classpath_limit )
			continue;

		foreach( $CONFIG['class_path'][$cp_part] as $path )
		{
			if( file_exists("$path$class_name.$extension") )
			{
				$ret = "$path$class_name.$extension";
                cache_set($key, $ret, $CONFIG['system']['cache_ttl']);
				return $ret;
			}

			if( file_exists("$path$class_name_lc.$extension") )
			{
				$ret = "$path$class_name_lc.$extension";
				cache_set($key, $ret, $CONFIG['system']['cache_ttl']);
				return $ret;
			}

			if( $short_class_name != "" )
			{
				if( file_exists("$path$short_class_name.$extension") )
				{
					$ret = "$path$short_class_name.$extension";
					cache_set($key, $ret, $CONFIG['system']['cache_ttl']);
					return $ret;
				}

				if( file_exists("$path$short_class_name_lc.$extension") )
				{
					$ret = "$path$short_class_name_lc.$extension";
					cache_set($key, $ret, $CONFIG['system']['cache_ttl']);
					return $ret;
				}
			}
		}
	}
	return false;
}

/**
 * Builds a request.
 *
 * This is quite basic and used very often. It will return an URL to the given controller.
 * It checks if the routing features are enabled and ensures the the URLs are working!
 * @param mixed $controller The page to be loaded (can be <Renderable> or string)
 * @param string $event The event to be executed
 * @param array|string $data Optional data to be passed
 * @param string $url_root Optional root, will use system-wide detected/set one if not given
 * @return string A complete Request (for use as HREF)
 */
function buildQuery($controller,$event="",$data="", $url_root=false)
{
	global $CONFIG;

	if( $controller instanceof Renderable )
    {
        if( empty($controller->_storage_id) )
            log_warn("Trying to buildQuery for ".get_class($controller)." without id. Did you call before parent::__construct?",system_get_caller());
        elseif( !in_object_storage($controller->_storage_id) )
            store_object($controller);
		$controller = $controller->_storage_id;
    }

    if(substr($controller, 0, 4) == "http" || substr($controller, 0, 2) == "//")
        return $controller;

	// allow buildQuery('controller/method')
	if( is_string($controller) && $event=="" && $data=="" && !$url_root )
	{
		if( preg_match('|^([a-z0-9]+)/([a-z0-9]+)$|i',$controller) )
			list($controller,$event) = explode("/",$controller);
	}

	if($controller != "")
		$route = "$controller/";
	else
		$route = "";
	if( $event != "" )
	{
		$route .= $event;
		if( '#' != substr($event, 0, 1) )
			$route .= '/';
	}

	/**
	 * data can contain a # to jump to named anchors i.e. on redirect
	 */
	$hash = false;
	if( is_array($data) )
	{
		if(isset($data['#']))
		{
			$hash = $data['#'];
			unset($data['#']);
		}
		$data = http_build_query($data);
	}

	if( !can_rewrite() )
	{
		$data = http_build_query(array('wdf_route'=>$route)).($data?"&$data":"");
		$route = "";
	}

	if( isDev() && isset($_REQUEST["XDEBUG_PROFILE"]) )
        $data .= ($data?"&":"")."XDEBUG_PROFILE";

    if (function_exists('session_needs_url_arguments') && session_needs_url_arguments())
        $data .= "&" . session_name() . "=" . session_id();

	if( !$url_root )
		$url_root = $CONFIG['system']['url_root'];
	return $url_root.$route.($data?"?$data":"").($hash?'#'.$hash:'');
}

/**
 * Builds a query for the current page.
 *
 * Calls buildQuery internally to build an URL to the current route.
 * @param string|array $data Additional data
 * @return string A complete Request (for use as HREF)
 */
function samePage($data="")
{
    if( avail(Wdf::$Request,'URL') )
    {
        if( !$data )
            return Wdf::$Request->URL;
        if( is_array($data) )
            $data = http_build_query($data);
        return array_first(explode("?",Wdf::$Request->URL))."?{$data}";
    }
	return buildQuery(current_controller(),current_event(),$data);
}

/**
 * Executed a header redirect to another page.
 *
 * Calls buildQuery internally to build an URL to the current route, but will also work
 * if `$controller` already is an URL.
 * Note: Will terminate the current processing silently and sent a "Location" header!
 * @param string $controller The page to be called
 * @param string $event The event to be executed
 * @param array|string $data Optional data to be passed
 * @param string $url_root Optional root, will use system-wide detected/set one if not given
 * @return void
 */
function redirect($controller,$event="",$data="",$url_root=false)
{
	if( is_array($controller) )
	{
		$url = [];
		foreach( $controller as $key=>&$val )
			$url[] = "$key=$val";
		$url = '?'.implode("&",$url);
	}
	else
		$url = buildQuery($controller,$event,$data,$url_root);

    translation_add_unknown_strings();

    // As discussed this breaks when AJAX request shall be redirected itself.
    // Trying to redirect if HTML is rendered in Ajax context, see <system_exit>.
//    if( system_is_ajax_call() )
//        system_exit(AjaxResponse::Redirect($url));

	header("Location: ".$url);
    system_exit('Location: '.$url);
}

/**
 * Generates random string in the given length.
 *
 * Can be used as password, sessionid, ticket....
 * @param int $len The length of the return string
 * @param int $case_sensitive If FALSE, only upper case chars are used. Applies only if $chars is not given
 * @param int $chars Chars to generate password from
 * @return string The generated string sequence
 */
function generatePW($len = 8, $case_sensitive=true, $chars='')
{
    if( !$chars )
    {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        if( $case_sensitive )
            $chars .= "abcdefghijklmnopqrstuvwxyz";
        $chars .= "0123456789";
    }
	$res = "";
    if( function_exists('random_int') )
    {
        while( strlen($res) < $len )
            $res .= $chars[random_int(0,strlen($chars)-1)];
    }
    else
    {
        mt_srand ((double) microtime(false) * 1000000);
        while( strlen($res) < $len )
            $res .= $chars[mt_rand(0,strlen($chars)-1)];
    }
	return $res;
}

/**
 * Appends a version parameter to a link.
 *
 * This is useful to avoid browser-side CSS and JS caching.
 * @param string $href The URL
 * @return string A new URL appended the nocache string
 */
function appendVersion($href)
{
	if( !isset($GLOBALS['APP_VERSION']) )
		setAppVersion (0, 0, 0, "default");

	if( !$href || $href[0] == '/' )
		return "/{$GLOBALS['APP_VERSION']['nc']}$href";
	return "{$GLOBALS['APP_VERSION']['nc']}/$href";
}

/**
 * Checks a string and returns true if it is UTF-8 encoded
 *
 * This performs some dirty checks and tries to detect if the given string is UTF8 encoded
 * @param string $string String to check
 * @return bool True if UTF-8
 */
function detectUTF8($string)
{
    return preg_match('%(?:
	    [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
	    |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
	    |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
	    |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
	    |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
	    |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
	    |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
	    )+%xs', $string);
}

/**
 * Returns an array containing the parameters of the referrer string.
 *
 * If $part is given (and set in data) will only return this value.
 * @param string $part Name of URL parameter to get
 * @return string|array Value of URL parameter $part if given, else array of all URL parameters
 */
function referrer($part='')
{
	$ref = explode("?",$_SERVER['HTTP_REFERER']);
	$res = [];
    $arref = explode("&",$ref[1]);
	foreach( $arref as $tmp )
	{
		list($name,$val) = explode("=",$tmp,2);
		$res[$name] = $val;
	}

	if( isset($res[$part]) )
		return $res[$part];

	return $res;
}

/**
 * Checks wether the calling IP address matches the given host od IP.
 *
 * May be useful to detect known IP addresses/hosts easily
 * @param string $host_or_ip Hostname or IP to be checked
 * @return bool true or false
 */
function is_host($host_or_ip)
{
	$ip_address = get_ip_address();
	if( $host_or_ip ==  $ip_address )
		return true;
	if( gethostbyaddr($ip_address) == $host_or_ip )
		return true;
	return false;
}

/**
 * Returns a value from the wdf cache.
 *
 * There are multiple caches: SESSION and global.
 * Global cache required additional globalcache module to be loaded.
 * Will only consult globalcache if `$use_global_cache` is true and `$use_session_cache` is false or
 * the object is not found in the SESSION cache
 * @param string $key Identifies what you want
 * @param mixed $default The default value you want if key is not present in the cache
 * @param bool $use_global_cache If true checks the global cache too (see globalcache module)
 * @param bool $use_session_cache If true checks the SESSION cache (that one is before the global cache)
 * @return mixed The value if found, else the default value
 */
function cache_get($key,$default=false,$use_global_cache=true,$use_session_cache=true)
{
	if ($use_session_cache && isset($_SESSION["system_internal_cache"][$key]) && function_exists('session_unserialize'))
	{
		if(is_array($_SESSION["system_internal_cache"][$key]))
		{
			if (avail($_SESSION["system_internal_cache"][$key], 'valid_until') && ($_SESSION["system_internal_cache"][$key]['valid_until'] < time()))
				unset($_SESSION["system_internal_cache"][$key]);
			else
				return session_unserialize($_SESSION["system_internal_cache"][$key]['data']);
		}
		else
			return session_unserialize($_SESSION["system_internal_cache"][$key]);
	}
	if( $use_global_cache && system_is_module_loaded('globalcache') )
    {
        $res = globalcache_get($key,$default);
		// if ($use_session_cache)
		// {
		// 	if ($res == $default)
		// 	{
		// 		if(isset($_SESSION["system_internal_cache"][$key]))
		// 			unset($_SESSION["system_internal_cache"][$key]);
		// 	}
		// 	else
		// 		$_SESSION["system_internal_cache"][$key] = ['data' => session_serialize($res)];
		// }
		return $res;
    }
    return $default;
}

/**
 * Stores a string value into the internal cache.
 *
 * Noting to say. Just stores where you want.
 * @param string $key a key for the value
 * @param mixed $value the value to store
 * @param int $ttl Time to life in seconds. -1 if it shall live forever
 * @param bool $use_global_cache If true stores in the global cache (see globalcache module)
 * @param bool $use_session_cache If true stores in the SESSION cache
 * @return void
 */
function cache_set($key,$value,$ttl=false,$use_global_cache=true,$use_session_cache=true)
{
	global $CONFIG;
	if( $ttl === false )
		$ttl = $CONFIG['system']['cache_ttl'];

	if( $use_global_cache && system_is_module_loaded('globalcache') )
		globalcache_set($key, $value, $ttl);

    if( $use_session_cache && function_exists('session_serialize') )
    {
		if (!isset($_SESSION['system_internal_cache']) || !is_array($_SESSION['system_internal_cache']))
			$_SESSION['system_internal_cache'] = [];
		if($ttl && ($ttl > 0))
			$_SESSION["system_internal_cache"][$key] = ['valid_until' => time() + $ttl, 'data' => session_serialize($value)];
		else
			$_SESSION["system_internal_cache"][$key] = ['data' => session_serialize($value)];
    }
}

/**
 * Removes an entry from the cache
 *
 * Will simply do nothing if there's nothing stored for the key.
 * @param string $key The key identifiying the entry
 * @return void
 */
function cache_del($key)
{
    if( isset($_SESSION["system_internal_cache"][$key]) )
		unset($_SESSION["system_internal_cache"][$key]);
	if( system_is_module_loaded('globalcache') )
		globalcache_delete($key);
}

/**
 * Clears the cache
 *
 * Note that calling this will NOT clear the complete `$_SESSION` variale, but only
 * `$_SESSION["system_internal_cache"]`.
 * @param bool $global_cache If true clears the global cache (see globalcache module)
 * @param bool $session_cache If true clears the SESSION cache
 * @return void
 */
function cache_clear($global_cache=true, $session_cache=true)
{
    if( $session_cache )
		$_SESSION["system_internal_cache"] = [];

    unset($_SESSION['js_strings_version']); // force JS string regeneration

    if( $global_cache && system_is_module_loaded('globalcache') )
		globalcache_clear();
    clear_less_cache();
}

/**
 * Returns a list of all keys in the cache
 *
 * Note that the returned array contains all key that are in one of the requested stores.
 * Means that there may be keys that are only in SESSION, but not in globalcache.
 * @param bool $global_cache If true checks the global cache (see globalcache module)
 * @param bool $session_cache If true checks the SESSION cache
 * @return array All defined keys
 */
function cache_list_keys($global_cache=true, $session_cache=true)
{
    $res = ($session_cache&&isset($_SESSION["system_internal_cache"]))
        ?array_keys($_SESSION["system_internal_cache"])
        :[];

	if( $global_cache && system_is_module_loaded('globalcache') )
		$res = array_merge($res, globalcache_list_keys() );

	sort($res);
	return array_unique($res);
}

/**
 * Returns the current chosen controller
 *
 * Note that if you request a controller object (`$as_string==false`) that may still be a string, if it has not been
 * instaciated yet!
 * @param bool $as_string If true will return the classname (or id if it is from object store)
 * @return mixed Depending on $as_string: Classname/Id or controller object
 */
function current_controller($as_string=true)
{
	if( !isset(Wdf::$Request->CurrentController) )
		return $as_string?'':null;
	if( $as_string )
		return strtolower(
            is_object(Wdf::$Request->CurrentController)
                ?get_class_simple(Wdf::$Request->CurrentController)
                :Wdf::$Request->CurrentController
            );
	return Wdf::$Request->CurrentController;
}

/**
 * Returns the current chosen event
 *
 * This can return an empty string if there's no current event or if that has not yet been parsed or if it simply IS an empty string.
 * @return string The current event
 */
function current_event()
{
	return isset(Wdf::$Request->CurrentEvent)?strtolower(Wdf::$Request->CurrentEvent):'';
}

/**
 * Returns the current url
 *
 * @return string The current url
 */
function current_url()
{
	return (isSSL() ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

/**
 * Returns information about the current request.
 *
 * If the current request is an AJAX request, it returns info about the last 'normal' call.
 * @param bool $as_url If true will return a string URL containing all the GET parametes
 * @return array|string Array with (string)controller,(string)method,(array)get and (array)post
 */
function system_current_request($as_url=false)
{
    if( system_is_ajax_call() )
    {
        $rid = Args::request('request_id');
        if( $rid && isset($_SESSION['latest_requests'][$rid]) )
        {
            if( !$as_url )
                return $_SESSION['latest_requests'][$rid];
            return buildQuery
            (
                $_SESSION['latest_requests'][$rid][0],
                $_SESSION['latest_requests'][$rid][1],
                $_SESSION['latest_requests'][$rid][2]
            );
        }
    }
    if( $as_url )
        return samePage($_GET);
    return [current_controller(),current_event(),$_GET,$_POST];
}

/**
 * Returns the value of a given class constant.
 *
 * Will check against name match and will use endswith to try to find
 * names without prefix.
 * Check is case insensitive!
 * @param string $class_name_or_object name of the class or object containing the constant
 * @param string $constant_name name of the constant to get
 * @return mixed value of the found constant or NULL
 */
function constant_from_name($class_name_or_object,$constant_name)
{
	$ref = WdfReflector::GetInstance($class_name_or_object);
	$constant_name = strtolower($constant_name);
	foreach( $ref->getConstants() as $name=>$value )
		if( strtolower($name) == $constant_name || ends_with(strtolower($name), $constant_name) )
			return $value;
	return null;
}

/**
 * Returns the name of a given class constant.
 *
 * Will check all constant values and return the first match.
 * @param string $class_name name of the class containing the constant
 * @param mixed $constant_value value of the constant to get
 * @param string $prefix Checked constants need to start with this prefix (useful if there are different constants with the same value)
 * @return string name of the found constant or NULL
 */
function name_from_constant($class_name,$constant_value,$prefix=false)
{
	$ref = WdfReflector::GetInstance($class_name);
	foreach( $ref->getConstants() as $name=>$value )
		if( $value == $constant_value && (!$prefix || starts_with($name, $prefix)) )
			return $name;
	return null;
}

/**
 * Wrapper for json_encode that ensures JS functions are not quoted.
 *
 * Will detect code that starts with '[jscode]' or 'function('
 * Example:
 * <code php>
 * array(
 *		'test1'=>"function(){alert('1');}",   // <- works
 *		'test2'=>"[jscode]SomeFunctionName",  // <- SomeFunctionName must be defined in code
 *		'test3'=>"[jscode]alert('1')"         // <- wont work because it is a call!
 * )
 * <code>
 * will generate
 * <code javascript>
 * {"test1":function(){alert('1');}, "test2":SomeFunctionName, "test3": alert('1')} // <- syntax error due to test3
 * <code>
 * Note: Make sure your 'embedded' JS code does NOT end with a semicolon (;)!
 * @param mixed $value Value to be encoded as JSON
 * @return string JSON encoded value
 */
function system_to_json($value)
{
	$res = json_encode($value);
    $res = preg_replace_callback('/\"\[jscode\](.*)\"([,\]\}])/U',
        function($m)
        {
            // single quotes are essential here,
            // or alternative escape all $ as \$
            return stripcslashes($m[1]).$m[2];
        }, $res );
    $res = preg_replace_callback('/\"function\(.*[^\\\\]\"/U',
        function($m)
        {
            // single quotes are essential here,
            // or alternative escape all $ as \$
            return json_decode($m[0]);
        }, $res );
	return $res;
}

/**
 * Calls an objects method with given arguments
 *
 * `call_user_func_array` does not allow byref arguments since 5.3 anymore
 * so we wrap this in our own funtion. This is even faster then `call_user_func_array`.
 * @param object $object Object to call methos in
 * @param string $funcname Name of method to call
 * @param array $args Arguments to pass to the method
 * @return mixed The result of the called method
 */
function system_call_user_func_array_byref(&$object, $funcname, &$args)
{
	switch(count($args))
	{
		case 0:
			return $object->{$funcname}();
		case 1:
			return $object->{$funcname}($args[0]);
		case 2:
			return $object->{$funcname}($args[0], $args[1]);
		case 3:
			return $object->{$funcname}($args[0], $args[1], $args[2]);
		case 4:
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3]);
		case 5:
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3], $args[4]);
		case 6:
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
		case 7:
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
		case 8:
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
		default:
			return call_user_func_array(array($object, $funcname), $args);
	}
}

/**
 * Checks if a method exists in a class.
 *
 * This performs cached searches, so it is faster than native method_exists function when called
 * multiple times.
 * @param mixed $object_or_classname Object or classname to check
 * @param string $method_name Name of method to check for
 * @return bool true or false
 */
function system_method_exists($object_or_classname,$method_name)
{
	if( is_array($object_or_classname) || (is_scalar($object_or_classname) && !is_string($object_or_classname)) )
		return false;

	$key = 'method_exists_'.session_name().'-'.getAppVersion('nc').'-'.(is_string($object_or_classname)?$object_or_classname:get_class($object_or_classname)).'.'.$method_name;
	$ret = cache_get($key);
	if( $ret !== false )
		return $ret=="1";
	$ret = method_exists($object_or_classname,$method_name);
	cache_set($key,$ret?"1":"0");
	return $ret;
}

/**
 * Shuffle an array and preserve key=>value binding
 *
 * http://www.php.net/manual/en/function.shuffle.php#94697
 * @param array $array Array to be shuffled
 * @return void
 */
function shuffle_assoc(&$array)
{
	$keys = array_keys($array);
	shuffle($keys);
	foreach($keys as $key)
		$new[$key] = $array[$key];
	$array = $new;
}

/**
 * Renders a complete object tree.
 *
 * This means that the tree is checked for Renderable objects, arrays and so on
 * and all the needed actions are triggered recursively.
 * @param array $array_of_objects Array of objects
 * @return mixed An array containing the rendered strings
 */
function system_render_object_tree($array_of_objects)
{
	if( !isset($GLOBALS['system_render_object_tree_stack']) )
		$GLOBALS['system_render_object_tree_stack'] = [];

	$res = [];
	foreach( $array_of_objects as $key=>&$val )
	{
		if( $val instanceof Renderable )
		{
			if( in_array($val, $GLOBALS['system_render_object_tree_stack'], true) )
			{
                $info = [];
                foreach( $GLOBALS['system_render_object_tree_stack'] as $obj )
                    $info[] = "".$obj;
				log_debug("XREF in object tree! Object already rendered elsewhere:","$val",$info);
				continue;
			}
			$GLOBALS['system_render_object_tree_stack'][] = $val;
			$res[$key] = $val->WdfRender();
		}
		elseif( is_array($val) )
			$res[$key] = system_render_object_tree($val);
		elseif( $val instanceof DateTime )
			$res[$key] = $val->format("Y-m-d H:i:s");
		else
			$res[$key] = system_encode_for_output($val,true);
	}
	return $res;
}

/**
 * Encodes a string for output to the browser.
 *
 * This function basically uses htmlentities to savely encode output thus avoiding XSS attacks.
 * If recursively walks given arrays/objects and is able to encode <Model> objects properties only.
 * It also avoid double encoding $values.
 *
 * Note that <Model> objects that are assigned to <Control>s or <Template>s are automatically encoded by the WDF.
 *
 * @param mixed $value Value or array/object of values to be encoded
 * @param bool $encode_models_only If true only properties of <Model> objects are encoded
 * @return mixed The encoded value(s)
 */
function system_encode_for_output($value,$encode_models_only=false)
{
	if( !$encode_models_only && is_string($value) )
		return htmlentities($value,ENT_COMPAT | ENT_HTML401,"UTF-8",false);

    if( is_object($value) )
        $value = clone($value);

	if( $value instanceof ScavixWDF\Model\Model )
	{
		foreach( $value->GetColumnNames() as $col )
		{
			if( isset($value->$col) )
				$value->$col = system_encode_for_output($value->$col,false);
		}
		return $value;
	}
	if( is_array($value) )
	{
		foreach( $value as $k=>$v )
			$value[$k] = system_encode_for_output($v);
		return $value;
	}
	return $value;
}

/**
 * @internal Called from the autoloader and used for backwards compatibility: This is needed for projects that do not yet use WDF with namespaces).
 */
function create_class_alias($original,$alias,$strong=false)
{
	if( $strong )
		class_alias($original,$alias);

	$alias = strtolower($alias);
	if( isset(Wdf::$ClassAliases[$alias]) )
	{
		if( Wdf::$ClassAliases[$alias] == $original )
			return;

		if( !is_array(Wdf::$ClassAliases[$alias]) )
			Wdf::$ClassAliases[$alias] = array(Wdf::$ClassAliases[$alias]);
        elseif( in_array($original,Wdf::$ClassAliases[$alias]) )
            return;
		Wdf::$ClassAliases[$alias][] = $original;
	}
	else
		Wdf::$ClassAliases[$alias] = $original;
}

/**
 * @internal Maps a classname given as string to a full qualified class identifier.
 */
function fq_class_name($classname)
{
	if( strpos($classname, '\\')!==false )
		return $classname;
	$cnl = strtolower($classname);
	switch( $cnl )
	{
		case 'template':                  return '\\ScavixWDF\\Base\\Template';
		case 'renderable':                return '\\ScavixWDF\\Base\\Renderable';
		case 'control':                   return '\\ScavixWDF\\Base\\Control';
		case 'requestparamattribute':     return '\\ScavixWDF\\Reflection\\RequestParamAttribute';
		case 'resourceattribute':         return '\\ScavixWDF\\Reflection\\ResourceAttribute';
		case 'externalresourceattribute': return '\\ScavixWDF\\Reflection\\ExternalResourceAttribute';
		case 'nominifyattribute':         return '\\ScavixWDF\\Reflection\\NoMinifyAttribute';
		case 'wdfresource':               return '\\ScavixWDF\\WdfResource';
		case 'datasource':                return '\\ScavixWDF\\Model\\DataSource';
		case 'sysadmin':                  return '\\ScavixWDF\\Admin\\SysAdmin';
		case 'translationadmin':          return '\\ScavixWDF\\Translation\\TranslationAdmin';
		case 'minifyadmin':               return '\\ScavixWDF\\Admin\\MinifyAdmin';
		case 'tracelogger':               return '\\ScavixWDF\\Logging\\TraceLogger';
		case 'phpsession':                return '\\ScavixWDF\\Session\\PhpSession';
        case 'dbsession':                 return '\\ScavixWDF\\Session\\DbSession';
		case 'sessionstore':              return '\\ScavixWDF\\Session\\SessionStore';
        case 'dbstore':                   return '\\ScavixWDF\\Session\\DbStore';
        case 'apcstore':                  return '\\ScavixWDF\\Session\\APCStore';
        case 'redisstore':                return '\\ScavixWDF\\Session\\RedisStore';
        case 'filesstore':                return '\\ScavixWDF\\Session\\FilesStore';
	}

	if( isset(Wdf::$ClassAliases[$cnl]) )
	{
		if( is_array(Wdf::$ClassAliases[$cnl]) )
			WdfException::Raise("Ambigous classname: $classname",Wdf::$ClassAliases[$cnl]);
		return Wdf::$ClassAliases[$cnl];
	}
	return $classname;
}

/**
 * Checks if a process it still running.
 *
 * @param int $pid Process id to check
 * @return bool true if running, else false
 */
function system_process_running($pid)
{
    if( !$pid || !is_numeric($pid) )
        return false;

    static $detection_mode = 0;
    if( $detection_mode === 0)
    {
        if (PHP_OS_FAMILY == "Linux")
        {
            if (function_exists('posix_getpgid')) // this is the fastest way to check if a process is running
                $detection_mode = 1;
            else
                $detection_mode = 2; // see further method testing below
        }
        else
            $detection_mode = -1; // this is currenlty windows, the 'default' below
    }

    switch( $detection_mode )
    {
        case 1:
            return !!posix_getpgid($pid);

        case 2: // 2 signals that further "best-method-testing" is needed

        case 10: // try to get process info via /proc
            $stat = @file_get_contents("/proc/$pid/stat");
            if ($stat)
            {
                $d = sscanf($stat, "%d %s %c %d");
                if (isset($d[2]))
                {
                    if ($detection_mode == 2)
                        $detection_mode = 10;
                    return $d[2] == 'S' || $d[2] == 'R' || $d[2] == 'D';
                }
            }
            if ($detection_mode == 10) // if "best-method-testing" succeeded before, 'false' is correct result
                return false;
            // fall thru to "ps"-detection because "proc" access may be forbidden

        case 11:
            $output = [];
            exec("ps -q $pid -o pid= 2>/dev/null", $output); // this is in fact really slow but failsafe
            if (!empty($output))
            {
                if ($detection_mode == 2) // if "proc"-detection faild but this check worked, disable "proc"
                    $detection_mode = 11;
                return true;
            }
            return false;

        default:
            return strpos(shell_exec("tasklist /FI \"PID eq $pid\" /FO \"CSV\" /NH"), "\"$pid\"");
    }
}

/**
 * Creates a named lock.
 *
 * This is useful in some special cases where different PHP processes are creating for example datasets that must be
 * unique. So use it like this:
 * <code php>
 * system_get_lock('creating_something');
 * // do critical things
 * system_release_lock('creating_something');
 * </code>
 * Note that `system_get_lock` will check all existent locks if the processes that created them are still running
 * by using <system_process_running>(). That one depends on <shell_exec>() so make sure it is not disabled.
 * Another note to the datasource argument: This defaults to 'internal' and the 'internal' datasource defaults to 'sqlite:memory'.
 * So if you dont change this the locks will have no effect beyond process bounds!
 * @param string $name A name for the lock.
 * @param mixed $datasource Name of datasource to use or <DataSource> object itself.
 * @param int $timeout Timeout in seconds (an Exception will be thrown on timeout). If <=0 will return immediately true|false
 * @return void|bool Returns true|false only if $timeout is <=0. Else will return nothing or throw an exception
 */
function system_get_lock($name,$datasource='internal',$timeout=10)
{
	$ds = ($datasource instanceof DataSource)?$datasource:model_datasource($datasource);
	$ds->ExecuteSql("CREATE TABLE IF NOT EXISTS `wdf_locks` (
        `lockname` VARCHAR(500) NOT NULL,
        `pid` INT(10) UNSIGNED NOT NULL,
        `created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`lockname`)
        ) ENGINE=MEMORY;");

	$start = microtime(true);

	$args = array($name,getmypid());
	do
	{
		if( isset($cnt) )
		{
			usleep(100000);
			if( microtime(true)-$start > $timeout)
				WdfException::Raise("Timeout while awaiting the lock '$name'");
		}

		foreach( $ds->ExecuteSql("SELECT pid FROM wdf_locks")->Enumerate('pid') as $pid )
		{
			if( !system_process_running($pid) )
				$ds->ExecuteSql("DELETE FROM wdf_locks WHERE pid=?",$pid);
		}
		try
        {
            $ds->ExecuteSql("INSERT OR IGNORE INTO wdf_locks(lockname,pid)VALUES(?,?)",$args);
            $cnt = $ds->getAffectedRowsCount();
        }
        catch (Exception $ex)
        {
            $cnt = 0;
        }

		if( $cnt == 0 && $timeout <= 0 )
			return false;
	}while( $cnt == 0 );
	return true;
}

/**
 * Releases a named lock.
 *
 * See <system_get_lock>() for details about this.
 * @param string $name Name of the lock to release
 * @param mixed $datasource Name of datasource to use or <DataSource> object itself.
 * @return bool Returns true if the lock was released, else false.
 */
function system_release_lock($name,$datasource='internal')
{
	$ds = ($datasource instanceof DataSource)?$datasource:model_datasource($datasource);
	$rs = $ds->ExecuteSql("DELETE FROM wdf_locks WHERE lockname=?",$name);
	return ($rs->Count() > 0);
}
