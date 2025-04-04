<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

/**
 * Initializes the textdata module
 *
 * This module provides functions for text-file processing
 * @return void
 */
function textdata_init()
{
}

/**
 * Parses CSV data into an array.
 *
 * See http://www.php.net/manual/de/function.str-getcsv.php#95132
 * @param string $csv CSV data as string
 * @param string $delimiter CSV delimiter used
 * @param string $enclosure CSV enclosure string
 * @param string $escape CSV escape string
 * @param string|\Closure $callback Function to be called for each detected row
 * @return array An array with one entry per line, each beeing an array of fields
 */
function csv_to_array($csv, $delimiter = false, $enclosure = '"', $escape = '\\',$callback=false)
{
    $csv = str_replace("\r\n","\n",$csv);

    if( !$delimiter )
        $delimiter = csv_detect_delimiter(substr($csv,0,1024));

    $result = [];
    $csv    = trim($csv);
    $rows = preg_split('~\R~', $csv);
    $csv = "";
    $names = str_getcsv(array_shift($rows),$delimiter,$enclosure,$escape);
	$names = array_map('trim', $names);

    $line = "";
    foreach( $rows as $row )
	{
        $line .= $row;
        if( trim($line) )
		{
            $values = str_getcsv($line,$delimiter,$enclosure,$escape);
            if( !$values || count($values) != count($names) )
                continue;
            if( $callback )
            {
                if( false === $callback(array_combine($names,$values),$line) )
                    break;
            }
            else
                $result[] = array_combine($names,$values);
            $line = "";
        }
	}
	return $result;
}

/**
 * Extracts the header from a CSV data string
 *
 * This is usually the first line which contains the field names.
 * @param string $csv CSV data as string
 * @param string $delimiter CSV delimiter used
 * @param string $enclosure CSV enclosure string
 * @param string $escape CSV escape string
 * @param string $terminator CSV line terminator
 * @return array An array with one entry per field
 */
function csv_header($csv, $delimiter = ',', $enclosure = '"', $escape = '\\')
{
    $csv = str_replace("\r\n","\n",$csv);
	$rows = explode("\n",trim($csv));
    return str_getcsv(array_shift($rows),$delimiter,$enclosure,$escape);
}

/**
 * Tries to detect the CSV field delimiter
 *
 * This may be one of: ';'  ','  '|'  '\t'
 * @param string $csv CSV data as string
 * @param string $terminator CSV line terminator
 * @return string The delimiter that seems to match best
 */
function csv_detect_delimiter($csv)
{
    $csv = str_replace("\r\n","\n",$csv);
	$rows = explode("\n",trim($csv));
	$r = $rows[0];
	$counts = [];
	foreach( array(';',',','|',"\t") as $delim )
		$counts[count(explode($delim,$r))] = $delim;
	krsort($counts);
    return array_shift($counts);
}

/**
 * Removes the BOM header from a string.
 *
 * @see http://stackoverflow.com/a/15423899
 * @param string $text Text, potentially with leading BOM
 * @return string Clean string
 */
function remove_utf8_bom($text)
{
    $bom = pack('H*','EFBBBF');
    return preg_replace("/^$bom/", '', $text);
}