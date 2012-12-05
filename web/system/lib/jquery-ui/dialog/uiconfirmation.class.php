<?php
class uiConfirmation extends uiDialog
{
	const OK_CANCEL = 1;
	const YES_NO = 2;
	
	function __initialize($title='TITLE_CONFIRMATION',$text='TXT_CONFIRMATION',$ok_callback=false,$button_mode=self::OK_CANCEL)
	{
		$options = array(
			'autoOpen'=>true,
			'modal'=>true,
			'width'=>450,
			'height'=>300
		);
		
		parent::__initialize($title,$options);
		switch( $button_mode )
		{
			case self::OK_CANCEL:
				$this->AddButton('BTN_OK',$ok_callback);
				$this->AddCloseButton('BTN_CANCEL');
				break;
			case self::YES_NO:
				$this->AddButton('BTN_YES',$ok_callback);
				$this->AddCloseButton('BTN_NO');
				break;
			default:
				throw new Exception("Wrong button_mode: $button_mode");
		}
		$this->content($text);
	}
}
?>
