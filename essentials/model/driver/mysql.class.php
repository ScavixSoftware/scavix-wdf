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
namespace ScavixWDF\Model\Driver;

use DateTime;
use Exception;
use PDO;
use ScavixWDF\Model\ColumnSchema;
use ScavixWDF\Model\ResultSet;
use ScavixWDF\Model\TableSchema;
use ScavixWDF\ToDoException;
use ScavixWDF\WdfDbException;

/**
 * MySQL database driver.
 *
 */
class MySql implements IDatabaseDriver
{
	private $_ds;
	private $_pdo;
    private $_tableexistbuffer = [];

	/**
	 * @implements <IDatabaseDriver::initDriver>
	 */
	function initDriver($datasource,$pdo)
	{
        global $CONFIG;
		$this->_ds = $datasource;
		$this->_pdo = $pdo;
        if(isset($CONFIG['model'][$datasource->_storage_id]) && isset($CONFIG['model'][$datasource->_storage_id]['bufferedquery']) && $CONFIG['model'][$datasource->_storage_id]['bufferedquery'])
            $this->_pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        $charset = 'utf8';
        if(isset($CONFIG['model'][$datasource->_storage_id]) && isset($CONFIG['model'][$datasource->_storage_id]['charset']) && $CONFIG['model'][$datasource->_storage_id]['charset'])
            $charset = $CONFIG['model'][$datasource->_storage_id]['charset'];

        $mode = $this->_pdo->query("SELECT @@SESSION.sql_mode; SET CHARACTER SET $charset; SET NAMES $charset;")
            ->finishScalar();
        if( stripos($mode,"STRICT_ALL_TABLES")!==false || stripos($mode,"STRICT_TRANS_TABLES")!==false )
        {
            $mode = str_ireplace(["STRICT_ALL_TABLES","STRICT_TRANS_TABLES"], ["",""], $mode);
            $mode = implode(",",array_filter(explode(",",$mode)));
            $this->_pdo->exec("SET sql_mode = '$mode';");
        }
        $this->_pdo->Driver = $this;
	}

	/**
	 * @implements <IDatabaseDriver::listTables>
	 */
	function listTables()
	{
		$sql = 'SHOW TABLES';
		$tables = [];
		foreach($this->_pdo->query($sql)->finishAll() as $row)
        {
			$tables[] = $row[0];
            $this->_tableexistbuffer[$row[0]] = true;
        }
		return $tables;
	}

	/**
	 * @implements <IDatabaseDriver::getTableSchema>
	 */
    function &getTableSchema_bak($tablename)
	{
		$sql = 'SHOW CREATE TABLE `'.$tablename.'`';
		$tableSql = $this->_pdo->query($sql);
		if( !$tableSql )
			WdfDbException::Raise("Table `$tablename` not found!","PDO error info: ",$this->_pdo->errorInfo());

        $tableSql = $tableSql->finishScalar(1);

		$res = new TableSchema($this->_ds, $tablename);
        $res->CreateCode = $tableSql;
		$sql = "show columns from `$tablename`";
		foreach($this->_pdo->query($sql)->finishAll() as $row)
		{

			$size = false;
			if( preg_match('/([a-zA-Z]+)\(*(\d*)\)*/',$row['Type'],$match) )
			{
				$row['Type'] = $match[1];
				$size = $match[2];
			}
			if( $row['Key'] == 'PRI' )
				$row['Key'] = 'PRIMARY';

			$col = new ColumnSchema($row['Field']);
			$col->Type = $row['Type'];
			$col->Size = $size;
			$col->Null = $row['Null'];
			$col->Key = $row['Key'];
			$col->Default = $row['Default'];
			$col->Extra = $row['Extra'];
			$res->Columns[] = $col;

            if( $col->Type == 'longtext' )
            {
				try
				{
					$db = $this->_ds->Database();
					$sql = "SELECT 1 FROM information_schema.CHECK_CONSTRAINTS cc WHERE cc.CONSTRAINT_SCHEMA='$db' AND cc.TABLE_NAME='$tablename' AND cc.CHECK_CLAUSE LIKE 'json_valid(%'";
					$q = $this->_pdo->query($sql);
					if( $q && $q->finishScalar() )
						$col->Type = 'json';
				}
				catch (Exception $e)
				{
				}
            }
		}

		return $res;
	}

	/**
	 * @implements <IDatabaseDriver::getTableSchema>
	 */
    function &getTableSchema($tablename)
	{
        if( !$tablename )
        {
            $tablename = '<undefined>';
            $res = new TableSchema($this->_ds, $tablename);
            $res->CreateCode = "/* cannot create table without name */";
            return $res;
        }
		$res = new TableSchema($this->_ds, $tablename);
        if( PHP_OS_FAMILY == "Linux" && file_exists("/run/shm") )
        {
            $um = umask(0);
            $dir = '/run/shm/wdf-'.md5(__SCAVIXWDF__.(defined("DATABASE_VERSION")?'-'.DATABASE_VERSION:''));
            @mkdir($dir,0777,true);

            $schemafile = $dir."/".md5($this->_ds->GetDsn()."/$tablename").".schema";
            $fmt = @filemtime($schemafile);
            if( $fmt && ((time()-$fmt) < 300) )
            {
                $schema = @unserialize(@file_get_contents($schemafile))?:[];
                $res->CreateCode = isset($schema['create'])?$schema['create']:'';
                $res->Columns = isset($schema['columns'])?$schema['columns']:[];
                $res->Keys = isset($schema['keys'])?$schema['keys']:[];
            }
        }

        if( !$res->CreateCode )
        {
            $sql = 'SHOW CREATE TABLE `'.$tablename.'`';
            $tableSql = $this->_pdo->query($sql);
            if( !$tableSql )
                WdfDbException::Raise("Table `$tablename` not found!","PDO error info: ",$this->_pdo->errorInfo());

            $res->CreateCode = $tableSql->finishScalar(1);
			$save_needed = true;
        }

        if( !count($res->Columns) )
        {
            $sql = "show full columns from `$tablename`";
            foreach($this->_pdo->query($sql)->finishAll() as $row)
            {
                $size = false;
                if( preg_match('/([a-zA-Z]+)\(*(\d*)\)*/',$row['Type'],$match) )
                {
                    $row['Type'] = $match[1];
                    $size = $match[2];
                }
                if( $row['Key'] == 'PRI' )
                    $row['Key'] = 'PRIMARY';
                elseif( $row['Key'] == 'UNI' )
                    $row['Key'] = 'UNIQUE';

                $col = new ColumnSchema($row['Field']);
                $col->Type = $row['Type'];
                $col->Size = $size;
                $col->Null = $row['Null'];
                $col->Key = $row['Key'];
                $col->Default = $row['Default'];
                $col->Extra = $row['Extra'];
                $col->Comment = $row['Comment'];
                $res->Columns[] = $col;

                if( $col->Type == 'longtext' )
                {
					try
					{
						$db = $this->_ds->Database();
						$sql = "SELECT 1 FROM information_schema.CHECK_CONSTRAINTS cc WHERE cc.CONSTRAINT_SCHEMA='$db' AND cc.TABLE_NAME='$tablename' AND cc.CHECK_CLAUSE LIKE 'json_valid(%'";
						$q = $this->_pdo->query($sql);
						if ($q && $q->finishScalar())
							$col->Type = 'json';
					}
					catch (Exception $e)
					{
					}
                }
            }
			$save_needed = true;
        }

		if( !count($res->Keys) )
        {
            $sql = "show keys from `$tablename`";
            foreach ($this->_pdo->query($sql)->finishAll() as $row)
            {
                $keyName = $row['Key_name'];
                if (!isset($res->Keys[$keyName]))
                    $res->Keys[$keyName] = ['unique' => $row['Non_unique'] == '0', 'columns' => []];
                $res->Keys[$keyName]['columns'][] = $row['Column_name'];
            }
            $save_needed = true;
        }

        if( isset($um) )
        {
			if( isset($save_needed) && $save_needed )
			{
				$schema = ['create'=>$res->CreateCode,'columns'=>$res->Columns,'keys'=>$res->Keys];
				@file_put_contents($schemafile,serialize($schema));
			}
            umask($um);
        }
		return $res;
	}

	/**
	 * @implements <IDatabaseDriver::listColumns>
	 */
	function listColumns($tablename)
	{
		$sql = 'SHOW COLUMNS FROM `'.$tablename.'`';
		$cols = [];
		foreach($this->_pdo->query($sql)->finishAll() as $row)
			$cols[] = $row[0];
		return $cols;
	}

	/**
	 * @implements <IDatabaseDriver::tableExists>
	 */
	function tableExists($tablename)
	{
        if(isset($this->_tableexistbuffer[$tablename]))
            return $this->_tableexistbuffer[$tablename];

        $sql = 'SHOW TABLES LIKE ?';
		$stmt = $this->_pdo->prepare($sql);
		$stmt->setFetchMode(PDO::FETCH_NUM);
		$stmt->bindValue(1,$tablename);
		if( !$stmt->execute() )
			WdfDbException::RaiseStatement($stmt);
		$row = $stmt->fetch();
        $stmt->closeCursor();
		$ret = is_array($row) && count($row)>0;
        $this->_tableexistbuffer[$tablename] = $ret;
        return $ret;

//        $sql = 'SHOW TABLES LIKE '.$this->_pdo->quote($tablename).';';
//        return $this->_pdo->exec($sql) > 0;
	}

	/**
	 * @implements <IDatabaseDriver::createTable>
	 */
	function createTable($objSchema)
	{ ToDoException::Raise("implement MySql->createTable()"); }

	/**
	 * @implements <IDatabaseDriver::getSaveStatement>
	 */
	function getSaveStatement($model,&$args,$columns_to_update=false)
	{
		$cols = [];
		$pks = $model->GetPrimaryColumns();
		$all = [];
		$vals = [];
		$pkcols = [];
		$pks2 = [];

		foreach( $pks as $col )
		{
			if( isset($model->$col) )
			{
				$pkcols[] = "`$col`=:$col";
				$all[] = "`$col`";
				$vals[] = ":$col";
				$args[":$col"] = $model->$col;
			}
			$pks2[$col] = $col;
		}
		$columns_to_update = $columns_to_update?$columns_to_update:$model->GetColumnNames(true);
		foreach( $columns_to_update as $col )
		{
			if( isset($pks2[$col]) || !$model->HasColumn($col) || !$model->HasValue($col) )
				continue;

            /* DEPRECATED! We do not set dynamic properties anymore but handle them via __get/__set.
                           This dynamic-property based handling produces errors when values are set to NULL because 'get_object_vars' will not return them anymore.
                           On the other hand, it is simply not needed because of the __get/__set handling.

			// isset returns false too if $this->$col is set to NULL, so we need some more logic here
			if( !isset($model->$col) )
			{
				if( !isset($ovars) )
				{
					$ovars = get_object_vars($model);
					$ovars = array_combine(array_keys($ovars),array_fill(0,count($ovars),true));
				}
				if( !isset($ovars[$col]) )
					continue;
			}
            */

			$tv = $model->TypedValue($col);
			if( is_string($tv) && (starts_iwith($tv,"now()") || starts_iwith($tv,"current_timestamp()")) )
			{
				$cols[] = "`$col`=$tv";
				$all[] = "`$col`";
				$vals[] = "$tv";
			}
			else
			{
                if( is_null($tv) && ($cd = $model->GetTableSchema()->GetColumn($col)) )
                {
                    if( $cd->IsNullAllowed() )
                    {
                        if( $cd->HasDefault() && !$model->_saved )
                        {
                            //log_debug("New Dataset, so ignoring NULL value for column '$col' (" . system_get_caller() . ")");
                            continue;
                        }
                    }
                    else
                    {
                        if( !$cd->HasDefault() )
                            log_warn("NULL value is not allowed for column '$col' (" . system_get_caller() . ")");
                        continue;
                    }
                }
                $argn = ":a".sprintf('%03d', count($cols));
				$cols[] = "`$col`=$argn";
				$all[] = "`$col`";
				$vals[] = "$argn";
				$args["$argn"] = $tv;

				/**
				 * Changed DateTime/Timestamp-Format from ISO8601 to 'Y-m-d H:i:s' because  MySQL 5.7 throws Error on inserting Timestamps in ISO8601 Format
				 */
				if( $args["$argn"] instanceof DateTime )
					$args["$argn"] = $args["$argn"]->format('Y-m-d H:i:s');
				elseif( is_object($args["$argn"]) || is_array($args["$argn"]) )
					$args["$argn"] = @json_encode($args["$argn"]);
			}
		}

		if( $model->_saved )
		{
			if( count($cols) == 0 )
				return false;

			$sql  = "UPDATE `".$model->GetTableName()."`";
			$sql .= " SET ".implode(",",$cols);
			$sql .= " WHERE ".implode(" AND ",$pkcols);
			$sql .= " LIMIT 1";
		}
		else
		{
			if( count($all) == 0 )
				$sql = (\ScavixWDF\Model\Model::$SaveDelayed?"INSERT DELAYED INTO `":"INSERT INTO `").$model->GetTableName()."`";
			else
				$sql = (\ScavixWDF\Model\Model::$SaveDelayed?"INSERT DELAYED INTO `":"INSERT INTO `").$model->GetTableName()."`(".implode(",",$all).")VALUES(".implode(',',$vals).")";
		}
		return new ResultSet($this->_ds, $this->_pdo->prepare($sql));
	}

	/**
	 * @implements <IDatabaseDriver::getDeleteStatement>
	 */
	function getDeleteStatement($model,&$args)
	{
		$pks = $model->GetPrimaryColumns();
		$cols = [];
		foreach( $pks as $col )
		{
			if( isset($model->$col) )
			{
				$cols[] = "`$col`=:$col";
				$args[":$col"] = $model->$col;
			}
		}
		if( count($cols) == 0 )
			return false;

		$sql = "DELETE FROM `".$model->GetTableName()."` WHERE ".implode(" AND ",$cols)." LIMIT 1";
		return new ResultSet($this->_ds, $this->_pdo->prepare($sql));
	}

	/**
	 * @implements <IDatabaseDriver::getPagedStatement>
	 */
	function getPagedStatement($sql,$page,$items_per_page)
	{
		$offset = ($page-1)*$items_per_page;
        if(intval($offset) < 0)
            $offset = 0;
		$sql = preg_replace('/LIMIT\s+[\d\s,]+/', '', $sql);
		$sql .= " LIMIT $offset,$items_per_page";
		return new ResultSet($this->_ds, $this->_pdo->prepare($sql));
	}

	/**
	 * @implements <IDatabaseDriver::getPagingInfo>
	 */
	function getPagingInfo($sql,$input_arguments=null)
	{
		if( !preg_match('/LIMIT\s+([\d\s,]+)/i', $sql, $amounts) )
			return false;

		$amounts = explode(",",$amounts[1]);
		if( count($amounts) > 1 )
			list($offset,$length) = $amounts;
		else
			list($offset,$length) = array(0,$amounts[0]);
		$offset = intval($offset);
		$length = intval($length);

        $key = 'DB_Cache_FoundRows_'.md5($sql.serialize($input_arguments));
        $found_rows = cache_get($key,false,false,true);
        if( $found_rows === false )
        {
            $sql = preg_replace("/(\/\*BEG-ORDER\*\/)(.*)(\/\*END-ORDER\*\/)/",'', $sql);

            $sql_limit = preg_replace("/\/\*BEG-LIMIT\*\/.+\/\*END-LIMIT\*\//",'', $sql);
            $sql = ($sql_limit == $sql)
                ?preg_replace('/LIMIT[\s0-9,]+$/i','',$sql)
                :$sql_limit;

            $sql_columns = preg_replace("/\/\*BEG-COLUMNS\*\/.+\/\*END-COLUMNS\*\//", (stripos($sql, ' having ') ? '*' : '1'), $sql);          // Might not work with virual columns
            $sql = ($sql_columns == $sql)
                ?((stripos($sql, 'select * from') === 0)?"SELECT 1 FROM".substr($sql,13):$sql)
                :$sql_columns;

            $ok = $this->_ds->ExecuteScalar("SELECT count(1) FROM ($sql) AS x",
                is_null($input_arguments)?[]:array_clean_assoc_or_sequence($input_arguments)
            );
            $total = intval($ok);
            if( $ok === false )
                $this->_ds->LogLastStatement("Error querying paging info");
            else
                cache_set($key,$total,60,false,true);
        }
        else
            $total = intval($found_rows);

		return array
		(
			'rows_per_page'=> $length,
			'current_page' => $length==0?0:floor($offset / $length) + 1,
			'total_pages'  => $length==0?0:ceil($total / $length),
			'total_rows'   => $total,
			'offset'       => $offset,
		);
	}

	/**
	 * @implements <IDatabaseDriver::Now>
	 */
	function Now($seconds_to_add=0)
	{
        if($seconds_to_add == 0)
            return 'NOW()';
		return "(NOW() + INTERVAL $seconds_to_add SECOND)";
	}

	/**
	 * @implements <IDatabaseDriver::PreprocessSql>
	 */
    function PreprocessSql($sql)
    {
        return str_ireplace("INSERT OR IGNORE", "INSERT IGNORE", $sql);
    }
}
