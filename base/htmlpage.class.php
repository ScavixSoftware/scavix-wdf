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
 * Base class for all Html pages.
 * Will perform all rendering and collect js, css, meta and more.
 * 
 * @attribute[Resource('jquery.js')]
 * @attribute[Resource('htmlpage.js')]
 */
class HtmlPage extends Template implements ICallable
{
	var $meta = array();
	var $js = array();
	var $css = array();
	var $dialogs = array();
	var $docready = array();
	var $plaindocready = array();
	var $bodyEvents = array();

	var $requireCookies = false;
	var $requireJs = false; 

	/**
	 * Setting this to a filename (relative to class) will load it as subtemplate
	 * @var bool|string Templatename or false
	 */
	var $SubTemplate = false;

	function __initialize($title="", $body_class=false)
	{
		// this makes HtmlPage.tpl.php the 'one and only' template
		// for all derivered classes, unless they override it after
		// parent::__initialize with $this->file = X
		$file = str_replace(".class.php",".tpl.php",__FILE__);
		global $CONFIG;
		parent::__initialize($file);

		if( isset($CONFIG['htmlpage']['cookies_required']) )
			$this->requireCookies = $CONFIG['htmlpage']['cookies_required'];

		if( isset($CONFIG['htmlpage']['js_required']) )
			$this->requireJs = $CONFIG['htmlpage']['js_required'];

		if( $this->requireCookies )
		{
			if(!isset($_COOKIE["cookietest"]) && (isset($CONFIG['session']['session_name']) && !isset($_COOKIE[$CONFIG['session']['session_name']])))
			{
				if( !Args::get('redirected',false) )
				{
					$query_string = (isset($_SERVER["QUERY_STRING"]) && $_SERVER["QUERY_STRING"] != "" ) ? "?".$_SERVER["QUERY_STRING"]."&redirected=1" : '?redirected=1';
					$redir_uri  = urlScheme(true).$_SERVER['HTTP_HOST'];
					$redir_uri .= isset($_SERVER["QUERY_STRING"])?str_replace($_SERVER["QUERY_STRING"],"",$_SERVER['REQUEST_URI']):$_SERVER['REQUEST_URI'];
					setcookie("cookietest", "test", time() + 3600, "/", null, isSSL(), true);

					// remove trailing ? if present
					$redir_uri = preg_replace('/\?$/',"",$redir_uri);

					header('location: '.  $redir_uri.$query_string);
					exit;
				}
				else
				{
					// after reload, check if the cookie is still there:
					execute_hooks(HOOK_COOKIES_REQUIRED);
				}
			}
		}

		$this->set("title",$title);
		$this->set("meta",$this->meta);
		$this->set("js",$this->js);
		$this->set("css",$this->css);
		$this->set("content",array());
		$this->set("docready",$this->docready);
		$this->set("plaindocready",$this->plaindocready);
		$this->set("bodyEvents",$this->bodyEvents);
		$this->set("requireJs",$this->requireJs);
		
		if( $body_class )
			$this->set("bodyClass",$body_class);

		if( resourceExists("favicon.ico") )
			$this->set("favicon", resFile("favicon.ico"));
	}

	function WdfRender()
	{
		global $CONFIG;

		// moved here to allow derivered classes to set isrtl based on another CI
		if( !$this->get('isrtl') && system_is_module_loaded('localization') )
		{
			$ci = Localization::detectCulture();
			if( $ci->IsRTL )
				$this->set("isrtl", " dir='rtl'");
		}
		
		$res = $this->__collectResources();
		log_debug($res);
		$this->js = array_reverse($this->js,true);
		foreach( array_reverse($res) as $r )
		{
			if( ends_with($r, '.css') )
				$this->addCss($r);
			else
				$this->addjs($r);
		}
		$this->js = array_reverse($this->js,true);
		
		$this->set("css",$this->css);
		$this->set("js",$this->js);
		$this->set("meta",$this->meta);
		
		return parent::WdfRender();
	}

	/**
	 * Adds a handler to the body tag.
	 * Examples:
	 *   addBodyEventHandler('load','myjavascriptloadhandler();');
	 *   addBodyEventHandler('load',"alert('Page loaded');");
	 * @param string $name Name of the attribute
	 * @param string $handler The value
	 */
	function addBodyEventHandler($name,$handler)
	{
		$this->bodyEvents[$name] = $handler;
		$this->set("bodyEvents",$this->bodyEvents);
	}

	/**
	 * Adds a meta tag to the page like this <meta name='$name' content='$content' scheme='$scheme'/>
	 * @param string $name The name
	 * @param string $content The content
	 * @param string $scheme The scheme
	 */
	function addMeta($name,$content,$scheme="")
	{
//		if( isset($this->meta[$name]) )
//			return;
		$meta = "\t<meta name='$name' content='$content'".(($scheme=="")?"":" scheme='$scheme'")."/>\n";
		$this->meta[$name.$content] = $meta;
//		$this->set("meta",$this->meta);
	}

	/**
	 * Adds a link tag to the page like this <link rel='$rel' type='$type' title=\"$title\" href='$href'/>
	 * @param string $rel The rel attribute
	 * @param string $href The href attribute
	 * @param string $type The type attribute
	 * @param string $title The title attribute
	 */
	function addLink($rel,$href,$type="",$title="")
	{
		if( isset($this->meta[$rel.$href.$type]) )
			return;
		$meta = "\t<link rel='$rel' type='$type' title=\"$title\" href='$href'/>\n";
		$this->meta[$rel.$href.$type] = $meta;
//		$this->set("meta",$this->meta);
	}

	/**
	 * Adds a script tag to the page like this <script type='text/javascript' src='$src'></script>
	 * @param string $src The src attribute
	 */
	function addJs($src)
	{
		if( isset($this->js[$src]) )
			return;
		$js = "\t<script type='text/javascript' src='$src'></script>\n";
		$this->js[$src] = $js;
	}

	/**
	 * Adds a link tag (type=css) to the page like this <link rel='stylesheet' type='text/css' href='$src'/>
	 * @param string $src The src attribute
	 */
	function addCss($src)
	{
		if( isset($this->css[$src]) )
			return;
		$css = "\t<link rel='stylesheet' type='text/css' href='$src'/>\n";
		$this->css[$src] = $css;
	}

	function addContent($content) { return $this->content($content); }
	function content($content)
	{
		$this->add2var("content",$content);
        return $content;
	}

	function addDialog(&$dialog)
	{
		$this->dialogs[] = $dialog;
		$this->set("dialogs",$this->dialogs);
	}

	function addDocReady($js_code,$jq_wrapped=true)
	{
		if( is_array($js_code) )
			$js_code = implode("\n",$js_code);
		if( !trim($js_code) )
			return;

		$k = "k".md5($js_code);
		if( $jq_wrapped )
		{
			if( !isset($this->docready[$k]) )
				$this->docready[$k] = $js_code;
		}
		else
		{
			if( !isset($this->plaindocready[$k]) )
				$this->plaindocready[$k] = $js_code;
		}
	}

	function WdfRenderAsRoot()
	{
		global $CONFIG;
		
		execute_hooks(HOOK_PRE_RENDER,array($this));

		$init_data = array('request_id' => request_id(),'site_root' => cfg_get('system','url_root'));
		if( cfg_getd('system','attach_session_to_ajax',false) )
		{
			$init_data['session_id'] = session_id();
			$init_data['session_name'] = session_name();
		}
		if( isDevOrBeta() )
			$init_data['log_to_console'] = true;

		$this->set("wdf_init","wdf.init(".json_encode($init_data).");");
		$this->set("docready",$this->docready);
		$this->set("plaindocready",$this->plaindocready);

		return parent::WdfRenderAsRoot();
	}
	
	function SetIE9PinningData($application,$tooltip,$start_url,$button_color=false)
	{
		if ( !isset($_SERVER['HTTP_USER_AGENT']) || ((strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 9' ) === false) && (strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 10' ) === false)) )
			return;
		$this->addMeta('application-name',"$application");
		$this->addMeta('msapplication-tooltip',"$tooltip");
		$this->addMeta('msapplication-starturl',"$start_url");
		$this->addMeta('msapplication-window',"width=1024;height=768");
		if( $button_color )
			$this->addMeta('msapplication-navbutton-color',"$button_color");
	}
	
	function AddIE9PinningTask($name,$url,$icon_url,$window_type="tab")
	{
		if ( !isset($_SERVER['HTTP_USER_AGENT']) || ((strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 9' ) === false) && (strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 10' ) === false)) )
			return;
		$this->addMeta('msapplication-task',"name=$name; action-uri=$url; icon-uri=$icon_url; window-type=$window_type");
	}
}
?>