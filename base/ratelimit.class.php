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
 * if( !RateLimit::Define('mycontroller1')->PerSeconds(10,100)->Reserve(5) )
 *     WdfException::Raise("429 Too Many Requests");
 * </code>
 * Or you may inhertit your own class with some predefined limits:
 * <code php>
 * class MyLimits extends RateLimit
 * {
 *     static function Dashboard()
 *     {
 *         return RateLimit::Define(__METHOD__)->PerSeconds(5,5)->PerDays(1,1000)->Reserve(3);
 *     }
 *     static function Login()
 *     {
 *         if( !RateLimit::Define(__METHOD__)->PerSeconds(5,5)->PerDays(1,1000)->Reserve(3) )
 *             WdfException::Raise("429 Too Many Requests");
 *     }
 * }
 * // and later
 * if( !MyLimits::Dashboard() )
 *     WdfException::Raise("429 Too Many Requests");
 * // or even
 * MyLimits::Login();
 * </code>
 *
 */
class RateLimit extends \ScavixWDF\Model\Model
{
	/** @var string */
	public $name;

	/** @var \ScavixWDF\Base\DateTimeEx|string */
	public $created;

    /**
     * @implements <Model::GetTableName()>
     */
    public function GetTableName() { return "wdf_ratelimits"; }

    private $limits = [];

    public $internal_name;

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

    /**
     * @internal RateLimits are never saved like this
     */
    final function Save($columns_to_update = false, &$changed = null)
    {
        log_warn("No need to call ".__METHOD__." in ".system_get_caller());
        return true;
    }

    /**
     * @internal RateLimits are never removed like this
     */
    final function Delete()
    {
        log_warn("No need to call ".__METHOD__." in ".system_get_caller());
        return true;
    }

    /**
     * Entry point for method chaining a new rate limit.
     *
     * @param string $name Name of the limit.
     * @return static
     */
    public static function Define($name)
    {
        $res = new RateLimit();
        $res->name = md5($name);
        $res->internal_name = $name;
        return $res;
    }

    /**
     * End point for method chaining.
     *
     * Tries to reseve a 'slot' for this limit if possible for $timeout_seconds seconds.
     * @param int $timeout_seconds Maximum secods to try for
     * @param bool $verbose If true will <log_debug> if Reserve fails
     * @return bool True if reserved successfully, else false
     */
    public function Reserve($timeout_seconds=10, $verbose = true)
    {
        $q = [];
        foreach( $this->limits as $s=>$l )
            $q[] = "SUM(IF(age < $s,1,0)) as '$s'";
        $sql = "SELECT SQL_NO_CACHE ".implode(",",$q)." FROM (SELECT timestampdiff(second,created,now()) as age FROM wdf_ratelimits WHERE name=?) as x";

        $maxage = array_first(array_keys($this->limits));

        $end = time() + $timeout_seconds;
        do
        {
            if( !\ScavixWDF\Wdf::GetLock($this->name,0) )
            {
                usleep(10000);
                continue;
            }
            $this->_ds->ExecuteSql('DELETE FROM wdf_ratelimits WHERE name=? AND created<NOW()-INTERVAL ? SECOND', [$this->name, $maxage]);
            $ok = true;
            $count = $this->_ds->ExecuteSql($sql, [$this->name])->current();
            foreach( $this->limits as $seconds=>$limit )
                $ok &= $count[$seconds] < $limit;

            if( $ok )
            {
                $this->_ds->ExecuteSql('INSERT INTO wdf_ratelimits SET name=?', [$this->name]);
                \ScavixWDF\Wdf::ReleaseLock($this->name);
                return true;
            }

            \ScavixWDF\Wdf::ReleaseLock($this->name);
            usleep(500000);
        }
        while( time() < $end );
        if ($verbose)
            log_debug(__METHOD__ . " failed, internal_name={$this->internal_name}, caller=" . system_get_caller());
        return false;
    }

    /**
     * Limit to $limit calls per $seconds seconds
     *
     * @param int $seconds Seconds
     * @param int $limit Max calls per interval
     * @return static
     */
    public function PerSeconds($seconds, $limit)
    {
        $this->limits[$seconds] = $limit;
        krsort($this->limits);
        return $this;
    }

    /**
     * Limit to $limit calls per $minute minutes
     *
     * @param int $minutes Minutes
     * @param int $limit Max calls per interval
     * @return static
     */
    public function PerMinutes($minutes, $limit)
    {
        return $this->PerSeconds($minutes*60, $limit);
    }

    /**
     * Limit to $limit calls per $hours hours
     *
     * @param int $hours Hours
     * @param int $limit Max calls per interval
     * @return static
     */
    public function PerHours($hours, $limit)
    {
        return $this->PerMinutes($hours*60, $limit);
    }

    /**
     * Limit to $limit calls per $days days
     *
     * @param int $days Days
     * @param int $limit Max calls per interval
     * @return static
     */
    public function PerDays($days, $limit)
    {
        return $this->PerHours($days*24, $limit);
    }

    /**
     * Limit to $limit calls per $months months
     *
     * @param int $months Months
     * @param int $limit Max calls per interval
     * @return static
     */
    public function PerMonths($months, $limit)
    {
        return $this->PerDays($months*30, $limit);
    }

    /**
     * Limit to $limit calls per $years years
     *
     * @param int $years Years
     * @param int $limit Max calls per interval
     * @return static
     */
    public function PerYears($years, $limit)
    {
        return $this->PerMonths($years*12, $limit);
    }
}
