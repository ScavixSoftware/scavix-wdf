<?
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
 
function translation2_init()
{
	global $CONFIG;
	if( !in_array("localization",$CONFIG['system']['modules']) )
		system_die("Missing dependency 'localization'!");

	$GLOBALS['__unknown_constants'] = array();
	$GLOBALS['__translate_functions'] = array();

//	if( !isset($CONFIG['translation']['default_language']) )
//		$CONFIG['translation']['default_language'] = 'en';

	if( !isset($CONFIG['translation']['datasource']) )
		$CONFIG['translation']['datasource'] = 'system';

	if( !isset($CONFIG['translation']['bufferstrings']) )
		$CONFIG['translation']['bufferstrings'] = true;

	if( !isset($CONFIG['translation']['searchpatterns']) )
		$CONFIG['translation']['searchpatterns'] = array();

	if( !isset($CONFIG['translation']['minlangtransrate']) )
		$CONFIG['translation']['minlangtransrate'] = 0.75;

	if( !isset($CONFIG['localization']['default_language']) )
		$CONFIG['localization']['default_language'] = "en";

	if( !isset($CONFIG['translation']['detect_ci_callback']) )
		$CONFIG['translation']['detect_ci_callback'] = false;

	$CONFIG['translation']['searchpatterns'] = array_merge(
		$CONFIG['translation']['searchpatterns'],
		array("WINDOW_","TITLE_","BTN_","MSG_","TXT_","ERR_","LAB_")
	);

	if( isset($_REQUEST["reloadstrings"]) && intval($_REQUEST["reloadstrings"]) == 1 )
		register_hook_function(HOOK_POST_INIT,'delete_buffered_strings');

	// build reg pattern once:
	$reg = array();
	foreach( $CONFIG['translation']['searchpatterns'] as $pat )
		$reg[] = '('.$pat.'[a-zA-Z0-9_-]+)(\[[^\]]+\])*';
	$reg = "/".implode("|",$reg)."/";
	$GLOBALS['__translate_regpattern'] = $reg;

}

function delete_buffered_strings()
{
	unset($_SESSION["buffered_strings".(defined("_nc") ? "_"._nc : "")]);
}

function translation_add_function($func)
{
	$GLOBALS['__translate_functions'][] = $func;
}

function __translate_callback($matches)
{
	global $__unknown_constants;

	$mod = array_pop($matches);
	$val = array_pop($matches);
	$do_js = false;
	$unbuffered = false;
	switch( $mod )
	{
		case '[NT]':
			return $val;
		case '[NC]':
			$unbuffered = true;
			break;
		case '[JS]':
			$do_js = true;
			break;
		default:
//			log_debug("Unknown tm? $mod");
			if( preg_match('/^\[.*\]$/', $mod) )
				log_debug("Unknown translation modifier: $mod");
			else
				$val = $mod;
			break;
	}
//	log_debug("post = $mod/$val");

	if( isset($__unknown_constants["k".$val]) )
		return $val."?";

	$trans = getString($val,null,$unbuffered);
	if( $trans == "$val?" )
	{
		$__unknown_constants["k".$val] = $val;
		return $trans;
	}

	if( $do_js && system_is_module_loaded('javascript') )
		return jsEscape($trans);
	return $trans;
}

function __translate($text)
{
	global $__unknown_constants;
	
//log_debug($text);
	// TODO: reactivate loop regarding unknown constants and thos that shall not be translated
	//while( preg_match($reg, $text) )
	{
		if(!is_string($text))
			return $text;

		$text = preg_replace_callback(
			$GLOBALS['__translate_regpattern'],
			'__translate_callback',
			$text
		);
	}
	
	if( ends_with($text, '[NT]') )
		$text = substr($text, 0, -4);

	if( count($__unknown_constants) > 0 )
		log_debug("Unknown text constants: ".my_var_export(array_values($__unknown_constants)));
//log_debug($text);
	return $text;
}

function detect_language()
{
	global $CONFIG;
	if( !$CONFIG['translation']['detect_ci_callback'] )
	{
		$ci = Localization::detectCulture();
		$ci = $ci->ResolveToLanguage();
	}
	else
		$ci = $CONFIG['translation']['detect_ci_callback']();

	$GLOBALS['current_language'] = ($ci instanceof CultureInfo)? $ci->Iso2 : $ci;	
}

function getStringLang($lang,$constant,$arreplace = null, $unbuffered = false)
{
	$mem = isset($GLOBALS['current_language'])?$GLOBALS['current_language']:false;
	if(!is_null($lang) && (trim($lang) != ""))
		$GLOBALS['current_language'] = $lang;
	$res = getString($constant, $arreplace,$unbuffered);
	if( $mem )
		$GLOBALS['current_language'] = $mem;
	return $res;
}

/**
 * returns a localized string from the current user's language. replaces all placeholders in string from arreplace
 * i.e. TXT_TEST => "this is a {tt}" with arreplace = array("{tt}" => "test") => returns "this is a test"
 * buffers all strings of the user in _SESSION on first access of this function
 * @global <type> $CONFIG
 * @param <string> $constant text constant. i.e. TXT_...
 * @param <array> $arreplace
 * @param <bool> $unbuffered reload from session instead of from cache buffer of current script
 * @param <string> $encoding E.g. cp1252. Default "null" => UTF-8 will be returned
 * @return <string>
 */
function getString($constant, $arreplace = null, $unbuffered = false, $encoding = null)
{
	global $CONFIG;
    if( !isset($GLOBALS['current_language']) )
		detect_language();

	$lang = $GLOBALS['current_language'];
	$cachekey = "buffered_strings".(defined("_nc") ? "_"._nc : "");
	// do not decide by localization module which shold be default because
	// the def-language have to be a fix language (usually we´ll user english)
	$deflang = $CONFIG['localization']['default_language'];
	//log_debug("getString: $constant (lang: $lang deflang: $deflang)");
	$reloadstrings = ( isset($_REQUEST["reloadstrings"]) && intval($_REQUEST["reloadstrings"]) == 1 );
	$useglobalcache = system_is_module_loaded("globalcache");		// globalcache feels like it's slower here?????
	$insession = (session_id() != "");
	$arStrings = array();

	if($useglobalcache && $insession)
		unset($_SESSION[$cachekey][$lang]);

	if( $CONFIG['translation']['bufferstrings'] && !$unbuffered )
	{
//		log_debug($lang);
//		log_debug($_SESSION[$cachekey][$lang]);
//		die();
		if( $reloadstrings )
		{
			if($useglobalcache)
				globalcache_delete("mod_translation2_buffer_$lang");
			elseif($insession)
				unset($_SESSION[$cachekey][$lang]);
		}

		// use already buffered strings
		if($useglobalcache)
			$arStrings = globalcache_get("mod_translation2_buffer_$lang");

		if( ($useglobalcache && ($arStrings === false)) || (!$useglobalcache && $insession && !isset($_SESSION[$cachekey][$lang])) )
		{
			$arStrings = array();
            $ds = model_datasource($CONFIG['translation']['datasource']);
			$sql = "SELECT constant,language_code,content FROM translate_strings WHERE language_code ".($deflang == $lang ? "=?0" : "IN(?0,?1)")." AND deleted IS NULL AND NOT content IS NULL AND content<>''";
			if( $reloadstrings )
				$rs = $ds->ExecuteSql($sql, ($deflang == $lang ? array($lang) : array($deflang,$lang)));
			else
				$rs = $ds->CacheExecuteSql($sql, ($deflang == $lang ? array($lang) : array($deflang,$lang)));
			log_debug("reloading strings to buffer: $lang");

            while( !$rs->EOF )
			{
				list($c,$lc,$content) = $rs->fields;
				if( $lc == $lang )
				{
					if(!is_null($content) && $content != "" )
						$arStrings[$c] = $content;
				}
				elseif( !isset($arStrings[$c]) )
					$arStrings[$c] = $content;
				$rs->MoveNext();
			}
//			log_debug($arStrings);
			if($useglobalcache)
				globalcache_set("mod_translation2_buffer_$lang", $arStrings, 60 * 60);
			elseif($insession)
				$_SESSION[$cachekey][$lang] = $arStrings;
		}
		else
		{
			// use already buffered strings
			if(!$useglobalcache && $insession)
				$arStrings = $_SESSION[$cachekey][$lang];
		}
	}
	else
	{
		$ds = model_datasource($CONFIG['translation']['datasource']);
		$sql = "SELECT constant,language_code,content FROM translate_strings WHERE constant=?0 AND language_code ".($deflang == $lang ? "=?0" : "IN(?0,?1)")." AND deleted IS NULL AND NOT content IS NULL AND content<>''";
		$rs = $ds->ExecuteSql($sql, ($deflang == $lang ? array($constant, $lang) : array($constant, $deflang,$lang)));
		while( !$rs->EOF )
		{
			list($c,$lc,$content) = $rs->fields;
			if( $lc == $lang )
			{
				if(!is_null($content) && $content != "" )
					$arStrings[$constant] = $content;
			}
			$rs->MoveNext();
		}
	}

	if(isset($arStrings[$constant]))
    {
		// already generated this string with exactly these replacements
        $res = $arStrings[$constant];
        $res = ReplaceVariables($res, $arreplace);
    }
    else
    {
		// replace the placeholders
        $res = ReplaceVariables($constant, $arreplace);
        if($res == $constant)
        {
//            log_debug("unknown text constant for $lang: $constant","UNKNOWN CONSTANT");
//			log_debug($arStrings);
//			log_debug($arreplace);
            $res = htmlspecialchars($constant)."?";
        }
    }

	if(!is_null($encoding))
        $res = iconv("UTF-8", $encoding."//IGNORE", $res);

	if( $reloadstrings )
		unset($_REQUEST["reloadstrings"]);

	return $res;
}

function getJsString($constant, $arreplace = null, $unbuffered = false, $encoding = null)
{
	$res = getString($constant, $arreplace, $unbuffered, $encoding);
	if( system_is_module_loaded("javascript") )
		$res = jsEscape($res);
	return $res;
}

function ReplaceVariables($text, $arreplace = null)
{
	if(!is_null($arreplace))
		$text = str_replace(array_keys($arreplace), array_values($arreplace), $text);
//	$trace = debug_backtrace(false);
	foreach( $GLOBALS['__translate_functions'] as &$func )
	{
//		foreach($trace as $btfunc)
//			if($btfunc["function"] != $func)
				$text = call_user_func($func, $text);
	}
	return $text;
}

function getAvailableLanguages( $min_percent_translated=false )
{
	global $CONFIG;
	$key = "getAvailableLanguages_".$min_percent_translated;
	if(isset($GLOBALS[$key]))
		return $GLOBALS[$key];
	$useglobalcache = system_is_module_loaded('globalcache');
	if($useglobalcache)
	{
		// look for file in global cache
		$ret = globalcache_get("mod_translation2_".$key);
		if($ret != false)
			return $ret;
	}
	$ds = model_datasource($CONFIG['translation']['datasource']);
	
	$arr_lang = array();
	$res = $ds->CacheExecuteSql("SELECT DISTINCT language_code FROM translate_strings");
	if( $min_percent_translated===false )
		$min_percent_translated = $CONFIG['translation']['minlangtransrate'];
	while( !$res->EOF )
	{
		if(getLangTransRate($res->fields['language_code']) >= $min_percent_translated )
			$arr_lang[] = $res->fields['language_code'];
		$res->MoveNext();
	}
	$GLOBALS[$key] = $arr_lang;
	if($useglobalcache)
		globalcache_set("mod_translation2_".$key, $arr_lang, $CONFIG['system']['cache_ttl']);
	return $arr_lang;
}

function checkForExistingLanguage($cultureCode)
{
	$key = "existing_language_check_".$cultureCode;
	if(isset($GLOBALS[$key]))
		return $GLOBALS[$key];
	$arr_lang = array_flip(getAvailableLanguages());
	if( isset($arr_lang[$cultureCode]) )
	{
		$GLOBALS[$key] = $cultureCode;
		return $cultureCode;
	}
	$parentCulture = substr($cultureCode,0,2); // this may match for many, but not for chinese! so fall back below
	if( isset($arr_lang[$parentCulture]) )
	{
		$GLOBALS[$key] = $parentCulture;
		return $parentCulture;
	}
	
	$ci = Localization::getCultureInfo($cultureCode); // this is fallback for above, clean implementation
	if($ci !== false)
	{
		$ci = $ci->ResolveToLanguage();
		if( isset($arr_lang[$ci->Code]) )
		{
			$GLOBALS[$key] = $ci->Code;
			return $ci->Code;
		}
	}
	
	$GLOBALS[$key] = false;
	return false;
}

function getLangTransRate($lang)
{
	global $CONFIG;
	if(!isset($CONFIG['translation']['default_language']))
		$CONFIG['translation']['default_language'] = "en";
	$deflang = $CONFIG['translation']['default_language'];
	if($lang == $deflang)
		return 1;
	$ds = model_datasource($CONFIG['translation']['datasource']);
	$table = "translate_strings";
	$sql = "SELECT (SELECT COUNT(*) FROM $table WHERE language_code=?0 AND deleted IS NULL AND content!='')/(SELECT COUNT(*) FROM $table WHERE language_code=?1 AND deleted IS NULL AND content!='') AS rate;";
	try {
		$rs_rate = $ds->CacheExecuteSql($sql, array($lang, $deflang));
//		log_debug("$lang ".$rs_rate->fields['rate']);
		return $rs_rate->fields['rate'];
	}
	catch(Exception $e)
	{
		return false;
	}
}

?>