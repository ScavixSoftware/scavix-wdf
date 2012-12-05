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
 
class WebServiceClient extends SoapClient
{
	protected $wsdl_path = false;
	protected $_currentRequest = "";
	protected $_debugNextCall = false;
	protected $_verbose = false;

	function __construct($wsdl_path,$cache_wsdl = false,$cert_file = false, $wrapper_classes_dir = false)
	{
//        TimeTrace("__construct");
		$type_mapper = new SoapTypeMapper();

		$cache_wsdl = $cache_wsdl?WSDL_CACHE_DISK:WSDL_CACHE_NONE;
		$cache_wsdl = WSDL_CACHE_NONE;

		$opts = array
		(
			"wsdl_cache_enabled"=>0,
			"wsdl_cache_ttl"=>0,
			"wsdl_cache_limit"=>0,
			"cache_wsdl"=>$cache_wsdl,
			"classmap"=>$type_mapper->GetClassMap($wrapper_classes_dir),
			"typemap"=>$type_mapper->GetTypeMap()
		);
		if( $cert_file )
		{
			$opts['local_cert'] = $cert_file;
			$opts['passphrase'] = "";
		}

		parent::__construct($wsdl_path,$opts);

		$this->wsdl_path = $wsdl_path;
	}

	private function GetBaseClass($wsdl,$classname)
	{
		$pattern = '/\<s:complexType name="'.$classname.'"\>.*\<\/s:complexType\>/sU';
		if( !preg_match_all($pattern, $wsdl, $array) )
			return "WsObjectBase";

		$wsdl = $array[0][0];
		//log_debug("searching wsdl for $classname :$wsdl");

		$pattern = '/\<s:complexType name="'.$classname.'"\>.*\<s:extension base="[^:]+:([^"]*)"\>.*\<\/s:complexType\>/sU';
		if( !preg_match_all($pattern, $wsdl, $array) )
			return "WsObjectBase";
		return $array[1][0];
	}

	function GenerateDataClasses($class_prefix = "", $target_dir = false)
	{
		if( !$target_dir )
			$target_dir = dirname(__FILE__)."/updated/";

		$wsdl = file_get_contents($this->wsdl_path);

		$classes = array("<?");
		foreach( $this->__getTypes() as $type )
		{
			//log_debug("processing type $type");
			$pattern = '/struct\s([^\s]*)\s\{/';
			if( preg_match_all($pattern, $type, $parts, PREG_SET_ORDER) == false )
				continue;

			$typename = $parts[0][1];

			$pattern = '/\n\s([^\s]+)\s([^;]+);/';
			if( preg_match_all($pattern, $type, $properties, PREG_SET_ORDER) > 0 )
			{
				$prop_ar = array();
				foreach( $properties as $prop )
					$prop_ar[] = "\n\t/** ".$prop[1]." **/\n\tvar $".$prop[2].";\n";
				$properties = implode("",$prop_ar);
			}
			else
				$properties = "";

			if( $typename == "" )
				continue;
			//	$classes[] = "class $typename extends WsObjectBase {".$properties."}";

			$target_file = $target_dir.strtolower($class_prefix.$typename).".class.php";
			$baseclass = $this->GetBaseClass($wsdl, $typename);
			file_put_contents($target_file,"<?\nclass $typename extends $baseclass\n{\n".$properties."\n}\n?>");
		}
		$classes[] = "?>";
		/*file_put_contents($target_file,implode("\n",$classes));*/
	}

	function GenerateServiceClass($soap_class_name,$target_path = false, $cert_file = false)
	{
		global $CONFIG;

		if( !$target_path )
			$target_path = dirname(__FILE__) . "/updated/";

		$target_file = $target_path . strtolower($soap_class_name) . ".class.php";

		//$additional_args = $cert_file?",false,'$cert_file'":"";
		$construction_args = array("'{$this->wsdl_path}'","false");
		$construction_args[] = $cert_file?"'$cert_file'":"false";
		$construction_args[] = "'.".makerelative($CONFIG['webservice']['base_path']."current/")."'";
		$construction_args = implode(",",$construction_args);

		$cls_tpl = "<?\n\nclass $soap_class_name extends WebServiceClient\n{\n\tfunction __construct()\n\t{\n\t\tparent::__construct({$construction_args});\n\t}\n\n{methods}\n}\n?>";
		$func_tpl = "\tfunction {func_name}({parameter})\n\t{\n\t\t{code}\n\t}";
		$prm_tpl = '\'{prm_name}\'=>${prm_name}';
		$code_tpl = "return $"."this->__call('{func_name}',\n\t\t\tarray('parameters'=>array({parameters}))\n\t\t);";
		$code_tpl_noprms = "return $"."this->__call('{func_name}', array());";

		$methods = array();
		foreach( $this->__getFunctions() as $func )
		{
			$pattern = '/([^\s]+)\s([^\(]+)\(([^\s]+)\s([^\)]+)\)/';
			if( !preg_match_all($pattern, $func, $parts, PREG_SET_ORDER) )
				continue;

			$parts = $parts[0];

			$func_name = $parts[2];
			$prm_type = $parts[3];

			//log_debug("creating dummy object of type '$prm_type'");
			if(in_array($prm_type, array("int", "string", "float")))
				continue;
			$prms = new $prm_type();
			$prms = $prms->GetDataProperties();

			if( count($prms) > 0 )
			{
				$inner_p = array();
				foreach( $prms as $p )
					$inner_p[] = str_replace("{prm_name}",$p,$prm_tpl);
				//$code = "return $"."this->__call('$func_name',\n\t\t\tarray('parameters'=>array(".implode(",",$inner_p)."))\n\t\t);";
				$code = str_replace("{func_name}",$func_name,$code_tpl);
				$code = str_replace("{parameters}",implode(",",$inner_p),$code);

				$prms = "$".implode(", $",$prms);
			}
			else
			{
				$prms = "";
				$code = str_replace("{func_name}",$func_name,$code_tpl_noprms);
			}

			$meth = str_replace("{func_name}","$func_name",$func_tpl);
			$meth = str_replace("{parameter}",$prms,$meth);
			$meth = str_replace("{code}",$code,$meth);
			$methods[] = $meth;
		}

		$class = str_replace("{methods}",implode("\n\n",array_unique($methods)),$cls_tpl);
		file_put_contents($target_file,$class);
	}

	function __call( $function_name, $arguments )
	{
		global $CONFIG;

		try
		{
            TimeTrace("__call 1 $function_name");
			if( is_array($arguments) && array_key_exists("parameters",$arguments) && class_exists($function_name) )
			{
				$arguments = $arguments['parameters'];
				//if( count($arguments) > 1 )
				{
					//log_debug("Creating parameter buffer $function_name");
					$args = new $function_name();
					foreach( $arguments as $key=>$lval )
					{
						if( is_object($lval) && $lval instanceof WsObjectBase )
							$lval->__prepareForCall();
						$args->$key = $lval;
					}
					//log_debug($args);
					$arguments = array($args);
				}
			}

			$start = time();
//			log_debug("Calling $function_name(".my_var_export($arguments).")");

			$default_socket_timeout = ini_get("default_socket_timeout");
			ini_set("default_socket_timeout",$CONFIG['webservice']['call_timeout']);
//            log_debug("pre call");
			$res = $this->__soapCall( $function_name, $arguments );			
			$bufferedresponse = $res;
			if( time() - $start > 2 )
				trace("Slow WebService call: $function_name");

			TimeTrace("__call 3");
            if( $res instanceof WsObjectBase )
				$res->__init($this);
//			else
//				system_die("Unwrapped object type: ".get_class($res));

//			if( $function_name == "LoadAbsencesByMDNrFromTo")
//				log_debug($res);

			while( $res instanceof WsObjectBase && $res->IsContainer() )
				$res = $res->GetOnlyProperty();

			$this->PrepareArrays($res);			

			ini_set("default_socket_timeout",$default_socket_timeout);
			$bufferedrequest = $this->_currentRequest;

            global $IS_DEVELOPSERVER;
            if($IS_DEVELOPSERVER)
            {
                if( $this instanceof EmployeeService && $function_name == "SaveEmployee" )
                {
                    $saved = $arguments[0]->employee;
                    $loaded = $this->LoadEmployee($saved->CompanyNumber,$saved->EmployeeNumber);
                    $properties = $saved->GetDataProperties();// get_object_vars($saved);
                    $diff = array();
                    foreach( $properties as $name )
                    {
                        if( $loaded->$name == null && $saved->$name == null )
                            continue;

                        if(in_array($name, array("BirthDate", "Period", "TaxCard", "CapitalSavingsPaymentID")))
                            continue;

                        if( $loaded->$name === null && $saved->$name !== null )
                        {
                            //$diff[$name] = array($saved->$name,$loaded->$name);
                        }
                        elseif( $loaded->$name !== null && $saved->$name === null )
                            $diff[$name] = array($saved->$name,$loaded->$name);
                        elseif( gettype($loaded->$name) == gettype($saved->$name) )
                        {
                            if( $loaded->$name != $saved->$name)
                                $diff[$name] = array($saved->$name,$loaded->$name);
                        }
                        else
                        {
                            $sval = $saved->$name;
                            $lval = $loaded->$name;
                            switch( gettype($lval) )
                            {
                                case "boolean":
                                    $sval = $sval=="true" || $sval=="1" || $sval=="on";
                                    $sval = $sval?"true":"false";
                                    $lval = $lval?"true":"false";
                                case "integer":
                                case "double":
                                    $lval = parseUserinputNumber($lval)."";
                                case "string":
                                    $lval = "".$lval;
                                    break;
                            }
                            if(trim($sval) != trim($lval))
                                $diff[$name] = array($sval,$lval);
                        }
                    }
                    if( count($diff) > 0 )
                    {
                        log_debug($bufferedrequest);
						log_debug($res);
//                        system_die("SaveEmployee fails partially: ".my_var_export($diff));
                    }
                }
            }
// DEPRECATED: 'To Dump each Request use this else' USE DebugNextCall AND Verbose methods
			if( $this->_debugNextCall )
			{
				if( !$this->_verbose )
					$this->_debugNextCall = true;
				log_debug($bufferedrequest,"WS_REQUEST");
				log_debug($bufferedresponse,"WS_RESPONSE");
				log_debug($res,"WS_RESULT");
			}

//			if( $function_name == "LoadCompany")
//				log_debug($res);
			return $res;
		}
		catch( ErrorException $ex)
		{
			ini_set("default_socket_timeout",$default_socket_timeout);
			system_die($ex->getMessage());
		}
		catch( SoapFault $ex )
		{
            $this->DumpRequest();
			system_die($ex->faultstring);
		}
		ini_set("default_socket_timeout",$default_socket_timeout);
		return false;
	}

	private function PrepareArrays($obj)
	{
		if( is_array($obj) )
		{
			foreach( $obj as &$item )
				$this->PrepareArrays($item);
			return;
		}
		if( !($obj instanceof WsObjectBase) )
		{
			//log_debug("No WsObjectBase");
			return;
		}

		$props = $obj->GetDataProperties();
		foreach( $props as $p )
		{
			$prop = $obj->$p;

			if( $prop == null )
				continue;
			if( !is_object($prop) )
				continue;
			if( !($prop instanceof WsObjectBase) )
				continue;
			if( !starts_with(get_class($prop),"ArrayOf") )
				continue;

			if( !$prop->IsContainer() )
				continue;

			$obj->$p = $prop->GetOnlyProperty();
			if( !is_array($obj->$p) )
			{
				if( $obj->$p == null )
					$obj->$p = array();
				else
					$obj->$p = array($obj->$p);
			}
		}

		foreach( $props as $p )
		{
			if( $obj->$p instanceof WsObjectBase )
                $this->PrepareArrays($obj->$p);
		}
	}

	public function __doRequest($request  , $location  , $action  , $version, $one_way = null)
	{
    	$this->_currentRequest = preg_replace("/<([^\/\?])/","\n<$1","".$request);
    	$this->_currentRequest = str_replace("\n\n","\n",$this->_currentRequest);
        
//    	$this->_currentRequest = preg_replace("/<([^>]+)>\n<\/([^>]+)>/","<$1></$2>","".$request);
//    	log_debug($this->_currentRequest);
		//$this->_currentRequest = $request;
    	return parent::__doRequest($this->_currentRequest,$location,$action,$version,$one_way);
    }

	function __toString()
	{
		return get_class($this);
	}

	function DumpRequest()
	{
		log_debug($this->_currentRequest);
	}

	function DebugNextCall()
	{
		$this->_debugNextCall = true;
	}

	function Verbose($enabled = true)
	{
		$this->_verbose = $enabled;
	}
}

?>