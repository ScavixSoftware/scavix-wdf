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
 
class System_Model extends Model
{
	var $DataSource;
	var $_table;
	var $foreignName;
	
	function GetTableName()
	{
		global $CONFIG;
		return $CONFIG['model'][strtolower(get_class($this))];
	}
	
	function __initialize($datasource=null)
	{
		parent::__initialize($datasource);
		$this->DataSource = $this->_ds;
    }
	
	protected function __ensureTableSchema()
	{
		parent::__ensureTableSchema();
		if( !$this->DataSource )
			$this->DataSource = $this->_ds;
		$this->foreignName = $this->_table = $this->_tableSchema->Name;
		return $this->_tableSchema;
	}
	
	public function GetColumns()
	{
		return $this->GetColumnNames();
	}
	
	/**
	 * Not fully compatible to old layer, as we'll skip $plain_array=false support
	 */
	function SetValues($data_array, $plain_array = false)
	{
		if( !$plain_array )
		{
			log_error("plain_array support not implemented");
			return;
		}
		
		foreach( $data_array as $key=>$val )
		{
			if( $this->HasColumn($key) )
				$this->$key = $val;
		}
	}
}

?>