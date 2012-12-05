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
 */
class ResultSet extends PDOStatement
{
	private $_ds = null;
	private $_sql_used = null;
	private $_arguments_used = null;
	private $_paging_info = null;
	private $_field_types = null;
	private $_index = -1;
	private $_rowbuffer = array();
	private $_loaded_from_cache = false;
	private $_data_fetched = false;
	
	/*--- Compatibility to old model ---*/
	private $_current = false;
	
	protected function __construct($datasource)
	{
		$this->_ds = $datasource;
	}
	
	public function ErrorOutput()
	{
		return $this->_sql_used."\n".my_var_export($this->errorInfo());
	}
	
	public function LogDebug()
	{
		$tmp = $this->_sql_used;
		if( is_array($this->_arguments_used) )
			foreach( $this->_arguments_used as $a )
				$tmp = preg_replace('/\?/', "'".$this->_ds->EscapeArgument($a)."'", $tmp, 1);
		log_debug("SQL: ".$this->_sql_used."\nARGS: ",$this->_arguments_used,"\nMerged: ",$tmp);
	}
	
	public function GetSql()
	{
		return $this->_sql_used;
	}

	public function GetArgs()
	{
		return $this->_arguments_used;
	}

	public function __get($name)
	{
		/*--- Compatibility to old model ---*/
		switch( $name )
		{
			case "EOF": 
				if( !$this->_current ) $this->_current = $this->fetch();
				return $this->_current === false;
			case "fields": 
				if( !$this->_current ) $this->_current = $this->fetch();
				return $this->_current;
		}
	}
	
	function serialize()
	{
		$buf = array(
			'ds' => $this->_ds->_storage_id,
			'sql' => $this->queryString,
			'args' => $this->_arguments_used,
			'paging_info' => $this->_paging_info,
			'field_types' => $this->_field_types,
			'index' => $this->_index,
			'rows' => $this->_rowbuffer,
			'df' => $this->_data_fetched,
		);		
		return serialize($buf);
	}
	
	static function &unserialize($data)
	{
		$buf = unserialize($data);
		$res = new ResultSet(model_datasource($buf['ds']));
		$res->_sql_used = $buf['sql'];
		$res->_arguments_used = $buf['args'];
		$res->_paging_info = $buf['paging_info'];
		$res->_field_types = $buf['field_types'];
		$res->_index = $buf['index'];
		$res->_rowbuffer = $buf['rows'];
		$res->_loaded_from_cache = true;
		$res->_data_fetched = isset($buf['df'])?$buf['df']:false;
		if( isset($res->_rowbuffer[$res->_index]) )
			$res->_current = $res->_rowbuffer[$res->_index];
		return $res;
	}
	
	function bindValue($parameter, $value, $data_type = null)
	{
		if( !$this->_arguments_used )
			$this->_arguments_used = array();
		$this->_arguments_used[$parameter] = $value;
		
		if( is_null($data_type) )
			return parent::bindValue($parameter, $value);
		else
			return parent::bindValue($parameter, $value, $data_type);
	}
	
	function execute($input_parameters = null)
	{
		if( !is_null($input_parameters) && !is_array($input_parameters) )
			$input_parameters = array($input_parameters);
		
		$this->_sql_used = $this->queryString;
		if( !is_null($input_parameters) )
		{
			if( is_null($this->_arguments_used) )
				$this->_arguments_used = $input_parameters;
			else
				$this->_arguments_used = array_merge($this->_arguments_used,$input_parameters);
		}
		
		if( $this->_ds )
			$this->_ds->LastStatement = $this;
		
		if( is_null($input_parameters) )
			return parent::execute();
		else
			return parent::execute($input_parameters);
	}
	
	function fetch($fetch_style = null, $cursor_orientation = null, $cursor_offset = null)
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
		
		$this->_current = parent::fetch($fetch_style, $cursor_orientation, $cursor_offset);
		if( $this->_current !== false )
		{
			$this->_index = count($this->_rowbuffer);
			$this->_rowbuffer[] = $this->_current;
		}
		return $this->_current;
	}
	
	function fetchAll($fetch_style = null, $column_index = null, $ctor_args = null)
	{
		$this->_data_fetched = true;
		
		if( $this->_loaded_from_cache )
			return $this->_rowbuffer;

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
					$this->_rowbuffer = parent::fetchAll();
				else
					$this->_rowbuffer = parent::fetchAll($fetch_style);
			else
				$this->_rowbuffer = parent::fetchAll($fetch_style,$column_index);
		else
			$this->_rowbuffer = parent::fetchAll($fetch_style, $column_index, $ctor_args);
		
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
			//foreach( $this->_rowbuffer as &$r)
			{
				$this->_rowbuffer[$i]->__initialize($this->_ds);
				$this->_rowbuffer[$i]->__init_db_values();
			}
			
			if( isset($mem_def_db) )
				Model::$DefaultDatasource = $mem_def_db;
		}
		return $this->_rowbuffer;
	}
	
	function fetchScalar($column=0)
	{
		$row = $this->fetch();
		if( !$row )
			return false;
		if( !isset($row[$column]) )
			return false;
		return $row[$column];
	}
	
	function GetPagingInfo()
	{
		if( !$this->_paging_info )
			$this->_paging_info = $this->_ds->Driver->getPagingInfo($this->queryString,$this->_arguments_used);
		return $this->_paging_info;
	}
	
	/*--- Compatibility to old model ---*/
	
	function Close()
	{
		$this->_index = -1;
		$this->_current = false;
		return $this->closeCursor();
	}
	
	function MoveFirst()
	{
		if( $this->_index < 0 )
			return $this->fetch();
		if( $this->_index < 1 )
			return $this->_current;
		$this->_index = 0;
		$this->_current = $this->_rowbuffer[0];
		return $this->_current;
	}
	
	function MoveNext()
	{
//		if( $this->_loaded_from_cache )
//		{
//			if( $this->_index < (count($this->_rowbuffer)-1) )
//			{
//				$this->_index++;
//				$this->_current = $this->_rowbuffer[$this->_index];
//				return $this->_current !== false;
//			}
//		}
		$this->fetch();
		return $this->_current !== false;
	}
	
	function GetArray($nRows=-1)
	{
		$res = array();
		while( $nRows != count($res) && !$this->EOF )
		{
			$res[] = $this->fields;
			$this->MoveNext();
		}
		return $res;
	}
	
	function GetRowAssoc($upper=1)
	{
		$res = array();
		foreach( $this->fields as $k=>$v )
			if( !is_integer($k) )
				$res[$k] = $v;
		
		switch($upper)
		{
			case 0: return array_change_key_case($res,CASE_LOWER);
			case 1: return array_change_key_case($res,CASE_UPPER);
		}
		return $res;
	}
	
	function GetRows($nRows = -1) 
	{
		$arr = $this->GetArray($nRows);
		return $arr;
	}
	
	function FieldTypesArray()
	{
		// as this is only used in datatable.class.php and only type=='datetime' is checked 
		// we simply prepare for compatibility of that call
		if( !$this->_field_types )
		{
			$this->_field_types = array();
			for($i=0; $i<$this->columnCount(); $i++)
			{
				$r = (object)$this->getColumnMeta($i);
				$r->type = strtolower($r->native_type);
				$this->_field_types[] = $r;
			}
		}
		return $this->_field_types;
	}
	
	function MaxRecordCount()
	{
		if( !$this->GetPagingInfo() )
			return false;
		return $this->_paging_info['total_rows'];
	}
	
	function AbsolutePage($page=-1)
	{
		// this should in fact be getter and setter but we ignore it as it is just used as getter
		if( !$this->GetPagingInfo() )
			return false;
		return $this->_paging_info['current_page'];
	}
	
	function LastPageNo($page = false)
	{
		// this should in fact be getter and setter but we ignore it as it is just used as getter
		if( !$this->GetPagingInfo() )
			return false;
		return $this->_paging_info['total_pages'];
	}
	
	/*--- Some shortcut methods ---*/
	
	function Enumerate($column_name, $distinct=true)
	{
		if( !$this->_data_fetched )
			$this->fetchAll();
		$res = array();
		foreach( $this->_rowbuffer as $row )
			if( !$distinct || !in_array($row[$column_name], $res) )
				$res[] = $row[$column_name];
		return $res;
	}
}

?>
