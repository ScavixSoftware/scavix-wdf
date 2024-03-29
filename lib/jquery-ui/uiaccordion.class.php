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
namespace ScavixWDF\JQueryUI;

use ScavixWDF\Base\Control;

/**
 * Wrapper around jQueryUI Accordion
 * 
 * See http://jqueryui.com/accordion/
 */
class uiAccordion extends uiControl
{
	public $_sections = [];
	
	/**
	 * @param array $options See http://api.jqueryui.com/accordion/
	 */
    function __construct($options = [])
	{
		parent::__construct("div");
		$this->Options = array_merge(array
			(
				"animate" => false,
				"collapsible" => true,
				"heightStyleType" => 'content'
			),force_array($options));
	}

	/**
	 * @override
	 */
	function PreRender($args = [])
	{
		foreach($this->_sections as $section=>$section_content)
		{
			$this->content("<h3>$section</h3>");
			$this->content($section_content);
		}
		parent::PreRender($args);
	}

	/**
	 * Adds a Section to the accordion
	 * 
	 * Also returns the div for the new section
	 * @param string $section_title name of the section
	 * @return Control the content container for the created
	 */
	public function &AddSection($section_title)
	{
		if( !isset($this->_sections[$section_title]) )
		{
			$ctrl = new Control("div");
			$this->_sections[$section_title] = $ctrl;
		}
        return $this->_sections[$section_title];
	}
	/**
	 * Adds Content to the specific section.
	 * 
	 * @param string $section the section name
	 * @param mixed $content the content which will be appended
	 * @return void
	 */
	public function AddContentToSection($section,$content)
	{
		$this->_sections[$section]->content($content);
	}
}
