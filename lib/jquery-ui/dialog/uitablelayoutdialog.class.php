<?php
class uiTableLayoutDialog extends uiDialog
{
	private $_table;
	
	function __initialize($title="TITLE_DIALOG", $options=array())
	{
		parent::__initialize($title,$options);
		$this->_table = $this->content( new Table() );
	}
	
	function AddLine($label, $control=false)
	{
		if( $control )
			$this->_table->NewRow(array($label,$control));
		else
			$this->_table->NewRow()->NewCell($label)->colspan = "2";
	}
}
?>