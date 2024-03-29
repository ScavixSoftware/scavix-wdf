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

/**
 * Schema of a database table.
 */
class TableSchema
{
	static $_typeMap = [];
	static $_colMap = [];
	static $_hasColMap = [];
	static $_colMapPri = [];

	private $_ds;
	public $_cacheKey;

    public $CreateCode;
	public $Name;
	public $Columns;
    public $Keys;

    function __construct($datasource,$tableName)
    {
		$this->_ds = $datasource;
        $this->Name = $tableName;
		$this->Columns = [];
        $this->Keys = [];
		$this->_cacheKey = $this->_ds->Database().$this->Name;
    }

	/**
	 * Gets the type of a column.
	 *
	 * @param string $column_name Column name to get type for
	 * @return string Type identifier
	 */
	public function TypeOf($column_name)
	{
		if( !isset(self::$_typeMap[$this->_cacheKey]) )
			self::$_typeMap[$this->_cacheKey] = [];
		if( !isset(self::$_typeMap[$this->_cacheKey][$column_name]) )
		{
			foreach( $this->Columns as $c )
				if( $c->Name == $column_name )
				{
					self::$_typeMap[$this->_cacheKey][$column_name] = $c->Type;
					break;
				}
		}
		return isset(self::$_typeMap[$this->_cacheKey][$column_name])?self::$_typeMap[$this->_cacheKey][$column_name]:false;
	}

	/**
	 * Returns a list of column names.
	 *
	 * @return array All column names
	 */
	function ColumnNames()
	{
		if( !isset(self::$_colMap[$this->_cacheKey]) )
		{
			self::$_colMap[$this->_cacheKey] = [];
			self::$_hasColMap[$this->_cacheKey] = [];
			foreach( $this->Columns as $c )
			{
				self::$_colMap[$this->_cacheKey][] = $c->Name;
				self::$_hasColMap[$this->_cacheKey][$c->Name] = true;
			}
		}
		return self::$_colMap[$this->_cacheKey];
	}

	/**
	 * Returns all columns that belong to the primary key.
	 *
	 * @return array All PK columns
	 */
	function PrimaryColumnNames()
	{
		if( !isset(self::$_colMapPri[$this->_cacheKey]) )
		{
			self::$_colMapPri[$this->_cacheKey] = [];
			foreach( $this->Columns as $c )
				if( $c->IsPrimary() )
					self::$_colMapPri[$this->_cacheKey][] = $c->Name;
		}
		return self::$_colMapPri[$this->_cacheKey];
	}

	/**
	 * Checks if the given column exists.
	 *
	 * @param string $column_name Column to check
	 * @return bool true or false
	 */
	function HasColumn($column_name)
	{
		if( !isset(self::$_hasColMap[$this->_cacheKey]) )
			$this->ColumnNames();
		return isset(self::$_hasColMap[$this->_cacheKey][$column_name]);
	}

    /**
     * Returns the <ColumnSchema> object for the given column name.
     *
     * @param string $column_name Column name
     * @return ColumnSchema|false The column or false if not found
     */
    function GetColumn($column_name)
	{
        foreach ($this->Columns as $c)
            if ($c->Name == $column_name)
                return $c;
        return false;
	}
}
