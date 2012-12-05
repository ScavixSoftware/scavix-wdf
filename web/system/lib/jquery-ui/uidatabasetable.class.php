<?php

class uiDatabaseTable extends DatabaseTable
{
	function __initialize($datasource,$datatype=false)
	{
		parent::__initialize($datasource,$datatype);
		$this->class .= " ui-widget-content ui-corner-all";
		$this->css("border-collapse","separate");
	}

	static function __css()
	{
		return array(skinFile('jquery-ui/jquery-ui.css'));
	}

	function &Header()
    {
        $res = parent::Header();
		$res->RowOptions = array("tr_class"=>"ui-widget-header ui-corner-all");
		return $res;
    }

//	function &Footer()
//    {
//        $res = parent::Footer();
//		$res->class = "ui-corner-all";
//		return $res;
//    }

	function do_the_execution()
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
		return parent::do_the_execution();
	}
}
?>
