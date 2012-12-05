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
 
class TableBindingExtender extends ControlExtender
{
	var $Datasource = null;

	function __initialize(&$control,&$datasource=null)
	{
		parent::__initialize($control);
		$this->Datasource = $datasource;
	}

	function TB_Bind($table, $valuefield, $labelfield=false, $selectedvalue=false, $where = false, $order = false)
	{
		$fields = $valuefield;
		if( $labelfield )
			$fields .= ",".$labelfield;
		else
			$labelfield = $valuefield;

		$where = $where?"WHERE $where":"";
		$order = $order?"ORDER BY $order":"ORDER BY $labelfield";

		$selectedvalue = $selectedvalue!==false?strtolower($selectedvalue):false;

		$rs = $this->Datasource->ExecuteSql("SELECT $fields FROM $table $where $order");
		while( !$rs->EOF )
		{
			$selected = $selectedvalue!==false?strtolower($rs->fields[$valuefield])==$selectedvalue:false;
			$this->ExtendedControl->addOption($rs->fields[$valuefield],$rs->fields[$labelfield],$selected);
			$rs->MoveNext();
		}
	}
}

?>
