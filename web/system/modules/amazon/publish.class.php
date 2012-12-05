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
 
/*
    Document   : Publish
    Created on : Feb 23, 2009, 5:50:03 PM
    Author     : Florian A. Talg
    Description: Publish js and skinfiles into static folder to sync with amazon cloud
*/
class Publish extends HtmlPage
{
	var $JsFiles = array();
	var $StaticProjectDir;

	function __initialize()
	{
		parent::__initialize();

		global $CONFIG;
		$this->StaticProjectDir = $CONFIG['amazon']['local_cloud_path'];
		$this->PublishSystemSkin();
		$this->PublishSystemJsFiles();
		$skinfiles = $this->GetProjectSpecificSkinFiles();
		$this->addContent("Publish all static files:<br/>");
		$this->addContent("<form action='?page=publish&event=publishfiles' method='post'>");

		$PublishAll = new Button("Publish All");
		$PublishAll->onclick = "$('form').submit()";
		$this->addContent($PublishAll);
		$this->addContent("<br/>");

		foreach( $skinfiles as $file)
		{
			$lab = new Label($file);
			$this->addContent($lab);
			$this->addContent("<br/>");
		}
		$this->addContent("</form>");
	}
	/**
	 *
	 * At the moment this function is only used to copy all skin and js files to static folder
	 */
	function PublishFiles($files=false)
	{
		global $CONFIG;
		$project_skins_path = realpath($CONFIG['system']['path_root']."/".$CONFIG['skins']['base_dir']);

		if( !$files )
			$this->dir_copy($project_skins_path, $this->StaticProjectDir);
	}

	/**
	 *	Copies /system/skin to each project specific cloud folder
	 *	to be replaced possibly with project-skin files
	 *
	 * @global <type> $CONFIG
	 */
	function PublishSystemSkin()
	{
		global $CONFIG;
		$project_skins_path = realpath($CONFIG['system']['path_root']."/".$CONFIG['skins']['base_dir']);
		$static_skin_folder = $this->StaticProjectDir."/";

		if( is_dir($static_skin_folder) )
		{
			$this->rmdir_recurse($static_skin_folder);
			rmdir($static_skin_folder);
		}
		mkdir($static_skin_folder);

		if( is_dir($project_skins_path) )
		{
			$folder_content =  glob($project_skins_path."/*",GLOB_ONLYDIR);
			foreach( $folder_content as $fc )
			{
				if( is_dir($fc) )
				{
					$system_skin_folder = dirname(__FILE__)."/../../skin";
					$new_static_skin_folder = $this->StaticProjectDir."/".basename($fc);
					$this->dir_copy($system_skin_folder, $new_static_skin_folder);
				}
			}
		}
	}
	/**
	 *	Copies /system/js to cloud folder to be replaced possibly with project-js files
	 *
	 * @global <type> $CONFIG
	 */
	function PublishSystemJsFiles()
	{
		global $CONFIG;
		$system_jspath = realpath($CONFIG['system']['path_root']."/js");
		$project_jspaths = $CONFIG['javascript']['dirs'];

		if( is_dir($system_jspath) )
			$this->dir_copy($system_jspath, $this->StaticProjectDir);

		if( !is_array($project_jspaths) )
			$project_jspaths = array($project_jspaths);

		$jspaths = array();
		foreach( $project_jspaths as $project_jspath )
		{
			$jspath = realpath($CONFIG['system']['path_root']."/../".$project_jspath);
			if( is_dir($jspath) )
				$this->dir_copy($jspath, $this->StaticProjectDir);
		}
	}

	/**
	 * Replaces system js and skinfiles in clod folder with
	 * projectspecific files
	 *
	 * @global <type> $CONFIG
	 * @return <type>
	 */
	function GetProjectSpecificSkinFiles()
	{
		global $CONFIG;
		$project_skins_path = realpath($CONFIG['system']['path_root']."/".$CONFIG['skins']['base_dir']);
		$result= array();

		if( is_dir($project_skins_path) )
		{
			$folder_content = glob($project_skins_path."/*",GLOB_ONLYDIR);
			foreach( $folder_content as $fc )
				$this->get_folder_content($fc, $result);
		}
		return $result;
	}

	function get_folder_content($folder_name, &$result=array())
	{
		if( !is_dir($folder_name) )
			return false;

		$files = glob("$folder_name/*");
		if( !$files )
			return;

		foreach( $files as $file )
		{
			if( preg_match('/\.svn/',$file) )
				continue;

			if( preg_match('/\Thumbs.db/',$file) )
				continue;
				
			if( is_dir($file) )
			{
				$this->get_folder_content($file,$result);
				continue;
			}
			$result[] = $file;
		}
	}

	function dir_copy($srcdir,$dstdir)
	{
		$num = 0;
		$fail = 0;
		
		if(!is_dir($dstdir))
			mkdir($dstdir);

		if($curdir = opendir($srcdir))
		{
			while($file = readdir($curdir))
			{
				if($file != '.' && $file != '..')
				{
					$srcfile = $srcdir . '/' . $file;
					$dstfile = $dstdir . '/' . $file;
					if(is_file($srcfile))
					{
						if(is_file($dstfile))
							$ow = filemtime($srcfile) - filemtime($dstfile);
						else
							$ow = 1;
						if($ow > 0)
						{
							if(copy($srcfile, $dstfile))
							{
								touch($dstfile, filemtime($srcfile));
								$num++;
								chmod($dstfile, 0777);
							}
							else
							{
								log_debug("Error: File '$srcfile' could not be copied!");
								$fail++;
							}
						}
					}
					else if(is_dir($srcfile))
					{
						if( basename($srcfile) == ".svn")
							continue;
						else
							$this->dir_copy($srcfile, $dstfile);
					}
				}
			}
			closedir($curdir);
		}
		log_debug("$num files copied $fail failed");
	}

	function rmdir_recurse($path)
	{
		$path= rtrim($path, '/').'/';
//		log_debug("rmdir_recurse");
		$handle = opendir($path);
		for (;false !== ($file = readdir($handle));)
			if($file != "." and $file != ".." )
			{
				$fullpath= $path.$file;
				if( is_dir($fullpath) )
				{
					$this->rmdir_recurse($fullpath);
					rmdir($fullpath);
				}
				else
				  unlink($fullpath);
			}
		closedir($handle);
	}

}
?>
