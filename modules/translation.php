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
 
function translation_init()
{
	global $CONFIG;
	if( !in_array("localization",$CONFIG['system']['modules']) )
		system_die("Missing dependency 'localization'!");

	$GLOBALS['__unknown_constants'] = array();
	$GLOBALS['__translate_functions'] = array();

	if( !isset($CONFIG['translation']['data_path']) )
		system_die('Please define $CONFIG["translation"]["data_path"]');

    if( isset($CONFIG['translation']['sync']['provider']) && $CONFIG['translation']['sync']['provider'] )
    {
        if( !isset($CONFIG['translation']['sync']['datasource']) )
            $CONFIG['translation']['sync']['datasource'] = 'internal';
        
        $CONFIG['class_path']['system'][] = dirname(__FILE__).'/translation/';
        $CONFIG['class_path']['system'][] = dirname(__FILE__).'/translation/'.strtolower($CONFIG['translation']['sync']['provider']).'/';
    }
    else
        $CONFIG['translation']['sync']['datasource'] = false;
    
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

	// build reg pattern once:
//	$chars = "A-Z0-9_";
//	$reg = "/(WINDOW_[$chars]+)|(TITLE_[$chars]+)|(BTN_[$chars]+)|(MSG_[$chars]+|(TXT_[$chars]+)|(ERR_[$chars]+)|(LAB_[$chars]+))/";
	$reg = array();
	foreach( $CONFIG['translation']['searchpatterns'] as $pat )
		$reg[] = '('.$pat.'[a-zA-Z0-9_-]+)(\[[^\]]+\])*';
	$reg = "/".implode("|",$reg)."/";
	$GLOBALS['__translate_regpattern'] = $reg;
    
    system_ensure_path_ending($CONFIG['translation']['data_path']);

	admin_register_handler('New strings','TranslationAdmin','NewStrings');
	admin_register_handler('Fetch strings','TranslationAdmin','Fetch');
}

function translation_do_includes()
{
	global $CONFIG;
//	include($CONFIG['translation']['data_path']."translation.inc.php");
//	include($CONFIG['translation']['data_path'].$CONFIG['localization']['default_language'].".inc.php");
	if( file_exists($CONFIG['translation']['data_path'].$GLOBALS['current_language'].".inc.php") )
	{
		include($CONFIG['translation']['data_path'].$GLOBALS['current_language'].".inc.php");
		$GLOBALS['translation']['included_language'] = $GLOBALS['current_language'];
	}
	else
	{
		$GLOBALS['translation']['included_language'] = $CONFIG['localization']['default_language'];
		if( file_exists($CONFIG['translation']['data_path'].$CONFIG['localization']['default_language'].".inc.php") )
		{
			include($CONFIG['translation']['data_path'].$CONFIG['localization']['default_language'].".inc.php");
		}
		else
		{
			log_fatal("No translations found!",$CONFIG['translation']['data_path'].$CONFIG['localization']['default_language'].".inc.php");
			$GLOBALS['translation']['properties'] = array();
			$GLOBALS['translation']['strings'] = array();
		}
	}

//	$GLOBALS['translation']['known_constants'] = array_keys($GLOBALS['translation']['strings']);
//	if( !is_null($null) )
//		globalcache_set('translation_known_constants',$GLOBALS['translation']['known_constants']);
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
	$as_attribute = false;
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
		case '[AT]':
			$as_attribute = true;
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
	if( $as_attribute )
		return htmlentities($trans,ENT_QUOTES,'UTF-8',false);
	return $trans;
}

function __translate_sort_constants($a,$b)
{
	$la = strlen($a);
	$lb = strlen($b);
	return ($la==$lb)?0:(($la<$lb)?1:-1);
}

function __translate($text)
{
	global $CONFIG, $__unknown_constants;
	
	// TODO: reactivate loop regarding unknown constants and thos that shall not be translated
	//while( preg_match($GLOBALS['__translate_regpattern'], $text) )
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
    {
        if( $CONFIG['translation']['sync']['datasource'] )
        {
            $ds = model_datasource($CONFIG['translation']['sync']['datasource']);
			$ds->ExecuteSql("CREATE TABLE IF NOT EXISTS wdf_unknown_strings (
				term VARCHAR(255) NOT NULL,
				last_hit DATETIME NOT NULL,
				hits INT DEFAULT 0,
				PRIMARY KEY (term))");
			
            $now = $ds->Driver->Now();
            $sql1 = "INSERT OR IGNORE INTO wdf_unknown_strings(term,last_hit,hits)VALUES(?,$now,0);";
            $sql2 = "UPDATE wdf_unknown_strings SET last_hit=$now, hits=hits+1 WHERE term=?;";
            foreach( $__unknown_constants as $uc )
            {
                $ds->Execute($sql1,$uc);
                $ds->Execute($sql2,$uc);
            }
        }
        else
            log_debug("Unknown text constants: ".my_var_export(array_values($__unknown_constants)));
    }

	return $text;
}

function __noTranslate_callback($matches)
{
	global $__unknown_constants;

	$mod = array_pop($matches);
	$val = array_pop($matches);
	if( $mod != "[NT]" )
		return $mod."[NT]";
	return $val."[NT]";
}

function noTranslate($content)
{
	$res = preg_replace_callback(
		$GLOBALS['__translate_regpattern'],
		'__noTranslate_callback',
		$content
	);
//	log_debug("noTranslate($content) -> ",$res);
	return $res;
}

/**
 * Return Iso2 Code of the detected language
 */
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
	return $GLOBALS['current_language'];
}

/**
 * Sets the language and return the current one.
 * If there was no current yet, detect_language() is called.
 */
function translation_set_language($code_or_ci)
{
	if( !isset($GLOBALS['current_language']) )
		detect_language();
	$res = $GLOBALS['current_language'];
	if( $code_or_ci instanceof CultureInfo )
		$GLOBALS['current_language'] = $code_or_ci->ResolveToLanguage()->Code;
	else
		$GLOBALS['current_language'] = $code_or_ci;
	return $res;
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

function _text($constant, $arreplace = null, $unbuffered = false, $encoding = null)
{
	return getString($constant,$arreplace,$unbuffered,$encoding);
}

function getString($constant, $arreplace = null, $unbuffered = false, $encoding = null)
{
	if( !$arreplace )
		return getStringOrig($constant,$arreplace,$unbuffered,$encoding);
	$n = array();
	foreach( $arreplace as $k=>$v )
		if( $k[0] == '{' ) $n[$k] = $v; else $n['{'.$k.'}'] = $v;
	return getStringOrig($constant,$n,$unbuffered,$encoding);
}

/**
 * returns a localized string from the current user's language. replaces all placeholders in string from arreplace
 * i.e. TXT_TEST => "this is a {tt}" with arreplace = aray("{tt}" => "test") => returns "this is a test"
 * buffers all strings of the user in _SESSION on first access of this function
 * @global <type> $CONFIG
 * @param <string> $constant text constant. i.e. TXT_...
 * @param <array> $arreplace
 * @param <bool> $unbuffered reload from session instead of from cache buffer of current script
 * @param <string> $encoding E.g. cp1252. Default "null" => UTF-8 will be returned
 * @return <string>
 */
function getStringOrig($constant, $arreplace = null, $unbuffered = false, $encoding = null)
{
	global $CONFIG;
	
	// common 'ensure includes'-block. repeated multiple times in this file for performance reasons
	if( !isset($GLOBALS['current_language']) )
		detect_language();
	if( !isset($GLOBALS['translation']['included_language']) || $GLOBALS['translation']['included_language'] != $GLOBALS['current_language'] )
		translation_do_includes();
	
	if( !$unbuffered )
	{
		$key = "lang_{$GLOBALS['translation']['included_language']}_$constant".md5($constant.serialize($arreplace).$GLOBALS['current_language'].$encoding);
		$res = cache_get($key);
        if( $res !== false )
			return $res;
	}
	
	$GLOBALS['translation']['skip_buffering_once'] = false;
	if( isset($GLOBALS['translation']['strings'][$constant]) )
    {
        $res = $GLOBALS['translation']['strings'][$constant];
        $res = ReplaceVariables($res, $arreplace);
    }
    else // $constant is not really a constant, but just a string, so we just need to replace the vars in there
    {
        $res = ReplaceVariables($constant, $arreplace);
        if($res == $constant)
        {
            $res = htmlspecialchars($constant)."?";
			$GLOBALS['translation']['skip_buffering_once'] = true;
        }
    }

	if(!is_null($encoding))
        $res = iconv("UTF-8", $encoding."//IGNORE", $res);
	
	if( !$GLOBALS['translation']['skip_buffering_once'] && preg_match_all($GLOBALS['__translate_regpattern'], $res, $m) )
		$res = __translate($res);
	
	if( isset($key) && !$GLOBALS['translation']['skip_buffering_once'] )
		cache_set($key,$res);

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
	foreach( $GLOBALS['__translate_functions'] as &$func )
		$text = call_user_func($func, $text);
	return $text;
}

function getAvailableLanguages( $min_percent_translated=false )
{
	global $CONFIG;
	
	// common 'ensure includes'-block. repeated multiple times in this file for performance reasons
	if( !isset($GLOBALS['current_language']) )
		detect_language();
	if( !isset($GLOBALS['translation']['included_language']) || $GLOBALS['translation']['included_language'] != $GLOBALS['current_language'] )
		translation_do_includes();
	
	if( $min_percent_translated === false )
		$min_percent_translated = $CONFIG['translation']['minlangtransrate'];
	elseif( $min_percent_translated > 1 )
		$min_percent_translated /= 100;
	
	$key = "getAvailableLanguages_".$min_percent_translated;
	if(isset($GLOBALS[$key]))
		return $GLOBALS[$key];
	
	$res = array();
	foreach( $GLOBALS['translation']['properties'] as $lang=>$data )
		if( $data['percentage_empty'] < 1 - $min_percent_translated )
			$res[] = $lang;
	$GLOBALS[$key] = $res;
	return $res;
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

function translation_known_constants()
{
	global $CONFIG;
	
    $res = cache_get('translation_known_constants');
	if( $res )
		return $res;
	
	if( !isset($GLOBALS['translation']['known_constants']) )
	{
		// common 'ensure includes'-block. repeated multiple times in this file for performance reasons
		if( !isset($GLOBALS['current_language']) )
			detect_language();
		if( !isset($GLOBALS['translation']['included_language']) || $GLOBALS['translation']['included_language'] != $GLOBALS['current_language'] )
			translation_do_includes();
		$GLOBALS['translation']['known_constants'] = array_keys($GLOBALS['translation']['strings']);
	}
	
	cache_set('translation_known_constants',$GLOBALS['translation']['known_constants']);
	return $GLOBALS['translation']['known_constants'];
}

function translation_skip_buffering()
{
	$GLOBALS['translation']['skip_buffering_once'] = true;
}

function translation_string_exists($constant)
{
	$known = translation_known_constants();
	return in_array($constant, $known);
}

function translation_ensure_nt($text_potentially_named_like_a_constant)
{
	if( !translation_string_exists($text_potentially_named_like_a_constant) )
		return $text_potentially_named_like_a_constant;
	return $text_potentially_named_like_a_constant."[NT]";
}