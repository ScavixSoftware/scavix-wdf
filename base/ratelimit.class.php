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
namespace ScavixWDF\Base;

/**
 * Tool class to help stop flooding systems.
 * 
 * You may define a RateLimit on the fly directly where it is needed:
 * <code php>
 * if( !RateLimit::Define('mycontroller1')->PerSecond(10,100)->Request(5) )
 *     WdfException::Raise("429 Too Many Requests");
 * </code>
 * Or you may inhertit your own class with some predefined limits:
 * <code php>
 * class MyLimits extends RateLimit
 * {
 *     static function Dashboard()
 *     {
 *         return RateLimit::Define(__METHOD__)->PerSecond(5,5)->PerDay(1,1000)->Request(3);
 *     }
 *     static function Login()
 *     {
 *         if( !RateLimit::Define(__METHOD__)->PerSecond(5,5)->PerDay(1,1000)->Request(3) )
 *             WdfException::Raise("429 Too Many Requests");
 *     }
 * }
 * // and later
 * if( !MyLimits::Dashboard() )
 *     WdfException::Raise("429 Too Many Requests");
 * // or even
 * MyLimits::Login();
 * </code>
 */
class RateLimit extends \ScavixWDF\Model\Model
{
    private $limits = [];
    
    public function GetTableName() { return "wdf_ratelimits"; }
    
    protected function CreateTable()
    {
        $this->_ds->ExecuteSql(
            "CREATE TABLE `wdf_ratelimits` (
                `name` VARCHAR(40) NOT NULL,
                `created` TIMESTAMP NULL DEFAULT current_timestamp(),
                INDEX `name` (`name`),
                INDEX `created` (`created`)
            )
            ENGINE=MEMORY ;");
        $this->AlterTable();
    }
    
    protected function AlterTable(){}
    
    final function Save($columns_to_update = false, &$changed = null)
    {
        log_warn("No need to call ".__METHOD__);
        return true;
    }
    
    final function Delete()
    {
        log_warn("No need to call ".__METHOD__);
        return true;
    }
    
    public static function Define($name)
    {
        $res = new RateLimit();
        $res->name = md5($name);
        return $res;
    }
       
    public function Reserve($timeout_seconds=10)
    {
        $q = [];
        foreach( $this->limits as $s=>$l )
            $q[] = "SUM(IF(age < $s,1,0)) as '$s'";
        $sql = "SELECT SQL_NO_CACHE ".implode(",",$q)." FROM (SELECT timestampdiff(second,created,now()) as age FROM wdf_ratelimits WHERE name='{$this->name}') as x";
        
        $maxage = array_first(array_keys($this->limits));
        $this->_ds->Execute("DELETE FROM wdf_ratelimits WHERE name='{$this->name}' AND created<NOW()-INTERVAL $maxage SECOND");
        
        $end = time() + $timeout_seconds;
        do
        {
            if( !\ScavixWDF\Wdf::GetLock($this->name,0) )
            {
                usleep(10000);
                continue;
            }
            $ok = true;
            $count = $this->_ds->ExecuteSql($sql)->current();
            foreach( $this->limits as $seconds=>$limit )
                $ok &= $count[$seconds] < $limit;
            
            if( $ok )
            {
                $this->_ds->Execute("INSERT INTO wdf_ratelimits(name)VALUES('{$this->name}')");
                \ScavixWDF\Wdf::ReleaseLock($this->name);
                return true;
            }
            
            \ScavixWDF\Wdf::ReleaseLock($this->name);
            usleep(500000);
        }
        while( time() < $end );
        log_debug(__METHOD__, 'unable to reserve');
        return false;
    }
    
    /***
     * Limit to $limit calls per $seconds seconds
     */
    public function PerSeconds($seconds, $limit)
    {
        $this->limits[$seconds] = $limit;
        krsort($this->limits);
        return $this;
    }
    
    public function PerMinutes($minutes, $limit)
    {
        return $this->PerSeconds($minutes*60, $limit);
    }
    
    public function PerHours($hours, $limit)
    {
        return $this->PerMinutes($hours*60, $limit);
    }
    
    public function PerDays($days, $limit)
    {
        return $this->PerHours($days*24, $limit);
    }
    
    public function PerMonths($months, $limit)
    {
        return $this->PerDays($months*30, $limit);
    }
    
    public function PerYears($years, $limit)
    {
        return $this->PerMonths($years*12, $limit);
    }
}
