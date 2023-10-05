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
namespace ScavixWDF\Logging;

/**
 * Base class for logging.
 * 
 * Do not use this directly but the functions in logging.php instead.
 * Will ensure that logging information is writte to specified files.
 * Will also take care of rotating the logs and cleaning up old logfiles.
 */
class Logger
{
    /** @var string */
    protected $min_severity;
    /** @var string */
    protected $filename_pattern;
    /** @var string */
    protected $class;
    /** @var int */
    protected $max_filesize;
    /** @var int */
    protected $keep_for_days;
    /** @var int */
    protected $max_trace_depth;
    /** @var bool */
    protected $log_date;
    /** @var bool */
    protected $log_categories;
    /** @var bool */
    protected $log_severity;

    public static $severity_map =
    [
        'NOTICE'     => 'DEBUG',
        'DEPRECATED' => 'INFO',
        'WARNING'    => 'WARN',
        'STRICT'     => 'WARN',
        'PARSE'      => 'FATAL'
    ];
    
	const TRACE = 1;
	const DEBUG = 2;
	const INFO = 4;
	const WARN = 8;
	const ERROR = 16;
	const FATAL = 32;

	const SEV_ALL = 0;					// simply all
	const SEV_BETA = 0;					// for now all too
	const SEV_PRODUCTION = self::INFO;	// WARN|ERROR|FATAL

	// for PHP error reporting compatibility
	const NOTICE     = self::DEBUG;
	const DEPRECATED = self::INFO;
	const WARNING    = self::WARN;
	const STRICT     = self::WARN;
	const PARSE      = self::FATAL;
	
	public static $Instances = [];
	public static $FilenamePatterns = [];
    private $categories = [];
	
    protected $path = false;
    protected $filename;

	protected function __construct($config)
	{
		if( !is_array($config) )
			$config = include($config);

        $config = array_merge([
            'log_date' => true,
            'log_categories' => true,
            'log_severity' => true,
        ], $config);

		foreach( $config as $k=>$v )
			$this->$k = $v;
		
		if( !$this->path )
			$this->path = dirname(ini_get('error_log'))."/";
		
		if( !is_object($this) || !isset($this) )
			error_log(getmypid()." STACK: ".var_export(debug_backtrace(),true));
		$this->path = realpath($this->path);
		
		if( isset($this->min_severity) )
		{
			$this->min_severity = constant("self::".$this->min_severity);
			if( $this->min_severity == null )
				unset($this->min_severity);
		}
		
		$this->rotate();
	}
	
	/**
	 * Instanciates and return a <Logger> from a given config.
	 * 
	 * @param array $config Logger configuration data
	 * @return mixed The logger, may be of type <Logger> or whatever is specified in `$config['class']`
	 */
	public static function Get($config)
	{
		if( count(self::$FilenamePatterns) == 0 )
		{
			foreach( $_SERVER as $k=>$v )
				self::$FilenamePatterns[$k] = $v;
		}
		if( isset($config['class']) )
		{
			$log_cls = fq_class_name($config['class']);
			$res = new $log_cls($config);
		}
		else
			$res = new Logger($config);
		self::$Instances[] = $res;
		return $res;
	}
	
	protected function ensureFile()
	{
        $touch = function ()
        {
            if (!file_exists($this->filename))
                @touch($this->filename);
        };
        
		if( isset($this->filename) && $this->filename )
			return $touch();

		if( !isset($this->filename_pattern) || !$this->filename_pattern )
			$this->filename = ini_get('error_log');
		else
		{
			$this->filename = $this->path.'/'.$this->filename_pattern;
			if( !preg_match_all('/{(.+)}/U', $this->filename_pattern, $matches, PREG_SET_ORDER) )
				return $touch();

			foreach( $matches as $m )
			{
				$k = $m[1];
				$v = isset($this->$k)?$this->$k:"";
				if( $v )
					$this->filename = str_replace("{".$k."}","-".$v,$this->filename);
				else
				{
					$v = isset(self::$FilenamePatterns[$k])?self::$FilenamePatterns[$k]:"";
					if( $v )
						$this->filename = str_replace("{".$k."}","-".$v,$this->filename);
					else
						$this->filename = str_replace("{".$k."}","",$this->filename);
				}
			}
		}
        $touch();
	}
	
    /**
     * Forces log rotation.
     * @return void
     */
	function RotateNow()
    {
        $this->rotate(true);
    }
	
	protected function rotate($force=false)
	{
		$this->ensureFile();
		
		if( !$force && (!isset($this->max_filesize) || @filesize($this->filename)<$this->max_filesize) )
			return;
		
		$ext = pathinfo($this->filename, PATHINFO_EXTENSION);
		
		$archived = preg_replace('/.'.$ext.'$/',"_".date("Y-m-d-H-i-s").".$ext",$this->filename);
		if(!@rename($this->filename,$archived))
			return;
		
		$source = $archived;
		$archived = $archived.".gz";
		
		if($fp_out=gzopen($archived,'wb9'))
		{
			if($fp_in=fopen($source,'rb'))
			{
				while(!feof($fp_in))
					@gzwrite($fp_out,fread($fp_in,1024*512));
				@fclose($fp_in);
			}
			else 
				$error=true;
			@gzclose($fp_out);
		}
		@unlink($source);
		
		if( isset($this->keep_for_days) && $this->keep_for_days>0 )
		{
			$max_age = time()-(86400*$this->keep_for_days);
			foreach( system_glob( str_replace(".$ext","_*.gz",$this->filename) ) as $f )
				if( @filemtime($f) < $max_age )
					@unlink($f);
		}
	}
	
	protected function render($content)
	{
        $GLOBALS['logging_render_var_for_logger'] = true;
		$r = logging_render_var($content);
        unset($GLOBALS['logging_render_var_for_logger']);
        return $r;
	}
	
	protected function prepare($severity=false,$log_trace=false,...$args)
	{
		// translate PHP severities like NOTICE,... to our own
		if( isset(Logger::$severity_map[$severity]))
			$severity = Logger::$severity_map[$severity];
		
		if( isset($this->min_severity) )
		{
			$s = @constant("\\ScavixWDF\\Logging\\Logger::$severity");
			if( $s!==null && $s<$this->min_severity  )
				return false;
		}
				
		if( !isset($this->filename) )
			$this->ensureFile();
		// if( !file_exists($this->filename) )
		// 	touch($this->filename);
		if( (fileperms($this->filename) & 0777) != 0777 )
        {
            $um = umask(0);
			@chmod($this->filename, 0777);
            
            umask($um);
        }
		
		$parts = array_map([$this,'render'], $args);

		$max_trace_depth = isset($this->max_trace_depth)?$this->max_trace_depth:5;
        $time = (isset($this->log_date) && $this->log_date)?time():false;
		$severity = (isset($this->log_severity) && $this->log_severity)?$severity:false;
		$categories = (isset($this->log_categories) && $this->log_categories)?$this->categories:false;
		
		if( $log_trace )
			$entry = new LogEntry($time,$severity, $categories, debug_backtrace(), implode("\t",$parts), $max_trace_depth);
		else
			$entry = new LogEntry($time,$severity, $categories, false, implode("\t",$parts), $max_trace_depth);
		return $entry;
    }
    
	/**
	 * Writes a log entry.
	 * 
	 * @param string $severity Severity
	 * @param bool $log_trace If true appends a trace (see <debug_backtrace>).
	 * @param_array mixed $a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10 Data to be logged
	 * @return void
	 */
    public function write($severity=false,$log_trace=false,...$args)
	{
		$content = $this->prepare($severity,$log_trace,...$args);
		if( !$content ) return;
		$content = $content->toReadable();
        
        if( !$this->filename )
		{
			if( PHP_SAPI!='cli' )
				error_log($content);
            return;
		}
		$try = 0;
		while((@file_put_contents($this->filename, "$content\n", FILE_APPEND) === false) && ($try < 10) )
		{
			usleep(100);
			$try ++;
		}
		if($try >= 10)
			error_log("Cannot write to {$this->filename}: ".$content);
	}
	
	/**
	 * Extends the log filename.
	 * 
	 * @param string $key Key to use
	 * @param string $value Value to use
	 * @return void
	 */
	function extend($key,$value)
	{
		$this->$key = $value;
		unset($this->filename);
	}

	/**
	 * Adds a category
	 * 
	 * @param string $name Category to add
	 * @return void
	 */
    function addCategory($name)
	{
		if( !in_array($name, $this->categories) )
			$this->categories[] = $name;
	}

	/**
	 * Removes a category
	 * 
	 * @param string $name Category to remove
	 * @return void
	 */
    function removeCategory($name)
	{
		foreach( $this->categories as $i=>$cat )
            if( $cat == $name )
            {
                unset($this->categories[$i]);
                break;
            }
	}

	/**
	 * @shortcut Logs to severity TRACE
	 */
	function trace(...$args) 
	{ $this->write("TRACE",true,...$args); }
	
	/**
	 * @shortcut Logs to severity DEBUG
	 */
	function debug(...$args) 
	{ $this->write("DEBUG",false,...$args); }
	
	/**
	 * @shortcut Logs to severity INFO
	 */
	function info(...$args) 
	{ $this->write( "INFO",false,...$args); }
	
	/**
	 * @shortcut Logs to severity WARN
	 */
	function warn(...$args) 
	{ $this->write( "WARN",false,...$args); }
	
	/**
	 * @shortcut Logs to severity ERROR
	 */
	function error(...$args) 
	{ $this->write("ERROR",true,...$args); }
	
	/**
	 * @shortcut Logs to severity FATAL
	 */
	function fatal(...$args) 
	{ $this->write("FATAL",true,...$args); }
	
	/**
	 * Writes a <LogReport> to the log.
	 * 
	 * See <LogReport> class and <log_start_report>/<log_report> for details.
	 * @param LogReport $report The report
	 * @param string $severity Severity to use
	 * @param bool $log_trace Append a trace (see <debug_backtrace>)
	 * @return void
	 */
	function report(LogReport $report, $severity="TRACE", $log_trace=true)
	{
		$content = $report->render();
		$this->write($severity,$log_trace,$content);
	}
}