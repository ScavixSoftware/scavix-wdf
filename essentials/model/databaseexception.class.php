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
 
class DatabaseException extends Exception
{
	var $InnerException = null;

	function __construct($inner_exception,$paramcount = false)
	{
		$message = trim($inner_exception->getMessage());

//		if( is_integer($paramcount) )
//		{
//			if( is_array($inner_exception->params) )
//			{
//				if( $paramcount != count($inner_exception->params) )
//					$message = "Missing parameter in SQL statement.";
//			}
//			else
//			{
//				if( $paramcount > 0 )
//					$message = "Missing parameter in SQL statement.";
//			}
//		}

		$message .= "\n\nSQL: ".$inner_exception->sql;
		$message .= "\nParameter: ".var_export($inner_exception->params,true);
		$this->InnerException = $inner_exception;

		parent::__construct($message);
		$this->code = false;
	}
}

?>