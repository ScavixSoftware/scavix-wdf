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
 
/**
 * @attribute[Resource('locale_settings.js')]
 */
class TimezoneSelect extends Select
{
	function __initialize($current_timezone=false)
	{
		parent::__initialize();
		$this->script("Locale_Settings_Init();");
		$this->setData('role', 'timezone');
		
		if( !$current_timezone )
			$current_timezone = Localization::getTimeZone();
		$this->SetCurrentValue($current_timezone);
		foreach(Localization::GetAllTimeZones() as $tz )
		{
			$this->AddOption($tz, str_replace("_"," ",$tz));
		}
	}
	
	static function __js()
	{
		return array(jsFile('locale_settings.js'));
	}
}

