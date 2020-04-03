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
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Tasks;

use ScavixWDF\Model\DataSource;
use ScavixWDF\Model\Model;
use ScavixWDF\WdfDbException;
use ScavixWDF\Base\DateTimeEx;

class WdfTaskModel extends Model
{
    private $isVirtual = false;
    public static $PROCESS_FILTER = 'db:processwdftasks';
    public static $MAX_PROCESSES = 5;
    
	public function GetTableName() { return 'wdf_tasks'; }
    
    function __construct($datasource = null)
    {
        parent::__construct($datasource);
        if( !function_exists("cli_run_taskprocessor") )
            system_load_module('modules/cli.php');
    }
    
    protected function CreateTable()
    {
        $this->_ds->ExecuteSql(
            "CREATE TABLE IF NOT EXISTS `wdf_tasks` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `parent_task` INT(11) UNSIGNED NULL DEFAULT NULL,
                `follow_deletion` TINYINT(1) UNSIGNED NULL DEFAULT 0,
                `enabled` TINYINT(4) NULL DEFAULT 0,
                `created` DATETIME NULL DEFAULT NULL,
                `start` DATETIME NULL DEFAULT NULL,
                `assigned` DATETIME NULL DEFAULT NULL,
                `worker_pid` INT(11) NULL DEFAULT NULL,
                `name` VARCHAR(255) NULL DEFAULT NULL,
                `arguments` LONGTEXT NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `enabled_workerpid_parenttask_id` (`enabled`, `worker_pid`, `parent_task`, `id`)
            );");
    }
	
    public function Save($columns_to_update = false)
    {
        if( $this->isVirtual )
        {
//            log_debug("Skipping Save for virtual task");
            return true;
        }
        return parent::Save($columns_to_update);
    }
    
	public static function RunInstance($runtime_seconds=null)
	{
        if( count(self::getRunningProcessors()) < self::$MAX_PROCESSES )
            cli_run_taskprocessor($runtime_seconds);
	}
	
    public static function CreateOnce($name)
    {
        return self::Create($name,true);
    }
    
	public static function Create($name,$only_if_not_running=false,$virtual=false)
	{
        //log_debug(__METHOD__,$name,$only_if_not_running,$virtual);
        if( $only_if_not_running )
        {
            $tn = WdfTaskModel::Make()->eq('name',$name)->current();
            if( $tn )
            {
                if( !$tn->enabled && $tn->created->ot_mins(5) )
                {
                    $tn->DeleteChildren(true,true);
                    $tn = false;
                }
                else
                    $virtual = true;
            }
        }
        if( $virtual )
        {
            $tn = new WdfTaskModel();
            $tn->isVirtual = true;
            $tn->arguments = serialize(array());
            return $tn;
        }
        if( !isset($tn) || !$tn )
        {
            $tn = new WdfTaskModel();
            $tn->created = 'now()';
            $tn->enabled = 0;
            $tn->name = $name;
            $tn->arguments = serialize(array());
        }
		return $tn;
	}
	
	public function SetArg($name,$value)
	{
		$this->arguments = unserialize($this->arguments);
		$this->arguments[$name] = $value;
		$this->arguments = serialize($this->arguments);
		return $this;
	}
    
    public function SetArgs($arguments)
    {
        $this->arguments = serialize($arguments);
		return $this;
    }
	
	public function DependsOn($task,$follow_deletion=true)
	{
		if( !$task )
			return $this;
		if( $task instanceof WdfTaskModel )
        {
            $task->Save();
			$task = ifavail($task,'id');
        }
		$this->parent_task = $task;
		$this->follow_deletion = $follow_deletion?1:0;
		$this->Save();
		return $this;
	}
	
	public function Delay($seconds)
	{
        if( $seconds > 0 )
            $this->start = "NOW()+INTERVAL $seconds SECOND";
		return $this;
	}
    
    public function SetStart($start)
	{
		$this->start = \ScavixWDF\Base\DateTimeEx::Make($start);
		return $this;
	}
	
	public function Go($run_instance=true,$depth=0)
	{
        if( !$this->isVirtual )
        {
            $q = WdfTaskModel::Make()->eq('name',$this->name)->eq('arguments',$this->arguments);
            if( isset($this->id) )
                $q = $q->neq('id',$this->id);
            $other = $q->scalar('id');
            if( $other )
            {
                if( isset($this->id) )
                {
//                    log_debug("Removing duplicate task {$this->name}[{$this->id}] because of $other");
                    $this->Delete();
                }
//                else
//                    log_debug("Removing duplicate task {$this->name} because of $other");
                $this->isVirtual = true;
            }
            else
            {
                $this->enabled = 1;
                $this->Save();
                if( $depth++ < 10 ) 
                    foreach( WdfTaskModel::Make()->eq('parent_task',$this->id) as $t )		
                        $t->Go(false,$depth);
            }
        }
		if( $run_instance )
			WdfTaskModel::RunInstance();
		return $this;
	}
    
    private static function getRunningProcessors()
    {
        $filter = preg_quote(CLI_SELF,'/').".*".preg_quote(self::$PROCESS_FILTER,'/');
        $res = array();
        $out = shell_exec("ps -Af");
        if( preg_match_all('/\n[^\s+]*\s+(\d+)\s+.*'.$filter.'/i',$out,$m) )
        {
            foreach( $m[1] as $p )
                $res[] = $p;
        }
        return $res;
    }
	
    public static function FreeOrphans()
    {
        $ds = DataSource::Get();
        $test = $ds->ExecuteSql("SELECT DISTINCT worker_pid FROM wdf_tasks")->Enumerate('worker_pid');
        $tasks = self::getRunningProcessors();
        $pids = implode(",",array_filter(array_diff($test,$tasks)));
        if( $pids )
        {
            $rs = $ds->ExecuteSql("UPDATE wdf_tasks SET worker_pid=null, assigned=null WHERE worker_pid IN($pids)");
            if( $rs && $rs->Count() )
                log_debug("Restarted ".$rs->Count()." tasks whose workers did not exist anymore");
            
            $rs = $ds->ExecuteSql("UPDATE wdf_tasks SET assigned=null WHERE isnull(worker_pid)");
            if( $rs && $rs->Count() )
                log_debug("Restarted ".$rs->Count()." tasks that were assigend but to noone");
        }
    }
    
	public static function Reserve()
	{
		$wpid = getmypid();
        try
        {
            DataSource::Get()->ExecuteSql(
                "UPDATE wdf_tasks SET worker_pid=?, assigned=now() WHERE 
                    enabled=1 AND isnull(worker_pid) AND isnull(parent_task) AND
                    isnull(assigned) AND
                    (ISNULL(start) OR start<=now()) 
                    ORDER BY id DESC LIMIT 1"
                ,$wpid);
        }
        catch(WdfDbException $ex)
        {
            list($c1,$c2,$msg) = $ex->getErrorInfo();
            if( stripos($msg,"deadlock") === false )
                log_error($ex);
            return false;
        }
		return WdfTaskModel::Make()->eq('worker_pid',$wpid)->current();
	}
	
	public function Run($inline=false)
	{
        if( $this->isVirtual )
        {
            log_debug("Skipping virtual task ".$this->name);
            return;
        }
        list($name,$method) = explode("-","{$this->name}-run");
		$args = unserialize($this->arguments);
		
		if( is_subclass_of($name, \ScavixWDF\Tasks\Task::class) )
		{
            $worker = new $name($this);
			
            if( !method_exists($worker, $method) )
            {
                log_debug("Method $name::$method does not exist, falling back to 'run'");
                $method = 'run';
            }
            $exectime = microtime(true);
			$worker->$method($args);
            $exectime = round((microtime(true) - $exectime) * 1000);
            
            $start = DateTimeEx::Make(ifavail($this,'start','created'));
            $runtime = max($exectime,round((microtime(true) - $start->getTimestamp()) * 1000));
            $worker->Finished($method,$runtime,$exectime);
            
            if( $inline )
            {
                foreach( WdfTaskModel::Make()->eq('parent_task',$this->id)->orderBy('id') as $child )
                    $child->Run(true);
            }
            else
            {
                // make sure children are enabled if (for whatever reason) they are not
                foreach( WdfTaskModel::Make()->eq('parent_task',$this->id)->eq('enabled',0) as $t )		
                    $t->Go(false);
            }
			$this->Delete();
		}
		else
		{
            $this->assigned = NULL;
            $this->worker_pid = NULL;
			$this->enabled = 0;
			$this->Save();
			log_error("Task processor not found: '$name'. Disabling to stop chain");
		}
	}
	
	function Delete()
	{
        if( $this->isVirtual )
            return true;
        
		if( !parent::Delete() )
		{
            $this->assigned = NULL;
            $this->worker_pid = NULL;
			$this->enabled = 0;
			$this->Save();
			log_error("Task was processed but could not be deleted from DB. Disabling to stop chain");
			return false;
		}
        $this->_ds->ExecuteSql("UPDATE wdf_tasks SET parent_task=null WHERE parent_task=?",$this->id);
		return true;
	}
	
	function DeleteChildren($delete_self=false,$complete=false)
	{
        if( $this->isVirtual )
            return;
        
		$q = WdfTaskModel::Make()->eq('parent_task',$this->id);
		if( !$complete )
			$q = $q->eq('follow_deletion',1);
		foreach( $q as $t )
			$t->DeleteChildren(true);
		if( $delete_self )
			$this->Delete();
	}
    
    function AddChild($name,$args='copy')
    {
        $this->CreateChild($name,$args);
        return $this;
    }
    
    function CreateChild($name,$args='copy',$only_if_not_running=false)
    {
        if( $args == 'copy' )
            $args = unserialize($this->arguments);
        if( $this->isVirtual )
            return WdfTaskModel::Create($name,null,$only_if_not_running,true)->SetArgs($args)->DependsOn($this);
        return WdfTaskModel::Create($name,null,$only_if_not_running)->SetArgs($args)->DependsOn($this);
    }
    
    function CreateChildOnce($name,$args='copy')
    {
        return $this->CreateChild($name, $args, true);
    }
}