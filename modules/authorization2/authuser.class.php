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
 
class AuthUser extends System_Model
{
	var $UsernameField = 'username';
	var $PasswordField = 'password';

    function __construct(&$ds=null)
	{
		global $CONFIG;

		if( !isset($CONFIG['model']['authuser']) )
			$CONFIG['model']['authuser'] = 'users';

		if( is_null($ds) )
			$ds = model_datasource($CONFIG['authorization']['datasource']);
		parent::__construct($ds);
	}
	
	/**
	 * just to get around the warning messafe for system_model session storage
	 */
	public function __wakeup() 
	{
	}

	function Verify()
	{
		$unf = $this->UsernameField;
		$pwf = $this->PasswordField;
		$test = $this->DataSource->ExecuteScalar(
			"SELECT count(*) FROM {$this->_table} WHERE $unf=?0 AND BINARY $pwf=?1",
			array($this->$unf,$this->$pwf) );
		return $test == 1;
	}

	function Login($username,$password,$pw_is_encrypted = false)
	{
		$unf = $this->UsernameField;
		$pwf = $this->PasswordField;

		if( !$pw_is_encrypted )
			$password = md5($password);
		return $this->Load("$unf=?0 AND BINARY $pwf=?1",array($username,$password));
	}

	function HasRight($route,$check_inherited=true)
	{
		if( !$check_inherited )
		{
			$sql = "select count(*) from users u
					left join user_groups ug on ug.user_id=u.id
					left join groups g on g.id = ug.group_id
					left join user_rights ur on ur.user_id = u.id AND (ur.route=?0)
					left outer join group_rights gr on gr.group_id = g.id AND (gr.route=?1)
					where u.id = ?2 AND
					(
						( ( (ur.forbidden = 0 OR ur.forbidden is null) AND ur.route is not null) AND gr.forbidden = 1 )
						OR
						( ( ur.forbidden = 0 OR ur.forbidden is null ) AND ( gr.forbidden = 0 OR gr.forbidden is null) )

					)";
			$res = $this->DataSource->ExecuteScalar($sql, array($route,$route,$this->id));
			return $res > 0;
		}
		
		$sql = "select count(*) from users u
				left join user_groups ug on ug.user_id=u.id
				left join groups g on g.id = ug.group_id
				left join user_rights ur on ur.user_id = u.id AND (?0 LIKE ur.route OR ur.route LIKE ?1)
				left outer join group_rights gr on gr.group_id = g.id AND (?2 LIKE gr.route OR gr.route LIKE ?3)
				where u.id = ?4
				AND
				(
					( ( (ur.forbidden = 0 OR ur.forbidden is null) AND ur.route is not null) AND gr.forbidden = 1 )
					OR
					( ( ur.forbidden = 0 OR ur.forbidden is null ) AND ( gr.forbidden = 0 OR gr.forbidden is null) )
					
				)";

		$res = $this->DataSource->ExecuteScalar($sql, array($route,$route,$route,$route,$this->id));
		return $res > 0;
	}

    function SetCookie()
    {
        global $CONFIG, $IS_SSL;
		
		$unf = $this->UsernameField;
		$pwf = $this->PasswordField;
        setcookie($CONFIG['authorization']['cookie_name'], $this->$unf.'|'.$this->$pwf, time()+(60*60*24*$CONFIG['authorization']['cookie_ttl']), '/', null, $IS_SSL, true);
    }

    function ClearCookie()
    {
        global $CONFIG, $IS_SSL;

        setcookie($CONFIG['authorization']['cookie_name'], '', time()-(60*60*24*$CONFIG['authorization']['cookie_ttl']), '/', null, $IS_SSL, true);
    }

    static function CheckCookie()
    {
        global $CONFIG;

		$cookieName = $CONFIG['authorization']['cookie_name'];
        $arCookieContent = ( isset($_COOKIE[$cookieName]) != '' ) ? explode('|', $_COOKIE[$cookieName]) : '';

		$ds = model_datasource($CONFIG['authorization']['datasource']);
        $user = $ds->CreateInstance($CONFIG['authorization']['user_type']);
        if( isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] != '' && $user->Load($user->UsernameField."=?0 AND ".$user->PasswordField."=?1",array($arCookieContent[0],$arCookieContent[1])) )
        {
            store_object($user,'user');
            return true;
        }
        else
            return false;
    }
}
?>