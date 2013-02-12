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
 
function payment_init()
{
	global $CONFIG;
	$CONFIG['class_path']['system'][] = dirname(__FILE__).'/payment/';
	
	$CONFIG['resources'][] = array
	(
		'ext' => 'png|jpg|gif',
		'path' => realpath(__DIR__.'/../skin/payment/'),
		'url' => $CONFIG['system']['system_uri'].'skin/payment/',
		'append_nc' => true,
	);
}

abstract class PaymentProvider
{
	public $title = "";
	public $type = null;
	public $type_name = null;
	public $small_image = null;
	protected $data = array();
	
	const PROCESSOR_INTERNAL	= 0; //"internal";
	const PROCESSOR_PAYPAL		= 1; //"paypal";
	const PROCESSOR_GATE2SHOP	= 2; //"gate2shop";
	const PROCESSOR_TESTING		= 3; //"test";
	
	public function SetVar($name,$value)
	{
		$this->data[$name] = $value;
		return $this;
	}
	
	function __construct()
	{
		$this->title = "TXT_PAYMENTPROVIDER_".strtoupper(get_class($this));
	}
	
	/**
	 * Possibility to disable/enable the payment provider list
	 * @return type 
	 */
	public function IsAvailable()
	{
		return true;
	}
	
	/**
	 * Handle the IPN (called DMN at g2s, ...) call from the payment provider
	 * @param type $ipndata Array with IPN data (i.e. POST data) from payment provider
	 * @return bool|string True if everything went well, errormessage as string otherwise 
	 */
	public function HandleIPN($ipndata)
	{
		return true;
	}
	
	/**
	 * Ensure a valid processor_id
	 * @param string $processor_id One of PROCESSOR_PAYPAL or PROCESSOR_GATE2SHOP.
	 * @return string One of PROCESSOR_PAYPAL or PROCESSOR_GATE2SHOP.
	 */
	static function SanitizePaymentProcessorId($processor_id)
	{
		switch($processor_id)
		{
			case self::PROCESSOR_INTERNAL:
			case self::PROCESSOR_GATE2SHOP:
			case self::PROCESSOR_PAYPAL:
			case self::PROCESSOR_TESTING:
				return $processor_id;
				
			default:
				return self::PROCESSOR_PAYPAL;
		}
	}	
	
	protected function Redirect($url)
	{
		$q = array();
		foreach( $this->data as $k=>$v )
			$q[] = "$k=".urldecode($v);
		log_debug(get_class($this)."::Redirect -> $url?$q",$url,$q,$this->data);
		redirect("$url?$q");
	}
	
	protected function CheckoutForm($url)
	{
		$form = new Form();
		$form->action = $url;
		$form->method = 'post';
		$form->class = 'nocsrf';
		foreach( $this->data as $k=>$v )
			$form->AddHidden($k,$v);
		$form->script("$('#{$form->id}').submit();");
		return $form;
	}
	
	abstract public function StartCheckout(IShopOrder $order);
	
	/**
	 * Correct the status from the arguments passed by the PP
	 * @param string $status status passed by PP
	 * @param array $ipndata data from the PP
	 * @return string the status 
	 */
	public function SanitizeStatusFromPP($status, $ipndata)
	{
		return $status;
	}
	/*
	 * Process the user returning from the PP
	 */
	public function HandleReturnFromPP($ipndata) 
	{
		return true;
	}
}

interface IShopOrder
{
	function GetInvoiceId();
	function GetCurrency();
	function SetCurrency($currency_code);
	function GetLocale();
	function ListItems();
	function GetAddress();		// must return a ShopOrderAddress object
	function GetTotalPrice($price = false);
	function GetTotalVat();
	function GetVatPercent();
}

class ShopOrderAddress
{
	public $Firstname;
	public $Lastname;
	public $Companyname;
	public $Email;
	public $Address1;	
	public $Address2;
	public $Country;
	public $State;
	public $Zip;
	public $City;
	public $Phone1;
	public $Phone2;
	public $Phone3;
}

interface IShopOrderItem
{
	function GetName();				// items name
//	function GetSerial();			// itemcode/serial
	function GetAmount($currency);	// price per item in the requested currency
	function GetShipping();			// shipping cost per item
	function GetHandling();			// handling cost per item
	function GetDiscount();			// discount for this item
	function GetQuantity();			// number of pieces
}

function payment_list_providers()
{
	$res = array();
	foreach( system_glob(dirname(__FILE__).'/payment/*.class.php') as $file )
	{
		$cn = basename($file,".class.php");
		$cn = new $cn();
		if( ($cn instanceof PaymentProvider) && $cn->IsAvailable() )
			$res[] = $cn;
	}
	return $res;
}