<?php

if( PHP_SAPI != 'cli' ) exit;
define("NO_CONFIG_NEEDED",true);
require(__DIR__.'/system.php');
system_init('scavix-wdf-cli');
\ScavixWDF\Model\DataSource::SetDefault("sqlite://:memory:");

\ScavixWDF\CLI\CliLogger::$LOG_SEVERITY = false;
classpath_add(getcwd());

if( count($GLOBALS['argv'])<2 )
	$GLOBALS['argv'][] = 'help';

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
			log_info("Syntax: php scavix-wdf.phar (help|search|<cmd> [<args>])");
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
		log_info("Searching for tasks in current folder...");
		$fqcns = [];
		$processClassFile = function($file)use(&$fqcns)
		{
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
		
		$dir = new RecursiveDirectoryIterator(getcwd());
		$it = new RecursiveIteratorIterator($dir);
		$regit = new RegexIterator($it, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
		foreach( $regit as $phpFile )
			$processClassFile($phpFile[0]);
		
		$dir = new RecursiveDirectoryIterator(__DIR__,FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
		$it = new RecursiveIteratorIterator($dir);
		$regit = new RegexIterator($it, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
		foreach( $regit as $phpFile )
			$processClassFile($phpFile[0]);
		
		$in_phar = (stripos(__DIR__,"phar://") !== false);
		$reflector = $in_phar?str_replace("phar://","",__DIR__):__FILE__;
		$tasks = [];
		foreach( array_chunk($fqcns,20) as $chunk )
		{
			try
			{
				$cls = implode(" ",$chunk);
			
				$test = shell_exec("php $reflector search-reflect $cls");
				if( !preg_match_all('/^ok:(.*)$/im',$test,$matches) )
					continue;
				foreach( $matches[1] as $m )
					$tasks[] = $m;
			}
			catch(\Exception $ex){ log_debug("Caught",$ex); }
		}
		foreach( $tasks as $cls )
		{
			$ref = \ScavixWDF\Reflection\WdfReflector::GetInstance($cls);
			$comment = $ref->getCommentObject();
			$md = trim($comment?$comment->RenderAsMD():'');
			$name = strtolower(str_ireplace("task","",array_last(explode("\\",$cls))));
			log_info("\nCommand    : {$name}\nDescription: {$md}");
		}
	}
	
	function Reflect($args)
	{
		ini_set('display_errors',0);
		error_reporting(0);
		while( count($args)>0 )
		{
			$ref = false;
			$cls = array_shift($args);
			try
			{
				$ref = new \ScavixWDF\Reflection\WdfReflector($cls);
			}
			catch(\Exception $ex){}
			
			if( $ref && $ref->isSubclassOf(\ScavixWDF\Tasks\Task::class) )
				echo "ok:$cls\n";
			else
				echo "nok:$cls\n";
		}
	}
}

system_execute();