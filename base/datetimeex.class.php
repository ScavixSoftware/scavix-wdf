<?php
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
 
class DateTimeEx extends DateTime
{
	const SECONDS = 'sec';
	const MINUTES = 'min';
	const HOURS   = 'hour';
	const DAYS    = 'day';
	const WEEKS   = 'weeks';
	const MONTHS  = 'month';
	const YEARS   = 'year';
	
	function __toString()
	{
		return $this->format("Y-m-d H:i:s");
	}

	public static function Make($source)
	{
		if( $source instanceof DateTimeEx )
			return clone $source;
		if( $source instanceof DateTime )
			return new DateTimeEx( $source->format('c') );
		return new DateTimeEx($source);
	}
	
	public static function Now()
	{
		return new DateTimeEx();
	}
	
	public function Offset($value,$interval)
	{
		$di = DateInterval::createFromDateString("$value $interval");
		$res = clone $this;
		$res->add($di);
		return $res;
	}
	
	public function Age($unit)
	{
		$now = self::Now();
		$factor = ($this>$now)?-1:1;
		$diff = $now->diff($this);
		switch( $unit )
		{
			case self::YEARS:
				return $factor * $diff->y;
			case self::MONTHS:
				return $factor * ($diff->y*12 + $diff->m);
			case self::DAYS:
				return $factor * $diff->days;
			case self::HOURS:
				return $factor * ($diff->days*24 + $diff->h);
			case self::MINUTES:
				return $factor * ($diff->days*24*60 + $diff->i);
			case self::SECONDS:
				return $factor * ($diff->days*24*60*60 + $diff->s);
		}
		WdfException::Raise("Getting the age is not possible in unit '$unit'");
	}
	
	public function youngerThan($value,$interval)
	{
		$other = new DateTime("-$value $interval");
		return $this > $other;
	}
	
	public function olderThan($value,$interval)
	{
		$other = new DateTime("-$value $interval");
		return $this < $other;
	}
	
	/// only shortcut methods below this marker
	
	public function yt_days($days)
	{
		return $this->youngerThan($days, self::DAYS);
	}
	
	public function yt_hours($hours)
	{
		return $this->youngerThan($hours, self::HOURS);
	}
	
	public function yt_mins($minutes)
	{
		return $this->youngerThan($minutes, self::MINUTES);
	}
	
	public function ot_days($days)
	{
		return $this->olderThan($days, self::DAYS);
	}
	
	public function ot_hours($hours)
	{
		return $this->olderThan($hours, self::HOURS);
	}
	
	public function ot_mins($minutes)
	{
		return $this->olderThan($minutes, self::MINUTES);
	}
	
	public function age_secs()
	{
		return $this->Age(self::SECONDS);
	}
	
	public function is_future_date()
	{
		return $this > self::Now();
	}
	
	public function is_past_date()
	{
		return $this < self::Now();
	}
}

?>
