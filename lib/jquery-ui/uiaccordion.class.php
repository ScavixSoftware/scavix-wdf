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

//for more information about the accordion visit: http://jqueryui.com/demos/accordion/
class uiAccordion extends uiControl
{
	var $_sections = array();
	var $_options;
	
    function __initialize($options = array(),$css_propertys = array())
	{
		parent::__initialize("div");
		$this->_options  = array(
				"animate"=>false,
				"collapsible"=>true,
				"heightStyleType"=>'content'
			);

		if(!is_array($options))
			$this->_options = array($options);

		$this->_options = array_merge($options,$this->_options);
		
		foreach($css_propertys as $name=>$val)
		{ $this->css($name,$val); }
	}

	function PreRender($args = array())
	{
		foreach($this->_sections as $section=>$section_content)
		{
			$this->content("<h3>$section</h3>");
			$this->content($section_content);
		}
		
		$this->script("$('#".$this->id."').accordion(".system_to_json($this->_options).")");
		parent::PreRender($args);
	}

	/**
	 * Adds a Section to the accordion, also returns the div for the new section
	 * @param <type> $section_title name of the section
	 * @return Control the content container for the created
	 */
	public function &AddSection($section_title)
	{
		if(!array_key_exists($section_title, $this->_sections))
		{
			$ctrl = new Control("div");
			$this->_sections[$section_title] = $ctrl;
			return $ctrl;
		}
	}
	/**
	 * Adds Content to the specific section
	 * @param <type> $section the section name
	 * @param <type> $content the content which will be appended
	 */
	public function AddContentToSection($section,$content)
	{
		$this->_sections[$section]->content($content);
	}

	public function MakeDraggable()
	{
		$this->script("$('#".$this->id."').draggable()");
	}

	/**
	 * Adds options to the accordion
	 * @param <type> $options
	 */
	public function AddOptions($options = array())
	{
		$this->_options = array_merge($this->_options,$options);
	}
}
?>
