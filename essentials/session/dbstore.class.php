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
class DbStore extends ObjectStore
{
    public function __construct()
    {
        global $CONFIG;
        
        if( !isset($CONFIG['session']['dbstore']['datasource']) )
            $CONFIG['session']['dbstore']['datasource'] = 'internal';
        
        $this->ds = model_datasource($CONFIG['session']['dbstore']['datasource']);
        
        $this->exec(
            "DELETE FROM wdf_objects WHERE session_id=? AND ISNULL(data)",
            [session_id()]
        );
        
        $GLOBALS['object_ids'] = $this->exec(
            "SELECT classname,no FROM wdf_objects WHERE session_id=? GROUP BY classname ORDER BY classname ASC, no DESC",
            [session_id()])->Enumerate('no',false,'classname');
    }
    
    private function exec($sql,$args=[])
    {
        try
        {
            return $this->ds->ExecuteSql($sql,$args);
        }
        catch (\ScavixWDF\WdfDbException $ex)
        {
            $info = $ex->getErrorInfo();
            if( isset($info[1]) && $info[1] == 1146 )
            {
                $this->ds->ExecuteSql("CREATE TABLE `wdf_objects` (
                        `session_id` VARCHAR(100) NOT NULL,
                        `id` VARCHAR(255) NOT NULL,
                        `classname` VARCHAR(255) NOT NULL,
                        `created` DATETIME NULL DEFAULT NULL,
                        `last_access` DATETIME NULL DEFAULT NULL,
                        `data` LONGTEXT NULL,
                        PRIMARY KEY (`session_id`, `id`)
                    )
                    COLLATE='utf8_general_ci'
                    ENGINE=InnoDB");
                
                return $this->ds->ExecuteSql($sql,$args);
            }
        }
        return new \ScavixWDF\Model\ResultSet($this->ds);
    }
    
    function Store(&$obj,$id="")
    {
		$id = strtolower($id);
		if( $id == "" )
		{
			if( !isset($obj->_storage_id) )
				WdfException::Raise("Trying to store an object without storage_id!");
			$id = $obj->_storage_id;
		}
		else
			$obj->_storage_id = $id;
        
        $cn = strtolower(get_class_simple($obj));
        $no = str_replace($cn,'',$obj->_storage_id);
		$serializer = new Serializer();
		$content = $serializer->Serialize($obj);
        
//        log_debug(__METHOD__,session_id(),$id);
		$this->exec("INSERT INTO wdf_objects
				SET session_id	= ?,
					id		    = ?,
					classname   = ?,
					no          = ?,
					created	    = now(),
					last_access	= now(),
					data		= ?
				ON DUPLICATE KEY UPDATE
					last_access	= now(),
					data		= ?", [session_id(),$obj->_storage_id,$cn,$no,$content,$content]);
		$GLOBALS['object_storage'][$id] = $obj;
    }
    
	function Delete($id)
    {
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
        
        if( isset($GLOBALS['object_storage'][$id]) )
            unset($GLOBALS['object_storage'][$id]);
		$this->exec("DELETE FROM wdf_objects WHERE session_id=? AND id=?", [session_id(),$id]);
    }
    
	function Exists($id)
    {
//        log_debug(__METHOD__,session_id(),$id);
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
		$id = strtolower($id);
		if( isset($GLOBALS['object_storage'][$id]) )
			return true;

		return $this->exec("SELECT id FROM wdf_objects WHERE session_id=? AND id=?", [session_id(),$id])->Count()>0;
    }
    
	function Restore($id)
    {
		$id = strtolower($id);
//        log_debug(__METHOD__,session_id(),$id);

		if( isset($GLOBALS['object_storage'][$id]) )
			$res = $GLOBALS['object_storage'][$id];
        else
        {
            $row = $this->exec("SELECT data FROM wdf_objects WHERE session_id=? AND id=?", [session_id(),$id])->current();
            $data = $row['data'];

            $serializer = new Serializer();
            $res = $serializer->Unserialize($data);
            $GLOBALS['object_storage'][$id] = $res;

        }
		return $res;
    }
    
    function CreateId(&$obj)
    {
		if( unserializer_active() )
		{
			log_trace("create_storage_id while unserializing object of type ".get_class_simple($obj));
			$obj->_storage_id = "to_be_overwritten_by_unserializer";
			return $obj->_storage_id;
		}

		$cn = strtolower(get_class_simple($obj));
		if( !isset($GLOBALS['object_ids'][$cn]) )
			$GLOBALS['object_ids'][$cn] = 1;
		else
			$GLOBALS['object_ids'][$cn]++;

        do
        {
            $obj->_storage_id = $cn.$GLOBALS['object_ids'][$cn];
            if( system_is_ajax_call() )
                return $obj->_storage_id;
            
            $rs = $this->exec("INSERT IGNORE INTO wdf_objects
                    SET session_id	= ?,
                        id		    = ?,
                        classname   = ?,
                        no          = ?,
                        created	    = now(),
                        last_access	= now()", [session_id(),$obj->_storage_id,$cn,$GLOBALS['object_ids'][$cn]]);
            if( $rs->Count() > 0 )
                break;
            $GLOBALS['object_ids'][$cn]++;
        }while( true );
		return $obj->_storage_id;
    }
    
    function Cleanup($classname=false)
    {
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

        $this->exec(
            "DELETE FROM wdf_objects WHERE 
                (session_id=? AND (last_access<now()-interval 60 second OR ISNULL(data)) )
                OR (last_access<now()-interval 300 second)",
            [session_id()]
        );
    }
    
    function Update($keep_alive=false)
    {
//        log_debug(__METHOD__,session_id(),$keep_alive);
        
        if( $keep_alive )
        {
            $this->exec("UPDATE wdf_objects SET last_access=now() WHERE session_id=?",[session_id()]);
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
//        log_debug("Migrate($old_session_id, $new_session_id)");
        $this->exec("UPDATE IGNORE wdf_objects SET session_id=? WHERE session_id=?",[$new_session_id,$old_session_id]);
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
