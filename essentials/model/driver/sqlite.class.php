<?
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
		throw new DatabaseException("Unknown columne type {$colAttr->Type}");
	}

	function initDriver($datasource,$pdo)
	{
		$this->_ds = $datasource;
		$this->_pdo = $pdo;
	}

	function listTables()
	{
		$sql = 'SELECT tbl_name FROM sqlite_master WHERE type="table" ORDER BY tbl_name';
		$tables = array();
		foreach($this->_pdo->query() as $row)
			$tables[] = $row['tbl_name'];
		return $tables;
	}

    function &getTableSchema($tablename)
	{
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
		$tableSql = $tableSql['sql'];

		if( !$tableSql )
		{
			log_fatal("PDO error info: ",$this->_pdo->errorInfo());
			throw new Exception("Table `$tablename` not found!");
		}

		$res = new TableSchema($this->_ds, $tablename);
		$sql = 'PRAGMA table_info("'.$tablename.'")';
		foreach($this->_pdo->query($sql) as $row)
		{
//			$col = new ColumnAttribute($row['name'],$row['type']);
//			if( preg_match('/"'.$row['name'].'" '.$row['type'].' ([^,]*)/i', $tableSql, $match) )
//				$col->Contraints = $match[1];
//			$res->Columns[] = $col;
			
			
			$col = new ColumnSchema($row['name']);
			$col->Type = $row['type'];
//			$col->Size = $size;
			$col->Null = $row['notnull'] == 0;
			$col->Key = ($row['pk']==1)?"PRIMARY":null;
//			$col->Default = $row['Default'];
//			$col->Extra = $row['Extra'];
			$res->Columns[] = $col;
		}
		return $res;
	}

	function listColumns($tablename)
	{
		$sql = 'PRAGMA table_info("'.$tablename.'")';
		$cols = array();
		foreach($this->_pdo->query($sql) as $row)
			$cols[] = $row['name'];
		return $cols;
	}

	function tableExists($tablename)
	{
		$sql = 'SELECT tbl_name FROM sqlite_master WHERE type="table" AND tbl_name=?';
		$stmt = $this->_pdo->prepare($sql);//,array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL));
		$stmt->setFetchMode(PDO::FETCH_NUM);
		$stmt->bindValue(1,$tablename);
		if( !$stmt->execute() )
			throw new DatabaseException($stmt->errorInfo());
		$row = $stmt->fetch();
		return is_array($row) && count($row)>0;
	}

	function createTable($objSchema)
	{
		$sql = array();

		foreach( $objSchema->Columns as $col )
			$sql[] = $this->columnDef($col);

		$sql = 'CREATE TABLE "'.$objSchema->Table.'" ('."\n".implode(",\n",$sql)."\n".')';
		log_debug($sql);

		$stmt = $this->_pdo->prepare($sql);//,array(PDO::ATTR_CURSOR=>PDO::CURSOR_SCROLL));
		if( !$stmt->execute() )
			throw new DatabaseException($stmt->errorInfo());
	}

	function getSaveStatement($model,&$args)
	{
		throw new Exception("TODO");
	}
	
	function getDeleteStatement($model,&$args){}
	function getPagedStatement($sql,$page,$items_per_page){}
	function getPagingInfo($sql,$input_arguments=null){}
	
	function Now($seconds_to_add)
	{
		$seconds_to_add = ($seconds_to_add>=0)?"+$seconds_to_add":"-$seconds_to_add";
		return "(datetime('now','$seconds_to_add seconds'))";
	}
}
?>
