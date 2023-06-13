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

/**
 * Represents a dataset in the wdf_oauthstore table.
 * 
 * @suppress PHP0413
 */
class OAuthStorageModel extends Model
{
	/** @var int */
	public $local_id;
	
	/** @var string */
	public $provider;
	
	/** @var string */
	public $identifier;
	
	/** @var string */
	public $access_token;
	
	/** @var string */
	public $refresh_token;
	
	/** @var \ScavixWDF\Base\DateTimeEx|string */
	public $created;
	
	/** @var \ScavixWDF\Base\DateTimeEx|string */
	public $expires;
	
	/** @var \ScavixWDF\Base\DateTimeEx|string */
	public $deleted;
	
	/** @var string */
	public $resource_owner_id;
	
	/** @var string */
	public $data;
	
	/** @var string */
	public $owner_data;
    
    /**
     * @implements <Model::GetTableName()>
     */
    public function GetTableName() { return "wdf_oauthstore"; }

    protected function CreateTable()
    {
        $this->_ds->ExecuteSql(
            "CREATE TABLE IF NOT EXISTS `wdf_oauthstore` (
                `local_id` INT(11) NOT NULL,
                `provider` VARCHAR(255) NOT NULL,
                `identifier` VARCHAR(255) NULL DEFAULT NULL,
                `access_token` TEXT NULL DEFAULT NULL,
                `refresh_token` TEXT NULL DEFAULT NULL,
                `created` TIMESTAMP NULL DEFAULT current_timestamp(),
                `expires` TIMESTAMP NULL DEFAULT NULL,
                `deleted` TIMESTAMP NULL DEFAULT NULL,
                `resource_owner_id` TEXT NULL DEFAULT NULL,
                `data` TEXT NULL DEFAULT NULL,
                `owner_data` TEXT NULL DEFAULT NULL,
                PRIMARY KEY (`local_id`, `provider`) USING BTREE
            );");
    }
    
    /**
     * Searches datasets for a local_id, optionally filtered for a provider.
     * 
     * @param mixed $local_id The local user ID
     * @param string $provider_name Optional provider filter
     * @return OAuthStorageModel
     */
    static function Search($local_id,$provider_name=false)
    {
        return OAuthStorageModel::Make()->eq('local_id',$local_id)
            ->if($provider_name)->eq('provider',$provider_name);
    }

    /**
     * Generates a anonymous local ID.
     * 
     * This may be used later once the OAuth flow returns. 
     * Use case: Registration with OAuth without local user account.
     * @return mixed The local id
     */
    static function GetAnonId()
    {
        $ds = DataSource::Get();
        do
        {
            $anonid = random_int(-999999, -1);
        } while ($ds->ExecuteScalar('SELECT COUNT(*) FROM wdf_oauthstore WHERE local_id=? LIMIT 1', [$anonid]));
        return $anonid;
    }

    /**
     * Change a (previously anonymous) local ID.
     * 
     * @param mixed $new_local_id The new ID for the dataset.
     * @return static
     */
    function ChangeLocalId($new_local_id)
    {
        $this->_ds->ExecuteSql(
            "DELETE FROM wdf_oauthstore WHERE provider=? AND local_id=?",
            [$this->provider,$new_local_id]
        );
        $this->_ds->ExecuteSql(
            "UPDATE wdf_oauthstore SET local_id=? WHERE provider=? AND identifier=? AND local_id=?",
            [$new_local_id,$this->provider,$this->identifier,$this->local_id]
        );
        $this->local_id = $new_local_id;
        return $this;
    }
    
    /**
     * @internal Updates this datasets oauth token data.
     */
    function UpdateFromToken(\League\OAuth2\Client\Token\AccessToken $token)
    {
        $this->access_token = $token->getToken();
        $this->refresh_token = $token->getRefreshToken();
        $this->expires = $token->getExpires();
        $this->expires = $this->expires?DateTimeEx::Make($this->expires):null;
        if( $token instanceof \League\OAuth2\Client\Token\ResourceOwnerAccessTokenInterface )
            $this->resource_owner_id = $token->getResourceOwnerId();
        
        $this->data = json_encode($token->getValues());
        $this->Save();
    }
    
    /**
     * @internal Updates this datasets oauth owner data.
     */
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
    
    /**
     * Return oauth token data.
     * 
     * @return array
     */
    function GetTokenData()
    {
        $data = $this->AsArray('access_token','refresh_token','resource_owner_id');
        $data['expires'] = avail($this,'expires')?$this->expires->getTimestamp():null;
        return $data;
    }
    
    /**
     * Checks if this dataset still contains valid OAuth data.
     * 
     * @return bool True is valid, else false
     */
    function Validate()
    {
        $handler = new OAuthHandler($this->local_id, $this->provider);
        return $handler->isAuthorized($this);
    }
}
