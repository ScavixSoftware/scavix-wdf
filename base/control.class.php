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
 * These are unsiversal HTML attributes.
 * Each (1st dimension) array key represents an attribute and the value (array) contains
 * all tags it is allowed to be used in.
 */
$GLOBALS['html_universals'] = array(
	'class' => array('base', 'basefont', 'head', 'html', 'meta', 'param', 'script', 'style', 'title'),
	'id' => array('base', 'head', 'html', 'meta', 'script', 'style', 'title'),
	'style' => array('base', 'basefont', 'head', 'html', 'meta', 'param', 'script', 'style', 'title'),
	'title' => array('base', 'basefont', 'head', 'html', 'meta', 'param', 'script', 'style', 'title'),
	'dir' => array('applet', 'base', 'basefont', 'br', 'frame', 'frameset', 'hr', 'iframe', 'param', 'script'),
	'lang' => array('applet', 'base', 'basefont', 'br', 'frame', 'frameset', 'hr', 'iframe', 'meta', 'param', 'script'),
	'onclick' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'ondblclick' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'onmousedown' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'onmouseup' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'onmouseover' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'onmousemove' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'onmouseout' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'onkeypress' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'onkeydown' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'onkeyup' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title'),
	'contextmenu' => array()
);
/**
 * These are HTML tags.
 * Each (1st dimension) array key represents a tag and the value (array) contains
 * all attributes that are allowed to use with it.
 */
$GLOBALS['html_attributes'] = array(
	'a' => array('Bedeutung','Attribut','accesskey','charset','coords','href','hreflang','name','onblur','onfocus','rel','rev','shape','tabindex','target','type'),
	'applet' => array('align','alt','archive','code','codebase','height','hspace','name','object','vspace','width'),
	'area' => array('alt','accesskey','coords','href','nohref','onblur','onfocus','shape','tabindex','target'),
	'base' => array('href','target'),
	'basefont' => array('color','face','size'),
	'bdo' => array('dir'),
	'blockquote' => array('cite'),
	'body' => array('alink','background','bgcolor','link','onload','onunload','text','vlink'),
	'br' => array('clear'),
	'button' => array('accesskey','disabled','name','onblur','onfocus','tabindex','type','value'),
	'caption' => array('align'),
	'col' => array('align','char','charoff','span','valign','width'),
	'colgroup' => array('align','char','charoff','span','valign','width'),
	'del' => array('cite','datetime'),
	'dir' => array('compact'),
	'div' => array('align'),
	'dl' => array('compact'),
	'font' => array('color','face','size'),
	'form' => array('action','accept','accept-charset','enctype','method','name','onreset','onsubmit','target'),
	'frame' => array('frameborder','longdesc','marginwidth','marginheight','name','noresize','scrolling','src'),
	'frameset' => array('cols','onload','onunload','rows'),
	'h1' => array('align'),
	'h2' => array('align'),
	'h3' => array('align'),
	'h4' => array('align'),
	'h5' => array('align'),
	'h6' => array('align'),
	'head' => array('profile'),
	'hr' => array('align','noshade','size','width'),
	'html' => array('version'),
	'iframe' => array('align','frameborder','height','longdesc','marginwidth','marginheight','name','scrolling','src','width','type'),
	'img' => array('align','alt','border','height','hspace','ismap','longdesc','name','src','usemap','vspace','width','onload'),
	'input' => array('accept','accesskey','align','alt','checked','disabled','ismap','maxlength','name','onblur','onchange','onfocus','onselect','readonly','size','src','tabindex','type','usemap','value','placeholder'),
	'ins' => array('cite','datetime'),
	'isindex' => array('prompt'),
	'label' => array('accesskey','for','onblur','onfocus'),
	'legend' => array('accesskey','align'),
	'li' => array('type','value'),
	'link' => array('charset','href','hreflang','media','rel','rev','target','type'),
	'map' => array('name'),
	'menu' => array('compact'),
	'meta' => array('name','content','http-equiv','scheme'),
	'object' => array('align','archive','border','classid','codebase','codetype','data','declare','height','hspace','name','standby','tabindex','type','usemap','vspace','width'),
	'ol' => array('compact','start','type'),
	'optgroup' => array('disabled','label'),
	'option' => array('disabled','label','selected','value'),
	'p' => array('align'),
	'param' => array('id','name','value','valuetype','type'),
	'pre' => array('width'),
	'q' => array('cite'),
	'script' => array('charset','defer','event','language','for','src','type'),
	'select' => array('disabled','multiple','name','onblur','onchange','onfocus','size','tabindex','title'),
	'style' => array('media','title','type'),
	'table' => array('align','border','bgcolor','cellpadding','cellspacing','frame','rules','summary','width'),
	'tbody' => array('align','char','charoff','valign'),
	'td' => array('abbr','align','axis','bgcolor','class','char','charoff','colspan','headers','height','nowrap','rowspan','scope','valign','width'),
	'textarea' => array('accesskey','cols','disabled','name','onblur','onchange','onfocus','onselect','readonly','rows','tabindex','value'),
	'tfoot' => array('align','char','charoff','valign'),
	'th' => array('abbr','align','axis','bgcolor','char','charoff','colspan','headers','height','nowrap','rowspan','scope','valign','width'),
	'thead' => array('align','char','charoff','valign'),
	'tr' => array('align','bgcolor','char','charoff','valign'),
	'ul' => array('compact','type'),
	'menu' => array('type'),
	'menuitem' => array('label'),
	'audio' => array('controls','autoplay','loop','preload','src','onload'),
	'source' => array('src','type','onload'),
);

/**
 * Tags that need a closing tag wether there's content or not.
 */
$GLOBALS['html_close_tag_needed'] = array(
	'span','textarea','div','td','select','audio','iframe'
);
$GLOBALS['html_close_tag_needed'] = array_combine($GLOBALS['html_close_tag_needed'], $GLOBALS['html_close_tag_needed']);

/**
 * Tags that will NOT be echoed when there's no content
 */
$GLOBALS['html_skip_if_empty'] = array(
	'tbody','thead','tfoot','tr'
);
$GLOBALS['html_skip_if_empty'] = array_combine($GLOBALS['html_skip_if_empty'], $GLOBALS['html_skip_if_empty']);

/**
 * Base class for interactive webpage content like AJAX TextInputs
 */
class Control extends Renderable
{
	var $Tag = "";
	
	var $_css = array();
	var $_attributes = array();
	var $_data_attributes = array();
	
	var $_extender = array();
	var $_extenders_rendered = false;

	var $_skipRendering = false;
	
	function __getContentVars(){ return array_merge(parent::__getContentVars(),array('_extender')); }

	/**
	 * The one and only constructor for all subclasses.
	 * These must not implement a constructor but the __initialize method.
	 */
	function __construct()
	{
		if( !hook_already_fired(HOOK_PRE_RENDER) )
		{
			register_hook(HOOK_PRE_RENDER,$this,"PreRender");
		}
		else
			if( !hook_already_fired(HOOK_POST_EXECUTE) )
			{
				register_hook(HOOK_POST_EXECUTE,$this,"PreRender");
			}

		if( !unserializer_active() )
		{
			create_storage_id($this);
			$args = func_get_args();
			system_call_user_func_array_byref($this, '__initialize', $args);
		}
	}

	/**
	 * Override this method instead of writing a constructor.
	 * @param string $tag The HTML Tag of this control. Default ""
	 */
	function __initialize($tag = "", $auto_lowercase = true)
	{
		$this->Tag = $auto_lowercase?strtolower($tag):$tag;
        $class = $auto_lowercase?strtolower(get_class($this)):get_class($this);

        if( $class != $this->Tag && $class != "control" && !($this instanceof ApiContent) )
            $this->class = $class;
	}

	/**
	 * Magic method __get.
	 * @link http://ch2.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members Member overloading
	 */
	function __get($name)
	{
		// automatically set the id when it's required (ex:for ajax)
		if( $name == "id" && !isset($this->_attributes[$name]) )
			$this->_attributes[$name] = $this->_storage_id;

		if( isset($this->_attributes[$name]) )
			return $this->_attributes[$name];

		foreach( $this->_extender as &$ex)
		{
			if( property_exists($ex,$name) )
				return $ex->$name;
		}
		
		return null;
	}

	/**
	 * Magic method __set.
	 * @link http://ch2.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members Member overloading
	 */
	function __set($varname,$value)
	{
		if( !$this->IsAllowedAttribute($varname) )
		{
			foreach( $this->_extender as &$ex)
			{
				if( property_exists($ex,$varname) )
				{
					$ex->$varname = $value;
					return;
				}
			}
			WdfException::Raise("'$varname' is not an allowed attriute for a control of type '{$this->Tag}'");
		}
		$this->_attributes[$varname] = $value;

		if( strtolower($varname) == "id" )
			$this->_storage_id = $value;
	}
	
	/**
	 * Magic method __set.
	 * @link http://ch2.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.methods Method overloading
	 */
	public function __call($name, $arguments)
	{
        foreach( $this->_extender as &$ex)
		{
			if( system_method_exists($ex,$name) )
				return system_call_user_func_array_byref($ex, $name, $arguments);
		}
		WdfException::Raise("Call to undefined method '$name' on object of type '".get_class($this)."'");
    }

	/**
	 * Magic method __isset.
	 * @link http://ch2.php.net/manual/en/language.oop5.overloading.php#language.oop5.overloading.members Member overloading
	 */
	public function __isset($name)
	{
		if( property_exists($this,$name) )
			return true;

		if( array_key_exists($name,$this->_attributes) )
			return true;

        foreach( $this->_extender as &$ex)
		{
			if( property_exists($ex,$name) )
				return true;
		}
		return false;
    }

	/**
	 * Checks if this class implements a method.
	 * Needed because of the extender pattern.
	 * @param string $name name of the Method
	 * @return bool true|false
	 */
	public function __method_exists($name)
	{
		if( system_method_exists($this,$name) )
			return true;

        foreach( $this->_extender as &$ex)
		{
			if( system_method_exists($ex,$name) )
				return true;
		}
		return false;
    }

	/**
	 * Magic method __wakeup.
	 * @link http://ch2.php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep __sleep and __wakeup
	 */
	function __wakeup()
	{
		$this->_extenders_rendered = false;
	}
	
	/**
	 * Static creator method
	 * 
	 * This is cabable of creating derivered classes too:
	 * <code php>
	 * Control::Make('div')->content('Doh!');
	 * TextInput::Make()->css('width','300px');
	 * </code>
	 * @return object The created control
	 */
	public static function Make($tag=false)
    {
		$className = get_called_class();
		if( $tag === false )
			return new $className();
		return new $className($tag);
	}

	/**
	 * Adds JavaScript-Code to the control.
	 * Code will be echoed out with the control.
	 * You may specify dependencies in the form of JS files that must be loaded to
	 * make the control work.
	 * @param string|array $scriptCode The JavaScript code
	 * @return string The code with additional Script-Loading code
	 */
	function script($scriptCode)
	{
		$scriptCode = str_replace("{self}", $this->id, $scriptCode);
		$k = "k".md5($scriptCode);
		if(!isset($this->_script[$k]))
			$this->_script[$k] = $scriptCode;
		return $this;
	}

	/**
	 * Adds a CSS property to the control.
	 * If value is an integer (or numeric string like '12') 'px' will be added.
	 * @param string $name Name of the CSS property (like width, background-image,...)
	 * @param string $value Value of the CSS property
	 */
	function css($name,$value)
	{
		$name = strtolower($name);
		$this->_css[$name] = is_numeric($value)?$value.'px':$value;
		return $this;
	}

	/**
	 * Adds content to the Control.
	 * @param mixed $content The content to be added
	 * @param bool $replace if true replaces the whole content.
	 */
	function &content($content,$replace=false)
	{
		if( !$replace && is_array($content) )
			foreach( $content as &$c )
				$this->content($c);
		elseif( $replace )
			$this->_content = is_array($content)?$content:array($content);
		else
			$this->_content[] = $content;
		return $this->_content[count($this->_content)-1];
	}

	/**
	 * Checks whether this control needs a closing tag (in HTML code)
	 * @return bool true if needed
	 */
	protected function CloseTagNeeded()
	{
		return (isset($GLOBALS['html_close_tag_needed'][$this->Tag]) || (count($this->_content) > 0));
	}

	/**
	 * Checks if the given attribute is valid for a html element like this (depending on tag)
	 * @param string $attr The attribute to check
	 * @return bool true if valid
	 */
	protected function IsAllowedAttribute($attr)
	{
		$attr = strtolower($attr);
		$isattr = isset($GLOBALS['html_attributes'][$this->Tag]);
		if( $isattr && !isset($GLOBALS['html_attributes-keys'][$this->Tag]))
			$GLOBALS['html_attributes-keys'][$this->Tag] = array_flip($GLOBALS['html_attributes'][$this->Tag]);
		if($isattr && isset($GLOBALS['html_attributes-keys'][$this->Tag][$attr]) )
			return true;
		else
		{
			if( isset($GLOBALS['html_universals'][$attr]) )
			{
				if(!isset($GLOBALS['html_universals-keys'][$attr]))
					$GLOBALS['html_universals-keys'][$attr] = array_flip($GLOBALS['html_universals'][$attr]);
				if(!isset($GLOBALS['html_universals-keys'][$attr][$this->Tag]))
					return true;
			}
		}
		return false;
	}

	/**
	 * Will be executed on HOOK_PRE_RENDER.
	 * Prepares the control and all it's extenders for output.
	 * @internal
	 */
	function PreRender($args=array())
	{
		if( $this->_skipRendering )
			return;

		if( count($args) > 0 )
		{
			$controller = $args[0];
			if( $controller instanceof HtmlPage )
				$controller->addDocReady(implode("\n",$this->_script)."\n");
		}
	}

	/**
	 * Renders all the Extenders of this control.
	 * @internal
	 */
	function PreRenderExtender()
	{
		if( $this->_extenders_rendered )
			return;

		$this->_extenders_rendered = true;
		foreach( $this->_extender as &$ex )
			$ex->PreRender();
	}

	function addDocReady($js_code)
	{
		$this->script($js_code);
	}

	/**
	 * Renders this control.
	 * This is the 'outer' rendering startpoint when only this control is rendered.
	 * @internal
	 */
	function WdfRenderAsRoot()
	{
		if( !hook_already_fired(HOOK_PRE_RENDER) )
		{
			$this->_skipRendering = true;
			execute_hooks(HOOK_PRE_RENDER,array($this));
		}
		return $this->WdfRender();
	}

	/**
	 * Renders this control.
	 * This is the 'inner' rendering startpoint, called when this control is rendered
	 * as part of a parent container.
	 * @internal
	 */
	function WdfRender()
	{
		//log_debug("rendering ".$this->Tag);
		$this->PreRenderExtender();

		$attr = array();
		foreach( $this->_attributes as $name=>$value )
		{
			if($name{0} != "_")
				$attr[] = "$name=\"".str_replace("\"","\\\"",$value)."\"";
		}
		foreach( $this->_data_attributes as $name=>$value )
		{
			$attr[] = "data-$name='".str_replace("'","\\'",$value)."'";
		}
		
		$content = system_render_object_tree($this->_content);

		if( isset($GLOBALS['html_skip_if_empty'][$this->Tag]) )
			if( trim(implode(" ",$content)) == "" )
				return "";

		$css = array();
		foreach( $this->_css as $key=>$val )
			$css[] = "$key:$val;";

		$attr = count($attr)>0?" ".implode(" ",$attr):"";
		$css = count($css)>0?" style=\"".implode(" ",$css)."\"":"";
		$content = count($content)>0?implode("",$content):"";

		if( $this->Tag )
		{
			if( $content || $this->CloseTagNeeded() )
				$res = "<{$this->Tag}{$attr}{$css}>{$content}</{$this->Tag}>";
			else
				$res = "<{$this->Tag}{$attr}{$css}/>";
		}
		else
			$res = "{$content}";
			
		if( system_is_ajax_call() && count($this->_script)>0 )
			$res .= "<script> ".implode("\n",$this->_script)."</script>";
		return $res;
	}

	/**
	 * Extends this control with additional functionality.
	 * @param string|ControlExtender $extender The type of or an extender object itself
	 * Note: When $extender is a string containing the Extenders datatype, you will have
	 * to pass additional parameters that the extender class constructor requires!
	 */
	function Extend($extender)
	{
		if( is_string($extender) )
		{
			if( preg_match('/.*Extender$/',$extender) == 0 )
				$extender .= "Extender";

			if( array_key_exists($extender,$this->_extender) )
				return;

			$args = func_get_args();
			$args[0] = $this;
			$ref = System_Reflector::GetInstance($extender);
			$extender = $ref->CreateObject($args);
		}

		$key = get_class($extender);
		if( array_key_exists($key,$this->_extender) )
			return;
		$this->_extender[$key] = $extender;
	}

	/**
	 * Adds a value to the 'class' attribute.
	 * Note: you may pass multiple classes at once in a tring space separated: 'cls1 cls2'
	 * @return Control $this
	 */
	function addClass($class)
	{
		$c = explode(" ",$this->class);
		if( in_array($class,$c) )
			return;
		$c[] = $class;
		$this->class = trim(implode(" ",$c));
		return $this;
	}

	/**
	 * Removes a value from the 'class' attribute
	 * @return Control $this
	 */
	function removeClass($class)
	{
		$this->class = str_replace($class,"",$this->class);
		$this->class = str_replace("  "," ",trim($this->class));
		return $this;
	}
	
	/**
	 * Set a valud to a data-$name attribute.
	 * Those can be accessed in JS easily using jQuery.data method
	 * @return Control $this
	 */
	function setData($name,$value)
	{
		if( is_array($value) || is_object($value) )
			$this->_data_attributes[$name] = system_to_json($value);
		else
			$this->_data_attributes[$name]= $value;
		return $this;
	}
	
	/**
	 * Removes a data-$name attribute
	 * @return Control $this
	 */
	function removeData($name)
	{
		if( isset($this->_data_attributes[$name]) )
			unset($this->_data_attributes[$name]);
		return $this;
	}
	
	/**
	 * Append contents (see content method)
	 * @return Control $this
	 */
	function append($content)
	{
		$this->content($content);
		return $this;
	}
	
	/**
	 * Prepends something to the contents of this control
	 * @return Control $this
	 */
	function prepend($content)
	{
		$buf = $this->_content;
		$this->content($content,true);
		foreach( $buf as $b )
			$this->_content[] = $b;
		return $this;
	}
	
	/**
	 * Wraps this control into another one.
	 * @return Control The (new) wrapping control
	 */
	function wrap($tag_or_obj='')
	{
		$res = ($tag_or_obj instanceof Control)?$tag_or_obj:new Control($tag_or_obj);
		$res->content($this);
		return $res;
	}
	
	/**
	 * Append this control to another control
	 * @return Control $this
	 */
	function appendTo($target)
	{
		if( ($target instanceof Control) || ($target instanceof HtmlPage) )
			$target->content($this);
		else
			WdfException::Raise("Target must be of type Control or HtmlPage");
		return $this;
	}
}
