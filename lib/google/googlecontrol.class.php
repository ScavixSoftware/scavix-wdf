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
namespace ScavixWDF\Google;

use ScavixWDF\Base\Control;
use ScavixWDF\Base\HtmlPage;
use ScavixWDF\Base\Renderable;
use ScavixWDF\Localization\CultureInfo;

/**
 * Base class for all google controls.
 * 
 * Ensures all libraries are loaded correctly and stuff.
 */
class GoogleControl extends Control
{
	protected static $_apis = [];
	private static $_delayedHookAdded = false;
	private $disposed = false;
	private $frozen = true;
	var $_culture = false;
    private $gchartsversion = false;
	
	/**
	 * @param string $tag Allows to specify another tag for the wrapper control, default for google controls is &lt;span&gt;
	 */
	function __construct($tag='span', $frozen = true, $gchartsversion = 'current')
	{
		parent::__construct($tag);
		$this->frozen = $frozen;
        $this->gchartsversion = $gchartsversion;
	}
    
    protected function __collectResourcesInternal($template, &$static_stack = [])
    {
        $res = parent::__collectResourcesInternal($template, $static_stack);
        if( $this->frozen )
            $res[] = '//www.gstatic.com/charts/loader.js';
        else
            $res[] = '//www.google.com/jsapi';
        return $res;
    }
	
	function __dispose()
	{
		delete_object($this->id);
		$this->disposed = true;
	}
	
	/**
	 * Assigns a culture to this control.
	 * 
	 * This will be used for value formatting.
	 * @param CultureInfo $ci The culture object
	 * @return static
	 */
	function setCulture(CultureInfo $ci)
	{
		$this->_culture = $ci;
		return $this;
	}
	
	/**
	 * @override
	 */
	function PreRender($args = [])
	{
		// we register a new HOOK_PRE_RENDER handler here so that it will be executed when all others are
		// finished. so derivered classes can add their loader code in PreRender as usual.
		if( count($args) > 0 )
		{
			$controller = $args[0];
			if( $controller instanceof Renderable )
			{
				if( !self::$_delayedHookAdded )
				{
					self::$_delayedHookAdded = true;
					register_hook(HOOK_PRE_RENDER, $this, 'AddLoaderCode');
				}
			}
		}
		return parent::PreRender($args);
	}
	
	/**
	 * @internal PreRender HOOK handler
	 */
	function AddLoaderCode($args)
	{
		$loader = [];
		foreach( self::$_apis as $api=>$definition )
		{
            if(count($definition) > 1)
                list($version,$options) = $definition;
            else
            {
                $version = 1;
                $options = array_first($definition);
            }
			if( isset($options['callback']) )
				$options['callback'] = "function(){ ".implode("\n",$options['callback'])." }";
			else
				$options['callback'] = "function(){}";
			
			if( $this->_culture )
				$options['language'] = $this->_culture->ResolveToLanguage()->Code;
			
			if( $this->frozen )
			{
				$loader[] = "window.googleLoadCallback = ".$options['callback'];
				$options['callback'] = 'function(){ window.googleLoadCallback(); }';
				$loader[] = "if( window.googleLoaded ) { window.googleLoadCallback(); } else { window.googleLoaded = true; google.charts.load('".$this->gchartsversion."',".system_to_json($options)."); }";
			}
			else
				$loader[] = "google.load('$api','$version',".system_to_json($options).");";
		}
		$controller = $args[0];
		if( system_is_ajax_call() && (($controller instanceof GoogleControl) || $controller->hasContentOfInstance('ScavixWDF\Google\GoogleControl')) )
			$controller->script($loader);
		elseif( $controller instanceof HtmlPage )
			$controller->addDocReady($loader,false); // <- see the 'false'? we add these codes inline, not into the ready handler as this crashes
	}
	
	protected function _loadApi($api,$version,$options)
	{
		self::$_apis[$api] = array($version, $options);
	}
	
	protected function _addLoadCallback($api,$script,$prepend=false)
	{
		if( $this->disposed )
			return;
		
		if( !isset(self::$_apis[$api][1]['callback']) )
			self::$_apis[$api][1]['callback'] = [];
		
		if( is_array($script) )
			$script = implode("\n",$script);

		if( $prepend )
		{
			$temp = array_reverse(self::$_apis[$api][1]['callback']);
			$temp[$this->id] = $script;
			self::$_apis[$api][1]['callback'] = array_reverse($temp);
		}
		else
			self::$_apis[$api][1]['callback'][$this->id] = $script;
	}
}