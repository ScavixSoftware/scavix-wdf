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

/**
 * Contains a user dataset.
 *
 * @author dschroeter
 * @copyright Copyright (c) 2008
 * @access public
 */
class User extends System_Model
{
	var $GroupArray = array();
	var $RightArray = array();
	var $_profile = null;

	/**
	 * Constructor.
	 * @internal
	 */
	function __construct(&$datasource=null)
	{
		global $CONFIG;

		if( !isset($CONFIG['model']['user']) )
			$CONFIG['model']['user'] = 'users';

		if( $datasource == null && isset($CONFIG['authorization']) && isset($CONFIG['authorization']['datasource']))
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
			<field name="user_id" type="I" size="10">
				<KEY/><AUTOINCREMENT/><UNSIGNED/>
			</field>
			<field name="user_name" type="C" size="255">
				<UNIQUE/>
			</field>
			<field name="user_pass" type="C" size="50"/>
			<field name="email" type="C" size="150"/>
			<field name="groups" type="C" size="255"/>
			<field name="rights" type="C" size="255"/>

		</table>';
		return $res;
	}

	function Load($where,$bindarr=false)
	{
		$res = parent::Load($where,$bindarr);
		if( $res )
			$this->InitProperties();
		return $res;
	}

	function InitProperties()
	{
		global $CONFIG;
		
		$this->GroupArray = array();
		$this->RightArray = array();

		$groups = explode(",",$this->groups);
		foreach( $groups as $group )
		{
			$obj = $this->DataSource->CreateInstance($CONFIG['authorization']['group_type'],"group_id=?0",array($group));
			$this->GroupArray[] = $obj;
		}

		$rights = explode(",",$this->rights);
                //log_debug($rights);
		foreach( $rights as $right )
		{
			$obj = $this->DataSource->CreateInstance($CONFIG['authorization']['right_type'],"right_id=?0",array($right));
			$this->RightArray[] = $obj;
		}
	}

	function hasRight($right_id)
	{
		$right_id = strtolower($right_id);

		foreach( $this->RightArray as $right )
			if( strtolower($right->right_id) == 'all' ||
			    strtolower($right->right_id) == $right_id )
				return true;

		foreach( $this->GroupArray as $group )
			if( $group->hasRight($right_id) )
				return true;

		return false;
	}

	function GroupNames()
	{
		$res = array();
		foreach( $this->GroupArray as $group )
			$res[] = $group->label;
		return implode(", ",$res);
	}

	function RightIds($include_user_rights=true,$include_group_rights=true)
	{
		$res = array();

		if( $include_user_rights )
			foreach( $this->RightArray as $r )
				$res[] = $r->right_id;
                                
		if( $include_group_rights )
			foreach( $this->GroupArray as $group )
				$res = array_merge($res,$group->RightIds());
		return $res;
	}

    /**
     * Copy/cascade the group rights to the user.
     */
    function CopyGroupRights()
    {
        $this->DataSource->ExecuteSql("INSERT INTO users_rights
        (SELECT DISTINCT user_id, right_id FROM groups_rights, users u
            WHERE user_id=?0 AND FIND_IN_SET(group_id, u.groups) )", array($this->user_id));
    }

//	function DisplayName()
//	{
//		$res = $this->Profile()->DisplayName();
//		//log_debug("DisplayName() -> $res");
//		return $res;
//	}

//	function Profile()
//	{
//		if( !isset($this->_profile) )
//		{
//			$this->_profile = new UserProfileModel();
//			if( !$this->_profile->Load("user_id=?0",$this->user_id) )
//				$this->_profile->user_id = $this->user_id;
//		}
//		return $this->_profile;
//	}
}

?>