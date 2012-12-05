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
 * Select from a localized list of countries.
 */
class CountrySelect extends Select {
    
    private $language = false;
    private $selected_country = false;
 
    /**
     * Initialize the country select.
     * @param mixed $cid Optional HTML element id.
     * @param mixed $name Optional HTML element name.
     * @param mixed $language Optional language for the localization.
     * @param mixed $selected_country Optional ISO code of the selected country.
     */
    public function __initialize($cid=false, $name=false, $language=false,$selected_country=false)
    {
        $this->language = $language;
        $this->selected_country = $selected_country;
        parent::__initialize($cid, $name);
        
        if( $language )
		{
			$lang = Localization::getLanguageCulture($language);
			$regions = $lang->GetRegions(true);
		}
        else
            $regions = Localization::get_all_regions(true);
	
		if(!$selected_country)
			$selected_country = Localization::detectCulture()->Region->Code;
		if( count($regions)>0 )
		{
			$sorted = array();
			foreach($regions as $code)
				$sorted[$code] = array("name"=>getString("TXT_COUNTRY_".strtoupper($code)),"code",$code);
			uasort($sorted, "CountrySelect::compareCountryNames");

			foreach($sorted as $code=>$item)
			{
				$selected = strtolower($code)==( ($selected_country)?strtolower($selected_country):strtolower($language) );
				$this->AddOption($code, $item['name'], $selected);
			}
		}
    }
    
    public static function compareCountryNames($a, $b)
    {
		$chars = array('Ä'=>'A', 'Ö'=>'O', 'Ü'=>'U', 'ä'=>'a', 'ö'=>'o', 'ü'=>'u', 'ß'=>'ss');
		$a = strtr($a["name"], $chars);
		$b = strtr($b["name"], $chars);
		return strnatcasecmp($a, $b);
    }
}
 