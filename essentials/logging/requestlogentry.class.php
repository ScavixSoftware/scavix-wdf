<?php
/**
 * Scavix Web Development Framework
 *
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
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Logging;

use ScavixWDF\Base\Renderable;
use ScavixWDF\JQueryUI\uiMessage;
use ScavixWDF\Model\DataSource;
use ScavixWDF\Model\Model;
use Swoole\MySQL\Exception;

/**
 * Represents an entry in the wdf_requests table.
 */
class RequestLogEntry extends Model
{
    protected $started;
    protected $handled = false;
    protected static $Current = false;
        
    /**
     * @implements <Model::GetTableName()>
     */
    public function GetTableName(): string { return "wdf_requests"; }

    protected function CreateTable()
    {
        $this->_ds->ExecuteSql(
            "CREATE TABLE `wdf_requests` (
                `id` VARCHAR(40) NOT NULL,
                `created` DATETIME NULL DEFAULT NULL,
                `ms` FLOAT NULL DEFAULT NULL,
                `session_id` VARCHAR(50) NULL DEFAULT NULL,
                `ip` VARCHAR(50) NOT NULL,
                `url` VARCHAR(255) NULL DEFAULT NULL,
                `post` TEXT NULL DEFAULT NULL,
                `result` TEXT NULL DEFAULT NULL,
                INDEX `created` (`created`),
                INDEX `ip` (`ip`),
                INDEX `url` (`url`),
                INDEX `post` (`post`),
                PRIMARY KEY (`id`)
            )
            COLLATE='utf8_unicode_ci';");
        $this->AlterTable();
    }
    
    protected function AlterTable(){}
    
    protected function Blacklisted()
    {
        return stripos($this->url,"wdfresource")!== false;
    }
    
    /**
     * @internal Starts a new request
     */
    public static function Start()
    {
        $entry = new RequestLogEntry();
        $entry->SaveToDB();
    }
    
    protected function SaveToDB($data=[])
    {
        $this->started = microtime(true); // not in DB!
        $this->created = 'now()';
        $this->session_id = session_id();
        $this->ip = get_ip_address();
        $this->url = ifavail(\ScavixWDF\Wdf::$Request,'URL')?:system_current_request(true);
        $this->post = json_encode($_POST);
        $id = md5($this->ip.'-'.$this->url.'-'.$this->post.'-'.$this->started);
        
        if( $this->Blacklisted() )
            return;
        
        foreach( $data as $k=>$v )
            $this->$k = $v;
        
        $i = 0;
        do
        {
            try
            {
                $this->id = $id.$i;
                $this->Save();
                register_hook(HOOK_PRE_FINISH,$this,'_done',true);
                register_hook(HOOK_SYSTEM_DIE,$this,'_died',true);
                RequestLogEntry::$Current = $this;
                return true;
            }
            catch(Exception $ex)
            {
                if( preg_match("/Duplicate entry '.*' for key 'id'/i", $ex->getMessage(), $dummy) === false )
                    throw $ex;
            }
        }
        while($i++<10);
        return false;
    }
    
    public function _died($data)
    {
        list($reason,$stacktrace) = $data;
        $this->_done([$reason."\nStacktrace:\n".system_stacktrace_to_string($stacktrace)]);
    }
    
    public function _done($args)
    {
        if( $this->handled )
            return;
        $this->handled = true;
        $message = array_shift($args);
        
        if( $message instanceof uiMessage )
            $message = json_encode($message->messages);
        elseif( $message instanceof Renderable )
            $message = $message->WdfRender();
        if( !is_string($message) )
            $message = json_encode($message);

        $this->ms = ceil( (microtime(true)-$this->started)*1000 );
        $this->result = $message;
        
        $this->_ds->ExecuteSql(
            "UPDATE `wdf_requests` SET ms={$this->ms}, result=? WHERE id='{$this->id}'",
            [$this->result]
        );
    }
    
    /**
     * @internal Finishes a previously started request
     */
    public static function Finish($result)
    {
        if( self::$Current )
            self::$Current->_done([$result]);
    }
    
    /**
     * Cleans up entries older than a given age.
     * 
     * @param string $maxage Age string like '30 day' or '1 year'
     * @return void
     */
    public static function Cleanup($maxage)
    {
        DataSource::Get()->ExecuteSql(
            "DELETE FROM wdf_requests WHERE created<now()-interval $maxage"
        );
    }
}