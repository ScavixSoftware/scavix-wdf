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
namespace ScavixWDF\Admin;

/**
 * Represents a user logged into <SysAdmin>.
 * 
 * @internal Has methods to login/logout/...
 */
class SysAdminUser 
{
    public $username, $role, $properties, $_storage_id;
    
	function __construct($data=[])
    {
        if( unserializer_active() )
            return;
        $this->username = ifavail($data,'username');
        $this->role = ifavail($data,'role')?:'admin';
        $this->properties = array_filter
        (
            $data, 
            function($k){ return !is_in($k,'username','password','role','enabled','actions'); },
            ARRAY_FILTER_USE_KEY
        );
    }
    
    static function IsAuthenticated()
    {
        return restore_object('sysadmin_user') instanceof SysAdminUser;
    }
    
    static function GetCurrent()
    {
        $user = restore_object('sysadmin_user');
        return ($user && ($user instanceof SysAdminUser))?$user:false;
    }
    
    static function Login($username,$password)
    {
        $CFG = $GLOBALS['CONFIG']['system']['admin'];
        $credentials = avail($CFG,'credentials')?$CFG['credentials']:[$CFG];
        //log_debug($credentials);
        foreach( $credentials as $cred )
        {
            if( $username != $cred['username'] || $password != $cred['password'] )
                continue;

            $user = new SysAdminUser($cred);
            store_object($user,'sysadmin_user');
//            log_debug("SysAdmin logged in:",$user);
            return true;
        }
        return false;
    }
    
    function Logout()
    {
        delete_object('sysadmin_user');
    }
    
    function hasAccess($controller,$method)
    {
        list($controller,$method) = array_map('strtolower',[$controller,$method]);
        $controller = array_last(explode("\\",$controller));
        switch( $controller )
        {
            case 'login': 
                return true;
            case 'sysadmin': 
                if( is_in($method,'forbidden','logout') )
                    return true;
                break;
        }
        switch( $this->role )
        {
            case 'admin': return true;
            case 'translator':
                if( $controller == 'translationadmin' && is_in($method,'newstrings','translate','savestring') )
                    return true;
                break;
        }
        return false;
    }
    
    function getProperty($name, $default=false)
    {
        return isset($this->properties[$name])?$this->properties[$name]:$default;
    }
}
