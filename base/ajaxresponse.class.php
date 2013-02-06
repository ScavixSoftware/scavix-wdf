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
 
class AjaxResponse
{
	var $_data = false;
	var $_text = false;
	
	public static function None()
	{
		return new AjaxResponse();
	}
	
	public static function Js($script=false,$abort_handling=false)
	{
		$data = new stdClass();
		$data->script = is_array($script)?implode("\n",$script):$script;
		$data->script = "<script>{$data->script}</script>";
		$data->abort = $abort_handling;
		return AjaxResponse::Json($data);
	}
	
	public static function Json($data=null)
	{
		$res = new AjaxResponse();
		if( $data !== null )
			$res->_data = $data;
		return $res;
	}
	
	public static function Text($text=false)
	{
		$res = new AjaxResponse();
		if( $text !== false )
			$res->_text = $text;
		return $res;
	}
	
	public static function Renderable(Renderable $content)
	{
		$wrapped = new stdClass();

		$wrapped->html = $content->WdfRenderAsRoot();
		if( $content->_translate && system_is_module_loaded('translation') )
			$wrapped->html = __translate($wrapped->html);
		
		foreach( $content->__collectResources() as $r )
		{
			if( ends_with($r, '.css') )
				$wrapped->dep_css[] = $r;
			else
				$wrapped->dep_js[] = $r;
		}
		return AjaxResponse::Json($wrapped);
	}
	
	public static function Error($message,$abort_handling=false)
	{
		$data = new stdClass();
		$data->error = $message;
		$data->abort = $abort_handling;
		return AjaxResponse::Json($data);
	}
	
	public static function Redirect($controller,$event='',$data='')
	{
		$q = buildQuery($controller,$event,$data);
		return AjaxResponse::Js("wdf.redirect('$q');");
	}
	
	function Render()
	{
		if( $this->_data )
			$res = system_to_json($this->_data);
		elseif( $this->_text )
			$res = $this->_text;
		else
			return '""'; // return an empty string JSON encoded to not kill the app JS side
		return system_is_module_loaded("translation")?__translate($res):$res;
	}
}

?>