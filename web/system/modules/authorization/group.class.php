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

class Group extends System_Model
{
	private $_rights = false;

	function __construct(&$datasource=null)
	{
		global $CONFIG;

		if( !isset($CONFIG['model']['group']) )
			$CONFIG['model']['group'] = 'groups';

		if( !isset($CONFIG['model']['group_id_field']) )
			$CONFIG['model']['group_id_field'] = 'id';

		if( $datasource == null )
			$datasource = model_datasource($CONFIG['authorization']['datasource']);

		parent::__construct($datasource);
	}

	public function GetSchemaDefinition()
	{
		global $CONFIG;

		$res  = '
		<table name="'.$this->_table.'">
			<desc>This table holds all user groups</desc>

			<field name="'.$CONFIG['model']['group_id_field'].'" type="I" size="10">
				<KEY/><AUTOINCREMENT/><UNSIGNED/>
			</field>
			<field name="label" type="C" size="255">
				<UNIQUE/>
			</field>

		</table>';
		return $res;
	}

	function Rights()
	{
		if( !$this->_rights )
		{
			global $CONFIG;
			$type = $CONFIG['authorization']['right_type'];
			$ridf = $CONFIG['authorization']['right_rightid_field'];
			$pidf = $CONFIG['authorization']['right_parentid_field'];

                        $my_right_id = $this->$CONFIG['model']['group_id_field'];
			$grouprights = $this->DataSource->Select("GroupRight","group_id=?0",array($my_right_id));
                        
			$grtemp = array();
			foreach( $grouprights as $gr )
				$grtemp[] = $gr->right_id;

                        $this->_rights = $this->DataSource->Select($type,
				"$ridf IN('".implode("','",$grtemp)."')");
		}
		return $this->_rights;
	}

	function hasRight($right)
	{
		foreach( $this->Rights() as $r )
			if( $r->Matches($right) )
				return true;
		return false;
	}

	function RightIds()
	{
		global $CONFIG;
		$ridf = $CONFIG['authorization']['right_rightid_field'];

		$res = array();
                //log_debug("GROUP: ".var_export($this->Rights(),true));
		foreach( $this->Rights() as $r )
			$res = array_merge($res,$r->RightIds());
		return $res;
	}
}

?>