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
class Template extends Renderable
{
	var $_data = array();
	var $file = "";
	
	function __getContentVars(){ return array_merge(parent::__getContentVars(),array('_data')); }

	static function Make($template_basename)
	{
		if( file_exists($template_basename) )
			$tpl_file = $template_basename;
		else
		{
			foreach( array_reverse(cfg_get('system','tpl_ext')) as $tpl_ext )
			{
				$tpl_file = __search_file_for_class($template_basename,$tpl_ext);
				if( $tpl_file )
					break;
			}
		}
		if( !$tpl_file )
			system_die("Template not found: $template_basename");
		$res = new Template($tpl_file);
		return $res;
	}
	
    /**
	 * Constructs a new object.
	 * Will prevent constructor calls when objects are restored from session-storage.
	 */
	function __construct()
	{
		if( !hook_already_fired(HOOK_PRE_RENDER) )
			register_hook(HOOK_PRE_RENDER,$this,"PreRender");
		elseif( !hook_already_fired(HOOK_POST_EXECUTE) )
			register_hook(HOOK_POST_EXECUTE,$this,"PreRender");
		
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
		{
			create_storage_id($this);
			$this->set('id',$this->_storage_id);
		}
	}

	function __getpropertynames($inherited=true)
	{
		$res = System_Reflector::GetPropertyNames($inherited);
		return array_merge(array('_data','file','_translate','_storage_id'),$res);
	}

	function __getminimalpropertynames()
	{
		return array('_data','file','_translate','_storage_id');
	}
	
	/**
	 * Will be executed on HOOK_PRE_RENDER.
	 * Prepares the template for output.
	 * @internal
	 */
	function PreRender($args=array())
	{
		if( count($args) > 0 && count($this->_script) > 0 )
		{
			$controller = $args[0];
			if( $controller instanceof HtmlPage )
			{
				$controller->addDocReady(implode("\n",$this->_script)."\n");
			}
		}
	}

	/**
	 * Set a variable for use in template file
	 * @param string $name Var can be use in template under this name
	 * @param mixed $value The value
	 */
	public function set($name, $value)
	{
		$this->_data[$name] = $value;
		if( $name == 'id' )
			$this->_storage_id = $value;
		return $this;
	}
	
	public function add2var($name, $value)
	{
		if( !isset($this->_data[$name]) )
			$this->_data[$name] = array($value);
		elseif( !is_array($this->_data[$name]) )
			$this->_data[$name] = array($this->_data[$name],$value);
		else
			$this->_data[$name][] = $value;
		return $this;
	}

	/**
	 * Sets all template variables
	 * @param array $vars Key=>Value pairs of variables
	 * @param bool $clear Overwrite the whole vars (defaults to false)
	 */
	function set_vars($vars, $clear = false)
	{
		if($clear) {
			$this->_data = $vars;
		}
		else {
			if(is_array($vars))
				$this->_data = array_merge($this->_data, $vars);
			else
				$this->_data[] = $vars;
		}
		return $this;
	}
	
	function get($name)
	{
		return isset($this->_data[$name])?$this->_data[$name]:null;
	}
	
	function get_vars()
	{
		return $this->_data;
	}
	
	/**
	 * Adds JavaScript-Code to the template.
	 */
	function script($scriptCode)
	{
		$scriptCode = str_replace("{self}", $this->_storage_id, $scriptCode);
		$k = "k".md5($scriptCode);
		if(!isset($this->_script[$k]))
			$this->_script[$k] = $scriptCode;
		return $this;
	}

	/**
	 * Renders the Template.
	 * Should be called from Base container objects like HtmlPage.
	 * @param bool $encode Deprecated! Do not use!
	 * @return string The rendered content
	 */
	function WdfRenderAsRoot()
	{
		if( !hook_already_fired(HOOK_PRE_RENDER) )
			execute_hooks(HOOK_PRE_RENDER,array($this));
        return $this->WdfRender();
	}

	/**
	 * Inner redering method.
	 * @return string The rendered object
	 */
	function WdfRender()
	{
		$tempvars = system_render_object_tree($this->get_vars());

		foreach( $GLOBALS as $key=>&$val )
			$$key = $val;

		$buf = array();
		foreach( $tempvars as $key=>&$val )
		{
			if( isset($$key) )
				$buf[$key] = $$key;
			$$key = $val;
		}

		if( ($this instanceof HtmlPage) && stripos($this->file,"htmlpage.tpl.php") !== false )
		{
			$__template_file = __autoload__template($this,$this->SubTemplate?$this->SubTemplate:"");
			if( $__template_file === false )
				system_die("SubTemplate for class '".get_class($this)."' not found: ".$this->file,$this->SubTemplate);

			if( stripos($__template_file,"htmlpage.tpl.php") === false )
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
        
		return $contents;
	}
}

?>