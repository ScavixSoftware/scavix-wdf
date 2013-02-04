<? if( !defined('FRAMEWORK_LOADED') || FRAMEWORK_LOADED != 'uSI7hcKMQgPaPKAQDXg5' ) die('');

define("HOOK_POST_INIT",1);
define("HOOK_POST_INITSESSION",2);
define("HOOK_PRE_EXECUTE",3);
define("HOOK_PRE_RENDER",8);
define("HOOK_POST_EXECUTE",4);
define("HOOK_PRE_FINISH",5);
define("HOOK_POST_MODULE_INIT",6);
define("HOOK_PING_RECIEVED",7);
define("HOOK_COOKIES_REQUIRED",200);
define("HOOK_ARGUMENTS_PARSED",300);
define("HOOK_SYSTEM_DIE",999);

/**
 * Some quick markers to be able to switch application behaviour.
 * Typical code sits in config.php (that's why this block is defined here)
 * and looks like this:
 * 
 */
define("ENVIRONMENT_DEV",'dev');
define("ENVIRONMENT_BETA",'beta');
define("ENVIRONMENT_SANDBOX",'sandbox');
define("ENVIRONMENT_LIVE",'live');
if( !isset($_ENV['CURRENT_ENVIRONMENT']) )
    $_ENV['CURRENT_ENVIRONMENT'] = ENVIRONMENT_LIVE;
function setEnvironment($value){ $_ENV['CURRENT_ENVIRONMENT'] = $value; }
function getEnvironment(){ return $_ENV['CURRENT_ENVIRONMENT']; }
function switchToDev(){ $_ENV['CURRENT_ENVIRONMENT'] = ENVIRONMENT_DEV; }
function switchToBeta(){ $_ENV['CURRENT_ENVIRONMENT'] = ENVIRONMENT_BETA; }
function switchToSandbox(){ $_ENV['CURRENT_ENVIRONMENT'] = ENVIRONMENT_SANDBOX; }
function switchToLive(){ $_ENV['CURRENT_ENVIRONMENT'] = ENVIRONMENT_LIVE; }
function isDev(){ return $_ENV['CURRENT_ENVIRONMENT'] == ENVIRONMENT_DEV; }
function isBeta(){ return $_ENV['CURRENT_ENVIRONMENT'] == ENVIRONMENT_BETA; }
function isSandbox(){ return $_ENV['CURRENT_ENVIRONMENT'] == ENVIRONMENT_SANDBOX; }
function isLive(){ return $_ENV['CURRENT_ENVIRONMENT'] == ENVIRONMENT_LIVE; }
function isNotLive(){ return $_ENV['CURRENT_ENVIRONMENT'] != ENVIRONMENT_LIVE; }
function isDevOrBeta(){ return $_ENV['CURRENT_ENVIRONMENT'] == ENVIRONMENT_DEV || $_ENV['CURRENT_ENVIRONMENT'] == ENVIRONMENT_BETA; }

/**
 * Sets a config value.
 * 
 * uses given arguments for key path like this:
 * cfg_set('system','use_cfg','really',true);
 * will set
 * $CONFIG['system']['use_cfg']['really'] = true;
 * 
 * measured performance agains direct assignment: it is about 5 times
 * slower on a Windows7 x64 system with 8GB RAM.
 * But for 1000 calls it just needs 5ms, so just leave me alone with that.
 */
function cfg_set()
{
	global $CONFIG;
	$args = func_get_args();
	switch( func_num_args() )
	{
		case 2: $CONFIG[$args[0]] = $args[1]; break;
		case 3: $CONFIG[$args[0]][$args[1]] = $args[2]; break;
		case 4: $CONFIG[$args[0]][$args[1]][$args[2]] = $args[3]; break;
		case 5: $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]] = $args[4]; break;
		case 6: $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]] = $args[5]; break;
		case 7: $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]] = $args[6]; break;
		default: throw new Exception("Illegal argument count: ".count($args));
	}
}

/**
 * sets a config value only if it has not been set.
 * see cfg_set() for usage and performance thoughts
 */
function cfg_setd()
{
	global $CONFIG;
	$args = func_get_args();
	switch( func_num_args() )
	{
		case 2: if( !isset($CONFIG[$args[0]]) ) $CONFIG[$args[0]] = $args[1]; break;
		case 3: if( !isset($CONFIG[$args[0]][$args[1]]) ) $CONFIG[$args[0]][$args[1]] = $args[2]; break;
		case 4: if( !isset($CONFIG[$args[0]][$args[1]][$args[2]]) ) $CONFIG[$args[0]][$args[1]][$args[2]] = $args[3]; break;
		case 5: if( !isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]]) ) $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]] = $args[4]; break;
		case 6: if( !isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]]) ) $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]] = $args[5]; break;
		case 7: if( !isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]]) ) $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]] = $args[6]; break;
		default: throw new Exception("Illegal argument count: ".count($args));
	}
}

/**
 * adds an entry to a config value array.
 * see cfg_set() for usage and performance thoughts
 */
function cfg_add()
{
	global $CONFIG;
	$args = func_get_args();
	switch( func_num_args() )
	{
		case 2: $CONFIG[$args[0]][] = $args[1]; break;
		case 3: $CONFIG[$args[0]][$args[1]][] = $args[2]; break;
		case 4: $CONFIG[$args[0]][$args[1]][$args[2]][] = $args[3]; break;
		case 5: $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][] = $args[4]; break;
		case 6: $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][] = $args[5]; break;
		case 7: $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]][] = $args[6]; break;
		default: throw new Exception("Illegal argument count: ".count($args));
	}
}

/**
 * gets a config value.
 * see cfg_set() for usage and performance thoughts
 */
function cfg_get()
{
	global $CONFIG;
	$args = func_get_args();
	switch( func_num_args() )
	{
		case 1: return isset($CONFIG[$args[0]])?$CONFIG[$args[0]]:false;
		case 2: return isset($CONFIG[$args[0]][$args[1]])?$CONFIG[$args[0]][$args[1]]:false;
		case 3: return isset($CONFIG[$args[0]][$args[1]][$args[2]])?$CONFIG[$args[0]][$args[1]][$args[2]]:false;
		case 4: return isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]])?$CONFIG[$args[0]][$args[1]][$args[2]][$args[3]]:false;
		case 5: return isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]])?$CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]]:false;
		case 6: return isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]])?$CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]]:false;
		default: throw new Exception("Illegal argument count: ".count($args));
	}
}

/**
 * gets a config value and uses the last argument given as default if it is not set.
 * see cfg_set() for usage and performance thoughts
 */
function cfg_getd()
{
	global $CONFIG;
	$args = func_get_args();
	switch( func_num_args() )
	{
		case 2: return isset($CONFIG[$args[0]])?$CONFIG[$args[0]]:$args[1];
		case 3: return isset($CONFIG[$args[0]][$args[1]])?$CONFIG[$args[0]][$args[1]]:$args[2];
		case 4: return isset($CONFIG[$args[0]][$args[1]][$args[2]])?$CONFIG[$args[0]][$args[1]][$args[2]]:$args[3];
		case 5: return isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]])?$CONFIG[$args[0]][$args[1]][$args[2]][$args[3]]:$args[4];
		case 6: return isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]])?$CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]]:$args[5];
		case 7: return isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]])?$CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]]:$args[6];
		default: throw new Exception("Illegal argument count: ".count($args));
	}
}

/**
 * deletes a config value
 * see cfg_set() for usage and performance thoughts
 */
function cfg_del()
{
	global $CONFIG;
	$args = func_get_args();
	switch( func_num_args() )
	{
		case 1: unset($CONFIG[$args[0]]); break;
		case 2: unset($CONFIG[$args[0]][$args[1]]); break;
		case 3: unset($CONFIG[$args[0]][$args[1]][$args[2]]); break;
		case 4: unset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]]); break;
		case 5: unset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]]); break;
		case 6: unset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]]); break;
		default: throw new Exception("Illegal argument count: ".count($args));
	}
}

/**
 * checks if a config is set and throws an ex2ception if not.
 * last argument will be used as exception message.
 * see cfg_set() for usage and performance thoughts
 */
function cfg_check()
{
	global $CONFIG;
	$args = func_get_args();
	switch( func_num_args() )
	{
		case 2: if( !isset($CONFIG[$args[0]]) || !$CONFIG[$args[0]] ) throw new Exception($args[1]); break;
		case 3: if( !isset($CONFIG[$args[0]][$args[1]]) || !$CONFIG[$args[0]][$args[1]] ) throw new Exception($args[2]); break;
		case 4: if( !isset($CONFIG[$args[0]][$args[1]][$args[2]]) || $CONFIG[$args[0]][$args[1]][$args[2]] ) throw new Exception($args[3]); break;
		case 5: if( !isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]]) || $CONFIG[$args[0]][$args[1]][$args[2]][$args[3]] ) throw new Exception($args[4]); break;
		case 6: if( !isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]]) || !$CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]] ) throw new Exception($args[5]); break;
		case 7: if( !isset($CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]]) || !$CONFIG[$args[0]][$args[1]][$args[2]][$args[3]][$args[4]][$args[5]] ) throw new Exception($args[6]); break;
		default: throw new Exception("Illegal argument count: ".count($args));
	}
}

/**
 * Sets the application version.
 */
function setAppVersion($major,$minor,$build,$codename="",$nc_salt=false)
{
	$major = intval($major);
	$minor = intval($minor);
	$build = intval($build);
	$GLOBALS['APP_VERSION'] = compact('major','minor','build','codename');
	$GLOBALS['APP_VERSION']['string'] = "$major.$minor.$build";
	if( $codename )
		$GLOBALS['APP_VERSION']['string'] .= " ($codename)";
	$GLOBALS['APP_VERSION']['nc'] = 'nc'.preg_replace('/[^0-9]/', '', md5($GLOBALS['APP_VERSION']['string'].$nc_salt));
}

/**
 * gets the application version.
 * if key is given, returns that part only.
 */
function getAppVersion($key=false)
{
	if( !isset($GLOBALS['APP_VERSION']) )
		setAppVersion (0, 0, 0, "default");
	
	if( $key && isset($GLOBALS['APP_VERSION'][$key]) )
		return $GLOBALS['APP_VERSION'][$key];
	return $GLOBALS['APP_VERSION'];
}

/**
 * Returns true when the current request is SSL secured, else false
 */
function isSSL()
{
	return (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) || (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] == "https");
}

/**
 * Returns http, https, http:// or https:// 
 * by checking the current request and depending on the append_slashes argument
 */
function urlScheme($append_slashes=false)
{
	if( $append_slashes )
		return isSSL()?"https://":"http://";
	return isSSL()?"https":"http";
}

/**
 * Ensures that the given path ends with a directory separator
 */
function system_ensure_path_ending(&$path, $make_realpath=false)
{
	if( $make_realpath )
		$path = realpath($path);
    if( !ends_with($path, DIRECTORY_SEPARATOR) )
        $path .= DIRECTORY_SEPARATOR;
}

/**
 * Checks if a string starts with another one.
 * @param string $string String to check
 * @param string $start The start to be checked
 * @return bool true|false
 */
function starts_with($string,$start)
{
	return strpos($string,$start) === 0;
}

/**
 * Checks if a string ends with another one.
 * @param string $string String to check
 * @param string $end The end to be checked
 * @return bool true|false
 */
function ends_with($string,$end)
{
	return substr($string,strlen($string)-strlen($end)) == $end;
}

/**
 * Tests if the first given argument is one of the others.
 * This is a shortcut for in_array.
 * Use like this:
 * is_in('nice','Hello','nice','World')
 */
function is_in()
{
	$args = func_get_args();
	$needle = array_shift($args);
	return in_array($needle,$args);
}

/**
 * Returns array value at key if it exists, else default is returned.
 * This is shortcut for
 * $val = (array_key_exists($key,$array) && $array[$key])?$array[$key]:$default;
 * @param array $array The source array
 * @param mixed $key The key to be checked
 * @param mixed $default Default value to return if array does not contain key
 * @return mixed
 */
function array_val($array,$key,$default=null)
{
	if( array_key_exists($key, $array) )
		return $array[$key];
	return $default;
}

/**
 * Checks if an array contains key and if the value is needle
 * This is shortcut for
 * if( array_key_exists($key,$array) && $array[$key]==$needle  ) 
 *     ...;
 * @param array $array The source array
 * @param mixed $key The key to be checked
 * @param mixed $needle The value to check against
 * @return bool
 */
function array_val_is($array,$key,$needle)
{
	if( array_key_exists($key, $array) )
		return $array[$key] == $needle;
	return false;
}

/**
 * Tests if 'we are' currently handling an ajax request
 */
function system_is_ajax_call()
{
	if( array_val_is($_SERVER, 'HTTP_X_REQUESTED_WITH', 'xmlhttprequest') )
		return true;
	return 
		isset($_REQUEST['request_id']) && isset($_SESSION['request_id']) &&
		$_REQUEST['request_id'] == $_SESSION['request_id'];
}

/**
 * Strips given tags from string
 * @see http://www.php.net/manual/en/function.strip-tags.php#93567
 * @param string $str String to strip
 * @param array $tags Tags to be stripped
 * @return string cleaned up string
 */
function strip_only(&$str, $tags)
{
	if(isset($str) && is_array($str))
		return $str;
    if(!is_array($tags))
	{
        $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
        if(end($tags) == '') array_pop($tags);
    }

	$size = sizeof($tags);
	$keys = array_keys($tags);
	for ($i=0; $i<$size; $i++)
	{
		$tag = $tags[$keys[$i]];
		if(isset($tag) && is_array($tag))
			$str = strip_only($str, $tag);
		else
		{
			if(stripos($str, $tag) !== false)
				$str = preg_replace('#</?'.$tag.'[^>]*>#is', '', $str);
		}
	}
	return $str;
}

/**
 * Returns the ordinal number for a char.
 * Code 'stolen' from php.net ;)
 * The following uniord function is simpler and more efficient than any of the ones suggested without
 * depending on mbstring or iconv.
 * It's also more validating (code points above U+10FFFF are invalid; sequences starting with 0xC0 and 0xC1 are
 * invalid overlong encodings of characters below U+0080),
 * though not entirely validating, so it still assumes proper input.
 * @see http://de3.php.net/manual/en/function.ord.php#77905
 * @param char $c Character to get ORD of
 * @return int The ORD code
 */
function uniord($c)
{
	$h = ord($c{0});
	if ($h <= 0x7F) {
		return $h;
	} else if ($h < 0xC2) {
		return false;
	} else if ($h <= 0xDF) {
		return ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
	} else if ($h <= 0xEF) {
		return ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6
								 | (ord($c{2}) & 0x3F);
	} else if ($h <= 0xF4) {
		return ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12
								 | (ord($c{2}) & 0x3F) << 6
								 | (ord($c{3}) & 0x3F);
	} else {
		return false;
	}
}

/**
 * Here's a PHP function which does just that when given a UTF-8 encoded string. It's probably not the best way to do it, but it works:
 * @see http://www.iamcal.com/understanding-bidirectional-text/
 * Uncommented PDF correction because it's too weak and kills some currency symbols in CurrencyFormat::Format
 */
function unicode_cleanup_rtl($data)
{
	#
	# LRE - U+202A - 0xE2 0x80 0xAA
	# RLE - U+202B - 0xE2 0x80 0xAB
	# LRO - U+202D - 0xE2 0x80 0xAD
	# RLO - U+202E - 0xE2 0x80 0xAE
	#
	# PDF - U+202C - 0xE2 0x80 0xAC
	#

	$explicits	= '\xE2\x80\xAA|\xE2\x80\xAB|\xE2\x80\xAD|\xE2\x80\xAE';
//	$pdf		= '\xE2\x80\xAC';

	preg_match_all("!$explicits!",	$data, $m1, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
	//preg_match_all("!$pdf!", 	$data, $m2, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
	$m2 = array();

	if (count($m1) || count($m2)){

		$p = array();
		foreach ($m1 as $m){ $p[$m[0][1]] = 'push'; }
		foreach ($m2 as $m){ $p[$m[0][1]] = 'pop'; }
		ksort($p);

		$offset = 0;
		$stack = 0;
		foreach ($p as $pos => $type){

			if ($type == 'push'){
				$stack++;
			}else{
				if ($stack){
					$stack--;
				}else{
					# we have a pop without a push - remove it
					$data = substr($data, 0, $pos-$offset)
						.substr($data, $pos+3-$offset);
					$offset += 3;
				}
			}
		}

		# now add some pops if your stack is bigger than 0
		for ($i=0; $i<$stack; $i++){
			$data .= "\xE2\x80\xAC";
		}

		return $data;
	}

	return $data;
}

/**
 * @see http://stackoverflow.com/a/3742879
 */
function utf8_clean($str)
{
    return iconv('UTF-8', 'UTF-8//IGNORE', $str);
}

/**
 * Return the client's IP address
 * @return string IP address
 */
function get_ip_address()
{
//	if( isDev() )
//		return "66.135.205.14";	// US (ebay.com)
//		return "46.122.252.60"; // ljubljana
//		return "190.172.82.24"; // argentinia? (#5444)
//		return "84.154.26.132"; // probably invalid ip from munich
//		return "203.208.37.104"; // google.cn
//		return "62.215.83.54";	// kuwait
//		return "41.250.146.224";	// Morocco (rtl!)
//		return "85.13.144.94";	// pamfax.biz = DE
//		return "66.135.205.14";	// US (ebay.com)
//		return "121.243.179.122";	// india
//		return "109.253.21.90";	// invalid (user says UK)
//		return "82.53.187.74";	// IT
//		return "190.172.82.24";	// AR
//		return "99.230.167.125";	// CA
//		return "95.220.134.145";	// N/A
//		return "194.126.108.2";	// Tallinn/Estonia (Skype static IP)

	global $DETECTED_CLIENT_IP;

	if( isset($DETECTED_CLIENT_IP) )
		return $DETECTED_CLIENT_IP;

	$proxy_headers = array(
		'HTTP_VIA',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_FORWARDED',
		'HTTP_CLIENT_IP',
		'HTTP_FORWARDED_FOR_IP',
		'VIA',
		'X_FORWARDED_FOR',
		'FORWARDED_FOR',
		'X_FORWARDED',
		'FORWARDED',
		'CLIENT_IP',
		'FORWARDED_FOR_IP',
		'HTTP_PROXY_CONNECTION',
		'REMOTE_ADDR' // REMOTE_ADDR must be last -> fallback
	);

	foreach( $proxy_headers as $ph )
	{
		if( !empty($_SERVER) && isset($_SERVER[$ph]) )
		{
			$DETECTED_CLIENT_IP = $_SERVER[$ph];
			break;
		}
		elseif( !empty($_ENV) && isset($_ENV[$ph]) )
		{
			$DETECTED_CLIENT_IP = $_ENV[$ph];
			break;
		}
		elseif( @getenv($ph) )
		{
			$DETECTED_CLIENT_IP = getenv($ph);
			break;
		}
	}

	if( !isset($DETECTED_CLIENT_IP) )
		return false;

	$is_ip = preg_match('/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/',$DETECTED_CLIENT_IP,$regs);
	if( $is_ip && (count($regs) > 0) )
		$DETECTED_CLIENT_IP = $regs[1];
	return $DETECTED_CLIENT_IP;
}

function classpath_add($path, $recursive=true, $part=false)
{
	global $CONFIG;
	system_ensure_path_ending($path,true);
	if( !$part )
		$part = $CONFIG['system']['application_name'];
	
	$CONFIG['class_path'][$part][] = $path;
	if( !in_array($part, $CONFIG['class_path']['order']) )
		$CONFIG['class_path']['order'][] = $part;
			
	if( $recursive )
	{
		foreach( glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT) as $sub )
			classpath_add($sub, true, $part);
	}
}
