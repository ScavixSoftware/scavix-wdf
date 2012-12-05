<?
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
 
/**
 * Building blocks of web pages.
 * When adding new Templates, make sure that the folders are added to
 * $CONFIG['class_path']['content'][] in /index.php
 */
class Template implements IRenderable
{
	var $vars = array();
	var $file = "";
	var $_translate = true;
	var $_storage_id;
	var $tpl_as_subtpl = false;
	var $_container_path = false;

    /**
	 * Constructs a new object.
	 * Will prevent constructor calls when objects are restored from session-storage.
	 */
	function __construct()
	{
		if( !unserializer_active() )
		{
			$args = func_get_args();
			system_call_user_func_array_byref($this, '__initialize' ,$args);
		}
	}

	/**
	 * 'real' constructor. See __construct.
	 * @param string $file Template file for this class. Usually '' (empty string)
	 */
	function __initialize($file = "")
	{
		$this->file = $file;

		if( !unserializer_active() )
			create_storage_id($this);
	}

	function __getpropertynames($inherited=true)
	{
		$res = System_Reflector::GetPropertyNames($inherited);
		return array_merge(array('vars','file','translate','_storage_id','tpl_as_subtpl','_container_path'),$res);
	}

	function __getminimalpropertynames()
	{
		return array('vars','file','translate','_storage_id','tpl_as_subtpl','_container_path');
	}

//	public function __clone()
//	{
//		$old = $this->_storage_id;
//		create_storage_id($this);
//        foreach( $this->vars as $name=>$val )
//        {
//        	if( $val == $old )
//        		$this->vars[$name] = $this->_storage_id;
//		}
//
//		if( in_object_storage($old) )
//			store_object($this);
//    }

	/**
	 * Set a variable for use in template file
	 * @param string $name Var can be use in template under this name
	 * @param mixed $value The value
	 */
	public function set($name, $value)
	{
		$this->vars[$name] = $value;
	}

	/**
	 * Sets all template variables
	 * @param array $vars Key=>Value pairs of variables
	 * @param bool $clear Overwrite the whole vars (defaults to false)
	 */
	function set_vars($vars, $clear = false)
	{
		if($clear) {
			$this->vars = $vars;
		}
		else {
			if(is_array($vars))
				$this->vars = array_merge($this->vars, $vars);
			else
				$this->vars[] = $vars;
		}
	}

	/**
	 * Renders the Template.
	 * Should be called from Base container objects like HtmlPage.
	 * @param bool $encode Deprecated! Do not use!
	 * @return string The rendered content
	 */
	function execute($encode=false,$is_root_node=false)
	{
		if( $is_root_node && !hook_already_fired(HOOK_PRE_RENDER) )
		{
			$this->SkipRendering = true;
			execute_hooks(HOOK_PRE_RENDER,array($this));
		}
		if( !$this->_container_path )
		{
			$bt = debug_backtrace();
			$i = 0;
			$btcnt = count($bt);
			while( $i<$btcnt && isset($bt[$i]['object']) && strtolower($bt[$i]['function']) == 'execute' )
				$i++;
			if( $i<$btcnt )
			{
				$this->_container_path = array();
				while( $i<$btcnt )
				{
					if( isset($bt[$i]['object']) && $bt[$i]['object'] instanceof IRenderable && isset($bt[$i]['object']->_container_path) )
					{
						if( is_array($bt[$i]['object']->_container_path) )
							$this->_container_path = array_merge($this->_container_path,$bt[$i]['object']->_container_path);
						else
							$this->_container_path[] = get_class($bt[$i]['object']);
					}
					$i++;
				}
				$this->_container_path = array_values(array_unique($this->_container_path));
			}
		}

        foreach( $this->vars as &$val )
			$this->AssignContainer($val,$this,$this->_container_path);

        $res = $this->do_the_execution();
		if( $encode )
			return sprintf('document.write(unescape("%s"));', rawurlencode($res));
		return $res;
	}

	/**
	 * Injects a container path.
	 * @param string $path_string A path seperated by '->'
	 */
	function SetContainerPath($path_string)
	{
		$this->_container_path = explode('->',$path_string);
	}

	/**
	 * Creates a 'tree' of parent containers for the template.
	 * @param object $obj The target object
	 * @param object $parent The parent object
	 * @param array $path OUT: The complete path
	 */
	function AssignContainer(&$obj, &$parent, $path = array())
	{

		if( $obj instanceof HtmlElement && $obj->AutoAjax )
			store_object($obj);
		elseif( $parent != null && $obj instanceof IRenderable &&
			isset($obj->_storage_id) && $obj->_storage_id != "" && in_object_storage($obj->_storage_id) )
		{
			$path[] = get_class($parent);
			$obj->_container_path = $path;
			store_object($obj);
		}

		if( isset($obj->vars) && is_array($obj->vars) )
		{
			foreach($obj->vars as &$val )
			{
				if( $val instanceof IRenderable )
				{
					$path[] = get_class($parent);
					$this->AssignContainer($val,$obj,$path);
				}
				elseif( is_array($val) )
				{
					foreach( $val as &$entry )
						$this->AssignContainer($entry,$parent,$path);
				}
			}
		}
		elseif( is_array($obj) )
		{
			foreach( $obj as &$entry )
				$this->AssignContainer($entry,$parent,$path);
		}
	}

	/**
	 * Inner redering method.
	 * @return string The rendered object
	 */
	function do_the_execution()
	{
//        if($this instanceof Template && $this->Tag == "option")
//            return;
//        TimeTrace("do_the_execution ".$this->_storage_id." ".get_class()." ".$this->Tag);
//		if( method_exists($this, "__beforeexecute") )
//			$this->__beforeexecute();

		if( !($this instanceof HtmlElement) || $this->_ownTemplate )
		{
			if( system_is_module_loaded("skins") && skinFileExists("trans.gif") )
				$this->set("trans","<img src='".skinFile("trans.gif")."' alt='' width='1px' height='1px'/>");
			else
				$this->set("trans","");
		}

		$tempvars = $this->execute_array($this->vars);

		foreach( $GLOBALS as $key=>&$val )
			$$key = $val;

		$buf = array();
		foreach( $tempvars as $key=>&$val )
		{
			if( isset($$key) )
				$buf[$key] = $$key;
			$$key = $val;
		}

		if( ($this instanceof HtmlPage) && strpos($this->file,"htmlpage.tpl.php") !== false )
		{
			$__template_file = __autoload__template($this,$this->SubTemplate?$this->SubTemplate:"");
			if( $__template_file === false )
				system_die("SubTemplate for class '".get_class($this)."' not found: ".$this->file);

			if( strpos($__template_file,"htmlpage.tpl.php") === false )
			{
				ob_start();
				require($__template_file);
				$sub_template_content = ob_get_contents();
				ob_end_clean();
			}
			$this->file = dirname(__FILE__)."/htmlpage.tpl.php";
		}

		$__template_file = __autoload__template($this,$this->file);
		if( $__template_file === false )
			system_die("Template for class '".get_class($this)."' not found: ".$this->file);

		ob_start();
		require($__template_file);
		$contents = ob_get_contents();
		ob_end_clean();

		foreach( $tempvars as $key=>&$val )
			unset($$key);
		foreach( $buf as $key=>&$val )
			$$key = $val;

//		if( $this->translate && function_exists("__translate") )
//			$contents = __translate($contents);

/* // Uncommented to save performance
		if( !($this instanceof HtmlPage || $this instanceof ControlState || $this instanceof Help) &&
			$this->_storage_id != "to_be_overwritten_by_unserializer" && 
			in_object_storage($this->_storage_id) && $this->_container_path === false )
		{
			$container_path = $this->_container_path?$this->_container_path:array();
			$class_to_check = get_class($this);
			$ref = System_Reflector::GetInstance($class_to_check);
			$rights = $ref->GetClassAttributes(array('right','public','inheritedright'),$container_path);

			$allow_access = false;//hasRight('all');
			foreach( $rights as &$r )
				$allow_access |= $r->Granted();

// ToDo: Find the bug and reactivate the following
//			if( !$allow_access )
//			{
//				trace("ViewAccess to (".get_class($this)."){$this->_storage_id} denied!\r\nContainer path is ".implode("->",$container_path));
//				return "ACCESS DENIED";
//			}
		}
*/
//        TimeTrace("x");
		return $contents;
	}

	protected function execute_array($array)
	{
		foreach( $array as $key=>&$val )
		{
			if( $val instanceof IRenderable )
				$array[$key] = $val->do_the_execution();
			elseif( is_array($val) )
				$array[$key] = $this->execute_array($val);
		}
		return $array;
	}
}

?>