<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
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
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 *
 * @SuppressWarnings
 */

use ScavixWDF\Wdf;
use ScavixWDF\WdfException;

define('globalcache_CACHE_OFF',0);
define('globalcache_CACHE_EACCELERATOR',1);
define('globalcache_CACHE_MEMCACHE',2);
define('globalcache_CACHE_APC',4);
define('globalcache_CACHE_DB',5);
define('globalcache_CACHE_YAC',6);
define('globalcache_CACHE_FILES',7);

/**
 * Initializes the globalcache module.
 *
 * @return void
 */
function globalcache_init()
{
	global $CONFIG;

	if( !isset($CONFIG['globalcache']) )
		$CONFIG['globalcache'] = [];

    // ensure valid config if present
    if( isset($CONFIG['globalcache']['CACHE']) )
    {
        if( $CONFIG['globalcache']['CACHE'] == globalcache_CACHE_APC && !function_exists('apc_store') )
            unset($CONFIG['globalcache']['CACHE']);
        elseif( $CONFIG['globalcache']['CACHE'] == globalcache_CACHE_YAC && !class_exists('Yac') )
            unset($CONFIG['globalcache']['CACHE']);
        elseif( $CONFIG['globalcache']['CACHE'] == globalcache_CACHE_EACCELERATOR )
            unset($CONFIG['globalcache']['CACHE']);
        elseif( $CONFIG['globalcache']['CACHE'] == globalcache_CACHE_MEMCACHE )
            unset($CONFIG['globalcache']['CACHE']);
    }

    // autodetect best cache if not set (or previously unset)
    if( !isset($CONFIG['globalcache']['CACHE']) )
    {
        if( class_exists('Yac') )
            $CONFIG['globalcache']['CACHE'] = globalcache_CACHE_YAC;
        elseif( function_exists('apc_store') )
            $CONFIG['globalcache']['CACHE'] = globalcache_CACHE_APC;
        else
            $CONFIG['globalcache']['CACHE'] = globalcache_CACHE_DB;
    }

	$servername = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:"SCAVIX_WDF_SERVER";
	if( isset($CONFIG['globalcache']['key_prefix']))
		$GLOBALS['globalcache_key_prefix'] = $CONFIG['globalcache']['key_prefix'];
	else
		$GLOBALS["globalcache_key_prefix"] = "K".md5($servername."-".session_name()."-".getAppVersion('nc'));


    if( $CONFIG['globalcache']['CACHE'] == globalcache_CACHE_YAC )
        $CONFIG['globalcache']['handler'] = new WdfYacWrapper($GLOBALS['globalcache_key_prefix']);
    elseif( $CONFIG['globalcache']['CACHE'] == globalcache_CACHE_FILES )
        $CONFIG['globalcache']['handler'] = new WdfFileCacheWrapper($GLOBALS['globalcache_key_prefix']);
}

/**
 * Save a value/object in the global cache.
 *
 * @param string $key the key of the value
 * @param mixed $value the object/string to save
 * @param int $ttl time to live (in seconds) of the caching
 * @return bool true if ok, false on error
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_set($key, $value, $ttl = false)
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return false;

	global $CONFIG;
	try
	{
		switch($CONFIG['globalcache']['CACHE'])
		{
			case globalcache_CACHE_OFF:
				return true;

			case globalcache_CACHE_APC:
				return apc_store($GLOBALS["globalcache_key_prefix"].$key, $value, $ttl);

			case globalcache_CACHE_YAC:
            case globalcache_CACHE_FILES:
                $CONFIG['globalcache']['handler']->set($key, $value, $ttl);
                return true;

            case globalcache_CACHE_DB:
                $val = session_serialize($value);
                $ds = model_datasource($CONFIG['globalcache']['datasource']);
				try
				{
					if( $ttl > 0 )
					{
						$ds->ExecuteSql(
							"REPLACE INTO wdf_cache(ckey,full_key,cvalue,valid_until)VALUES(?,?,?,".$ds->Driver->Now($ttl).")",
							array(md5($key),$key,$val)
						);
					}
					else
						$ds->ExecuteSql("REPLACE INTO wdf_cache(ckey,full_key,cvalue)VALUES(?,?,?)",array(md5($key),$key,$val));
				}
				catch(Exception $ex)
				{
					$ds->ExecuteSql("CREATE TABLE IF NOT EXISTS wdf_cache (
                        ckey VARCHAR(32)  NOT NULL,
                        cvalue LONGTEXT  NOT NULL,
                        valid_until DATETIME  NULL,
						full_key TEXT  NOT NULL,
                        PRIMARY KEY (ckey))");

					if( $ttl > 0 )
						$ds->ExecuteSql(
							"REPLACE INTO wdf_cache(ckey,full_key,cvalue,valid_until)VALUES(?,?,?,".$ds->Driver->Now($ttl).")",
							array(md5($key),$key,$val)
						);
					else
						$ds->ExecuteSql("REPLACE INTO wdf_cache(ckey,full_key,cvalue)VALUES(?,?,?)",array(md5($key),$key,$val));
				}
                return true;
		}
	}
	catch(Exception $ex)
	{
		WdfException::Log($ex);
		die($ex->__toString());
	}
	return false;
}

/**
 * Get a value/object from the global cache.
 *
 * @param string $key the key of the value
 * @param mixed $default a default return value if the key can not be found in the cache
 * @return mixed The object from the cache or `$default`
 *
 * @suppress PHP0404,PHP0417,PHP0412,PHP0423,PHP1412,PHP0443
 */
function globalcache_get($key, $default = false)
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return $default;

	global $CONFIG;
	try {
		if( !isset($CONFIG['globalcache']) || !isset($CONFIG['globalcache']['CACHE']) )
			return $default;

		switch($CONFIG['globalcache']['CACHE'])
		{
			case globalcache_CACHE_OFF:
				return $default;

			case globalcache_CACHE_APC:
				$ret = apc_fetch($GLOBALS["globalcache_key_prefix"].$key, $success);
				return $success?$ret:$default;

            case globalcache_CACHE_YAC:
				$ret = $CONFIG['globalcache']['handler']->get($key,$default);
				return $ret===false?$default:$ret;

            case globalcache_CACHE_FILES:
				$ret = $CONFIG['globalcache']['handler']->get($key,$default,$exists);
				return $exists?$ret:$default;

            case globalcache_CACHE_DB:
                $ds = model_datasource($CONFIG['globalcache']['datasource']);
				try
				{
					$ret = $ds->ExecuteScalar("SELECT cvalue FROM wdf_cache WHERE ckey=? AND (valid_until IS NULL OR valid_until>=".$ds->Driver->Now().")",
						array(md5($key)));
				}catch(Exception $ex){ return $default; }
                if( $ret === false )
                    return $default;
                return session_unserialize($ret);
		}
	}
	catch(Exception $ex)
	{
		WdfException::Log($ex);
		die($ex->__toString());
	}
	return isset($ret)?$ret:$default;
}

/**
 * Empty the whole cache.
 *
 * @param bool $expired_only If true, only expired entries will be deleted
 * @return bool true if ok, false on error
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_clear($expired_only=false)
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return false;

	global $CONFIG;
	switch($CONFIG['globalcache']['CACHE'])
	{
		case globalcache_CACHE_OFF:
			return true;

        case globalcache_CACHE_APC:
            if ($expired_only)
                return true; // apc handles expiry itself
			return apc_clear_cache('user');

        case globalcache_CACHE_YAC:
        case globalcache_CACHE_FILES:
			return $CONFIG['globalcache']['handler']->clear($expired_only);

        case globalcache_CACHE_DB:
            $ds = model_datasource($CONFIG['globalcache']['datasource']);
            $sql = $expired_only ? "DELETE FROM wdf_cache WHERE valid_until<now()" : "DELETE FROM wdf_cache";
			try{ $ds->ExecuteSql($sql); }catch(Exception $ex){}
			break;
	}
	return false;
}

/**
 * Delete a value from the global cache.
 *
 * @param string $key the key of the value
 * @param mixed $value the object/string to save
 * @param int $ttl time to live (in seconds) of the caching
 * @return bool true if ok, false on error
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_delete($key)
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return false;

	global $CONFIG;
	switch($CONFIG['globalcache']['CACHE'])
	{
		case globalcache_CACHE_OFF:
			return true;

		case globalcache_CACHE_APC:
			return apc_delete($GLOBALS["globalcache_key_prefix"].$key);

        case globalcache_CACHE_YAC:
        case globalcache_CACHE_FILES:
			return $CONFIG['globalcache']['handler']->delete($key);

        case globalcache_CACHE_DB:
            $ds = model_datasource($CONFIG['globalcache']['datasource']);
			try{ $ds->ExecuteSql("DELETE FROM wdf_cache WHERE ckey=?",md5($key)); }catch(Exception $ex){}
            return true;
	}
	return false;
}

/**
 * Returns information about the cache usage.
 *
 * Note: this currently returns various different information and format thus needs to be streamlined.
 * @return mixed Cache information
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_info()
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return false;

	global $CONFIG;
	$ret = false;
	switch($CONFIG['globalcache']['CACHE'])
	{
		case globalcache_CACHE_APC:
			$status = apc_cache_info('user');
			return(var_export($status, true));

        case globalcache_CACHE_YAC:
        case globalcache_CACHE_FILES:
			$status = $CONFIG['globalcache']['handler']->info();
			return(var_export($status, true));

		case globalcache_CACHE_OFF:
			return "no stats available";

        case globalcache_CACHE_DB:
            $ds = model_datasource($CONFIG['globalcache']['datasource']);
			try{
				$ret  = "Global cache is handled by DB module.\n";
				$ret .= "Datasource: {$CONFIG['globalcache']['datasource']}\n";
				$ret .= "DSN: ".$ds->GetDsn()."\n";
				$ret .= "Records: ".$ds->ExecuteScalar("SELECT count(*) FROM wdf_cache")."\n";
			}catch(Exception $ex){}
			break;
	}

	return $ret;
}

/**
 * Gets a list of all keys in the cache.
 *
 * @return array list of all keys
 *
 * @suppress PHP0404,PHP0417
 */
function globalcache_list_keys()
{
    if( !hook_already_fired(HOOK_POST_INIT) )
        return [];

	global $CONFIG;
	switch($CONFIG['globalcache']['CACHE'])
	{
		case globalcache_CACHE_DB:
			$ds = model_datasource($CONFIG['globalcache']['datasource']);
			try
			{
				$rs = $ds->ExecuteSql("SELECT full_key FROM wdf_cache WHERE (valid_until IS NULL OR valid_until>=".$ds->Driver->Now().")");
				return $rs->Enumerate('full_key');
			}catch(Exception $ex){}
			return [];

        case globalcache_CACHE_APC:
            $ret = [];
            $cacheinfo = apc_cache_info('user');
            $keyprefixlen = strlen($GLOBALS["globalcache_key_prefix"]);
            foreach($cacheinfo['cache_list'] as $cacheentry)
                $ret[] = substr($cacheentry['info'], $keyprefixlen);
            return $ret;

        case globalcache_CACHE_YAC:
        case globalcache_CACHE_FILES:
            return $CONFIG['globalcache']['handler']->keys();

		default:
			WdfException::Log("globalcache_list_keys not implemented for handler {$CONFIG['globalcache']['CACHE']}");
			break;
	}
	return [];
}

/**
 * @internal Wrapper class for YAC
 * @suppress PHP0404,PHP0413
 */
class WdfYacWrapper
{
    private $id, $yac;

    function __construct($key_prefix)
    {
        $this->id = substr(md5($key_prefix),0,16);
        $this->yac = new Yac("$this->id-");
    }

    private function getKey($key)
    {
        $map = $this->yac->get('keymap')?:[];
        if( isset($map[$key]) )
            return $map[$key];
        $map[$key] = count($map);
        $this->yac->set('keymap', $map);
        return $map[$key];
    }

    function get($key,$default)
    {
        $k = $this->getKey($key);
        $ret = $this->yac->get($k);
        return ($ret === false)?$default:$ret;
    }

    function set($key,$val,$ttl)
    {
        $this->yac->set($this->getKey($key),$val,$ttl);
    }

    function delete($key)
    {
        $this->yac->delete($this->getKey($key));
    }

    function clear($expired_only)
    {
        // ignore expired_only as Yac should handle itself
        $this->yac->flush();
        return true;
    }

    function info()
    {
        return $this->yac->info();
    }

    function keys()
    {
        return array_keys( $this->yac->dump(999999) );
    }
}

/**
 * @internal Wrapper class file-based caching
 */
class WdfFileCacheWrapper
{
    private $root;
    private $prefix;
    private $map;

    public function __construct($key_prefix)
    {
        $this->prefix = $key_prefix;
        $this->map = [];
        $this->root = sys_get_temp_dir()."/wdf_globalcache/{$key_prefix}";
        $um = umask(0);
        @mkdir($this->root,0777,true);
        umask($um);

        if( $this->get('WdfFileCacheWrapper::NextCleanup',0,$ex) < time() )
        {
            if( $lock = Wdf::GetLock(__METHOD__,0,false) )
            {
                $this->set('WdfFileCacheWrapper::NextCleanup', strtotime('midnight + 23 hour'));
                //log_debug("WdfFileCacheWrapper starting auto-cleanup");
                $this->clear(true,1);
                Wdf::ReleaseLock($lock);
            }
        }
    }

    protected function getPath($key)
    {
        $file = md5($key);
        $dir = $this->root."/".substr($file, 0, 2);
        $um = umask(0);
        @mkdir($dir, 0777, true);
        umask($um);
        return "$dir/$file";
    }

    protected function unpack($file, $metadata_only = false)
    {
        $c = @file_get_contents($file);
        if (!$c)
            return null;
        $res = session_unserialize($c);
        if (!isset($data['exp']) && isset($res['expiry']))
            $res['exp'] = $res['expiry'];
        elseif( !$metadata_only )
            $res['data'] = session_unserialize($res['data']);
        return $res;
    }

    public function set($key, $val, $ttl = 0)
    {
        $eol = time() + (($ttl <= 0) ? 86400 : $ttl);
        $val = array(
            'exp' => $eol>time()?$eol:false,
            'key' => $key,
            'data' => session_serialize($val),
        );
        // Write to temp file first to ensure atomicity
        $um = umask(0);
        $dest = $this->getPath($key);
        $tmp = $dest . '.' . uniqid('', true) . '.tmp';
        file_put_contents($tmp, session_serialize($val), LOCK_EX);
        rename($tmp, $dest);
        umask($um);
        //opcache_invalidate($dest);
    }

    public function get($key,$default,&$exists)
    {
        $exists = false;
        $file = $this->getPath($key);

        $filemtime = @filemtime($file);

        if( isset($this->map[$key]) && $this->map[$key]['filemtime'] == $filemtime )
            return $this->map[$key]['data'];

        $val = $this->unpack($file);
        if (!isset($val['key']) || $val['key'] != $key )
            return $default;

        if (!$val['exp'] || $val['exp'] > time())
        {
            $this->map[$key] = $val;
            $this->map[$key]['filemtime'] = $filemtime;
            $exists = true;
            return $val['data'];
        }
        $this->delete($key);
        return $default;
    }

    public function delete($key)
    {
        $file = $this->getPath($key);
        @unlink($file);
        if( isset($this->map[$key]) )
            unset( $this->map[$key]);
    }

    function clear($expired_only, $ttl = 10)
    {
        $count = 0;
        $forced_end = time() + $ttl;
        system_walk_files($this->root, '*', function ($file)use($expired_only, $forced_end, $ttl,&$count)
        {
            if( $expired_only )
            {
                $val = $this->unpack($file,true);
                if( !isset($val['exp']) || !$val['exp'] || $val['exp'] > time() )
                    return;
                //usleep(100000);
            }
            @unlink($file);
            //log_debug($file);
            $count++;

            if( time()>$forced_end )
            {
                //log_debug("WdfFileCacheWrapper::clear() unfinished after {$ttl}s (and $count files), let others work too");
                $this->set('WdfFileCacheWrapper::NextCleanup', time());
                return false;
            }
        });
        $this->map = [];
        return true;
    }

    function info($include_keys=true)
    {
        $r = [
            'map_size' => count($this->map),
            'entries' => 0,
            'next_cleanup' => date("c", $this->get('WdfFileCacheWrapper::NextCleanup', time(), $ex)),
            'next_cleanup_file' => $this->getPath('WdfFileCacheWrapper::NextCleanup'),
        ];
        if ($include_keys)
        {
            $keys = $this->keys();
            $r['entries'] = count($keys);
            $r['keys'] = $keys;
        }
        else
            system_walk_files($this->root, '*', function ($file) use (&$r)
            {
                $r['entries']++;
            });
        return $r;
    }

    function keys()
    {
        $ret = [];
        system_walk_files($this->root, '*', function ($file)use(&$ret)
        {
            $val = $this->unpack($file,true);
            if( isset($val['key']) )
                $ret[] = $val['key'];
        });
        return $ret;
    }
}
