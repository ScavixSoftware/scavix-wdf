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
 
function authorization_init()
{
	global $CONFIG;
	
	$CONFIG['class_path']['model'][] = dirname(__FILE__).'/authorization/';

	if( !isset($CONFIG['authorization']['datasource']) )
		$CONFIG['authorization']['datasource'] = 'system';

	if( !isset($CONFIG['authorization']['group_type']) )
		$CONFIG['authorization']['group_type'] = 'Group';

	if( !isset($CONFIG['authorization']['user_type']) )
		$CONFIG['authorization']['user_type'] = 'User';

	if( !isset($CONFIG['authorization']['user_id_field']) )
		$CONFIG['authorization']['user_id_field'] = 'id';

	if( !isset($CONFIG['authorization']['user_name_field']) )
		$CONFIG['authorization']['user_name_field'] = 'user_name';

	if( !isset($CONFIG['authorization']['user_pass_field']) )
		$CONFIG['authorization']['user_pass_field'] = 'user_pass';

	if( !isset($CONFIG['authorization']['group_id_field']) )
		$CONFIG['authorization']['group_id_field'] = 'group_id';

	if( !isset($CONFIG['authorization']['group_label_field']) )
		$CONFIG['authorization']['group_label_field'] = 'label';

	if( !isset($CONFIG['authorization']['group_rights_field']) )
		$CONFIG['authorization']['group_rights_field'] = 'rights';

	if( !isset($CONFIG['authorization']['right_type']) )
		$CONFIG['authorization']['right_type'] = 'Right';

	if( !isset($CONFIG['authorization']['right_rightid_field']) )
		$CONFIG['authorization']['right_rightid_field'] = 'right_id';

	if( !isset($CONFIG['authorization']['right_parentid_field']) )
		$CONFIG['authorization']['right_parentid_field'] = 'parent_id';
		
	// Mantis #2606 keep logged in
	if( !isset($CONFIG['authorization']['cookie_name']) )
		$CONFIG['authorization']['cookie_name'] = 'pamfax_console';

	if( !isset($CONFIG['authorization']['cookie_ttl']) ) // lifetime in days!
		$CONFIG['authorization']['cookie_ttl'] = 180;		
}

function verifyUser($container="",$call_postverify=true,$pw_is_encrypted=false)
{
	global $CONFIG;
	$ds = model_datasource($CONFIG['authorization']['datasource']);
	
	if( $container == "" )
	{
		//log_debug("restoring user");
		$user = restore_object('user');
		if( !$user )
			return false;
		//log_debug($user);
		$name = $user->$CONFIG['authorization']['user_name_field'];
		$pass = $user->$CONFIG['authorization']['user_pass_field'];
	}
	else
	{
		$name = $container['username'];
		$pass = $pw_is_encrypted?$container['password']:md5($container['password']);
	}
//	log_debug("verifyUser: $name $pass");
	$user = $ds->CreateInstance($CONFIG['authorization']['user_type']);
	if( $user->Load("".$CONFIG['authorization']['user_name_field']."=?0 AND ".$CONFIG['authorization']['user_pass_field']."=?1",array($name,$pass)) )
	{
		if(isset($_COOKIE["language"]) && function_exists('setUserLanguage') )		// copy lang selection to session
			setUserLanguage($_COOKIE["language"]);

		$_SESSION['userid'] = $user->$CONFIG['authorization']['user_id_field'];
		$_SESSION['username'] = $user->$CONFIG['authorization']['user_name_field'];
		$_SESSION['password'] = $user->$CONFIG['authorization']['user_pass_field'];
//log_debug("user stored");
		store_object($user,"user");
		if( $call_postverify && method_exists($user,"PostVerify") )
			$user->PostVerify();
		//log_debug($_SESSION, "USER_VERIFIED");
		return true;
	}
	logoutUser();
	return false;
}

function verifyUserDirect()
{
	global $CONFIG;

	$username = isset($_SESSION['username'])?$_SESSION['username']:'';
	$userpass = isset($_SESSION['password'])?$_SESSION['password']:'';

	$table = isset($CONFIG['model']['user'])?$CONFIG['model']['user']:'users';

	$ds = model_datasource($CONFIG['authorization']['datasource']);
	$rs = $ds->ExecuteSql("SELECT * FROM `$table` WHERE ".$CONFIG['authorization']['user_name_field']."=?0 AND ".$CONFIG['authorization']['user_pass_field']."=?1",array($username,$userpass));
	$GLOBALS['authorization']['directly_logged_in'] = !$rs->EOF;
	if( !$GLOBALS['authorization']['directly_logged_in'] )
	{
		log_trace("verifyUserDirect() fails: ".$rs->sql,$_SESSION);
	}
}

function logoutUser()
{
    log_debug("logoutUser()");
    
	global $CONFIG;
	$name = $CONFIG['authorization']['cookie_name']."_user";
	$pass = $CONFIG['authorization']['cookie_name']."_pass";

	if( isset($_COOKIE["$name"]) && isset($_COOKIE["$pass"]) && $_COOKIE["$name"] != "" && $_COOKIE["$pass"] != "" )
		clear_auth_cookie();

	unset($_SESSION['username']);
	unset($_SESSION['password']);
	unset($_SESSION['userid']);
    unset($_SESSION["request_language"]);
	session_kill_all();
	delete_object('user');
}

function loggedInDirect()
{
	if( isset($GLOBALS['authorization']['directly_logged_in']) && $GLOBALS['authorization']['directly_logged_in'] )
		return true;
	return false;
}

function loggedIn()
{
	if( in_object_storage('user') )
		return true;
	return false;
}

function userGroups()
{
	if( !loggedIn() )
		return array();
	return restore_object('user')->GroupArray;
}

/**
 * Return all ChildRights for a Parent
 *
 * @param <string> $right_id
 * @param <string> $operator
 * @return <string>
 */
function getChildRights($right_id=false,$operator="||")
{
    if(isset($GLOBALS['rights_buffer_getChildRights'][$right_id."_".$operator]))
        return $GLOBALS['rights_buffer_getChildRights'][$right_id."_".$operator];

	global $CONFIG;
	$RightExpression = false;
	$ds = model_datasource($CONFIG['authorization']['datasource']);

	if( $right_id === false)
		return false;

	$rights = $ds->Select("Right","parent_id = ?0",array($right_id));

	if( count($rights) == 0 )
		return false;
	
	foreach($rights as $right)
	{
		if( $RightExpression == false )
			$RightExpression = $right->right_id;
		else
			$RightExpression .= $operator.$right->right_id;
	}
    $GLOBALS['rights_buffer_getChildRights'][$right_id."_".$operator] = $RightExpression;
	return $RightExpression;
}

/**
 * Returns the right_id of the parent right
 *
 * @param <string> $right_id
 * @return <string> parent
 */
function getParentRight($right_id=false)
{
    if(isset($GLOBALS['rights_buffer_getParentRight'][$right_id]))
        return $GLOBALS['rights_buffer_getParentRight'][$right_id];

	global $CONFIG;
	$ds = model_datasource($CONFIG['authorization']['datasource']);

	if( $right_id === false)
    {
        $GLOBALS['rights_buffer_getParentRight'][$right_id] = false;
		return false;
    }

	$parent = $ds->Select("Right","right_id = ?0",array($right_id));

	if( count($parent) != 1 )
    {
        $GLOBALS['rights_buffer_getParentRight'][$right_id] = false;
		return false;
    }

    $GLOBALS['rights_buffer_getParentRight'][$right_id] = $parent[0]->parent_id;
	return $parent[0]->parent_id;
}

/**
 *
 * @param <string> $right_id
 * @param <string> $operator
 * @return <string> rightexpression of all siblings
 */
function getSiblingRights($right_id=false,$operator="||")
{
    if(isset($GLOBALS['rights_buffer_getSiblingRights'][$right_id."_".$operator]))
        return $GLOBALS['rights_buffer_getSiblingRights'][$right_id."_".$operator];

	global $CONFIG;
	$ds = model_datasource($CONFIG['authorization']['datasource']);
	$RightExpression = false;

	if( $right_id === false)
    {
        $GLOBALS['rights_buffer_getSiblingRights'][$right_id."_".$operator] = false;
		return false;
    }

	$parent = $ds->Select("Right","right_id = ?0",array($right_id));

	if( count($parent) != 1 )
    {
        $GLOBALS['rights_buffer_getSiblingRights'][$right_id."_".$operator] = false;
		return false;
    }

	$rights = $ds->Select("Right","parent_id = ?0",array($parent[0]->parent_id));

	if( count($rights) == 0 )
    {
        $GLOBALS['rights_buffer_getSiblingRights'][$right_id."_".$operator] = false;
		return false;
    }

	foreach($rights as $right)
	{
		if( $RightExpression == false )
			$RightExpression = $right->right_id;
		else
			$RightExpression .= $operator.$right->right_id;
	}
    $GLOBALS['rights_buffer_getSiblingRights'][$right_id."_".$operator] = $RightExpression;
	return $RightExpression;
}

function hasRight($right)
{
    if(isset($GLOBALS['rights_buffer_hasRight'][$right]))
        return $GLOBALS['rights_buffer_hasRight'][$right];

	if( !loggedIn() )
    {
        log_trace("hasRight($right) -> no user logged in");
		return false;
    }
	$user = restore_object('user');
	$ret = $user->hasRight($right);
    $GLOBALS['rights_buffer_hasRight'][$right] = $ret;
    return $ret;
}

function hasLicense($licitemkey)
{
    if(isset($GLOBALS['license_buffer_hasLicense'][$licitemkey]))
        return $GLOBALS['license_buffer_hasLicense'][$licitemkey];

	if( !loggedIn() )
    {
        log_debug("hasLicense($licitemkey) -> no user logged in");
		return false;
    }
	$user = restore_object('user');
    $ret = $user->hasLicense($licitemkey);
    $GLOBALS['license_buffer_hasLicense'][$licitemkey] = $ret;
    return $ret;
}

function rightInLicense($right_id)
{
    if(isset($GLOBALS['license_buffer_rightInLicense'][$right_id]))
        return $GLOBALS['license_buffer_rightInLicense'][$right_id];

	if( !loggedIn() )
    {
        log_debug("rightInLicense($licitemkey) -> no user logged in");
		return false;
    }
	$user = restore_object('user');
    $ret = $user->rightInLicense($right_id);
    $GLOBALS['license_buffer_rightInLicense'][$right_id] = $ret;
    return $ret;
}


function generateDirectLink($href=false)
{
	global $CONFIG;
	$ds = model_datasource($CONFIG['authorization']['datasource']);

	$ticket = $ds->CreateInstance('Ticket');
	$ticket->value = generatePW(20);
	$ticket->url = $href;
	$ticket->Save();

	return buildQuery("Login","Ticket","t=".$ticket->value);
}

function set_auth_cookie(&$user)
{
	global $CONFIG;
	setcookie($CONFIG['authorization']['cookie_name']."_user",$user->$CONFIG['authorization']['user_name_field'],time()+(60*60*24*$CONFIG['authorization']['cookie_ttl']), "/");
	setcookie($CONFIG['authorization']['cookie_name']."_pass",$user->$CONFIG['authorization']['user_pass_field'],time()+(60*60*24*$CONFIG['authorization']['cookie_ttl']), "/");
}

function clear_auth_cookie()
{
	global $CONFIG;
	setcookie($CONFIG['authorization']['cookie_name']."_user",'',time()-(60*60*24*$CONFIG['authorization']['cookie_ttl']), "/");
	setcookie($CONFIG['authorization']['cookie_name']."_pass",'',time()-(60*60*24*$CONFIG['authorization']['cookie_ttl']), "/");
}

function check_auth_cookie()
{
	global $CONFIG;
	$name = $CONFIG['authorization']['cookie_name']."_user";
	$pass = $CONFIG['authorization']['cookie_name']."_pass";

	$user_name_field = $CONFIG['authorization']['user_name_field'];
	$user_pass_field = $CONFIG['authorization']['user_pass_field'];

	$ds = model_datasource("system");
	$user = $ds->CreateInstance('User');
	if( isset($_COOKIE["$name"]) && isset($_COOKIE["$pass"]) && $_COOKIE["$name"] != "" && $_COOKIE["$pass"] != "" && $user->Load("$user_name_field=?0 AND $user_pass_field=?1",array($_COOKIE["$name"],urldecode($_COOKIE["$pass"]))) )
	{
		store_object($user,"user");
		return true;
	}
	else
		return false;
}

?>