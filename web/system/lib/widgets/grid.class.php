<?php
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
class Grid extends Template
{
	protected $DefaultColumnOptions = array(
		"width" => 50,
		"resizable" => false,
		"sortable" => false
	);

	var $Columns = array();
	var $Rows = array();
	var $Userdata = array();

	var $Caption = false;
	var $RowNum = 10;
	var $IdField = false;
	var $Width = false;
	var $Height = false;
	var $Pager = true;
    
    var $BtnRefresh = false;
    var $BtnEdit = false;
    var $BtnAdd = false;
    var $BtnDel = false;
    var $BtnSearch = false;

	var $Page = 1;
	var $Total = false;
	var $Records = false;

	var $SaveCellMethod = false;
    var $RowEdit = false;

	function __initialize($id = false)
	{
		parent::__initialize();

		if( $id )
		{
			$this->_storage_id = $id;
			$this->set("id",$id);
			store_object($this);
		}
		else
			$this->set("id",$this->_storage_id);
	}

	static function __js()
	{
		return array(
			jsFile("jquery/jqGrid/min/grid.locale-en-min.js"),
			jsFile("jquery/jqGrid/packall/grid.pack.js"),
            jsFile("jquery/ui.datepicker.js"),
            jsFile("jquery/jqGrid/jqDnR.js")
		);
	}

	static function __css()
	{
		return array(
			skinFile("grid/jqModal.css"),
			skinFile("grid/basic/grid.css"),
            skinFile("datepicker.css")
		);
	}

	function AddColumn($caption,$name,$options=array())
	{
		$col = array('caption'=>$caption,'name'=>$name);

		$options = array_merge($this->DefaultColumnOptions,$options);

		foreach( $options as $opt=>$val)
			$col[$opt] = $val;
		$this->Columns[$name] = $col;
	}

	function AddRow($row)
	{
		$names = array_keys($this->Columns);
		foreach( $names as $name )
			if( !isset($row[$name]) )
				$row[$name] = "";

		$this->Rows[] = $row;
	}

    function CellAttribute($cellname,$attr_name,$attr_value,$row=false)
    {
        if( !$row )
            $row = count($this->Rows)-1;

        if( !is_array($this->Rows[$row][$cellname]) )
            $this->Rows[$row][$cellname] = array('__value'=>$this->Rows[$row][$cellname]);

        $this->Rows[$row][$cellname][$attr_name] = $attr_value;
    }

	function AddUserdata($name,$value)
	{
		$this->Userdata[$name] = $value;
	}

	function SetFooter($footer)
	{
		if( is_object($footer) && $footer instanceof IRenerable )
			$footer = $footer->Execute();
		$this->AddUserdata("footer_html",$footer);
	}

	function do_the_execution()
	{
		//log_debug($this);

		$model = array();
		$names = array();
		$cellEdit = false;
		foreach( $this->Columns as $name=>$col )
		{
			$names[] = $col['caption'];
			unset($col['caption']);
			$model[] = jsArray2JSON($col);
			$cellEdit |= isset($col['editable']) && $col['editable'] === true;
		}
        $cellEdit = $this->RowEdit?false:$cellEdit;

		if( $this->SaveCellMethod )
		{
			store_object($this->SaveCellMethod[0]);
			$this->set("cellurl","?load={$this->SaveCellMethod[0]->_storage_id}&event=$this->SaveCellMethod[1]");
		}
		else
			$this->set("cellurl","?load={$this->_storage_id}&event=SaveCell");

		$this->set("colNames","'".implode("','",$names)."'");
		$this->set("colModel",implode(",",$model));
		$this->set("hasFooter",isset($this->Userdata['footer_html']));
		$this->set("cellEdit",$cellEdit);

		if( $this->RowNum < 0 )
			$this->RowNum = count($this->Rows);

		store_object($this);
		return parent::do_the_execution();
	}

	protected function PackXml($tag,$value,$attributes=array(),$newline_after_start=false,$newline_after_end=true,$aspcdata=false)
	{
		$attr = "";
		foreach( $attributes as $k=>$v)
			$attr .= " $k='$v'";

		$newline_after_start = $newline_after_start?"\n":"";
		$newline_after_end = $newline_after_end?"\n":"";

		if( $aspcdata )
			$value = "<![CDATA[$value]]>";

		return "<$tag$attr>$newline_after_start$value</$tag>$newline_after_end";
	}

	function GetData()
	{
		log_debug($_REQUEST);
		$this->Page = $_REQUEST['page'];
		$this->RowNum = $_REQUEST['rows'];
		//$this->xx = $_REQUEST['xx'];

		$page = $this->PackXml("page",$this->Page);
		$total = $this->PackXml("total",$this->Total?$this->Total:ceil($this->RowNum==0?0:count($this->Rows)/$this->RowNum));
		$records = $this->PackXml("records",$this->Records?$this->Records:(count($this->Rows)));

		$userdata = "";
		foreach( $this->Userdata as $name=>$val )
			$userdata .= $this->PackXml("userdata",$val,array('name'=>$name),false,false,true);

		$start = ($this->Page-1) * $this->RowNum;
		$end = min($start + $this->RowNum,count($this->Rows));
		log_debug("$start -> $end");
		$rows = "";
		for($i=$start; $i<$end; $i++)
		{
			$row = $this->Rows[$i];
			$cell = "";
			foreach( $row as $key=>$val )
            {
                if( is_array($val) )
                {
                    $attr = $val;
                    $val = $val['__value'];
                    unset($attr['__value']);
                }
                else
                   $attr = array();
				$cell .= $this->PackXml("cell",$val,$attr,false,false,true);
            }

			if( $this->IdField && isset($row[$this->IdField]) )
				$rows .= $this->PackXml("row",$cell,array('id'=>$row[$this->IdField]),true);
			else
				$rows .= $this->PackXml("row",$cell,array(),true);
		}

		header("Content-Type: text/xml");
		return "<?xml version='1.0' encoding='utf-8'?>\n".$this->PackXml("rows","{$page}{$total}{$records}{$userdata}{$rows}",array(),true);
	}

	function SaveCell()
	{
		$idx = $_REQUEST['id']-1;
		foreach( $_REQUEST as $name=>$value )
			if( isset($this->Rows[$idx][$name]) )
			{
				log_debug("storing value: $name => $value");
				$this->Rows[$idx][$name] = $value;
			}

		store_object($this);
		return "";
	}
}

?>