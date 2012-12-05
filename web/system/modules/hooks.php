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
 
define("HOOK_POST_INIT",1);
define("HOOK_POST_INITSESSION",2);
define("HOOK_PRE_EXECUTE",3);
define("HOOK_POST_EXECUTE",4);
define("HOOK_PRE_FINISH",5);
define("HOOK_DB_INITIALIZED",6);

function hooks_init()
{
	$GLOBALS['system']['hooks'][HOOK_POST_INIT] = array();
	$GLOBALS['system']['hooks'][HOOK_POST_INITSESSION] = array();
	$GLOBALS['system']['hooks'][HOOK_PRE_EXECUTE] = array();
	$GLOBALS['system']['hooks'][HOOK_POST_EXECUTE] = array();
	$GLOBALS['system']['hooks'][HOOK_PRE_FINISH] = array();
	$GLOBALS['system']['hooks'][HOOK_DB_INITIALIZED] = array();
}

function register_hook($type,&$handler_obj,$handler_method)
{
	is_valid_hook_type($type);
	$GLOBALS['system']['hooks'][$type][] = array(
		$handler_obj, $handler_method
	);
}

function execute_hooks($type)
{
	is_valid_hook_type($type);

	log_debug($GLOBALS['system']['hooks'][$type],"execute_hooks($type)");
	$cbt = count($GLOBALS['system']['hooks'][$type]);
	for($i=$cnt-1; $i>=0; $i--)
	{
		$hook = $GLOBALS['system']['hooks'][$type][$i];
		$res = $hook[0]->$hook[1]();
		if( $res === false )
			break;
	}
}

function is_valid_hook_type($type)
{
	if( $type == HOOK_POST_INIT || $type == HOOK_POST_INITSESSION ||
	    $type == HOOK_PRE_EXECUTE || $type == HOOK_POST_EXECUTE ||
		$type == HOOK_PRE_FINISH || $type == HOOK_DB_INITIALIZED )
		return true;

	system_die("Invalid hook type ($type)!");
}

?>