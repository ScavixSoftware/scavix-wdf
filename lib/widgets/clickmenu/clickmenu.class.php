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
 
class ClickMenu extends HtmlElement
{
	function __initialize($label)
	{
		parent::__initialize();

		$this->set("id",$this->_storage_id);
		$this->set("label",$label);
		$this->set("items",array());
		$this->set("cases",array());
	}

	function do_the_execution()
	{
		//parent::PreparePage($page);
		//system_die("x");
		//global $PAGE;

//		$page->addJs(jsFile('jquery.js'));
//		$page->addJs(jsFile('jquery/jquery.menu.js'));
//
//		$id = $this->_storage_id;
//		$page->addDocReady("ClickMenu_Init('#$id',".$id."_perform_click);");

		$cases = array();
		foreach( $this->vars['items'] as $item )
			$cases[] = $item->GetCaseStatement();
		$this->set("cases",$cases);

		//$id = $this->_storage_id;
		//$PAGE->addDocReady("ClickMenu_Init('#$id',".$id."_perform_click);");
		//log_debug("ClickMenu: ".$this->_storage_id);

		return parent::do_the_execution();
	}

    static function __js()
    {
        return array(jsFile('jquery/jquery.menu.js'));
    }

	function addItem($label,$action)
	{
		$this->vars['items'][] = new ClickMenuItem($label,$action);
	}

	function addSpacer()
	{
		$this->vars['items'][] = new ClickMenuItem("","");
	}
}

?>