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
 * A DB Session handler.
 * THIS WHOLE CLASS IS UNTESTED!!!
 */
class DbSession extends SessionBase
{
	var $ds;

	function __constrruct()
	{
		parent::__construct();
		$this->ds = model_datasource($CONFIG['session']['datasource']);
	}

	function Sanitize()
	{
		global $CONFIG;
		$this->ds->ExecuteSql(
			"DELETE FROM ".$CONFIG['session']['table']."
			WHERE last_access<NOW()-INTERVAL $lt MINUTE"
		);

		$rs = $this->ds->ExecuteSql(
			"SELECT storage_id FROM ".$CONFIG['session']['table']."
			WHERE id=?0 AND auto_load>0",session_id()
		);
		while( !$rs->EOF )
		{
			restore_object($rs->fields['storage_id']);
			$rs->MoveNext();
		}
	}

	function KillAll()
	{
		global $CONFIG;
        $this->ds->ExecuteSql(
            "DELETE FROM ".$CONFIG['session']['table']."
            WHERE id=?0",array(session_id())
        );
		unset($_SESSION[$CONFIG['session']['prefix']."session_lastaccess"]);
		session_destroy();
		session_start();
	}

	function KeepAlive($request_key='PING')
	{
		global $CONFIG;
		$rid = isset($_REQUEST[$request_key])?$_REQUEST[$request_key]:"";

		$this->ds->ExecuteSql(
			"UPDATE ".$CONFIG['session']['table']."
			SET last_access=NOW() WHERE id=?0 AND (request_id=?1 OR auto_load=1)",array(session_id(),$rid)
		);

		$GLOBALS['session_request_id'] = $rid;
	}

	function Store(&$obj,$id="",$autoload=false)
	{
		global $CONFIG;

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

		$vals = "id=?0 , request_id=?1 , storage_id=?2 , last_access=NOW(), content=?3";
		$updates = "last_access=NOW(), content=?4, request_id=?5";
		if( $autoload )
		{
			$vals .= " , auto_load=1";
			$updates .= " , auto_load=1";
		}

		$this->ds->ExecuteSql(
			"REPLACE INTO ".$CONFIG['session']['table']."
			SET $vals",
			array(session_id(),request_id(),$id,$content)
		);

		$GLOBALS['object_storage'][strtolower($id)] = $obj;
	}

	function Delete($id)
	{
		global $CONFIG;
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;

		$this->ds->ExecuteSql(
			"DELETE FROM ".$CONFIG['session']['table']."
			WHERE id=?0 AND storage_id=?1",array(session_id(),$id)
		);
		unset($GLOBALS['object_storage'][strtolower($id)]);
	}

	function Exists($id)
	{
		global $CONFIG;
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;

		if( isset($GLOBALS['object_storage'][strtolower($id)]) )
			return true;

		$rs = $this->ds->ExecuteSql(
			"SELECT COUNT(*) as cnt FROM ".$CONFIG['session']['table']."
			WHERE id=?0 AND storage_id=?1 LIMIT 1",array(session_id(),$id)
		);
		return $rs->fields['cnt'] > 0;
	}

	function &Restore($id)
	{
		global $CONFIG;

		if( isset($GLOBALS['object_storage'][strtolower($id)]) )
			return $GLOBALS['object_storage'][strtolower($id)];

		$rs = $this->ds->ExecuteSql(
			"SELECT content FROM ".$CONFIG['session']['table']."
			WHERE id=?0 AND storage_id=?1 LIMIT 1",array(session_id(),$id)
		);
		if( $rs->EOF )
		{
			log_trace("Trying to restore unknown object '$id'");
			$null = null;
			return $null;
		}
		$data = $rs->fields['content'];

		$serializer = new Serializer();
		$res = $serializer->Unserialize($data);
		$GLOBALS['object_storage'][strtolower($id)] = $res;
		return $GLOBALS['object_storage'][strtolower($id)];
	}
}

?>