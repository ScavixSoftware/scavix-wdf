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

/**
 * Initializes the OAuth module.
 * @return void
 */
function oauth_init()
{
    classpath_add(__DIR__."/oauth");
    if( !class_exists("\\League\\OAuth2\\Client\\Provider\\GenericProvider") )
    {
        log_warn("Missing OAuth classes, see https://oauth2-client.thephpleague.com/ for installation instructions");
        return;
    }
    
    if( in_object_storage('oauth_current_handler') )
    {
        $handler = restore_object('oauth_current_handler');
        register_hook(HOOK_POST_INIT,$handler,'authorize');
    }//else log_debug('OAuth not started');
}

/**
 * Starts OAuth process.
 * 
 * @param mixed $local_id Local user identifier
 * @param string $provider_name Name of the OAuth provider to be used
 */
function oauth_authorize($local_id, $provider_name, $provider_config=[])
{
    $handler = new ScavixWDF\OAuth\OAuthHandler($local_id, $provider_name, $provider_config);
    $handler->authorize();
}

/**
 * Add an OAuth provider config.
 * 
 * @param string $provider_name Name of the OAuth provider
 * @param string $client_id Client ID
 * @param string $client_secret Client Secret
 * @param array $options More (optional) options
 */
function oauth_add_config($provider_name,$client_id,$client_secret,$options=[])
{
    $options['clientId'] = $client_id;
    $options['clientSecret'] = $client_secret;
    ScavixWDF\Wdf::GetBuffer('oauth_configurations')->set($provider_name,$options);
}
