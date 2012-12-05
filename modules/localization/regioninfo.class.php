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
 
class RegionInfo
{
	var $Code;
	var $EnglishName;
	var $NativeName;
	var $KnownCultures;

	function  __construct($code="",$english="",$native="",$cultures="")
	{
		$this->Code = $code;
		$this->EnglishName = $english;
		$this->NativeName = $native;
		$this->KnownCultures = $cultures;
	}

	function DefaultCulture()
	{
		foreach( $this->KnownCultures as $kc )
		{
			$ci = internal_getCultureInfo($kc);
			if( $ci ) return $ci;
		}
		return false;
	}

	function GetCulture($culture)
	{
		if( $culture instanceof CultureInfo )
			$culture = $culture->Code;

		foreach( $this->KnownCultures as $kc )
		{
			$ci = internal_getCultureInfo($kc);
			if( strtolower($ci->Code) == strtolower($culture) )
				return $ci;

			if( $ci->IsParentOf($culture) || $ci->IsChildOf($culture) )
				return $ci;
		}
	}

	function ContainsCulture($culture)
	{
		if( $culture instanceof CultureInfo )
			$culture = $culture->Code;

		foreach( $this->KnownCultures as $kc )
		{
			if( strtolower($kc) == strtolower($culture) )
				return true;

			$ci = internal_getCultureInfo($kc);
			if( $ci->IsParentOf($culture) || $ci->IsChildOf($culture) )
				return true;
		}
		return false;
	}
}

?>
