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
 
function authorization2_init()
{
	global $CONFIG;
	$CONFIG['class_path']['model'][] = dirname(__FILE__).'/authorization2/';
	$CONFIG['class_path']['system'][] = dirname(__FILE__).'/../lib/widgets/authorization2/';

	if( !isset($CONFIG['authorization']['datasource']) )
		$CONFIG['authorization']['datasource'] = 'system';

	if( !isset($CONFIG['authorization']['group_type']) )
		$CONFIG['authorization']['group_type'] = 'AuthGroup';

	if( !isset($CONFIG['authorization']['user_type']) )
		$CONFIG['authorization']['user_type'] = 'AuthUser';

	if( !isset($CONFIG['authorization']['cookie_name']) )
		$CONFIG['authorization']['cookie_name'] = 'framework_cookie';

	if( !isset($CONFIG['authorization']['cookie_ttl']) ) // lifetime in days!
		$CONFIG['authorization']['cookie_ttl'] = 180;		
}

function auth_ensure_tables()
{
	global $CONFIG;
	$ds = model_datasource($CONFIG['authorization']['datasource']);
	$group = $ds->CreateInstance($CONFIG['authorization']['group_type']);
	$groupright = $ds->CreateInstance("AuthGroupRight");
	$user = $ds->CreateInstance($CONFIG['authorization']['user_type']);
	$user_group = $ds->CreateInstance("AuthUserGroup");
	$user_right = $ds->CreateInstance("AuthUserRight");

	if( !$group->Load("id=?0",1) )
	{
		$group->name = "Administrators";
		$group->Save();
	}

	if( !$user->Load("id=?0",1) )
	{
		$user->username = "admin";
		$user->password = md5("admin");
		$user->Save();
	}
	
	if( !$user_group->Load("user_id=?0 AND group_id=?1",array(1,1)) )
	{
		$user_group->user_id = 1;
		$user_group->group_id = 1;
		$user_group->Save();
	}
	
	if( !$groupright->Load("group_id=?0",1) )
	{
		$groupright->group_id = 1;
		$groupright->route = "%";
		$groupright->Save();
	}
}

function auth_user()
{
	return auth_verify(true);
}

function auth_verify($return_user = false)
{
	if( !in_object_storage('user') )
    {
        if( !AuthUser::CheckCookie() )
            return false;
    }

	$user = restore_object('user');
	if( !($user instanceof AuthUser) )
		return false;

	if( $return_user )
		return $user->Verify()?$user:false;
	return $user->Verify();
}

function auth_login($username,$password,$pw_is_encrypted = false)
{
	global $CONFIG;
	$ds = model_datasource($CONFIG['authorization']['datasource']);
	$user = $ds->CreateInstance($CONFIG['authorization']['user_type']);
	if( !$user->Login($username,$password,$pw_is_encrypted) )
		return false;

	store_object($user,'user');
	return true;
}

function loggedIn()
{
	if( in_object_storage('user') )
		return true;
	return false;
}

function auth_has_right($route)
{
	$user = auth_user();
	if( !$user )
		return false;

	return $user->HasRight($route);
}

function auth_set_cookie()
{
	$user = auth_user();
	if( !$user )
		return false;

	return $user->SetCookie();
}

function auth_clear_cookie()
{
	$user = auth_user();
	if( !$user )
		return false;

	return $user->ClearCookie();
}
?>