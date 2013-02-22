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
 
function invoices_init()
{
	global $CONFIG;

	$CONFIG['class_path']['model'][] = dirname(__FILE__).'/invoices/';

	system_load_module("modules/zend.php");
	zend_load("Zend/Pdf.php");
	zend_load("pdf/Cell.php");
	zend_load("pdf/pdfdocument.class.php");
	
	if(!isset($GLOBALS['VAT_COUNTRIES']))
		WdfException::Raise("VAT_COUNTRIES not defined (invoices_init)");
}

function invoices_check_requirements()
{
	global $CONFIG;
	if( !isset($CONFIG['invoices']['logofile']) )
		WdfException::Raise("\$CONFIG['invoices']['logofile'] not defined");	
	if(!file_exists($CONFIG['invoices']['logofile']))
		WdfException::Raise("invoice logo (".$CONFIG['invoices']['logofile'].") not found");	
}

function invoiceStandardLogo()
{
	invoices_check_requirements();
	return $GLOBALS['CONFIG']['invoices']['logofile'];
}

function createShopInvoice($invoicenr, $user, $shop)
{
	invoices_check_requirements();	
	$shop_user_id = $user->GetProfileValue('Shop','shop_user_id',0);
	$ds = model_datasource('system');

	$sql = "SELECT o.user_id, o.name, o.first_name, o.last_name,
				 o.address1, o.address2, o.city, o.zip,
				 o.country_id, o.tax_percent, o.tax_total, o.order_total, o.total_quantity,
				 o.order_id, o.invoice_number, o.order_placed_date, o.currency_code,
				 o.currency_rate, o.company_name, ps.payment_name as payment_processor, o.order_status
			FROM va_orders o
			LEFT JOIN va_orders_items i ON i.order_id=o.order_id
			INNER JOIN va_payment_systems ps ON ps.payment_id=o.payment_id
			WHERE o.invoice_number=?0 AND o.user_id=?1
			ORDER BY i.order_item_id";
	$rs = $shop->ExecuteSql($sql, array($invoicenr,$shop_user_id));
	if( $rs->Count() == 0 )
		return("not found!");

	$vat_country_code = $shop->ExecuteScalar("SELECT country_code
							 FROM va_countries
							 WHERE country_id = ?0", array($rs['country_id']));
	
	$vat_number = $shop->ExecuteScalar("SELECT property_value
							 FROM va_orders_properties
							 WHERE order_id = ?0 AND property_name like '%VAT%'", array($rs['order_id']));
	$pdf = new InvoicePdf();
	$pdf->Logo = invoiceStandardLogo();
	$pdf->InvoiceNumber = $invoicenr;
	$pdf->Firstname = $rs['first_name'];
	$pdf->Lastname = trim($rs['last_name'].$rs['name']);
	$pdf->Companyname = $rs['company_name'];
	$pdf->Zip = $rs['zip'];
	$pdf->City = $rs['city'];
	$pdf->Country = getString("TXT_COUNTRY_".$vat_country_code);
	$pdf->Address1 = $rs['address1'];
	$pdf->Address2 = $rs['address2'];
	$pdf->VatPercent = $rs['tax_percent'];
	$pdf->VatNumber = $vat_number;
	$pdf->VatCountryCode = $vat_country_code;
	$pdf->OrderDate = $rs['order_placed_date'];
	
	if($rs['payment_processor'] != "")
		$pdf->PaidHintProcessor = $rs['payment_processor'];
		
	$pdf->CI = Localization::get_currency_culture($rs['currency_code']);
	$pdf->Language = $user->getCulture()->ResolveToLanguage();
	
	$rsi = $shop->ExecuteSql("SELECT item_code, item_name, price, tax_percent, quantity
							 FROM va_orders_items
							 WHERE order_id = ?0
							 ORDER BY order_item_id", array($rs['order_id']));
	foreach( $rsi as $row)
	{
		$itemname = str_replace('?', '', $row['item_name']);
		$itemname = invoicePdfPreFormatText($itemname,$pdf->Language->Code,$user);
		$price = $rs['currency_rate'] * $row['price'];				
		$pdf->AddItem($itemname, $row['quantity'], $price);
	}
	
	$pdf->RenderInvoice();
	return writePdfToFile($pdf,sys_get_temp_dir()."/Invoice_$invoicenr.pdf");
}

function writePdfToFile(PdfDocument $pdf_doc, $filename)
{
	$pdf_doc->RenderToFile($filename);
	return $filename;
}

/**
 * Returns the width of the text
 *
 * @param string $text The textstring.
 * @param string $font The font of the text.
 * @param integer $fsize The size of the selected font.
 * @return integer Number of PDF units wide the text should be.
 */

function invoicePdfGetWidth($text, $font, $fsize)
{
	//make into a character array
	$charArray=array();
	$text = iconv('UTF-8', 'UTF-16BE//IGNORE', $text);
	for ($x=0;$x<strlen($text);$x++) {
		$charArray[]= (ord($text[$x++]) << 8) | ord($text[$x]);;
	}
	$lengths=$font->widthsForGlyphs($charArray);
	$fontGlyphWidth=array_sum($lengths);
	return 	$fontGlyphWidth/$font->getUnitsPerEm()*$fsize;
}

/**
 * Returns a given text in english, german, japanese or unaltered format
 *
 * @param string $text to be formatted
 * @param string $lang 'en', 'de', 'ja'
 * @return string the preformatted text
 */

function invoicePdfPreFormatText($text, $lang, $user)
{
	// new shop mechanism?
	if(strpos($text, "[en]") === false)
	{
		$ret = $user->getString($text);
		if($ret == $text.'?')
			$ret = $text;
		return $ret;
	}

	$start = strpos($text, "[$lang]")+4;
	$end = strpos($text, "[/$lang]");
	$length = $end - $start;
	if($length > 0)
		$result = substr($text, $start, $length);
	else
	{
		// try en as default
		$lang = "en";
		$start = strpos($text, "[$lang]")+4;
		$end = strpos($text, "[/$lang]");
		$length = $end - $start;
		if($length > 0)
			$result = substr($text, $start, $length);
		else
			$result = $text;
	}

//		log_debug("$text -> $result");
	return $result;
}

function invoicePdfExecute()
{
}

/**
 * Checks if a vat number is valid
 * 
 * @param type $vat_number VAT number to be checked
 * @return bool true if valid, else false
 */
function check_vat_number($vat_number)
{
	$vat = strtoupper(str_replace(array(" ", "-", ",", ".", "/", "\\"), "", $vat_number));
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
		try{
			$res = cache_get("vat_check_{$country_code}_{$vat}");
			if( !$res )
			{			
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
		}catch(Exception $ex){ WdfException::Log($ex); }
		return true; // ignore service exceptions
	}
	return false;
}
