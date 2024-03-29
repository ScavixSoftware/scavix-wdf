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
namespace ScavixWDF\Payment;

use ScavixWDF\Controls\Form\Form;

/**
 * Payment providers must extend this class.
 * 
 */
abstract class PaymentProvider
{
	public $title = "";
	public $type = null;
	public $type_name = null;
	public $small_image = null;
	protected $data = [];
	
	const PROCESSOR_INTERNAL	= 0; //"internal";
	const PROCESSOR_PAYPAL		= 1; //"paypal";
	const PROCESSOR_GATE2SHOP	= 2; //"gate2shop";
	const PROCESSOR_TESTING		= 3; //"test";
	
	protected function LoadOrder($order_id)
	{
		return call_user_func("{$GLOBALS['CONFIG']["payment"]["order_model"]}::FromOrderId",$order_id);
	}
	
	/**
	 * Sets data for the PaymentProvider.
	 * 
	 * @param string $name Argument name
	 * @param mixed $value Argument value
	 * @return PaymentProvider `$this`
	 */
	public function SetVar($name,$value)
	{
		$this->data[$name] = $value;
		return $this;
	}
	
	function __construct()
	{
		$this->title = "TXT_PAYMENTPROVIDER_".strtoupper(get_class_simple($this));
	}
	
	/**
	 * Possibility to disable/enable the payment provider list
	 * @return bool 
	 */
	public function IsAvailable()
	{
		return true;
	}
	
	/**
	 * Handle the IPN (called DMN at g2s, ...) call from the payment provider
	 * @param mixed $ipndata Array with IPN data (i.e. POST data) from payment provider
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
		$q = [];
		foreach( $this->data as $k=>$v )
			$q[] = "$k=".urldecode($v);
        redirect("$url?" . implode("&", $q));
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
	
    /**
     * Return any fields that need to be shown in the checkout process before the checkout can be used
     * @return array
     */
	public function GetCheckoutInputFields() : array
	{
		return [];
	}
    
    /**
     * Return the renderable object that shows any button, form or qr code to start the checkout process
     * 
     * @param IShopOrder $order The order to get UI for
     * @return bool
     */
    public function GetStartCheckoutRenderable(IShopOrder $order)
    {
        return false;
    }
	
	/**
	 * Starts the checkout process
	 * 
	 * @param IShopOrder $order The order to start checkout for
	 * @param string $ok_url URL to be redirected to after payment
	 * @param string $cancel_url URL to be redirected to when user cancels payment
	 * @return Form Must return a <Form> control 
	 */
	abstract public function StartCheckout(IShopOrder $order, $ok_url=false, $cancel_url=false);
	
	/**
	 * Correct the status from the arguments passed by the PP.
	 * 
	 * @param string $status status passed by PP
	 * @param array $ipndata data from the PP
	 * @return string the status 
	 */
	public function SanitizeStatusFromPP($status, $ipndata)
	{
		return $status;
	}
	
	/**
	 * Process the user returning from the PP.
	 * 
	 * @param mixed $ipndata Data returned from PP
	 * @return bool currently always true
	 */
	public function HandleReturnFromPP($ipndata) 
	{
		return true;
	}
    
    protected function compatConfig()
    {
        if( !avail($GLOBALS,'CONFIG',"invoices","invoice_id_prefix") )
            return;
        log_warn("Deprecated CONFIG found: ['invoices']['invoice_id_prefix'] should be ['payment']['invoice_id_prefix']");
        if( !avail($GLOBALS,'CONFIG',"payment","invoice_id_prefix") )
            $GLOBALS['CONFIG']['payment']['invoice_id_prefix'] = $GLOBALS['CONFIG']['invoices']['invoice_id_prefix'];
    }
}