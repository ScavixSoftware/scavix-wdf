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

/**
 * @internal Model class representing tasks that can be handled asynchronously
 */
class WdfTaskModel extends Model
{
	/** @var int */
	public $id;

	/** @var int */
	public $parent_task;

	/** @var int */
	public $follow_deletion;

	/** @var int */
	public $enabled;

	/** @var int */
	public $priority;

	/** @var \ScavixWDF\Base\DateTimeEx|string */
	public $created;

	/** @var \ScavixWDF\Base\DateTimeEx|string */
	public $start;

	/** @var \ScavixWDF\Base\DateTimeEx|string */
	public $assigned;

	/** @var int */
	public $worker_pid;

	/** @var string */
	public $name;

	/** @var string */
	public $arguments;

    public $RecreateOnSave = false;

    private $isVirtual = false, $prevent_duplicate = false, $cascade_go = true, $children = [];
    public static $PROCESS_FILTER = 'db-processwdftasks';
    public static $MAX_PROCESSES = 10;
    public static $MIN_RUNTIME = 0;

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
            "CREATE TABLE `wdf_tasks` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `parent_task` INT(11) UNSIGNED NULL DEFAULT NULL,
                `follow_deletion` TINYINT(1) UNSIGNED NULL DEFAULT '0',
                `enabled` TINYINT(4) NULL DEFAULT '0',
                `priority` TINYINT(4) NULL DEFAULT '3',
                `created` DATETIME NULL DEFAULT NULL,
                `start` DATETIME NULL DEFAULT NULL,
                `assigned` DATETIME NULL DEFAULT NULL,
                `worker_pid` INT(11) NULL DEFAULT NULL,
                `name` VARCHAR(255) NULL DEFAULT NULL,
                `arguments` MEDIUMTEXT NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `enabled_workerpid_parenttask_id` (`enabled`, `worker_pid`, `parent_task`, `id`),
                INDEX `worker_pid` (`worker_pid`, `assigned`),
                INDEX `name` (`name`),
                INDEX `parent_task` (`parent_task`)
            )
            ENGINE=InnoDB;
            ");
    }

    public function Save($columns_to_update = false, &$changed=null)
    {
        if( $this->isVirtual )
        {
//            log_debug("Skipping Save for virtual task");
            return true;
        }
        if( $this->RecreateOnSave )
        {
            $still_present = $this->_ds->ExecuteScalar("SELECT count(*) FROM wdf_tasks WHERE id=?", [$this->id]);
            // log_debug('RecreateOnSave', $this->id, $this->name, $still_present, $this->children);
            if (!$still_present)
            {
                $this->_saved = false;
                $this->_dbValues = [];
                $this->enabled = 0;
                $this->worker_pid = null;
                $this->assigned = null;
                //log_debug("Ensuring re-save for taskmodel");
            }
        }

        $ret = false;
        try
        {
            $ret = parent::Save($columns_to_update, $changed);
        }
        catch(WdfDbException $ex)
        {
            if($ex->isDuplicateKeyException('PRIMARY') && (strpos($this->name, 'TaskPool-') !== false))
            {
                // special handling for reusable taskpool tasks. Just ignore this exception.
                //log_debug($ex);
            }
            else
            {
                throw $ex;
            }
        }
        return $ret;
    }

    public static function HasToDo()
    {
        return !!DataSource::Get()->ExecuteScalar("SELECT count(*) FROM wdf_tasks WHERE enabled=1 AND ISNULL(parent_task)");
    }

    public static function CountRunningInstances()
    {
        static $shmdir = false;
        if( $shmdir === false )
            $shmdir = "/run/shm/" . $GLOBALS['CONFIG']['system']['application_name'];

        return count(glob("{$shmdir}/*.*"));
    }

    public static function GetRunningInstances()
    {
        static $shmdir = false;
        if( $shmdir === false )
            $shmdir = "/run/shm/" . $GLOBALS['CONFIG']['system']['application_name'];

        $pids = [];
        foreach (glob("{$shmdir}/*.*") as $f)
            $pids[] = intval(array_first(explode(".", basename($f))));
        return array_filter($pids);
    }

	public static function RunInstance($runtime_seconds=null)
	{
        static $counter = false;
        if ($counter === false || PHP_SAPI == 'cli' )
            $counter = self::CountRunningInstances();
        if ($counter >= self::$MAX_PROCESSES)
            return;
        $counter++;

        if( !function_exists("cli_run_taskprocessor") )
            system_load_module('modules/cli.php');
        cli_run_taskprocessor($runtime_seconds);
	}

    public static function CreateOnce($name, $return_original=false)
    {
        return self::Create($name,true,false,$return_original);
    }

	public static function Create($name,$only_if_not_running=false,$virtual=false,$return_original=false)
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
                elseif( $return_original )
                    return $tn;
                else
                    $virtual = true;
            }
        }
        if( $virtual )
        {
            $tn = new WdfTaskModel();
            $tn->isVirtual = true;
            $tn->arguments = serialize([]);
            return $tn;
        }
        if( !isset($tn) || !$tn )
        {
            $tn = new WdfTaskModel();
            $tn->created = 'now()';
            $tn->enabled = 0;
            $tn->name = $name;
            $tn->arguments = serialize([]);
        }
		return $tn;
	}

	public function SetArg($name,$value)
	{
		$args = unserialize($this->arguments);
		$args[$name] = $value;
		$this->arguments = serialize($args);
		return $this;
	}

    public function SetArgs($arguments, $replace = true)
    {
        if ($replace)
            $this->arguments = serialize($arguments ?: []);
        else
            foreach ($arguments as $n => $v)
                $this->SetArg($n, $v);
        return $this;
    }

    public function GetArg($name,$default=false)
    {
        $args = unserialize($this->arguments);
        return isset($args[$name])?$args[$name]:$default;
    }

    public function GetArgs()
    {
        return unserialize($this->arguments);
    }

    public function SetCascadeGo($on=true)
    {
        $this->cascade_go = $on;
        return $this;
    }

    public function PreventDuplicate()
    {
        $this->prevent_duplicate = true;
        return $this;
    }

	public function DependsOn($task,$follow_deletion=true)
	{
		if( $task instanceof Task )
            $task = $task->model;
		if( $task instanceof WdfTaskModel )
        {
            $task->Save();
            if($task->isVirtual)
                $this->isVirtual = true;
            $task->children[] = $this;
			$task = ifavail($task,'id');
        }
		if( !$task )
			return $this;
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
		$this->start = DateTimeEx::Make($start);
		return $this;
	}

	public function Go($run_instance=true,$depth=0)
	{
        if( !$this->isVirtual && ($this->enabled == 0 || avail($this,'worker_pid')) )
        {
            $this->enabled = 1;

            if( $this->prevent_duplicate )
            {
                $q = WdfTaskModel::Make()->eq('name',$this->name)->eq('arguments',$this->arguments);
                if( isset($this->id) )
                    $q = $q->neq('id',$this->id);
                $other = $q->scalar('id');
            }
            else
                $other = false;

            if( $other )
            {
                if( isset($this->id) )
                    $this->Delete();
                $this->isVirtual = true;
            }
            else
            {
                $this->Save();
                if ($this->cascade_go)
                {
                    if ($depth++ < 50) // limit depth to 50 to avoid too large trees)
                    {
                        if (count($this->children) == 0)
                            $this->children = WdfTaskModel::Make()->eq('parent_task', $this->id)->eq('enabled', 0)->results();
                        foreach ($this->children as $t)
                            $t->Go(false, $depth);
                    }
                }
                elseif(count($this->children))
                {
                    // at least save children to not loose "->Delay" and stuff
                    foreach ($this->children as $ch)
                    {
                        if (!avail($ch, 'parent_task')) // the parent task id might not have been yet available at setting DependsOn()
                        {
                            // log_debug(__METHOD__, "Setting parent_task to {$this->id} for child {$ch->id}", $ch->name, $this->name);
                            $ch->parent_task = $this->id;
                        }
                        $ch->Save();
                    }
                }
            }
        }
        elseif( !$this->isVirtual )
        {
            if (avail($this, 'worker_pid'))
                log_debug(__METHOD__, "Cannot modify running task", system_get_caller());
            else
                $this->Save();
        }
        if ($run_instance && !avail($this, 'worker_pid'))
            WdfTaskModel::RunInstance();
		return $this;
	}

    public static function FreeOrphans()
    {
        $ds = DataSource::Get();

        //$ds->ExecuteSql("UPDATE wdf_tasks SET parent_task=null WHERE parent_task IS NOT NULL AND parent_task NOT IN(SELECT id FROM wdf_tasks)");

        $test = $ds->ExecuteSql("SELECT DISTINCT worker_pid FROM wdf_tasks WHERE worker_pid IS NOT NULL")->Enumerate('worker_pid');
        $tasks = array_filter($test,'system_process_running');

        $pids = implode(",",array_filter(array_diff($test,$tasks)));
        if( $pids )
        {
            $debug = $ds->ExecuteSql("SELECT * FROM wdf_tasks WHERE (assigned<NOW()-INTERVAL 5 SECOND) AND worker_pid IN($pids)")->results();
            $rs = $ds->ExecuteSql(
                "UPDATE wdf_tasks SET worker_pid=null, assigned=null WHERE
                 (assigned<NOW()-INTERVAL 5 SECOND) AND worker_pid IN($pids)"
                );
            if( $rs && $rs->Count() )
                log_debug("Restarted ".$rs->Count()." tasks whose workers ($pids) did not exist anymore",$debug);

            $rs = $ds->ExecuteSql("UPDATE wdf_tasks SET assigned=null WHERE isnull(worker_pid)");
            if( $rs && $rs->Count() )
                log_debug("Restarted ".$rs->Count()." tasks that were assigend but to noone");
        }
    }

	public static function Reserve(int $max_priority=0)
	{
        $ds = DataSource::Get();
		$wpid = getmypid();
        try
        {
            $where = "enabled=1 AND isnull(worker_pid) AND isnull(parent_task) AND isnull(assigned) AND (ISNULL(start) OR start<=now())";
            if( $max_priority )
                $where .= " AND priority<=$max_priority";

            $ids = $ds->ExecuteSql("SELECT id FROM wdf_tasks WHERE $where ORDER BY id DESC LIMIT 10")->Enumerate('id');
            if (count($ids) == 0)
                return false;

            $ds->ExecuteSql(
                "UPDATE wdf_tasks SET worker_pid=?, assigned=now() WHERE
                    $where AND id IN(".implode(",",$ids).")
                    ORDER BY priority ASC, id DESC LIMIT 1"
                ,$wpid);
        }
        catch(WdfDbException $ex)
        {
            list($c1,$c2,$msg) = $ex->getErrorInfo();
            if( stripos($msg,"deadlock") === false )
                log_error($ex);
            return false;
        }
		return WdfTaskModel::Make($ds)->eq('worker_pid',$wpid)->current();
	}

    public static function SetState($state = 'idle')
    {
        static $shmdir = false, $shm = false;
        $um = umask(0);
        try
        {
            if ($shm === false)
            {
                $shmdir = "/run/shm/" . $GLOBALS['CONFIG']['system']['application_name'];
                @mkdir($shmdir, 0777, true);
                $shm = "{$shmdir}/" . getmypid();
            }
            switch ($state)
            {
                case 'idle':
                    @unlink("{$shm}.running");
                    break;
                case 'running':
                    @unlink("{$shm}.idle");
                    break;
                case 'done':
                    @unlink("{$shm}.running");
                    @unlink("{$shm}.idle");
                default:
                    return;
            }

            @touch("{$shm}.{$state}");
            // log_debug(__METHOD__, "{$shm}.{$state}");
            if ($state == 'idle')
            {
                $ttl = time() - 30;
                foreach (glob("{$shmdir}/*.*") as $f)
                {
                    if (!($mt = @filemtime($f)) || ($mt > $ttl))
                        continue;
                    $pid = array_first(explode(".", basename($f)));
                    // if (file_exists("/proc/$pid/cmdline"))
                    if (system_process_running($pid))
                        @touch($f);
                    else
                    {
                        // log_debug("unklink $f");
                        @unlink($f);
                    }
                }
            }
        }
        finally
        {
            umask($um);
        }
    }

    public function SetPriority(int $priority = 3)
    {
        $this->priority = $priority;
        return $this;
    }

	public function Run($inline=false)
	{
        if( $this->isVirtual )
        {
            log_debug("Skipping virtual task ".$this->name);
            return;
        }
        list($name,$method) = explode("-","{$this->name}-run");
        $args = isset($this->arguments) ? unserialize($this->arguments) : [];

		if( is_subclass_of($name, \ScavixWDF\Tasks\Task::class) )
		{

            $worker = new $name($this);

            if( !method_exists($worker, $method) )
            {
                log_debug("Method $name::$method does not exist, falling back to 'run'");
                $method = 'run';
            }
            $exectime = microtime(true);
            try
            {
                $worker->$method($args);
            }
            catch (\ScavixWDF\TerminationException $tex)
            {
                $tex->writeLog();
            }

            $exectime = round((microtime(true) - $exectime) * 1000);

            $start = DateTimeEx::Make(ifavail($this,'start','created'));
            $runtime = max($exectime,round((microtime(true) - $start->getTimestamp()) * 1000));
            $worker->Finished($method,$runtime,$exectime);

            if( $inline )
            {
                // seeems unused. todo: check and remove
                foreach( WdfTaskModel::Make($this->_ds)->eq('parent_task',$this->id)->orderBy('id') as $child )
                    $child->Run(true);
            }
            else
            {
                // make sure children are enabled if (for whatever reason) they are not
                $children = WdfTaskModel::Make($this->_ds)->eq('parent_task', $this->id)->eq('enabled', 0); //    ->orX(2)->isNull('start')->isPast('start');
                foreach ($children as $t)
                {
                    $t->Go(false);
                    // log_debug("Releasing {$t->id} for parent {$this->id}",$t->AsArray());
                }
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

    /**
     * @override <Model::Delete>
     */
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
        $this->_ds->ExecuteSql("UPDATE wdf_tasks SET parent_task=null, enabled=1 WHERE parent_task=?", [$this->id]);
		return true;
	}

    /**
     * Deletes all children of this WdfTaskModel.
     *
     * @param bool $delete_self Delete itself too?
     * @param bool $complete If true deletes tasks that are marked to follow deletion of this one
     * @return void
     */
	function DeleteChildren($delete_self=false,$complete=false)
	{
        // log_debug(__METHOD__, $this, $delete_self, $complete);
        if( $this->isVirtual )
            return;

		$q = WdfTaskModel::Make()->eq('parent_task',$this->id);
		if( !$complete )
			$q = $q->eq('follow_deletion',1);
		foreach( $q as $t )
			$t->DeleteChildren(true, $complete);
		if( $delete_self )
			$this->Delete();
	}

    /**
     * Adds a child task.
     *
     * If no arguments are given, will copy arguments of this task to the child.
     *
     * @param string $name Task name
     * @param array $args Optional arguments.
     * @return WdfTaskModel $this
     */
    function AddChild($name,$args='copy')
    {
        $this->CreateChild($name,$args);
        return $this;
    }

    /**
     * Creates a child task and returns it.
     *
     * @param string $name Task name
     * @param array|string $args Optional arguments.
     * @param bool $only_if_not_running Creates a virtual dummy child, if there's already another task with the same name.
     * @return WdfTaskModel The child task
     */
    function CreateChild($name,$args='copy',$only_if_not_running=false)
    {
        if( $args == 'copy' )
            $args = unserialize($this->arguments);
        if( $this->isVirtual )
            return WdfTaskModel::Create($name,null,$only_if_not_running,true)->SetArgs($args)->DependsOn($this);
        return WdfTaskModel::Create($name,null,$only_if_not_running)->SetArgs($args)->DependsOn($this);
    }

    /**
     * @shortcut <WdfTaskModel::CreateChildOnce> with `$only_if_not_running=true`
     */
    function CreateChildOnce($name,$args='copy')
    {
        return $this->CreateChild($name, $args, true);
    }

    function Repeat($delay_seconds=0)
    {
        $r = $this->GetArg('repetition',0)+1;
        return $this->CreateChild($this->name)->SetArg('repetition',$r)->Delay($delay_seconds)->Go();
    }
}