<?php

/**
 * Standard help output
 */
class HelpTask extends \ScavixWDF\Tasks\Task
{
    /**
     * Prints information about classes.
     *
     * @param mixed $args The classname to print help for
     * @return void
     */
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