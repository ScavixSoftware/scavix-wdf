<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF;

use Exception;

if( !defined('FRAMEWORK_LOADED') || FRAMEWORK_LOADED != 'uSI7hcKMQgPaPKAQDXg5' ) die('');

class Wdf
{
    public static $Logger = [];
    public static $Timer = [];
    public static $DataSources = [];
    public static $Hooks = [];
    public static $Modules = [];
    public static $ClassAliases = [];
    
    public static $Request;
    public static $ClientIP;
    public static $SessionHandler;
    public static $ObjectStore;
    public static $Translation;
    
    protected static $buffers = [];
    public static function HasBuffer($name)
    {
        return isset(self::$buffers[$name]);
    }
    
    /**
     * Creates a buffer that can be used instead of $GLOBALS variable.
     * Optionally, buffers can be mapped to a SESSION variable.
     * 
     * @param string $name Buffer identifier
     * @param array|Callable $initial_data Array with initial data or callback returning this initial data
     * @return ScavixWDF\WdfBuffer
     */
    public static function GetBuffer($name,$initial_data=[])
    {
        if( !isset(self::$buffers[$name]) )
            self::$buffers[$name] = new WdfBuffer($initial_data);
        return self::$buffers[$name];
    }
    
}

class WdfBuffer
{
    protected $changed = false;
    protected $data = [];
    protected $session_name = false;
    
    function __construct($initial_data=[])
    {
        if( is_callable($initial_data) )
            $this->data = $initial_data();
        else
            $this->data = is_array($initial_data)?$initial_data:[];
    }
    
    function mapToSession($name=false)
    {
        if( !$this->session_name )
            $this->session_name = $name;
        return $this;
    }
    
    function dump()
    {
        if( $this->session_name && isset($_SESSION[$this->session_name]) )
            return array_merge($_SESSION[$this->session_name],$this->data);
        return $this->data;
    }
    
    function hasChanged()
    {
        return $this->changed;
    }
    
    function keys()
    {
        $keys = array_keys($this->data);
        if( $this->session_name && isset($_SESSION[$this->session_name]) )
            $keys = array_unique(array_merge($keys,array_keys($_SESSION[$this->session_name])));
        return $keys;
    }
    
    function has($name)
    {
        return isset($this->data[$name])
            || ($this->session_name && isset($_SESSION[$this->session_name][$name]));
    }
    
    function set($name, $value)
    {
        if( !$this->changed )
            $prev = $this->get($name,null);    
        $this->data[$name] = $value;
        if( $this->session_name )
            $_SESSION[$this->session_name][$name] = $value;
        if( !$this->changed ) 
            $this->changed = ($prev !== $value);
        return $value;
    }

    function del($name)
    {
        if( isset($this->data[$name]) )
        {
            $r = $this->data[$name];
            unset($this->data[$name]);
            $this->changed = true;
        }
        if( $this->session_name && isset($_SESSION[$this->session_name][$name]) )
        {
            unset($_SESSION[$this->session_name][$name]);
            $this->changed = true;
        }
        return isset($r)?$r:null;
    }

    function get($name, $default=null)
    {
        if( !isset($this->data[$name]) && $this->session_name && isset($_SESSION[$this->session_name][$name]) )
            $this->data[$name] = $_SESSION[$this->session_name][$name];
        if( isset($this->data[$name]) )
            return $this->data[$name];
        return (is_callable($default))?$default($name):$default;
    }
}

/**
 * We use this to test access to controllers.
 * All controllers must implement this interface
 */
interface ICallable {}


/**
 * Transparently wraps Exceptions thus providing a way to catch them easily while still having the original
 * Exception information.
 * 
 * Using static <WdfException::Raise>() method you can pass in multiple arguments. ScavixWDF will try to detect
 * if there's an exception object given and use it (the first one detected) as inner exception object.
 * <code php>
 * WdfException::Raise('My simple test');
 * WdfException::Raise('My simple test2',$obj_to_debug_1,'and',$obj_to_debug_2);
 * try{ $i=42/0; }catch(Exception $ex){ WdfException::Raise('That was stupid!',$ex); }
 * <code>
 */
class WdfException extends Exception
{
	private function ex()
	{
		$inner = $this->getPrevious();
		return $inner?$inner:$this;
	}
	
	/**
	 * Use this to throw exceptions the easy way.
	 * 
	 * Can be used from derivered classes too like this:
	 * <code php>
	 * ToDoException::Raise('implement myclass->mymethod()');
	 * </code>
	 * @return void
	 */
	public static function Raise()
	{
		$msgs = array();
		$inner_exception = false;
		foreach( func_get_args() as $m )
		{
			if( !$inner_exception && ($m instanceof Exception) )
				$inner_exception = $m;
			else 
				$msgs[] = logging_render_var($m);
		}
		$message = implode("\t",$msgs);
		
		$classname = get_called_class();
		if( $inner_exception )
			throw new $classname($message,$inner_exception->getCode(),$inner_exception);
		else
			throw new $classname($message);
	}
	
	/**
	 * Use this to easily log an exception the nice way.
	 * 
	 * Ensures that all your exceptions are logged the same way, so they are easily readable.
	 * sample: 
	 * <code php>
	 * try{
	 *  some code
	 * }catch(Exception $ex){ WdfException::Log("Weird:",$ex); }
	 * </code>
	 * Note that Raise method will log automatically, so this is mainly useful when silently catching exceptions.
	 * @return void
	 */
	public static function Log()
	{
		call_user_func_array('log_error', func_get_args());
	}
	
	/**
	 * Returns exception message.
	 * 
	 * Check if there's an inner exception and combines this and that messages into one if so.
	 * @return string Combined message
	 */
	public function getMessageEx()
	{
		$inner = $this->getPrevious();
		return $this->getMessage().($inner?"\nOriginal message: ".$inner->getMessage():'');
	}
	
	/**
	 * Calls this or the inner exceptions getFile() method.
	 * 
	 * See http://www.php.net/manual/en/exception.getfile.php
	 * @return string Returns the filename in which the exception was created
	 */
	public function getFileEx(){ return $this->ex()->getFile(); }
	
	/**
	 * Calls this or the inner exceptions getCode() method.
	 * 
	 * See http://www.php.net/manual/en/exception.getcode.php
	 * @return string Returns the exception code as integer
	 */
	public function getCodeEx(){ return $this->ex()->getCode(); }
	
	/**
	 * Calls this or the inner exceptions getLine() method.
	 * 
	 * See http://www.php.net/manual/en/exception.getline.php
	 * @return string Returns the line number where the exception was created
	 */
	public function getLineEx(){ return $this->ex()->getLine(); }
	
	/**
	 * Calls this or the inner exceptions getTrace() method.
	 * 
	 * See http://www.php.net/manual/en/exception.gettrace.php
	 * @return string Returns the Exception stack trace as an array
	 */
	public function getTraceEx(){ return $this->ex()->getTrace(); }
}

/**
 * Thrown when something still needs investigation
 * 
 * We use this like this: `ToDoException::Raise('Not yet implemented')`
 */
class ToDoException extends WdfException {}

/**
 * Thrown from all database related system parts
 * 
 * All code in the model essential (essentials/model.php + essentials/model/*) use this instead of WdfException.
 * Just to have everyting nicely wrapped.
 */
class WdfDbException extends WdfException
{
    public static $DISABLE_LOGGING = false;
        
    private $statement;
    
    /**
     * @internal Raises an Exception for a failed DB Statement.
     */
    public static function RaiseStatement($statement, $use_extended_info = false)
	{
        $errid = uniqid();
        if(!($statement instanceof Model\ResultSet))
            $statement = new Model\ResultSet($statement->_ds, $statement);
        
        if(isDev())
        {
            if( $use_extended_info )
                $msg = "SQL Error: ".$statement->ErrorOutput()."\nSQL:".$statement->GetMergedSql()."\nError ID: ".$errid;
            else
                $msg = render_var($statement->ErrorOutput())."\nError ID: ".$errid;
        }
        else
        {
            $msg = 'SQL Error occured. Please contact the technical team and tell them this error ID: '.$errid;
            if( !self::$DISABLE_LOGGING )
                log_error("SQL Error", $errid, $statement->ErrorOutput(), $statement->GetMergedSql());
        }
        $ex = new WdfDbException($msg);
        $ex->statement = $statement;
		throw $ex;
	}
    
    /**
     * Returns the SQL string used
     * 
     * @return string SQL
     */
    function getSql()
    {
        if( $this->statement )
            return $this->statement->GetSql();
        return '(undefined)';
    }
    
    /**
     * Returns the arguments used
     * 
     * @return array The arguments
     */
    function getArguments()
    {
        if( $this->statement )
            return $this->statement->GetArgs();
        return [];
    }
    
    /**
     * Returns a string merged from the SQL statement and the arguments
     * 
     * @return string Merged SQL statement
     */
    function getMergedSql()
    {
        if( $this->statement )
            return $this->statement->GetMergedSql();
        return '(undefined)';
    }
    
    /**
     * Returns a string merged from the SQL statement and the arguments
     * 
     * @return string Merged SQL statement
     */
    function getErrorInfo()
    {
        if( $this->statement )
            return $this->statement->ErrorInfo();
        return ['','',"Error preparing the SQL statement. Most likely there's an error in the SQL syntax."];
    }
}
