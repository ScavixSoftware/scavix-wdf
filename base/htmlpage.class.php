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

use ScavixWDF\ICallable;
use ScavixWDF\Localization\Localization;

default_string('ERR_JAVASCRIPT_AND_COOKIES_REQUIRED','This page requires JavaScript and Cookies.');
define("WDF_HTMLPAGE_TEMPLATE", __DIR__."/htmlpage.tpl.php");
/**
 * Base class for all Html pages.
 *
 * Will perform all rendering and collect js, css, meta and more.
 * @attribute[Resource('jquery.js')]
 * @attribute[Resource('htmlpage.js')]
 */
class HtmlPage extends Template implements ICallable
{
	public $meta = [];
	public $js = [];
	public $css = [];
	public $docready = [];
	public $inlineheaderpre = false;
	public $inlineheader = false;
	public $plaindocready = [];
	public $wdf_settings = array('focus_first_input'=>true);

    public static $RENDER_NOSCRIPT = true;
    public static $DOCTYPE = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

	/**
	 * Setting this to a filename (relative to class) will load it as subtemplate
	 * @var bool|string Templatename or false
	 */
	public $SubTemplate = false;

    public static $POLYFILLS = [];

    /**
     * Adds polyfill to the rendered HTML page.
     *
     * This feature uses polyfill to magically make your website work in nearly every browser
     * See https://www.all-about-security.de/der-angriff-auf-die-polyfill-io-domaene/ why it's loaded from cloudflare
     *
     * @param string|array $pf Requested polyfills
     * @return void
     */
    public static function AddPolyfills($pf)
    {
        if( is_string($pf) )
            $pf = explode(",", str_replace([' ','|'],[',',','],$pf));
        self::$POLYFILLS = array_merge(self::$POLYFILLS,$pf);
    }

    function __toString()
    {
        return "{$this->_storage_id} [".get_class($this)."]";
    }

	/**
	 * @param string $title Page title
	 * @param string $body_class Optional value for the class attribute of the &lt;body&gt; element
	 */
    function __construct($title = "", $body_class = false)
    {
        if (current_event() == 'wdfgettext')
            system_exit($this->WdfGetText(Args::request('id', '')));

        // this makes HtmlPage.tpl.php the 'one and only' template
        // for all derivered classes, unless they override it after
        // parent::__construct with $this->file = X
        parent::__construct(WDF_HTMLPAGE_TEMPLATE);

        $this->set("title", $title);
        $this->set("meta", $this->meta);
        $this->set("js", $this->js);
        $this->set("css", $this->css);
        $this->set("content", []);
        $this->set("docready", $this->docready);
        $this->set("plaindocready", $this->plaindocready);
        $this->set("inlineheaderpre", $this->inlineheaderpre);
        $this->set("inlineheader", $this->inlineheader);

        if ($body_class)
            $this->set("bodyClass", "$body_class");

        if (resourceExists("favicon.ico"))
            $this->set("favicon", resFile("favicon.ico"));

        // set up correct display on mobile devices: http://stackoverflow.com/questions/8220267/jquery-detect-scroll-at-bottom
        $this->addMeta("viewport", "width=device-width, height=device-height, initial-scale=1.0");
        $this->addMeta("referrer", "strict-origin-when-cross-origin");

        $buffer = \ScavixWDF\Wdf::GetBuffer('wdf_js_strings')->mapToSession('wdf_js_strings');
        $jsstrings = $this->getJsRegisteredStrings();
        $jsstringsversion = md5(join('-', array_keys($jsstrings)) . '-' . join('-', $jsstrings));
        if (ifavail($_SESSION, 'js_strings_version') != $jsstringsversion)
        {
            foreach ($jsstrings as $id => $txt)
            {
                if (is_numeric($id))
                    $id = $txt;
                $buffer->set($id, $txt);
            }
            $_SESSION['js_strings_version'] = $jsstringsversion;
        }
        if (iterator_count($buffer))
        {
			if (method_exists($this, 'WdfResourceTexts'))
				$url = can_rewrite() ? buildQuery(current_controller(), 'wdfresourcetexts').$_SESSION['js_strings_version'].".js" : buildQuery(current_controller(), 'wdfresourcetexts', ['v' => $_SESSION['js_strings_version']]);
			else
				$url = can_rewrite() ? buildQuery('wdfresource', 'texts').$_SESSION['js_strings_version'].".js" : buildQuery('wdfresource', 'texts', ['v' => $_SESSION['js_strings_version']]);
			$this->addJs($url);

            // if (can_rewrite())
            //     $this->addJs(buildQuery('wdfresource', 'texts').$_SESSION['js_strings_version'].".js");
            // else
            //     $this->addJs(buildQuery('wdfresource', 'texts', ['v' => $_SESSION['js_strings_version']]));
        }
    }

	/**
	 * Override this method to register texts for usage in JavaScript.
	 *
	 * Note that this will only be called once in a session.
	 * @return array Key-Value pairs of string-constants and their values
	 */
	function getJsRegisteredStrings()
	{
		return [];
	}

	/**
	 * @override
	 */
	function WdfRenderAsRoot()
	{
        if( isset($GLOBALS['CONFIG']['system']['htmlpage']['doctype']) )
            log_warn('"doctype" config is deprecated, use HtmlPage::$DOCTYPE instead');
        if( isset($GLOBALS['CONFIG']['system']['htmlpage']['render_noscript']) )
            log_warn('"render_noscript" config is deprecated, use HtmlPage::$RENDER_NOSCRIPT instead');

        self::$_renderingRoot = $this;
		execute_hooks(HOOK_PRE_RENDER,array($this));

		$init_data = $this->wdf_settings;
		$init_data['request_id'] = request_id();
		$init_data['site_root']  = cfg_get('system','url_root');
        $init_data['rewrite'] = can_rewrite();

		if (function_exists('session_needs_url_arguments') && session_needs_url_arguments())
		{
			$init_data['session_id'] = session_id();
			$init_data['session_name'] = session_name();
		}
		if(isDevOrBeta() && !isset($init_data['log_to_console']) )
			$init_data['log_to_console'] = true;

		if( isset($init_data['texts']) )
		{
			$buffer = \ScavixWDF\Wdf::GetBuffer('wdf_js_strings')->mapToSession('wdf_js_strings');
			foreach( $init_data['texts'] as $id=>$txt )
				$buffer->set(str_replace("[NT]","",$id),$txt);
		}

		$this->set("wdf_init","wdf.init(".json_encode($init_data).");");
		$this->set("docready",$this->docready);
		$this->set("plaindocready",$this->plaindocready);

        $this->set('polyfills',array_filter(array_unique(self::$POLYFILLS)));

		return parent::WdfRenderAsRoot();
	}

	/**
	 * @override
	 */
	function WdfRender()
	{
		if( system_is_module_loaded('localization') )
		{
			$ci = Localization::detectCulture();
			if( !$this->get('isrtl') && $ci->IsRTL )
				$this->set("isrtl", " dir='rtl'");
            $this->set("languagecode", $ci->Code);
		}

		$res = $this->__collectResources();
		$this->js = array_reverse($this->js,true);
		foreach( Renderable::CategorizeResources(array_reverse($res)) as $r )
		{
			if( $r['ext'] == 'css' || $r['ext'] == 'less' )
				$this->addCss($r['url'],$r['key']);
			else
				$this->addjs($r['url'],$r['key']);
		}
		$this->js = array_reverse($this->js,true);

		$this->set("css",$this->css);
		$this->set("js",$this->js);
		$this->set("meta",$this->meta);
		$this->set("content",$this->_content);
		$this->set("inlineheaderpre",$this->inlineheaderpre);
		$this->set("inlineheader",$this->inlineheader);

		return parent::WdfRender();
	}

	/**
	 * Adds a meta tag to the page
	 *
	 * Like this &lt;meta name='$name' content='$content' scheme='$scheme'/&gt;
	 * @param string $name The name
	 * @param string $content The content
	 * @param string $scheme The scheme
	 * @param string $type The meta-tags name ('name','http-equiv',...)
	 * @return static
	 */
	function addMeta($name,$content,$scheme="",$type='name')
	{
		$meta = "\t<meta $type='$name' content='$content'".(($scheme=="")?"":" scheme='$scheme'")."/>\n";
		$this->meta[$name.$content] = $meta;
		return $this;
	}

	/**
	 * Adds a link tag to the page
	 *
	 * Like this: &lt;link rel='$rel' type='$type' title='$title' href='$href'/&gt;
	 * @param string $rel The rel attribute
	 * @param string $href The href attribute
	 * @param string $type The type attribute
	 * @param string $title The title attribute
	 * @return static
	 */
	function addLink($rel,$href,$type="",$title="")
	{
		if( isset($this->meta[$rel.$href.$type]) )
			return $this;
		$meta = "\t<link rel='$rel' type='$type' title=\"$title\" href='$href'/>\n";
		$this->meta[$rel.$href.$type] = $meta;
		return $this;
	}

	/**
	 * Adds a script tag to the page
	 *
	 * Like this: &lt;script type='text/javascript' src='$src'&gt;&lt;/script&gt;
	 * @param string $src The src attribute
	 * @param string $key Optional data-key attribute for the script tag
	 * @return static
	 */
	function addJs($src,$key='')
	{
		if( isset($this->js[$src]) )
			return $this;
		$js = "\t<script type='text/javascript' src='$src'".($key != '' ? " data-key='$key'" : '')."></script>\n";
		$this->js[$src] = $js;
		return $this;
	}

	/**
	 * Adds a link tag (type=css) to the page
	 *
	 * Like this: &lt;link rel='stylesheet' type='text/css' href='$src'/&gt;
	 * @param string $src The src attribute
	 * @param string $key Optional data-key attribute for the link tag
	 * @return static
	 */
	function addCss($src,$key='')
	{
		if( isset($this->css[$src]) )
			return $this;
		$css = "\t<link rel='stylesheet' type='text/css' href='$src'".($key != '' ? " data-key='$key'" : '')."/>\n";
		$this->css[$src] = $css;
		return $this;
	}

	/**
	 * Adds raw code to the header
	 *
	 * @param string $code The raw code
	 * @param bool $pre Should it be added before the standard css and js files in the header?
	 * @return static
	 */
	function addHeaderRaw($code, $pre = false)
	{
		if($pre)
		{
			if( !$this->inlineheaderpre )
				$this->inlineheaderpre = '';
			$this->inlineheaderpre .= $code;
		}
		else
		{
			if( !$this->inlineheader )
				$this->inlineheader = '';
			$this->inlineheader .= $code;
		}
		return $this;
	}

	/**
	 * Adds code to the document ready event.
	 *
	 * @param mixed $js_code JS code as string or array
	 * @param bool $jq_wrapped If true adds the code to the ready event handler, else will be added inline into the head script element
	 * @return static
	 */
	function addDocReady($js_code,$jq_wrapped=true)
	{
		if( is_array($js_code) )
			$js_code = implode("\n",$js_code);
		if( !trim($js_code) )
			return $this;

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
		return $this;
	}

	/**
	 * Sets InternetExplorer 'Pinned Site Metadata'
	 *
	 * See http://msdn.microsoft.com/en-us/library/ie/gg491732%28v=vs.85%29.aspx
	 * @param string $application See [application-name](http://msdn.microsoft.com/en-us/library/ie/gg491732%28v=vs.85%29.aspx#application-name)
	 * @param string $tooltip See [msapplication-tooltip](http://msdn.microsoft.com/en-us/library/ie/gg491732%28v=vs.85%29.aspx#msapplication-tooltip)
	 * @param string $start_url See [msapplication-starturl](http://msdn.microsoft.com/en-us/library/ie/gg491732%28v=vs.85%29.aspx#msapplication-starturl)
	 * @param string $button_color See [msapplication-navbutton-color](http://msdn.microsoft.com/en-us/library/ie/gg491732%28v=vs.85%29.aspx#msapplication-navbutton-color)
	 * @return static
	 */
	function SetIE9PinningData($application,$tooltip,$start_url,$button_color=false)
	{
		if ( !isset($_SERVER['HTTP_USER_AGENT']) || ((strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 9' ) === false) && (strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 10' ) === false)) )
			return $this;
		$this->addMeta('application-name',"$application");
		$this->addMeta('msapplication-tooltip',"$tooltip");
		$this->addMeta('msapplication-starturl',"$start_url");
		$this->addMeta('msapplication-window',"width=1024;height=768");
		if( $button_color )
			$this->addMeta('msapplication-navbutton-color',"$button_color");
		return $this;
	}

	/**
	 * Adds a task to the InternetExplorer 'Jump List'
	 *
	 * See http://msdn.microsoft.com/en-us/library/ie/gg491725%28v=vs.85%29.aspx
	 * @param string $name The task name that appears in the Jump List
	 * @param string $url The address that is launched when the item is clicked
	 * @param string $icon_url The icon resource that appears next to the task in the Jump List
	 * @param string $window_type One of 'tab', 'self' or 'window'
	 * @return static
	 */
	function AddIE9PinningTask($name,$url,$icon_url,$window_type="tab")
	{
		if ( !isset($_SERVER['HTTP_USER_AGENT']) || ((strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 9' ) === false) && (strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE 10' ) === false)) )
			return $this;
		$this->addMeta('msapplication-task',"name=$name; action-uri=$url; icon-uri=$icon_url; window-type=$window_type");
		return $this;
	}

	/**
	 * @internal Will be called automatically if missing strings are detected browserside.
	 *
	 * @attribute[RequestParam('id','string','')]
	 */
	function WdfGetText($id)
	{
		$buffer = \ScavixWDF\Wdf::GetBuffer('wdf_js_strings')->mapToSession('wdf_js_strings');
		$buffer->set($id,$id);
		$_SESSION['js_strings_version'] = time();
		return AjaxResponse::Json([$id=>_text($id)]);
	}
}
