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
namespace ScavixWDF\OAuth;

use Exception;
use ScavixWDF\Model\OAuthStorageModel;
use ScavixWDF\Wdf;
use ScavixWDF\WdfException;

/**
 * @suppress PHP0413
 */
class OAuthHandler
{
    protected static $map = 
    [
        // 'Official Provider Clients' https://oauth2-client.thephpleague.com/providers/league/ 
        'facebook'  => [
            'cls' => '\League\OAuth2\Client\Provider\Facebook',  
            'pkg' => 'league/oauth2-facebook',
            'cfg' => [ 'graphApiVersion' => 'v2.10' ],
        ],
        'github'    => ['cls'=>'\League\OAuth2\Client\Provider\Github',    'pkg'=>'league/oauth2-github' ],
        'google'    => ['cls'=>'\League\OAuth2\Client\Provider\Google',    'pkg'=>'league/oauth2-google' ],
        'instagram' => ['cls'=>'\League\OAuth2\Client\Provider\Instagram', 'pkg'=>'league/oauth2-instagram' ],
        'linkedin'  => ['cls'=>'\League\OAuth2\Client\Provider\LinkedIn',  'pkg'=>'league/oauth2-linkedin' ],
        
        // 'Third-party Provider Clients' https://oauth2-client.thephpleague.com/providers/thirdparty/
        'hubspot'    => ['cls'=>'\HelpScout\OAuth2\Client\Provider\HubSpot',        'pkg'=>'helpscout/oauth2-hubspot' ],
        'pipedrive'  => ['cls'=>'\Daniti\OAuth2\Client\Provider\Pipedrive',         'pkg'=>'daniti/oauth2-pipedrive' ],
        'zoho'       => ['cls'=>'\Asad\OAuth2\Client\Provider\Zoho',                'pkg'=>'asad/oauth2-zoho' ],
        'amazon'     => ['cls'=>'\MichaelKaefer\OAuth2\Client\Provider\Amazon',     'pkg'=>'michaelkaefer/oauth2-amazon' ],
        'salesforce' => ['cls'=>'\Stevenmaguire\OAuth2\Client\Provider\Salesforce', 'pkg'=>'stevenmaguire/oauth2-salesforce' ],
        //'' => ['cls'=>'', 'pkg'=>'' ],
    ];
    var $local_id, $provider_name, $provider_config, $state;
    
    function __construct($local_id, $provider_name, $provider_config=[])
    {
        log_debug(__METHOD__);
        $this->local_id = $local_id;
        $this->provider_name = $provider_name;
        $this->state = false;
        
        $def = ['redirectUri' => system_current_request(true)];
        $map = avail(self::$map,$provider_name,'cfg')?self::$map[$provider_name]['cfg']:[];
        $cfg = Wdf::GetBuffer('oauth_configurations')->get($provider_name,[]);
        
        $this->provider_config = array_merge($def,$map,$cfg,$provider_config);
        
        if( !avail($this->provider_config,'clientId') )
            WdfException::Raise("Missing clientId for OAuth provider ".$this->provider_name);
        if( !avail($this->provider_config,'clientSecret') )
            WdfException::Raise("Missing clientSecret for OAuth provider ".$this->provider_name);
    }
    
    function getProviderInstance()
    {
        $map = ifavail(self::$map,$this->provider_name);
        if( !$map )
        {
            log_error("Unknown OAuth provider {$this->provider_name}. Implementation in WDF missing.");
            return null;
        }
        if( !class_exists($map['cls']) )
        {
            log_error("OAuth provider {$this->provider_name} not found (composer require {$map['pkg']})");
            return null;
        }
        return new $map['cls']($this->provider_config);
    }
    
    function getTokenInstance($data)
    {
        // todo: check map for special classes
        return new \League\OAuth2\Client\Token\AccessToken($data);
    }
    
    function authorize()
    {
        try
        {
            if( isset($_GET['error_code']) )
            {
                delete_object('oauth_current_handler');
                log_debug(__METHOD__,'OAuth error',$this,$_GET);
                die();
            }
            
            $provider = $this->getProviderInstance();
            if( !$provider )
            {
                delete_object('oauth_current_handler');
                return;
            }
            
            if( !isset($_GET['code']) )
            {
                $authorizationUrl = $provider->getAuthorizationUrl(); // must be called before ->getState() !
                $this->state = $provider->getState();
                store_object($this,'oauth_current_handler');
                
                log_debug(__METHOD__,'STAGE1',$this);
                header('Location: ' . $authorizationUrl);
                die();
            }
            elseif( !$this->state || ifavail($_GET,'state') !== $this->state )
            {
                delete_object('oauth_current_handler');
                log_debug(__METHOD__,'Invalid OAuth state',$this,$_GET);
                die();
            }
            else
            {
                log_debug(__METHOD__,'STAGE2',$this);
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);

                $model = OAuthStorageModel::Search($this->local_id,$this->provider_name)
                    ->current()?:new OAuthStorageModel();
                $model->provider = $this->provider_name;
                $model->local_id = $this->local_id;
                $model->UpdateFromToken($token);
                
                try
                {
                    $owner = $provider->getResourceOwner($token);
                    $model->UpdateFromOwner($owner);
                } catch (Exception $ex) { log_debug($ex); }
                
                log_debug(__METHOD__,'Token',$token->jsonSerialize(),$token->getValues());
                delete_object('oauth_current_handler');
            }
        }
        catch (Exception $ex)
        {
            log_error($ex);
            die();
        }
    }
    
    function isAuthorized($model=false)
    {
        $model = $model?:OAuthStorageModel::Search($this->local_id,$this->provider_name)->current();
        if( !$model )
            return false;
        
        try
        {
            if( $model->expires->is_future_date() )
                return true;
            
            $token = $this->getTokenInstance($model->GetTokenData());
            if( !$token->hasExpired() )
                return true;
            
            $provider = $this->getProviderInstance();
            $token = $provider->getAccessToken('refresh_token', [
                'refresh_token' => $token->getRefreshToken()
            ]);
            
            $model->UpdateFromToken($token);
            
            try
            {
                $owner = $provider->getResourceOwner($token);
                $model->UpdateFromOwner($owner);
            } catch (Exception $ex) { log_debug($ex); }
            
            return true;
        }
        catch (Exception $ex)
        {
            log_error($ex);
        }
        return false;
    }
}
