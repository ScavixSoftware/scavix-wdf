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
 
class WsObjectBase
{
	protected $_webService;
	protected $_instanceId;
	var $_storage_id;

	function __init(&$webservice)
	{
		if( !isset($GLOBALS['WsObjectBase_Instance_Counter'][get_class($this)]) )
			$GLOBALS['WsObjectBase_Instance_Counter'][get_class($this)] = 0;
		$this->_instanceId = $GLOBALS['WsObjectBase_Instance_Counter'][get_class($this)]++;

		$this->_webService =& $webservice;
		foreach( get_object_vars($this) as $prop=>$val)
			$this->__init_rec($this->$prop,$webservice);
	}

	function __prepareForCall()
	{
		$ref_class = new ReflectionClass(get_class($this));
		while( $ref_class->getName() != "WsObjectBase" )
		{
			//log_debug($ref_class->getName()."->__prepareForCall()");
			foreach( $ref_class->getProperties() as $prop )
			{
				//log_debug("  processing ".$prop->getName());
				$comment = $prop->getDocComment();
				if( !$comment )
					continue;

				$type = trim($comment,"/* ");
				switch( $type )
				{
					case 'dateTime':
						if( $prop->getValue($this) == "" )
						{
							//log_debug("setting dateTime field ".$prop->getName()." to NULL");
							$prop->setValue($this,null);
						}
//                        elseif( $prop->getValue($this) < 0 )
//                            $prop->setValue($this,654456);
//                        else
//                            log_debug("dateTime found: ".$prop->getValue($this));
						break;
					case 'double':
						$val = str_replace(",",".",$prop->getValue($this));
						//log_debug("setting double field ".$prop->getName()." to $val");
						$prop->setValue($this,$val);
						break;
				}
			}
			$ref_class = $ref_class->getParentClass();
			//log_debug("Parent = ".$ref_class->getName());
		}
	}

	private function __init_rec(&$data,&$ws)
	{
		if( $data instanceof WsObjectBase )
			$data->__init($ws);
		elseif( is_array($data) )
			foreach( $data as &$elem )
				$this->__init_rec($elem,$ws);
		elseif( is_object($data) )
			foreach( get_object_vars($data) as $prop=>$val )
				$this->__init_rec($data->$prop,$ws);
//		else
//			log_debug("-> ".get_class($this));
	}

	function __sleep()
	{
		$res = array();		
		foreach( get_object_vars($this) as $name=>$val)
		{
			switch( $name )
			{
				case '_webService':
				case '_instanceId':
					break;
				default:
					$res[] = $name;
					break;
			}
		}
		return $res;
	}

	function GetDataProperties()
	{
		$res = array();
		foreach( get_object_vars($this) as $name=>$val)
		{
			switch( $name )
			{
				case '_webService':
				case '_instanceId':
				case '_storage_id':
					break;
				default:
					$res[] = $name;
					break;
			}
		}
		return $res;
	}

	function __set($varname,$varvalue)
	{
		//log_debug(get_class($this)."::__set($varname,$varvalue)");
		$this->$varname = $varvalue;
	}

	function __toString()
	{
		return "Object of type ".get_class($this)." #".$this->_instanceId;
	}

	function IsContainer()
	{
		return count($this->GetDataProperties()) == 1;
	}

	function &GetOnlyProperty()
	{
		if( !$this->IsContainer() )
			throw new SoapFault("WsObjectBase","Trying to strip down a multi-property object.");

		$prop = $this->GetDataProperties();
		$prop = $prop[0];
		if( starts_with(get_class($this),"ArrayOf") && !is_array($this->$prop) && $this->$prop != null )
		{
			$res = array($this->$prop);
			return $res;
		}
		return $this->$prop;
	}

	function SetAllProperties($data)
	{
		foreach( $this->GetDataProperties() as $prop )
			if( isset($data[$prop]) )
				$this->$prop = $data[$prop];
	}
}

?>