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
 * Schema of a database column
 *
 * Will be created from the DB and used to automatically detect columns and their types.
 */
class ColumnSchema
{
	public $Name;
	public $Type;
	public $Size;
	public $Null;
	public $Key;
	public $Default;
	public $Extra;
    public $Comment;

    function __construct($name)
    {
        $this->Name = $name;
    }

	/**
	 * Checks if this column belongs to the primary key
	 *
	 * In fact just `return $this->Key == "PRIMARY";`
	 * @return bool true or false
	 */
	function IsPrimary()
	{
		return $this->Key == "PRIMARY";
	}

    /**
     * Checks if NULL is allowed for this column.
     *
     * @return bool
     */
    function IsNullAllowed()
    {
        return avail($this, 'Null') && is_in(strtolower($this->Null), '1', 'yes');
    }

    /**
     * Checks if this column has a default value.
     *
     * @return bool
     */
    function HasDefault()
    {
        return isset($this->Default);
    }
}
