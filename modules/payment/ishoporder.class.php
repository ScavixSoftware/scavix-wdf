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
namespace ScavixWDF\Payment;

/**
 * Order <Model>s must implement this interface.
 * @property mixed $amount_currency
 */
interface IShopOrder
{
	/**
	 * Creates an instance from an order id.
	 * @param mixed $order_id ID of order to load
	 * @return IShopOrder The new/loaded order <Model>
	 */
	static function FromOrderId($order_id);
	
	/**
	 * Gets the invoice ID.
	 * @return mixed Invoice identifier
	 */
	function GetInvoiceId();
	
	/**
	 * Gets the currency code.
	 * @return string A valid currency code
	 */
	function GetCurrency();
	
	/**
	 * Sets the currency
	 * @param string $currency_code A valid currency code
	 * @return void
	 */
	function SetCurrency($currency_code);
	
	/**
	 * Gets the order culture code.
	 * 
	 * See <CultureInfo>
	 * @return string Valid culture code
	 */
	function GetLocale();
	
	/**
	 * Returns all items.
	 * 
	 * @return array A list of <IShopOrderItem> objects
	 */
	function ListItems();
	
	/**
	 * Gets the orders address.
	 * @return ShopOrderAddress The order address
	 */
	function GetAddress();
	
    /**
	 * Return the total price incl. VAT (if VAT applies for the given country). 
	 * @param float $price The price without VAT.
	 * @return float Price including VAT (if VAT applies for the country).
	 */
	function GetTotalPrice($price = false);
    
    /**
	 * Return the total VAT (if VAT applies for the given country). 
	 * @return float VAT in order currency
	 */
	function GetTotalVat();
    
    /**
	 * Return the total VAT percent (if VAT applies for the given country). 
	 * @return float VAT percent
	 */
	function GetVatPercent();
	
	/**
	 * Called when the order has been paid.
	 * 
	 * This is a callback from the payment processor. Will be called when the customer has paid the order.
	 * @param int $payment_provider_type Provider type identifier (<PaymentProvider>::PROCESSOR_PAYPAL, <PaymentProvider>::PROCESSOR_GATE2SHOP, ...)
	 * @param mixed $transaction_id Transaction identifier (from the payment provider)
	 * @param string $statusmsg An optional status message
	 * @return void
	 */
	function SetPaid($payment_provider_type, $transaction_id, $statusmsg = false);
	
	/**
	 * Called when the order has reached pending state.
	 * 
	 * This is a callback from the payment processor. Will be called when the customer has paid the order but the
	 * payment has not yet been finished/approved by the provider.
	 * @param int $payment_provider_type Provider type identifier (<PaymentProvider>::PROCESSOR_PAYPAL, <PaymentProvider>::PROCESSOR_GATE2SHOP, ...)
	 * @param mixed $transaction_id Transaction identifier (from the payment provider)
	 * @param string $statusmsg An optional status message
	 * @return void
	 */
	function SetPending($payment_provider_type, $transaction_id, $statusmsg = false);
	
	/**
	 * Called when the order has failed.
	 * 
	 * This is a callback from the payment processor. Will be called when there was an error in the payment process.
	 * This can be synchronous (when cutsomer aborts in then initial payment ui) or asynchronous when something goes wrong
	 * later in the payment processors processes.
	 * @param int $payment_provider_type Provider type identifier (<PaymentProvider>::PROCESSOR_PAYPAL, <PaymentProvider>::PROCESSOR_GATE2SHOP, ...)
	 * @param mixed $transaction_id Transaction identifier (from the payment provider)
	 * @param string $statusmsg An optional status message
	 * @return void
	 */
	function SetFailed($payment_provider_type, $transaction_id, $statusmsg = false);
	
	/**
	 * Called when the order has been refunded.
	 * 
	 * This is a callback from the payment processor. Will be called when the payment was refunded for any reason.
	 * This can be reasons from the provider and/or from the customer (when he cancels the payment later).
	 * @param int $payment_provider_type Provider type identifier (<PaymentProvider>::PROCESSOR_PAYPAL, <PaymentProvider>::PROCESSOR_GATE2SHOP, ...)
	 * @param mixed $transaction_id Transaction identifier (from the payment provider)
	 * @param string $statusmsg An optional status message
	 * @return void
	 */
	function SetRefunded($payment_provider_type, $transaction_id, $statusmsg = false);
	
	/**
	 * Checks if VAT needs to be paid.
	 * @return bool true or false
	 */
	function DoAddVat();
}