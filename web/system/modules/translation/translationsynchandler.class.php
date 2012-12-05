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
 
class TranslationSyncHandler extends Template implements ICallable
{
	var $Salt = "BIIg8@l0!cEBylL!f@V%";
	var $Result = array();
	
	function __construct()
	{
		parent::__construct();
		$this->set("content",$this->Result);
	}
	
	private function CheckSecret()
	{
        $check = md5($_SERVER['REMOTE_ADDR'].date("Ymd").$this->Salt);
        $secret_word = isset($_SERVER['HTTP_X_SYNC_SECRET_WORD'])
			?$_SERVER['HTTP_X_SYNC_SECRET_WORD']
			:(isset($_SERVER['X_SYNC_SECRET_WORD'])?$_SERVER['X_SYNC_SECRET_WORD']:"");
        return $check === $secret_word;
	}
	
	function UploadTranslation()
	{
        global $CONFIG;
		if( !$this->CheckSecret() && !$GLOBALS['IS_DEVELOPSERVER'] )
		{
			log_error("Access denied");
			$this->set("content", "Access denied");
			return;
		}
		
        $path = $CONFIG['translation']['data_path'];
		$is_apc = function_exists('apc_cache_info');
		
		if( isset($_FILES)  && is_array($_FILES) )
		{
            foreach( $_FILES as $lang=>$upload )
			{
				if( $upload['error'] != 0 )
					continue;
				
				$target = $path.$upload['name'];
				if( file_exists($target) )
				{
					if( file_exists($target.".bak") )
						unlink($target.".bak");
					rename($target, $target.".bak");
				}
				
				move_uploaded_file($upload['tmp_name'], $path.$upload['name']);
				
				// remove backup
				if( file_exists($target) )
				{
					// all ok. remove backup
					if( file_exists($target.".bak") )
						unlink($target.".bak");
				}
				else
				{
					// saving went wrong. restore backup
					rename($target.".bak", $target);
				}
				
				if( $is_apc )
				{
					$ci = apc_cache_info('user');
					foreach( $ci['cache_list'] as $item )
					{
						if( strpos($item['info'],"translation_known_constants" !== false ) ||
						    strpos($item['info'],"lang_{$lang}_") === false )
							apc_delete($item['info']);
					}
				}
				
				$this->Result[$lang] = my_var_export($upload);
			}
		}
				
		$this->set("content",$this->Result);
	}
}