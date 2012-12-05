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

class GroupRight extends System_Model
{
	private $_rights = false;

	function __construct(&$datasource=null)
	{
		global $CONFIG;

		if( !isset($CONFIG['model']['groupright']) )
			$CONFIG['model']['groupright'] = 'group_rights';

		if( $datasource == null )
			$datasource = model_datasource($CONFIG['authorization']['datasource']);

		parent::__construct($datasource);
	}

	public function GetSchemaDefinition()
	{
		$res  = '
		<table name="'.$this->_table.'">

			<field name="group_id" type="I" size="10">
				<KEY/>
			</field>
			<field name="right_id" type="C" size="50">
				<KEY/>
			</field>

		</table>';
		return $res;
	}
}

?>