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
 
class DataTable extends Template
{
	var $DataSource = null;
	var $Type = null;

	var $Actions = array();
	var $Header = array();
	var $Fields = array("*");
	var $Where = "";
	var $OrderBy = "";
	var $GroupBy = "";
	var $ItemsPerPage = 20;
	var $CurrentPage = 1;
	var $CountPages = 0;

	var $_id;
	var $_dataSet = null;
	var $_formatDateFunction = false;

	var $SuppressPager = false;
	var $ExtraFooter = array();
	var $RowDataCallback = null;
	var $ColAligns = array();
	var $InfoButtonTextId = null;
	var $Float = null;
	var $Data = null;
	var $ShowNumOfEntries = false;

	function __initialize(&$datasource=null,$type=null,$actions=array())
	{
		parent::__initialize();
		$this->translate = false;
		$this->set("id",$this->_storage_id);

		if( $datasource != null )
			$this->DataSource = $datasource;
		if( $type != null )
			$this->Type = $type;
		$this->Actions = $actions;
		store_object($this);
	}

	function __sleep()
	{
		$fields = array_keys(get_object_vars($this));
		$res = array();
		foreach( $fields as $f )
			if( $f != "_dataSet" )
				$res[] = $f;
		return $res;
	}
//
//	function __wakeup()
//	{
//		log_debug('__wakeup','DATATABLE');
//		$this->DataSource = model_datasource($this->DataSource);
//	}

	static function __js()
	{
		return array(); //jsFile('jquery/jquery.dimensions.js'));
	}

	function PreparePage(&$page)
	{
		$page->IncludeFiles("pager");
//		$page->addJs(jsFile('jquery.js'));
//		$page->addJs(jsFile('datatable.js'));
//		$page->addJs(jsFile('jquery/jquery.dimensions.js'));
		//return parent::PreparePage($page);
	}

	function GotoPage()
	{
		$this->CurrentPage = $_REQUEST['currentpage'];
		$this->set("force_creation",false);
	}

	function Change()
	{
		$obj = $this->DataSource->CreateInstance($this->Type);
		$fields = $obj->GetAttributeNames();

		$args = ajax_args();
		$this->Where = array();
		$last_was_op = false;
		foreach( $args as $key=>$val )
		{
			$val = trim($val);
			if( $val == "" || $val == "%" || $val == "%%" )
				continue;

			if( substr($key,0,8) == 'operator' && count($this->Where) > 0 )
			{
				$this->Where[] = "$val";
				$last_was_op = true;
			}
			elseif( in_array($key,$fields) )
			{
				$last_was_op = false;
				if( strpos($val,"%") === false )
					$this->Where[] = "$key='$val'";
				else
					$this->Where[] = "$key LIKE '$val'";
			}
		}
		if( $last_was_op )
			array_pop($this->Where);

		$this->Where = implode(" ",$this->Where);
		$this->CurrentPage = 1;
		$this->set("force_creation",false);
	}

	function CreateNew()
	{
		$res = clone $this;
		$res->Change();
		$res->set("force_creation",true);
		return $res;
	}

	protected function ExecuteSql($sql,$prms=array())
	{
		global $ADODB_COUNTRECS;

		$sql = $this->DataSource->PrepareWhere($sql);

		$savec = $ADODB_COUNTRECS;
		$ADODB_COUNTRECS = true;
		$this->_dataSet = $this->DataSource->DB->PageExecute($sql,$this->ItemsPerPage,$this->CurrentPage,$prms);
		if( $this->DataSource->DB->ErrorMsg() )
			log_error($this->DataSource->DB->ErrorMsg());
		$ADODB_COUNTRECS = $savec;
	}

	function GetData()
	{
		if( is_array($this->Fields) )
			$fields = implode(",",$this->Fields);
		else
			$fields = $this->Fields;

		$table = $this->DataSource->TableForType($this->Type);

		$sql = "SELECT $fields FROM `$table`";
		if( $this->Where != "" )
			$sql .= " WHERE ".$this->Where;
		if( $this->GroupBy != "" )
			$sql .= " GROUP BY ".$this->GroupBy;
		if( $this->OrderBy != "" )
			$sql .= " ORDER BY ".$this->OrderBy;

		$this->ExecuteSql($sql);
		$fieldinfo = $this->_dataSet->FieldTypesArray();

		$data = array();
		while( !$this->_dataSet->EOF )
		{
			if( count($this->Header) == 0 )
			{
				foreach( $this->_dataSet->fields as $key=>$val )
					if( !is_integer($key) )
						$this->Header[] = $key;

				if( count($this->Actions) > 0 )
					$this->Header[] = "&nbsp;";
			}
			$row = array();
			foreach( $this->_dataSet->fields as $key=>$val )
			{
				if( is_integer($key) )
					continue;

				$is_dt = false;
				if( $this->_formatDateFunction )
				{
					foreach( $fieldinfo as $fi )
					{
						if( $fi->name == $key )
						{
							$is_dt = $fi->type == 'datetime';
							break;
						}
					}
				}

				if( $is_dt )
					$row[$key] = call_user_func($this->_formatDateFunction,$val);
				else
					$row[$key] = $val;
			}

			if( count($this->Actions) > 0 )
			{
				$ra = array();
				foreach($this->Actions as $act)
				{
					$tmp = $act->href;
					$act->href = $act->href.(substr($act->href, 0, 11) == "javascript:" ? "" : "&row=".urlencode(serialize($row)));
					$ra[] = $act->execute();
					$act->href = $tmp;
				}
				$row['__actions'] = implode("&nbsp;",$ra);
			}
			elseif( system_method_exists($this,'GetActions') )
			{
				$ra = array();
				foreach($this->GetActions($row) as $act)
					$ra[] = $act->execute();
				$row['__actions'] = implode("&nbsp;",$ra);
			}
			elseif($this->RowDataCallback !== null)
				system_call_user_func_array_byref($this, 'RowDataCallback', $a = array(&$row));
			$data[] = $row;

			$this->_dataSet->MoveNext();
		}


		return $data;
	}

	function do_the_execution()
	{
		unset( $this->vars['pager']);

		if( system_method_exists($this,"GetHeader") )
		{
			$this->Header = $this->GetHeader();
		}
		elseif( count($this->Header) < 1 )
		{
			$this->Header = array();
		}

		$this->Data = $this->GetData();

		$this->set("header",$this->Header);
		$this->set("data",$this->Data);
		$this->set("extrafooter",$this->ExtraFooter);
		if(isset($this->InfoButtonTextId))
			$this->set("infobuttontextid", getString($this->InfoButtonTextId));
		$this->set("aligns", $this->ColAligns);
		$this->set("float", $this->Float);

		if( !$this->SuppressPager && ($this->ShowNumOfEntries || $this->_dataSet->LastPageNo() > 1 ))
		{
			$pager = new Pager($this->_dataSet->LastPageNo(),$this->_dataSet->AbsolutePage(),$this);
			if( isset($this->vars['force_creation']) )
				$pager->set("force_script",true);
			$this->set("pager",$pager);
		}
		return parent::do_the_execution();
	}

	function SetRowDataCallback($object, $funcname)
	{
		$this->RowDataCallback = array($object, $funcname);
	}
}

?>