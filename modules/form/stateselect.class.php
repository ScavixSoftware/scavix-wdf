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
 * Select from a localized list of states (for a given country).
 */
class StateSelect extends Select {
    
    private $language = false;
    private $selected_state = false;
 
    /**
     * Initialize the state select.
     * @param string $country_code The ISO code of the selected country.
     * @param mixed $cid Optional HTML element id.
     * @param mixed $name Optional HTML element name.
     * @param mixed $language Optional language for the localization.
     * @param mixed $selected_state Optional ISO code of the selected country.
     */
    public function __initialize($country_code, $cid=false, $name=false, $language=false, $selected_state=false)
    {
        $this->language = $language;
        $this->selected_state = $selected_state;
        parent::__initialize($cid, $name);
        	
        $states = Localization::get_country_states($country_code);
		foreach($states as $code=>$state)
		{
			$selected = ($code == strtoupper($selected_state));
			$this->AddOption($code, $state, $selected);
		}
    }
   
}
 