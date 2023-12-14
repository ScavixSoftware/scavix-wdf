<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) since 2023 Scavix Software GmbH & Co. KG
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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2023 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace ScavixWDF\Uploads;

use Exception;
use ScavixWDF\Base\DateTimeEx;
use ScavixWDF\Wdf;
use ScavixWDF\WdfDbException;
use ScavixWDF\WdfException;

/**
 * Provides methods for handling files.
 *
 * Use this in your own file-model class to be able to handle files and even archive them on disk!
 */
trait WdfFileModel
{
    /** @var int */
	public $id;

    /** @var \ScavixWDF\Base\DateTimeEx|string */
	public $created;

    /** @var string */
	public $name;

    /** @var string */
	public $path;

    /** @var string */
	public $mime;

    /** @var int */
	public $size;

    protected function getFolderArchive()
    {
        $name = explode("/", ltrim($this->path,'/'))[0];
        try
        {
            return new WdfFolderArchive(__FILES__ . "/$name");
        }
        catch (Exception $ex)
        {
            log_debug($ex);
        }
        return null;
    }

    /**
     * @implements <Model::GetTableName>
     */
    function GetTableName()
    {
        return "wdf_files";
    }

    protected function CreateTable()
    {
        if ($this->_ds->Driver instanceof \ScavixWDF\Model\Driver\MySql)
        {
            $this->_ds->ExecuteSql($this->GetCreateStatement());
        }
        else
            WdfDbException::Raise("Unable to translate mysql table definition to sqlite. This is a known issue as uploads-module currently only works with mysql.");
    }

    protected function GetCreateStatement()
    {
        return "CREATE TABLE IF NOT EXISTS `wdf_files` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `created` timestamp NULL DEFAULT current_timestamp(),
            `name` varchar(255) DEFAULT NULL,
            `path` varchar(255) DEFAULT NULL,
            `mime` varchar(100) DEFAULT NULL,
            `size` int(10) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `created` (`created`)
          )";
    }

    /**
     * Deletes this from DB and disk.
     *
     * @return bool True on success, false on failure.
     */
    function Delete()
    {
        $res = parent::Delete();
        if( $res )
            @unlink($this->GetFullPath());
        return $res;
    }

	protected static function currentFilePath($filename='')
	{
        $um = umask(0);
		$base = __FILES__.'/'.date("Y/m/d/");
		if( !file_exists($base) )
			mkdir($base,0777,true);
        umask($um);
		return "$base$filename";
	}

    protected static function relativeFilePath($fullpath)
	{
		return ltrim(str_replace(__FILES__.'/', '', $fullpath),'/');
	}

    protected static function createFileStub($extension)
    {
        $folder = static::currentFilePath();
        mt_srand((double) microtime(false) * 1000000);
        $prefix = date("His-");
        $lock = __METHOD__ . '_' . $folder;
        Wdf::GetLock($lock);
        $um = umask(0);
        try
        {
            $i = 0;
            do
            {
                // $fn = $prefix . str_pad("$i", 4, '0', STR_PAD_LEFT) . ".$extension";
                $fn = $prefix.str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT).'.'.$extension;
            } while (file_exists("$folder$fn") && ++$i < 10000);

            if (file_exists("$folder$fn"))
                WdfException::Raise("Unable to find a unique filename");

            if (touch("$folder$fn"))
                return "$folder$fn";
            WdfException::Raise("Unable to find a unique filename");
        }
        finally
        {
            Wdf::ReleaseLock($lock);
            umask($um);
        }
    }

    /**
     */
    protected function processFile($filename, $name=false, $mime=false, $removesourcefile=true, $extra_columns=[])
    {
        if( !file_exists($filename) )
            WdfException::Raise("Uploaded file not found '$filename'");
        while( is_link($filename) )
            $filename = readlink($filename);
        if( !file_exists($filename) )
            WdfException::Raise("Resolved uploaded file not found");

        $name = $name ?: basename($filename);
        $mime = $mime ?: system_guess_mime($filename);

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if( !$extension )
            $extension = pathinfo($name, PATHINFO_EXTENSION);
        if( !$extension && $mime )
            $extension = system_mime_to_extension($mime);

        $fullpath = static::createFileStub($extension ? strtolower($extension) : 'unknown');
        if( $filename != $fullpath )
        {
            $um = umask(0);
            copy($filename, $fullpath);
            if($removesourcefile && file_exists($fullpath))
                @unlink($filename);
            umask($um);
        }

        $this->created = DateTimeEx::Now();
        $this->name = $name;
        $this->mime = $mime;
        $this->path = static::relativeFilePath($fullpath);
        $this->size = filesize($fullpath);
        foreach ($extra_columns as $k => $v)
            $this->$k = $v;
        $this->Save();

        if ($fa = $this->getFolderArchive())
            if ($fa->updateFile($this->path))
                @unlink($this->GetFullPath());

        return $this;
    }

    /**
     * Creates a new model from an uploaded file.
     *
     * @param mixed $file The $_FILES entry
     * @param mixed $forced_mime Optionally set a mimetype
     * @param mixed $extra_columns Currently unused
     * @return static
     */
	public static function ProcessUpload($file,$forced_mime=false,$extra_columns=[])
	{
        switch( $file['error'] )
        {
            case UPLOAD_ERR_INI_SIZE:
                WdfException::Raise("ERR_UPLOAD_INI_SIZE","upload_max_filesize=".ini_get('upload_max_filesize'));
                break;
            case UPLOAD_ERR_FORM_SIZE:
                WdfException::Raise("ERR_UPLOAD_FORM_SIZE","See MAX_FILE_SIZE directive that was specified in the HTML form");
                break;
            case UPLOAD_ERR_PARTIAL:
                WdfException::Raise("ERR_UPLOAD_PARTIAL");
                break;
            case UPLOAD_ERR_NO_FILE:
                WdfException::Raise("ERR_UPLOAD_NO_FILE");
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                WdfException::Raise("ERR_UPLOAD_NO_TMP_DIR");
                break;
            case UPLOAD_ERR_CANT_WRITE:
                WdfException::Raise("ERR_UPLOAD_CANT_WRITE");
                break;
            case UPLOAD_ERR_EXTENSION:
                WdfException::Raise("ERR_UPLOAD_EXTENSION");
                break;
        }
        $cls = get_called_class();
        $res = new $cls();
        return $res->processFile($file['tmp_name'], $file['name'], $forced_mime);
	}

    /**
     * Processes the complete $_FILES array.
     *
     * @param mixed $forced_mime Optionally set a mimetype
     * @param mixed $extra_columns Currently unused
     * @return array Array of model instances
     */
    public static function ProcessUploads($forced_mime=false, $extra_columns=[]):array
    {
        $res = array();
        foreach( $_FILES as $file )
        {
            if ($file['error'] == UPLOAD_ERR_NO_FILE)
                continue;
            $res[$file['tmp_name']] = static::ProcessUpload($file,$forced_mime);
        }
        return $res;
    }

    /**
     * Checks if this is an image.
     *
     * @param mixed $mime If given, the given value will be checked instead of $this->mime.
     * @return bool True if this is an image, false otherwise.
     */
    function IsImage($mime=false)
    {
        return starts_iwith($mime?:$this->mime,"image");
    }

    /**
     * Checks if this is a video.
     *
     * @param mixed $mime If given, the given value will be checked instead of $this->mime.
     * @return bool True if this is a video, false otherwise.
     */
    function IsVideo($mime=false)
    {
        return starts_iwith($mime?:$this->mime,"video");
    }

    /**
     * Checks if this is an audio.
     *
     * @param mixed $mime If given, the given value will be checked instead of $this->mime.
     * @return bool True if this is an audio, false otherwise.
     */
    function IsAudio($mime=false)
    {
        return starts_iwith($mime?:$this->mime,"audio");
    }

    /**
     * Returns the full path on disk.
     *
     * @return string Full path on disk.
     */
	function GetFullPath()
	{
		return __FILES__."/".ltrim($this->path,"/");
	}

    /**
     * Checks if the file exists on disk.
     *
     * @return bool True if the file exists on disk, false otherwise.
     */
    function Exists()
	{
		$res = file_exists($this->GetFullPath());
        if (!$res && $fa = $this->getFolderArchive())
            $res = $fa->contains(ltrim($this->path,"/"));
        return $res;
	}

    /**
     * @shortcut static::FormatSize($this->size)
     */
    function GetSizeString()
    {
        return static::FormatSize($this->size);
    }

    /**
     * Returns a human readable string representation of a file size.
     *
     * @param int $size Size in bytes.
     * @return string Human readable string representation the given size.
     */
    static function FormatSize($size)

    {
        $bytes = $size; $h = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        for($i = 0; ($bytes / 1024) > 1; $i++, $bytes /= 1024) {}
        return round($bytes, 0).$h[$i];
    }

    /**
     * Passed the files contents to the requesting browser.
     *
     * Valid headers will be genrerated too.
     *
     * @param mixed $asattachment If true, the file will be sent as an attachment (Content-Disposition).
     * @return void
     */
	function PassToBrowser($asattachment = true)
	{
        $file = $this->GetFullPath();
        if (!file_exists($file) )
        {
            if( $fa = $this->getFolderArchive() )
            {
                if (!$fa->contains(ltrim($this->path,"/")))
                    system_die_http(404);
                $um = umask(0);
                if( !file_exists(dirname($file)) )
                    mkdir(dirname($file), 0777, true);
                file_put_contents($file, $fa->get(ltrim($this->path,"/")));
                umask($um);
            }
            if (!file_exists($file))
                system_die_http(404);
        }

        header("Content-Type: {$this->mime}");
        header("Content-Length: {$this->size}");
        if($asattachment)
            header("Content-Disposition: attachment; filename=\"{$this->name}\";");
        else
            \ScavixWDF\WdfResource::ValidatedCacheResponse($file);
        @ob_clean();
        $m = strtolower(ifavail($_SERVER, 'REQUEST_METHOD') ?: 'get');
        if ($m == 'get' || $m == 'post')
        {
            readfile($file);
            if (isset($fa))
                @unlink($file);
        }
        die();
	}

    /**
     * Returns the files contents.
     *
     * @return mixed Contents of the file or NULL
     */
    function getContent()
    {
        if (file_exists($this->GetFullPath()))
            return file_get_contents($this->GetFullPath());
        if ($fa = $this->getFolderArchive())
        {
            if ($fa->contains(ltrim($this->path, '/')))
                return $fa->get(ltrim($this->path, '/'));
        }
        return null;
    }
}