<?
/**
 * Scavix Web Development Framework
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
 
define("MSECPERDAY", 24 * 60 * 60);

/**
 * Parse a number that the user entered to a valid us format.
 * 
 * i.e. 1.000,99 -> 1000.99
 * @todo: different locales
 * @param mixed $number
 * @return float
 */
function parseUserinputNumber($number)
{
    if(strlen($number) - strpos($number, ".") <= 3)        // user entered . instead of ,
        $number = str_replace(".", ",", $number);
	$number = str_replace(".", "", $number);
	$number = str_replace(",", ".", $number);
	$number = $number + 0;  // make it float
	return $number;
}

/**
 * Format a number to display it to the user
 * @todo: different locales
 * @param mixed $number
 * @param int $digits the digits after the seperator
 * @param bool $keeptrailzeros keep the trailing 0
 * @return string
 */
function formatFloat($number, $digits = 2, $keeptrailzeros = false)
{
    $ret = number_format(floatval($number), $digits, ',', '.');
    if(strpos($ret, ",") && !$keeptrailzeros)
      $ret = ereg_replace("\,$", "", ereg_replace("(0*)$", "", $ret));

    return $ret;
}
