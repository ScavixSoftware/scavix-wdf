<?php

/**
 * Searches current folder for Task implementations
 */
class SearchTask extends \ScavixWDF\Tasks\Task
{
    private function listFiles()
    {
        $res = [];
        log_info("Searching for files in '".getcwd()."'...");
		$dir = new RecursiveDirectoryIterator(getcwd());
		$it = new RecursiveIteratorIterator($dir);
		$regit = new RegexIterator($it, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
		foreach( $regit as $phpFile )
			$res[] = $phpFile[0];

		$in_phar = (stripos(__DIR__,"phar://") !== false);

        if ($in_phar)
        {
			// log_info("Adding PHAR itself...");
            $res[] = __FILE__;
            $res[] = str_replace("searchtask","helptask",__FILE__);
            $res[] = str_replace("searchtask","devserver",__FILE__);
        }
		elseif( strpos(__DIR__,getcwd().'/') !== 0 )
		{
			log_info("Adding files in '".__DIR__."'...");
			$dir = new RecursiveDirectoryIterator(__DIR__,FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS);
			$it = new RecursiveIteratorIterator($dir);
			$regit = new RegexIterator($it, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
			foreach( $regit as $phpFile )
				$res[] = $phpFile[0];
		}
        // log_info("Total: ".count($res));
        return $res;
    }

    /**
     * Searche for executable tasks.
     *
     * @param mixed $args No args
     * @return void
     */
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

        foreach( $this->listFiles() as $file )
            $processClassFile($file);

        $in_phar = (stripos(__DIR__,"phar://") !== false);
        if (file_exists(getcwd().'/index.php'))
            $reflector = 'index.php';
        else
            $reflector = $in_phar ? (explode(".phar/", __DIR__)[0]) . ".phar" : $GLOBALS['argv'][0];
        // log_debug("Reflector script: $reflector");
		$tasks = [];
		foreach( array_chunk(array_unique($fqcns),20) as $chunk )
		{
			try
			{
                $cls = implode(" ",array_map(function($c){ return "\"$c\""; },$chunk));
                $out = shell_exec("php $reflector searchtask-reflect $cls");
				$test = json_decode("$out",true);
				if( !$test )
					continue;
				foreach( $test as $entry )
                {
                    if( isset($entry['ex']) )
                        log_error($entry['ex']);
					else
                        log_info("\nFile       : {$entry['file']}\nCommand    : {$entry['name']}\nDescription: {$entry['desc']}");
                }
			}
			catch(\Exception $ex){ log_debug("Caught",$ex); }
		}
	}

    /**
     * @internal Helper
     */
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

    /**
     * @internal Helper
     */
    function LoadClassesTest($args)
	{
        $preload = implode(" ",array_map(function($c){ return "\"$c\""; },$args));
        $in_phar = (stripos(__DIR__,"phar://") !== false);
        $reflector = $in_phar?str_replace("phar://","",__DIR__):$GLOBALS['argv'][0];
        foreach( $this->listFiles() as $file )
        {
            if( $file == __FILE__ || !fnmatch("*.class.php", $file) || strpos($file, "/system") )
                continue;
            $out = trim(shell_exec("php $reflector SearchTask-LoadClassesTestWorker $preload \"$file\" 2>&1"));
            if( $out )
                log_error("Error in file $file:\n$out\n");
        }
    }

    /**
     * @internal Helper
     */
    function LoadClassesTestWorker($args)
	{
        ob_start();
        foreach( $args as $a )
            require($a);
        ob_end_clean();
	}
}
