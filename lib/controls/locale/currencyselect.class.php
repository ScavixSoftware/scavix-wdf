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

use ScavixWDF\Controls\Form\Select;
use ScavixWDF\Localization\Localization;

/**
 * Selector for currencies.
 * 
 * @attribute[Resource('locale_settings.js')]
 */
class CurrencySelect extends Select
{
	public $current_currency_code = false;
	
	/**
	 * @param string $current_currency_code Currently selected currency
	 * @param array $supported_currencies Array of supported currencies or false
	 */
	function __construct($current_currency_code=false, $supported_currencies = false)
	{
		parent::__construct();
		$this->script("Locale_Settings_Init();");
		$this->data('role', 'currency');
		
		$this->current_currency_code = $current_currency_code;
		if( $current_currency_code )
			$this->setValue($current_currency_code);
		
		if( !$supported_currencies )
			$supported_currencies = Localization::get_currency_codes();
		
		foreach($supported_currencies as $code)
		{
			$ci = Localization::get_currency_culture($code);
			$this->AddOption($code, "{$ci->CurrencyFormat->Code} ({$ci->CurrencyFormat->Symbol})");
		}
		store_object($this);
	}
}

