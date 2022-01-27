<?php

if( PHP_SAPI != 'cli' ) exit;
define("NO_CONFIG_NEEDED",true);
require(__DIR__.'/system.php');
system_init('scavix-wdf-cli');
\ScavixWDF\Model\DataSource::SetDefault("sqlite://:memory:");
if( class_exists("\\ScavixWDF\\CLI\\CliLogger") )
	\ScavixWDF\CLI\CliLogger::$LOG_SEVERITY = false;
classpath_add(getcwd());

if( count($GLOBALS['argv'])<2 )
	$GLOBALS['argv'][] = 'helptask';


/**
 * Standard help output
 */
class HelpTask extends \ScavixWDF\Tasks\Task
{
	function Run($args)
	{
		$cls = array_shift($args);
		$method = array_shift($args);
		if( $cls )
		{
			$fqcls = fq_class_name($cls);
			if( !class_exists($fqcls) )
			{
				if( !ends_iwith($cls,'task') ) 
					$fqcls = fq_class_name("{$cls}task");
				if( !class_exists($fqcls) )
					return log_error("Unknown object: $cls");
			}
			
			$ref = \ScavixWDF\Reflection\WdfReflector::GetInstance($fqcls);
			if( $method )
				$method = $ref->getMethod($method);
			
			$comment = $ref->getCommentObject($method?$method->getName():false);
			$md = $comment?$comment->RenderAsMD():'';
			$name = strtolower(str_ireplace("task","",array_last(explode("\\",$cls))));
			log_info("\nClassname  : {$ref->getName()}");
			
			$par = $ref->getParentClass();
			if( $par )
				log_info("Parent     : {$par->getName()}");
			
			if( $method )
			{
				$dec = $method->getDeclaringClass();
				if( $dec && $dec->getName() != $ref->getName() )
					log_info("Declared in: {$dec->getName()}");
				log_info("Method     : {$method->getName()}");
			}
			log_info("Description: {$md}");
			
			if( !$method )
			{
				$methods = [];
				foreach( $ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m )
				{
					$dec = $m->getDeclaringClass();
					if( $dec && $dec->getName() != $ref->getName() )
						continue;
					if( !starts_with($m->getName(),'__') )
						$methods[] = $m->getName();
				}
				if( count($methods) )
					log_info("Methods    : ".implode(", ",$methods));
			}
		}
		else
		{
			log_info("Syntax: php ".basename($GLOBALS['argv'][0])." (help|search|<cmd> [<args>])");
			if( file_exists(__DIR__.'/VERSION') )
			{
				$v = array_map('trim',file(__DIR__.'/VERSION'));
				log_info("\nGIT revision",array_shift($v));
				log_info("GIT date",date("Y-m-d H:i:s",array_shift($v)));
				log_info("GIT branch",array_shift($v));
			}
			log_info("\nCommand syntax: help [classname [methodname]]");
			log_info("Command syntax: search");
		}
	}
}

/**
 * Searches current folder for Task implementations
 */
class SearchTask extends \ScavixWDF\Tasks\Task	
{
	function Run($args)
	{
		$fqcns = []; $done = [];
		$processClassFile = function($file)use(&$fqcns,&$done)
		{
			if( isset($done[$file]) ) return;
			$done[$file] = true;
			if( stripos($file,"/vendor/") ) return;
			
			$content = file_get_contents($file);
			$tokens = @token_get_all($content);
			$namespace = '';
			for ($index = 0; isset($tokens[$index]); $index++)
			{
				if (!isset($tokens[$index][0])) {
					continue;
				}
				if (T_NAMESPACE === $tokens[$index][0])
				{
					$namespace = '';
					$index += 2;
					while (isset($tokens[$index]) && is_array($tokens[$index])) {
						$namespace .= $tokens[$index++][1];
					}
				}
				if (T_CLASS === $tokens[$index][0] && T_WHITESPACE === $tokens[$index + 1][0] && T_STRING === $tokens[$index + 2][0])
				{
					$index += 2;
					$fqcns[] = $namespace.'\\'.$tokens[$index][1];
				}
			}
		};
		
		log_info("Searching for tasks in '".getcwd()."'...");
		$dir = new RecursiveDirectoryIterator(getcwd());
		$it = new RecursiveIteratorIterator($dir);
		$regit = new RegexIterator($it, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
		foreach( $regit as $phpFile )
			$processClassFile($phpFile[0]);
		
		$in_phar = (stripos(__DIR__,"phar://") !== false);
		
		if( $in_phar )
			$processClassFile(__FILE__);
		elseif( strpos(__DIR__,getcwd().'/') !== 0 ) 
		{
			log_info("Searching for tasks in '".__DIR__."'...");
			$dir = new RecursiveDirectoryIterator(__DIR__,FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
			$it = new RecursiveIteratorIterator($dir);
			$regit = new RegexIterator($it, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
			foreach( $regit as $phpFile )
				$processClassFile($phpFile[0]);
		}
		
		$reflector = $in_phar?str_replace("phar://","",__DIR__):$GLOBALS['argv'][0];
		$tasks = [];
		foreach( array_chunk(array_unique($fqcns),20) as $chunk )
		{
			try
			{
				$cls = implode(" ",$chunk);
				$out = shell_exec("php $reflector searchtask-reflect $cls");
				$test = json_decode($out,true);
				if( !$test )
					continue;
				foreach( $test as $entry )
					log_info("\nFile       : {$entry['file']}\nCommand    : {$entry['name']}\nDescription: {$entry['desc']}");
			}
			catch(\Exception $ex){ log_debug("Caught",$ex); }
		}
	}
	
	function Reflect($args)
	{
		ini_set('display_errors',0);
		error_reporting(0);
		$res = [];
		while( count($args)>0 )
		{
			$ref = false;
			$cls = array_shift($args);
			try
			{
				cache_clear();
				$ref = new \ScavixWDF\Reflection\WdfReflector($cls);
				if( $ref && $ref->isSubclassOf(\ScavixWDF\Tasks\Task::class) )
				{
					$comment = $ref->getCommentObject();
					$md = trim($comment?$comment->RenderAsMD():'');
					$res[] = ['name'=>$cls,'file'=>$ref->getFileName(),'desc'=>$md];
				}
			}
			catch(\Exception $ex){ }
		}
		die(json_encode($res));
	}
}

\system_execute();