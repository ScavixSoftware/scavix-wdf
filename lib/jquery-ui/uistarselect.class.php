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
default_string('TXT_VERY_POOR', 'Poor');
default_string('TXT_NOT_THAT_BAD', 'Bad');
default_string('TXT_AVERAGE', 'Average');
default_string('TXT_GOOD', 'Good');
default_string('TXT_PERFECT', 'Perfect');
/**
 * Wraps a jQueryUI 'Star-Rating' control.
 * 
 * See http://plugins.jquery.com/project/Star_Rating_widget
 * @attribute[Resource('jquery-ui/ui.stars.js')]
 * @attribute[Resource('jquery-ui/ui.stars.css')]
 */
class uiStarSelect extends uiControl
{
	private $_value = 3;
	private $_options;
	public $_content;

	var $_scale = array(
		1=>"TXT_VERY_POOR",
		2=>"TXT_NOT_THAT_BAD",
		3=>"TXT_AVERAGE",
		4=>"TXT_GOOD",
		5=>"TXT_PERFECT"
	);

	/**
	 * @param array $options See http://plugins.jquery.com/project/Star_Rating_widget
	 */
    function __initialize( $options=array() )
	{
		parent::__initialize("div");
		
		$this->_options = $options;
		$this->_options['inputType'] = 'select';

		if( isset($this->_options['disabled']) )
			$this->_options['disabled'] = true;

		store_object($this);
	}

	/**
	 * @override Some initializations
	 */
	public function WdfRender()
	{
		if( isset($this->_options['captionEl']) )
		{
			$title = isset($this->_options['captionTitle'])?$this->_options['captionTitle'].": ":getString("TXT_RATING").": ";
			$labTitle = new Label($title);
			$labTitle->class = "userrating";

			$caption_element = "<span id='".$this->_options['captionEl']."'></span>";
			$caption = ", captionEl: $('#".$this->_options['captionEl']."')}";
			
			unset($this->_options['captionEl']);
			unset($this->_options['captionTitle']);

			$this->_options = system_to_json($this->_options);
			$this->_options = str_replace("}",$caption,$this->_options);

			$this->_content[] = $labTitle;
			$this->_content[] = $this->CreateSelect($this->id."_select");
			//$this->_content[] = "&nbsp;&nbsp;(".$caption_element.")";
		}
		else
		{
			$this->_options = system_to_json($this->_options);
			$this->_content[] = $this->CreateSelect($this->id."_select");
		}
		
		$script = "$('#{$this->id}').stars($this->_options);";
		$this->script($script);
//		log_debug($generate_script_code?"doing: $script":"");

		return parent::WdfRender();
	}

	private function CreateSelect($sel_name)
	{
		$Select = new Select($sel_name);
		foreach( $this->_scale as $val => $desc )
		{
			$selected = ($val==$this->_value)?true:false;
			$Select->AddOption( $val, $desc, $selected);
		}
		return $Select;
	}

	/**
	 * Sets the value.
	 * 
	 * @param mixed $value The new value
	 * @return void
	 */
	public function SetValue($value=false)
	{
		if( !$value )
			return;
		$this->_value = $value;
	}

	/**
	 * Sets the caption.
	 * 
	 * @param string $caption_title The label
	 * @return void
	 */
	public function SetCaption($caption_title=null)
	{
		$this->_options['captionEl'] = "stars-cap".$this->id;

		if( !is_null($caption_title) )
			$this->_options['captionTitle'] = $caption_title;
	}
}
