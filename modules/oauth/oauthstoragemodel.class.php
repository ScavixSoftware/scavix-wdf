<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2021 Scavix Software GmbH & Co. KG
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
 * @copyright since 2021 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Model;

use ScavixWDF\Base\DateTimeEx;
use ScavixWDF\OAuth\OAuthHandler;

class OAuthStorageModel extends Model
{
    public function GetTableName() { return "wdf_oauthstore"; }

    protected function CreateTable()
    {
        $this->_ds->ExecuteSql(
            "CREATE TABLE IF NOT EXISTS `wdf_oauthstore` (
                `local_id` int NULL DEFAULT NULL,
                `provider` VARCHAR(255) NULL DEFAULT NULL,
                `identifier` VARCHAR(255) NULL DEFAULT NULL,
                `access_token` TEXT NULL DEFAULT NULL,
                `refresh_token` TEXT NULL DEFAULT NULL,
                `expires` TIMESTAMP NULL DEFAULT NULL,
                `deleted` TIMESTAMP NULL DEFAULT NULL,
                `resource_owner_id` TEXT NULL DEFAULT NULL,
                `data` TEXT NULL DEFAULT NULL,
                `owner_data` TEXT NULL DEFAULT NULL,
                PRIMARY KEY (`local_id`,`provider`)
            );");
    }
    
    static function Search($local_id,$provider_name=false)
    {
        return OAuthStorageModel::Make()->eq('local_id',$local_id)
            ->if($provider_name)->eq('provider',$provider_name);
    }
    
    function UpdateFromToken(\League\OAuth2\Client\Token\AccessToken $token)
    {
        $this->access_token = $token->getToken();
        $this->refresh_token = $token->getRefreshToken();
        $this->expires = DateTimeEx::Make($token->getExpires());
        if( $token instanceof \League\OAuth2\Client\Token\ResourceOwnerAccessTokenInterface )
            $this->resourceowner_id = $token->getResourceOwnerId();
        
        $this->data = json_encode($token->getValues());
        $this->Save();
    }
    
    function UpdateFromOwner(\League\OAuth2\Client\Provider\ResourceOwnerInterface $owner)
    {
        $data = $owner->toArray();
        
        $this->identifier = ifavail(array_change_key_case($data,CASE_LOWER),'email','e-mail','e_mail','userid','user-id','user_id');
        if( !$this->identifier )
        {
            foreach( $data as $d )
            {
                if( !is_array($d) )
                    continue;
                $this->identifier = ifavail(array_change_key_case($d,CASE_LOWER),'email','e-mail','e_mail','userid','user-id','user_id');
                if( $this->identifier )
                {
                    $data = $d;
                    break;
                }
            }
        }
        $this->owner_data = json_encode($data);
        $this->Save();
    }
    
    function GetTokenData()
    {
        $data = $this->AsArray('access_token','refresh_token','resource_owner_id');
        $data['expires'] = $this->expires->getTimestamp();
        return $data;
    }
    
    function Validate()
    {
        $handler = new OAuthHandler($this->local_id, $this->provider);
        return $handler->isAuthorized($this);
    }
    
    function Delete()
    {
        $this->access_token = null;
        $this->refresh_token = null;
        $this->expires = null;
        $this->data = null;
        $this->deleted = 'now()';
        // keep identifier and owner_data to be able to match returning users
        return $this->Save();
    }
}
