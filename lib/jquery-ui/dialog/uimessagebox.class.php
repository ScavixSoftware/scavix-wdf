<?php
class uiMessageBox extends uiDialog
{
    function __initialize($message_text,$type='hint',$title='TITLE_WARNING')
	{
		$options = array(
			'autoOpen'=>true,
			'modal'=>true,
			'width'=>450,
			'height'=>300

		);
		parent::__initialize($title,$options);
		$this->class = $type;

		$this->content($message_text);
		$this->AddCloseButton('BTN_OK');
	}
}
?>
