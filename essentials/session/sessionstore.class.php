<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) since 2013 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Session;

/**
 */
class SessionStore extends ObjectStore
{
    public function __construct()
    {
        if( isset($_SESSION['object_id_storage']) )
    		$GLOBALS['object_ids'] = $_SESSION['object_id_storage'];
        else
            $GLOBALS['object_ids'] = [];
    }
    
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
		$GLOBALS['object_storage'][$id] = $obj;
        
        $_SESSION[$CONFIG['session']['prefix']."object_access"][$obj->_storage_id] = time();
    }
    
	function Delete($id)
    {
        global $CONFIG;
        
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
        
        if( isset($_SESSION[$CONFIG['session']['prefix']."object_access"][$id]) )
            unset($_SESSION[$CONFIG['session']['prefix']."object_access"][$id]);
        if( isset($_SESSION['object_id_storage'][$id]) )
            unset($_SESSION['object_id_storage'][$id]);
        if( isset($GLOBALS['object_ids'][$id]) )
            unset($GLOBALS['object_ids'][$id]);
        
		$id = strtolower($id);
		if(isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]))
			unset($_SESSION[$CONFIG['session']['prefix']."session"][$id]);
		unset($GLOBALS['object_storage'][$id]);
    }
    
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
    
	function Restore($id)
    {
        global $CONFIG;
		$id = strtolower($id);

		if( isset($GLOBALS['object_storage'][$id]) )
			$res = $GLOBALS['object_storage'][$id];
        else
        {
            if(!isset($_SESSION[$CONFIG['session']['prefix']."session"][$id]))
                return null;
            
            $data = $_SESSION[$CONFIG['session']['prefix']."session"][$id];

            $serializer = new Serializer();
            $res = $serializer->Unserialize($data);
            $GLOBALS['object_storage'][$id] = $res;

        }
        
        if( $res )// update objects last access       
            $_SESSION[$CONFIG['session']['prefix']."object_access"][$res->_storage_id] = time();
		return $res;
    }
    
    function CreateId(&$obj)
    {
        global $CONFIG;

		if( unserializer_active() )
		{
			log_trace("create_storage_id while unserializing object of type ".get_class_simple($obj));
			$obj->_storage_id = "to_be_overwritten_by_unserializer";
			return $obj->_storage_id;
		}

		$cn = strtolower(get_class_simple($obj));
		if( !isset($GLOBALS['object_ids'][$cn]) )
		{
			$i = 1;
			while(isset($_SESSION[$CONFIG['session']['prefix']."session"][$cn.$i]))
				$i++;
			$GLOBALS['object_ids'][$cn] = $i;
		}
		else
			$GLOBALS['object_ids'][$cn]++;

		$obj->_storage_id = $cn.$GLOBALS['object_ids'][$cn];

		if( session_id() )
			$_SESSION['object_id_storage'] = $GLOBALS['object_ids'];

		return $obj->_storage_id;
    }
    
    function Cleanup($classname=false)
    {
        global $CONFIG;
        
        if( $classname )
        {
            $classname = strtolower($classname);
            foreach( $GLOBALS['object_storage'] as $id=>&$obj )
            {
                if( get_class_simple($obj,true) == $classname )
                    $this->Delete($id);
            }
            return;
        }
        
        foreach( $_SESSION[$CONFIG['session']['prefix']."object_access"] as $id=>$time )
        {
            if( isset($GLOBALS['object_storage'][$id]) || $time + 60 > time() )
                continue;
            delete_object($id);
        }
    }
    
    function Update($keep_alive=false)
    {
        global $CONFIG;
        
        if( $keep_alive )
        {
            foreach( $_SESSION[$CONFIG['session']['prefix']."object_access"] as $id=>$time )
                $_SESSION[$CONFIG['session']['prefix']."object_access"][$id] = time();
            return;
        }
        foreach( $GLOBALS['object_storage'] as $id=>&$obj )
		{
			try
			{
				$this->Store($obj,$id);
			}
			catch(Exception $ex)
			{
				WdfException::Log("updating storage for object $id [".get_class($obj)."]",$ex);
			}
		}
    }
    
    function Migrate($old_session_id, $new_session_id)
    {
        // nothing to to because session variable is migrated by PHP itself
    }
    
    function ListIds($classname=false)
    {
        if( !$classname )
            return array_keys($GLOBALS['object_storage']);
        $classname = strtolower($classname);
        $res = [];
        foreach( $GLOBALS['object_storage'] as $id=>&$obj )
        {
            if( get_class_simple($obj,true) == $classname )
                $res[] = $id;
        }
        return $res;
    }
}
