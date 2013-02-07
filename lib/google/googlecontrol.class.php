<?
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2012 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

/**
 * @attribute[ExternalResource('//www.google.com/jsapi')]
 */
class GoogleControl extends Control
{
	private static $_packages = array();
	private static $_initCodeRendered = false;
	
	function __initialize()
	{
		parent::__initialize('span');
	}
	
	function PreRender($args = array())
	{
		if( !self::$_initCodeRendered )
		{
			self::$_initCodeRendered = true;
			if( count($args) > 0 )
			{
				$controller = $args[0];
				if( $controller instanceof HtmlPage )
				{
					// let these controls render plain, not wrapped into jQuery's document ready function
					// note the FALSE in the addDocReady calls!
					$loader = array();
					foreach( self::$_packages as $api=>$pack )
						foreach( $pack as $version=>$packages )
							$loader[] = "google.load('$api','$version',".json_encode($packages).");";
					$controller->addDocReady($loader,false);
					$controller->addDocReady("google.setOnLoadCallback(function(){ wdf.debug('Google APIs loaded'); });",false);
					$controller->addDocReady(implode("\n",$this->_script),false);
					return;
				}
			}
		}
		return parent::PreRender($args);
	}
	
	protected function _loadPackage($api,$version,$package)
	{
		self::$_packages[$api][$version]['packages'][] = $package;
	}
}