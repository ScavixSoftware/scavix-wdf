<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2017-2019 Scavix Software Ltd. & Co. KG
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
 * @copyright 2017-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Session;

/**
 * Implements <ObjectStore> for use with APC.
 * 
 * @suppress PHP0417
 */
class APCStore extends ObjectStore
{
    private $serializer;
    public static $apcstore_key_prefix = 'wdf_apcstore_';
    
    public function __construct()
    {
        global $CONFIG;
        $servername = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:"SCAVIX_WDF_SERVER";
        if( isset($CONFIG['apcstore']['key_prefix']) )
            APCStore::$apcstore_key_prefix = "apcstore_".md5($servername."-".$CONFIG['apcstore']['key_prefix']."-".getAppVersion('nc')).'_';
        else
            APCStore::$apcstore_key_prefix = "apcstore_".md5($servername."-".session_name()."-".getAppVersion('nc')).'_';

        $this->serializer = new Serializer();
        
        if( !isset($_SESSION['object_ids']) )
            $_SESSION['object_ids'] = [];
    }
    
    /**
     * @override <ObjectStore::Store>
     */
    function Store(&$obj,$id="")
    {
        $start = microtime(true);
		$id = strtolower($id);
		if( $id == "" )
		{
			if( !isset($obj->_storage_id) )
				\ScavixWDF\WdfException::Raise("Trying to store an object without storage_id!");
			$id = $obj->_storage_id;
		}
		else
			$obj->_storage_id = $id;
        
        $content = $this->serializer->Serialize($obj);
        
        apc_store(APCStore::$apcstore_key_prefix.session_id().'_'.$id, $content, (ini_get('session.gc_maxlifetime')?:300));

        ObjectStore::$buffer[$id] = $obj;
        $this->_stats(__METHOD__,$start);
    }
    
    /**
     * @override <ObjectStore::Delete>
     */
	function Delete($id)
    {
        $start = microtime(true);
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
        
        if( isset(ObjectStore::$buffer[$id]) )
            unset(ObjectStore::$buffer[$id]);
        
		apc_delete(APCStore::$apcstore_key_prefix.session_id().'_'.$id);
        
        $this->_stats(__METHOD__,$start);
    }
    
    /**
     * @override <ObjectStore::Exists>
     */
	function Exists($id)
    {
        $start = microtime(true);
		if( is_object($id) && isset($id->_storage_id) )
			$id = $id->_storage_id;
		$id = strtolower($id);
		if( isset(ObjectStore::$buffer[$id]) )
            $res = true;
        else
            $res = (apc_exists(APCStore::$apcstore_key_prefix.session_id().'_'.$id) === true);
        $this->_stats(__METHOD__,$start);
		return $res;
    }
    
    /**
     * @override <ObjectStore::Restore>
     */
	function Restore($id)
    {
        $start = microtime(true);
		$id = strtolower($id);

		if( isset(ObjectStore::$buffer[$id]) )
			$res = ObjectStore::$buffer[$id];
        else
        {
            $data = apc_fetch(APCStore::$apcstore_key_prefix.session_id().'_'.$id);
            $res = $this->serializer->Unserialize($data);
            ObjectStore::$buffer[$id] = $res;

        }
        $this->_stats(__METHOD__,$start);
		return $res;
    }
    
    /**
     * @override <ObjectStore::CreateId>
     */    
    function CreateId(&$obj)
    {
        $start = microtime(true);
		if( unserializer_active() )
		{
			log_trace("create_storage_id while unserializing object of type ".get_class_simple($obj));
			$obj->_storage_id = "to_be_overwritten_by_unserializer";
			return $obj->_storage_id;
		}

		$cn = strtolower(get_class_simple($obj));
		if( !isset($_SESSION['object_ids'][$cn]) )
			$_SESSION['object_ids'][$cn] = 1;
		else
			$_SESSION['object_ids'][$cn]++;

        $obj->_storage_id = $cn.$_SESSION['object_ids'][$cn];
        $this->_stats(__METHOD__,$start);
        return $obj->_storage_id;
    }
    
    /**
     * @override <ObjectStore::Cleanup>
     */
    function Cleanup($classname=false)
    {
        // not necessary for APC
    }
    
    /**
     * @override <ObjectStore::Update>
     */
    function Update($keep_alive=false)
    {
        $start = microtime(true);
        
        if( $keep_alive )
        {
            $data = apc_cache_info('user');
            if($data && $data['cache_list'])
            {
                foreach($data['cache_list'] as $entry)
                {
                    if(starts_with($entry['info'], APCStore::$apcstore_key_prefix.session_id().'_'))
                        apc_store($entry['info'], apc_fetch($entry['info']), (ini_get('session.gc_maxlifetime')?:300));
                }
                return;
            }
            $this->_stats(__METHOD__."/KA",$start);
            return;
        }
        
        $sql = [];
        foreach( ObjectStore::$buffer as $id=>$obj )
		{
			try
			{
                $this->Store($obj, $id);
			}
			catch(\Exception $ex)
			{
				\ScavixWDF\WdfException::Log("updating storage for object $id [".get_class($obj)."]",$ex);
			}
		}
        $this->_stats(__METHOD__,$start);
    }
    
    /**
     * @override <ObjectStore::Migrate>
     */
    function Migrate($old_session_id, $new_session_id)
    {
//        log_debug('Migrate', $old_session_id, $new_session_id);
        $start = microtime(true);
        $data = apc_cache_info('user');
        if($data && $data['cache_list'])
        {
            foreach($data['cache_list'] as $entry)
            {
                if(starts_with($entry['info'], APCStore::$apcstore_key_prefix.$old_session_id.'_'))
                {
                    apc_store(str_replace(APCStore::$apcstore_key_prefix.$old_session_id.'_', APCStore::$apcstore_key_prefix.$new_session_id.'_', $entry['info']), apc_fetch($entry['info']), (ini_get('session.gc_maxlifetime')?:300));
                }
            }
        }
        $this->_stats(__METHOD__,$start);
    }
}
