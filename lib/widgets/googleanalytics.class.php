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
namespace ScavixWDF\Widgets;

use ScavixWDF\Base\Template;

/**
 * Provides automatic GA tracking.
 * 
 */
class GoogleAnalytics extends Template
{
	/**
	 * @param string $account_code Your GA account
	 * @param string $js_varname Name of the JS variable
	 * @param bool $track_immediately If true calls _trackPageview() instantly
	 * @param string $track_prefix Prefix for tracked events
	 */
	function __construct($account_code,$js_varname="pageTracker",$track_immediately=true,$track_prefix="")
	{
		parent::__construct();

		$this->set("account_code",$account_code);
		$this->set("js_varname",$js_varname);
		$this->set("track_immediately",$track_immediately);
		$this->set("track_prefix",$track_prefix);

		$this->set("tracker",array());
	}
}
