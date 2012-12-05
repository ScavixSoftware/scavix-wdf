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
 
class ClickMenuItem extends Template
{
	function __initialize($label,$action,$icon="")
	{
		parent::__initialize();
		$this->set("id",$this->_storage_id);
		$this->set("label",$label);
		$this->set("action",$action);
		$this->set("icon",$icon);
	}

	function GetCaseStatement()
	{
		$label = $this->vars['label'];
		$action = $this->vars['action'];

		if( !ends_with($action,";") )
			$action .= ";";

		return "\ncase '$label': $action break;";
	}
}

?>