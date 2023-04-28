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
 * Base class for interactive webpage content like AJAX TextInputs.
 * 
 * @attribute[Resource('jquery.js')]
 */
class Control extends Renderable
{
    /**
     * Tags that need a closing tag wether there's content or not.
     */
    protected static $html_close_tag_needed = ['span'=>1,'textarea'=>1,'div'=>1,'td'=>1,'select'=>1,'audio'=>1,'iframe'=>1,'i'=>1,'video'=>1,'button'=>1,'form'=>1];

    /**
     * Tags that will NOT be echoed when there's no content
     */
    protected static $html_skip_if_empty = ['tbody'=>1,'thead'=>1,'tfoot'=>1,'tr'=>1];
    
    /**
     * These are unsiversal HTML attributes.
     * Each (1st dimension) array key represents an attribute and the value (array) contains
     * all tags it is allowed to be used in.
     */
    protected static $html_universals = [
        'class' => ['base', 'basefont', 'head', 'html', 'meta', 'param', 'script', 'style', 'title'],
        'id' => ['base', 'head', 'html', 'meta', 'script', 'style', 'title'],
        'style' => ['base', 'basefont', 'head', 'html', 'meta', 'param', 'script', 'style', 'title'],
        'title' => ['base', 'basefont', 'head', 'html', 'meta', 'param', 'script', 'style', 'title'],
        'dir' => ['applet', 'base', 'basefont', 'br', 'frame', 'frameset', 'hr', 'iframe', 'param', 'script'],
        'lang' => ['applet', 'base', 'basefont', 'br', 'frame', 'frameset', 'hr', 'iframe', 'meta', 'param', 'script'],
        'onclick' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'ondblclick' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'onmousedown' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'onmouseup' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'onmouseover' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'onmousemove' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'onmouseout' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'onkeypress' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'onkeydown' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'onkeyup' => ['applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'],
        'contextmenu' => []
       ];
    
    /**
     * These are HTML tags.
     * Each (1st dimension) array key represents a tag and the value (array) contains
     * all attributes that are allowed to use with it.
     */
    protected static $html_attributes = [
        'a' => ['accesskey','charset','coords','href','hreflang','name','onblur','onfocus','rel','rev','shape','tabindex','target','type'],
        'applet' => ['align','alt','archive','code','codebase','height','hspace','name','object','vspace','width'],
        'area' => ['alt','accesskey','coords','href','nohref','onblur','onfocus','shape','tabindex','target'],
        'base' => ['href','target'],
        'basefont' => ['color','face','size'],
        'bdo' => ['dir'],
        'blockquote' => ['cite'],
        'body' => ['alink','background','bgcolor','link','onload','onunload','text','vlink'],
        'br' => ['clear'],
        'button' => ['accesskey','disabled','name','onblur','onfocus','tabindex','type','value'],
        'caption' => ['align'],
        'col' => ['align','char','charoff','span','valign','width'],
        'colgroup' => ['align','char','charoff','span','valign','width'],
        'del' => ['cite','datetime'],
        'dir' => ['compact'],
        'div' => ['align'],
        'dl' => ['compact'],
        'font' => ['color','face','size'],
        'form' => ['action','accept','accept-charset','enctype','method','name','onreset','onsubmit','target'],
        'frame' => ['frameborder','longdesc','marginwidth','marginheight','name','noresize','scrolling','src'],
        'frameset' => ['cols','onload','onunload','rows'],
        'h1' => ['align'],
        'h2' => ['align'],
        'h3' => ['align'],
        'h4' => ['align'],
        'h5' => ['align'],
        'h6' => ['align'],
        'head' => ['profile'],
        'hr' => ['align','noshade','size','width'],
        'html' => ['version'],
        'iframe' => ['align','frameborder','height','longdesc','marginwidth','marginheight','name','scrolling','src','width','type'],
        'img' => ['align','alt','border','height','hspace','ismap','longdesc','name','src','usemap','vspace','width','onload'],
        'input' => ['accept','accesskey','align','alt','checked','disabled','ismap','maxlength','name','onblur','onchange','onfocus','onselect','readonly','size','src','tabindex','type','usemap','value','placeholder','autocomplete'],
        'ins' => ['cite','datetime'],
        'isindex' => ['prompt'],
        'label' => ['accesskey','for','onblur','onfocus'],
        'legend' => ['accesskey','align'],
        'li' => ['type','value'],
        'link' => ['charset','href','hreflang','media','rel','rev','target','type'],
        'map' => ['name'],
        'meta' => ['name','content','http-equiv','scheme'],
        'object' => ['align','archive','border','classid','codebase','codetype','data','declare','height','hspace','name','standby','tabindex','type','usemap','vspace','width'],
        'ol' => ['compact','start','type'],
        'optgroup' => ['disabled','label'],
        'option' => ['disabled','label','selected','value'],
        'p' => ['align'],
        'param' => ['id','name','value','valuetype','type'],
        'pre' => ['width'],
        'q' => ['cite'],
        'script' => ['charset','defer','event','language','for','src','type'],
        'select' => ['disabled','multiple','name','onblur','onchange','onfocus','size','tabindex','title','autocomplete'],
        'style' => ['media','title','type'],
        'table' => ['align','border','bgcolor','cellpadding','cellspacing','frame','rules','summary','width'],
        'tbody' => ['align','char','charoff','valign'],
        'td' => ['abbr','align','axis','bgcolor','class','char','charoff','colspan','headers','height','nowrap','rowspan','scope','valign','width'],
        'textarea' => ['accesskey','cols','disabled','name','onblur','onchange','onfocus','onselect','readonly','rows','tabindex','value'],
        'tfoot' => ['align','char','charoff','valign'],
        'th' => ['abbr','align','axis','bgcolor','char','charoff','colspan','headers','height','nowrap','rowspan','scope','valign','width'],
        'thead' => ['align','char','charoff','valign'],
        'tr' => ['align','bgcolor','char','charoff','valign'],
        'ul' => ['compact','type'],
        'menu' => ['compact','type'],
        'menuitem' => ['label'],
        'audio' => ['controls','autoplay','loop','preload','src','onload'],
        'video' => ['controls','autoplay','loop','preload','src','onload','width','height'],
        'source' => ['src','type','onload'],
        'track' => ['default','kind','label','src','srclang'],
        'svg' => ['width','height','viewbox','preserveaspectratio'],
        'rect' => ['x','y','width','height','id'],
       ];
    
    protected static $html_universals_keys = [];
    protected static $html_attributes_keys = [];
    
	public $Tag = "";
	
	public $_css = [];
	public $_attributes = [];
	public $_data_attributes = [];
	
	public $_skipRendering = false;
    
    function __toString()
    {
        $r = parent::__toString();
        return $r." ".$this->__renderStructure([]);
    }

	/**
	 * Constructs a Control
	 * 
	 * @param string $tag The HTML Tag of this control. Default ""
	 */
	function __construct($tag = "")
	{
		$this->__constructed();

        create_storage_id($this);
        $this->Tag = strtolower("$tag");
        $class = strtolower(get_class_simple($this));

        if( $class != $this->Tag && $class != "control" )
            $this->class = $class;
	}
    
    function __constructed()
    {
        if( !hook_already_fired(HOOK_PRE_RENDER) )
		{
			register_hook(HOOK_PRE_RENDER,$this,"PreRender");
		}
		elseif( !hook_already_fired(HOOK_POST_EXECUTE) )
        {
            register_hook(HOOK_POST_EXECUTE,$this,"PreRender");
        }
    }

	function __initialize($tag = "")
	{
		WdfException::Raise(get_class($this)," calling obsolete __initialize, please implement constructor!");
	}

	/**
	 * @internal Magic method __get.
	 * See [Member overloading](http://ch2.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members)
	 */
	function __get($name)
	{
		// automatically set the id when it's required (ex:for ajax)
		if( $name == "id" && !isset($this->_attributes[$name]) )
			$this->_attributes[$name] = $this->_storage_id;

		if( isset($this->_attributes[$name]) )
			return $this->_attributes[$name];
	
		return null;
	}

	/**
	 * @internal Magic method __set.
	 * See [Member overloading](http://ch2.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members)
	 */
	function __set($varname,$value)
	{
		if( !$this->IsAllowedAttribute($varname) )
			WdfException::Raise("'$varname' is not an allowed attriute for a control of type '{$this->Tag}'");

		$this->_attributes[$varname] = $value;

		if( strtolower("$varname") == "id" )
			$this->_storage_id = $value;
	}
	
	/**
	 * @internal Magic method __isset.
	 * See [Member overloading](http://ch2.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members)
	 */
	public function __isset($name)
	{
		if( property_exists($this,$name) )
			return true;

		if( array_key_exists($name,$this->_attributes) )
			return true;

		return false;
    }

	/**
	 * @internal Checks if this class implements a method.
	 * @param string $name name of the Method
	 * @return bool true|false
	 */
	public function __method_exists($name)
	{
		if( system_method_exists($this,$name) )
			return true;
		return false;
    }
	
	/**
	 * Static creator method
	 * 
	 * This is cabable of creating derivered classes too:
	 * <code php>
	 * Control::Make('div')->content('Doh!');
	 * TextInput::Make()->css('width','300px');
	 * </code>
	 * Arguments will be passed to constructor.
     * 
     * @param mixed ...$args Arguments as described
	 * @return static The created control
	 */
	public static function Make(...$args)
    {
		$className = get_called_class();
		$res = new $className(...$args);
		return $res;
	}

	/**
	 * Adds a CSS property to the control.
	 * 
	 * If value is an integer (or numeric string like '12') 'px' will be added.
	 * @param string $name Name of the CSS property (like width, background-image,...)
	 * @param string $value Value of the CSS property
	 * @return static
	 */
	function css($name,$value)
	{
		$name = strtolower("$name");
		$this->_css[$name] = ($name!='flex'&&is_numeric($value))?$value.'px':$value;
		return $this;
	}

	/**
	 * Checks whether this control needs a closing tag (in HTML code).
	 * 
	 * @return bool true if needed
	 */
	protected function CloseTagNeeded()
	{
		return (isset(Control::$html_close_tag_needed[$this->Tag]) || (count($this->_content) > 0));
	}

	/**
	 * Checks if the given attribute is valid for a html element like this (depending on tag).
	 * 
	 * @param string $attr The attribute to check
	 * @return bool true if valid
	 */
	protected function IsAllowedAttribute($attr)
	{
		$attr = strtolower("$attr");
        if( $attr == "is")
            return true;
		$isattr = isset(Control::$html_attributes[$this->Tag]);
		if( $isattr && !isset(Control::$html_attributes_keys[$this->Tag]))
			Control::$html_attributes_keys[$this->Tag] = array_flip(Control::$html_attributes[$this->Tag]);
		if($isattr && isset(Control::$html_attributes_keys[$this->Tag][$attr]) )
			return true;
		else
		{
			if( isset(Control::$html_universals[$attr]) )
			{
				if(!isset(Control::$html_universals_keys[$attr]))
					Control::$html_universals_keys[$attr] = array_flip(Control::$html_universals[$attr]);
				if(!isset(Control::$html_universals_keys[$attr][$this->Tag]))
					return true;
			}
		}
		return false;
	}

	/**
	 * Will be executed on HOOK_PRE_RENDER.
	 * 
	 * Adds this controls init code to rendering <HtmlPage> if root is of that type.
	 * @internal
	 */
	function PreRender($args=[])
	{
		if( $this->_skipRendering )
			return;
        
		if( count($args) > 0 && count($this->_script) > 0 )
		{
			$controller = $args[0];
            if( is_null($controller) || ($this != $controller && !$this->isChildOf($controller)) )
                return;
			if( method_exists($controller,'addDocReady') )
            {
				$controller->addDocReady(implode("\n",$this->_script));
                if( system_is_ajax_call() )
                    $this->_script = [];
            }
		}
	}

	/**
	 * @shortcut <Control::script>
	 */
	function addDocReady($js_code)
	{
		$this->script($js_code);
	}

	/**
	 * @override
	 */
	function WdfRenderAsRoot()
	{
        self::$_renderingRoot = $this;
		if( !hook_already_fired(HOOK_PRE_RENDER) )
		{
			$this->_skipRendering = true;
			execute_hooks(HOOK_PRE_RENDER, [$this]);
		}
		return $this->WdfRender();
	}
    
    function __renderStructure($content)
    {
		if( isset(Control::$html_skip_if_empty[$this->Tag]) )
			if( trim(implode(" ",$content)) == "" )
				return "";
        $content = count($content)>0?implode("",$content):"";
        if( !$this->Tag )
            return "$content";
        
        $attr = [];
		foreach( $this->_attributes as $name=>$value )
		{
            if( $name[0] == "_" || ($name == "class" && $value=="") )
                continue;
    		$attr[] = "$name=\"".str_replace("\"","&#34;","$value")."\"";
		}
		foreach( $this->_data_attributes as $name=>$value )
			$attr[] = "data-$name=\"".str_replace("\"","&#34;","$value")."\"";
//			$attr[] = "data-$name='".str_replace("'","\\'",$value)."'";
		
		$css = [];
		foreach( $this->_css as $key=>$val )
			$css[] = "$key:$val;";

		$attr = count($attr)>0?" ".implode(" ",$attr):"";
		$css = count($css)>0?" style=\"".implode(" ",$css)."\"":"";
        
        if( $content || $this->CloseTagNeeded() )
            return "<{$this->Tag}{$attr}{$css}>{$content}</{$this->Tag}>";
        else
            return "<{$this->Tag}{$attr}{$css}/>";
    }

	/**
	 * @override
	 */
	function WdfRender()
	{
		$content = system_render_object_tree($this->_content);
        $res = $this->__renderStructure($content);

		if( system_is_ajax_call() && count($this->_script)>0 )
        {
            $scriptCode = "$('#{$this->id}').on('remove',function(){ $('[data-wdf-remove-with=\"{$this->id}\"]').remove(); });";
            $k = "k".md5($scriptCode);
            if(!isset($this->_script[$k]))
                $this->_script[$k] = $scriptCode;
			$res .= "<script type='text/javascript' data-wdf-remove-with='{$this->id}'> ".implode("\n",$this->_script)."</script>";
        }
		return $res;
	}

	/**
	 * Adds a value to the 'class' attribute.
	 * 
	 * Note: you may pass multiple classes at once in a tring space separated: 'cls1 cls2'
	 * @param string $class CSS class(es)
	 * @return static
	 */
	function addClass($class)
	{
        $class = is_array($class)?$class:explode(" ",$class);
        $class = array_merge(explode(" ","{$this->class}"),$class);
		$this->class = trim(implode(" ",array_unique($class)));
		return $this;
	}

	/**
	 * Removes a value from the 'class' attribute.
	 * 
	 * @param string $class CSS class
	 * @return static
	 */
	function removeClass($class)
	{
		$this->class = str_replace($class,"",$this->class);
		$this->class = str_replace("  "," ",trim($this->class));
		return $this;
	}
	
	/**
	 * Set a value to a data-$name attribute.
	 * 
	 * Those can be accessed in JS easily using jQuery.data method
	 * @param string $name Data name
	 * @param mixed $value Data value (<system_to_json> will be used for arrays and objects) 
	 * @return static
     * @deprecated Use <Control::data> instead
	 */
	function setData($name,$value)
	{
        if( !is_string($name) && $this instanceof \ScavixWDF\Controls\ChartJS )
            WdfException::Raise("ChartJS::setData is obsolete, use setChartData instead");
        
        log_warn("Calling Control::setData is obsolete, use Control::data instead");
		if( is_array($value) || is_object($value) )
			$this->_data_attributes[$name] = system_to_json($value);
		else
			$this->_data_attributes[$name] = $value;
		return $this;
	}
	
	/**
	 * Removes a data-$name attribute.
	 * 
	 * @param string $name Data name
	 * @return static
	 */
	function removeData($name)
	{
		if( isset($this->_data_attributes[$name]) )
			unset($this->_data_attributes[$name]);
		return $this;
	}
    
    /**
	 * Removes attribute $name.
	 * 
	 * @param string $name Attribute name
	 * @return static
	 */
	function removeAttr($name)
	{
		if( isset($this->_attributes[$name]) )
			unset($this->_attributes[$name]);
		return $this;
	}
    
    /**
	 * data-* attribute Handling
	 * 
     * This work exaclty like <Control::attr> but with all the data-* attributes.
     * 
     * @param mixed ...$args Arguments as described
	 * @return static|mixed `$this`, a data-attribute value or an array of data-attributes
	 */
	function data(...$args)
	{
        $cnt = count($args);
		switch( $cnt )
		{
			case 0:
				return $this->_data_attributes;
			case 1: 
				$name = $args[0];
				if( is_array($name) )
				{
					foreach( $name as $n=>$v )
						$this->data($n,$v);
					return $this;
				}
				return isset($this->_data_attributes[$name])
                    ?$this->_data_attributes[$name]
                    :null;
			case 2: 
                list($name,$value) = $args;
                if( is_array($value) || is_object($value) )
                    $this->_data_attributes[$name] = system_to_json($value);
                else
                    $this->_data_attributes[$name] = $value;
				return $this;
		}
        WdfException::Raise("Control::data needs 0,1 or 2 parameters");
	}
	
	/**
	 * Attribute handling.
	 * 
	 * This method may be used in four different ways:
	 * 1. to get all attributes
	 * 2. to get one attribute
	 * 3. to set one attribute
	 * 4. to set many attributes
	 * 
	 * To achieve this pass different parameters into like this:
	 * 1. $c->attr() returns all attributes
	 * 2. $c->attr('name') returns the 'name' attributes value
	 * 3. $c->attr('name','mycontrol') sets the 'name' attribute values
	 * 4. $c->attr(['name'=>'myname','href'=>'my.domain']) sets 'name' and 'href' attribute values
	 * 
	 * Note: Will return `$this` in cases 3. and 4. (the set cases).
     * @param mixed ...$args Arguments as described
	 * @return static|mixed `$this`, an attribute value or an array of attribute values
	 */
	function attr(...$args)
	{
		$cnt = count($args);
		switch( $cnt )
		{
			case 0:
				return $this->_attributes;
			case 1: 
				$name = $args[0];
				if( is_array($name) )
				{
					foreach( $name as $n=>$v )
						$this->attr($n,$v);
					return $this;
				}
				return $this->$name;
			case 2: 
				$name = $args[0];
				$this->$name = $args[1];
				return $this;
		}
		WdfException::Raise("Control::attr needs 0,1 or 2 parameters");
	}
    
    /**
     * Sets the title attribute.
     * 
     * @param string $title Value for the title-atribute
     * @return static
     */
    function setTitle($title)
    {
        return $this->attr('title',$title);
    }
    
	/**
     * Overrides <Renderable::capture> to ensure a valid `id`.
     * 
	 * @see <Renderable::capture>
     * @param static $variable Variable to assign `$this` to
	 * @return static
	 */
    function capture(&$variable)
    {
        $this->_attributes['id'] = $this->_storage_id; // ensure there's an ID present
        return parent::capture($variable);
    }
}
