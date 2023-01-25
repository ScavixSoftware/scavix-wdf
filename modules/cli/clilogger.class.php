<?php
/**
 * Scavix Web Development Framework
 *
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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\CLI;

use ScavixWDF\Logging\Logger;
use ScavixWDF\Wdf;

/**
 * Logs to stdpout.
 */
class CliLogger extends Logger
{
	public static $LOG_SEVERITY = true;
	
    private static $COLORS = 
        [
            'TRACE' => '0;37',
            'DEBUG' => '0;37',
            'INFO'  => '0;32',
            'WARN'  => '0;33',
            'ERROR' => '0;31',
            'FATAL' => '0;41',
        ];

    private function detectTerminal()
    {
        static $detected = '0';
        if( $detected === '0' )
            $detected = function_exists('stream_isatty') && stream_isatty(STDOUT);
        return $detected;
    }

	/**
	 * Writes a log entry.
	 * 
	 * @param string $severity Severity
	 * @param bool $log_trace Ignored, will log traces only for 'FATAL'
	 * @param_array mixed $a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8,$a9,$a10 Data to be logged
	 * @return void
	 */
    public function write($severity = false, $log_trace = false, $a1 = null, $a2 = null, $a3 = null, $a4 = null, $a5 = null, $a6 = null, $a7 = null, $a8 = null, $a9 = null, $a10 = null)
    {
        $entry = $this->prepare($severity, $log_trace, $a1, $a2, $a3, $a4, $a5, $a6, $a7, $a8, $a9, $a10);
        if (!$entry) return;
        $msg = $entry->toReadable();
        if (isset(self::$COLORS[$severity]) && $this->detectTerminal() )
            $msg = "\033[" . self::$COLORS[$severity] . "m{$msg}\033[0m";
        echo "{$msg}\n";
    }
}