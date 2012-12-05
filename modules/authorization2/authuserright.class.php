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
 
class AuthUserRight extends System_Model
{
    function __construct(&$ds=null)
	{
		global $CONFIG;

		if( !isset($CONFIG['model']['authuserright']) )
			$CONFIG['model']['authuserright'] = 'user_rights';

		if(	is_null($ds) )
			$ds = model_datasource($CONFIG['authorization']['datasource']);
		parent::__construct($ds);
	}

	public function GetSchemaDefinition()
	{
		$res  = '
		<table name="'.$this->_table.'">
			<field name="user_id" type="I" size="10"><KEY/></field>
			<field name="route" type="C" size="255"><KEY/></field>
			<field name="forbidden" type="I" size="1"/>
		</table>';
		return $res;
	}
}
?>
