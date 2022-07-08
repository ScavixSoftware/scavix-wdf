<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

use ScavixWDF\Payment\PaymentProvider;
use ScavixWDF\WdfException;
 
/**
 * Initializes the payment module.
 * 
 * @return void
 */
function payment_init()
{
	global $CONFIG;
	$CONFIG['class_path']['system'][] = __DIR__.'/payment/';
	
	if( !isset($CONFIG["payment"]["order_model"]) || !$CONFIG["payment"]["order_model"] )
		WdfException::Raise('Please configure an order_model for the payment module');
}

/**
 * Returns a list of payment providers.
 * 
 * @return array List of <PaymentProvider> objects
 */
function payment_list_providers()
{
	$res = [];
	foreach( system_glob(__DIR__.'/payment/*.class.php') as $file )
	{
		$cn = basename($file,".class.php");
		$cn = new $cn();
		if( ($cn instanceof PaymentProvider) && $cn->IsAvailable() )
			$res[] = $cn;
	}
	return $res;
}

if( !function_exists('check_vat_number') )
{
    /**
     * Checks if a vat number is valid
     * 
     * @param string $vat_number VAT number to be checked
     * @return bool true if valid, else false
     */
    function check_vat_number($vat_number)
    {
        $vat = strtoupper(preg_replace('/[^0-9A-Z]/i', '', $vat_number));
        if (preg_match("/^(AT|BE|BG|CY|CZ|DE|DK|EE|EL|ES|FI|FR|GB|HU|IE|IT|LT|LU|LV|MT|NL|PL|PT|RO|SE|SI|SK)(.*)/i", $vat, $matches))
        {
            $country_code = strtoupper($matches[1]);
            $vat = $matches[2];
        }
        if( !isset($country_code) )
            return false;

        $regex = array(
            'AT'=>'/(U[0-9]{8})/i',
            'BE'=>'/(0[0-9]{9})/i',
            'BG'=>'/([0-9]{9,10})/i',
            'CY'=>'/([0-9]{8}[a-z])/i',
            'CZ'=>'/([0-9]{8}|[0-9]{9}|[0-9]{10})/i',
            'DE'=>'/([0-9]{9})/i',
            'DK'=>'/([0-9]{8})/i',
            'EE'=>'/([0-9]{9})/i',
            'EL'=>'/([0-9]{9})/i',
            'ES'=>'/([a-z][0-9]{8}|[0-9]{8}[a-z]|[a-z][0-9]{7}[a-z])/i',
            'FI'=>'/([0-9]{8})/i',
            'FR'=>'/([a-z0-9]{2}[0-9]{9})/i',
            'GB'=>'/([0-9]{9}|[0-9]{12}|GD[0-9]{3}|HA[0-9]{3})/i',
            'HU'=>'/([0-9]{8})/i',
            'IE'=>'/([0-9][a-z0-9\+\*][0-9]{5}[a-z])/i',
            'IT'=>'/([0-9]{11})/i',
            'LT'=>'/([0-9]{9}|[0-9]{12})/i',
            'LU'=>'/([0-9]{8})/i',
            'LV'=>'/([0-9]{11})/i',
            'MT'=>'/([0-9]{8})/i',
            'NL'=>'/([0-9]{9}B[0-9]{2})/i',
            'PL'=>'/([0-9]{10})/i',
            'PT'=>'/([0-9]{9})/i',
            'RO'=>'/([0-9]{2,10})/i',
            'SE'=>'/([0-9]{12})/i',
            'SI'=>'/([0-9]{8})/i',
            'SK'=>'/([0-9]{10})/i',
        );

        if( !isset($regex[$country_code]) )
            return false;

        if( !preg_match($regex[$country_code],$vat,$m) )
            return false;

        // only ask service is syntax-check is ok
        if( $m[1] == $vat )
        {
            $lock = 'europa.eu-checkVatService';
            try{
                $res = cache_get("vat_check_{$country_code}_{$vat}");
                if( !$res )
                {
                    if( !ScavixWDF\Wdf::GetLock($lock) )
                        return true;
                    $sc = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
                    $test = $sc->checkVat(array('countryCode'=>$country_code,'vatNumber'=>$vat));
                    if( !$test->valid )
                        log_debug("VAT syntax ok, but SOAP says not",$vat_number,$country_code,$vat,$test);

                    $res = $test->valid?"valid":"invalid";
                    cache_set("vat_check_{$country_code}_{$vat}", $res);
                }
                elseif( $res != "valid" )
                    log_debug("VAT syntax ok, but CACHE says not",$vat_number,$country_code,$vat);
                return $res == "valid";
            }
            catch(Exception $ex)
            { 
                WdfException::Log($ex); 
            }
            finally
            {
                ScavixWDF\Wdf::ReleaseLock($lock);
            }
            return true; // ignore service exceptions
        }
        return false;
    }
}