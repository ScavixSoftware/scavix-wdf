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
 
class PagerExtender extends ControlExtender
{
	var $Total = 0;
	var $ItemsPerPage = 20;
	var $CurrentPage = 1;
	var $Container = false;

	var $MaxPagesToShow = 21;
	var $ShowFirstLast = true;
	var $ShowPrevNext = true;

	function __initialize(&$control,&$container,$itemsperpage=20)
	{
		parent::__initialize($control);
		$this->Container = $container;
		$this->ItemsPerPage = $itemsperpage;

		$oi = skinFile('jquery-ui/images/ui-bg_diagonals-small_0_aaaaaa_40x40.png');
		$li = skinFile('images/loading.gif');

		$control->script("$('#{$this->ExtendedControl->id}').control('PE_Init','$oi','$li');");
	}

	private function createAnchor($page,$label=false)
	{
		$label = $label?$label:$page;
		if( $page == $this->CurrentPage )
			return "<span class='current'>$label</span>";

		if( !isset($this->ExtendedControl->id) || $this->ExtendedControl->id == "" )
			system_die("Object needs to be stored to use pager functionality!");

		$id = $this->ExtendedControl->id;
		$res = new Anchor("javascript: void(0);",$label);
		$res->onclick = "$('#$id').control('PE_GotoPage',$page);";
		return $res;
	}

	private function Render()
	{		
		$ui = $this->Container;
		$ui->_content = array();

		$pages = ceil($this->Total / $this->ItemsPerPage);
//		log_debug("$pages = ceil($this->Total / $this->ItemsPerPage);");
		if( $pages < 2 )
		{
//			log_debug("PagerExtender: No pages to render");
			return;
		}
		$ui->addClass("pager");

		if( $this->CurrentPage > 1 )
		{
			if( $this->ShowFirstLast )
				$ui->content( $this->createAnchor(1,"|&lt;") );
			if( $this->ShowPrevNext )
				$ui->content( $this->createAnchor($this->CurrentPage-1,"&lt;") );
		}

		$start = 1;
		//$this->MaxPagesToShow = 21;
		while( $pages > $this->MaxPagesToShow && $this->CurrentPage > $start + $this->MaxPagesToShow / 2 )
		{
			$start++;
		}

		for( $i=$start; $i<=$pages && $i<($start+$this->MaxPagesToShow); $i++ )
		{
			$ui->content( $this->createAnchor($i) );
		}

		if( $this->CurrentPage < $pages )
		{
			if( $this->ShowPrevNext )
				$ui->content( $this->createAnchor($this->CurrentPage+1,"&gt;") );
			if( $this->ShowFirstLast )
				$ui->content( $this->createAnchor($pages,"&gt;|") );
		}
	}

	function PreRender()
	{
		if( isset($this->ExtendedControl->ResultSet) )
			$this->Total = $this->ExtendedControl->ResultSet->MaxRecordCount();
		$this->Render();
	}

	/**
	 * @attribute[RequestParam('number','int')]
	 */
	function GotoPage($number)
	{
		$this->CurrentPage = $number;
	}
}

?>
