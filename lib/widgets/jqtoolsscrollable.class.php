<?php
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
 
class jqToolsScrollable extends Template
{
	var $ItemGroups = array();
	protected $_options = array(
		'easing'=>false,
		'circular'=>false,
		'speed'=>700,
		'vertical'=>false
	);

    function __initialize($width=680,$height=120,$options=array())
	{
		parent::__initialize();

		if( !is_array($options) )
			$options = array($options);

		$this->_options = array_merge($this->_options,$options);

		$this->set('width',$width);
		$this->set('height',$height);
	}
	
	function do_the_execution()
	{
		$this->set('itemgroups',$this->ItemGroups);
		$this->set('options',jsArray2JSON($this->_options));

		return parent::do_the_execution();
	}

	static function __js()
	{
		return array(
			jsFile('jquery/jquery.tools.min.js')
		);
	}

	/**
	 * Add one portion of items to be scrolled
	 *
	 * @param <array> $items array of items to be displayed e.g. images
	 */
	function &AddItemGroup($items)
	{
		$this->ItemGroups[] = $items;
	}

	/**
	 * Add a custom easing function
	 *
	 * @param <string> $easingname name of the js easing-function
	 */
	function AddCustomEasing($easingname=false)
	{
		if( !$easingname )
			$this->_options['easing'] = 'jqtoolseasing';
	}
}
?>
