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
 
class PercentFormat
{
	var $DecimalDigits;
	var $DecimalSeparator;
	var $GroupSeparator;
	var $PositiveFormat;
	var $NegativeFormat;

	function  __construct($digits="",$decsep="",$groupsep="",$percpos="",$percneg="")
	{
		$this->DecimalDigits = $digits;
		$this->DecimalSeparator = $decsep;
		$this->GroupSeparator = $groupsep;
		$this->PositiveFormat = $percpos;
		$this->NegativeFormat = $percneg;
	}

	function Format($number)
	{
		$val = number_format($number,$this->DecimalDigits,$this->DecimalSeparator,$this->GroupSeparator);
		if( strlen($this->GroupSeparator) > 0 );
		{
			$ord = uniord($this->GroupSeparator);
			$val = str_replace($this->GroupSeparator[0],"&#$ord;",$val);
		}
		if( $number >= 0 )
			str_replace("%v", $val, $this->PositiveFormat);
		return str_replace("%v", $val, $this->NegativeFormat);
	}
}
