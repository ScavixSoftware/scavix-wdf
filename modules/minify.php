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

function minify_init()
{
	global $CONFIG;
	classpath_add(__DIR__."/minify/");
	admin_register_handler('Minify','MinifyAdmin','Start');
	
	cfg_check('minify','target_path','Minify module needs a target_path');
	cfg_check('minify','base_name','Minify module needs a base_name');
	cfg_check('minify','url','Minify module needs an url');
	
	$target_base_name = cfg_get('minify','target_path');
	system_ensure_path_ending($target_base_name,true);
	$target_base_name .= cfg_get('minify','base_name');
	$base_uri = cfg_get('minify','url');
	use_minified_file($target_base_name, 'js', $base_uri);
	use_minified_file($target_base_name, 'css', $base_uri);
}

function minify_all($paths,$target_base_name,$nc_argument)
{
	$target_base_name .= (isSSL()?".1":".0");
	foreach( glob($target_base_name.".*.*") as $f )
		unlink($f);
	
	$v = preg_replace('/[^\d]*/', "", $nc_argument);
	minify_js($paths,$target_base_name.".$v.js");
	minify_css($paths,$target_base_name.".$v.css",$v);
}

function use_minified_file($target_base_name,$kind,$base_uri)
{
	global $CONFIG;
	$target_base_name .= (isSSL()?".1":".0");
	foreach( glob($target_base_name.".*.$kind") as $f )
	{
		$CONFIG["use_compiled_$kind"] = $base_uri.basename($f);
		return;
	}
	unset($CONFIG["use_compiled_$kind"]);
}

function minify_js($paths,$target_file)
{
	require_once(dirname(__FILE__)."/minify/jsmin.php");
	$files = minify_collect_files($paths, 'js');
	log_debug("JS files to minify: ",$files);
	//die("stopped");
	$code = "";
	foreach( $files as $f )
	{
		$js = sendHTTPRequest($f,false,false,$response_header);
		if( mb_detect_encoding($js) != "UTF-8" )
			$js = mb_convert_encoding($js, "UTF-8");
		if( stripos($response_header,"404 Not Found") !== false )
			continue;
		
		$js = "/* FILE: $f */\n$js";
		if( !isset($_GET['nominify']) )
		{
			try {
				$code .= jsmin::minify($js)."\n";
			} catch(Exception $ex)
			{
				log_error("EXCEPTION occured in jsmin::minify ($js)", $ex);
				$code .= $js."\n";	
			}
		}
		else
			$code .= $js."\n";
	}
	file_put_contents($target_file, $code);
}

function minify_css($paths,$target_file,$nc_argument=false)
{
	require_once(dirname(__FILE__)."/minify/cssmin.php");
	global $current_url;
	$files = minify_collect_files($paths, 'css');
	log_debug("CSS files to minify: ",$files);	
	//die("stopped");
	$code = "";
	$res = array();
	$map = array();
	
	foreach( $files as $f )
	{
		if( !$f )
			continue;
		$css = sendHTTPRequest($f,false,false,$response_header);
		if( stripos($response_header,"404 Not Found") !== false )
			continue;
		if( mb_detect_encoding($css) != "UTF-8" )
			$css = mb_convert_encoding($css, "UTF-8");
		$current_url = parse_url($f);
		$current_url['onlypath'] = dirname($current_url['path'])."/";
		
		if( $nc_argument )
		{
			$current_url['onlypath'] = preg_replace('|/nc(.*)/|U',"/nc$nc_argument/",$current_url['onlypath']);
		}
		
		$css = preg_replace_callback("/url\s*\((.*)\)/siU", "minify_css_translate_url", $css);
		$css = preg_replace_callback("/AlphaImageLoader\(src='([^']*)'/siU", "minify_css_translate_url", $css);
		$css = "/* FILE: $f */\n$css";
		if( !isset($_GET['nominify']) )
			$code .= cssmin::minify($css)."\n";
		else
		{
			$code .= "$css\n";

			$test = str_replace("\r","",str_replace("\n","",$css));
			$test = preg_replace('|/\*.*\*/|U',"",$test);
			preg_match_all("/([^}]+){([^:]+:[^}]+)}/U", $test, $items, PREG_SET_ORDER);

			foreach( $items as $item )
			{
				$keys = explode(",",$item[1]);
				foreach( $keys as $k )
				{
					$k = trim($k);
					if( isset($res[$k]) )
					{
						if( $f != $map[$k] )
							$mem = $res[$k];
					}
					else
					{
						$map[$k] = $f;
						$res[$k] = array();
					}

					foreach( explode(";",$item[2]) as $e )
					{
						$e = trim($e);
						if( $e === "" )
							continue;
						$res[$k][] = $e;
					}
					sort($res[$k]);
					$res[$k] = array_unique($res[$k]);
					if( isset($mem) )
					{
						if( implode(",",$res[$k]) != implode(",",$mem) )
						{
							if( count($res[$k]) == count($mem) )
								log_debug("[$k] defninition overrides previously defined\nFILE: $f\nPREV: {$map[$k]}\nNEW : ".implode(";",$res[$k])."\nORIG: ".implode(";",$mem)."\n");
							else
							{
								foreach( $mem as $m )
									if( !in_array($m,$res[$k]) )
									{
										log_debug("[$k] defninition extends/overrides previously defined\nFILE: $f\nPREV: {$map[$k]}\nNEW : ".implode(";",$res[$k])."\nORIG: ".implode(";",$mem)."\n");
									}
							}
						}
//						else
//							log_debug("[$k] already defined [$f -> {$map[$k]}]");
						unset($mem);
					}
				}
			}
		}
	}
	file_put_contents($target_file, $code);
}

function minify_css_translate_url($match)
{
	global $current_url;
	$copy = $current_url;
	$url = parse_url(trim($match[1],"\"' "));
	$url = array_merge($copy,$url);	
	if( !isset($url['scheme']) || !$url['scheme'] )
		$url['scheme'] = urlScheme();
	$url = $url['scheme']."://".$url['host'].(isset($url['port'])?":{$url['port']}":"").$url['onlypath'].$url['path'];
	return "url($url)";
}

function minify_dependency_inc($file,$kind,&$sorted,&$deps,$root='',$inc=1)
{
	if( $file == $root )
		return;
	//log_debug("[$kind] minify_dependency_inc",$file,$root);
	$sorted[$file] = isset($sorted[$file])?$sorted[$file]+$inc:$inc;
	$k = basename($file,".$kind");
	if( isset($deps[$k]) )
		foreach( $deps[$k] as $i=>$df )
			minify_dependency_inc($df,$kind,$sorted,$deps,$file,$inc+(count($deps[$k])-$i+1));
}

function minify_collect_files($paths,$kind)
{
	$deps = array();
	$res = array();
	
	foreach( $paths as $path )
	{
		if( !ends_with($path, "/") ) $path .= "/";
		foreach( minify_list_files($path) as $f )
			minify_collect_from_file($kind,$f,$res,$deps);
		
		$done_templates = array();
		foreach( array_reverse(cfg_get('system','tpl_ext')) as $tpl_ext )
		{
			$files = minify_list_files($path,"*.$tpl_ext");
			foreach( $files as $f )
			{
				$skip = false;
				foreach( $done_templates as $d )
					if( isset($deps[basename($f,".$d")]) ){ $skip = true; break; }
				if( $skip ) continue;
				minify_collect_from_file($kind,$f,$res,$deps);
			}
			$done_templates[] = $tpl_ext;
		}
	}
	if( $kind == "css" )
	{
		log_debug("found files",$res);
		return array_keys($res);
	}
	
	log_debug("Dependencies: ",$deps);
	$new = array();
	foreach( array_keys($res) as $file )
		minify_dependency_inc($file,$kind,$new,$deps);
	arsort($new,SORT_NUMERIC);
	$max = array_values($new);
	$max = $max[0] + 1;
	foreach( array_keys($new) as $file )
	{
		$k = basename($file,".$kind");
		if( isset($deps[$k]) && ($k=='htmlpage' || $k=='control') )
		{
			foreach( $deps[$k] as $df )
				$new[$df] += $max;
			$new[$file] = $max;
		}
	}
	arsort($new,SORT_NUMERIC);
	
	log_debug("dep sorted files",$new);
	return array_keys($new);
	
	arsort($res,SORT_NUMERIC);
	log_debug("found files",$res);
	return array_keys($res);
	
	$res = array_unique($res);
	return array_values($res);
}

function minify_collect_from_file($kind,$f,&$res,&$processed)
{
	if( !$f )
		return array();
	$classname = strtolower(basename($f,".class.php"));
	if( isset($processed[$classname]) || $classname=='sysadmin' )
		return array();
	
	$order = ($kind == "js")
		?array('inherited','instanciated','incontent','self','eval')
		:array('self','eval','inherited','instanciated','incontent');
		//:array('self','incontent','instanciated','inherited');
	
	$getmethod = ($kind == "js")?"jsFile":"skinFile";
	$existsmethod = ($kind == "js")?"jsFileExists":"skinFileExists";
	$processed[$classname] = array();
	$content = file_get_contents($f);
	
//	log_debug("minify_collect_from_file($classname)");
	
	// remove block-comments
	$content = preg_replace("|/\*.*\*/|sU","",$content);
	do // remove line-comments in loop to catch subsequent comments too (start at the rightmost)
	{
		$c2 = preg_replace("|(.*)//.*$|m","$1",$content);
		if( $content == $c2 )
			break;
		$content = $c2;
	}while( true );
	
	foreach( $order as $o )
	{
		switch( $o )
		{
			case 'inherited':
				if( preg_match_all('/class\s+[^\s]+\s+extends\s+([^\s]+)/', $content, $matches, PREG_SET_ORDER) )
				{
					foreach( $matches as $m )
						minify_collect_from_file($kind,__search_file_for_class($m[1]),$res,$processed);
				}
				break;
			case 'instanciated':
				if( preg_match_all('/new\s+([^\(]+)\(/', $content, $matches, PREG_SET_ORDER) )
				{
					//log_debug($matches);
					foreach( $matches as $m )
						minify_collect_from_file($kind,__search_file_for_class($m[1]),$res,$processed);
				}
				break;
			case 'incontent':
				if( preg_match_all('/'.$getmethod.'\([\s"\']+([^,;]*)\.'.$kind.'[\s"\']+\)/U', $content, $matches) )
				{
					extract($GLOBALS);
					foreach( $matches[1] as $m )
					{
						$m = strtolower("$m.$kind");
						//log_debug('$m = '.$getmethod.'("'.$m.'");');
						eval('$m = strtolower('.$getmethod.'("'.$m.'"));');
						if( in_array($m,$res) )
							continue;
//						log_debug("incontent: ".$m);
						//$res[] = $m;
						$res[$m] = isset($res[$m])?$res[$m]+1:1;
						if( basename($m,".$kind") != $classname && !in_array($m,$processed[$classname]) )
							$processed[$classname][] = $m;
					}
				}
				break;
			case 'self':
				if( $existsmethod(strtolower("$classname.$kind")) )
				{
					$tmp = $getmethod(strtolower("$classname.$kind"));
//					log_debug("self: ".$tmp);
					//$res[] = $tmp;
					$res[$tmp] = isset($res[$tmp])?$res[$tmp]+1:1;
					if( basename($tmp,".$kind") != $classname && !in_array($tmp,$processed[$classname]) )
						$processed[$classname][] = $tmp;
				}
				break;
			case 'eval':
				try
				{
					$ref = System_Reflector::GetInstance($classname);
					if( !$ref->hasMethod("__$kind") )
						break;
					$mi = $ref->getMethod("__$kind");
					$buf = $mi->invoke(null);
//					if( $kind == "css" )
//						$buf = array_reverse($buf);
					foreach( $buf as $b )
					{
						$b = strtolower($b);
//						log_debug("eval: $b");
						//$res[] = $b;
						$res[$b] = isset($res[$b])?$res[$b]+1:1;
						if( basename($b,".$kind") != $classname && !in_array($b,$processed[$classname]) )
							$processed[$classname][] = $b;
					}
				}
				catch(Exception $ex){}
				break;
		}
	}

	return $res;
}

function minify_list_files($path='',$pattern='*.class.php')
{
	$paths = glob($path.'*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT);
	$files = glob($path.$pattern);
	foreach($paths as $path) { $files = array_merge($files,minify_list_files($path,$pattern)); }
	return $files;
}