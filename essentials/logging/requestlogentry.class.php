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
	/** @var string */
	public $id;
	
	/** @var \ScavixWDF\Base\DateTimeEx|string */
	public $created;
	
	/** @var float */
	public $ms;
	
	/** @var string */
	public $session_id;
	
	/** @var string */
	public $ip;
	
	/** @var string */
	public $url;
	
	/** @var string */
	public $post;
	
	/** @var string */
	public $result;

    protected $started;
    protected $handled = false;
    protected static $Current = false;
        
    /**
     * @implements <Model::GetTableName()>
     */
    public function GetTableName(): string { return "wdf_requests"; }

    protected function __ensureTableSchema()
    {
        if( $this->_ds->Driver instanceof \ScavixWDF\Model\Driver\MySql )
            return parent::__ensureTableSchema();
        
        self::$_schemaCache[$this->_cacheKey] 
            = $this->_tableSchema 
            = new \ScavixWDF\Model\TableSchema($this->_ds,$this->GetTableName());
        return $this->_tableSchema;
    }
    
    protected function CreateTable()
    {
        if( $this->_ds->Driver instanceof \ScavixWDF\Model\Driver\MySql )
        {
            $this->_ds->ExecuteSql(
                "CREATE TABLE `wdf_requests` (
                    `id` VARCHAR(40) NOT NULL,
                    `created` TIMESTAMP(3) NULL DEFAULT current_timestamp(3),
                    `ms` FLOAT NULL DEFAULT NULL,
                    `session_id` VARCHAR(50) NULL DEFAULT NULL,
                    `ip` VARCHAR(255) NOT NULL,
                    `url` VARCHAR(1000) NULL DEFAULT NULL,
                    `post` TEXT NULL DEFAULT NULL,
                    `result` TEXT NULL DEFAULT NULL,
                    INDEX `created` (`created`),
                    INDEX `ip` (`ip`),
                    INDEX `url` (`url`),
                    INDEX `post` (`post`(1024)),
                    PRIMARY KEY (`id`)
                )
                COLLATE='utf8_unicode_ci';");
            $this->AlterTable();
        }
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
    
    protected function SaveToDB($data=[], $url = false)
    {        
        if( !($this->_ds->Driver instanceof \ScavixWDF\Model\Driver\MySql) )
            return;
        
        $this->started = microtime(true); // not in DB!
        $this->session_id = session_id();
        $this->ip = get_ip_address();
        if (!$url)
        {
            $url = ifavail(\ScavixWDF\Wdf::$Request, 'URL') ?: system_current_request(true);
            $url = substr($url, strpos($url,"/",strpos($url, "://") + 3));
        }
        $this->url = $url;
        
        if( $this->Blacklisted() )
            return;
        
        $post = $_POST;
        if(count($post) == 0)
        {
            $post = json_decode(@file_get_contents('php://input'), true);
            if(!$post)
                $post = [];
        }
        $this->post = json_encode($this->obfuscateData($post));
        $id = md5($this->ip.'-'.$this->url.'-'.$this->post.'-'.$this->started);
        
        foreach( $data as $k=>$v )
            $this->$k = $v;
        
        \ScavixWDF\WdfDbException::$DISABLE_LOGGING = true;
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
                \ScavixWDF\WdfDbException::$DISABLE_LOGGING = false;
                return true;
            }
            catch(\ScavixWDF\WdfDbException $ex)
            {
                if($ex->isTableNotExistException('wdf_requests'))
				{
					$this->CreateTable();
					continue;
				}
                if(!$ex->isDuplicateKeyException('PRIMARY'))
                {
                    \ScavixWDF\WdfDbException::$DISABLE_LOGGING = false;
                    throw $ex;
                }
            }
        }
        while($i++<10);
        \ScavixWDF\WdfDbException::$DISABLE_LOGGING = false;
        return false;
    }
    
    protected function obfuscateData(array $data): array
    {
        foreach( $data as $k=>$v )
            if( stripos($k,'pass') !== false )
                $data[$k] = '***';
            else if( is_string($v) && starts_iwith($v,"data:") ) 
                $data[$k] = substr($v,0,30)."-TRUNCATED";
        return $data;
    }
    
    public function _died($data)
    {
        list($reason,$stacktrace) = $data;
        $this->_done([$reason."\nStacktrace:\n".system_stacktrace_to_string($stacktrace)]);
    }
    
    public function _done($args)
    {
        if( !($this->_ds->Driver instanceof \ScavixWDF\Model\Driver\MySql) )
            return;
        
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
        
        $changedcols = $this->GetChanges();
        if(count($changedcols) == 0)
            return;
        $args = [];
        $sql = 'UPDATE LOW_PRIORITY `wdf_requests` SET ';
        foreach($changedcols as $col => $vals)
        {
            $sql .= ((count($args) > 0) ? ', ' : '').'`'.$col.'`=?';
            $args[] = $vals[1];
        }
        $sql .= ' WHERE id=?';
        $args[] = $this->id;
        $this->_ds->ExecuteSql($sql, $args);
//        log_debug($sql, $args);
        
//        $this->_ds->ExecuteSql(
//            "UPDATE `wdf_requests` SET ms={$this->ms}, result=? WHERE id='{$this->id}'",
//            [$this->result]
//        );
    }
    
    /**
     * @internal Finishes a previously started request
     */
    public static function Finish($result)
    {
//        if( !($this->_ds->Driver instanceof \ScavixWDF\Model\Driver\MySql) )
//            return;
        
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
//        if( !($this->_ds->Driver instanceof \ScavixWDF\Model\Driver\MySql) )
//            return;
        
        DataSource::Get()->ExecuteSql(
            "DELETE FROM wdf_requests WHERE created<now()-interval $maxage"
        );
    }
}