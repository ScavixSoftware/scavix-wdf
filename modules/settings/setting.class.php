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
 
class System_Model_Setting extends System_Model
{
	function __construct(&$datasource=null)
	{
		global $CONFIG;

		if( !isset($CONFIG['model']['system_model_setting']) )
			$CONFIG['model']['system_model_setting'] = 'settings';

		if( $datasource == null )
			$datasource = model_datasource($CONFIG['authorization']['datasource']);

		parent::__construct($datasource);
	}

	/**
	 * Returns the database schema definition.
	 *
	 * @see System_Model::GetSchemaDefinition
	 */
	public function GetSchemaDefinition()
	{
		$res  = '
		<table name="'.$this->_table.'">
			<field name="section" type="C" size="50"><KEY/></field>
			<field name="name" type="C" size="50"><KEY/></field>
			<field name="value" type="X"/>
		</table>';
		return $res;
	}
}

?>