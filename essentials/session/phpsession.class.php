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
 
/**
 * PHP session handling.
 * 
 * This is the default behaviour.
 */
class PhpSession extends SessionBase
{
	/**
	 * Implements parents abstract
	 * 
	 * See <SessionBase::Sanitize>
	 * @return void
	 */
	function Sanitize()
	{
		global $CONFIG;
		$lt = $CONFIG['session']['lifetime'];
		$prefix = $CONFIG['session']['prefix'];

		if(isset($_SESSION[$prefix."session_lastaccess"]) && ($_SESSION[$prefix."session_lastaccess"] < time() - $lt * 60))
		{
			// session timed out
			// Implementations in system/modules/authorization.php and
			// common/modules/fax_authorization.php
			if( function_exists('logoutUser') )
				logoutUser();
		}

		if( $CONFIG['session']['iplock'] )
		{
			$ip_address = get_ip_address();
			if(isset($_SESSION[$prefix.'ip_address']) && function_exists('logoutUser') && ($_SESSION[$prefix.'ip_address'] != $ip_address))
				logoutUser();
			$_SESSION[$prefix.'ip_address'] = $ip_address;
		}
	}

	/**
	 * Implements parents abstract
	 * 
	 * See <SessionBase::KillAll>
	 * @return void
	 */
	function KillAll()
	{
		global $CONFIG;
		unset($_SESSION[$CONFIG['session']['prefix']."session"]);
		session_destroy();
		session_start();
	}

	/**
	 * Implements parents abstract
	 * 
	 * See <SessionBase::KeepAlive>
	 * @param string $request_key Key in the $_REQUEST variable where the request_id is stored
	 * @return void
	 */
	function KeepAlive($request_key='PING')
	{
		global $CONFIG;
		$_SESSION[$CONFIG['session']['prefix']."session_lastaccess"] = time();
	}

	/**
	 * Implements parents abstract
	 * 
	 * See <SessionBase::Store>
	 * @param object $obj Object to be stored
	 * @param string $id Id to store $obj to
	 * @param bool $autoload If true, will be restored on session initialization automatically
	 * @return void
	 */
	function Store(&$obj,$id="",$autoload=false)
	{
		global $CONFIG;
		$id = strtolower($id);
		if( $id == "" )
		{
			if( !isset($obj->_storage_id) )
				WdfException::Raise("Trying to store an object without storage_id!");
			$id = $obj->_storage_id;
		}
		else
			$obj->_storage_id = $id;
		$serializer = new Serializer();
		$content = $serializer->Serialize($obj);
		$_SESSION[$CONFIG['session']['prefix']."session"][$id] = $content;
		$GLOBALS['object_storage'][$id] = $obj;
	}

	/**
	 * Implements parents abstract
	 * 
	 * See <SessionBase::Delete>
	 * @param string $id Id of object to be deleted
	 * @return void
	 */
	function Delete($id)
	{
		global $CONFIG;
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
		$id = strtolower($id);
		if(isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]))
			unset($_SESSION[$CONFIG['session']['prefix']."session"][$id]);
		unset($GLOBALS['object_storage'][$id]);
	}

	/**
	 * Implements parents abstract
	 * 
	 * See <SessionBase::Exists>
	 * @param string $id Id of object to be checked
	 * @return bool true or false
	 */
	function Exists($id)
	{
		global $CONFIG;
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
		$id = strtolower($id);
		if( isset($GLOBALS['object_storage'][$id]) )
			return true;

		return isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]);
	}

	/**
	 * Implements parents abstract
	 * 
	 * See <SessionBase::Restore>
	 * @param string $id Id of object to be restored
	 * @return mixed Object from store or null
	 */
	function &Restore($id)
	{
		global $CONFIG;
		$id = strtolower($id);

		if( isset($GLOBALS['object_storage'][$id]) )
			return $GLOBALS['object_storage'][$id];

		if(!isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]))
		{
			$null = null;
			return $null;
		}
		$data = $_SESSION[$CONFIG['session']['prefix']."session"][$id];

		$serializer = new Serializer();
		$res = $serializer->Unserialize($data);
		$GLOBALS['object_storage'][$id] = $res;
		return $GLOBALS['object_storage'][$id];
	}
}

?>