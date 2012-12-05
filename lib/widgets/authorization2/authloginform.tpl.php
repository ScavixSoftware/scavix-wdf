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
 
?>
<form class="authloginform" method="post" action="<?=$action?>">
	<table>
		<thead>
			<tr><td colspan="2">TXT_LOGIN_TITLE</td></tr>
		</thead>
		<tbody>
			<tr>
				<td>TXT_LOGIN_USERNAME</td>
				<td><input name="username" type="text"/></td>
			</tr>
			<tr>
				<td>TXT_LOGIN_PASSWORD</td>
				<td><input name="password" type="password"/></td>
			</tr>
			<tr>
				<td colspan="2">
					<input name="keeplogin" id="keeplogin" value="1" type="checkbox"/>
					<label for="keeplogin">TXT_LOGIN_KEEP</label>
				</td>
			</tr>
			<tr>
				<td colspan="2" class="buttonbar">
					<input type="submit" value="BTN_LOGIN"/>
				</td>
			</tr>
		</tbody>
	</table>
</form>