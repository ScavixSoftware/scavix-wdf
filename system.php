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
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/table/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/controls/locale/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/jquery-ui/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/jquery-ui/dialog/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/jquery-ui/slider/';
	$CONFIG['class_path']['content'][] = __DIR__.'/lib/widgets/';
	
	$CONFIG['class_path']['order'] = array('system','model','content');

	$CONFIG['system']['path_root'] = __DIR__;

	$CONFIG['requestparam']['ignore_case'] = true;
	$CONFIG['requestparam']['tagstostrip'] = array('script');

	$CONFIG['model']['internal']['auto_create_tables'] = true;
	$CONFIG['model']['internal']['datasource_type']    = 'System_DataSource';	
	$CONFIG['model']['internal']['debug']			   = false;

	$CONFIG['system']['application_name'] = 'project';
	$CONFIG['system']['cache_datasource'] = 'internal';
	$CONFIG['system']['cache_ttl'] = 3600; // secs

	$CONFIG['system']['hook_logging'] = false;
	$CONFIG['system']['attach_session_to_ajax'] = false;
	
	$CONFIG['system']['header']['Content-Type'] = "text/html; charset=utf-8";
	$CONFIG['system']['header']['X-XSS-Protection'] = "1; mode=block";
	
    $path = explode("index.php",$_SERVER['PHP_SELF']);
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
	foreach( glob($thispath.'/essentials/*.php') as $essential )
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

/**
 * Executes the current request.
 * Reacts on _REQUEST['page'] for straight pages or _REQUEST['load'] for ajax calls
 */
function system_execute()
{
	global $CONFIG,$PAGE,$event;

	session_sanitize();
	execute_hooks(HOOK_POST_INITSESSION);

	// Cleanup URL params to avoid XSS (partially)
	if( is_array($CONFIG['requestparam']['tagstostrip']) && count($CONFIG['requestparam']['tagstostrip']) > 0 )
	{
		system_sanitize_parameters($_GET);
		system_sanitize_parameters($_POST);
		system_sanitize_parameters($_COOKIE);
		$GLOBALS['RAW_REQUEST'] = $_REQUEST;
		$_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
	}
	
	// if there's a handler bound to HOOK_PARSE_URI call it and let it prepare
	// page and event parameters
	if( hook_bound(HOOK_PARSE_URI) )
	{
		execute_hooks(HOOK_PARSE_URI);		
	}
	else
	{
		$pattern = "/[^A-Za-z0-9\-_]/";
		// getting query information
		$PAGE = (isset($_REQUEST['page'])&&$_REQUEST['page']!="")?$_REQUEST['page']:$CONFIG['system']['default_page'];
		$event = (isset($_REQUEST['event'])&&$_REQUEST['event']!="")?$_REQUEST['event']:$CONFIG['system']['default_event'];
        
        if( $PAGE === false )
            system_die("No default page defined!");
		// prevent XSS:
		$PAGE = substr(preg_replace($pattern, "", $PAGE), 0, 256);
		$event = substr(preg_replace($pattern, "", $event), 0, 256);
		if(isset($_REQUEST['event']) && ($_REQUEST['event'] != ""))
			$_REQUEST['event'] = $event;
	}
	$_REQUEST['page'] = $PAGE;

	execute_hooks(HOOK_PRE_PROCESSING, array($PAGE,$event));

	// respond to PING requests that are sended to keep the session alive
	if( isset($_REQUEST['PING']) )
	{
		session_keep_alive();
		$result = array();
		execute_hooks(HOOK_PING_RECIEVED,$result);
		if( count($result) == 0 )
			die("PONG");
		foreach( $result as $r )
			echo $r;
		die;
	}

	$isstrpage = is_string($PAGE);
	if( in_object_storage($PAGE) )
	{
		$PAGE_test = restore_object($PAGE);
		if( $PAGE_test instanceof IRenderable )
			$PAGE = $PAGE_test;

		$isstrpage = is_object($PAGE)?false:strtolower($PAGE) == 'internal';	// $PAGE is now an object
	}
	elseif( ($isstrpage && !class_exists($PAGE) && system_is_ajax_call())
			||
			($isstrpage && $PAGE==$CONFIG['system']['default_page'] && isset($_REQUEST['request_id']) && !isset($_SESSION['request_id'])))
	{
		die("__SESSION_TIMEOUT__");
	}

	if( $isstrpage )
	{
		if( !class_exists($PAGE) )
			die("Unknown page handler '$PAGE'");
		$PAGE = new $PAGE();
	}

	if( system_is_ajax_call() )
	{
		if( !($PAGE instanceof IRenderable) )
			system_die("ACCESS FORBIDDEN",get_class($PAGE)." is no IRenderable");
		if( $isstrpage && !($PAGE instanceof ICallable) ) 
			log_warn("AJAX call to IRenderable class: ".get_class($PAGE)."/$event",$_REQUEST);
	}
	else
	{
		if( !($PAGE instanceof ICallable) )
			system_die("ACCESS FORBIDDEN",get_class($PAGE)." is no ICallable");
	}	
	
	if( system_method_exists($PAGE,$event) || (system_method_exists($PAGE,'__method_exists') && $PAGE->__method_exists($event) ) )
	{
		$content = system_invoke_request($PAGE,$event,HOOK_PRE_EXECUTE);
	}
	execute_hooks(HOOK_POST_EXECUTE);
	set_time_limit(ini_get('max_execution_time'));
	if( !isset($content) || !$content )
		$content =& $PAGE;

	$dotranslate = (system_is_module_loaded("translation") || system_is_module_loaded("translation2"));
	if( system_is_ajax_call() )
	{
		if( $content instanceof JsResponse )
			$content = $content->Render();
		elseif( is_array($content) )
		{
			$res = new stdClass();
			foreach( $content as $k=>&$c )
			{
				if($dotranslate)
					$c = __translate($c);
				if($c instanceof ApiList)
					$c = $c->GetArray();
			}
			$res->html = $content;
			$content = system_to_json($res);
		}
		else
		{
			$res = new stdClass();
			if( $content instanceof IRenderable )
			{
				$dotranslate = $dotranslate && ( !isset($content->_translate) || $content->_translate );
						
				if( isset($CONFIG['use_compiled_js']) && isset($CONFIG['use_compiled_css']) )
					$content = $content->execute(false,true);
				else
				{
					$js = array();
					$css = array();
					system_collect_includes($content,$content,$js,$css);
					$js = array_unique(system_flatten_array($js));
					$css = array_unique(system_flatten_array($css));
					$content = $content->execute(false,true);
					if( !isset($CONFIG['use_compiled_js']) && count($js) > 0)
						$res->dep_js = $js;
					if( !isset($CONFIG['use_compiled_css']) && count($css) > 0)
						$res->dep_css = $css;
				}
			}
			if( starts_with($content, '[NT]') )
				$content = substr($content, 4);
			elseif( $dotranslate )
				$content = __translate($content);
			$res->html = str_replace("\\\\", "\\", $content);
			$content = system_to_json($res);
		}
	}
	else
	{
		$_SESSION['request_id'] = request_id();
		if( $content instanceof IRenderable)
		{
			$dotranslate = $content->_translate;
			$content = $content->execute();
		}
		if( $dotranslate && function_exists("__translate") )
			$content = __translate($content);
	}

	model_store();
	session_update();
	execute_hooks(HOOK_PRE_FINISH,array($content));

	echo $content;
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
	try{
//		$mi = $ref->getMethod($target_event);
//		$res = $mi->invokeArgs($target_class,$args);
//		log_debug("invoked ".get_class($target_class)."->$target_event(".implode(",",$args).") -> ".get_class($res));
//		return $res;
		return call_user_func_array(array(&$target_class,$target_event), $args);
	}
	catch(Exception $ex)
	{
        $r = log_start_report("Failed calling ".get_class($target_class).":$target_event");
        $r->add("Arguments: ", $args);
        $r->add("REQUEST: ", $req_data);
        $r->add("Arguments Checked: ", $argscheck);
        $r->add("Arguments: ", $args);
        log_report($r,'ERROR');
		system_die($ex);
	}
}

/**
 * Terminats the current run.
 * Will be called from exception and error handlers.
 * @param string $reason
 */
function system_die($reason,$additional_message=false)
{
	if( $reason instanceof Exception )
	{
		$code = $reason->getCode();
		$stacktrace = $reason->getTrace();

		$reason = $reason->getMessage();
		if( $code )
			$reason = "[$code] $reason";
	}

	log_fatal($reason,isset($stacktrace)?$stacktrace:null);
	$logged_reason = $reason;

	$lines = explode("\n",$reason);
	$reason = array();
	foreach( $lines as $line )
		$reason[] = trim($line);
	$reason = "<h1>".implode("\n",$reason)."</h1>";
	if( $additional_message )
		$reason .= "<p>$additional_message</p>";

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
		$code = "alert(unescape('".jsEscape($logged_reason."\n".$additional_message)."'));";
		$res = new stdClass();
		$res->html = "<script>$code</script>";
		die(system_to_json($res));
		
		$dlg = new uiDialog('system_error','Fatal System Error');
		if(isDev())
		{
			$dlg->addContent("<h2 style='text-align:left'>$reason</h2>");
			$dlg->addContent("<pre style='text-align:left'>".addslashes(system_stacktrace_to_string($stacktrace))."</pre>");
		}
		else
			$dlg->addContent("<h2 style='text-align:left'>Fatal System Error occured. Please restart your browser.</h2>");
		die("$('#system_error').remove();".$dlg->Execute());
	}
	else
	{
		$logfile  = '<div style="font-size: 12pt; font-weight: bold;">Logfile:</div>';

		$stacktrace = system_stacktrace_to_string($stacktrace);
		$res  = "<html><head><title>Fatal system error</title></head>";
		$res .= "<body>";
		if(isDev())
			$res .= "<pre>$reason</pre><pre>".$stacktrace."$logfile</pre>";
		else
			$res .= "Fatal System Error occured.<br/>Please try again.<br/>Contact our technical support if this problem occurs again.<br/><br/>Apologies for any inconveniences this may have caused you."; //: $reason";
		$res .= "</body></html>";
        echo($res);
        exit(0);
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

	$hkcnt = count($GLOBALS['system']['hooks'][$type]);
	$loghooks = ( $CONFIG['system']['hook_logging']); // && function_exists('dump') );
	for($i=0; $i<$hkcnt; $i++)
	{
		$hook = $GLOBALS['system']['hooks'][$type][$i];
		if( is_object($hook[0]) )
		{
			if( $loghooks )
				log_debug( "Executing (".get_class($hook[0]).")(".$hook[0].")->".$hook[1]."(...)",hook_type_to_string($type) );
			$res = $hook[0]->$hook[1]($arguments);
		}
		else
		{
			if( $loghooks )
				log_debug( "Executing '".$hook[1]."(...)'",hook_type_to_string($type) );
			$res = $hook[1]($arguments);
		}

		if( $res === false )
			break;
	}
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
		$type == HOOK_PING_RECIEVED ||
		$type == HOOK_AJAX_POST_LOADED || $type == HOOK_AJAX_PRE_EXECUTE ||
		$type == HOOK_AJAX_POST_EXECUTE || $type == HOOK_SYSTEM_DIE || $type == HOOK_PRE_RENDER ||
		$type == HOOK_PARSE_URI || $type == HOOK_PRE_PROCESSING || $type == HOOK_COOKIES_REQUIRED ||
		$type == HOOK_ARGUMENTS_PARSED
		)
		return true;

	system_die("Invalid hook type ($type)!");
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

		case HOOK_AJAX_POST_LOADED: return 'HOOK_AJAX_POST_LOADED';
		case HOOK_AJAX_PRE_EXECUTE: return 'HOOK_AJAX_PRE_EXECUTE';
		case HOOK_AJAX_POST_EXECUTE: return 'HOOK_AJAX_POST_EXECUTE';
		case HOOK_SYSTEM_DIE: return 'HOOK_SYSTEM_DIE';
		case HOOK_PRE_RENDER: return "HOOK_PRE_RENDER";
		case HOOK_PARSE_URI: return "HOOK_PARSE_URI";
		case HOOK_PRE_PROCESSING: return "HOOK_PRE_PROCESSING";
		case HOOK_COOKIES_REQUIRED: return 'HOOK_COOKIES_REQUIRED';
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
 * @param bool $include_file_excerpt include a file excerpt of the first stacktrace entry
 * @param string $crlf Line separator
 * @return string The stacktrace-string
 */
function system_stacktrace_to_string($stacktrace, $include_file_excerpt = true, $crlf = "\n")
{
	global $CONFIG;
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
	
	if( $include_file_excerpt && isset($CONFIG['error']['show_lines']) && $CONFIG['error']['show_lines'] == true )
		return implode($crlf,$stack).$crlf.system_get_file_excerpt($stacktrace);
	else
		return implode($crlf,$stack);
}

/**
 * Show Excerpt from File where Error has its origin
 *
 * @param mixed $stacktrace
 * @param int $index
 * @return string
 */
function system_get_file_excerpt($stacktrace,$index = 0, $for_html=true)
{
	$file = $stacktrace[$index]['file'];
	$line = $stacktrace[$index]['line'];
	$start_line = $line-6;

	if(file_exists($file))
		$arfile = file($file);
    else
        return;

	$error_lines = array();
	$error_lines = array_slice($arfile, $start_line, 11, true);
	if( $for_html )
		$file_excerpt = "	<div style='font-size:12pt; font-weight:bold;'>Excerpt: ".basename($file)."</div>
							<div style='background-color:#E8E8E8; padding-left:30px;'>";
	else
		$file_excerpt = "Excerpt: ".basename($file)."\n";

	foreach($error_lines as $key => $value)
	{
		$key++;

		$value = htmlspecialchars($value);

		if($key == $line && $for_html)
			$file_excerpt .= "<b style='background-color:red;'>".$key." - ".$value."</b>";
		else
			$file_excerpt .= $key." - ".$value;
	}
	if( $for_html )
		$file_excerpt.="</div>";
	return $file_excerpt;
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
//    log_error("autoload: ".$class_name);
	if(($class_name == "") || ($class_name{0} == "<"))
		return;  // it's html
    try
    {
        $file = __search_file_for_class($class_name);
//		log_error("autoload: $class_name file: $file");
        if( $file === false )
		{
            //log_error("Unable to find file for class $class_name");
			//log_error("ClassPath ".var_export($GLOBALS['CONFIG']['class_path'],true));
		}
        elseif( is_readable($file) )
            require_once($file);
    } 
    catch(Exception $ex)
    { error_log("system_spl_autoload: ".$ex->getMessage()); };
}
spl_autoload_register("system_spl_autoload",true,true);

/**
 * tries to load the template for the calling class
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

    $key = "search_file_for_class-".getAppVersion('nc').$class_name.$extension.$classpath_limit;
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
			system_die("Invalid ClassPath! No entry for '$cp_part'.");

		if( $classpath_limit && $cp_part != $classpath_limit )
			continue;

		foreach( $CONFIG['class_path'][$cp_part] as $path )
		{
//			log_debug("$path$class_name.$extension","CPS");
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
 * @param string $page The page to be loaded
 * @param string $event The event to be executed
 * @param array|string $data Optional data to be passed
 * @return string A complete Request (for use as HREF)
 */
function buildQuery($page,$event="",$data="", $url_root=false)
{
	global $CONFIG;

    if(substr($page, 0, 4) == "http")
        return $page;

	if( system_is_module_loaded('routing') )
	{
		if($page != "")
			$res = "$page/";
		else
			$res = "";
		if( $event != "" )
		{
			$res .= $event;
			if( '#' != substr($event, 0, 1) )
					$res .= '/';			
		}
		$p = "?";
	}
	else
	{
		$res = "?page=$page";
		if( $event != "" )
			$res .= "&event=$event";
		$p = "&";
	}
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
	global $CONFIG,$PAGE,$event;

	return buildQuery(current_page_class(),current_event(),$data);
}

/**
 * Executed a header redirect to another page.
 * Will terminate the current processing silently!
 * @param string $page The page to be called
 * @param string $event The event to be executed
 * @param array|string $data Optional data to be passed
 */
function redirect($page,$event="",$data="",$url_root=false)
{
	if( is_array($page) )
	{
		$url = array();
		foreach( $page as $key=>&$val )
			$url[] = "$key=$val";
		$url = '?'.implode("&",$url);
	}
	else
		$url = buildQuery($page,$event,$data,$url_root);

//	log_debug("redirect: $url");
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

function makerelative($realpath)
{
	global $CONFIG;
	$current_script = $_SERVER['SCRIPT_FILENAME'];

//log_debug("1: r: $realpath cs: $current_script");
	$current_script = explode("/",$current_script);
	$realpath = explode("/",$realpath);

	while( $current_script[0] == $realpath[0] )
	{
		$current_script = array_slice($current_script,1);
		$realpath = array_slice($realpath,1);
	}
	
	$current_script = implode("/",$current_script);
	$realpath = implode("/",$realpath);
//log_debug("2: r: $realpath cs: $current_script");
	if(substr($realpath, 1, 1) == "/")
		$realpath = str_repeat("../",count(explode("/",$current_script))+1) . $realpath;
    $realpath = str_replace("system/../", "", $realpath);
//log_debug("3: r: $realpath cs: $current_script");
    if( system_is_module_loaded('routing') )
    {
        // add some '..' when there's a 'virtual' URL called
        // ex.: http://server/Hallo/Welt will become http://server/index.php/Hallo/Welt due
        // to htaccess in dirname(index.php)
        $virtual = explode("index.php",$_SERVER['PHP_SELF']);
        if( count($virtual) > 0 )
        {
            $virtual = explode("/",trim($virtual[1],"/"));
            if( count($virtual) > 0 && !(count($virtual)==1 && $virtual[0]==""))
            {
                // add count() because root is currently index.php/
                //log_debug($realpath." -> ".str_repeat("../",count($virtual)).$realpath);
                $realpath = str_repeat("../",count($virtual)).$realpath;
            }
            //else
                //log_debug("skipping $realpath");
        }
    }
//	if( $CONFIG['system']['path_root'] != "" )
//		$realpath = str_replace($CONFIG['system']['path_root']."/",'',$realpath);
//log_debug("4: r: $realpath cs: $current_script");
    return $realpath;
}

function makerelativeuri($realpath)
{
	global $CONFIG;
    $realpath = makerelative($realpath);
    $realpath = str_replace($_SERVER['DOCUMENT_ROOT']."/",'',$realpath);
    $realpath = preg_replace('/\/([^\/]+)\/\.\.\//', '/', $realpath);
	return $realpath;
}

/**
 * Recursive implode.
 * Will implode the given pieces into a string and handle
 * multidimentional arrays too.
 * @param string $glue String to be used as 'connector'
 * @param array $pieces The pieces to be joined
 * @return string Resulting string
 */
function r_implode($glue,$pieces)
{
	foreach( $pieces as $index=>&$item )
		if( is_array($item) )
			$pieces[$index] = r_implode($glue,$item);

	return implode($glue,$pieces);
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

$sci_stack = array();
function system_collect_includes(&$controller,&$template,&$js_cache,&$css_cache,$pre = "")
{
	global $sci_stack;
	$isot = is_object($template);
	$isat = is_array($template);
	if( !$isot && !$isat )
		return;

	if( $pre == "" )
		$sci_stack = array();

	if( $isot )
	{
		foreach( $sci_stack as &$s )
			if( equals($s,$template) )
			{
				$classname = strtolower($isot?get_class($template):(string)$template);
//				log_debug($pre."BREAK system_collect_includes(".get_class($controller).",$classname,...) id=".$template->id);
				return;
			}
		$sci_stack[] = $template;
	}
	
	$classname = strtolower($isot?get_class($template):(string)$template);
//	log_debug("system_collect_includes(".get_class($controller).",$classname,...)");

	if( $isot && system_method_exists($template,'PreparePage') )
		$template->PreparePage($controller);

	if($isot && !$isat)
	{
		system_include_statics($classname,'__js',$js_cache);
		system_include_statics($classname,'__css',$css_cache);
	}

	if( $isot && $template instanceof IRenderable )//is_subclass_of($template,"Template") )
	{
		system_include_files($classname,$controller,$js_cache,$css_cache);
		$parent = strtolower(get_parent_class($template));
		while($parent != "" && $parent != "template" && $parent != "control" && $parent != "controlextender")
		{
			system_include_files($parent,$controller,$js_cache,$css_cache);
			$parent = strtolower(get_parent_class($parent));
		}
	}

	if( $isot && isset($template->vars) && is_array($template->vars) && count($template->vars)>0 )
	{
//		log_debug($pre."IsTemplate (".get_class($template).") id=".$template->id);
		foreach( $template->vars as $varname=>$var )
		{
//			log_debug($pre."->$varname...");
			system_collect_includes($controller,$var,$js_cache,$css_cache,"\t$pre");
		}
//		log_debug($pre."<<<IsTemplate(".get_class($template).")");
	}

	if( $isot && $template instanceof Control )
	{
//		log_debug($pre."IsControl (".get_class($template).") id=".$template->id);
		$vars = get_object_vars($template);
		foreach( $vars as $name=>&$var )
		{
//			log_debug($pre."->$name...");
			system_collect_includes($controller,$var,$js_cache,$css_cache,"\t$pre");
		}
//		log_debug($pre."<<<IsControl(".get_class($template).")");
	}

	if( $isot && isset($template->_extender) && is_array($template->_extender) && count($template->_extender) > 0 )
	{
//		log_debug($pre."IsExtender (".get_class($template).") id=".$template->id);
		foreach( $template->_extender as $varname=>$var )
		{
//			log_debug($pre."->$varname...");
			system_collect_includes($controller,$var,$js_cache,$css_cache,"\t$pre");
		}
//		log_debug($pre."<<<IsExtender(".get_class($template).")");
	}

	if( $isat && count($template) > 0 )
	{
//		log_debug($pre."IsArray");
		foreach( $template as $key=>$v )
		{
//			log_debug($pre."->$key...");
			system_collect_includes($controller,$v,$js_cache,$css_cache,"\t$pre");
		}
//		log_debug($pre."<<<IsArray");
	}
}

function system_include_files($classname,&$controller,&$js_cache,&$css_cache)
{
	if( system_is_module_loaded("skins") && skinFileExists("$classname.css") )
	{
		$css_cache[] = skinFile("$classname.css");
	}
	if( system_is_module_loaded("javascript") && jsFileExists("$classname.js") )
	{
		$js_cache[] = jsFile("$classname.js");
	}
}

/**
 * Will collect all static data for a given classname and method into a (also given) cache.
 * Will check all parent classes too!
 * @param string $classname The classname to be checked
 * @param string $method The name of the static method
 * @param array $cache The cache to be used
 */
function system_include_statics($classname,$method,&$cache)
{
	if( ($classname == "array") || isset($cache[$classname]) || !class_exists($classname) )
		return;

	$ref = new ReflectionClass($classname);
	if( $ref->hasMethod($method) )
	{
		$meth = $ref->getMethod($method);
		$ref = $meth->getDeclaringClass();
		$classname = strtolower($ref->getName());
        
        if( isset($cache[$classname]) )
            return;
        $cache[$classname] = $meth->invoke(null);

		$ref = $ref->getParentClass();
		if( $ref )
			system_include_statics(strtolower($ref->getName()),$method,$cache);
	}
}

/**
 * Converts a multidimensional array to a single dimensional one.
 * Duplicated keys will get the value of the last accessible.
 * @param array $ar Array to be flattened
 * @return array The resulting array.
 */
function system_flatten_array(array $ar)
{
	$ret_array = array();
	foreach(new RecursiveIteratorIterator(new RecursiveArrayIterator($ar)) as $value)
		$ret_array[] = $value;
	return $ret_array;
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

/**
 * Strips given tags from array (GET, POST, REQUEST)
 * @see http://www.php.net/manual/en/function.strip-tags.php#93567
 * @param array $param Parameter array to strip
 */
function system_sanitize_parameters(&$params)
{
	global $CONFIG;
	$tags = $CONFIG['requestparam']['tagstostrip'];
    if(!is_array($tags))
	{
        $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
        if(end($tags) == '') array_pop($tags);
    }

	$size = sizeof($tags);
	$keys = array_keys($tags);
	$paramsize = sizeof($params);
	$paramkeys = array_keys($params);

	for ($j=0; $j<$paramsize; $j++)
	{
		for ($i=0; $i<$size; $i++)
		{
			$tag = $tags[$keys[$i]];
			if(is_string($params[$paramkeys[$j]]))
			{
				if(stripos($params[$paramkeys[$j]], $tag) !== false)
					$params[$paramkeys[$j]] = preg_replace('#</?'.$tag.'[^>]*>#is', '', $params[$paramkeys[$j]]);
			}
			elseif(is_array($params[$paramkeys[$j]]))
				system_sanitize_parameters ($params[$paramkeys[$j]]);
		}
	}
}

function cache_get($key,$default=false,$use_global_cache=true)
{
	if( isset($_SESSION["system_internal_cache"][$key]) )
		return $_SESSION["system_internal_cache"][$key];
    
	if( $use_global_cache && system_is_module_loaded('globalcache') )
    {
        $res = globalcache_get($key,$default);
        if( $res !== $default )
            $_SESSION["system_internal_cache"][$key] = $res;
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
function cache_set($key,$value,$ttl=false,$use_global_cache=true)
{
	global $CONFIG;
	if( $ttl === false )
		$ttl = $CONFIG['system']['cache_ttl'];

	if( $use_global_cache && system_is_module_loaded('globalcache') )
		globalcache_set($key, $value, $ttl);

	$_SESSION["system_internal_cache"][$key] = $value;
}

function cache_clear($global_cache_too=true)
{
    $_SESSION["system_internal_cache"] = array();
    if( $global_cache_too && system_is_module_loaded('globalcache') )
		globalcache_clear();
}

function current_page( $strtolower=false )
{
	if(!isset($_REQUEST['page']))
		return false;
	
	if( $strtolower )
		return strtolower($_REQUEST['page']);
	
	return $_REQUEST['page'];
}

function current_page_class()
{
	$cp = current_page();
	return is_object($cp)?get_class($cp):$cp;
}

function current_event( $strtolower=false )
{
	global $event;
	if( $strtolower )
		return strtolower($event);

	return $event;
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
	//$res = preg_replace('/\"\[jscode\](.*)\"([,\]\}])/U', '$1$2', $res );
	$res = preg_replace_callback('/\"\[jscode\](.*)\"([,\]\}])/U',
		create_function(
            // single quotes are essential here,
            // or alternative escape all $ as \$
            '$m',
            'return stripcslashes($m[1]).$m[2];'
        ), $res );
	$res = preg_replace_callback('/\"(function\()(.*)\"([,\]\}])/U',
		create_function(
            // single quotes are essential here,
            // or alternative escape all $ as \$
            '$m',
            'return $m[1].stripcslashes($m[2]).$m[3];'
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
	if( $ret != false )
		return $ret=="1";
	$ret = method_exists($object_or_classname,$method_name);
	cache_set("method_exists_$key",$ret?"1":"0");
	return $ret;
}

?>