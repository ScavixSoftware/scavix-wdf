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
abstract class ObjectStore
{
    var $Statistics = [];
    protected function _stats($name,$started)
    {
        $now = microtime(true);
        if( isset($this->Statistics[$name]) )
        {
            $this->Statistics[$name][0]++;
            $this->Statistics[$name][1] += ($now-$started)*1000;
        }
        else
            $this->Statistics[$name] = [1,($now-$started)*1000];
    }
    
	abstract function Store(&$obj,$id="",$serialized_data=false);
	
	abstract function Delete($id);
	
	abstract function Exists($id);
	
	abstract function Restore($id);
    
	abstract function CreateId(&$obj);
    
    abstract function Cleanup($classname=false);
    
    abstract function Update($keep_alive=false);
    
    abstract function Migrate($old_session_id, $new_session_id);
}
