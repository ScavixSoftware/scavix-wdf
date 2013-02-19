<?
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2012 Scavix Software Ltd. & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

class uiDatabaseTable extends DatabaseTable
{
	function __initialize($datasource,$datatype=false)
	{
		parent::__initialize($datasource,$datatype);
		$this->class .= " ui-widget-content ui-corner-all";
		$this->css("border-collapse","separate");
	}

	function &Header()
    {
        $res = parent::Header();
		$res->RowOptions = array("tr_class"=>"ui-widget-header ui-corner-all");
		return $res;
    }

	function WdfRender()
    {
		if( $this->footer )
		{
			$controls = array();
			system_find($this->footer,"Tr",$controls);
			foreach( $controls as $c )
			{
				$c->class = isset($c->class)?$c->class." ui-corner-all":"ui-corner-all";
			}
		}
		return parent::WdfRender();
	}
}
