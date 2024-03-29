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
namespace ScavixWDF\Reflection;

use Exception;
use ReflectionMethod;
use ScavixWDF\Base\Control;
use ScavixWDF\WdfException;

/**
 * Wraps ReflectionMethod class and overrides invokeArgs method to allow control extender pattern to work.
 * 
 */
class WdfReflectionMethod extends ReflectionMethod
{
	/**
	 * Overrides default invokeArgs method
	 * 
	 * See <ReflectionMethod::invokeArgs>
	 * Will additionally check all defined extenders and call the method there if present.
	 * @param object $object The object to invoke the method on. In case of static methods, you can pass null to this parameter
	 * @param array $argsarray The parameters to be passed to the function, as an array
	 * @return mixed Returns the method result. 
	 */
    #[\ReturnTypeWillChange]
	public function invokeArgs($object, array $argsarray)
	{
		try{
			return parent::invokeArgs($object, $argsarray);
		}catch(Exception $e){ }
		WdfException::Raise("Error invoking ".$this->class."->".$this->name);
	}
}
