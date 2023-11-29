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
namespace ScavixWDF\Logging;

/**
 * Generates a machine readable log.
 *
 * Use WdfTracer to read these files.
 * https://github.com/ScavixSoftware/WebFramework/tree/master/tools
 * In fact writes a JSON encoded object per line that contains full tracing information.
 */
class TraceLogger extends Logger
{
	/**
	 * Writes a log entry.
	 *
	 * @param string $severity Severity
	 * @param bool $log_trace Ignored, will log trace always
	 * @param mixed $args Data to be logged
	 * @return void
	 */
    public function write($severity=false,$log_trace=false,...$args)
	{
		$content = $this->prepare($severity,true,...$args);
		if( !$content ) return;
		$content = $content->serialize();
		@file_put_contents($this->filename, "$content\n", FILE_APPEND);
	}
}