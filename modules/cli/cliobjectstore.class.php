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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2023 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Session;

use ScavixWDF\WdfException;

/**
 * Stores objects in the filesystem.
 * 
 * This is by far the fastets <ObjectStore> implementation. As we use it mostly,
 * it is most commonly updated!
 */
class CliObjectStore extends FilesStore
{
    public function __construct() // hides the parent constructor!
    {
        $this->serializer = new Serializer();
        if( !isset($_SESSION['object_ids']) )
            $_SESSION['object_ids'] = [];
    }
    
    /**
     * @override <ObjectStore::Delete>
     */
	function Delete($id)
    {
        $start = microtime(true);
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
        
        if( isset(ObjectStore::$buffer[$id]) )
            unset(ObjectStore::$buffer[$id]);
        $this->_stats(__METHOD__,$start);
    }
    
    /**
     * @override <ObjectStore::Exists>
     */
	function Exists($id)
    {
        $start = microtime(true);
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
		$id = strtolower($id);
		if( isset(ObjectStore::$buffer[$id]) )
            $res = true;
        else
            $res = false;
        $this->_stats(__METHOD__,$start);
		return $res;
    }
    
    /**
     * @override <ObjectStore::Restore>
     */
	function Restore($id)
    {
        $start = microtime(true);
		$id = strtolower($id);

		if( isset(ObjectStore::$buffer[$id]) )
        {
			$res = ObjectStore::$buffer[$id];
            $this->_stats(__METHOD__,$start);
        }
        else
        {
            $res = null;
        }
		return $res;
    }
    
    /**
     * @override <ObjectStore::Cleanup>
     */
    function Cleanup($classname=false)
    {
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
        $this->_stats(__METHOD__,$start);
    }
    
    /**
     * @override <ObjectStore::Update>
     */
    function Update($keep_alive=false)
    {
    }
    
    /**
     * @override <ObjectStore::Migrate>
     */
    function Migrate($old_session_id, $new_session_id)
    {
    }
}
