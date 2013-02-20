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
 
/**
 * This is base class for data objects.
 * It provides all the stuff to handle DB access really simple following the
 * ActiveRecord paradigm.
 * Implements Iterator, Countable and ArrayAccess for ease of use in for and foreach loops.
 * Also has methods like all(), like() and so on to access your data the really easy way:
 * <code>
 * $some_does = MyModelClass::Make()->where('OR')->like('firstname','%john%')->equal('lastname','doe');
 * foreach( $some_does as $sd ) echo $sd;
 * </code>
 */
abstract class Model implements Iterator, Countable, ArrayAccess
{
	abstract function GetTableName();
	public static $DefaultDatasource = false;
	
	protected static $_schemaCache = array();
	private static $_typeMap = array();
	protected $_className = false;
	protected $_isInherited = false;
	protected $_cacheKey;
	
    protected $_ds = false;
    protected $_tableSchema = false;

	protected $_query = false;
	protected $_results = false;
	protected $_index = 0;
	protected $_fieldValues = array();
	protected $_dbValues = array();
	
	protected $_querySql = false;
	protected $_queryArgs = array();
	
	var $_saved = false;

	function rewind() { $this->_index = 0; }
    function current() { $this->__ensureResults(); return isset($this->_results[$this->_index])?$this->_results[$this->_index]:null; }
    function key() { return $this->_index; }
    function next() { $this->_index++; }
    function valid() { $this->__ensureResults(); return isset($this->_results[$this->_index]); }

	function count(){ $this->__ensureResults(); return count($this->_results); }

	public function offsetSet($offset, $value)
	{ $this->__ensureResults(); $this->_results[$offset] = $value; }
    public function offsetExists($offset)
	{ $this->__ensureResults(); return isset($this->_results[$offset]); }
    public function offsetUnset($offset)
	{ $this->__ensureResults(); unset($this->_results[$offset]); }
    public function offsetGet($offset)
	{ $this->__ensureResults(); return isset($this->_results[$offset]) ? $this->_results[$offset] : null; }

	function enumerate($property_or_fieldname, $distinct=true)
	{
		$res = array();
		foreach( $this as $tmp )
			if( !$distinct || !in_array($tmp->$property_or_fieldname, $res) )
				$res[] = $tmp->$property_or_fieldname;
		return $res;
	}

	public function IsQuery()
	{
		return $this->_query;
	}
	
	public function IsRow()
	{
		return !$this->IsQuery();
	}
	
	public function LogDebug()
	{
		$this->__ensureResults();
		$this->_ds->LastStatement->LogDebug();
	}

    function __construct($datasource=null)
    {
		$this->_className = get_class($this);
		$this->_isInherited = $this->_className != "Model";
		if( !$datasource && self::$DefaultDatasource )
			$this->__initialize(self::$DefaultDatasource);
		elseif( $datasource )
			$this->__initialize($datasource);
	}
	
	function __initialize($datasource=null)
	{
		if( $datasource && !($datasource instanceof DataSource) )
			WdfDbException::Raise("Invalid argument. Object of type DataSource expected",$datasource);
		
        $this->_ds = $datasource;
		if( $this->_ds )
		{
			if( !isset($this->_cacheKey) || !$this->_cacheKey )
				$this->_cacheKey = $this->_ds->Database().$this->_className;
			$this->__ensureTableSchema();
		}
		else
			log_trace("Missing datasource argument");
    }
	
	function __wakeup()
	{ 
		$q = $this->_ds->Query($this->GetTableName());
		foreach( $this->GetPrimaryColumns() as $pk )
			$q = $q->eq($pk,$this->$pk);
		$q = $q->current();
		foreach( $this->GetColumnNames() as $cn )
			$this->$cn = $q->$cn;
		$this->__init_db_values();
	}
	
	function __init_db_values($known_as_empty=false)
	{
		$this->_saved = !$known_as_empty;
		$this->_dbValues = array();
		if( !$this->_tableSchema )
			$this->__ensureTableSchema();
		foreach( $this->_tableSchema->ColumnNames() as $col )
		{
			if( $known_as_empty )
			{
				$this->$col = null;
				$this->_dbValues[$col] = null;
			}
			else
			{
				$this->$col = !isset($this->$col)?null:$this->__typedValue($col); // do not use $this->TypedValue because may be overridden
				$this->_dbValues[$col] = $this->$col;
			}
		}
	}
	
	private function __typeOf($column_name)
	{
		if( isset(self::$_typeMap[$this->_cacheKey][$column_name]) )
			self::$_typeMap[$this->_cacheKey][$column_name];
				
		if( !$this->_tableSchema )
			$this->__ensureTableSchema();
		if( $res = $this->_tableSchema->TypeOf($column_name) )
		{
			self::$_typeMap[$this->_cacheKey][$column_name] = $res;
			return $res;
		}
		if( isset($this->$column_name) )
		{
			self::$_typeMap[$this->_cacheKey][$column_name] = gettype($this->$column_name);
			return self::$_typeMap[$this->_cacheKey][$column_name];
		}
		return false;
	}
	
	private function __typedValue($column_name)
	{
		if( !isset($this->$column_name) )
			return null;
		return $this->__toTypedValue($column_name, $this->$column_name);
	}
	
	private function __toTypedValue($column_name,$value)
	{
		if( isset(self::$_typeMap[$this->_cacheKey][$column_name]) )
			$t = self::$_typeMap[$this->_cacheKey][$column_name];
		else
			$t = $this->__typeOf($column_name);
		
		switch( $t )
		{
			case 'int':
			case 'integer':
				return intval($value);
			case 'float':
			case 'double':
				return floatval($value);
			case 'date':
			case 'time':
			case 'datetime':
			case 'timestamp':
				
				try
				{
					return Model::EnsureDateTime($value);
				}
				catch(Exception $ex)
				{
					WdfException::Log("date/time error with value '$value'",$ex);
				}
				break;
		}
		return $value;
	}

	public function __clone()
	{
		if( $this->_query )
			$this->_query = clone $this->_query;
		$this->_results = false;
		$this->_index = 0;
	}
	
	protected function __ensureTableSchema()
	{
		if( $this->_tableSchema )
			return $this->_tableSchema;
		
		if( !$this->_ds )
			WdfDbException::Raise("Missing Datasource");

		if( !isset(self::$_schemaCache[$this->_cacheKey]) )
		{
			self::$_schemaCache[$this->_cacheKey] = $this->_tableSchema = $this->_ds->Driver->getTableSchema($this->GetTableName());
		}
		else
		{
			$this->_tableSchema = self::$_schemaCache[$this->_cacheKey];
		}
		if( !($this->_tableSchema) )
			WdfDbException::Raise("Error using table schema");
		
		return $this->_tableSchema;
	}
	
	protected function __ensureResults($ctor_args=null)
	{
		if( $this->_results === false )
		{
			if( $this->_query )
				$this->_results = $this->_query->__execute($this->_querySql,$this->_queryArgs,$ctor_args);
			$this->_index = 0;
		}
	}
	
	public function scalar($property,$default=null)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->setResultFields($property);
		$res->_query->limit(0,1);
		$res->__ensureResults();
		$res = $res->current();
		if( $res && isset($res->$property) )
			return $res->$property;
		return $default;
	}
	
	public function GetPagingInfo($key=false)
	{
		$this->__ensureResults();
		return $this->_query->GetPagingInfo($key);
	}
	
	public function FieldValues()
	{
		$res = array();
		foreach( func_get_args() as $col )
			$res[] = $this->__typedValue($col);
		return $res;
	}
	
	/**
	 * Wrapper around private method to allow overriding without breaking internal functionality
	 */
	public function TypeOf($column_name)
	{
		return $this->__typeOf($column_name);
	}
	
	/**
	 * Wrapper around private method to allow overriding without breaking internal functionality
	 */
	public function TypedValue($column_name)
	{
		return $this->__typedValue($column_name);
	}

	public static function &Select($datasource)
	{
		$className = get_called_class();
		$res = new $className($datasource);
		$res->__ensureSelect();
		return $res;
	}
	
	public static function &Query($sql,$args=array(),$datasource=null)
	{
		$className = get_called_class();
		$res = new $className($datasource);
		$res->__ensureSelect();
		$res->_querySql = $sql;
		$res->_queryArgs = $args;
		return $res;
	}

	public static function &Make($datasource=null,$pk_value=false)
    {
		$className = get_called_class();
		$res = new $className($datasource);
		if( $pk_value !== false )
		{
			$q = $res->where("AND");
			$pkcols = $res->GetPrimaryColumns();
			if( count($pkcols) == 1 && !is_array($pk_value) )
				$pk_value = array($pkcols[0] => $pk_value);
			foreach( $pkcols as $pkc )
			{
				if( !isset($pk_value[$pkc]) )
					WdfDbException::Raise("Missing value for primary key column '{$pkc}'");
				$q = $q->equal($pkc,$pk_value[$pkc]);
			}
			if( count($q) > 0 )
			{
				$q = $q[0];
				return $q;
			}
			return false;
		}
		return $res;
    }
	
	public static function EnsureDateTime($value,$convert_now_to_value=false)
	{
		if( $value instanceof DateTimeEx )
			return $value;
		if( $value instanceof DateTime )
			return DateTimeEx::Make($value);
		if( is_string($value) )
		{
			// special handling for NOW() argument
			if( strtolower($value) == "now()" )
			{
				if( $convert_now_to_value )
					return new DateTimeEx();
				return "now()";
			}
			// check if we have a timestamp as string
			if( is_numeric($value) )
			{
				$res = new DateTimeEx();
				$res->setTimestamp(intval($value));
				return $res;
			}
			else
				// add eventually missing fractional seconds part to ISO 8601 format
				$value = preg_replace(
						'/([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})([+-]{1})([0-9]{2}):([0-9]{2})/',
						'$1-$2-$3T$4:$5:$6.00$7$8:$9',
						$value);
		}
		elseif( is_integer($value) )
		{
			$res = new DateTimeEx();
			$res->setTimestamp($value);
			return $res;
		}
		return new DateTimeEx($value);
	}
	
	public function GetPrimaryColumns()
	{
		return $this->__ensureTableSchema()->PrimaryColumnNames();
	}

	public function GetColumnNames($changed_only = false)
	{
		if( !$changed_only )
			return $this->__ensureTableSchema()->ColumnNames();
		
		$res = array();
		//$cols = array_diff(array_keys(get_object_vars($this)), array_keys(get_class_vars(get_class($this))));
		foreach( $this->__ensureTableSchema()->ColumnNames() as $col )
		{
			if( isset($this->$col) )
			{
				if( !isset($this->_dbValues[$col]) )
					$res[] = $col;
				else
				{
					$v1 = $this->$col;
					if( $v1 instanceof DateTime )
						$v1 = $v1->format('U');
					
					$v2 = $this->_dbValues[$col];
					if( $v2 instanceof DateTime )
						$v2 = $v2->format('U');
					
					if( $v1 != $v2 )
						$res[] = $col;
				}
			}
			elseif( !isset($this->$col) && isset($this->_dbValues[$col]) )
				$res[] = $col;
			elseif( (!isset($this->$col) || is_null($this->$col)) && (!isset($this->_dbValues[$col]) || !is_null($this->_dbValues[$col])) )
				$res[] = $col;
		}
		return $res;
		//return array_keys($this->_changedColumns);
	}

	public function HasColumn($name)
	{
		return $this->__ensureTableSchema()->HasColumn($name);
	}

	public function FullQualifiedFieldName($name)
	{
		return "`{$this->GetTableName()}`.".$this->__ensureFieldname($name);
	}
	
	public function AsArray()
	{
		$res = array();
		foreach( $this->GetColumnNames() as $cn )
			$res[$cn] = $this->__typedValue($cn);
		return $res;
	}
	
	public function SanitizeValues()
	{
		$vals = $this->AsArray();
		system_sanitize_parameters($vals);
		foreach( $vals as $k=>$v )
			$this->$k = $v;
	}

	function __ensureFieldname($name)
	{
		if( $this->HasColumn($name) )
			return $name;
		WdfDbException::Raise("Unknown column '$name' in table '{$this->_tableSchema->Name}'");
	}

	private function __ensureSelect()
	{
		if( !$this->_query )
			$this->_query = new SelectQuery($this,$this->_ds);
	}

	public function all()
	{
		$res = clone $this;
		$res->__ensureSelect();
		return $res;
	}

	function andAll()
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->andAll();
		return $res;
	}

	function orAll()
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->orAll();
		return $res;
	}

	function andX($count)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->andX($count);
		return $res;
	}

	function orX($count)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->orX($count);
		return $res;
	}

	public function where($defaultOperator = "AND")
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->where($defaultOperator);
		return $res;
	}

	public function having($defaultOperator = "AND")
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->having($defaultOperator);
		return $res;
	}

	function eq($property,$value,$value_is_sql=false) { return $this->equal($property,$value,$value_is_sql); }
	public function equal($property,$value,$value_is_sql=false)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->equal($this->__ensureFieldname($property),$value_is_sql?$value:$this->__toTypedValue($property,$value),$value_is_sql);
		return $res;
	}
	
	function neq($property,$value) { return $this->notEqual($property,$value); }
	public function notEqual($property,$value)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->notEqual($this->__ensureFieldname($property),$this->__toTypedValue($property,$value));
		return $res;
	}
	
	function lte($property,$value) { return $this->lowerThanOrEqualTo($property,$value); }
	public function lowerThanOrEqualTo($property,$value)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->lowerThanOrEqualTo($this->__ensureFieldname($property),$this->__toTypedValue($property,$value));
		return $res;
	}
	
	function lt($property,$value) { return $this->lowerThan($property,$value); }
	public function lowerThan($property,$value)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->lowerThan($this->__ensureFieldname($property),$this->__toTypedValue($property,$value));
		return $res;
	}
	
	function gte($property,$value) { return $this->greaterThanOrEqualTo($property,$value); }
	public function greaterThanOrEqualTo($property,$value)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->greaterThanOrEqualTo($this->__ensureFieldname($property),$this->__toTypedValue($property,$value));
		return $res;
	}
	
	function gt($property,$value) { return $this->greaterThan($property,$value); }
	public function greaterThan($property,$value)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->greaterThan($this->__ensureFieldname($property),$this->__toTypedValue($property,$value));
		return $res;
	}
	
	public function binary($property,$value)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->andX(2);
		$res->_query->equal($this->__ensureFieldname($property),$this->__toTypedValue($property,$value));
		$res->_query->binary($this->__ensureFieldname($property),$this->__toTypedValue($property,$value));
		return $res;
	}

	public function like($property,$value)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->like($this->__ensureFieldname($property),"$value");
		return $res;
	}

	public function rlike($property,$value)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->rlike($this->__ensureFieldname($property),"$value");
		return $res;
	}

	public function in($property,$values)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->in($this->__ensureFieldname($property),$values);
		return $res;
	}
	
	public function notIn($property,$values)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->notIn($this->__ensureFieldname($property),$values);
		return $res;
	}

//	public function isBlank($property)
//	{
//		$res = clone $this;
//		$res->__ensureSelect();
//		$res->_query->blank($this->__ensureFieldname($property));
//		return $res;
//	}
//
//	public function notBlank($property)
//	{
//		$res = clone $this;
//		$res->__ensureSelect();
//		$res->_query->notBlank($this->__ensureFieldname($property));
//		return $res;
//	}

	public function isNull($property)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->isNull($this->__ensureFieldname($property));
		return $res;
	}

	public function notNull($property)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->notNull($this->__ensureFieldname($property));
		return $res;
	}

	public function orderBy($property,$direction = "ASC")
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->orderBy($this->__ensureFieldname($property),$direction);
		return $res;
	}
	
	public function shuffle()
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->orderBy('rand()','');
		return $res;
	}

	public function groupBy($property)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->groupBy($this->__ensureFieldname($property));
		return $res;
	}

	public function limit($limit)
	{
		return $this->page(0,$limit);
	}

	public function page($offset,$items)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->limit($offset,$items);
		return $res;
	}

	/**
	 * Join two database tables
	 * @param string $direction E.g. 'LEFT', 'RIGHT' or 'FULL'. Also 'LEFT OUTER'.
	 * @param object $model An instance of a Model subclass.
	 */
	public function join($direction,$model)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->join($direction,$model);
		return $res;
	}
	
	public function newerThan($property,$value,$interval)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->newerThan($property,$this->__toTypedValue($property,$value),$interval);
		return $res;
	}
	
	public function olderThan($property,$value,$interval)
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->olderThan($property,$this->__toTypedValue($property,$value),$interval);
		return $res;
	}
	
	public function noop()
	{
		$res = clone $this;
		$res->__ensureSelect();
		$res->_query->noop();
		return $res;
	}
	
	/*--- Currently only used from Serializer ---*/
	function DataSourceName()
	{
		if( $this->_ds )
			return $this->_ds->_storage_id;
		elseif( self::$DefaultDatasource )
			return self::$DefaultDatasource->_storage_id;
		WdfDbException::Raise("Model has no valid DataSource");
	}
	
	/*--- Compatibility to old model ---*/
	public function Load($where, $arguments=false)
	{
		$q = new SelectQuery($this, $this->_ds);
		$sql = $q->__toString()." WHERE ".$where;
		
		if( $arguments !== false && !is_array($arguments) ) $arguments = array($arguments);
		$q = $this->_ds->ExecuteSql($sql,$arguments);
		if( $q->rowCount() > 0 )
		{
			foreach( $this->GetColumnNames() as $col )
				$this->$col = $q[$col];
			$this->_query = false;
			$this->_results = false;
			$this->_index = 0;
			$this->__init_db_values();
			return true;
		}
		else
			$this->__init_db_values(true);
		
		return false;
	}
	
	public function Save()
	{
		$args = array();
		$stmt = $this->_ds->Driver->getSaveStatement($this,$args);

		if( !$stmt )
			return true; // nothing to save
				
		if( !$stmt->execute($args) )
			WdfDbException::Raise(my_var_export($stmt->ErrorOutput()));

		$pkcols = $this->GetPrimaryColumns();
		if( count($pkcols) == 1 )
		{
			$id = $pkcols[0];
			if( !isset($this->$id) )
				$this->$id = $this->_ds->LastInsertId();
		}
		$this->__init_db_values();
		return true;
	}
	
	public function Delete()
	{
		$args = array();
		$stmt = $this->_ds->Driver->getDeleteStatement($this,$args);
		if( !$stmt || !$stmt->execute($args) )
		{
			if( $stmt )
				log_error(get_class($this)."->Delete failed: ",$stmt->ErrorOutput());
			else
				log_error(get_class($this)."->Delete failed: ",$this);
			return false;
		}
		return true;
	}
	
	public function Find($where="",$prms=array())
	{
		$sql = "SELECT * FROM ".$this->GetTableName().($where?" WHERE $where":"");
		$q = new SelectQuery($this, $this->_ds);
		return $q->__execute($sql, $prms);
	}
}
