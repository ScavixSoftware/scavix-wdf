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
 
function settings_init()
{
	global $CONFIG;
	
	$CONFIG['class_path']['model'][] = dirname(__FILE__).'/settings/';
	
	if( !isset($CONFIG['settings']['datasource']) )
		$CONFIG['settings']['datasource'] = 'system';

	if( !isset($CONFIG['settings']['name_field']) )
		$CONFIG['settings']['name_field'] = 'name';

	if( !isset($CONFIG['settings']['section_field']) )
		$CONFIG['settings']['section_field'] = 'section';

	if( !isset($CONFIG['settings']['value_field']) )
		$CONFIG['settings']['value_field'] = 'value';

	if( !isset($CONFIG['settings']['type']) )
		$CONFIG['settings']['type'] = 'System_Model_Setting';
}

$_settingsbuffer = array();

function getSetting($section,$name,$default="")
{
	global $CONFIG, $_settingsbuffer;
    if(isset($_settingsbuffer[$section."_".$name]))
        return $_settingsbuffer[$section."_".$name];
	$ds = model_datasource($CONFIG['settings']['datasource']);

	$res = $ds->CreateInstance($CONFIG['settings']['type']);
	if( $res->Load("".$CONFIG['settings']['section_field']."=?0 AND ".$CONFIG['settings']['name_field']."=?1",array($section,$name)) )
	{
		//log_debug("getting $section.$name = ".$res->value." from ".$ds->Database());
        $_settingsbuffer[$section."_".$name] = $res->value;
		return $res->value;
	}
    $_settingsbuffer[$section."_".$name] = $default;
	return $default;
}

function setSetting($section,$name,$value)
{
	global $CONFIG, $_settingsbuffer;
	$ds = model_datasource($CONFIG['settings']['datasource']);

	$res = $ds->CreateInstance($CONFIG['settings']['type'],"".$CONFIG['settings']['section_field']."=?0 AND ".$CONFIG['settings']['name_field']."=?1",array($section,$name));

	$res->$CONFIG['settings']['section_field'] = $section;
	$res->$CONFIG['settings']['name_field'] = $name;
	$res->$CONFIG['settings']['value_field'] = $value;
	$res->Save();

    $_settingsbuffer[$section."_".$name] = $value;
}

function getUserSetting($name,$default = "")
{
	global $CONFIG;

	if( loggedInDirect() )
	{
		$res = getSetting( "user.".$_SESSION['userid'], $name, "__undefined__value__" );
//		log_debug("getUserSetting 1",$res);
		if( $res != "__undefined__value__" )
			return $res;
	}

	if( !loggedIn() )
	{
		if(isset($_COOKIE[$name]))
		{
//			log_debug("getUserSetting 2",$res);
			return $_COOKIE[$name];
		}
		return $default;
	}
	$user = restore_object('user');
	$res = getSetting( "user.".$user->$CONFIG['authorization']['user_id_field'], $name, "__undefined__value__" );
	if( $res != "__undefined__value__" )
	{
//		log_debug("getUserSetting 3",$res);
		return $res;
	}
	return $default;
}

function getAllUserSettings()
{
	if( !loggedIn() )
		return false;

	global $CONFIG;
	$ds = model_datasource($CONFIG['settings']['datasource']);

	$user = restore_object('user');
	$section = "user.".$user->$CONFIG['authorization']['user_id_field'];
	$rs = $ds->Select($CONFIG['settings']['type'],$CONFIG['settings']['section_field']."=?0",array($section));
	return $rs;
}

function setUserSetting($name,$value)
{
	global $CONFIG;
	if( !loggedIn() )
	{
		setcookie($name, $value, time()+30*60*60*1000, "/");
		$_COOKIE[$name] = $value;
		return false;
	}
	$user = restore_object('user');
	return setSetting( "user.".$user->$CONFIG['authorization']['user_id_field'],$name,$value );
}

?>