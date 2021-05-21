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
namespace ScavixWDF\Base;

use ScavixWDF\WdfException;

/**
 * Building blocks of web pages.
 * 
 * Each template consist of a logic part and a layout part. The logic part is optional and can be handled
 * by this (base) class (see <Template::Make>).
 * @attribute[Resource('jquery.js')]
 */
class Template extends Renderable
{
	var $_data = array();
	var $file = "";
    
    function __toString()
    {
        $r = parent::__toString();
        return $r." ".$this->file;
    }
	
	function __getContentVars(){ return array_merge(parent::__getContentVars(),array('_data')); }

	/**
	 * Creates a template with layout only.
	 * 
	 * Sometimes you just want to separate parts of your layout without giving them some special logic.
	 * You may just store them as *.tpl.php files and create a template from them like this:
	 * <code php>
	 * // assuming template file is 'templates/my.tpl.php'
	 * $tpl = Template::Make('my');
	 * $tpl->set('myvar','I am just layout');
	 * <code>
	 * @param string $template_basename Name of the template
	 * @return Template The created template
	 */
	static function Make($template_basename=false)
	{
		$className = get_called_class();
		if( $template_basename && file_exists($template_basename) )
			$tpl_file = $template_basename;
		else
		{
			if( !$template_basename )
				$template_basename = $className;
			$tpl_file = false;
			foreach( array_reverse(cfg_get('system','tpl_ext')) as $tpl_ext )
			{
				$tpl_file = __search_file_for_class($template_basename,$tpl_ext);
				if( $tpl_file )
					break;
			}
		}
		if( !$tpl_file )
			WdfException::Raise("Template not found: $template_basename");
		
		$res = new $className($tpl_file);
		return $res;
	}
	
	/**
	 * Constructs a Template
	 */
	function __construct($file = "")
	{
		$this->__constructed();
		
		$this->file = $file;
        create_storage_id($this);
        $this->set('id',$this->_storage_id);
	}
    
    function __constructed()
    {
        if( !hook_already_fired(HOOK_PRE_RENDER) )
			register_hook(HOOK_PRE_RENDER,$this,"PreRender");
		elseif( !hook_already_fired(HOOK_POST_EXECUTE) )
			register_hook(HOOK_POST_EXECUTE,$this,"PreRender");
    }

	function __initialize($file = "")
	{
		WdfException::Raise(get_class($this)," calling obsolete __initialize, please implement constructor!");
	}
	
	/**
	 * @internal Magic method __get.
	 * See [Member overloading](http://ch2.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members)
	 */
	function __get($name)
	{
		if( isset($this->_data[$name]) )
			return $this->_data[$name];
		return null;
	}
	
	/**
	 * Will be executed on HOOK_PRE_RENDER.
	 * 
	 * Prepares the template for output.
	 * @internal
	 */
	function PreRender($args=array())
	{
		if( count($args) > 0 && count($this->_script) > 0 )
		{
			$controller = $args[0];
            if( is_null($controller) || ($this != $controller && !$this->isChildOf($controller)) )
                return;
			if( method_exists($controller,'addDocReady') )
				$controller->addDocReady(implode("\n",$this->_script)."\n");
		}
	}

	/**
	 * Set a variable for use in template file.
	 * 
	 * @param string $name Var can be use in template under this name
	 * @param mixed $value The value
	 * @return Template `$this`
	 */
	public function set($name, $value)
	{
		if( $value instanceof Renderable )
			$value->_parent = $this;
		$this->_data[$name] = $value;
		if( $name == 'id' )
			$this->_storage_id = $value;
		return $this;
	}
	
	/**
	 * Adds a value to an already defined var.
	 * 
	 * If $name is not already an array it will be converted to one.
	 * <code php>
	 * $tpl->set('a','one');
	 * $tpl->add2var('a','two');
	 * // $a is now array('one','two')
	 * $tpl->set('a','three');
	 * // $a is now 'three'
	 * $tpl->add2var('b','four');
	 * // $b is now array('four')
	 * </code>
	 * @param string $name Variable name
	 * @param mixed $value Value to add
	 * @return Template `$this`
	 */
	public function add2var($name, $value)
	{
		if( $value instanceof Renderable )
			$value->_parent = $this;
		if( !isset($this->_data[$name]) )
			$this->_data[$name] = array($value);
		elseif( !is_array($this->_data[$name]) )
			$this->_data[$name] = array($this->_data[$name],$value);
		else
			$this->_data[$name][] = $value;
		return $this;
	}

	/**
	 * Sets all template variables.
	 * 
	 * @param array $vars Key=>Value pairs of variables
	 * @param bool $clear Overwrite the whole vars (defaults to false)
	 * @return Template `$this`
	 */
	function set_vars($vars, $clear = false)
	{
		if( $clear )
        	$this->_data = [];
        
        foreach( force_array($vars) as $name=>$value )
            $this->set($name,$value);
        
		return $this;
	}
	
	/**
	 * Gets a variables value.
	 * 
	 * @param string $name Var name
	 * @return mixed Value of var
	 */
	function get($name)
	{
		return isset($this->_data[$name])?$this->_data[$name]:null;
	}
	
	/**
	 * Gets all variables.
	 * 
	 * @return array All variables
	 */
	function get_vars()
	{
		return $this->_data;
	}
	
	/**
	 * @override
	 */
	function WdfRenderAsRoot()
	{
        self::$_renderingRoot = $this;
		if( !hook_already_fired(HOOK_PRE_RENDER) )
			execute_hooks(HOOK_PRE_RENDER,array($this));
        return $this->WdfRender();
	}

    private function isHtmlPageTemplate($tpl)
    {
        $tpl = strtolower(str_replace("\\","/",$tpl));
        return $tpl == strtolower(str_replace("\\","/",WDF_HTMLPAGE_TEMPLATE));
    }
    
	/**
	 * @override
	 */
	function WdfRender()
	{
		$tempvars = system_render_object_tree($this->get_vars());
        $scriptcnt = count($this->_script);

        /* parameters are $file and $variables, keeping them anonymous to avoid conflicts with named variables */
        $render_in_context = function()
        {
            Renderable::PushRenderer($this);
            
            extract($GLOBALS);
            extract(func_get_arg(1));
            
            ob_start();
            require(func_get_arg(0));
            $result = ob_get_contents();
            ob_end_clean();
            
            Renderable::PopRenderer();
            return $result;
        };

		if( ($this instanceof HtmlPage) && $this->isHtmlPageTemplate($this->file) )
		{
			$__template_file = __autoload__template($this,$this->SubTemplate?$this->SubTemplate:"");
			if( $__template_file === false )
				WdfException::Raise("SubTemplate for class '".get_class($this)."' not found: ".$this->file,$this->SubTemplate);

			if( !$this->isHtmlPageTemplate($__template_file) )
			{
                $tempvars['sub_template_content'] = $render_in_context($__template_file,$tempvars);
                
                foreach( Renderable::CategorizeResources(Renderable::__getLazyResources()) as $r )
                {
                    if( $r['ext'] == 'css' || $r['ext'] == 'less' )
                        $this->addCss($r['url'],$r['key']);
                    else
                        $this->addjs($r['url'],$r['key']);
                }                
                $tempvars['meta'] = $this->meta;
                $tempvars['css'] = $this->css;
                $tempvars['js'] = $this->js;
			}
			$this->file = WDF_HTMLPAGE_TEMPLATE;
		}

		$__template_file = __autoload__template($this,$this->file);
		if( $__template_file === false )
			WdfException::Raise("Template for class '".get_class($this)."' not found: ".$this->file);

        $contents = $render_in_context($__template_file,$tempvars);
        
        $script = '';
		if( system_is_ajax_call() )
        {
            if( count($this->_script)>0 )
    			$script = implode("\n",$this->_script);
        }
        elseif( $scriptcnt < count($this->_script) ) 
            $script = implode("\n",array_slice($this->_script,$scriptcnt));
        
        if(trim($script) != '')
            $contents .= "<script>".$script."</script>";
        
		return $contents;
	}
}
