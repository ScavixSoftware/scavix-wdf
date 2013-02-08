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
 
define('FRAMEWORK_LOADED','uSI7hcKMQgPaPKAQDXg5');
require_once(__DIR__.'/system_exceptions.php');
require_once(__DIR__.'/system_functions.php');

// Config handling
system_config_default( !defined("NO_DEFAULT_CONFIG") );
if( file_exists("config.php") )
	include("config.php");
elseif( file_exists(__DIR__."/config.php") )
	include(__DIR__."/config.php");
elseif( !defined("NO_CONFIG_NEEDED") )
	system_die("No valid configuration found!");

/**
 * Loads a consig file. Should not be used if a config file is present ind root path.
 * @param string $filename
 */
function system_config($filename,$reset_to_defaults=true)
{
	if( $reset_to_defaults )
		system_config_default();
	require_once($filename);
}

/**
 * Resets the global $CONFIG variable to defauls values.
 */
function system_config_default($reset = true)
{
	global $CONFIG;

	if( $reset )
		$CONFIG = array();
	
	$CONFIG['class_path']['system'][]  = __DIR__.'/reflection/';
	$CONFIG['class_path']['system'][]  = __DIR__.'/base/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/extender/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/form/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/table/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/locale/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/jquery-ui/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/jquery-ui/dialog/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/jquery-ui/slider/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/widgets/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/google/';
	
	$CONFIG['class_path']['order'] = array('system','model','content');

	$CONFIG['system']['path_root'] = __DIR__;

	$CONFIG['requestparam']['ignore_case'] = true;
	$CONFIG['requestparam']['tagstostrip'] = array('script');

	$CONFIG['model']['internal']['auto_create_tables'] = true;
	$CONFIG['model']['internal']['datasource_type']    = 'System_DataSource';	
	$CONFIG['model']['internal']['debug']			   = false;

	$CONFIG['system']['application_name'] = 'wdf_application';
	$CONFIG['system']['cache_datasource'] = 'internal';
	$CONFIG['system']['cache_ttl'] = 3600; // secs

	$CONFIG['system']['hook_logging'] = false;
	$CONFIG['system']['attach_session_to_ajax'] = false;
	
	$CONFIG['system']['header']['Content-Type'] = "text/html; charset=utf-8";
	$CONFIG['system']['header']['X-XSS-Protection'] = "1; mode=block";
	
    $path = explode("index.php",$_SERVER['PHP_SELF']);
	if( !isset($_SERVER['REQUEST_SCHEME']) )
		$_SERVER['REQUEST_SCHEME'] = 'http';
	
	$CONFIG['system']['url_root'] = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}{$path[0]}";
    $CONFIG['system']['modules'] = array();
    $CONFIG['system']['default_page'] = "HtmlPage";
    $CONFIG['system']['default_event'] = false;
	$CONFIG['system']['tpl_ext'] = array("tpl.php");
	
	$CONFIG['system']['admin']['enabled']  = false;
	$CONFIG['system']['admin']['username'] = false;
	$CONFIG['system']['admin']['password'] = false;
}

/**
 * Loads a module.
 * @param string $path_to_module Complete path to module file
 */
function system_load_module($path_to_module)
{
	// prevent double-loading:
	$mod = basename($path_to_module,".php");

	if(system_is_module_loaded($mod))
		return true;

	require($path_to_module);

	$initfuncname = $mod."_init";
	if( function_exists($initfuncname) )
		$initfuncname();

	execute_hooks(HOOK_POST_MODULE_INIT,array($mod));

	// mark module loaded:
	$GLOBALS["loaded_modules"][$mod] = $path_to_module;
}

/**
 * Checks if a module is already loaded.
 * @param <type> $mod The name of the module (not the path!)
 */
function system_is_module_loaded($mod)
{
	return isset($GLOBALS["loaded_modules"][$mod]);
}

/**
 * Initializes the framework.
 * @param string $application_name Optional application name to be stored in config.
 * @param bool $skip_header Optional. If true, does not send headers.
 */
function system_init($application_name, $skip_header = false, $logging_category=false)
{
	global $CONFIG;
	$thispath = dirname(__FILE__);

	if(!isset($_SESSION["system_internal_cache"]))
		$_SESSION["system_internal_cache"] = array();

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
	foreach( glob($thispath.'/essentials/*.php') as $essential ) // load all other essentials
		system_load_module($essential);
	
	if( $logging_category )
		logging_add_category($logging_category);
	logging_set_user(); // works as both (session and logging) are now essentials
	
	// auto-load all system-modules defined in $CONFIG['system']['modules']
	foreach( $CONFIG['system']['modules'] as $mod )
	{
		if( file_exists($thispath."/modules/$mod.php") )
			system_load_module($thispath."/modules/$mod.php");
		elseif( file_exists( "$mod.php") )
			system_load_module("$mod.php");
	}

	//if( $CONFIG['error']['clean_each_run'] )
	//	log_debug("=== Initialization (modules already loaded =================================");
	session_run();

	if( isset($_REQUEST['request_id']) )
	{
		session_keep_alive('request_id');
	}

	// attach more headers here if required
	if( !$skip_header )
	{
		try {
			foreach( $CONFIG['system']['header'] as $k=>$v )
				header("$k: $v");
		} catch(Exception $ex) {}
	}

	// if $_SERVER['SCRIPT_URI'] is not set build from $_SERVER['SCRIPT_NAME'] and $_SERVER['SERVER_NAME'] Mantis #3477
	if( ( !isset($_SERVER['SCRIPT_URI']) || $_SERVER['SCRIPT_URI'] == '' ) && isset($_SERVER['SCRIPT_NAME']) && isset($_SERVER['SERVER_NAME']) )
	{
		$_SERVER['SCRIPT_URI'] = $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
	}
    
	execute_hooks(HOOK_POST_INIT);
}

function system_parse_request_path()
{
	// parse the request for controller and event	
	$path = explode("index.php",$_SERVER['PHP_SELF']);
	$path = explode("/",trim($path[1],"/"));
	if( count($path)>0 )
	{
		if( class_exists($path[0]) || in_object_storage($path[0]) )
		{
			$controller = $path[0];
			if( count($path)>1 )
			{
				$event = $path[1];
				if( count($path)>2 )
					$GLOBALS['routing_args'] = array_slice($path,2);
			}
		}
	}
	
	if( !isset($controller) || !$controller )
		$controller = Args::request('page', cfg_get('system','default_page')); // really oldschool
	if( !isset($event) || !$event )
		$event = Args::request('event', cfg_get('system','default_event')); // really oldschool
	
	$pattern = "/[^A-Za-z0-9\-_]/";
	$controller = substr(preg_replace($pattern, "", $controller), 0, 256);
	$event = substr(preg_replace($pattern, "", $event), 0, 256);
	return array($controller,$event);
}

function system_instanciate_controller($controller_id)
{
	if( in_object_storage($controller_id) )
		$res = restore_object($controller_id);
	elseif( class_exists($controller_id) )
		$res = new $controller_id();
	
	if( system_is_ajax_call() )
	{
		if( !($res instanceof Renderable) )
		{
			log_fatal("ACCESS DENIED: $controller_id is no Renderable");
			die("__SESSION_TIMEOUT__");
		}
	}
	else if( !($res instanceof ICallable) )
		throw new Exception("ACCESS DENIED: $controller_id is no ICallable");
	
	return $res;
}

/**
 * Executes the current request.
 */
function system_execute()
{
	session_sanitize();
	execute_hooks(HOOK_POST_INITSESSION);

	// respond to PING requests that are sended to keep the session alive
	if( Args::request('ping',false) )
	{
		session_keep_alive();
		execute_hooks(HOOK_PING_RECIEVED);
		die("PONG");
	}

	Args::strip_tags();
	
	global $current_controller,$current_event;
	list($current_controller,$current_event) = system_parse_request_path();

	$current_controller = system_instanciate_controller($current_controller);

	if( system_method_exists($current_controller,$current_event) || 
		(system_method_exists($current_controller,'__method_exists') && $current_controller->__method_exists($current_event) ) )
	{
		$content = system_invoke_request($current_controller,$current_event,HOOK_PRE_EXECUTE);
	}
	
	execute_hooks(HOOK_POST_EXECUTE);
	set_time_limit(ini_get('max_execution_time'));
	if( !isset($content) || !$content )
		$content = $current_controller;

	if( system_is_ajax_call() )
	{
		if( $content instanceof AjaxResponse )
			$response = $content->Render();
		elseif( $content instanceof Renderable )
			$response = AjaxResponse::Renderable($content)->Render();
		else
			throw new WdfException("Unknown AJAX return value");
	}
	elseif( $content instanceof AjaxResponse ) // is system_is_ajax_call() failed to detect AJAX but response in fact IS for AJAX
		die("__SESSION_TIMEOUT__");
	else
	{
		$_SESSION['request_id'] = request_id();
		if( $content instanceof Renderable)
		{
			$response = $content->WdfRenderAsRoot();
			if( $content->_translate && system_is_module_loaded("translation") )
				$response = __translate($response);
		}
		elseif( system_is_module_loaded("translation") )
			$response = __translate($content);
	}

	model_store();
	session_update();
	execute_hooks(HOOK_PRE_FINISH,array($response));

	echo $response;
}

/**
 * Executes the given request.
 * Will parse the target class/method for required parameters
 * and prepare the data given in the $_REQUEST variable to match them.
 * @param string $target_class Name of the class
 * @param string $target_event Name of the method
 * @param int $pre_execute_hook_type Type of Hook to be executed pre call
 * @return mixed The result of the target-method
 */
function system_invoke_request($target_class,$target_event,$pre_execute_hook_type)
{
	$ref = System_Reflector::GetInstance($target_class);
	$params = $ref->GetMethodAttributes($target_event,"RequestParam");
	$args = array();
	$argscheck = array();
	$failedargs = array();

	$req_data = array_merge($_GET,$_POST);
	foreach( $params as $prm )
	{
		$argscheck[$prm->Name] = $prm->UpdateArgs($req_data,$args);
		if( $argscheck[$prm->Name] !== true )
		{
//			log_debug("system.php -> argscheck for [$prm->Name] failed!");
			$failedargs[$prm->Name] = "ARGUMENT FAILED";
			$args[$prm->Name] = "ARGUMENT FAILED";
		}
	}

	if( count($failedargs) > 0 )
		execute_hooks(HOOK_ARGUMENTS_PARSED, $failedargs);

	execute_hooks($pre_execute_hook_type,array($target_class,$target_event,$args));
	return call_user_func_array(array(&$target_class,$target_event), $args);
}

/**
 * Terminats the current run.
 * Will be called from exception and error handlers.
 * @param string $reason
 */
function system_die($reason,$additional_message='')
{
	if( $reason instanceof Exception )
	{		
		$stacktrace = $reason->getTrace();
		$reason = logging_render_var($reason);
	}

	if( !isset($stacktrace) )
		$stacktrace = debug_backtrace();

	if( isset($GLOBALS['system']['hooks'][HOOK_SYSTEM_DIE]) && count($GLOBALS['system']['hooks'][HOOK_SYSTEM_DIE]) > 0 )
	{
		execute_hooks(HOOK_SYSTEM_DIE,array(
			$reason,
			$stacktrace
		));
	}

    if( system_is_ajax_call() )
	{
		$res = AjaxResponse::Error($reason."\n".$additional_message,true);
		die($res->Render());
		$code = "alert(unescape(".json_encode($reason."\n".$additional_message)."));";
		$res = new stdClass();
		$res->html = "<script>$code</script>";
		die(system_to_json($res));
	}
	else
	{
		$stacktrace = system_stacktrace_to_string($stacktrace);
		$res  = "<html><head><title>Fatal system error</title></head>";
		$res .= "<body>";
		if(isDev())
			$res .= "<pre>$reason</pre><pre>$additional_message</pre><pre>".$stacktrace."</pre>";
		else
			$res .= "Fatal System Error occured.<br/>Please try again.<br/>Contact our technical support if this problem occurs again.<br/><br/>Apologies for any inconveniences this may have caused you.";
		$res .= "</body></html>";
        die($res);
	}
}

/**
 * Registers a function to be executed on a system hook.
 * @param int $type See lines 10-22
 * @param string $handler_method name of function to call
 */
function register_hook_function($type,$handler_method)
{
	$dummy = false;
	register_hook($type,$dummy,$handler_method);
}

/**
 * Registers a method to be executed on a system hook.
 * @param int $type See lines 10-22
 * @param object $handler_object The object containig the handler method
 * @param string $handler_method name of method to call
 */
function register_hook($type,&$handler_obj,$handler_method)
{
	if( !isset($GLOBALS['system']['hooks'][$type]) )
		$GLOBALS['system']['hooks'][$type] = array();

	is_valid_hook_type($type);
	$GLOBALS['system']['hooks'][$type][] = array(
		$handler_obj, $handler_method
	);
}

/**
 * Executes a system hook (calls all registered handlers).
 * @param int $type See lines 10-22
 * @param array $arguments to be passed to the handler functions/methods
 */
function execute_hooks($type,$arguments = array())
{
	global $CONFIG;

	$GLOBALS['system']['hooks']['fired'][$type] = $type;
	if( !isset($GLOBALS['system']['hooks'][$type]) )
		return;

	is_valid_hook_type($type);

	$loghooks = ( $CONFIG['system']['hook_logging']); // && function_exists('dump') );
	
	if( $loghooks )
		log_debug("BEGIN ".hook_type_to_string($type));
	
	// note: as hooks may be added to the chain do not remove the count(...) here: it may grow!
	for($i=0; $i<count($GLOBALS['system']['hooks'][$type]); $i++)
	{
		$hook = $GLOBALS['system']['hooks'][$type][$i];
		if( is_object($hook[0]) )
		{
			if( $loghooks )
				log_debug( "Executing ".get_class($hook[0])."->".$hook[1]."(...)",hook_type_to_string($type) );
			$res = $hook[0]->$hook[1]($arguments);
			if( $loghooks )
				log_debug( "result:",$res);
		}
		else
		{
			if( $loghooks )
				log_debug( "Executing '".$hook[1]."(...)'",hook_type_to_string($type) );
			$res = $hook[1]($arguments);
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
 * @param int $type
 * @return bool true if valid
 */
function is_valid_hook_type($type)
{
	if( $type == HOOK_POST_INIT || $type == HOOK_POST_INITSESSION ||
	    $type == HOOK_PRE_EXECUTE || $type == HOOK_POST_EXECUTE ||
		$type == HOOK_PRE_FINISH || $type == HOOK_POST_MODULE_INIT ||
		$type == HOOK_PING_RECIEVED || $type == HOOK_SYSTEM_DIE || $type == HOOK_PRE_RENDER ||
		$type == HOOK_ARGUMENTS_PARSED
		)
		return true;

	throw new WdfException("Invalid hook type ($type)!");
}

/**
 * Returns the string representation of an int hook type.
 * @param int $type
 * @return Type as string
 */
function hook_type_to_string($type)
{
	switch( $type )
	{
		case HOOK_POST_INIT: return 'HOOK_POST_INIT';
		case HOOK_POST_INITSESSION: return 'HOOK_POST_INITSESSION';
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
 * @param int $type Hook Type
 * @return bool true|false 
 */
function hook_already_fired($type)
{
	if( isset($GLOBALS['system']['hooks']['fired']) && isset($GLOBALS['system']['hooks']['fired'][$type]) )
		return true;
	return false;
}

/**
 * Checks if there is a handler bound to a HOOK
 * @param int $type Hook Type
 * @return bool true|false
 */
function hook_bound($type)
{
	return isset($GLOBALS['system']['hooks'][$type]) && count($GLOBALS['system']['hooks'][$type]) > 0;
}

/**
 * Returns a string representation of the given stacktrace
 * @param array $stacktrace Use debug_backtrace() to get this
 * @return string The stacktrace-string
 */
function system_stacktrace_to_string($stacktrace,$crlf="\n")
{
	$stack = array();

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
		else
			$stack[] = sprintf("+ %s(...)",$function);
	}
	return implode($crlf,$stack);
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
 * See http://www.php.net/manual/de/function.spl-autoload-register.php
 */
function system_spl_autoload($class_name)
{
	if(($class_name == "") || ($class_name{0} == "<"))
		return;  // it's html
    try
    {
        $file = __search_file_for_class($class_name);
        if( $file && is_readable($file) )
            require_once($file);
    } 
    catch(Exception $ex)
    { error_log("system_spl_autoload: ".$ex->getMessage()); };
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
        $key = "autoload_template-".getAppVersion('nc').$template_name;
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

    $key = "autoload_template_class-".$class;
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
 * @param <type> $class_name
 * @param <type> $extension
 * @param <type> $classpath_limit
 * @return <type>
 */
function __search_file_for_class($class_name,$extension="class.php",$classpath_limit=false)
{
	global $CONFIG;

    $key = "autoload_class-".getAppVersion('nc').$class_name.$extension.$classpath_limit;
    $r = cache_get($key);
    if( $r !== false )
        return $r;
    
	$class_name_lc = strtolower($class_name);

	$short_class_name = "";
	if( strpos($class_name,"_") !== false )
	{
		$short_class_name = explode("_",$class_name);
		$short_class_name = $short_class_name[count($short_class_name)-1];
		$short_class_name_lc = strtolower($short_class_name);
	}

	foreach( $CONFIG['class_path']['order'] as $cp_part )
	{
		if( !isset($CONFIG['class_path'][$cp_part]))
			throw new WdfException("Invalid ClassPath! No entry for '$cp_part'.");

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
 * Returns all property names of the given object
 * @param object $obj Object to check
 * @return array An array of property names
 */
function system_get_dynamic_props($obj)
{
	$fields = system_get_fields(get_class($obj));
	$vars = array_keys(get_object_vars($obj));
	return array_diff($fields,$vars);
}

/**
 * Returns an array containig all field-names of the given
 * class. Will return fields of all subclasses too.
 * @param string $classname Name of the class to check
 * @return array All field names
 */
function system_get_fields($classname)
{
	$res = array_keys(get_class_vars($classname));
	$parent = get_parent_class($classname);
	if( $parent != "" )
		$res = array_merge($res,system_get_fields($parent));
	return $res;
}

/**
 * Builds a request
 * @param string $controller The page to be loaded
 * @param string $event The event to be executed
 * @param array|string $data Optional data to be passed
 * @return string A complete Request (for use as HREF)
 */
function buildQuery($controller,$event="",$data="", $url_root=false)
{
	global $CONFIG;

	if( $controller instanceof Renderable )
		$controller = $controller->_storage_id;
		
    if(substr($controller, 0, 4) == "http")
        return $controller;

	if($controller != "")
		$res = "$controller/";
	else
		$res = "";
	if( $event != "" )
	{
		$res .= $event;
		if( '#' != substr($event, 0, 1) )
				$res .= '/';			
	}
	$p = "?";
	
	if( is_array($data) )
	{
		if( isset($data['page']) ) unset($data['page']);
		if( isset($data['event']) ) unset($data['event']);
		$res .= $p.http_build_query($data);
	}
	else if( $data != "" )
	{
		$res .= "$p$data";
		$p = "&";
	}
	if(isDev() && isset($_REQUEST["XDEBUG_PROFILE"]))
        $res .= $p."XDEBUG_PROFILE";

	if( !$url_root )
		$url_root = $CONFIG['system']['url_root'];
	return $url_root.$res;
}

/**
 * Builds a query for the current page.
 * @param string|array $data Additional data
 * @return string A complete Request (for use as HREF)
 */
function samePage($data="")
{
	return buildQuery(current_controller(),current_event(),$data);
}

/**
 * Executed a header redirect to another page.
 * Will terminate the current processing silently!
 * @param string $controller The page to be called
 * @param string $event The event to be executed
 * @param array|string $data Optional data to be passed
 */
function redirect($controller,$event="",$data="",$url_root=false)
{
	if( is_array($controller) )
	{
		$url = array();
		foreach( $controller as $key=>&$val )
			$url[] = "$key=$val";
		$url = '?'.implode("&",$url);
	}
	else
		$url = buildQuery($controller,$event,$data,$url_root);

	header("Location: ".$url);
	exit;
}

/**
 * generates random string in the given length. can be used as password, sessionid or ticket
 * @param <int> $len the length of the return string. default = 8
 * @return <string> the generated string sequence
 */
function generatePW($len = 8)
{
	$chars  = "abcdefghijklmnopqrstuvwxyz";
	$chars .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$chars .= "0123456789";
	$res = "";
    mt_srand ((double) microtime() * 1000000);
	while( strlen($res) < $len )
		$res .= $chars[mt_rand(0,strlen($chars)-1)];

	return $res;
}

/**
 * Appends a version parameter to a link. This is useful to
 * avoid browser-side CSS and JS caching.
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
 * If $part is given (and set in data) will only return this value.
 * @param string $part Name of URL parameter to get
 * @return string|array Value of URL parameter $part if given, else array of all URL parameters
 */
function referrer($part='')
{
	$ref = explode("?",$_SERVER['HTTP_REFERER']);
	$res = array();
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
 * @param string $host_or_ip Hostname or IP to be checked
 * @return bool true|false
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
 * Finds all objects of a given classname in the given content.
 * @todo: this one with all it's recursions kills performance massively!
 * @param array Content to search in
 * @param string Classname to find
 * @param array Found objects
 */
function system_find(&$content,$classname,&$result = array(),$recursion=0, $stack=array())
{
	if($recursion > 10)
		return true;
	if( is_object($content) )
	{
		if(isset($content->_storage_id))
		{
			if(isset($stack[$content->_storage_id]))
				return true;
			$stack[$content->_storage_id] = $content->_storage_id;
		}
		if( get_class($content) == $classname || is_subclass_of($content, $classname) )
            $result[] = $content;
		$ov = get_object_vars($content);
		foreach( $ov as $p=>&$val )
		{
			if(system_find($content->$p,$classname,$result,$recursion+1,$stack))
				return true;
		}
	}
    elseif( is_array($content) )
    {
        foreach( $content as &$c )
            if(system_find($c,$classname,$result,$recursion+1,$stack))
				return true;
    }
	return false;
}

function cache_get($key,$default=false,$use_global_cache=true,$use_session_cache=true)
{
	if( $use_session_cache && isset($_SESSION["system_internal_cache"][$key]) )
		return session_unserialize($_SESSION["system_internal_cache"][$key]);
    
	if( $use_global_cache && system_is_module_loaded('globalcache') )
    {
        $res = globalcache_get($key,$default);
        if( $use_session_cache && $res !== $default )
            $_SESSION["system_internal_cache"][$key] = session_serialize($res);
		return $res;
    }
    return $default;
}

/**
 * Stores a string value into the internal cache.
 * @param string $key a key for the value
 * @param string $value the value to store
 * @param int $ttl Time to life in seconds. -1 if it shall live forever
 */
function cache_set($key,$value,$ttl=false,$use_global_cache=true,$use_session_cache=true)
{
	global $CONFIG;
	if( $ttl === false )
		$ttl = $CONFIG['system']['cache_ttl'];

	if( $use_global_cache && system_is_module_loaded('globalcache') )
		globalcache_set($key, $value, $ttl);

	if( $use_session_cache )
		$_SESSION["system_internal_cache"][$key] = session_serialize($value);
}

function cache_del($key)
{
	if( isset($_SESSION["system_internal_cache"][$key]) )
		unset($_SESSION["system_internal_cache"][$key]);
	if( system_is_module_loaded('globalcache') )
		globalcache_delete($key);
}

function cache_clear($global_cache=true, $session_cache=true)
{
	if( $session_cache )
		$_SESSION["system_internal_cache"] = array();
    if( $global_cache && system_is_module_loaded('globalcache') )
		globalcache_clear();
}

function cache_list_keys($global_cache=true, $session_cache=true)
{
	$res = $session_cache?array_keys($_SESSION["system_internal_cache"]):array();
	
	if( $global_cache && system_is_module_loaded('globalcache') )
		$res = array_merge($res, globalcache_list_keys() );
	
	sort($res);
	return array_unique($res);
}

function current_controller($as_string=true)
{
	if( !isset($GLOBALS['current_controller']) )
		return $as_string?'':null;
	if( $as_string )
		return strtolower(is_object($GLOBALS['current_controller'])?get_class($GLOBALS['current_controller']):$GLOBALS['current_controller']);
	return $GLOBALS['current_controller'];
}

function current_event()
{
	return isset($GLOBALS['current_event'])?strtolower($GLOBALS['current_event']):'';
}

/**
 * Returns the value of a given class constant.
 * Will check against name match and will use endswith to try to find
 * names without prefix.
 * Check is case insensitive!
 * @param string $class_name_or_object name of the class or object containing the constant
 * @param string $constant_name name of the constant to get
 * @return mixed value of the found constant or NULL
 */
function constant_from_name($class_name_or_object,$constant_name)
{
	$ref = System_Reflector::GetInstance($class_name_or_object);
	$constant_name = strtolower($constant_name);
	foreach( $ref->getConstants() as $name=>$value )
		if( strtolower($name) == $constant_name || ends_with(strtolower($name), $constant_name) )
			return $value;
	return null;
}

/**
 * Returns the name of a given class constant.
 * Will check all constant values and return the first match.
 * @param string $class_name name of the class containing the constant
 * @param mixed $constant_value value of the constant to get
 * @return string name of the found constant or NULL
 */
function name_from_constant($class_name,$constant_value,$prefix=false)
{
	$ref = System_Reflector::GetInstance($class_name);
	foreach( $ref->getConstants() as $name=>$value )
		if( $value == $constant_value && (!$prefix || starts_with($name, $prefix)) )
			return $name;
	return null;
}

/**
 * Wrapper for json_encode that ensures JS functions are not quoted.
 * Will detect code that starts with '[jscode]' or 'function('
 * Example:
 * array(
 *		'test1'=>"function(){alert('1');}",   // <- works
 *		'test2'=>"[jscode]SomeFunctionName",  // <- SomeFunctionName must be defined in code
 *		'test3'=>"[jscode]alert('1')"         // <- wont work because it is a call!
 * )
 * will generate
 * {"test1":function(){alert('1');}, "test2":SomeFunctionName, "test3": alert('1')} // <- syntax error due to test3
 * Note: Make sure your 'embedded' JS code does NOT end with a semicolon (;)!
 */
function system_to_json($value)
{
	$res = json_encode($value);
	$res = preg_replace_callback('/\"\[jscode\](.*)\"([,\]\}])/U',
		create_function(
            // single quotes are essential here,
            // or alternative escape all $ as \$
            '$m',
            'return stripcslashes($m[1]).$m[2];'
        ), $res );
	$res = preg_replace_callback('/\"function\(.*[^\\\\]\"/U',
		create_function(
            // single quotes are essential here,
            // or alternative escape all $ as \$
            '$m',
            'return json_decode($m[0]);'
        ), $res );
	return $res;
}

$time_start = microtime(true);
$debug_track_events = array();
function system_track_event($evtname)
{
	global $debug_track_events;
	$debug_track_events[$evtname] = microtime(true);
}

function system_print_tracking_times($printit = true)
{
	global $time_start, $debug_track_events;
	$time_end = microtime(true);

	$ret = "url: ".$_SERVER["REQUEST_URI"]."\r\n";
	$ret .= "Script start: "._format_timestamp($time_start)."\r\n";
	$prev = $time_start;
	foreach($debug_track_events as $evt => $time)
	{
		$ret .= "	$evt: ".sprintf('%01.4f Seconds', $time - $prev)."\r\n";
		$prev = $time;
	}
	$ret .= "Script end: "._format_timestamp($time_end)." (".sprintf('%01.4f Seconds', $time_end - $prev).")\r\n";
	$ret .= "Overall: ".sprintf('%01.4f Seconds', $time_end - $time_start)."\r\n";
	
	if($printit)
		echo $ret;
	return $ret;
}

function _format_timestamp($microtime)
{
	$timestamp = floor($microtime);
    $milliseconds = substr(round(($microtime - $timestamp) * 1000000), 2);
	return date('Y-m-d H:i:s.').$milliseconds;
//	return date('i:s.').$milliseconds;
}

function tail_file($file, $num_to_get=10)
{
	$fp = fopen($file, 'r');
	$position = filesize($file);
	$chunklen = 4096;
	if( $position-$chunklen <= 0 )
		fseek($fp,0);
	else
		fseek($fp, $position-$chunklen);
	$data="";$ret="";$lc=0;
	while($chunklen > 0)
	{
		$data = fread($fp, $chunklen);
		$dl=strlen($data);
		for($i=$dl-1;$i>=0;$i--)
		{
			if($data[$i]=="\n")
			{
				if($lc==0 && $ret!="")$lc++;
				$lc++;
				if($lc>$num_to_get)return $ret;
			}
			$ret=$data[$i].$ret;
		}
		if($position-$chunklen <= 0 )
		{
			fseek($fp,0);
			$chunklen = $chunklen - abs($position-$chunklen);
		}
		else
			fseek($fp, $position-$chunklen);
		$position = $position - $chunklen;
	}
	fclose($fp);
	return $ret;
}

function array_move_to_top(&$array,$value)
{
	$index = array_search($value,$array);
	if( $index === false )
		return;

	for($i=$index; $i>0; $i--)
		$array[$i] = $array[$i-1];
	$array[0] = $value;
}

/**
 * call_user_func_array does not allow byref arguments since 5.3 anymore
 * so we wrap this in our own funtion. this is even faster then call_user_func_array
 */
function system_call_user_func_array_byref(&$object, $funcname, &$args)
{
	switch(count($args)) 
	{
		case 0: 
			return $object->{$funcname}(); 
			break;
		case 1: 
			return $object->{$funcname}($args[0]); 
			break;
		case 2: 
			return $object->{$funcname}($args[0], $args[1]); 
			break;
		case 3: 
			return $object->{$funcname}($args[0], $args[1], $args[2]); 
			break;
		case 4: 
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3]); 
			break;
		case 5: 
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3], $args[4]); 
			break;
		case 6: 
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]); 
			break;
		case 7: 
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]); 
			break;
		case 8: 
			return $object->{$funcname}($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]); 
			break;
		default: 
			return call_user_func_array(array($object, $funcname), $args);  
			break;
	}
}

function system_method_exists($object_or_classname,$method_name)
{
	$key = (is_string($object_or_classname)?$object_or_classname:get_class($object_or_classname)).'.'.$method_name;
	$ret = cache_get("method_exists_$key");
	if( $ret !== false )
		return $ret=="1";
	$ret = method_exists($object_or_classname,$method_name);
	cache_set("method_exists_$key",$ret?"1":"0");
	return $ret;
}

/**
 * Shuffle an array and preserve key=>value binding
 * http://www.php.net/manual/en/function.shuffle.php#94697
 */
function shuffle_assoc(&$array)
{
	$keys = array_keys($array);
	shuffle($keys);
	foreach($keys as $key)
		$new[$key] = $array[$key];
	$array = $new;
}

function system_render_object_tree($array_of_objects)
{
	$res = array();
	foreach( $array_of_objects as $key=>&$val )
	{
		if( $val instanceof Renderable )
			$res[$key] = $val->WdfRender();
		elseif( is_array($val) )
			$res[$key] = system_render_object_tree($val);
		elseif( $val instanceof DateTime )
			$res[$key] = $val->format("Y-m-d H:i:s");
		else
			$res[$key] = $val;
	}
	return $res;
}