<?php

class uiDatabaseTable extends DatabaseTable
{
	function __initialize($datasource,$datatype=false)
	{
		parent::__initialize($datasource,$datatype);
		$this->class .= " ui-widget-content ui-corner-all";
		$this->css("border-collapse","separate");
	}

	function &Header()
    {
        $res = parent::Header();
		$res->RowOptions = array("tr_class"=>"ui-widget-header ui-corner-all");
		return $res;
    }

	function WdfRender()
    {
		if( $this->footer )
		{
			$controls = array();
			system_find($this->footer,"Tr",$controls);
			foreach( $controls as $c )
			{
				$c->class = isset($c->class)?$c->class." ui-corner-all":"ui-corner-all";
			}
		}
		return parent::WdfRender();
	}
}
?>
