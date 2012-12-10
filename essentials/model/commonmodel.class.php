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
 
class CommonModel extends Model
{
	protected $_tableName = false;
	
	function GetTableName()
	{
		return $this->_tableName;
	}
	
	function __construct($datasource=null, $tablename=null)
    {
		if( $tablename )
		{
			$this->_tableName = $tablename;
			if( $datasource )
				$this->_cacheKey = $datasource->Database().$this->_tableName;
		}
		parent::__construct($datasource);
	}
	
	protected function __ensureResults($ctor_args=null)
	{
		if( !$ctor_args )
			$ctor_args = array($this->_ds,$this->_tableName);
		return parent::__ensureResults($ctor_args);
	}
}