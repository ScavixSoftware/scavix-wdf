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
use PDO;
use ScavixWDF\Model\ColumnSchema;
use ScavixWDF\Model\ResultSet;
use ScavixWDF\Model\TableSchema;
use ScavixWDF\WdfDbException;

/**
 * SqLite database driver.
 */
class SqLite implements IDatabaseDriver
{
	private $_ds;
	private $_pdo;

	private function columnDef($colAttr)
	{
		switch( strtolower($colAttr->Type) )
		{
			case 'string':
				if( isset($colAttr->Size) && $colAttr->Size>0 )
					return $colAttr->Name.' VARCHAR('.$colAttr->Size.')';
				return $colAttr->Name.' TEXT';
			case 'integer':
			case 'int':
				if( isset($colAttr->Size) && $colAttr->Size>0 )
					return $colAttr->Name.' INTEGER('.$colAttr->Size.')';
				return $colAttr->Name.' INTEGER';
			case 'boolean':
			case 'bool':
				return $colAttr->Name.' INTEGER(1)';
		}
		WdfDbException::Raise("Unknown columne type {$colAttr->Type}");
	}

	/**
	 * @implements <IDatabaseDriver::initDriver>
	 */
	function initDriver($datasource,$pdo)
	{
		$this->_ds = $datasource;
		$this->_pdo = $pdo;
        $this->_pdo->Driver = $this;
	}

	/**
	 * @implements <IDatabaseDriver::listTables>
	 */
	function listTables()
	{
		$sql = 'SELECT tbl_name FROM sqlite_master WHERE type="table" ORDER BY tbl_name';
		$tables = [];
		foreach($this->_pdo->query($sql) as $row)
			$tables[] = $row['tbl_name'];
		return $tables;
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

		if( strtolower($tablename) == 'sqlite_master' )
		{
			$res = new TableSchema($this->_ds, $tablename);
			$col = new ColumnSchema('type');
			$col->Type = 'text';
			$col->Null = true;
			$res->Columns[] = $col;
			$col = new ColumnSchema('name');
			$col->Type = 'text';
			$col->Null = true;
			$res->Columns[] = $col;
			$col = new ColumnSchema('tbl_name');
			$col->Type = 'text';
			$col->Null = true;
			$res->Columns[] = $col;
			$col = new ColumnSchema('rootpage');
			$col->Type = 'integer';
			$col->Null = true;
			$res->Columns[] = $col;
			$col = new ColumnSchema('sql');
			$col->Type = 'text';
			$col->Null = true;
			$res->Columns[] = $col;
			return $res;
		}

		$tableSql = $this->_pdo->query(
			'SELECT sql FROM sqlite_master WHERE type="table" AND name = "'.$tablename.'"'
		)->fetch();
		$tableSql = isset($tableSql['sql'])?$tableSql['sql']:false;

		if( !$tableSql )
			WdfDbException::Raise("Table `$tablename` not found!","PDO error info: ",$this->_pdo->errorInfo());

		$res = new TableSchema($this->_ds, $tablename);
        $res->CreateCode = $tableSql;
		$sql = 'PRAGMA table_info("'.$tablename.'")';
		foreach($this->_pdo->query($sql) as $row)
		{
			$col = new ColumnSchema($row['name']);
			$col->Type = $row['type'];
			$col->Null = $row['notnull'] == 0;
			$col->Key = ($row['pk']>0)?"PRIMARY":null;
			$res->Columns[] = $col;
		}

        $sql = "SELECT il.name as index_name, ii.name as column_name, CASE il.origin when 'pk' then 1 else 0 END as is_primary_key, il.[unique] as is_unique
                FROM sqlite_master AS m, pragma_index_list(m.name) AS il, pragma_index_info(il.name) AS ii
                WHERE m.type = 'table' AND m.tbl_name = '{$tablename}'
                GROUP BY m.tbl_name,il.name,ii.name,il.origin,il.partial,il.seq
                ORDER BY index_name, ii.seqno";
        foreach($this->_pdo->query($sql) as $row)
        {
            $keyName = $row['is_primary_key'] ? 'PRIMARY' : $row['index_name'];
            if (!isset($res->Keys[$keyName]))
                $res->Keys[$keyName] = ['unique' => $row['is_unique']>0, 'columns' => []];
            $res->Keys[$keyName]['columns'][] = $row['column_name'];
        }

		return $res;
	}

	/**
	 * @implements <IDatabaseDriver::listColumns>
	 */
	function listColumns($tablename)
	{
		$sql = 'PRAGMA table_info("'.$tablename.'")';
		$cols = [];
		foreach($this->_pdo->query($sql) as $row)
			$cols[] = $row['name'];
		return $cols;
	}

	/**
	 * @implements <IDatabaseDriver::tableExists>
	 */
	function tableExists($tablename)
	{
		$sql = 'SELECT tbl_name FROM sqlite_master WHERE type="table" AND tbl_name=?';
		$stmt = $this->_pdo->prepare($sql);//,array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL));
		$stmt->setFetchMode(PDO::FETCH_NUM);
		$stmt->bindValue(1,$tablename);
		if( !$stmt->execute() )
			WdfDbException::RaiseStatement($stmt);
		$row = $stmt->fetch();
		return is_array($row) && count($row)>0;
	}

	/**
	 * @implements <IDatabaseDriver::createTable>
	 */
	function createTable($objSchema)
	{
		$sql = [];

		foreach( $objSchema->Columns as $col )
			$sql[] = $this->columnDef($col);

		$sql = 'CREATE TABLE "'.$objSchema->Name.'" ('."\n".implode(",\n",$sql)."\n".')';
		$stmt = $this->_pdo->prepare($sql);//,array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL));
		if( !$stmt->execute() )
			WdfDbException::RaiseStatement($stmt);
	}

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

		foreach( $pks as $col )
		{
			if( isset($model->$col) )
			{
				$pkcols[] = "`$col`=:$col";
				$all[] = "`$col`";
				$vals[] = ":$col";
				$args[":$col"] = $model->$col;
			}
		}

		$columns_to_update = $columns_to_update?$columns_to_update:$model->GetColumnNames(true);
		foreach( $columns_to_update as $col )
		{
			if( in_array($col,$pks) || !$model->HasColumn($col) || !$model->HasValue($col) )
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
			if( is_string($tv) && strtolower($tv)=="now()" )
			{
				$cols[] = "`$col`=datetime('now')";
				$all[] = "`$col`";
				$vals[] = "datetime('now')";
			}
            elseif( is_null($tv) && ($cd = $model->GetTableSchema()->GetColumn($col)) && !$cd->IsNullAllowed() )
            {
                if( !$cd->HasDefault() )
                    log_warn("NULL value is not allowed for column '$col' (" . system_get_caller() . ")");
            }
			else
			{
				$cols[] = "`$col`=:$col";
				$all[] = "`$col`";
				$vals[] = ":$col";
				$args[":$col"] = $tv;

				if( $args[":$col"] instanceof DateTime )
					$args[":$col"] = $args[":$col"]->format("c");
			}
		}

		if( $model->_saved )
		{
			if( count($cols) == 0 )
				return false;

			$sql  = "UPDATE `".$model->GetTableName()."`";
			$sql .= " SET ".implode(",",$cols);
			$sql .= " WHERE ".implode(" AND ",$pkcols);
		}
		else
		{
			if( count($all) == 0 )
				$sql = "INSERT INTO `".$model->GetTableName()."`";
			else
				$sql  = "INSERT INTO `".$model->GetTableName()."`(".implode(",",$all).")VALUES(".implode(',',$vals).")";
		}
        $stmt = $this->_pdo->prepare($sql);
        if (!$stmt)
            WdfDbException::Raise("SQL Error",$sql);
		return new ResultSet($this->_ds, $stmt);
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

		$sql = "DELETE FROM `".$model->GetTableName()."` WHERE ".implode(" AND ",$cols);
		return new ResultSet($this->_ds, $this->_pdo->prepare($sql));
	}

	/**
	 * @implements <IDatabaseDriver::getPagedStatement>
	 */
	function getPagedStatement($sql,$page,$items_per_page)
	{
		$offset = ($page-1)*$items_per_page;
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

		$sql = preg_replace('/LIMIT\s+[\d\s,]+/i', '', $sql);
		$sql = "SELECT count(*) FROM ($sql) AS x";
		$stmt = $this->_pdo->prepare($sql);
		$stmt->execute($input_arguments);
		$total = intval($stmt->fetchColumn());

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
		$seconds_to_add = ($seconds_to_add>=0)?"+$seconds_to_add":"-$seconds_to_add";
		return "(datetime('now','$seconds_to_add seconds','localtime'))";
	}

	/**
	 * @implements <IDatabaseDriver::PreprocessSql>
	 */
    function PreprocessSql($sql)
    {
        $sql = preg_replace('/isnull\(([^\)]+)\)/i',"($1 IS NULL)", $sql);
        return str_ireplace("now()", "datetime('now')", $sql);
    }
}
