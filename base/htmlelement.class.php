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
	'onkeyup' => array('applet', 'base', 'basefont', 'bdo', 'br', 'font', 'frame', 'frameset', 'head', 'html', 'iframe', 'isindex', 'param', 'script', 'style', 'title')
);
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
	'iframe' => array('align','frameborder','height','longdesc','marginwidth','marginheight','name','scrolling','src','width'),
	'img' => array('align','alt','border','height','hspace','ismap','longdesc','name','src','usemap','vspace','width'),
	'input' => array('accept','accesskey','align','alt','checked','disabled','ismap','maxlength','name','onblur','onchange','onfocus','onselect','readonly','size','src','tabindex','type','usemap','value'),
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
	'select' => array('disabled','multiple','name','onblur','onchange','onfocus','size','tabindex'),
	'style' => array('media','title','type'),
	'table' => array('align','border','bgcolor','cellpadding','cellspacing','frame','rules','summary','width'),
	'tbody' => array('align','char','charoff','valign'),
	'td' => array('abbr','align','axis','bgcolor','char','charoff','colspan','headers','height','nowrap','rowspan','scope','valign','width'),
	'textarea' => array('accesskey','cols','disabled','name','onblur','onchange','onfocus','onselect','readonly','rows','tabindex','value'),
	'tfoot' => array('align','char','charoff','valign'),
	'th' => array('abbr','align','axis','bgcolor','char','charoff','colspan','headers','height','nowrap','rowspan','scope','valign','width'),
	'thead' => array('align','char','charoff','valign'),
	'tr' => array('align','bgcolor','char','charoff','valign'),
	'ul' => array('compact','type')
);

$GLOBALS['html_close_tag_needed'] = array(
	'span', 'iframe'
);

/**
 * Base class for interactive webpage content like AJAX TextInputs
 * @deprecated
 */
class HtmlElement extends Template
{
	var $Tag = "";
	var $UserData;
	var $_ownTemplate = false;
	var $_queryUsed = false;
	var $_css = array();
	var $_attributes = array();
	var $_content = array();
	var $_script = array();
//	var $RelatedElements = array();
	// taken from input base
//	var $RadioGroupName = false;
	var $DataObjectName = false;
	var $FieldName = false;
	var $DataBindings = array();

	var $Bindings = array();
	var $Datasource = null;
	var $__label = false;
	var $IsRequired = false;
	var $ValueLength = null;
	var $AutoAjax = true;
	var $ControlIsValid = false;

	var $_logicVars = array();
	var $_logicVars_keys = array();

	function __initialize($tag = "", &$ds = null)
	{
		parent::__initialize();

		$this->_logicVars = array("_logicVars","Tag","_ownTemplate","_queryUsed","_css",
			"_attributes","_content","_script","DataBindings","Datasource","RelatedElements",
			"UserData","Bindings","DataObjectName","FieldName","Datasource","__label",
			"IsRequired","ValueLength","AutoAjax","ControlIsValid","_container_path");

		$this->Tag = strtolower($tag);
		
		$this->Datasource = $ds;

		$__template_file = __autoload__template($this,$this->file);
		$this->_ownTemplate = stripos($__template_file,"htmlelement.tpl.php") === false;
	}

//	function __sleep()
//	{
//		$res = array();
//		foreach( array_keys(get_object_vars($this)) as $key )
//			if( $key != '_logicVars' )
//				$res[] = $key;
//		return $res;
//	}

	function __get($varname)
	{
		if( strtolower($varname) == "id" )
		{
			$this->id = $this->_storage_id;
			//store_object($this);
			return $this->_storage_id;
		}
		return null;
	}

	function __set($varname,$value)
	{
//		if( $this->_ownTemplate )
//			system_die("Use of property assignment in an HtmlElement derivered class that has an own template.");
		$this->$varname = $value;

		if( strtolower($varname) == "id" )
			$this->_storage_id = $value;

		parent::set($varname,$value);
	}

//	static function __css()
//	{
//		$res = array();
//		$bt = debug_backtrace();
//		$bt = $bt[1];
//		$bt = $bt['object'];
//		$bt = $bt->class;
//
//		$ref_class = new ReflectionClass($bt);
//		if( $ref_class->hasMethod("autocomplete") )
//			$res[] = skinFile('autocomplete.css');
//
//		return $res;
//	}

	static function __js()
	{
		return array(jsFile('jquery.js'));
	}

//	function PreparePage(&$page)
//	{
//		//$page->addJs(jsFile('jquery.js'));
//		//log_debug("PreparePage ".get_class($this));
//		$ref_class = new ReflectionClass(get_class($this));
//		if( $ref_class->hasMethod("autocomplete") )
//		{
//			$page->addJs(jsFile('jquery/jquery.autocomplete.js'));
//			$page->addCss(skinFile('autocomplete.css'));
//		}
//	}

	function &CreateLabel($text_constant)
	{
		if( $this->IsRequired == true )
			$res = new Label($text_constant,true,true);
		else
			$res = new Label($text_constant,true);
		$this->__label = getString($text_constant);
		return $res;
	}

	function set($name, $value, $hide_warning = false)
	{
		if( !$hide_warning && !$this->_ownTemplate )
			system_die("Use of 'set' in an HtmlElement derivered class.
				Use 'css()...', 'attr()...', 'content()...' or assign properties directly.
				You may also create a template file for your class to override default processing.");

		parent::set($name, $value);
	}

	function css($name,$value)
	{
		$name = strtolower($name);
		$this->_css[$name] = $value;
	}

	/**
	 * DEPRECATED
	 * @param <type> $name
	 * @param <type> $value
	 * @param <type> $append
	 */
	function attr($name,$value,$append=false)
	{
		//log_debug(get_class().": $name = $value");
		$name = strtolower($name);
		if( $name == "style" )
			system_die("Illegal use of 'attr(...)' to set up 'style'. Use css(...) instead!");

		if( isset($this->_attr[$name]) )
		{
			if( $append )
			{
				if( !is_array($this->_attr[$name]) )
					$this->_attr[$name] = array($this->_attr[$name]);
				$this->_attr[$name][] = $value;
			}
			else
			{
				$this->_attr[$name] = $value;
			}
		}
		else
		{
			$this->_attr[$name] = $value;
		}
	}

	/**
	 * Adds content to the HtmlElement.
	 * @param mixed $content The content to be added
	 * @param bool $replace if true replaces the whole content.
	 */
	function content($content,$replace=false)
	{
		if( $replace )
			$this->_content = array($content);
		else
			$this->_content[] = $content;
	}

	/**
	 * Adds script code to the HtmlElement
	 * @param string $script The Code
	 * @param int|string $id An id for this script-entry
	 * @param string|array $depends_on Additional JS files this code depends on
	 * @param bool $return If true returns the generated JS code, else adds it to the internal buffer
	 * @return string Js Code (only if $return is true)
	 */
	function script($script,$id=false,$depends_on=false,$return=false)
	{
		if( is_array($script) )
			foreach( $script as &$s )
				$this->script($s);
		else
		{
			//log_debug($script,"ENCODE");
			if( $depends_on )
			{
				$loader = $script;
				if( !is_array($depends_on) )
					$depends_on = array($depends_on);

				$depends_on = array_reverse($depends_on);
				foreach( $depends_on as &$do )
				{
					$hash = preg_replace('/[^a-zA-Z0-9]/',"",$do);
					$loader = "ajax_script('$hash','$do','".jsEscape($loader)."');";
				}
				$script = $loader;

//				$hash = preg_replace('/[^a-zA-Z0-9]/',"",$depends_on);
//				//$varname = "top.{$hash}_loaded";
//				//$script = "if( !$varname ) $.getScript('$depends_on',function(){ $varname = true; $script }); else{ $script }";
//				$script = "ajax_script('$hash','$depends_on','".jsEscape($script)."');";
			}

			$script = "try{".$script."}catch(e){ } ";

			if( $return )
				return $script;
				
			if( !in_array($script,$this->_script) )
			{
				//log_debug("adding $script");
				if( $id )
					$this->_script[$id] = $script;
				else
					$this->_script[] = $script;
			}
		}
	}

//	function RelatedTo($role,&$relatedelement)
//	{
//		$this->RelatedElements[$role] = $relatedelement;
//	}

	/**
	 * Bind and relate elements.
	 * @param object $obj The Dataobject
	 * @param string $field_name Name of the Dataobjects property to bind to
	 * @param string $local_property Name of the local property (this object) to bind to
	 */
	function AssignDataobject(&$obj,$field_name,$local_property = 'value')
	{
		//log_debug($obj);
		$this->DataObjectName = $obj->_storage_id;
		$this->FieldName = $field_name;
		$this->$local_property = $obj->$field_name;

		$this->DataBindings[$local_property] = array($obj->_storage_id,$field_name);
	}

	/**
	 * Checks if there's a databinding to the local property
	 * @param string $local_property Name of the property to check
	 * @return bool true or false
	 */
	function HasDatabinding($local_property = 'value')
	{
		return isset($this->DataBindings[$local_property]);
	}

	/**
	 * Get the object binded to a property
	 * @param string $local_property Name of the property
	 * @return object The dataobject or null
	 */
	function &GetDatabindingObject($local_property = 'value')
	{
		$obj = restore_object($this->DataBindings[$local_property][0]);
		return $obj;
	}

	/**
	 * Binds this object to another HtmlElement object.
	 * The target will be accessible as GetRelatedObj($role)
	 * @param object $target The Target
	 * @param string $role The role
	 */
	function AddBinding(&$target,$role)
	{
		if( !in_array($target->id,$this->Bindings) )
		{
			$this->Bindings[$role] = $target->id;
			store_object($this);
			//store_object($target);
		}
	}

	/**
	 * Related two HtmlElement objects
	 * The object will be able to access each other via GetRelatedObj($role)
	 * @param object $partner The relation partner
	 * @param string $this_role Role for this object
	 * @param string $partner_role Role for partner object
	 */
	function Relate(&$partner,$this_role,$partner_role)
	{
		$this->AddBinding($partner,$partner_role);
		$partner->AddBinding($this,$this_role);
	}

	/**
	 * Returns the object related as role
	 * @param string $role_or_id Role
	 * @return object The related object
	 */
	function &GetRelatedObj($role_or_id)
	{
		$res = null;
		foreach( $this->Bindings as $r=>&$b)
		{
			if( $r == $role_or_id )
			{
				$res = restore_object($b);
				return $res;
			}
		}
		if( in_object_storage($role_or_id) )
			$res = restore_object($role_or_id);

		return $res;
	}

	function GetRelatedObjects()
	{
		$res = array();
		foreach( $this->Bindings as $r=>&$b)
			$res[] = restore_object($b);
		return $res;
	}

	/**
	 * Dummy implementation. Overwrite to use.
	 * Will be called when a related partners values has changed and this was reported via AJAX
	 * @param object $partner The partner that raised the event
	 * @param mixed $newvalue The value it has been changed to
	 */
	function PartnerChanged(&$partner,$newvalue=false)
	{
		return "";
	}

	/**
	 * Checks if this HtmlElement is required (in forms).
	 * @return string JS code to set this objects state (error,success,...)
	 */
	function Required()
	{
//		log_debug("{$this->id}->Required()");
		//trace($_REQUEST);
		if( !$this->DataObjectName )
			return "";
		
		$obj = restore_object($this->DataObjectName);
		$value = isset($_REQUEST['value'])?$_REQUEST['value']:"";
		$value = trim($value);
		
		if( $value == "" || $value == null )
		{
			if( $this->__label )
				$err = str_replace("{element}",$this->__label,getString("ERR_REQUIRED"));
			else
				$err = str_replace("{element}","",getString("ERR_REQUIRED"));
			return $this->SetError($err);
		}
		return $this->SetSuccess();
	}

	function CheckValueLength($value="")
	{
		$value = trim($value);

		if( $this->ValueLength == null )
			return $this->SetValue($value);

		$valuelength = $this->ValueLength;
		
		if( strlen($value) > $valuelength )
			$value = substr($value,0,$valuelength);
		
		return $this->SetValue($value);
	}

	/**
	 * Returns JS code to set this controls value (client side)
	 * @param mixed $new_value The new value
	 * @param string $local_property name of the property that should be changed to the value (defaults to 'value')
	 * @return string JS code to perform the action client-side
	 */
	function SetValue($new_value="",$local_property='value')
	{
//		if( !$this->DataObjectName )
//			return "Debug('No DataObjectName');";
//
//		if( !$this->IsAllowedAttribute("value") )
//			return "Debug('Value field not allowed');";

		$this->$local_property = $new_value;

		if( $this->HasDatabinding($local_property) )
		{
			$dataobj = $this->GetDatabindingObject($local_property);
			$fieldname = $this->DataBindings[$local_property][1];
//			if($dataobj->$fieldname == $this->$local_property)        // already the same value. no need to change it
//				return "Debug('No change');";
			$dataobj->$fieldname = $this->$local_property;
	//        log_debug($fieldname." = ".$this->value);
			store_object($dataobj);
//			log_debug("HtmlElement");
//			log_debug($dataobj);
//			store_object($this);
		}

		if( $local_property == "value" )
			return "$('#{$this->id}').val('".jsEscape($new_value)."');\n";
		return "";
	}
	
	private function _setState($state,$text)
	{
		if( $this->Tag != "Select" || $this->Tag != "StateSelect" )
			$res = $this->SetTitle($text);
			
		$hint = $this->GetRelatedObj('hint');
		if( $hint == null )
			return $res;

		if( !system_method_exists($hint,"SetState") )
			trace($hint);
		$res .= $hint->SetState($this,$state,$text);
		$res .= $hint->Update();
		return $res;
	}

	/**
	 * Sets this controls title client-side
	 * @param string $title the new title
	 * @return string JS code to perform client-side
	 */
	function SetTitle($title)
	{
		$this->title = $title;
		if( $title == "" )
			return "$('#{$this->id}').removeAttr('title').tooltip();";

		return "$('#{$this->id}').attr('title',unescape('".jsEscape($title)."'));";
	}

	function SetSuccess($text = "")
	{
		$this->ControlIsValid = true;
		return $this->_setState("success",$text);
	}
	function SetError($text)
	{
		$this->ControlIsValid = false;
		return $this->_setState("error",$text);
	}
	function SetWarning($text)
	{
		$this->ControlIsValid = false;
		return $this->_setState("warning",$text);
	}
	function IfUnchanged($callvalue,$script)
	{
		$script = "if( $('#{$this->id}').val() == '$callvalue' ){ ".$script." }";
		return $script;
	}

	function addClass($class)
	{
		if( !isset($this->class) )
			$this->class = $class;
		else
		{
			$mem = explode(" ",$this->class);
			if( !in_array($class,$mem) )
				$mem[] = $class;
			$this->class = implode(" ",$mem);
		}
	}

	function removeClass($class)
	{
		if( !isset($this->class) )
			return

		$mem = explode(" ",$this->class);
		$this->class = array();
		foreach( $mem as &$cl )
		{
			if( $cl != $class )
				$this->class[] = $cl;
		}
		$this->class = implode(" ",$this->class);
	}

	protected function _ajaxLink(&$object,$event,$cachable=false,$novalue=false)
	{
		$this->_queryUsed = true;
        if($event == "OnChange")
            $id = $object->id;
         else
            $id = $object->_storage_id;

        // jquery val() does not work with our checkboxes...
        if($object->Tag == "input" && $object->type == "checkbox") // get_class($object) == "CheckBox")
            $val = $novalue?'':",value:document.getElementById('".$object->id."').checked";
        else
            $val = $novalue?'':",value:$('#$object->id').val()";

		if( $cachable )
			$res = "ajax_cache('get','?',{load:'$id',event:'$event'$val});";
		else
			$res = "ajax_get('?',{load:'$id',object:'$object->id',event:'$event'$val});";
        //if(get_class($object) == "CheckBox" && $event == "OnChange")
        //    $res .= "alert(document.getElementById('".$object->id."').checked);";
		return $res;
	}

	/**
	 * Checks whether this control needs a closing tag (in HTML code)
	 * @return bool true if needed
	 */
	function CloseTagNeeded()
	{
		return in_array($this->Tag,$GLOBALS['html_close_tag_needed']);
	}

	/**
	 * Echoes attributes. Should be called from TPL only!
	 * @param string $name Attribute name
	 * @param string|array $value Attribute value
	 */
	function PrintAttribute($name,$value)
	{
		if( is_array($value) )
			foreach( $value as &$v )
				$this->PrintAttribute($name,$v);
		else
			echo " $name=\"".str_replace('"','\"',$value)."\"";
	}

	/**
	 * Echoes content. Should be called from TPL only!
	 * @param string|array $content The content to write
	 * @param bool $decode Deprecated
	 * @param bool $use_crlf Deprecated
	 */
	function PrintArray($content,$decode=false,$use_crlf=true)
	{
		if( !isset($content) )
			return;

		if( is_array($content) )
		{
			ksort($content);
			foreach( $content as &$c )
				$this->PrintArray($c,$decode);
		}
		elseif( $decode )
			echo ($content).($use_crlf?"\n":"");
		else
			echo $content.($use_crlf?"\n":"");
	}

	/**
	 * Checks if the given attribute is valid for a html element like this (depending on tag)
	 * @param string $attr The attribute to check
	 * @return bool true if valid
	 */
	function IsAllowedAttribute($attr)
	{
		if( isset($GLOBALS['html_attributes'][$this->Tag]) && in_array($attr,$GLOBALS['html_attributes'][$this->Tag]) )
			return true;
		elseif( isset($GLOBALS['html_universals'][strtolower($attr)]) && !in_array($this->Tag,$GLOBALS['html_universals'][strtolower($attr)]) )
			return true;
		//log_debug("not allowed: ".$this->Tag." ".$attr);
		return false;
	}

	/**
	 * Binds a method to an ajax event
	 * @param string $method Method to bind (like onchange, onmouseover)
	 * @param ReflectionClass $ref_class Object to use for reflection queries. OPTIONAL
	 */
	function BindEvent($method,$ref_class = false)
	{
		$code = $this->_ajaxLink($this,$method);
		$type = preg_replace('/^on/',"",strtolower($method));

		if( !$ref_class )
			$ref_class = new ReflectionClass(get_class($this));

		$action = 'bind';
		$comment = $ref_class->getMethod("$method")->getDocComment();
		if( preg_match_all('/\/\*\*.*Attributes:([^\s]*).*\*\*\//s', $comment, $comment) )
		{
			$comment = $comment[1];
			$comment = explode(",",strtolower($comment[0]));

			$action = in_array('once',$comment)?'one':$action;
			$noval = in_array('novalue',$comment);
			$cache = in_array('cache',$comment);
			$prefetch = in_array('prefetch',$comment);

			if( $cache || $noval )
				$code = $this->_ajaxLink($this,$method,$cache,$noval);

			if( $prefetch )
				$this->script($code);
		}

		if( $type == 'change' && $ref_class->hasMethod('autocomplete') )
		{
			$code = "if( $('.ac_results:visible').length == 0 ){ $code };";
			$this->script("$('#{$this->id}').$action('$type',function(e){ $code });");
			return;
		}

		$code = "$('#{$this->id}').$action('$type',function(e){ $code });";

		$this->script($code);
	}

	/**
	 * Returns JS code to load CSS files
	 * @param string|array $css The file(s) to load
	 */
	function LoadCss($css)
	{
		$css = skinFile($css);
		$this->script("if( $('link[href=$css]').length == 0 ) $('<link rel=\"stylesheet\" type=\"text/css\" href=\"$css\" media=\"screen\"/>').appendTo($('head'));");
	}

	/**
	 * Overwrites Template::do_the_execution()
	 * Special processing for AJAX activation.
	 * @return string The rendered content
	 */
	function do_the_execution()
	{
		// only process if there is no template file defined for the control
		if( !$this->_ownTemplate )
		{
			$attributes = array();
			$content = array();

			if( $this->AutoAjax )
			{
				$ref_class = new ReflectionClass(get_class($this));
                $armethods = $ref_class->getMethods();
				foreach( $armethods as &$method )
				{
					$m = strtolower($method->name);
					if( strtolower($method->getDeclaringClass()->getName()) == 'htmlelement' )
						continue;

					if( $method->isPublic() && $this->IsAllowedAttribute($m) )
					{
						//$this->$m = $this->_ajaxLink($this,$method->name);
						$this->BindEvent($method->name,$ref_class);
						store_object($this);
					}
					elseif( $m == "autocomplete" )
					{
						$comment = $method->getDocComment();
						if( preg_match_all('/\/\*\*.*Attributes:([^\s]*).*\*\*\//s', $comment, $comment) )
						{
							$comment = $comment[1];
							$comment = explode(",",strtolower($comment[0]));

							$multiple = in_array('multiple',$comment)?'true':'false';
						}
						else
							$multiple = 'false';

						$url = "?load={$this->id}&event=autocomplete";
						$opts = "{
							extraParams: {request_id:document.request_id},
							minChars: 3,
							autoFill: false,
							highlight: false,
							selectFirst: false,
							scroll: true,
							scrollHeight: 300,
							cacheLength:0,
							multiple: $multiple,
							parse: function(row){ return $.map(eval(row), function(row){ return { data: row, value: unescape(row.value), result: unescape(row.value) } }); },
							formatItem: function(row, i, max, term){ if( row.init ) eval(unescape(row.init)); return unescape(row.html); },
							formatResult: function(row){ return unescape(row.value); }
						}";
						//$resfunc = "function(e,row){ eval('row = ' + row); if( row.select ) eval(unescape(row.select)); }";
						$resfunc = "function(e,row){ $('#{$this->id}').change(); if( row.select ) eval(unescape(row.select)); }";
						//$this->script("$('#{$this->id}').autocomplete('$url',$opts).result($resfunc);");//.result($resfunc);");
						$resfunc = "$('#{$this->id}').autocomplete('$url',$opts).result($resfunc);";

						$this->LoadCss('autocomplete.css');
						//$this->script("if( $('link[href=$css]').length == 0 ) $('<link/>').attr({rel:'stylesheet',type:'text/css',href:'$css',media:'screen'}).appendTo($('head'));");
	//					$resfunc = "top.autocomplete_loaded = true; ".$resfunc;
	//					$this->script("if( !top.autocomplete_loaded ) $.getScript('".jsFile('jquery/jquery.autocomplete.js')."',function(){ $resfunc }); else $resfunc");

						$file = jsFile('jquery/jquery.autocomplete.js');
						$this->script($resfunc,false,$file);

//						if( $this instanceof HealthInsuranceContributionTypeSelect )
//							log_debug($this->_script);
						//$this->script("$('#{$this->id}').autocomplete('$url',$opts);");
					}
				}
			}

			// search for directly assigned properties and add them to attributes
			// or content depending on the globally defined rules
			$parent_props = get_class_vars("Template");
			$this_props = get_object_vars($this);
			if(!isset($this->_logicVars_keys))
				$this->_logicVars_keys = array_flip($this->_logicVars);
			foreach( $this_props as $key=>&$val )
			{
				// skin processing properties
				if( isset($this->_logicVars_keys[$key]) )
					continue;

				// skip parent class properties
				if( isset($parent_props[$key]) )
					continue;

				// is it a valid attribute?
				if( $this->IsAllowedAttribute($key) )
					$attributes[$key] = $val;
				// no? so it must be some content
				else
				{
//					if( $this->Tag == "input" )
//						log_debug("using $key as content");
					$content[] = $val;
				}
			}

			// append CSS to elements style attribute
			if( count($this->_css) > 0 )
			{
				$css = array();
				foreach( $this->_css as $key=>&$val )
					$css[] = "$key:$val;";
				$css = implode(" ",$css);
				$attributes['style'] = $css;
			}

			// prepare for execution by adding attributes and content to the vars array
			$this->vars = array();
			$this->vars['attr'] = array_merge($this->_attributes, $attributes);
			$this->vars['content'] = array_merge($this->_content, $content);
			$this->vars['script'] = $this->_script;
		}
//		log_debug("do_the_execution ".$this->_storage_id." ".$this->value);
		return parent::do_the_execution();
	}
}
?>