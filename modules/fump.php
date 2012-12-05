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
 
/**
 * Generic interface with FUMP mail processor (E.g. user registration)
 */
function fump_init()
{
	global $CONFIG;

	if( !isset($CONFIG['fump']['url']) )
		$CONFIG['fump']['url'] = "";

}

/**
 * FUMP user registration.
 */
class FumpUser
{
	/**
	 * Register a new fump user for the given account.
	 * @param int $account FUMP account id (list id).
	 * @param string $email Email address of the new user.
	 * @param string $name Optional name. If null the email address is re-used.
	 * @return bool True if registration was successfull, otherwise false.
	 */
	public static function Register($account, $email, $name=null)
	{
		global $CONFIG;
		/**
		 * Sanitize input.
		 */
		$account = intval($account);
		if(is_null($name))
			$name = $email;
		if( !isset($CONFIG['fump']['url']) || ("" == $CONFIG['fump']['url']) )
		{
				log_debug('FumpUser::Register(): FATAL ERROR - FUMP URL not configured!');
				return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $CONFIG['fump']['url'].'?account='.$account.
			'&name='.urlencode($name).'&email='.urlencode($email));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		log_debug(curl_exec($ch));

		// close cURL resource, and free up system resources
		curl_close($ch);
	}
}

?>