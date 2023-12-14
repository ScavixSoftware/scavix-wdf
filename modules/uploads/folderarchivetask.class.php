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

use ScavixWDF\Tasks\Task;
use ScavixWDF\WdfException;

/**
 */
class FolderArchiveTask extends Task
{
    private static function eachFiles($folder, $present_in_arch, $present_locally, $maxfiles, $maxruntime, $callback)
    {
        if (!realpath($folder))
            $folder = __FILES__ . "/$folder";
        $end = time() + $maxruntime;
        $fa = new WdfFolderArchive($folder);
        $fa->forEach($present_in_arch, $present_locally, function ($arch, $local, $fa)use($callback,&$maxfiles, $end)
        {
            $callback($arch, $local, $fa);
            if (--$maxfiles < 1)
                return false;
            if (time() > $end)
                return false;
        });
        $fa->removeEmptyLocalFolders();
    }

    public static function MoveSomeFiles($folder, $maxfiles, $maxruntime, $verbose = false)
    {
        self::eachFiles($folder, false, true, $maxfiles, $maxruntime, function ($in_arch, $local, $fa)use($verbose)
        {
            if( $fa->updateFile($local) )
            {
                @unlink($local);
                if( $verbose )
                    log_debug("Archived and removed local copy of {$local}");
            }
        });
    }

    public static function DeleteSomeArchivedLocally($folder, $maxfiles, $maxruntime, $verbose = false)
    {
        self::eachFiles($folder, true, true, $maxfiles, $maxruntime, function ($in_arch, $local, $fa)use($verbose)
        {
            @unlink($local);
            if( $verbose )
                log_debug("Removed local copy of archived file {$local}");
        });
    }

    private function getFolderArchive($args)
    {
        $folder = $this->getArg($args, 'folder', 0);
        if ($folder)
        {
            if (!realpath($folder))
                $folder = __FILES__ . "/$folder";

            return new WdfFolderArchive($folder);
        }
        return false;
    }

    function Run($args)
    {
        log_info("Syntax: folderarchive-(list|update|extract)");
    }

    function List($args)
    {
        $fa = $this->getFolderArchive($args);
        if( $fa )
        {
            foreach ($fa->list() as $file)
                echo("$file\n");
            return;
        }
        $wrap = new class
        {
            use WdfFileModel;
            function fs($size)
            {
                return self::FormatSize($size);
            }
        };
        foreach( WdfFolderArchive::CreateFromBaseFolder(__FILES__) as $fa )
        {
            $a = "{$fa->folder}.7z";
            if (!file_exists($a))
            {
                echo("$a\t(not found)\n");
                continue;
            }
            echo("$a\t".$wrap->fs(filesize($a))."\t".date("Y-m-d H:i:s", filemtime($a))."\n");
        }
    }

    /**
     * Updates the archive with (missing/updated) files from disk (can last quite long!).
     *
     * Note: If mode is 'move' files will be removed after beein archived.
     * <code>
     * // Syntax: folderarchive-update <folder> [mode=(copy|move|clean)]
     * </code>
     * @param array $args Cli arguments
     * @return void
     */
    function Update($args)
    {
        $fa = $this->getFolderArchive($args);
        if (!$fa)
        {
            log_info("folderarchive-update <folder> [mode=(copy|move|clean)]", "Updates the archive with (missing/updated) files from disk (can last quite long!). If mode is 'move' files will be removed after beein archived.");
            return;
        }

        $mode = $this->getArg($args, 'mode', 1) ?: 'copy';

        if( is_in($mode,'copy','move') )
        {
            $files = $fa->updateAll();
            log_debug("Updated ".count($files)." files",$files);
        }

        if( is_in($mode,'move','clean') )
        {
            log_debug("Scanning for files to remove...");
            $fa->forEach(true, true, function ($in_ach, $local_file)
            {
                if (@unlink($local_file))
                    log_debug("Removed local copy of archived file {$local_file}");
                else
                    log_warn("Cannot remove file {$local_file}");
            });
            $fa->removeEmptyLocalFolders();
        }
    }

    /**
     * Extracts a file/folder from the archive to a local folder.
     *
     * <code>
     * // Syntax: folderarchive-extract <folder> <path-in-archive> <local-folder>
     * </code>
     * @param array $args Cli arguments
     * @return void
     */
    function Extract($args)
    {
        $fa = $this->getFolderArchive($args);
        $in_arch = $this->getArg($args, 1);
        $local_folder = $this->getArg($args, 2);
        if (!$fa || !$in_arch || !$local_folder )
        {
            log_info("folderarchive-extract <folder> <path-in-archive> <local-folder>", "Extracts a file/folder from the archive to a local folder.");
            return;
        }
        if ($local_folder != '-')
        {
            if (!file_exists($local_folder) || !is_dir($local_folder))
                WdfException::Raise("Not a folder: '$local_folder'");
            if (!is_writable($local_folder))
                WdfException::Raise("Local folder is not writable: '$local_folder'");
        }

        if( !$fa->contains($in_arch) )
            WdfException::Raise("Not found in archive: '$in_arch'");

        $c = $fa->get($in_arch);
        if ($local_folder == '-')
            die($c);
        $name = basename($in_arch);
        file_put_contents("$local_folder/$name", $c);
    }
}