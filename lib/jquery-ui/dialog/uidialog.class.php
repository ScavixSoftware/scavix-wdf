<?php
class uiDialog extends uiControl
{
	protected $Options = array();
	protected $Buttons = array();
	protected $CloseButton = null;
	protected $CloseButtonAction = null;

	function __initialize($title="TITLE_DIALOG", $options=array())
	{
		parent::__initialize("div");
		$this->title = $title;

		$this->Options = array_merge(array(
				'autoOpen'=>true,
				'modal'=>true,
				'resizable'=>false,
				'draggable'=>false,
				'width'=>350,
				'height'=>150,
				'open'=>"function(){ $(this).parent().find('.ui-button').button('enable'); }",
			),$options);
	}

	function SetOption($name,$value)
	{
		$this->Options[$name] = $value;
	}

	function PreRender($args=array())
	{
		if( count($args) > 0 )
		{
			$controller = &$args[0];
			// just to render close button with the right id
			if( !is_null($this->CloseButton) )
			{
				if(is_null($this->CloseButtonAction))
					$this->CloseButtonAction = "[jscode] function(){ $('#{$this->id}').dialog('close'); }";
				$temp = array( $this->CloseButton => $this->CloseButtonAction );
				$this->Buttons = array_merge($this->Buttons, $temp);
			}
			$this->Options['buttons'] = $this->Buttons;
			$tmp = $this->_script;
			$this->_script = array();
			$this->script("try{ $('#{$this->id}').dialog(".system_to_json($this->Options)."); }catch(ex){ wdf.debug(ex); }");
			$this->script("$('#{$this->id}').parent().find('.ui-button').click(function(){ $(this).parent().find('.ui-button').button('disable'); });");
			$this->_script = array_merge($this->_script,$tmp);

			foreach( $this->_script as $s )
			{
				$controller->addDocReady($s);
			}
		}
		return parent::PreRender($args);
	}
	/**
	 * Look at system.php system_to_json($value) documentation
	 */
	function AddButton($label,$action)
	{
		//$label = getString($label);
		$this->Buttons[$label] = $action;
	}

	function AddCloseButton($label, $action = false)
	{
		$this->CloseButton = $label;
		if($action !== false)
			$this->CloseButtonAction = $action;
	}
}
?>