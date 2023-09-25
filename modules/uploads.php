<?php
use ScavixWDF\WdfException;
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2023 Scavix Software GmbH & Co. KG
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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2023 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

/**
 * Initializes the uploads module
 * 
 * This module provides functions for handling uploads on disk and in DB.
 * @return void
 */
function uploads_init()
{
    classpath_add(__DIR__ . '/uploads');
    if (!defined('__FILES__'))
        WdfException::Raise("uploads module: please define a root folder: define('__FILES__','/some/where')");
    if( !file_exists(__FILES__) )
        WdfException::Raise("uploads module: please create the root folder: ". __FILES__);
    if( !is_writable(__FILES__) )
        WdfException::Raise("uploads module: please make the root folder writable: ". __FILES__);
}
