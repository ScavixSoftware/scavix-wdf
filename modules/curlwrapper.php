<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

/**
 * @internal Just init
 */
function curlwrapper_init()
{
    if( !function_exists('curl_init') )
        ScavixWDF\WdfException::Raise("CURL not found, please install php-curl");
    
    classpath_add(__DIR__.'/curlwrapper');
    create_class_alias(ScavixWDF\Tasks\WebRequest::class,'WebRequest');
}

/**
 * Sets a proxy for all subsequent downloads.
 * 
 * @param string $ip Hostname/IP Address
 * @param string|int $port Port
 * @param int $type CURLPROXY_HTTP or CURLPROXY_SOCKS5
 * @return void
 */
function setDownloadProxy($ip,$port,$type=CURLPROXY_SOCKS5)
{
	$GLOBALS['download']['proxy'] = "$ip:$port";
	$GLOBALS['download']['proxy_type'] = $type;
}

/**
 * Removes the Proxy specification set with <setDownloadProxy>
 * 
 * @return void
 */
function releaseDownloadProxy()
{
	unset($GLOBALS['download']['proxy']);
	unset($GLOBALS['download']['proxy_type']);
}

/**
 * @shortcut <downloadData>($url,$postdata,$request_header,$cacheTTLsec,$request_timeout,$response_header,$cookie_file)
 */
function sendHTTPRequest($url, $postdata = false, $cacheTTLsec = false, &$response_header = false, $request_header = [], $request_timeout = 120, $cookie_file=false)
{
	return downloadData($url,$postdata,$request_header,$cacheTTLsec,$request_timeout,$response_header,$cookie_file);
}

/**
 * Downloads remote contents into a string.
 * 
 * @param string $url URL to download
 * @param array|string $postdata Data to POST, associative array or string from <http_build_query>
 * @param array|bool $request_header Headers to send along with the request (one entry per header)
 * @param int $cacheTTLsec If set: time to life in cache
 * @param int $request_timeout Timeout in seconds
 * @param array $response_header <b>OUT</b> Will contain the response headers
 * @param string $cookie_file Name of the cookie file to use
 * @return string The downloaded data
 */
function downloadData($url, $postdata = false, $request_header = [], $cacheTTLsec = false, $request_timeout = 120, &$response_header = false, $cookie_file=false)
{
	if( starts_with($url, '//') )
		$url = urlScheme().':'.$url;
	if( $cacheTTLsec )
	{
		$hash = md5($url."|".($postdata ? serialize($postdata) : "").'|'.serialize($request_header));
		$ret = cache_get("CURL_$hash", false, true, false);
		if($ret !== false)
			return $ret;
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, abs($request_timeout));
	curl_setopt($ch, CURLOPT_TIMEOUT, abs($request_timeout));
	if($postdata)
	{
		curl_setopt($ch, CURLOPT_POST, 1);
		if( is_string($postdata) )
		{
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
			if( !is_array($request_header) )
				$request_header = [];
			$request_header[] = "Content-Length: ".strlen($postdata);
            $postdata = trim($postdata);
            if( starts_with($postdata,"{") || starts_with($postdata,"[") )
                $request_header[] = "Content-Type: application/json";
        }
        else
        {
            $contains_file = false;
            foreach( $postdata as $k=>$v )
            {
                if( is_string($v) && starts_with($v,"@") && file_exists(substr($v,1)) )
                    $contains_file = true;
                if( $v instanceof CURLFile )
                    $contains_file = true;
            }
            if( $contains_file )
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            else
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        }
	}
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	if( isset($GLOBALS['download']['proxy']) )
	{
		//log_debug("Using download proxy {$GLOBALS['download']['proxy']}");
		curl_setopt($ch, CURLOPT_PROXY, $GLOBALS['download']['proxy']);
		curl_setopt($ch, CURLOPT_PROXYTYPE, $GLOBALS['download']['proxy_type']);
	}
	
	if( $cookie_file )
	{
		curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie_file);
		curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie_file); 
	}

	if( is_array($request_header) && count($request_header) > 0  )
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);

	$result = curl_exec($ch);	
	$info = curl_getinfo($ch);
	if($result === false)
	{
		log_error('Curl error: ' . curl_error($ch),"url = ",$url,"curl_info = ",$info);
		curl_close($ch);
		return $result;
	}
//	log_info($info);
	curl_close($ch);

    if($response_header !== false)
    {
        $headers = substr($result, 0, $info['header_size']);
        if(is_array($response_header))
        {
            $headers = explode("\r\n", $headers);
            $response_header = [];
            foreach($headers as $h)
            {
                if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#", $h, $out ) )
                {
                    $response_header['status'] = $h;
                    $response_header['response_code'] = intval($out[1]);
                }
                else
                {
                    $hk = explode(':', $h, 2);
                    if(isset($hk[1]))
                        $response_header[trim($hk[0])] = trim($hk[1]);
                }
            }
        }
        else
            $response_header = $headers;
    }
	$result = substr($result, $info['header_size']);

	if($cacheTTLsec)
		cache_set("CURL_$hash", $result, $cacheTTLsec, true, false);
	
	return $result;
}

/**
 * Downloads a file via HTTP(s).
 * 
 * $url may contain username:password for basic http auth, but this is the only supported
 * authentication method.
 * URL Examples:
 * http://myusername:andpassord@somewhere.com/download.php?id=0815
 * https://myusername:andpassord@somewhere.com/image1.jpeg
 * http://www.justplaindomain.com/test.txt
 *
 * NOTES:
 * - This method only supports plain downloads or HTTP basic auth
 * - File will be stored in sys temp dir, but you must NOT use move_uploaded_file, but rename
 * - Return value will follow the rules of $_FILES array, but misses the first dimesion, so
 *   there's no $result['myfile']['tmp_name'], but $result['tmp_name'].
 * - FALSE return value means that the download idi not work, but there may be another error in
 *   $result['error'], when for example the authentication failed.
 *
 * FEEL FREE TO EXTEND THIS FUNCTION TO SUPPORT OTHER AUTHENTICATION METHODS OR HTTP ERROR CODES!
 *
 * @param string $url URL to file/script to download
 * @param array|bool $postdata Optional data to post to the URI
 * @param array|bool $request_header Headers to send along with the request (one entry per header)
 * @param bool $follow_location If true follows redirects
 * @param string $cookie_file Name of the cookie file to use
 * @return array|false Array following the rules of the $_FILES superglobal (but without the first dimension) or FALSE if an error occured. Note that $_FILES may contain an error too!
 */
function downloadFile($url, $postdata = false, $request_header = [], $follow_location=true, $cookie_file=false)
{
	$parsed_url = parse_url($url);
	$GLOBALS['downloadFile_data'] = [];
	$GLOBALS['downloadFile_data']['error'] = 0;
	$GLOBALS['downloadFile_data']['name'] = basename($parsed_url['path']);
    $GLOBALS['downloadFile_data']['response_headers'] = [];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 2 * 60);
	if($postdata)
	{
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	}

	if( $follow_location===true )
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	if( isset($parsed_url['user']) && $parsed_url['user'] && isset($parsed_url['pass']) && $parsed_url['pass'] )
	{
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "{$parsed_url['user']}:{$parsed_url['pass']}");
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	
	if( !$cookie_file && $follow_location )
		$cookie_file = tempnam(system_app_temp_dir(), "downloadFile_cookie_");
	
	if( $cookie_file )
	{
		curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie_file);
		curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie_file); 
	}

	if( count($request_header) > 0  )
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);

	curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'downloadFile_header');

	$GLOBALS['downloadFile_data']['tmp_name'] = tempnam(system_app_temp_dir(), 'DOWNLOAD_');
	$tmp_fp = fopen($GLOBALS['downloadFile_data']['tmp_name'],'w');
	curl_setopt($ch, CURLOPT_FILE, $tmp_fp);

	$result = curl_exec($ch);
	if( curl_errno($ch) )
		$result = false;
	
	curl_close($ch);
	fclose($tmp_fp);

	if( !$result )
		return false;

	$result = $GLOBALS['downloadFile_data'];
	$result['size'] = filesize($GLOBALS['downloadFile_data']['tmp_name']);
	
	unset($GLOBALS['downloadFile_data']);
	return $result;
}

/**
 * @internal Used to capture the downloaded files name
 */
function downloadFile_header($ch, $header)
{
    if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#", $header, $out ) )
    {
        $GLOBALS['downloadFile_data']['response_headers']['status'] = trim($header);
        $GLOBALS['downloadFile_data']['response_headers']['response_code'] = intval($out[1]);
    }
    else
    {
        $hk = explode(':', $header, 2);
        if(isset($hk[1]))
            $GLOBALS['downloadFile_data']['response_headers'][trim($hk[0])] = trim($hk[1]);
    }
    
	if( preg_match('/Content-Disposition:\s*(.*)/i', $header, $res) )
	{
		$p = explode(";",$res[1]);
		foreach( $p as $part )
		{
			$args = explode("=",trim($part));
			if( count($args) < 2 || $args[0] != 'filename' )
				continue;
			
			$name = trim(trim($args[1],"\""));
			if( $name )
			{
				$GLOBALS['downloadFile_data']['name'] = $name;
				break;
			}
		}
	}
	elseif( strtoupper(substr($header, 0, 12)) == "HTTP/1.1 401" )
		$GLOBALS['downloadFile_data']['error'] = UPLOAD_ERR_NO_FILE;
	return strlen($header);
}
