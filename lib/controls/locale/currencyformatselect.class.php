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
namespace ScavixWDF\Controls\Locale;

use ScavixWDF\Base\AjaxResponse;
use ScavixWDF\Controls\Form\Select;
use ScavixWDF\Localization\Localization;
 
/**
 * Selector for currency formats.
 * 
 * @attribute[Resource('locale_settings.js')]
 */
class CurrencyFormatSelect extends Select
{
	/**
	 * @param string $currency_code A valid currency code
	 * @param mixed $selected_format The currently selected format
	 */
	function __construct($currency_code, $selected_format=false)
	{
		parent::__construct();
		$this->script("Locale_Settings_Init();");
		$this->data('role', 'currenyformat');
		$this->data('controller', buildQuery($this->id));
		
		if( $selected_format )
			$this->setValue( $selected_format );
		$samples = $this->getCurrencySamples($currency_code,1234.56,true);
		foreach($samples as $code => $label)
			$this->AddOption($code, $label);
		store_object($this);
	}
	
	/**
	 * Returns a list of option elements.
	 * 
	 * Called via AJAX to dynamically update the control.
	 * @attribute[RequestParam('currency','string')]
	 * @param string $currency Valid currency string
	 * @return <AjaxResponse::Text> Html string with options
	 */
	public function ListOptions($currency)
	{
		$samples = $this->getCurrencySamples($currency,1234.56,true);
		$res = [];
		foreach($samples as $code=>$item)
			$res[] = "<option value='$code'>$item</option>";
		return AjaxResponse::Text(implode("\n",$res));
	}
	
	private function getCurrencySamples($currency_code, $sample_value, $unique_values = false)
	{
		$cultures = Localization::get_currency_culture_codes($currency_code);

		$res = [];
		foreach( $cultures as $culture_code )
		{
			$ci = Localization::getCultureInfo($culture_code);
			if( !$ci )
				continue;

			$res[$culture_code] = $ci->FormatCurrency($sample_value);
		}
		if( $unique_values )
			return array_unique($res);
		return $res;
	}
}

