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
 
class AjaxAction
{
	private static function _data($data)
	{
		if( $data )
		{
			if( is_string($data) )
				return "$data";
			if( is_array($data) || is_object($data) )
				return json_encode($data);
			WdfException::Raise("Invalid argmuent: 'data' should be string, array or object, but '".gettype($data)."' detected");
		}
		return '';
	}
	
	public static function Url($controller,$event='')
	{
		return ($controller instanceof Renderable)?"{$controller->_storage_id}/$event":"$controller/$event/";
	}
	
	public static function Post($controller,$event='',$data='',$callback='')
	{
		$q = self::Url($controller,$event);
		$data = self::_data($data);
		if( $data ) $data = ",$data";
		if( $callback ) $callback = ",$callback";
		return "wdf.post('$q'$data$callback);";
	}
	
	public static function Confirm($text_base,$controller,$event='',$data='')
	{
		$dlg = new uiConfirmation($text_base);
		$q = self::Url($controller,$event);
		$data = self::_data($data);
		$data = "var d = ".($data?$data:'{}')."; for(var n in $('#{$dlg->id}').data()) if( typeof $('#{$dlg->id}').data(n) == 'string') d[n] = $('#{$dlg->id}').data(n); ";
		$action = "$data".AjaxAction::Post($controller,$event,'d',$dlg->CloseButtonAction);
		$dlg->SetOkCallback($action);
		$_SESSION['ajax_confirm'][$text_base] = md5(time());
		$dlg->setData('confirmed', $_SESSION['ajax_confirm'][$text_base]);
		return $dlg;
	}
	
	public static function IsConfirmed($text_base)
	{
		if( isset($_SESSION['ajax_confirm'][$text_base]) && $_SESSION['ajax_confirm'][$text_base] == Args::request('confirmed',false) )
			return true;
		return false;
	}
}
