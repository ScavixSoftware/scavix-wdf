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

use ScavixWDF\Base\Renderable;
use ScavixWDF\Controls\Form\Select;
use ScavixWDF\Localization\CultureInfo;
use ScavixWDF\Localization\Localization;

/**
 * Region selector.
 * 
 * @attribute[Resource('locale_settings.js')]
 */
class RegionSelect extends Select
{
	/**
	 * @param mixed $current_language_code Currently selected language
	 * @param type $current_region_code Currently selected region
	 */
	function __construct($current_language_code=false, $current_region_code=false)
	{
		parent::__construct();
		$this->script("Locale_Settings_Init();");
		$this->data('role', 'region');
		$this->data('controller', buildQuery($this->id));
		
		if( $current_language_code )
		{
			if( $current_language_code instanceof CultureInfo )
				$lang = $current_language_code->ResolveToLanguage();
			else
				$lang = Localization::getLanguageCulture($current_language_code);
			if( !$lang )
				$lang = Localization::detectCulture()->ResolveToLanguage();
			$regions = $lang->GetRegions(false);
			
			if( !$current_region_code )
				$current_region_code = $lang->DefaultRegion()->Code;
		}
		else
			$regions = Localization::get_all_regions(false);
		
		if( $current_region_code )
		{
			if( $current_region_code instanceof CultureInfo )
				$this->SetCurrentValue($current_region_code->DefaultRegion()->Code);
			else
				$this->SetCurrentValue($current_region_code);
		}
		else
			$this->AddOption(null, 'TXT_PLEASE_CHOOSE');
			
		if( count($regions)>0 )
		{
			$cc = current_controller(false);
			$translations_active = ($cc instanceof Renderable) && $cc->_translate;
			$sorted = [];
			foreach($regions as $reg)
			{
				if( !$reg ) continue;
				$code = $reg->Code;
				if( $translations_active )
					$sorted[$code] = array("name"=>_text(tds("TXT_COUNTRY_".strtoupper($code), $reg->EnglishName)), "code" => $code);
				else
					$sorted[$code] = array("name"=>$reg->EnglishName, "code" => $code);
			}
			uasort($sorted, __CLASS__."::compareCountryNames");

			foreach($sorted as $code=>$item)
				$this->AddOption($code, $item['name']);
		}
	}
	
	/**
	 * @internal Compares country names
	 */
	public static function compareCountryNames($a, $b)
    {
		$chars = array('Ä'=>'A', 'Ö'=>'O', 'Ü'=>'U', 'ä'=>'a', 'ö'=>'o', 'ü'=>'u', 'ß'=>'ss');
		$a = strtr($a["name"], $chars);
		$b = strtr($b["name"], $chars);
		return strnatcasecmp($a, $b);
    }
	
	/**
	 * Returns a list of option elements.
	 * 
	 * Called via AJAX to dynamically update the control.
	 * @attribute[RequestParam('language','string')]
	 * @param string $language Language code
	 * @return <AjaxResponse::Text> Html string with options
	 */
	public function ListOptions($language)
	{
		$lang = Localization::getLanguageCulture($language);
		if(!$lang)
			$lang = Localization::getLanguageCulture('en');
		$regions = $lang->GetRegions(true);
		$sorted = [];
		foreach($regions as $code)
			$sorted[$code] = array("name"=>getString("TXT_COUNTRY_".strtoupper($code)),"code" => $code);
		uasort($sorted, "RegionSelect::compareCountryNames");

		$res = [];
		foreach($sorted as $code=>$item)
			$res[] = "<option value='$code'>{$item['name']}</option>";
		return \ScavixWDF\Base\AjaxResponse::Text(implode("\n",$res));
	}
}