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
use ScavixWDF\Wdf;
use ScavixWDF\WdfException;

/**
 */
class WdfFolderArchive
{
    public $folder, $lastError;

    public static $LOG_ERRORS = true;

	function __construct($folder)
    {
        if( !realpath($folder) && file_exists(realpath("$folder.7z")) )
        {
            log_debug("Creating folder '{$folder}' according to present 7z file");
            $um = umask(0);
            mkdir($folder, 0777);
            umask($um);
        }
        $this->folder = $this->canonialPath(realpath($folder) ?: '');
        $this->lastError = '';

        if (is_file($this->folder) && ends_iwith($this->folder, ".7z"))
        {
            $this->folder = substr($this->folder, 0, -3);
            if( !file_exists($this->folder) )
            {
                log_debug("Creating folder '{$this->folder}' for given present 7z file");
                $um = umask(0);
                mkdir($this->folder, 0777);
                umask($um);
            }
        }

        if (!is_dir($this->folder))
            WdfException::Raise("Folder '$folder' not found ({$this->folder})");
    }

    static function CreateFromBaseFolder($folder):array
    {
        $dir = realpath($folder);
        if (!is_dir($dir))
            WdfException::Raise("Base folder '$folder' not found");

        $res = [];
        foreach( glob("$dir/*",GLOB_ONLYDIR) as $subfolder )
        {
            $name = basename($subfolder);
            if (is_in($name, '.', '..'))
                continue;

            $res[$name] = new WdfFolderArchive($subfolder);
        }
        return $res;
    }

    protected function canonialPath($path)
    {
        $path = str_replace(['\\','/./','//'], ['/','/','/'], $path);
        $parts = explode("/..", $path, 2);
        if( count($parts)>1 )
            $path = dirname($parts[0]) . "{$parts[1]}";
        // elseif (count($parts) > 0)
        //     log_debug("??? $path",$parts);

        return rtrim($path, '/.');
    }

    protected function isFullPath($path)
    {
        if (PHP_OS_FAMILY == "Linux")
            return starts_with($path, '/');
        return starts_iwith($this->folder, substr($path, 0, 2));
    }

    protected function returnError($return_value, $message)
    {
        $this->lastError = $message;
        if (self::$LOG_ERRORS)
            log_error("[WdfFolderArchive," . basename($this->folder) . "] ", $message);
        return $return_value;
    }

    protected function get7z()
    {
        static $bin = false;

        if ($bin === false)
        {
            $buffer = system_app_temp_dir('uploads') . "7z.binary";
            $bin = @file_get_contents($buffer);
            if (file_exists($bin) )
                return $bin;

            if (PHP_OS_FAMILY == "Linux")
                $bin = trim(shell_exec("which 7z"));
            elseif (PHP_OS_FAMILY == "Windows")
            {
                $end = time() + 10;
                foreach( ['%programfiles%\7-zip','%programfiles%',dirname(PHP_BINARY, 1024)] as $path )
                {
                    $path = trim(shell_exec("echo $path"));
                    // log_debug("Searching 7z.exe in '{$path}'...");
                    system_walk_files($path, '*.*', function ($file) use (&$bin, $end)
                    {
                        if (strtolower(basename($file)) == '7z.exe')
                        {
                            $bin = $file;
                            return false;
                        }
                        if (time() > $end)
                            return false;
                    });
                    if( file_exists($bin) )
                    {
                        // log_debug("Found '{$bin}'");
                        break;
                    }
                }
            }
            else
                WdfException::Raise("Unsupported operating system '" . PHP_OS_FAMILY . "'");

            if (!file_exists($bin))
                WdfException::Raise("7-zip binary not found ('$bin', ".PHP_OS_FAMILY.")");

            file_put_contents($buffer, $bin);
        }
        return $bin;
    }

    protected function makeFullPath($relative_path)
    {
        $path = $this->canonialPath($relative_path);
        if (!$this->isFullPath($path))
        {
            $name = basename($this->folder);
            if (strpos($path, "$name/") === 0)
                $path = dirname($this->folder) . '/' . $path;
            else
                $path = $this->folder . '/' . $path;
        }
        // log_debug("rel $relative_path -> $path");
        return $path;
    }

    protected function makeRelativePath($full_path)
    {
        $path = $this->canonialPath($full_path);

        if ( $this->isFullPath($path) )
        {
            if (strpos($path, $this->folder) !== 0)
            {
                // log_debug("makeRelativePath($full_path) OUTSIDE");
                return false;
            }
            $path = ltrim(str_replace(dirname($this->folder), '', $path), '/');
            // log_debug("makeRelativePath($full_path) FULL",$path);
            return $path;
        }

        $name = basename($this->folder);
        if (strpos($full_path, "$name/") === 0)
        {
            // log_debug("makeRelativePath($full_path) INSIDE CORRECT",$full_path);
            return $full_path;
        }
        else
        {
            $path = "$name/".ltrim($full_path,'/');
            // log_debug("makeRelativePath($full_path) INSIDE MISSING BASE ",$path);
            return $path;
        }
    }

    protected function run(...$args)
    {
        $name = basename($this->folder);
        $cd = dirname($this->folder);
        $cmd = "cd ".escapeshellarg($cd)." && ".str_replace(
            ["{archive}", "{folder}"],
            [escapeshellarg("{$name}.7z"), escapeshellarg($name)],
            escapeshellarg($this->get7z()) . " " . implode(" ", $args)
        );
        return shell_exec($cmd) ?: '';
    }

    protected function runAtomar($callback, $defaultReturnValue)
    {
        try
        {
            $lock = get_class() . "_{$this->folder}";
            if (Wdf::GetLock($lock, 10, false))
                return $callback();
            log_error("Unable to get lock");
            return $defaultReturnValue;
        }
        finally
        {
            // log_debug("release $lock");
            Wdf::ReleaseLock($lock);
        }
    }

    function archiveExists()
    {
        return file_exists("{$this->folder}.7z");
    }

    protected function listOrContains($path_to_check, $callback):array
    {
        if (!$this->archiveExists())
            return [];
        if( $callback && !is_callable($callback) ) $callback = false;
        $res = [];
        $out = $this->run('l', '-ba', '-slt', "{archive}",$path_to_check);
        if( preg_match_all('/Path\s=\s([^\n]+)\n.*Attributes\s=\s(.)/sU',$out,$matches,PREG_SET_ORDER) )
        {
            foreach ($matches as $m)
            {
                $fn = $this->canonialPath($m[1]);
                if ($m[2] == "A")
                {
                    if ($callback)
                    {
                        if( $callback($fn) === false )
                            return [];
                    }
                    else
                        $res[] = $fn;
                }
            }
        }
        return $res;
    }

    function list($callback=false):array
    {
        return $this->runAtomar(function () use ($callback)
        {
            return $this->listOrContains(false, $callback);
        }, []);
    }

    function contains($path_inside_archive):bool
    {
        $path = $this->makeRelativePath($path_inside_archive);
        if (!$path)
            return false;
        return $this->runAtomar(function () use ($path)
        {
            return count($this->listOrContains(escapeshellarg($path), false)) > 0;
        }, false);
    }

    protected function addOrUpdate($file_or_path, $is_file = true)
    {
        return $this->runAtomar(function () use ($file_or_path, $is_file)
        {
            if (!$is_file)
                $present = $this->listOrContains(false, false);

            $op = $this->archiveExists() ? "u" : "a";
            $this->run($op, '-ms=100f10m', '-y', "{archive}", $file_or_path);
            if ($is_file)
                return count($this->listOrContains($file_or_path, false)) > 0;

            $now = $this->listOrContains(false, false);
            return array_diff($now, $present);
        }, $is_file ? false : []);
    }

    function updateAll():array
    {
        return $this->addOrUpdate("{folder}",false);
    }

    function updateFolder($path_inside_archive):array
    {
        $path = $this->makeFullPath($path_inside_archive);
        if (!file_exists($path))
            return $this->returnError([], "Folder '$path_inside_archive' not found");
        if (!is_dir($path))
            return $this->returnError([], "Not a folder: '$path_inside_archive'");

        if (strpos($path, $this->folder) !== 0)
            return $this->returnError([], "Folder '$path' is not inside the managed folder '$this->folder'");

        $path = $this->makeRelativePath($path);
        return $this->addOrUpdate(escapeshellarg($path),false);
    }

    function updateFile($path_inside_archive):bool
    {
        $path = $this->makeFullPath($path_inside_archive);
        if (!file_exists($path))
            return $this->returnError(false, "File '$path_inside_archive' not found");
        if (is_dir($path))
            return $this->returnError(false, "Not a file '$path_inside_archive'");

        if (strpos($path, $this->folder) !== 0)
            return $this->returnError(false, "File '$path' is not inside the managed folder '$this->folder'");

        $path = $this->makeRelativePath($path);
        return $this->addOrUpdate(escapeshellarg($path));
    }

    function delete($path_inside_archive):array
    {
        return $this->runAtomar(function () use ($path_inside_archive, &$result)
        {
            $path = $this->makeRelativePath($path_inside_archive);
            if (!$path)
                return $this->returnError([], "File '$path_inside_archive' not in archive");
            $present = $this->listOrContains(false,false);
            $this->run('d', '{archive}', escapeshellarg($path));
            $now = $this->listOrContains(false,false);
            $result = array_diff($present, $now);
        },[]);
    }

    function get($path_inside_archive)
    {
        $path = $this->makeRelativePath($path_inside_archive);
        if (!$path)
            return $this->returnError('', "File '$path_inside_archive' not in archive");

        return $this->runAtomar(function () use ($path)
        {
            return $this->run('e', '-so', '{archive}', escapeshellarg($path));
        }, null);
    }

    function add($path_inside_archive, $content, $keep_local = false):bool
    {
        $local_path = $this->makeFullPath($path_inside_archive);
        if( file_exists($local_path) )
            return $this->returnError(false, "File '$path_inside_archive' already exists locally");

        $um = umask(0);
        $dir = dirname($local_path);
        if( !file_exists($dir) )
			mkdir($dir,0777,true);
        file_put_contents($local_path, $content);
        umask($um);
        
        $path = $this->makeRelativePath($local_path);
        $res = $this->addOrUpdate(escapeshellarg($path));
        if ($keep_local)
            @unlink($local_path);
        return $res;
    }

    function forEach(bool $present_in_archive, bool $present_local, $callback, $force_local_scan = false)
    {
        $archived = $this->listOrContains(false,false);
        // hit_timer("WdfFolderArchive","forEach: list archive");

        static $files = [];
        if (!isset($files[$this->folder]))
            $files[$this->folder] = [];
        if (count($files[$this->folder]) == 0 || $force_local_scan)
        {
            $files[$this->folder] = [];
            system_walk_files($this->folder, '*', function ($file) use (&$files)
            {
                $files[$this->folder][] = $this->canonialPath($file);
            });
            // hit_timer("WdfFolderArchive", "forEach: update local");
        }
        // else
        //     hit_timer("WdfFolderArchive", "forEach: using buffered local");
        
        if( $present_in_archive )
        {
            foreach( $archived as $relative )
            {
                $local = $this->makeFullPath($relative);
                $exists = in_array($local, $files[$this->folder]);
                if (($present_local && !$exists) || (!$present_local && $exists))
                    continue;
                if (false === $callback($relative,$present_local?$local:false,$this))
                    return;
            }
            return;
        }
        if( $present_local )
        {
            foreach ($files[$this->folder] as $file)
            {
                $relative = $this->makeRelativePath($file);
                if (in_array($relative, $archived))
                    continue;
                if (false === $callback(false,$file,$this))
                    return;
            }
            return;
        }
    }

    function removeEmptyLocalFolders()
    {
        // see https://stackoverflow.com/a/1833681
        $loop = function ($path)use(&$loop)
        {
            $empty = true;
            foreach (glob("$path/*") as $file) 
                $empty &= is_dir($file) && $loop($file);
            return $empty && (is_readable($path) && count(scandir($path)) == 2) && @rmdir($path);
        };
        foreach( glob("{$this->folder}/*",GLOB_ONLYDIR) as $dir )
            $loop($dir);
    }
}