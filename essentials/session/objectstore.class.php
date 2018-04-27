<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2017 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2017 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Session;

/**
 * ObjectStore class is used to store objects without blowing up the PHP session.
 * 
 * When objects are serialized into the $_SESSION variable, PHP startup time increases
 * dramatically once a fair number of objects is reached. To avoid this we use a special
 * ObjectStore. In fact, we implemented different stores for different scenarios, all inherited from this
 * base class.
 */
abstract class ObjectStore
{
    var $Statistics = [];
    protected function _stats($name,$started)
    {
        if(!isDev())
            return;
        $now = microtime(true);
        if( isset($this->Statistics[$name]) )
        {
            $this->Statistics[$name][0]++;
            $this->Statistics[$name][1] += ($now-$started)*1000;
        }
        else
            $this->Statistics[$name] = [1,($now-$started)*1000];
    }
    
    /**
     * @internal Used for performance testing
     */
    public function GetStats()
    {
        if(!isDev())
            return;
        if( isset($this->Statistics['total_time']) )
            unset($this->Statistics['total_time']);
        $t = 0;
        foreach( $this->Statistics as $k=>$v )
            $t += $v[1];
        $this->Statistics['total_time'] = $t;
        return $this->Statistics;
    }
    
    /**
     * Stores an object.
     * 
     * @param mixed $obj Object to be stored
     * @param string $id Optional id
     * @return void
     */
	abstract function Store(&$obj,$id="");
	
    /**
     * Removes an object from the store.
     * 
     * @param string $id The object ID
     * @return void
     */
	abstract function Delete($id);
	
    /**
     * Checks, if an object is stored.
     * 
     * @param string $id The object ID
     * @return bool true or false
     */
	abstract function Exists($id);
	
    /**
     * Loads an object from the store.
     * 
     * @param string $id The object ID
     * @return mixed The object or false
     */
	abstract function Restore($id);
    
    /**
     * @internal Creates a unique object ID
     */
	abstract function CreateId(&$obj);
    
    /**
     * @internal Cleans things up
     */
    abstract function Cleanup($classname=false);
    
    /**
     * Updates used objects.
     * 
     * @param bool $keep_alive If true, updates the 'used' timestamps too
     * @return void
     */
    abstract function Update($keep_alive=false);
    
    /**
     * Changes the session ID.
     * 
     * @param string $old_session_id The old session ID
     * @param string $new_session_id The new session ID
     * @return void
     */
    abstract function Migrate($old_session_id, $new_session_id);
}
