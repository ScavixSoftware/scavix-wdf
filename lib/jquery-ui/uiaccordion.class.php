<?php

//for more information about the accordion visit: http://jqueryui.com/demos/accordion/
class uiAccordion extends Control
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

	static function  __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'));
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'));
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
	//	$this->script("$('#".$this->id."').accordion(".system_to_json($this->_options).")");
	}
}
?>
