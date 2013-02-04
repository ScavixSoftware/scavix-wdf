<?php
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

require_once(__DIR__.'/session/serializer.class.php');
define("FW_SESSION_MODULE_LOADED",true);

function session_init()
{
	global $CONFIG;

	$CONFIG['class_path']['system'][]   = dirname(__FILE__).'/session/';
	$GLOBALS['object_storage'] = array();

	if( !isset($CONFIG['session']['session_name']) )
		$CONFIG['session']['session_name'] = 'FW_SESSION';

	if( !isset($CONFIG['session']['datasource']) )
		$CONFIG['session']['datasource'] = 'internal';

	if( !isset($CONFIG['session']['table']) )
		$CONFIG['session']['table'] = 'sessions';

	if( !isset($CONFIG['session']['prefix']) )
		$CONFIG['session']['prefix'] = '';

	if( !isset($CONFIG['session']['lifetime']) )
		$CONFIG['session']['lifetime'] = '10';

	// Deprecated! Use $CONFIG['session']['handler'] instead!
//	if( !isset($CONFIG['session']['usephpsession']) )
//		$CONFIG['session']['usephpsession'] = true;

	// Bind sessions to one ip address
	if( !isset($CONFIG['session']['iplock']))
		$CONFIG['session']['iplock'] = false;

	// Classname of the Session Handler
	if( !isset($CONFIG['session']['handler']))
		$CONFIG['session']['handler'] = 'PhpSession';
}

/**
 * Starts the session handler.
 * Will automatically be called by system_init()
 */
function session_run()
{
	global $CONFIG;
	// check for backwards compatibility
	if( isset($CONFIG['session']['usephpsession']))
	{
		if( ($CONFIG['session']['usephpsession'] && $CONFIG['session']['handler'] != "PhpSession") ||
			(!$CONFIG['session']['usephpsession'] && $CONFIG['session']['handler'] == "PhpSession") )
			throw new WdfException('Do not use $CONFIG[\'session\'][\'usephpsession\'] anymore! See session_init() for details.');
	}
	$GLOBALS['fw_session_handler'] = new $CONFIG['session']['handler']();

//	if( system_is_ajax_call() && isset($_SESSION['object_id_storage']) )
	if( isset($_SESSION['object_id_storage']) )
		$GLOBALS['object_ids'] = $_SESSION['object_id_storage'];
}

/**
 * Checks if the unserializer is doing something
 * @return bool true if running, else false
 */
function unserializer_active()
{
	return isset($GLOBALS['unserializing_level']) && $GLOBALS['unserializing_level'] > 0;
}

/**
 * Tests two objects for equality.
 * Checks reference-equality or storage_id equality (if storage_id is set)
 * @param object $o1 First object to compare
 * @param object $o2 Second object to compare
 * @return bool true if eual, else false
 */
function equals(&$o1, &$o2)
{
	if($o1 === $o2)
		return true;
	$iso1 = is_object($o1);
	$iso2 = is_object($o2);
	if(( !$iso1 && $iso2 ) || ( $iso1 && !$iso2 ))
		return false;
	if( !$iso1 && !$iso2 )
		return ($o1 === $o2);
	
	if( ($o1 instanceof Closure) || !($o2 instanceof Closure) )
		return false;
	if( !($o1 instanceof Closure) && ($o2 instanceof Closure) )
		return false;
	if( ($o1 instanceof Closure) && ($o2 instanceof Closure) && $o1==$o2 )
		return false;
	
	return (
		isset($o1->_storage_id) &&
		isset($o2->_storage_id) &&
		$o1->_storage_id == $o2->_storage_id
	);
}

//--------------------------------------------------------------------------------------
// All functions below this comment are shortcuts to the session handler object
// to ensure backwards compatibility.
// You may use the glogal $fw_session_handler to call these (or more) functions
// directly.
//--------------------------------------------------------------------------------------

function session_sanitize()
{
	return $GLOBALS['fw_session_handler']->Sanitize();
}

function session_kill_all()
{
	$GLOBALS['fw_session_handler']->KillAll();
}

function session_keep_alive($request_key='PING')
{
	return $GLOBALS['fw_session_handler']->KeepAlive($request_key);
}

function session_update()
{
	return $GLOBALS['fw_session_handler']->Update();
}

function request_id()
{
	return $GLOBALS['fw_session_handler']->RequestId();
}

function store_object(&$obj,$id="",$autoload=false)
{
	return $GLOBALS['fw_session_handler']->Store($obj,$id,$autoload);
}

function delete_object($id)
{
	return $GLOBALS['fw_session_handler']->Delete($id);
}

function in_object_storage($id)
{
	if( !isset($GLOBALS['fw_session_handler']) )
		return false;
	return $GLOBALS['fw_session_handler']->Exists($id);
}

function &restore_object($id)
{
	return $GLOBALS['fw_session_handler']->Restore($id);
}

function create_storage_id(&$obj)
{
	if( isset($GLOBALS['fw_session_handler']) && is_object($GLOBALS['fw_session_handler']) )
		return $GLOBALS['fw_session_handler']->CreateId($obj);
	return false;
}

function regenerate_session_id()
{
	return $GLOBALS['fw_session_handler']->RegenerateId();
}

function generate_session_id()
{
	return $GLOBALS['fw_session_handler']->GenerateSessionId();
}

function session_get_handler()
{
//	log_error("session.save_handler = ".ini_get("session.save_handler"));
	return ini_get("session.save_handler");
}

function session_get_path()
{
	return ini_get("session.save_path");
}

function session_use_memcache()
{
	return ((session_get_handler() == "memcache") || (session_get_handler() == "memcached"));
}

function session_memcache_host()
{
	$u = parse_url(session_save_path());
	if( !isset($u['host']) )
		throw new Exception("Invalid memcache server or not using memcache");
	return $u['host'];
}

function session_memcache_port()
{
	$u = parse_url(session_save_path());
	return isset($u['port'])?$u['port']:11211;
}

function session_serialize($value)
{
	$s = new Serializer();
	return $s->Serialize($value);
}

function session_unserialize($value)
{
	$s = new Serializer();
	$res = $s->Unserialize($value);
	return $res;
}

?>