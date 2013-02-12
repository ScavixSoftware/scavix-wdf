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
default_string("TXT_NOFILTER",'No filter');

class FilterExtender extends ControlExtender
{
	var $CurrentFilter = "";
	var $NoFilterText;
	var $Container = false;
	var $DefaultOnAddHandler = false;
	var $FieldsToFilter = array();
	var $HitCount = 0;

	function __initialize(&$control,&$container,$fieldsToFilter=false,$nofiltertext="TXT_NOFILTER")
	{
		parent::__initialize($control);
		$this->Container = $container;
		$this->NoFilterText = $nofiltertext;

		if( !$fieldsToFilter || !is_array($fieldsToFilter) || in_array("*",$fieldsToFilter) )
			WdfException::Raise("FilterExtender needs valid fieldnames to know where to filter");
		$this->FieldsToFilter = $fieldsToFilter;

		$oi = resFile('overlay.png');
		$li = resFile('images/loading.gif');

		$control->script("$('#{$this->ExtendedControl->id}').control('FE_Init','$oi','$li');");

		$control->OverrideExecuteSql($this, "OverriddenExecuteSql");

		if( !$control->Where && !$control->Having )
			$control->Where = "1=1";
	}

	function OverriddenExecuteSql(&$table,$sql,$prms)
	{
		if( $this->CurrentFilter != "" )
		{
			$orig = $sql;

			$tmp = array();
			foreach( $this->FieldsToFilter as $ftf )
				$tmp[] = "$ftf LIKE '%".$table->DataSource->EscapeArgument($this->CurrentFilter)."%'";

			$tmp1 = str_replace(" WHERE "," WHERE (",$table->Where).") AND (".implode(" OR ",$tmp).")";
			$tmp2 = str_replace(" HAVING "," HAVING (",$table->Having).") AND (".implode(" OR ",$tmp).")";

			$sql = str_replace($table->Where,$tmp1,$table->Sql);
			$sql = str_replace($table->Having,$tmp2,$sql);

			$rs = $table->DataSource->ExecuteSql("SELECT count(*) FROM (".$sql.") AS TESTING");
			$this->HitCount = $rs->fields['count(*)'];
			if( $this->HitCount == 0 )
				$sql = $orig;
		}

//		log_debug("Filtering: ".$sql);
//		log_debug($this->CurrentFilter);
		if( isset($table->ItemsPerPage) )
			$table->ResultSet = $table->DataSource->DB->PageExecute($sql,$table->ItemsPerPage,$table->CurrentPage,$prms);
		else
		{
			if( $table->CacheExecute )
				$table->ResultSet = $table->DataSource->CacheExecuteSql($sql,$prms);
			else
				$table->ResultSet = $table->DataSource->DB->Execute($sql,$prms);
		}
	}

	private function Render()
	{
		$ui = $this->Container;
		$ui->_content = array();
		if( $this->CurrentFilter == "" )
		{
			$tb = new TextInput( $this->NoFilterText );
			$tb->css("color","gray");
		}
		else
			$tb = new TextInput( $this->CurrentFilter );

		$id = $this->ExtendedControl->id;
		$tb->id = $id."_filter";
		$tb->class = "filterextender";
		$tb->title = $this->CurrentFilter;
//		$tb->onkeyup  = "if( $(this).val().length > 2 ){ $('#$id').control('FE_Filter',$(this).val());}";
//		$tb->onkeyup .= " else if( $(this).val().length == 0 ){ $('#$id').control('FE_Filter',''); }";
		$tb->onkeyup  = "$('#$id').control('FE_Filter',$(this).val());";
		$tb->onfocus = "if( $(this).val() == '{$this->NoFilterText}' ) $(this).val('').css('color','black');";
		$tb->onblur = "if( $(this).val() == '' ) $(this).val('{$this->NoFilterText}').css('color','gray');";

		if( $this->CurrentFilter != "" && $this->HitCount == 0 )
			$tb->class .= " no_hits";

		$ui->content($tb);
	}

	function PreRender()
	{
		$this->Render();
	}

	/**
	 * my comment
	 * @attribute[RequestParam('filter','string')]
	 */
	function FilterData($filter)
	{
		$this->CurrentFilter = $filter;
	}
}

?>
