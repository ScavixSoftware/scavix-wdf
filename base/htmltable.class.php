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
 
class HtmlTable extends HtmlElement
{
	var $Rows = array();
	function __initialize()
	{
		parent::__initialize("table");
		$this->set("id",$this->_storage_id);
		$this->_logicVars[] = "Rows";
	}

	function __sleep()
	{
		$res = array();
		foreach( parent::__sleep() as $key )
			if( $key != 'Rows' )
				$res[] = $key;
		return $res;
	}

	function &AddRow()
	{
		$row = new HtmlTableRow();
		$this->Rows[] =& $row;
		//$this->set("rows", $this->Rows);
		return $row;
	}

	function PreparePage(&$page)
	{
		foreach($this->Rows as &$row)
			$row->PreparePage($page);
		$this->set("rows", $this->Rows);
	}
}

class HtmlTableRow extends HtmlElement
{
	var $Cells = array();
	function __initialize()
	{
		parent::__initialize("tr");
		$this->_logicVars[] = "Cells";
	}

	function &AddCell(&$content = null)
	{
		$cell = new HtmlTableCell($content);
		$this->Cells[] =& $cell;
		return $cell;
	}

	function PreparePage(&$page)
	{
		foreach($this->Cells as &$cell)
			$cell->PreparePage($page);
	}
}

class HtmlTableCell extends HtmlElement
{
	function __initialize($content = null)
	{
		parent::__initialize("td");
		if($content !== null)
			$this->content($content);
	}

	function addContent($content)
	{
		$this->content($content);
	}

	function PreparePage(&$page)
	{
		foreach($this->_content as $cnt)
		{
			if( is_object($cnt) && system_method_exists($cnt, "PreparePage"))
				$cnt->PreparePage($page);
		}
	}
}

?>