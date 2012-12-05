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
 
/**
 * A simple email blacklist
 *
 * @author steffenm
 */
class EmailBlacklist extends Model 
{
	
	function GetTableName(){ return 'email_blacklist'; }
	
	/**
	 * Verify an email and check it againt the blacklist.
	 * @param type $email
	 * @param $ds Optional datasource parameter
	 * @return bool true if the email is valid, otherwise false.
	 */
	public static function CheckEmail($email,$ds=false)
	{
		if( !mail_validate($email) )
			return false;

		if( !$ds )
			return true;

		$id = $ds->CacheDLookUp("id","email_blacklist","? LIKE CONCAT('%',mail)",$email);
		if( !$id )
			return true;

		$ds->ExecuteSql("UPDATE email_blacklist SET hits=hits+1 WHERE id=?",$id);
		return false;
	}
}

