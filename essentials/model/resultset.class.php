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
namespace ScavixWDF\Model;

use ArrayAccess;
use Iterator;
use PDO;
use PDOStatement;
use ScavixWDF\Model\Driver\MySql;

/**
 * This is our own Statement class
 *
 * There are some difficulties with PHPs PDOStatement class as it will not allow us to override all methods (Traversable hides Iterator).
 * So we cannot simply inherit from there, but must wrap it.
 */
class ResultSet implements Iterator, ArrayAccess, \Serializable
{
	private $_stmt = null;
	private $_ds = null;
	private $_pdo = null;
	private $_sql_used = null;
	private $_arguments_used = null;
	private $_paging_info = null;
	private $_field_types = null;
	private $_index = -1;
	private $_rowbuffer = [];
	private $_loaded_from_cache = false;
	private $_data_fetched = false;
	private $_rowCount = false;

	/*--- Compatibility to old model ---*/
	private $_current = false;

	public $FetchMode = PDO::FETCH_ASSOC;

	function __construct(?DataSource $ds=null, ?WdfPdoStatement $statement=null)
	{
		$this->_ds = $ds;
		if( $statement )
		{
			$this->_stmt = $statement;
			$this->_pdo = $statement->_pdo;
		}
	}

	public function __clone()
	{
		$this->rewind();
	}

	/**
	 * Merges arguments into an SQL statement.
	 *
	 * Note that this is meant for debug output only!
	 * @param DataSource $ds <DataSource> used to escape the arguments (<DataSource::EscapeArgument>)
	 * @param string $sql SQL statement
	 * @param array $arguments Array of arguments
	 * @return string Merged statement
	 */
	public static function MergeSql($ds,$sql,$arguments)
	{
		if( is_array($arguments) )
        {
            $hasqm = ( stripos($sql,"?") !== false );
			foreach( $arguments as $n => $a )
			{
                $args = [];
                foreach( force_array($a,false) as $arg )
                    $args[] = is_null($arg)?"null":((is_numeric($arg) && !starts_with("$arg",'0') && (strpos($arg, '+') === false)) ?"$arg":"'".$ds->EscapeArgument("$arg")."'");
                $a = implode(",",$args);
				if($hasqm)
					$sql = preg_replace('/\?/', $a, $sql, 1);
				else
					$sql = str_replace("$n", $a, $sql);
			}
        }
		return $sql;
	}

	/**
	 * Returns the last statement and the error info
	 *
	 * Will combine that into a string for easy output
	 * @return string SQL[newline]ErrorInfo
	 */
	public function ErrorOutput()
	{
		return render_var($this->_stmt->errorInfo())."\n".$this->_sql_used;
	}

    /**
	 * Returns the error info
	 *
	 * @return array ErrorInfo
	 */
	public function ErrorInfo()
	{
		return $this->_stmt->errorInfo();
	}

	/**
	 * Returns if there was an error
	 *
	 * @return bool true if there was an error
	 */
	public function HadError()
	{
		return ($this->_stmt->errorCode() != '00000');
	}

	/**
	 * Logs this statement
	 *
	 * Sometimes you will need to debug specific statements. This method will create a logentry with the SQL query, the arguments used
	 * and try to combine it for easy copy+paste from log to your sql tool (for retry).
     * @param string $label Optional label to use as prefix for the log entry
	 * @return void
	 */
	public function LogDebug($label='')
	{
        if( $label ) $label = "$label\n";
        if( $this->_arguments_used && count($this->_arguments_used) )
            log_debug("{$label}SQL   : ".$this->_sql_used."\nARGS  : ".json_encode($this->_arguments_used)."\nMERGED: ".SqlFormatter::format(ResultSet::MergeSql($this->_ds,$this->_sql_used,$this->_arguments_used),false));
        else
            log_debug("{$label}SQL: ".SqlFormatter::format($this->_sql_used, false));
	}

	/**
	 * Gets the query used
	 * @return string SQL query
	 */
	public function GetSql()
	{
		return $this->_sql_used?:$this->_stmt->queryString;
	}

	/**
	 * Gets the arguments
	 * @return array SQL arguments
	 */
	public function GetArgs()
	{
		return $this->_arguments_used;
	}

    /**
	 * Gets the merged query used (inline arguments)
	 * @return string SQL query
	 */
	public function GetMergedSql()
	{
		return ResultSet::MergeSql($this->_ds,$this->GetSql(),$this->_arguments_used);
	}

    /**
     * Executes the query representes by this <ResultSet>.
     *
     * Will autodetect if the query references named (:arg1, :arg2) or numerical (?) arguments
     * and bind the values from $arguments accordingly.
     *
     * @param array $arguments Array of arguments, matching the SQL query given.
     * @return mixed The query result
     */
    function ExecuteWithArguments($arguments)
    {
		if(DataSource::$LogSlowQueries)
		{
			start_timer("WdfSqlPerformance");
			hit_timer("WdfSqlPerformance", system_get_caller());
		}
		$sql = $this->GetSql();
		foreach (array_clean_assoc_or_sequence(force_array($arguments,false)) as $n => $v)
		{
			if (is_numeric($n))
				$n = $n + 1;
			elseif (strpos($sql, $n) === false)
				continue;

			if (is_integer($v))
				$this->bindValue($n, $v, PDO::PARAM_INT);
			elseif ($v instanceof DateTime)
				$this->bindValue($n, $v->format("Y-m-d H:i:s"));
			elseif (is_string($v))
				$this->bindValue($n, $v, PDO::PARAM_STR);
			else
				$this->bindValue($n, $v);
		}
		$res = $this->execute();
		if(DataSource::$LogSlowQueries)
			finish_timer("WdfSqlPerformance", DataSource::$LogSlowQueriesSeconds * 1000);
		return $res;
    }

	/**
	 * Savely serializes this object
	 *
	 * This is mainly needed for query caching
	 * @return string serialized data string
	 */
	function serialize()
	{
		return serialize($this->__serialize());
	}

    function __serialize() : array
    {
        return [
            'ds' => $this->_ds->_storage_id,
			'sql' => $this->_stmt->queryString,
			'args' => $this->_arguments_used,
			'paging_info' => $this->_paging_info,
			'field_types' => $this->_field_types,
			'index' => $this->_index,
			'rows' => $this->_rowbuffer,
			'rowCount' => $this->_rowCount,
			'df' => $this->_data_fetched
        ];
    }

    /**
	 * Savely unserializes this object
	 *
	 * This is mainly needed for query caching.
     * @param string $data Serialized data, see result of <ResultSet::serialize>
	 * @return void
	 */
    function unserialize($data)
	{
        //log_debug(__METHOD__,$data);
		$buf = unserialize($data);
		$this->__unserialize($buf);
	}

    function __unserialize(array $data)
    {
        $this->_ds = model_datasource($data['ds']);
		$this->_sql_used = $data['sql'];
		$this->_arguments_used = $data['args'];
		$this->_paging_info = $data['paging_info'];
		$this->_field_types = $data['field_types'];
		$this->_index = $data['index'];
		$this->_rowbuffer = $data['rows'];
		$this->_rowCount = isset($data['rowCount'])?$data['rowCount']:false;
		$this->_loaded_from_cache = true;
		$this->_data_fetched = isset($data['df'])?$data['df']:false;
		if( isset($this->_rowbuffer[$this->_index]) )
			$this->_current = $this->_rowbuffer[$this->_index];
    }

	/**
	 * Creates a ResultSet from a serialized data string
	 *
	 * This is mainly needed for query caching
	 * @param string $data serialized data string
	 * @return ResultSet Restored ResultSet object
	 */
	static function &restore($data)
	{
		$buf = unserialize($data);
		$res = new ResultSet(model_datasource($buf['ds']),null);
		$res->_sql_used = $buf['sql'];
		$res->_arguments_used = $buf['args'];
		$res->_paging_info = $buf['paging_info'];
		$res->_field_types = $buf['field_types'];
		$res->_index = $buf['index'];
		$res->_rowbuffer = $buf['rows'];
		$res->_rowCount = isset($buf['rowCount'])?$buf['rowCount']:false;
		$res->_loaded_from_cache = true;
		$res->_data_fetched = isset($buf['df'])?$buf['df']:false;
		if( isset($res->_rowbuffer[$res->_index]) )
			$res->_current = $res->_rowbuffer[$res->_index];
		return $res;
	}

	/**
	 * Overrides parent to capture arguments
	 *
	 * We want to know which arguments are used, so we need to capture theme here
	 * before passing control to parents method.
	 * See <PDOStatement::bindvalue>
	 * @param string $parameter Parameter identifier. For a prepared statement using named placeholders, this will be a parameter name of the form :name. For a prepared statement using question mark placeholders, this will be the 1-indexed position of the parameter
	 * @param mixed $value The value to bind to the parameter.
	 * @param int $data_type Explicit data type for the parameter using the PDO::PARAM_* constants
	 * @return bool true or false
	 */
	function bindValue($parameter, $value, $data_type = null)
	{
		if( !$this->_arguments_used )
			$this->_arguments_used = [];
		$this->_arguments_used[$parameter] = $value;

        if( is_null($data_type) )
			return $this->_stmt->bindValue($parameter, $value);
		else
			return $this->_stmt->bindValue($parameter, $value, $data_type);
	}

	/**
	 * Overrides parent to capture query and arguments
	 *
	 * We want to know which query and arguments are used, so we need to capture theme here
	 * before passing control to parents method.
	 * See <PDOStatement::execute>
	 * @param array $input_parameters An array of values with as many elements as there are bound parameters in the SQL statement being executed
	 * @return bool true or false
	 */
	function execute($input_parameters = null)
	{
		if( !is_null($input_parameters) && !is_array($input_parameters) )
			$input_parameters = array($input_parameters);

		$this->_sql_used = $this->_stmt->queryString;
		if( !is_null($input_parameters) )
		{
			if( is_null($this->_arguments_used) )
				$this->_arguments_used = $input_parameters;
			else
				$this->_arguments_used = array_merge($this->_arguments_used,$input_parameters);
		}

		if( $this->_ds )
			$this->_ds->LastStatement = $this;

        $deadlock_retries = 0;
        do
        {
            try
            {
                if( is_null($input_parameters) )
                    $result = $this->_stmt->execute();
                else
                    $result = $this->_stmt->execute($input_parameters);
                hit_timer("WdfSqlPerformance", ResultSet::MergeSql($this->_ds,$this->_sql_used,$this->_arguments_used));
            }
            catch(\PDOException $ex)
            {
				// this is MySQL deadlock
				if( $deadlock_retries++<5 )
				{
					$ei = $this->_stmt->errorInfo();
					if( $ei && isset($ei[1]) && is_in($ei[1], 1213, 1205) )
					{
						usleep(10 * 1000);
						continue;
					}
				}

				// rethrow as WdfDbException to get more details
				\ScavixWDF\WdfDbException::RaisePdoEx($ex,$this);
			}

            break;
        }
        while(true);

		return $result;
	}

	/**
	 * Overrides parent for buffering
	 *
	 * See <PDOStatement::fetch>
	 * @param int $mode See php.net docs
	 * @param int $cursorOrientation See php.net docs
	 * @param int $cursorOffset See php.net docs
	 * @return mixed See php.net docs
	 */
    #[\ReturnTypeWillChange]
    function fetch(?int $mode = null, ?int $cursorOrientation = null, ?int $cursorOffset = null)
	{
		$this->_data_fetched = true;
		if( $this->_index < (count($this->_rowbuffer)-1) )
		{
			$this->_index++;
			$this->_current = $this->_rowbuffer[$this->_index];
			return $this->_current;
		}
		if( $this->_loaded_from_cache )
		{
			$this->_current = false;
			return false;
		}

		if( $mode == null && $this->FetchMode )
			$mode = $this->FetchMode;

		$this->_current = $this->_stmt->fetch(
            is_null($mode)?PDO::FETCH_DEFAULT:$mode,
            is_null($cursorOrientation)?PDO::FETCH_ORI_NEXT:$cursorOrientation,
            is_null($cursorOffset)?0:$cursorOffset
        );
		if( $this->_current !== false )
		{
			$this->_index = count($this->_rowbuffer);
			$this->_rowbuffer[] = $this->_current;
		}
		return $this->_current;
	}

	/**
	 * Overrides parent for buffering
	 *
	 * See <PDOStatement::fetchall>
	 * @param int $fetch_style See php.net docs
	 * @param int $column_index See php.net docs
	 * @param mixed $ctor_args See php.net docs
	 * @return mixed See php.net docs
	 */
	function fetchAll($fetch_style = null, $column_index = null, $ctor_args = null)
	{
		$this->_data_fetched = true;

		if( $this->_loaded_from_cache )
			return $this->_rowbuffer;

		if( $fetch_style == null && $this->FetchMode )
			$fetch_style = $this->FetchMode;

		// we need to set the default datasource as it is not passed thru ctor in all cases
		// so we just remember the default here and set it to this ones if they differ
		if( $fetch_style == PDO::FETCH_CLASS && Model::$DefaultDatasource != $this->_ds )
		{
			$mem_def_db = Model::$DefaultDatasource;
			Model::$DefaultDatasource = $this->_ds;
		}

		// weird calling because PHP doesnt know the nullarray type so we cannot just put null valued arguments to the parent
		if( is_null($ctor_args) )
			if( is_null($column_index) )
				if( is_null($fetch_style) )
					$this->_rowbuffer = $this->_stmt->fetchAll();
				else
					$this->_rowbuffer = $this->_stmt->fetchAll($fetch_style);
			else
				$this->_rowbuffer = $this->_stmt->fetchAll($fetch_style,$column_index);
		else
			$this->_rowbuffer = $this->_stmt->fetchAll($fetch_style, $column_index, $ctor_args);

		if( count($this->_rowbuffer) > 0 )
		{
			$this->_index = 0;
			$this->_current = $this->_rowbuffer[$this->_index];
		}

		// call init method on objects after they are loaded. Other methods do not work as documented :(
		if( $fetch_style == PDO::FETCH_CLASS )
		{
			$cnt = count($this->_rowbuffer);
			for( $i=0; $i<$cnt; $i++ )
			{
				$this->_rowbuffer[$i]->__constructed($this->_ds);
				$this->_rowbuffer[$i]->__init_db_values();
			}

			if( isset($mem_def_db) )
				Model::$DefaultDatasource = $mem_def_db;
		}
		return $this->_rowbuffer;
	}

	/**
	 * @shortcut <ResultSet::fetchAll>.
	 */
	function results($className=false)
	{
        if( !$this->_data_fetched )
			$this->fetchAll();

        if( !$className )
            return $this->_rowbuffer;

        $res = [];
        foreach( $this->_rowbuffer as $row )
            $res[] = Model::MakeFromData($row, $this->_ds, true, $className);
        return $res;
	}

	/**
	 * Returns a scalar value
	 *
	 * Will return the first result rows $column.
	 * @param int $column Column index
	 * @return mixed The value or false on error
	 */
	function fetchScalar($column=0)
	{
		$row = $this->fetch();
		if( !$row )
			return false;
		if( !isset($row[$column]) )
			return false;
		return $row[$column];
	}

	/**
	 * Returns a row
	 *
	 * Will return the first result row as array.
	 * Useful when for example querying for min/max values like this:
	 * <code php>
	 * list($min,$max) = $ds->ExecuteSql("SELECT min(a), max(a) FROM some_table")->fetchRow(false);
	 * </code>
	 * @param bool $assoc If true returns an associative array, else only array values are returned
	 * @return array|false The next rows values or false on error
	 */
	function fetchRow($assoc=true)
	{
		$row = $this->fetch();
		if( !$row )
			return false;
		return $assoc?$row:array_values($row);
	}

	/**
	 * Returns information about paging
	 *
	 * Result will be an array with these keys: 'rows_per_page', 'current_page', 'total_pages', 'total_rows', 'offset'
	 * @param mixed $key If given returns one of the keys value only
	 * @return array Paging info
	 */
	function GetPagingInfo($key=false)
	{
        if( !$this->_paging_info )
        {
            if( !$this->_ds || !$this->_ds->Driver )
                return $key?0:[];
			$this->_paging_info = $this->_ds->Driver->getPagingInfo($this->_stmt->queryString,$this->_arguments_used);
        }
		if( $key && isset($this->_paging_info[$key]) )
			return $this->_paging_info[$key];
		return $this->_paging_info;
	}

	/**
	 * Returns all values for a specified column
	 *
	 * Will build an array with all values for the specified column in this result sets rows.
	 * <code php>
	 * $ids = $dataSource->ExecuteSql("SELECT * FROM my_table WHERE id<1000")->Enumerate("id");
	 * $tables = $dataSource->ExecuteSql("SHOW TABLES")->Enumerate(0);
	 * </code>
	 * @param string|int $column_name Column to enumerate values for. If an integer is given will see that as zero-based index.
	 * @param bool $distinct True to array_unique, false to keep duplicates
	 * @param string $key_column_name If given uses this column as key for an associative resulting array
	 * @return array
	 */
	function Enumerate($column_name, $distinct=true, $key_column_name=false)
	{
		if( !$this->_data_fetched )
			$this->fetchAll();
		$res = [];
		if( is_integer($column_name) && count($this->_rowbuffer)>0 )
		{
			$temp = array_keys($this->_rowbuffer[0]);
			$column_name = $temp[$column_name];
		}
		foreach( $this->_rowbuffer as $row )
		{
			if( $distinct && in_array($row[$column_name], $res) )
                continue;
			if( $key_column_name && is_string($key_column_name) )
                $res[$row[$key_column_name]] = $row[$column_name];
            else
                $res[] = $row[$column_name];
		}
		return $res;
	}

	/**
	 * Calls a callback function for each result dataset.
	 *
	 * Callback function will receive each row as array and must return the (eventually changed) array.
	 * Note that this method will not clone the result, but return the object itself!
	 * @param mixed $callback Anonymous callback function
	 * @return ResultSet Returns `$this`
	 */
	function Process($callback)
	{
		if( !$this->_data_fetched )
			$this->fetchAll();

		$cnt = count($this->_rowbuffer);
		if( $cnt > 0 )
		{
			for($i=0; $i<$cnt; $i++)
				$this->_rowbuffer[$i] = $callback($this->_rowbuffer[$i]);
			$this->_current = $this->_rowbuffer[$this->_index];
		}
		return $this;
	}

	/**
	 * Returns the number of affected rows.
	 *
	 * @return int Number of affected rows
	 */
	function Count()
	{
		return $this->rowCount();
	}

	/**
	 * @override Make sure that SqLite returns something on SELECT statements too
	 */
	function rowCount()
	{
		if( $this->_rowCount === false )
		{
			if( $this->_ds->Driver instanceof MySql )
				$this->_rowCount = $this->_stmt->rowCount();
			elseif( !starts_with(trim(strtolower($this->_sql_used)),'select') )
				$this->_rowCount = $this->_stmt->rowCount();
			else
			{
				$stmt = $this->_pdo->prepare("SELECT count(*) FROM( {$this->_sql_used} ) as x");
				$stmt->execute($this->_arguments_used);
				$this->_rowCount = $stmt->finishScalar();
			}
		}
		return $this->_rowCount;
	}

    /**
     * @internal Closes the inner statements cursor if present
     */
    function closeCursor()
	{
        if( $this->_stmt )
            $this->_stmt->closeCursor();
    }

	/**
	 * @implements <ArrayAccess::offsetExists>
	 */
	public function offsetExists($offset): bool
	{
		if( !$this->_current ) $this->_current = $this->fetch();
		return isset($this->_current[$offset]);
	}

	/**
	 * @implements <ArrayAccess::offsetGet>
	 */
    #[\ReturnTypeWillChange]
	public function offsetGet($offset):mixed
	{
		if( !$this->_current ) $this->_current = $this->fetch();
		return isset($this->_current[$offset]) ? $this->_current[$offset] : null;
	}

	/**
	 * @implements <ArrayAccess::offsetSet>
	 */
	public function offsetSet($offset, $value):void
	{
		if( !$this->_current ) $this->_current = $this->fetch();
		$this->_current[$offset] = $value;
	}

	/**
	 * @implements <ArrayAccess::offsetUnset>
	 */
	public function offsetUnset($offset):void
	{
		if( !$this->_current ) $this->_current = $this->fetch();
		unset($this->_current[$offset]);
	}

	/**
	 * @implements <Iterator::current>
	 */
    #[\ReturnTypeWillChange]
	public function current():mixed{
		if( !$this->_current ) $this->_current = $this->fetch();
		return $this->_current;
	}

	/**
	 * @implements <Iterator::key>
	 */
    #[\ReturnTypeWillChange]
	public function key():mixed {
		return $this->_index;
	}

	/**
	 * @implements <Iterator::next>
	 */
	public function next(): void {
		$this->_current = $this->fetch();
	}

	/**
	 * @implements <Iterator::rewind>
	 */
	public function rewind(): void {
		$this->_index = -1;
        $this->_current = false;
	}

	/**
	 * @implements <Iterator::valid>
	 */
	public function valid(): bool {
		if( !$this->_current ) $this->_current = $this->fetch();
		return $this->_current !== false;
	}
}

/**
 * @internal Extends PDOStatement so that we can easily capture calling <DataSource>
 */
class WdfPdoStatement extends PDOStatement
{
	public $_ds = null;
	public $_pdo = null;

	protected function __construct($datasource,$pdo)
	{
		$this->_ds = $datasource;
		$this->_pdo = $pdo;
	}

    function finishAll()
    {
        $res = $this->fetchAll();
        $this->closeCursor();
        return $res;
    }

    function finishScalar($column_index=0)
    {
        $res = $this->fetchColumn($column_index);
        $this->closeCursor();
        return $res;
    }
}
