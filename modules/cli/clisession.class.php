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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2023 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Session;

use ScavixWDF\WdfException;

/**
 * PHP session handling.
 *
 * This is the default behaviour.
 */
class CliSession extends PhpSession
{
    function __construct()
    {
        global $CONFIG;
        $_SESSION = [];
        if ((session_name() != $CONFIG['session']['session_name']) || (session_id() == ""))
        {
            $name = $CONFIG['session']['session_name'];
            session_name($name);
        }
    }

	/**
	 * @implements <SessionBase::Sanitize>
	 */
	function Sanitize(){}

	/**
	 * @implements <SessionBase::KillAll>
	 */
	function KillAll()
	{
		global $CONFIG;
		unset($_SESSION[$CONFIG['session']['prefix']."session"]);
	}

    /**
	 * @implements <SessionBase::RegenerateId>
	 */
    function RegenerateId($destroy_old_session = false)
	{
        $ret = $this->GenerateSessionId();
        $old = session_id($ret);
        $this->store->Migrate($old,$ret);
		return $ret;
	}
}
