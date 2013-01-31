<?php

/**
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

    function __initialize( $cid="", $options=array() )
	{
		parent::__initialize("div");
		$this->id = $cid;
		
		$this->_options = $options;
		$this->_options['inputType'] = 'select';

		if( isset($this->_options['disabled']) )
			$this->_options['disabled'] = true;

		store_object($this);
	}

	static function __js()
	{
		return array(jsFile('jquery-ui/jquery-ui.js'),jsFile('jquery-ui/ui.stars.js'));
	}

	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'),skinFile('jquery-ui/ui.stars.css'));
	}

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

			$this->_options = jsArray2JSON($this->_options);
			$this->_options = str_replace("}",$caption,$this->_options);

			$this->_content[] = $labTitle;
			$this->_content[] = $this->CreateSelect($this->id."_select");
			//$this->_content[] = "&nbsp;&nbsp;(".$caption_element.")";
		}
		else
		{
			$this->_options = jsArray2JSON($this->_options);
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

	public function SetValue($value=false)
	{
		if( !$value )
			return false;
			
		$this->_value = $value;
	}

	public function SetCaption($caption_title=null)
	{
		$this->_options['captionEl'] = "stars-cap".$this->id;

		if( !is_null($caption_title) )
			$this->_options['captionTitle'] = $caption_title;
	}
}
?>
