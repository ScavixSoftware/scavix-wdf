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
 
class AutoReloadExtender extends ControlExtender
{
	var $ItemsPerPage = 20;
	var $CurrentPage = 1;
	var $Container = false;
	var $AutoReload = false;
	
	function __initialize(&$control,&$container,$itemsperpage=20,$autoreload=false,$showbutton=false)
	{
		parent::__initialize($control);

		$this->Container = $container;
		$this->ItemsPerPage = $itemsperpage;
		$this->AutoReload = $autoreload;
		$id = $this->ExtendedControl->id;
		
		$oi = resFile('jquery-ui/images/ui-bg_diagonals-small_0_aaaaaa_40x40.png');
		$li = resFile('loading.gif');

		$control->script("$('#{$this->ExtendedControl->id}').control('AE_Init','$oi','$li','$autoreload');");

		if( !$autoreload && $showbutton )
			$this->Render();
		else if( $autoreload )
			$control->script("$('#$id').control('AE_AutoReload');");
	}

	function Render()
	{
		$id = $this->ExtendedControl->id;

		$ReloadButton = new Button("TXT_RELOAD");
		$ReloadButton->id = "reload_table";
		$ReloadButton->name = "reload_table";
		$ReloadButton->onclick = "$('#$id').control('AE_ReloadPage');";

		$ui = $this->Container;
		$ui->content( $ReloadButton );
	}
	
	function ReloadPage()
	{
	}
}

?>
