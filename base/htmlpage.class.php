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
 */
class HtmlPage extends Template implements ICallable
{
	var $meta = array();
	var $js = array();
	var $css = array();
	var $content = array();
	var $dialogs = array();
	var $docready = array("");
	var $ajaxWaitDialogId = false;
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
				if(!isset($_GET['redirected']))
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
				if(isset($_GET['redirected']) && $_GET['redirected']==1)
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
		$this->set("content",$this->content);
		$this->set("docready",$this->docready);
		$this->set("bodyEvents",$this->bodyEvents);
		$this->set("requireJs",$this->requireJs);
		
		if( $body_class )
			$this->set("bodyClass",$body_class);

		if(system_is_module_loaded("skins"))
			$this->set("favicon", skinFile("favicon.ico"));

		if(system_is_module_loaded("javascript"))
		{
			$this->addJs(jsFile("jquery.js"));
			$this->addJs(jsFile("jquery.ajaxmanager.js"));
			$this->addJs(jsFile("jquery.jcache.js"));

			$this->addJs(jsFile("dialog.js"));
			$this->addDocReady("");
		}
	}

	function do_the_execution()
	{
		global $CONFIG;

		// moved here to allow derivered classes to set isrtl based on another CI
		if( !isset($this->vars['isrtl']) && system_is_module_loaded('localization') )
		{
			$ci = Localization::detectCulture();
			if( $ci->IsRTL )
				$this->set("isrtl", " dir='rtl'");
		}
		
		if( isset($CONFIG['use_compiled_css']) )
		{
			$this->css = array();
			$this->addCss($CONFIG['use_compiled_css']);
		}
		$this->set("css",$this->css);
		
		if( isset($CONFIG['use_compiled_js']) )
		{
			$this->js = array();
			$this->addJs($CONFIG['use_compiled_js']);
		}
		$this->set("js",$this->js);
		
		$this->set("meta",$this->meta);
		return parent::do_the_execution();
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
//		$this->set("js",$this->js);
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
//		$this->set("css",$this->css);
	}

	function addContent($content)
	{
		$this->content[] = $content;
		$this->set("content",$this->content);
        return $content;
	}

	function addDialog(&$dialog)
	{
		$this->dialogs[] = $dialog;
		$this->set("dialogs",$this->dialogs);
	}

	function addDocReady($js_code)
	{
		$k = "k".md5($js_code);
		if( !isset($this->docready[$k]) )
		{
			$this->docready[$k] = $js_code;
			$this->set("docready",$this->docready);
		}
	}

	function execute($encode=false)
	{
		global $CONFIG;
		execute_hooks(HOOK_PRE_RENDER,array($this));

		$init_code = "HtmlPage_Init({request_id:'".request_id()."',site_root:'".$CONFIG['system']['url_root']."'";
		if( $this->ajaxWaitDialogId )
			$init_code .= ",ajax_wait_dlg_id:'".$this->ajaxWaitDialogId."'";

		if( isset($CONFIG['system']['attach_session_to_ajax']) && $CONFIG['system']['attach_session_to_ajax'] )
			$init_code .= ",session_name:'".session_name()."',session_id:'".session_id()."'";

		$init_code .= "});";
		$this->docready[0] = $this->vars['docready'][0] = $init_code;

		if( isset($GLOBALS['debugger']) )
			$GLOBALS['debugger']->PreparePage($this);

		$_SESSION['cssCache'] = array();
		$_SESSION['jsCache'] = array();

		$this->CollectIncludes($this);
		$this->CollectIncludes($this->dialogs);

		foreach( $_SESSION['cssCache'] as $css )
		{
			if( is_array($css) )
				foreach( $css as $c ) $this->addCss($c);
			else
				$this->addCss($css);
		}
		foreach( $_SESSION['jsCache'] as $js )
		{
			if( is_array($js) )
				foreach( $js as $j ) $this->addJs($j);
			else
				$this->addJs($js);
		}
		$ret = parent::execute($encode);
        return $ret;
	}

	private function CollectIncludes(&$template)
	{
		$isot = is_object($template);
		$isat = is_array($template);
		if( !$isat )
			$classname = strtolower($isot?get_class($template):(string)$template);

		if( $isot && system_method_exists($template,'PreparePage') )
			$template->PreparePage($this);

		if($isot && !$isat)
		{
			system_include_statics($classname,'__css',$_SESSION['cssCache']);
			system_include_statics($classname,'__js',$_SESSION['jsCache']);
		}

		if( $template instanceof IRenderable )
		{
			$this->IncludeFiles($classname);
			$parent = strtolower(get_parent_class($template));
			while($parent != "" && $parent != "template" && $parent != "control" && $parent != "controlextender")
			{
				$this->IncludeFiles($parent);
				$parent = strtolower(get_parent_class($parent));
			}
		}

		if( $isot )
		{
			if(isset($template->vars) && is_array($template->vars) )
			{
				foreach( $template->vars as $varname=>$var )
				{
					$this->CollectIncludes($var);
				}
			}
			
			if(isset($template->_content) && is_array($template->_content) )
			{
				foreach( $template->_content as $var )
				{
					$this->CollectIncludes($var);
				}
			}

			if( isset($template->_extender) && is_array($template->_extender) )
			{
				foreach( $template->_extender as $varname=>$var )
				{
					$this->CollectIncludes($var);
				}
			}
		}
		elseif( $isat )
		{
			foreach( $template as $key=>$v )
			{
				$this->CollectIncludes($v);
			}
		}
	}

	function IncludeFiles($classname)
	{
		if( system_is_module_loaded("skins") && skinFileExists("$classname.css") )
			$this->addCss( skinFile("$classname.css") );

		if( system_is_module_loaded("javascript") && jsFileExists("$classname.js") )
			$this->addJs( jsFile("$classname.js") );
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