<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2017-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2017-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Session;

use ScavixWDF\WdfException;

/**
 * Stores objects in the SESSION.
 * 
 * Well...this is the storage that does what we did not want anymore: Blow up the PHP Session.
 * It ist straight and some cases it can be useful, because it's configurationless.
 */
class SessionStore extends ObjectStore
{
    protected $serializer;
    
    public function __construct()
    {
        if( isset($_SESSION['object_id_storage']) )
    		ObjectStore::$ids = $_SESSION['object_id_storage'];
        else
            ObjectStore::$ids = [];
        
        $this->serializer = new Serializer();
    }
    
    /**
     * @override <ObjectStore::Store>
     */
    function Store(&$obj,$id="")
    {
        global $CONFIG;
        $start = microtime(true);
		$id = strtolower($id);
		if( $id == "" )
		{
			if( !isset($obj->_storage_id) )
				WdfException::Raise("Trying to store an object without storage_id!");
			$id = $obj->_storage_id;
		}
		else
			$obj->_storage_id = $id;
        
        $content = $this->serializer->Serialize($obj);
        $_SESSION[$CONFIG['session']['prefix']."session"][$id] = $content;
        
		ObjectStore::$buffer[$id] = $obj;
        
        $_SESSION[$CONFIG['session']['prefix']."object_access"][$obj->_storage_id] = time();
        $this->_stats(__METHOD__,$start);
    }
    
    /**
     * @override <ObjectStore::Delete>
     */
	function Delete($id)
    {
        global $CONFIG;
        $start = microtime(true);
        
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
        
        if( isset($_SESSION[$CONFIG['session']['prefix']."object_access"][$id]) )
            unset($_SESSION[$CONFIG['session']['prefix']."object_access"][$id]);
        if( isset($_SESSION['object_id_storage'][$id]) )
            unset($_SESSION['object_id_storage'][$id]);
        if( isset(ObjectStore::$ids[$id]) )
            unset(ObjectStore::$ids[$id]);
        
		$id = strtolower($id);
		if(isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]))
			unset($_SESSION[$CONFIG['session']['prefix']."session"][$id]);
		unset(ObjectStore::$buffer[$id]);
        $this->_stats(__METHOD__,$start);
    }
    
    /**
     * @override <ObjectStore::Exists>
     */
	function Exists($id)
    {
        global $CONFIG;
        $start = microtime(true);
        
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
		$id = strtolower($id);
		if( isset(ObjectStore::$buffer[$id]) )
			$res = true;
        else
            $res = isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]);
        $this->_stats(__METHOD__,$start);
		return $res;
    }
    
    /**
     * @override <ObjectStore::Restore>
     */
	function Restore($id)
    {
        global $CONFIG;
        $start = microtime(true);
		$id = strtolower($id);

		if( isset(ObjectStore::$buffer[$id]) )
			$res = ObjectStore::$buffer[$id];
        else
        {
            if(!isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]))
                return null;
            
            $data = $_SESSION[$CONFIG['session']['prefix']."session"][$id];

            $res = $this->serializer->Unserialize($data);
            ObjectStore::$buffer[$id] = $res;

        }
        
        if( $res && is_object($res) && isset($res->_storage_id) )// update objects last access       
            $_SESSION[$CONFIG['session']['prefix']."object_access"][$res->_storage_id] = time();
        $this->_stats(__METHOD__,$start);
		return $res;
    }
    
    /**
     * @override <ObjectStore::CreateId>
     */
    function CreateId(&$obj)
    {
        global $CONFIG;
        $start = microtime(true);

		if( unserializer_active() )
		{
			log_trace("create_storage_id while unserializing object of type ".get_class_simple($obj));
			$obj->_storage_id = "to_be_overwritten_by_unserializer";
			return $obj->_storage_id;
		}

		$cn = strtolower(get_class_simple($obj));
		if( !isset(ObjectStore::$ids[$cn]) )
		{
			$i = 1;
			while(isset($_SESSION[$CONFIG['session']['prefix']."session"][$cn.$i]))
				$i++;
			ObjectStore::$ids[$cn] = $i;
		}
		else
			ObjectStore::$ids[$cn]++;

		$obj->_storage_id = $cn.ObjectStore::$ids[$cn];

		if( session_id() )
			$_SESSION['object_id_storage'] = ObjectStore::$ids;

        $this->_stats(__METHOD__,$start);
		return $obj->_storage_id;
    }
    
    /**
     * @override <ObjectStore::Cleanup>
     */
    function Cleanup($classname=false)
    {
        global $CONFIG;
        $start = microtime(true);
        
        if( $classname )
        {
            $classname = strtolower($classname);
            foreach( ObjectStore::$buffer as $id=>&$obj )
            {
                if( get_class_simple($obj,true) == $classname )
                    $this->Delete($id);
            }
            $this->_stats(__METHOD__."/CN",$start);
            return;
        }
        
        if(isset($_SESSION[$CONFIG['session']['prefix']."object_access"]))
        {
            foreach( $_SESSION[$CONFIG['session']['prefix']."object_access"] as $id=>$time )
            {
                if( isset(ObjectStore::$buffer[$id]) || $time + 60 > time() )
                    continue;
                delete_object($id);
            }
        }
        $this->_stats(__METHOD__,$start);
    }
    
    /**
     * @override <ObjectStore::Update>
     */
    function Update($keep_alive=false)
    {
        global $CONFIG;
        $start = microtime(true);
        
        if( $keep_alive )
        {
            foreach( $_SESSION[$CONFIG['session']['prefix']."object_access"] as $id=>$time )
                $_SESSION[$CONFIG['session']['prefix']."object_access"][$id] = time();
            return;
        }
        
        foreach( ObjectStore::$buffer as $id=>&$obj )
		{
			try
			{
				$this->Store($obj,$id);
			}
			catch(\Exception $ex)
			{
				WdfException::Log("updating storage for object $id [".get_class($obj)."]",$ex);
			}
		}
        $this->_stats(__METHOD__.($keep_alive?"/KA":''),$start);
    }
    
    /**
     * @override <ObjectStore::Migrate>
     */
    function Migrate($old_session_id, $new_session_id)
    {
        // nothing to to because session variable is migrated by PHP itself
    }
}
