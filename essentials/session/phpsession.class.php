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
namespace ScavixWDF\Session;

use ScavixWDF\WdfException;

/**
 * PHP session handling.
 * 
 * This is the default behaviour.
 */
class PhpSession extends SessionBase
{
	/**
	 * @implements <SessionBase::Sanitize>
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
				call_user_func('logoutUser');
		}

		if( $CONFIG['session']['iplock'] )
		{
			$ip_address = get_ip_address();
			if(isset($_SESSION[$prefix.'ip_address']) && function_exists('logoutUser') && ($_SESSION[$prefix.'ip_address'] != $ip_address))
				call_user_func('logoutUser');
			$_SESSION[$prefix.'ip_address'] = $ip_address;
		}
	}

	/**
	 * @implements <SessionBase::KillAll>
	 */
	function KillAll()
	{
		global $CONFIG;
		unset($_SESSION[$CONFIG['session']['prefix']."session"]);
		session_destroy();
		session_start();
	}

	/**
	 * @implements <SessionBase::KeepAlive>
	 */
	function KeepAlive($request_key='PING')
	{
		global $CONFIG;
		$_SESSION[$CONFIG['session']['prefix']."session_lastaccess"] = time();
	}

	/**
	 * @implements <SessionBase::Store>
	 */
	function Store(&$obj,$id="")
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
		ObjectStore::$buffer[$id] = $obj;
	}

	/**
	 * @implements <SessionBase::Delete>
	 */
	function Delete($id)
	{
		global $CONFIG;
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
		$id = strtolower($id);
		if(isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]))
			unset($_SESSION[$CONFIG['session']['prefix']."session"][$id]);
		unset(ObjectStore::$buffer[$id]);
	}

	/**
	 * @implements <SessionBase::Exists>
	 */
	function Exists($id)
	{
		global $CONFIG;
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
		$id = strtolower($id);
		if( isset(ObjectStore::$buffer[$id]) )
			return true;

		return isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]);
	}

	/**
	 * @implements <SessionBase::Restore>
	 */
	function &Restore($id)
	{
		global $CONFIG;
		$id = strtolower($id);

		if( isset(ObjectStore::$buffer[$id]) )
			return ObjectStore::$buffer[$id];

		if(!isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]))
		{
			$null = null;
			return $null;
		}
		$data = $_SESSION[$CONFIG['session']['prefix']."session"][$id];

		$serializer = new Serializer();
		$res = $serializer->Unserialize($data);
		ObjectStore::$buffer[$id] = $res;
		return ObjectStore::$buffer[$id];
	}
}
