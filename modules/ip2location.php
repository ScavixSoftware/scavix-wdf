<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2021 Scavix Software GmbH & Co. KG
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
 * @copyright since 2021 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

use ScavixWDF\Wdf;
use ScavixWDF\WdfException;

/**
 * Module to localize ip-addresses.
 *
 * Uses [IP2Location](https://lite.ip2location.com/ip2location-lite)
 * Needs 'composer require ip2location/ip2location-php'
 * @return void
*/
function ip2location_init()
{
	global $CONFIG;

	if( !Wdf::$ClientIP )
		Wdf::$ClientIP = get_ip_address();

	if( !isset($CONFIG['ip2location']) || (!file_exists($CONFIG['ip2location']['ipv4_bin_file']) && !file_exists($CONFIG['ip2location']['ipv6_bin_file'])) )
		WdfException::Raise("ip2location module: missing database BIN file. Get it from https://lite.ip2location.com/ip2location-lite");

	if( !isset($CONFIG['ip2location']['autoload_file']) || !file_exists($CONFIG['ip2location']['autoload_file']) )
		WdfException::Raise("ip2location module: missing autoloader at '".ifavail($CONFIG['ip2location'],'autoload_file')."'");

    require_once($CONFIG['ip2location']['autoload_file']);
}

/**
 * Resolves an IP address to a location.
 *
 * @param string $ip IP address to check (defaults to <get_ip_address>)
 * @return stdClass|false Object containing location information
 *
 * @suppress PHP0413
 */
function get_geo_location_by_ip($ip = false)
{
 	// ip could be something like "1.1 ironportweb01.gouda.lok:80 (IronPort-WSA/7.1.1-038)" from proxies
	if($ip === false)
		$ip = Wdf::$ClientIP;
	if(starts_with($ip, "1.1 ") || starts_with($ip, "192.168."))
		return false;

    $key = "get_geo_location_by_ip.".getAppVersion('nc')."-".$ip;
    $ret = cache_get($key);
	if( $ret )
		return $ret;

    global $CONFIG;
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
        $binfile = $CONFIG['ip2location']['ipv4_bin_file'];
    elseif(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
        $binfile = $CONFIG['ip2location']['ipv6_bin_file'];
    else
        return false;

    $ipdb = new \IP2Location\Database($binfile, \IP2Location\Database::FILE_IO);
    $ret = $ipdb->lookup($ip, \IP2Location\Database::ALL);
    if($ret === false)
        return $ret;
    $ret = (object) $ret;
//    log_debug($ret);
    cache_set($key, $ret, 60 * 60); // keep that in cache for an hour

    return $ret;
}

/**
 * Returns the region name for the current IP address
 *
 * See <get_ip_address>
 * @param string $ip IP address to resolve (defaults to <get_ip_address>)
 * @return string|false Location name or empty string if unknown
 */
function get_geo_region($ip = false)
{
    $data = get_geo_location_by_ip($ip);
    if(avail($data, 'regionName'))
        return $data->regionName;
    return false;
}

/**
 * Resolves an IP address to geo coordinates.
 *
 * @param string $ip IP address to resolve (defaults to <get_ip_address>)
 * @return array|false Associative array with keys 'latitude' and 'longitude'
 */
function get_coordinates_by_ip($ip = false)
{
	// ip could be something like "1.1 ironportweb01.gouda.lok:80 (IronPort-WSA/7.1.1-038)" from proxies
	$data = get_geo_location_by_ip($ip);
    if(avail($data, 'longitude') && avail($data, 'latitude'))
        return [
            'latitude' => $data->latitude,
            'longitude' => $data->longitude
        ];
    return false;
}

/**
 * Resolves an IP address to a country code
 *
 * @param string $ip IP address to resolve (defaults to <get_ip_address>)
 * @return array|false Country code or empty string if not found
 */
function get_countrycode_by_ip($ip = false)
{
    $data = get_geo_location_by_ip($ip);
    if(avail($data, 'countryCode'))
        return $data->countryCode;
    return false;
}

/**
 * Returns the country name from the current IP
 *
 * @param string $ip IP address to resolve (defaults to <get_ip_address>)
 * @return string|false Country name or empty string if unknown
 */
function get_countryname_by_ip($ip = false)
{
    $data = get_geo_location_by_ip($ip);
    if(avail($data, 'countryName'))
        return $data->countryName;
    return false;
}

/**
 * Returns the timezone for an IP address.
 *
 * @suppress PHP0416
 * @param string $ip IP address to check (defaults to <get_ip_address>)
 * @return string Timezone identifier or false on error
 */
function get_timezone_by_ip($ip = false)
{
    global $CONFIG;
	if($ip === false)
		$ip = Wdf::$ClientIP;
	if( !$ip )
		return false;

    if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
		return false;

	$key = "get_timezone_by_ip.".getAppVersion('nc')."-".$ip;
    if($ret = cache_get($key))
		return $ret;

    if(($data = get_geo_location_by_ip($ip)) && (ifavail($data, 'countryCode') == 'DE'))
        return 'Europe/Berlin';     // Germany is always Europe/Berlin

    $isDst = date('I');
    // better way to figure out if our german server is in summer time:
    $year     = date('Y');
    $timezone = 'Europe/Berlin';
    $dt = new DateTimeZone($timezone);
    $ts = $dt->getTransitions(mktime(0, 0, 0, 1, 1, $year), mktime(0, 0, 0, 12, 31, $year));
    if (isset($ts[1]))
    {
        $offset = (($ts[1]['offset']-$ts[2]['offset'])/3600);
        if($offset != 0)
        {
            if((date_create()->getTimestamp() >= date_create($ts[1]['time'])->getTimestamp()) && (date_create()->getTimestamp() <= date_create($ts[2]['time'])->getTimestamp()))
                $isDst = 1;
        }
    }

    $services = [];
    $triedurls = [];

    $services['ip2location'] = (file_exists($CONFIG['ip2location']['ipv4_bin_file']) || file_exists($CONFIG['ip2location']['ipv6_bin_file']));

    if( isset($CONFIG['geoip']['ip-api']) && isset($CONFIG['geoip']['ip-api']['apikey']) )
    {
        $services["https://pro.ip-api.com/php/$ip?key=".$CONFIG['geoip']['ip-api']['apikey']] = function($response)
        {
            $data = @unserialize($response);
            if( $data && $data['status'] == 'success' )
                return [$data['timezone'], $data['countryCode']];
            return false;
        };
    }

    // use ip-api free service (limited)
    if( !isset($CONFIG['geoip']['ip-api']) || !isset($CONFIG['geoip']['ip-api']['usefree']) || $CONFIG['geoip']['ip-api']['usefree'] )
    {
        $services["http://ip-api.com/php/$ip"] = function($response)
        {
            $data = @unserialize($response);
            if( $data && $data['status'] == 'success' )
                return [$data['timezone'], $data['countryCode']];
            return false;
        };
    }

    if( isset($CONFIG['geoip']['ipinfodb']) && isset($CONFIG['geoip']['ipinfodb']['apikey']) )
    {
        $services["https://api.ipinfodb.com/v3/ip-city/?key={$CONFIG['geoip']['ipinfodb']['apikey']}&ip={$ip}&format=xml"] = function($response)
        {
            $data = simplexml_load_string($response);
            if(avail($data, 'timeZone'))
                return [(string)$data->timeZone, (string)ifavail($data, 'countryCode')];
            return false;
        };
    }
    if( isset($CONFIG['geoip']['geonames']) && isset($CONFIG['geoip']['geonames']['username']) )
        $services['geo'] = false; // prepare geo search inline to avoid overhead here

    // use geobytes free service (limited). Free API key is 7c756203dbb38590a66e01a5a3e1ad96
    $apikey = (isset($CONFIG['geoip']['geobytes']) && isset($CONFIG['geoip']['geobytes']['apikey'])) ? $CONFIG['geoip']['geobytes']['apikey'] : '7c756203dbb38590a66e01a5a3e1ad96';
    $services["https://secure.geobytes.com/GetCityDetails?key=".$apikey."&fqcn=$ip"] = function($response) use ($isDst)
    {
        $data = json_decode($response, true);
        if( $data && $data['geobytestimezone'] )
        {
            $tz = $data['geobytestimezone'];
            if($isDst && (strpos($tz, ':') !== false))
            {
                list($hours, $minutes) = explode(':', $tz);
                $hours = intval($hours) + 1;
                $tz = ($hours >= 0 ? '+' : '-').sprintf('%02d:%02d', abs($hours), $minutes);
            }
            return [$tz, ifavail($data, 'geobytesinternet')];
        }
        return false;
    };

    foreach( $services as $url=>$cb )
    {
        $triedurls[] = $url;

        if( $url == 'geo' ) // prepare geo search inline to only have overhead when we reach that case
        {
            $coords = get_coordinates_by_ip($ip);
            if( !$coords )
                continue;

            $url = "http://api.geonames.org/timezone?username={$CONFIG['geoip']['geonames']['username']}&lat={$coords['latitude']}&lng={$coords['longitude']}";
            $cb = function($response)
            {
                $data = simplexml_load_string($response);
                if(avail($data, 'timezoneId'))
                    return [(string)$data->timezoneId, (string)ifavail($data, 'countryCode')];
                return false;
            };
        }
        elseif( $url == 'ip2location' ) // prepare geo search inline to only have overhead when we reach that case
        {
            $url = false;
            $cb = function($response) use ($ip, &$isDst)
            {
                if(($data = get_geo_location_by_ip($ip)) && avail($data, 'timeZone'))
                {
                    // not reliable at all! timezone in ip2location files is with DST, but it depends on download date of bin files :-()
                    $isDst = true;
                    return [$data->timeZone, $data->countryCode];
                }
                return false;
            };
        }

        $resp = ($url ? downloadData($url, false, false, 60 * 60, 2) : false);
        $zone = $cb($resp);
    //    log_debug($ip, $url, $zone, $isDst);
        if( $zone !== false )
        {
            if(strpos($zone[0], ':') !== false)
            {
                if(!$zone[1])
                    $zone[1] = get_countrycode_by_ip($ip);
                list($hours, $minutes) = explode(':', $zone[0]);
                $seconds = $hours * 60 * 60 + $minutes * 60;
                $tz = false;
                if($zone[1])
                {
                    $tzincountry = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $zone[1]);
                    $validtz = [];
                    foreach(DateTimeZone::listAbbreviations() as $z)
                    {
                        foreach($z as $item)
                        {
                            if(($item['dst'] == $isDst) && ($item['offset'] == $seconds))
                                $validtz[] = $item['timezone_id'];
                        }
                    }
                    $possibletz = array_intersect($tzincountry, $validtz);
                    // log_debug($tzincountry, $possibletz);
                    if(count($possibletz))
                        $tz = array_first($possibletz);
                }
                if(!$tz)
                {
                    // Get timezone name from seconds
                    $tz = timezone_name_from_abbr('', $seconds, $isDst);
                    // Workaround for bug #44780
                    if($tz === false)
                        $tz = timezone_name_from_abbr('', $seconds, ($isDst ? 0 : 1));
                }
                if($tz)
                    $zone[0] = $tz;
            }

        //    if(isDev())
            //    log_debug($url, $zone, $tz, $isDst, $triedurls);
            cache_set($key, $zone[0], 24 * 60 * 60);
            return $zone[0];
        }
    }

    $zone = isset($CONFIG['geoip']['default_timezone'])
        ?$CONFIG['geoip']['default_timezone']
        :date_default_timezone_get();
    log_debug("No timezone found for $ip, falling back to $zone. Tried: ".join(', ', $triedurls));
    cache_set($key, $zone, 60 * 60); // keep that in cache for an hour
	return $zone;
}