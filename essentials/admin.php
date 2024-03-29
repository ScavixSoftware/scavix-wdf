<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

use ScavixWDF\WdfException;

/**
 * Initializes the admin essential.
 * 
 * @return void
 */
function admin_init()
{
	global $CONFIG;
	
	if( $CONFIG['system']['admin']['enabled'] )
	{
		$CONFIG['class_path']['system'][]   = __DIR__.'/admin/';
        
        if( (!isset($CONFIG['system']['admin']['credentials']) || count($CONFIG['system']['admin']['credentials']) == 0 )
		    &&
            (!$CONFIG['system']['admin']['username'] || !$CONFIG['system']['admin']['password'])
          )
			WdfException::Raise("System admin needs username and password to be set!");
		
		$CONFIG['system']['admin']['actions'] = [];
	}
}

/**
 * Registers a handler for the <SysAdmin> controller.
 * 
 * @param string $label Label for the navigation entry
 * @param string $controller Controller class
 * @param string $method Method to be called
 * @return void
 */
function admin_register_handler($label,$controller,$method)
{
	global $CONFIG;
	$CONFIG['system']['admin']['actions'][$label] = array($controller,$method);
}
