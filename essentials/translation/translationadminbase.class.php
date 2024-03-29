<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2012-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Translation;

use ScavixWDF\Admin\SysAdmin;
use ScavixWDF\Base\AjaxResponse;
use ScavixWDF\Base\Template;

/**
 * Base class for translation handlers.
 * 
 * @attribute[NoMinify]
 */
abstract class TranslationAdminBase extends SysAdmin
{
	function __construct($title = "", $body_class = false)
	{
		parent::__construct($title, $body_class);
	}
    
    /**
     * @internal Download all the translation files in a ZIP archive
     */
    function Download()
    {
        global $CONFIG;
        
        $zip = new \ZipArchive();
        $filename = tempnam(system_app_temp_dir(),'translations_');
//        log_debug("Tempfile: $filename");
        if( $zip->open($filename, \ZipArchive::CREATE) !== true )
            die("cannot open <$filename>");
        foreach( glob("{$CONFIG['translation']['data_path']}*.inc.php") as $fn )
            $zip->addFile($fn,basename($fn));
        $zip->close();
        
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false);
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=Translations.zip;" );
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($filename));
        readfile($filename);
        unlink($filename);
        die('');
    }
	
    /**
	 * @internal New string page
     */
    function NewStrings()
    {
        $this->setTitle('New strings');
		$this->content("<p>Default language is <b>'{$GLOBALS['CONFIG']['localization']['default_language']}'</b>, so please create new strings accordingly.</p>");
        $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
		translation_add_unknown_strings([]);
		foreach( $ds->Query('wdf_unknown_strings')->all() as $row )
        {
			$ns = Template::Make('translationnewstring');
            $ns->set_vars($row->AsArray());
            $ns->set('data',$ds->Query('wdf_unknown_strings_data')->eq('term',$ns->term)->enumerate('value',false,'name'));
            $this->content($ns);
        }
		if( !isset($row) )
			$this->content("<p>No unknown strings found</p>");
		$this->content("<br style='clear:both'/><br/><h1>Manually add string</h1>");
		Template::Make('translationnewstringmanually')->appendTo($this);
    }
    
    /**
	 * @internal Delete a string
     * @attribute[RequestParam('term','string')]
     */
    function DeleteString($term)
    {
        $ds = model_datasource($GLOBALS['CONFIG']['translation']['sync']['datasource']);
        $ds->ExecuteSql("DELETE FROM wdf_unknown_strings WHERE term=?",$term);
        return AjaxResponse::None();
    }
	
	/**
	 * Fetch strings from the translation system into the project.
	 * 
	 * They will be stored in the strings directory as PHP files for easy inclusion.
	 * @param array $languages Array of language codes to be fetched
	 * @return void
	 */
	abstract function Fetch($languages = false);
    
    /**
	 * Import strings from the XX.inc.php translation system into the database
	 * 
	 * @param array $languages Array of language codes to be fetched
	 * @param bool $clearbeforeimport If true, clears all strings before importing new ones
	 * @return void
	 */
	abstract function Import($languages = false, $clearbeforeimport = false);
	
	/**
	 * Creates a new string from unknowns table.
	 * 
	 * This transforms an unknown string (found in sourcecode) into a full translation term that can be 
	 * edited in the translation system.
	 * @param string $term The identifier (like TXT_MYSTRING1)
	 * @param string $text Content, in the default application language
	 * @return void
	 */
	abstract function CreateString($term,$text);
}