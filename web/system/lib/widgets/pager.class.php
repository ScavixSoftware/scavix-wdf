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
 
class Pager extends Template
{
	function __initialize($pageCount,$currentPage,$target_object="",$prefix="TXT_PAGE",$suffix="")
	{
		parent::__initialize();

		if( is_object($target_object) )
		{
			if( !isset($target_object->_storage_id) )
				system_die("Trying to use an object without storage_id as Pager target.");

			$this->set("ajax",true);
			$this->set("ajax_target",$target_object->_storage_id);
			$linkParameter = "";
		}
		else
		{
			$linkParameter = array();
			foreach( $_GET as $key=>$val )
				if( $key != "currentpage" && $key != "tabid" )
					$linkParameter[] = "$key=$val";
			foreach( $_POST as $key=>$val )
				if( $key != "currentpage" && $key != "tabid" )
					$linkParameter[] = "$key=$val";
			$linkParameter = implode("&",$linkParameter);
		}

		$urlbase  = "?$linkParameter";
		if( $urlbase != "?" )
			$urlbase .= "&";
		$urlbase .= "currentpage=";

		$anchors = array();

		if( $currentPage > 1 )
		{
			$anchors[] = new Anchor("$urlbase".(1), "TXT_LINK_FIRST");
			$anchors[] = new Anchor("$urlbase".($currentPage-1), "TXT_LINK_PREV");
		}

		$start = 1;
		$maxpages = 21;
		while( $pageCount > $maxpages && $currentPage > $start + $maxpages / 2 )
		{
			$start++;
		}

		for( $i=$start; $i<=$pageCount && $i<($start+$maxpages); $i++ )
		{
			if( $i == $currentPage )
				$anchors[] = "&nbsp;<b>$i</b>&nbsp;";
			else
				$anchors[] = new Anchor("$urlbase$i", "&nbsp;$i&nbsp;");
		}

		if( $currentPage < $pageCount )
		{
			$anchors[] = new Anchor("$urlbase".($currentPage+1), "TXT_LINK_NEXT");
			$anchors[] = new Anchor("$urlbase".($pageCount), "TXT_LINK_LAST");
		}

		$this->set("prefix", $prefix);
		$this->set("anchors", $anchors);
		$this->set("suffix", $suffix);
		$this->set("itemcount", $target_object->_dataSet->_maxRecordCount);
	}
}

?>