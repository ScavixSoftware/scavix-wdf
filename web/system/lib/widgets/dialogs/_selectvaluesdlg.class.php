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
 
class SelectValuesDlg extends Dialog
{
	var $choices = array();

	function __initialize($id,$title='TITLE_SELECT_VALUE')
	{
		parent::__initialize($id,$title);
		$this->addCloser("a.selectvaluesdlg_choice");
	}

	function addChoice($label,$actions = array())
	{
		$choice = new Anchor("#",$label);
		$actions[] = $this->CloseAction();
		$choice->onclick = implode("",$actions);
		$choice->addClass("selectvaluesdlg_choice");
		$this->choices[] = $choice->execute();
	}

	function do_the_execution()
	{
		$this->addContent("<ul class='selectvaluesdlg_choices'><li>");
		$this->addContent( implode("</li><li>",$this->choices) );
		$this->addContent("</li></ul>");
		return parent::do_the_execution();
	}
}

?>