<?
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
 
/** @addtogroup System
* @{
* @addtogroup System_Model Model
* @{ */

class Right extends System_Model
{
	private $_subRights = false;

	function __construct(&$datasource=null)
	{
		global $CONFIG;

		if( !isset($CONFIG['model']['right']) )
			$CONFIG['model']['right'] = 'rights';

		if( $datasource == null )
			$datasource = model_datasource($CONFIG['authorization']['datasource']);

		parent::__construct($datasource);
	}

	public function GetSchemaDefinition()
	{
		$res  = '
		<table name="'.$this->_table.'">
			<desc>This table holds all rights</desc>

			<field name="right_id" type="C" size="50">
				<KEY/>
			</field>
			<field name="parent_id" type="C" size="50"/>
			<field name="label" type="C" size="255">
				<UNIQUE/>
			</field>
			<field name="parent_order" type="I" size="11"/>
			<field name="child_order" type="I" size="11"/>

		</table>';
		return $res;
	}

	function SubRights()
	{
		if( !$this->_subRights )
		{
			global $CONFIG;
			$type = $CONFIG['authorization']['right_type'];
			$ridf = $CONFIG['authorization']['right_rightid_field'];
			$pidf = $CONFIG['authorization']['right_parentid_field'];

			$this->_subRights = $this->DataSource->Select($type,"$pidf=?0",array($this->$ridf));
		}
		return $this->_subRights;
	}

	function Matches($right)
	{
		global $CONFIG;
		$type = $CONFIG['authorization']['right_type'];
		$ridf = $CONFIG['authorization']['right_rightid_field'];

		if( strtolower($this->$ridf) == 'all' )
			return true;

		if( is_object($right) && strtolower(get_class($right)) == $type )
			$right = $right->$ridf;

		$right = strtolower($right);
		if( $right == strtolower($this->$ridf) )
			return true;
        //log_debug("right mismatch: $right != ".strtolower($this->$ridf));
	}

	function RightIds()
	{
		global $CONFIG;
		$ridf = $CONFIG['authorization']['right_rightid_field'];

		$res = array($this->$ridf);
		foreach( $this->SubRights() as $r )
			$res = array_merge($res,$r->RightIds());
		return $res;
	}
}

?>