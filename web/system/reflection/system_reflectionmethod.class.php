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
 
// ToDo: Try to touch files so that byte-cache (eAccelerator) is tricked out
// ToDo: Try to read files contents if byte-cache (eAccelerator) stripped comments out

class System_ReflectionMethod extends ReflectionMethod
{
	public function invokeArgs($object, array $argsarray)
	{
		try{
			return parent::invokeArgs($object, $argsarray);
		}catch(Exception $e){ log_debug("Checking for extender invokation"); }
		
		if( !is_null($object) && ($object instanceof Control || $object instanceof Api) )
		{
			foreach( $object->_extender as &$ex )
			{
				try{
					return $this->invokeArgs($ex, $argsarray);
				}catch(Exception $e){ log_debug("Checking other extenders"); }
			}
		}
		throw new Exception("Error calling ".$this->class."->".$this->name);
	}
}
?>
