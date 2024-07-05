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

/**
 * WDF internal replacement for $GLOBALS usage.
 */
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

    private static $once_buffer = [];

    /**
     * Helper to easily check if something was already done.
     *
     * @param mixed $id An ID value
     * @return bool True, if has already been called with $id, else false
     */
    public static function Once($id)
    {
        if( isset(self::$once_buffer[$id]) )
            return false;
        self::$once_buffer[$id] = true;
        return true;
    }

    protected static $buffers = [];
    protected static $locks = false;

    /**
     * Checks if there's a buffer present.
     *
     * @param string $name Buffer identifier
     * @return bool True if present, else false
     */
    public static function HasBuffer($name)
    {
        return isset(self::$buffers[$name]);
    }

    /**
     * Creates a buffer that can be used instead of $GLOBALS variable.
     * Optionally, buffers can be mapped to a SESSION variable.
     *
     * @param string $name Buffer identifier
     * @param array|callable $initial_data Array with initial data or callback returning this initial data
     * @return \ScavixWDF\WdfBuffer
     */
    public static function GetBuffer($name,$initial_data=[])
    {
        if( !isset(self::$buffers[$name]) )
            self::$buffers[$name] = new WdfBuffer($initial_data);
        return self::$buffers[$name];
    }

    /**
     * Sets up a LOCK for a given name.
     *
     * On Linux system uses the /run/lock folder to create a lock file. If this
     * succeeds returns true. If not and a timeout is given will try for that amount
     * of seconds. If still fails trhows an exception if $exceptiononfailure is true or returns false.
     * In all other OS see <system_get_lock>().
     *
     * @param string $name Lock name
     * @param int $timeout Seconds to wait/retry (default 10)
     * @param bool $exceptiononfailure If true will throw an exception if lock cannot be created (default: true)
     * @return bool True on success, else false
     */
    public static function GetLock($name,$timeout=10,$exceptiononfailure=true)
    {
        if( PHP_OS_FAMILY == "Linux" )
        {
            $lock = md5($name);
            $dir = '/run/lock/wdf-'.md5(__SCAVIXWDF__);
            $um = umask(0);
            @mkdir($dir, 0777, true);
            $end = time()+$timeout;
            do
            {
                $fp = @fopen("$dir/$lock","x+");
                if( !$fp )
                {
                    if( $timeout > 0 )
                        usleep(100000);
                    continue;
                }
                fwrite($fp,getmypid());
                fflush($fp);
                fclose($fp);

                if( self::$locks === false )
                {
                    self::$locks = [];
                    register_shutdown_function(function()
                    {
                        foreach( Wdf::$locks as $lock=>$fp )
                            @unlink('/run/lock/wdf-'.md5(__SCAVIXWDF__).'/'.$lock);
                    });
                }

                self::$locks[$lock] = $fp;
                umask($um);
                return true;
            }
            while(time()<$end);

            foreach( glob("$dir/???*") as $f )
            {
                if( !system_process_running(trim(@file_get_contents($f))) )
                    @unlink($f);
            }
            umask($um);
            if( ($timeout <= 0) || !$exceptiononfailure )
                return false;
            else
                WdfException::Raise("Timeout while awaiting the lock '$name'");
        }
        return system_get_lock($name,\ScavixWDF\Model\DataSource::Get(),$timeout);
    }

    /**
     * Releases a LOCK.
     *
     * @param string $name The LOCK name
     * @return bool True if successful, else false
     */
    public static function ReleaseLock($name)
    {
        if( PHP_OS_FAMILY == "Linux" )
        {
            $lock = md5($name);
            if( isset(self::$locks[$lock]) )
            {
                @unlink('/run/lock/wdf-'.md5(__SCAVIXWDF__).'/'.$lock);
                unset(self::$locks[$lock]);
                return true;
            }
            return false;
        }
        system_release_lock($name,\ScavixWDF\Model\DataSource::Get());
        return true;
    }
}

/**
 * Implements buffering methods.
 */
class WdfBuffer implements \Iterator, \JsonSerializable
{
    protected $changed = false;
    protected $data = [];
    protected $session_name = false;
    protected $position = 0;

    function __construct($initial_data=[])
    {
        if( is_callable($initial_data) )
            $this->data = $initial_data();
        else
            $this->data = is_array($initial_data)?$initial_data:[];
    }

	/**
	 * @internal see <JsonSerializable>
	 */
    #[\ReturnTypeWillChange]
	public function jsonSerialize()
	{
        return $this->dump();
    }

	/**
     * Maps this buffer to a $_SESSION variable.
	 *
	 * Mapping to the session means that from now on all data will be stored
	 * into $_SESSION[$name] and that getting data will transparently use that variable too.
     *
     * @param string $name Name of session variable
     * @return \ScavixWDF\WdfBuffer
     */
    function mapToSession($name=false)
    {
        if( !$this->session_name )
            $this->session_name = $name;
        return $this;
    }

	/**
     * Returns all data as assiciative array.
	 *
     * @return array
     */
    function dump()
    {
        if( $this->session_name && isset($_SESSION[$this->session_name]) )
            return array_merge($_SESSION[$this->session_name],$this->data);
        return $this->data;
    }

	/**
     * Returns true if some data has been changed.
	 *
	 * This is true, if <WdfBuffer::set> or <WdfBuffer::set> have been used
	 * and if they effectively did something.
	 *
     * @return bool
     */
    function hasChanged()
    {
        return $this->changed;
    }

	/**
     * Returns an array of data keys.
	 *
     * @return array
     */
    function keys()
    {
        $keys = array_keys($this->data);
        if( $this->session_name && isset($_SESSION[$this->session_name]) )
            $keys = array_unique(array_merge($keys,array_keys($_SESSION[$this->session_name])));
        return $keys;
    }

    /**
     * Returns true, if there's data stored with the given name.
	 *
	 * @param string $name The key for the data
     * @return bool
     */
    function has($name)
    {
        return isset($this->data[$name])
            || ($this->session_name && isset($_SESSION[$this->session_name][$name]));
    }

	/**
     * Stores data in the buffer.
	 *
	 * @param string $name The key for the data
	 * @param mixed $value The data to store
     * @return mixed The value given
     */
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

	/**
     * Removes data from the buffer.
	 *
	 * @param string $name The key for the data
     * @return mixed The removed value if present, else null
     */
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

    /**
     * Removes all data from the buffer.
     *
     * @return void
     */
    function clear()
    {
        $this->changed = count($this->data)>0;
        $this->data = [];

        if( $this->session_name && isset($_SESSION[$this->session_name]) )
        {
            $this->changed |= count($_SESSION[$this->session_name])>0;
            $_SESSION[$this->session_name] = [];
        }
    }

	/**
     * Returns data from the buffer.
	 *
	 * @param string $name The key for the data
	 * @param mixed $default A default value, can be a callable too that will get the name and must return the value;
     * @return mixed The removed value if present, else null
     */
    function get($name, $default=null)
    {
        if( !isset($this->data[$name]) && $this->session_name && isset($_SESSION[$this->session_name][$name]) )
            $this->data[$name] = $_SESSION[$this->session_name][$name];
        if( isset($this->data[$name]) )
            return $this->data[$name];
        if( is_callable($default) )
            return $this->set($name,$default($name));
        return $default;
    }

    /**
     * @implements <Iterator::rewind>
     */
    public function rewind():void
    {
        $this->position = 0;
    }

    /**
	 * @implements <Iterator::current>
	 */
    #[\ReturnTypeWillChange]
    public function current():mixed
    {
        return $this->get($this->key());
    }

    /**
	 * @implements <Iterator::key>
	 */
    #[\ReturnTypeWillChange]
    public function key():mixed
    {
        return $this->keys()[$this->position];
    }

    /**
	 * @implements <Iterator::next>
	 */
    public function next():void
    {
        $this->position++;
    }

    /**
	 * @implements <Iterator::valid>
	 */
    public function valid():bool
    {
        return isset($this->keys()[$this->position]);
    }
}

/**
 * We use this to test access to controllers.
 * All controllers must implement this interface
 */
interface ICallable {}


/**
 * Defines an objects that handles log-string creation itself.
 */
interface ILogWritable
{
    public function __toLogString(): string;
}


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
    public $details = '';

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
     * @param mixed ...$args Messages to be concatenated
	 * @return void
     * @suppress PHP1402
	 */
	public static function Raise(...$args)
	{
        [$message, $msgs, $inner_exception] = self::_prepareArgs(...$args);

        /**
         * @var WdfException $classname
         */
		$classname = get_called_class();
		if( $inner_exception )
			$ex =  new $classname($message,$inner_exception->getCode(),$inner_exception);
		else
			$ex = new $classname($message);

        $ex->details = implode("\t",$msgs);
        throw $ex;
	}

    protected static function _prepareArgs(...$args)
    {
        $msgs = [];
		$inner_exception = false;
		foreach( $args as $m )
		{
			if( !$inner_exception && ($m instanceof Exception) )
				$inner_exception = $m;
			else
				$msgs[] = logging_render_var($m);
		}
        $message = array_shift($msgs);
        return [$message, $msgs, $inner_exception];
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
     * @param mixed ...$args Messages to be concatenated
	 * @return void
	 */
	public static function Log(...$args)
	{
		call_user_func_array('log_error', $args);
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
	 * @return array Returns the Exception stack trace as an array
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

    private static function _prepare(string $message, ?Model\ResultSet $statement = null)
    {
        if( isDev() )
            $msg = "SQL Error: $message";
        else
            $msg = "SQL Error occured";

        if( $statement )
        {
            $trim_sql = function($s)
            {
                $lines = explode("\n",$s);
                foreach( ["\t"," "] as $ws )
                {
                    $pre = [];
                    foreach( $lines as $l )
                        $pre[] = strspn($l,$ws)?:999;
                    $min = min($pre);
                    if( $min == 999 )
                        continue;
                    foreach( $lines as $i=>&$l )
                        $l = preg_replace("/^{$ws}{{$min}}/","",$l);
                    break;
                }
                return implode("\n",$lines);
            };

            $sql = $trim_sql($statement->GetSql());
            $args = $statement->GetArgs();
            $msql = $trim_sql($statement->GetMergedSql());

            $details = "Message: $message\nSQL: $sql";
            if( $args && count($args) )
                $details .= "\nArguments: ".json_encode($args);
            if( $msql != $sql )
                $details .= "\nMerged: $msql";

            return [$msg, $details];
        }


        return [$msg, ''];
    }

    /**
     * @internal Wraps a \PDOException, optionally with the triggering statement
     */
    public static function RaisePdoEx(\PDOException $ex, ?Model\ResultSet $statement = null)
    {
        list($msg, $details) = self::_prepare($ex->getMessage(),$statement);
        $res = new WdfDbException($msg);
        $res->details = $details;
        $res->statement = $statement;
        throw $res;

    }

    /**
     * @internal Raises an Exception for a failed DB Statement.
     */
    public static function RaiseStatement($statement)
	{
        if(!($statement instanceof Model\ResultSet))
            $statement = new Model\ResultSet($statement->_ds, $statement);

        list($msg, $details) = self::_prepare(json_encode($statement->ErrorInfo()),$statement);
        $ex = new WdfDbException($msg);
        $ex->details = $details;
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
     * Returns an array with error information
     *
     * @return array
     */
    function getErrorInfo()
    {
        if( $this->statement )
            return $this->statement->ErrorInfo();
        return ['','',"Error preparing the SQL statement. Most likely there's an error in the SQL syntax."];
    }

    /**
     * @internal Helper method to detect if this represents an Exception indicating a duplicate key
     */
    function isDuplicateKeyException($key = false)
    {
        list($c1,$c2,$msg) = $this->getErrorInfo();
        $regex = "/Duplicate entry '.*' for key".($key ? " '".$key."'" : '')."/i";
        return (preg_match($regex, $msg, $dummy) != false);
    }

    /**
     * @internal Helper method to detect if this represents an Exception indicating a missing table.
     */
    function isTableNotExistException($table = false)
    {
        list($c1,$c2,$msg) = $this->getErrorInfo();
        $regex = "/Table '.*".($table ? $table : '')."' doesn't exist/i";
        return (preg_match($regex, $msg, $dummy) != false);
    }
}

/**
 * Thrown when some process reached a state where graceful but immidiate termination is required.
 *
 * We use this like this: `TerminationException::WithCode('HTTP_500','Server responded with 500, cannot do that now')`
 */
class TerminationException extends WdfException
{
    private $verbose, $reason;

    private static function _make(string $reason, bool $verbose, ...$args)
    {
        [$message, $msgs, $inner_exception] = self::_prepareArgs(...$args);
        $message = $message?"$reason: $message":$reason;
        if( $inner_exception )
			$ex = new TerminationException($message,$inner_exception->getCode(),$inner_exception);
		else
			$ex = new TerminationException($message);

        $ex->details = implode("\t",$msgs);
        $ex->verbose = $verbose;
        $ex->reason = $reason;
        return $ex;
    }

    static function Silent(string $reason, ...$args)
    {
        throw self::_make($reason, isDev(), ...$args);
    }

    static function Verbose(string $reason, ...$args)
    {
        throw self::_make($reason, true, ...$args);
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function writeLog()
    {
        if (!$this->verbose)
            return;
        log_debug($this->getMessageEx());
    }
}

