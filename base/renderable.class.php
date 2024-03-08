<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Base;

use ScavixWDF\Reflection\ResourceAttribute;
use ScavixWDF\WdfException;

/**
 * Base class for all HTML related stuff.
 *
 */
abstract class Renderable implements \JsonSerializable
{
	public $_translate = true;
	public $_storage_id;
	public $_parent = false;
	public $_content = [];
	public $_script = [];

    public static $SLIM_SERIALIZER = false;
    public static $SLIM_SERIALIZER_RUN = 0;
    private $serialized = false;

    /**
     * @internal Starts slim serialization mode (see <Renderable::jsonSerialize>)
     */
    public static function StartSlimSerialize()
    {
        if( self::$SLIM_SERIALIZER )
            return false;
        self::$SLIM_SERIALIZER = true;
        self::$SLIM_SERIALIZER_RUN++;
        return true;
    }
    /**
     * @internal Stops slim serialization mode (see <Renderable::jsonSerialize>)
     */
    public static function StopSlimSerialize()
    {
        self::$SLIM_SERIALIZER = false;
    }

    protected static $_renderingRoot = false;

    /**
     * @internal Gets the current rendering root object
     */
    public static function GetRenderingRoot()
    {
        return self::$_renderingRoot;
    }

    /**
     * @internal Checks if there's a current rendering root object
     */
    public static function HasRenderingRoot()
    {
        return self::$_renderingRoot instanceof Renderable;
    }

    protected static $_renderingStack = [];

    /**
     * @internal Adds an object to the rendering stack.
     */
    public static function PushRenderer(Renderable $r)
    {
        self::$_renderingStack[] = $r;
    }

    /**
     * @internal Removes an object from the rendering stack.
     */
    public static function PopRenderer()
    {
        array_pop(self::$_renderingStack);
    }

    /**
     * @internal Checks if the rendering stack is empty
     */
    public static function HasCurrentRenderer()
    {
        return count(self::$_renderingStack)>0;
    }

    /**
     * @internal Returns the current rendering object.
     */
    public static function GetCurrentRenderer()
    {
        return array_last(self::$_renderingStack);
    }

    /**
     * Returns all data needed for serializing this object into JSON.
     *
     * Note: This does _not_ return a string, but an object to be serialized.
     * @see <Renderable::StartSlimSerialize>
     * @return object|array If SlimSerialisation is active, returns an array, else returns $this
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        if( !self::$SLIM_SERIALIZER )
            return $this;

        if( $this->serialized === self::$SLIM_SERIALIZER_RUN )
            return ['ref_id'=>$this->_storage_id];
        $this->serialized = self::$SLIM_SERIALIZER_RUN;
        return ['class'=> get_class($this),'id'=>$this->_storage_id,'parent'=>$this->_parent,'content'=>isset($this->content)?$this->content:null];
    }

	/**
	 * @return string
	 */
    function __toString()
    {
        $p = is_object($this->_parent)?$this->_parent->_storage_id:'';
        $c = implode(",",array_map(function($o){ return ($o instanceof Renderable)?$o->_storage_id:"$o"; }, $this->_content));
        if( strlen($c)>20 )
            $c = substr($c,0,20)."[...]";
        $c = str_replace(["\r","\n","\t"],["\\r","\\n","\\t"], $c);
        return "{$this->_storage_id} [".get_class($this)."](parent=$p, content=$c)";
    }

    /**
     * @internal Dummy. Can be used in subclasses by overriding.
     */
    function PreRender($args=[]){}

	/**
	 * Renders this Renderable as controller.
	 *
	 * Extending classes must implement this (<Control>, <Template>).
	 * @return string The rendered object
	 */
	abstract function WdfRenderAsRoot();

	/**
	 * Renders this Renderable.
	 *
	 * Extending classes must implement this (<Control>, <Template>).
	 * @return string The rendered object
	 */
	abstract function WdfRender();

    /**
     * Renders this instance without dependencies direcly.
     *
     * @return string Rendered HTML content
     */
    function WdfRenderInline()
    {
        $this->_parent = Renderable::GetCurrentRenderer();
        $this->PreRender(array(current_controller(false)));

        Renderable::addLazyResources($this->__collectResourcesInternal($this));

        if( Renderable::HasCurrentRenderer() )
            Renderable::GetCurrentRenderer()->script($this->_script);

        return $this->WdfRender();
    }

    function __getContentVars(){ return array('_content'); }

	function __collectResources()
	{
		global $CONFIG;

		$min_js_file = isset($CONFIG['use_compiled_js'])?$CONFIG['use_compiled_js']:false;
		$min_css_file = isset($CONFIG['use_compiled_css'])?$CONFIG['use_compiled_css']:false;

		if( $min_js_file && $min_css_file )
			return array($min_css_file,$min_js_file);
		$res = $this->__collectResourcesInternal($this);
		if( !$min_js_file && !$min_css_file )
			return $res;


		$js = []; $css = [];
		foreach( $res as $r )
		{
			if( ends_with($r, '.css') || ends_with($r, '.less') )
			{
				if( !$min_css_file )
					$css[] = $r;
			}
			else
			{
				if( !$min_js_file )
					$js[] = $r;
			}
		}

		if( $min_js_file )
		{
			$css[] = $min_js_file;
			return $css;
		}

		$js[] = $min_css_file;
		return $js;
	}

 /**
  * @return array
  */
	protected function __collectResourcesInternal($template,&$static_stack = [])
	{
        // kind of dirty hack to allow overrides in subclasses
        if( $template instanceof Renderable && $template != $this )
            return $template->__collectResourcesInternal($template,$static_stack);

        $res = [];

		if( is_object($template) )
		{
			$classname = get_class($template);

			// first collect statics from the class definitions
            if( !isset($static_stack[$classname]) )
            {
                $static = ResourceAttribute::ResolveAll( ResourceAttribute::Collect($classname) );
                $res = array_merge($res,$static);
                $static_stack[$classname] = true;
            }

			if( $template instanceof Renderable )
			{
				// then check all contents and collect theis includes
				foreach( $template->__getContentVars() as $varname )
				{
					$sub = [];
					foreach( $template->$varname as $var )
					{
						if( is_object($var) || is_array($var) )
							$sub = array_merge($sub,$this->__collectResourcesInternal($var,$static_stack));
					}
					$res = array_merge($res,$sub);
				}

				// for Template class check the template file too
				if( $template instanceof Template )
				{
					$fnl = strtolower(array_first(explode(".",basename($template->file))));
                    if( !isset($static_stack[$fnl]) )
                    {
                        if( get_class_simple($template,true) != $fnl )
                        {
                            if( resourceExists("$fnl.css") )
                                $res[] = resFile("$fnl.css");
                            elseif( resourceExists("$fnl.less") )
                                $res[] = resFile("$fnl.less");
                            if( resourceExists("$fnl.js") )
                                $res[] = resFile("$fnl.js");
                        }
                    }
                    $static_stack[$fnl] = true;
				}

				// finally include the 'self' stuff (<classname>.js,...)
				// Note: these can be forced to be loaded in static if they require to be loaded before the contents resources
				$classname = get_class_simple($template);
				$parents = []; $cnl = strtolower($classname);
				do
				{
                    if( !isset($static_stack[$cnl]) )
                    {
                        if( resourceExists("$cnl.css") )
                            $parents[] = resFile("$cnl.css");
                        elseif( resourceExists("$cnl.less") )
                            $parents[] = resFile("$cnl.less");
                        if( resourceExists("$cnl.js") )
                            $parents[] = resFile("$cnl.js");
                        $static_stack[$cnl] = true;
                    }
					$classname = array_last(explode('\\',get_parent_class(fq_class_name($classname))));
    				$cnl = strtolower($classname);
				}
				while($classname != "");
				$res = array_merge($res,array_reverse($parents));
			}
		}
		elseif( is_array($template) )
		{
			foreach( $template as $var )
			{
				if( is_object($var)|| is_array($var) )
					$res = array_merge($res,$this->__collectResourcesInternal($var,$static_stack));
			}
		}
		return array_unique($res);
	}

    protected static $_lazy_resources = [];
    protected static function addLazyResources($res)
	{
        self::$_lazy_resources = array_merge(
            array_reverse(force_array($res)),
            self::$_lazy_resources
        );
	}

    public static function __getLazyResources()
    {
        return self::$_lazy_resources;
    }

    /**
     * @internal Prepares given resources to be processed
     */
    public static function CategorizeResources(...$args)
    {
        $res = [];
        foreach( $args as $a )
            $res = array_merge($res, force_array($a));

        $ret = [];
        foreach( $res as $url )
		{
            if( $url === '') continue;
            $key = get_requested_file($url);
            $ext = pathinfo(($key == '' ? $url : $key), PATHINFO_EXTENSION);
			$ret[] = compact('ext','key','url');
		}
        return $ret;
    }

	/**
	 * Adds JavaScript-Code to the <Renderable> object.
	 *
	 * @param string|array $scriptCode JS code to be added
	 * @return static
	 */
	function script($scriptCode)
	{
		if( is_array($scriptCode) )
			$scriptCode = implode(";",$scriptCode);

		$id = ($this instanceof Control)?$this->id:$this->_storage_id;
		$scriptCode = str_replace("{self}", $id, $scriptCode);
		$k = "k".md5($scriptCode);
		if(!isset($this->_script[$k]))
			$this->_script[$k] = $scriptCode;
		return $this;
	}

	/**
	 * Captures `$this` to the given `$variable`.
	 *
	 * This may me used to capture an instance from a method chain like this:
	 * <code php>
	 * TextInput::Make()->capture($tb)->appendTo($some_container)->par()->prepend($tb->CreateLabel('enter mail:'));
	 * </code>
	 * @param static $variable Variable to assign `$this` to
	 * @return static
	 */
	function capture(&$variable)
	{
		$variable = $this;
		return $this;
	}

	/**
	 * Adds content to the Renderable.
	 *
	 * Note that this will not return `$this` but the $content.
	 * This allows for method chaining like this:
	 * <code php>
	 * $this->content( new Control('div') )->css('border','1px solid red')->addClass('mydiv')->content('DIVs content');
	 * </code>
	 * @param mixed $content The content to be added
	 * @param bool $replace if true replaces the whole content.
	 * @return mixed The content added
	 */
	function &content($content,$replace=false)
	{
		if( $content instanceof Renderable )
        {
            if( $content->_parent instanceof Renderable )
                $content->_parent->remove($content);
			$content->_parent = $this;
        }
		if( !$replace && is_array($content) )
			foreach( $content as &$c )
				$this->content($c);
		elseif( $replace )
		{
			foreach( $this->_content as &$c )
				if( $c instanceof Renderable )
					$c->_parent = false;
			$this->_content = is_array($content)?$content:array($content);
		}
		else
			$this->_content[] = $content;
		return $this->_content[count($this->_content)-1];
	}

    function remove($content)
    {
        $buf = $this->_content;
        $this->_content = [];
        $a = ($content instanceof Renderable) ? $content->_storage_id : $content;
        foreach ($buf as $c)
        {
            $b = ($c instanceof Renderable) ? $c->_storage_id : $c;
            if ( $a !== $b )
                $this->_content[] = $c;
        }
        return $this;
    }

	/**
	 * Clears all contents.
	 *
	 * @return static
	 */
	function clearContent()
	{
		foreach( $this->_content as &$c )
			if( $c instanceof Renderable )
			{
				$c->_parent = false;
				release_hooks($c);
			}
		$this->_content = [];
		return $this;
	}

	/**
	 * Gets the number of contents.
	 *
	 * @return int Length of the contents array
	 */
	function length()
	{
		return count($this->_content);
	}

	/**
	 * Gets the content at index $index.
	 *
	 * @param int $index Zero based index of content to get
	 * @return mixed Content at index $index
	 */
	function get($index)
	{
		if( isset($this->_content[$index]) )
			return $this->_content[$index];
		WdfException::Raise("Index out of bounds: $index");
	}

	/**
	 * Returns the first content.
	 *
	 * Note that this does not behave like <Renderable::get>(0) because it wont throw an <Exception>
	 * when there's no content, but return a new empty <Control> object.
	 * @return Renderable First content or new empty <Control>
	 */
	function first()
	{
		if( isset($this->_content[0]) )
			return $this->_content[0];
		return log_return("Renderable::first() is empty",new Control());
	}

    /**
	 * Returns the last content.
	 *
	 * Note that this does not behave like <Renderable::get>(&lt;last_index&gt;) because it wont throw an <Exception>
	 * when there's no content at last_index, but return a new empty <Control> object.
	 * @return Renderable Last content or new empty <Control>
	 */
	function last()
	{
		if( count($this->_content)>0 )
			return $this->_content[count($this->_content)-1];
		return log_return("Renderable::last() is empty",new Control());
	}

	/**
	 * Returns this Renderables parent object.
	 *
	 * Note that this will throw an <Exception> when `$this` has not (yet) been added to another <Renderable>.
	 * @return Renderable Parent object
	 */
	function par()
	{
		if( !($this->_parent instanceof Renderable) )
			WdfException::Raise("Parent must be of type Renderable");
		return $this->_parent;
	}

	/**
	 * Return `$this` objects direct predecessor.
	 *
	 * Checks the parents content for `$this` and returns the object that was inserted directly before `$this`.
	 * Note that this method may throw an <Exception> when there's no parent or if `$this` is the first child.
	 * @return Renderable This objects predecessor in it's parent's content
	 */
	function prev()
	{
		$i = $this->par()->indexOf($this);
		return $this->par()->get($i-1);
	}

	/**
	 * Return `$this` objects direct successor.
	 *
	 * Checks the parents content for `$this` and returns the object that was inserted directly after `$this`.
	 * Note that this method may throw an <Exception> when there's no parent or if `$this` is the last child.
	 * @return Renderable This objects successor in it's parent's content
	 */
	function next()
	{
		$i = $this->par()->indexOf($this);
		return $this->par()->get($i+1);
	}

    /**
     * Returns the next Control of a given type when stepping up the object tree.
     *
     * @param string $classname Class to search for
     * @return mixed The closest object or false if not found
     */
    function closest($classname)
    {
        if( !$this->_parent )
            return false;
        if( is_subclass_of($this->_parent,fq_class_name($classname)) )
            return $this->_parent;
        if( get_class_simple($this->_parent,true) == strtolower($classname) )
            return $this->_parent;
        return $this->_parent->closest($classname);
    }

    /**
     * Check if this is part of another objects tree.
     *
     * @param Renderable $object Root object to test
     * @return bool True if this is child of object, else false
     */
    function isChildOf(Renderable $object)
    {
        if( !$this->_parent )
            return false;
        if( $this->_parent == $object )
            return true;
        return $this->_parent->isChildOf($object);
    }

	/**
	 * Appends content to this Renderable.
	 *
	 * This works exactly as <Renderable::content> but will return `$this` instead of the appended content.
	 * @param mixed $content The content to be appended
	 * @return static
	 */
	function append($content)
	{
		$this->content($content);
		return $this;
	}

	/**
	 * Prepends something to the contents of this Renderable.
	 *
	 * @param mixed $content Content to be prepended
	 * @return static
	 */
	function prepend($content)
	{
		return $this->insert($content,0);
	}

	/**
	 * Inserts something to the contents of this Renderable.
	 *
	 * @param mixed $content Content to be prepended
	 * @param int|static $index Zero base index where to insert OR Renderable to insert before
	 * @return static
	 */
	function insert($content,$index)
	{
		if( $index instanceof Renderable )
		{
			$index = $this->indexOf($index);
			if( $index < 0 )
				WdfException::Raise("Cannot insert because index not found");
		}
        if( count($this->_content) == 0 )
            return $this->content($content);

		$buf = $this->_content;
		$this->_content = [];
		$i = 0;
		foreach( $buf as $b )
		{
			if( $i++ == $index )
				$this->content($content);
			$this->_content[] = $b;
		}
		return $this;
	}

	/**
	 * Returns the zero based index of `$content`.
	 *
	 * Checks the content array for the given `$content` and returns it's index of found.
	 * Returns -1 if not found.
	 * @param mixed $content Content to search for
	 * @return int Zero based index or -1 if not found
	 */
	function indexOf($content)
	{
		$cnt = count($this->_content);
		for($i=0; $i<$cnt; $i++)
			if( $this->_content[$i] == $content )
				return $i;
		return -1;
	}

	/**
	 * Returns if there is an element in the content with the given instance type
	 *
	 * @param mixed $type Instance type to search for (via InstanceOf)
	 * @return bool True if an element of given instance was found
	 */
	function hasContentOfInstance($type)
	{
        foreach( get_object_vars($this) as $name => $c )
        {
            if( is_object($c) )
            {
                if( ($c instanceof $type) || is_subclass_of($c, $type) )
                   return true;
            }
            elseif(is_array($c))
            {
                foreach($c as $k => $obj)
                {
                    if(is_object($obj) && ($obj instanceof Renderable))
                    {
                        if( ($obj instanceof $type) || is_subclass_of($obj, $type) )
                            return true;
                    }
                }
            }
        }
        return false;
	}

	/**
	 * Wraps this Renderable into another one.
	 *
	 * Not words, just samples:
	 * <code php>
	 * $wrapper = new Control('div');
	 * $inner = new Control('span');
	 * $inner->content('INNER');
	 * $inner->wrap($wrapper)->content("I am below 'INNER'");
	 * // or
	 * $inner = new Control('span');
	 * $inner->content('INNER');
	 * $inner->wrap('div')->content("I am below 'INNER'");
	 * // or
	 * $inner = new Control('span');
	 * $inner->content('INNER');
	 * $inner->wrap(new Control('div'))->content("I am below 'INNER'");
	 * </code>
	 * @param mixed $tag_or_obj String or <Renderable>, see samples
	 * @return Renderable The (new) wrapping control
	 */
	function wrap($tag_or_obj='')
	{
		$res = ($tag_or_obj instanceof Renderable)?$tag_or_obj:new Control($tag_or_obj);
		$res->content($this);
		return $res;
	}

	/**
	 * Append this Renderable to another Renderable.
	 *
	 * @param mixed $target Object of type <Renderable>
	 * @return static
	 */
	function appendTo($target)
	{
		if( ($target instanceof Renderable) )
			$target->content($this);
		else
			WdfException::Raise("Target must be of type Renderable");
		return $this;
	}

    /**
	 * Prepends this Renderable to another Renderable.
	 *
	 * @param mixed $target Object of type <Renderable>
	 * @return static
	 */
	function prependTo($target)
	{
		if( ($target instanceof Renderable) )
			$target->prepend($this);
		else
			WdfException::Raise("Target must be of type Renderable");
		return $this;
	}

	/**
	 * Adds this Renderable before another Renderable.
	 *
	 * In fact it will be inserted before the other Renderable into the other Renderables parent.
	 * @param Renderable $target Object of type <Renderable>
	 * @return static
	 */
	function insertBefore($target)
	{
		if( ($target instanceof Renderable) )
			$target->par()->insert($this,$target);
		else
			WdfException::Raise("Target must be of type Renderable");
		return $this;
	}

	/**
	 * Inserts content after this element.
	 *
	 * @param mixed $content Content to be inserted
	 * @return static
	 */
	function after($content)
	{
		$this->par()->insert($content,$this->par()->indexOf($this)+1);
		return $this;
	}
}
