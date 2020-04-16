<?php
/**
 * Scavix Web Development Framework
 *
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
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Tasks;

abstract class Task
{
    var $model;
    var $ds;
    
    function __construct(WdfTaskModel $model=null)
    {
        $this->model = $model;
        $this->ds = \ScavixWDF\Model\DataSource::Get();
    }
    
    public static function Make() : Task
    {
        $name = get_called_class();
        return new $name();
    }
    
    public static function Async($method='run') : WdfTaskModel
    {
        $name = get_called_class()."-$method";
        return WdfTaskModel::Create($name);
    }
    
    public static function AsyncOnce($method='run', $return_original=false) : WdfTaskModel
    {
        $name = get_called_class()."-$method";
        return WdfTaskModel::CreateOnce($name, $return_original);
    }
    
    abstract function Run($args);
    
    /**
     * Called once the Task finished processing.
     * 
     * @param type $runtime The total time from creation/start till not in ms
     * @param type $exectime The time in ms needed for actual execution (Run method)
     */
    public function Finished($method, $runtime, $exectime)
    {
    }
    
    protected function mapCliArgs(&$args,$exact,$names)
    {
        $ca = count($args); 
        $cn = is_array($names)?count($names):$names;
        if( $ca<$cn || ($exact && $ca!=$cn) )
            return array_fill(0,$cn,false);
        if( !is_array($names) ) 
            $names = range(0,$cn-1);
        $res = [];
        foreach( $names as $n )
            $res[$n] = array_shift($args);
        //log_debug($res);
        return $res;
    }
    
    function inner()
    {
        log_debug(__METHOD__);
    }
}
