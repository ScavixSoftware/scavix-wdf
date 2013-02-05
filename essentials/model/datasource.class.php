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
 * Provides access to a database.
 * Use this to execute SQL statements directly when you need to do so.
 * Currently only tested in combination with BoaModel and BoaSchema classes.
 */
class DataSource extends System_DataSource
{
    private $_dsn;
	private $_username;
	private $_password;
    private $_pdo;
	
	private $_last_affected_rows_count = 0;
	
    public $_storage_id;
	public $Driver;
	public $LastStatement = false;
    
    function __construct($alias=false, $dsn=false, $username=false, $password=false)
    {
		if( !$alias || !$dsn )
			return;
		
		$test = parse_url($dsn);
		if( isset($test['host']) )
		{
			if( $username || $password )
				log_warn("Oldschool DSN overrides username and/or password given");
			$dsn = "{$test['scheme']}:host={$test['host']};dbname=".trim($test['path'],' /').";";
			$username = $test['user'];
			$password = $test['pass'];
		}
		
        $this->_storage_id = $alias;
        $this->_dsn = $dsn;
		$this->_username = $username;
		$this->_password = $password;
		
        $this->_pdo = new PdoLayer($dsn,$username,$password);
		if( !$this->_pdo )
			throw new WdfException("Something went horribly wrong with the PdoLayer");
		$this->_pdo->setAttribute( PDO::ATTR_STATEMENT_CLASS, array( "ResultSet", array($this) ) );

		$driver = $this->_pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
		switch( $driver )
		{
			case 'sqlite': 
                // trick out the autoloader as it consults the cache which needs a model thus circular...
                require_once(__DIR__.'/driver/sqlite.class.php');
                $this->Driver = new SqLite(); 
                break;
			case 'mysql': 
                // trick out the autoloader as it consults the cache which needs a model thus circular...
                require_once(__DIR__.'/driver/mysql.class.php');
                $this->Driver = new MySql(); 
                break;
			default: throw new Exception("Unknown DB driver: $driver");
		}
		$this->Driver->initDriver($this,$this->_pdo);
    }
	
	function __get($varname)
	{
		/*--- Compatibility to old model ---*/
		switch($varname)
		{
			case "DB": return $this;
		}
	}
	
	function __sleep()
	{
		return array('_storage_id');
	}
	
	function __wakeup()
	{
		global $CONFIG;
		if( isset($CONFIG['session']) && isset($CONFIG['session']['own_serializer']) && $CONFIG['session']['own_serializer'] )
		{
			$name = explode("::",$this->_storage_id,2);
			if( count($name) < 2 )
				$name = array($this->_storage_id,$this->_storage_id);

			$ds = model_datasource($name[1]);
			if( $ds != null )
			{
				$this->_storage_id = $ds->_storage_id;
				$this->_dsn = $ds->_dsn;
				$this->_username = $ds->_username;
				$this->_password = $ds->_password;
				$this->_pdo = $ds->_pdo;
				$this->Driver = $ds->Driver;
			}
			else
			{
				error_log("hahahahah");
				register_hook(HOOK_POST_INITSESSION,$this,'__wakeup_extended');
			}
		}
		else
		{
			error_log(var_export(debug_backtrace(),true));
			register_hook(HOOK_POST_INITSESSION,$this,'__wakeup_extended');
		}
	}
	
	function __wakeup_extended()
	{
		$name = explode("::",$this->_storage_id,2);
		if( count($name) < 2 )
			$name = array($this->_storage_id,$this->_storage_id);

		$ds = model_datasource($name[1]);
		$this->_storage_id = $ds->_storage_id;
		$this->_dsn = $ds->_dsn;
		$this->_username = $ds->_username;
		$this->_password = $ds->_password;
		$this->_pdo = $ds->_pdo;
		$this->Driver = $ds->Driver;
		log_debug($this->_storage_id." -> ".$this->Database(),"HOOK::__wakeup_extended");
	}
	
	function __equals(&$ds)
	{
		if( !is_object($ds) || get_class($this) != get_class($ds) )
			return false;

		return $this->_dsn == $ds->_dsn && $this->_username == $ds->_username && $this->_password == $ds->_password;
	}
	
	function GetDsn()
	{
		return $this->_dsn;
	}
	
	function EscapeArgument($value)
	{
		$res = $this->_pdo->quote($value);
		return substr($res, 1, count($res)-2);
	}
	
	function QuoteArgument($value)
	{
		return $this->_pdo->quote($value);
	}

	function Prepare($sql)
	{
		$stmt = $this->_pdo->prepare($sql);
		if( !$stmt )
			throw new Exception("Invalid SQL: $sql");
		return $stmt;
	}

	function ExecuteSql($sql,$parameter=array())
	{
		if( !is_array($parameter) )
			$parameter = array($parameter);

		if( count($parameter)==0 )
		{
			$ret = $this->_pdo->query($sql);
			$error = $this->_pdo->errorInfo();
			if( ($error[0] != "") && ($error[0] != "00000") )
				throw new Exception("SQL Error: ".$this->ErrorMsg ()."\n$sql");
			return $ret;
		}
		
		$stmt = $this->Prepare($sql);
		if( !$stmt->execute($parameter) )
			throw new Exception("SQL Error: ".$stmt->ErrorOutput()."\n$sql\n".my_var_export($parameter));
		$this->_last_affected_rows_count = $stmt->rowCount();
		return $stmt;
	}
	
	function EmptySQLCache()
	{
		// todo: implement logic here and add caching capabilities to Cache* methods
	}
	
	function CacheExecuteSql($sql,$prms=array(),$lifetime=300) // $lifetime in seconds
	{
		if( !system_is_module_loaded('globalcache') )
			return $this->ExecuteSql($sql, $prms);
		
		$key = 'DB_Cache_Sql_'.md5( $sql.serialize($prms) );
		$null = null;
		if( is_null($res = cache_get($key, $null, true, false)) )
		{
			$res = $this->ExecuteSql($sql, $prms);
			if( $res )
			{
				$res->fetchAll();
				$data = $res->serialize();
				cache_set($key, $data, $lifetime, true, false);
			}
		}
		else
			$res = ResultSet::unserialize($res);
		return $res;
	}
	
	function CacheDLookUp($field_name, $table_name = "", $where_condition = "", $parameter = array(),$lifetime=300)
	{
		if( !system_is_module_loaded('globalcache') )
			return $this->DLookUp($field_name, $table_name, $where_condition, $parameter);
		
		$key = 'DB_Cache_Look_'.md5( $field_name.$table_name.$where_condition.serialize($parameter) );
		$null = null;
		if( is_null($res = cache_get($key, $null, true, false)) )
		{
			$res = $this->DLookUp($field_name, $table_name, $where_condition, $parameter);
			cache_set($key, $res, $lifetime, true, false);
		}
		return $res;
	}
	
	function Query($tablename)
	{
		return new CommonModel($this,$tablename);
	}
	
	/*--- Compatibility to old model ---*/
	function CreateInstance($type,$where=false,$prms=array())
	{
		$res = new $type($this);
		if( $where )
			$res->Load($where,$prms);
		return $res;
	}
	
	function ModelFromArray($type,$fields,$as_new=false)
	{
		$obj = new $type($this);
		$obj->__init_db_values($as_new);
		$attr = array_change_key_case(array_flip($obj->GetColumnNames()), CASE_LOWER);
		foreach( $fields as $k=>$v )
			if( array_key_exists(strtolower($k), $attr) )
				$obj->$k = $v;
		return $obj;
	}
	
	function TableExists($name)
	{
		return $this->Driver->tableExists($name);
	}
	
	function Now($seconds_to_add=0)
	{
		// Reactivating DB querying because this is cause of MANY error (we only see the obvious: PamFax SPH are reregistering all the time)
		// Sample from DEV system: 
		// - SELECT NOW() -> 2012-01-25 09:42:59
		// - time()       -> 2012-01-25 08:43:36
		// So even our DEV system is more that half a minute off -> this is not acceptable!		
		// Dont think that using wrong values for performance reasons is best practice!
		$sql = $this->Driver->Now($seconds_to_add);
		$rs = $this->CacheExecuteSql("SELECT $sql as dt",array(),1);
		return $rs->fields['dt'];
		
		
		// For performance reason we use system time here, not the DB servers time.
		// This is in fact wrong as for example MySQL *may* use other time settings than the system.
		$res = time() + $seconds_to_add;
		return date("Y-m-d H:i:s", $res);
	}
	
	function TableForType($type)
	{
		$obj = new $type($this);
		return $obj->GetTableName();
	}
	
	function PrepareWhere($sql)
	{
		return $sql;
		$stmt = $this->Prepare($sql);
		return $stmt->GetSql();
	}
	
	function ExecuteScalar($sql,$prms=array())
	{
		$stmt = $this->Prepare($sql);
		$stmt->execute($prms);
		$this->_last_affected_rows_count = $stmt->rowCount();
		return $stmt->fetchColumn();
	}
	
	function CacheExecuteScalar($sql,$prms=array(),$lifetime=300)
	{
		if( !system_is_module_loaded('globalcache') )
			return $this->ExecuteScalar($sql, $prms);
		
		$key = 'SB_Cache_Scalar_'.md5( $sql.serialize($prms) );
		$null = null;
		if( is_null($res = cache_get($key, $null, true, false)) )
		{
			$res = $this->ExecuteScalar($sql, $prms);
			cache_set($key, $res, $lifetime, true, false);
		}
		return $res;
	}
	
	function GetOne($sql,$prms=array())
	{
		return $this->ExecuteScalar($sql,$prms);
	}
	
	function DLookUp($field_name, $table_name = "", $where_condition = "", $parameter = array())
	{
		$sql = "SELECT " . $field_name . " ". ($table_name ? "FROM " . $table_name : "") . ($where_condition ? " WHERE " . $where_condition : "")." LIMIT 1";
		$res = $this->ExecuteScalar($sql,$parameter);
		return $res===false?null:$res;
	}
	
	function Select($type,$where="",$prms=false,$page=-1,$rows_per_page=-1)
	{
		// ignoring (also previously) ignored arguments $page and $rows_per_page
		$dummy = new $type($this);
		return $dummy->Find($where,$prms);
	}
	
	function PageExecute($sql,$items_per_page,$page,$parameter=array())
	{
		$stmt = $this->Driver->getPagedStatement($sql,$page,$items_per_page);
		if( !$stmt->execute($parameter) )
			log_error("SQL Error: $sql",$parameter);
		$this->_last_affected_rows_count = $stmt->rowCount();
		return $stmt;
	}
	
	function Execute($sql,$args=array())
	{
		return $this->ExecuteSql($sql,$args);
	}
	
	function _Execute($sql,$args=array())
	{
		return $this->ExecuteSql($sql,$args);
	}
	
	function ErrorMsg()
	{
		$ei = $this->_pdo->errorInfo();
		if( count($ei) == 1 && $ei[0] === "00000" )
			return false;
		if( count($ei) == 0 )
			return false;
		return $ei[2];
	}
	
	function getAffectedRowsCount()
	{
		return $this->_last_affected_rows_count;
	}
	
	function Affected_Rows()
	{
		return $this->_last_affected_rows_count;
	}
	
	function Host()
	{
		if( !preg_match('/host=([^;]+);*/', $this->_dsn.";", $m) )
			return false;
		return trim($m[1]);
	}
	function Username()
	{
		return $this->_username;
	}
	function Password()
	{
		return $this->_password;
	}
	function Database()
	{
		if( !preg_match('/dbname=([^;]+);*/', $this->_dsn, $m) )
			return false;
		return trim($m[1]);
	}
}

?>
