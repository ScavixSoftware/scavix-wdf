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

/**
 * Collect large number of same tasks into a pool and let it manage the processing
 * without overloading the system.
 *
 * To add Tasks to a pool simply Depend them on it:
 * <code php>
 * $pool = TaskPool::Async()->SetArg('processors',10);
 * for($i=0; $i<10000; $i++)
 *     MyTask::Async()->SetArg('tasknum',$i)->DependsOn($pool);
 * $pool->Go();
 * </code>
 * Note that 'processors' must be a numeric value between 1 and 100, default is 5.
 */
class TaskPool extends Task
{
    /**
     * Creates a reusable TaskPool.
     *
     * Will check the wdf_tasks table to see if there's already a Taskpool with the same name.
     * If so it will be used, else a new one will be created.
     * @param mixed $name TaskPool name
     * @return WdfTaskModel The new or loaded TaskPool
     */
    public static function Reusable($name)
    {
        $res = self::Async();
        $res->name .= "-$name";
        if( $cur = WdfTaskModel::Make()->eq('name', $res->name)->current() )
        {
            $cur->RecreateOnSave = true;
            return $cur;
        }
        return $res;
    }

    /**
     * @internal Overwrites parent to fix 'run' method set up things.
     */
    public static function Async($method = 'run'): WdfTaskModel
    {
        return parent::Async('run')->SetCascadeGo(false);
    }

    /**
     * @internal Overwrites parent to fix 'run' method set up things.
     */
    public static function AsyncOnce($method = 'run', $return_original = false, $args = false): WdfTaskModel
    {
        return parent::AsyncOnce('run', $return_original, $args)->SetCascadeGo(false);
    }

    /**
     * @internal TaskPool loop
     */
    function Run($args)
    {
        $processors = max(1, min(100, intval(ifavail($args, 'processors') ?: '5')));
        $delay = max(1000, min(10000, intval(ifavail($args, 'delay') ?: '1000')));
        $min_tasks_required = $processors;

        while (true) // Loop for Reusable pools, (re-)load child-tasks from DB
        {
            $task_ids = WdfTaskModel::Make()->eq('parent_task', $this->model->id)
                ->orderBy('priority')->orderBy('start')
                ->enumerate('id');
            if (count($task_ids) <= $min_tasks_required)
                break;

            // After initial comparison check again existance of at least one child task.
            // This is needed to minimize hanging tasks created for a reusable pool.
            $min_tasks_required = 0;
            $tasks = [];

            // Inner loop: Load and release some tasks and keep that number running while there are some in the preloaded IDs
            $keep_list_until = time() + 10; // do not keep the task list forever as this would kill priority handling for reusable pools
            while (count($task_ids) > 0 && time() < $keep_list_until)
            {
                while (count($tasks) < $processors)
                {
                    $task = WdfTaskModel::Make()->eq('id', array_shift($task_ids) ?: 0)->current();
                    if ($task)
                    {
                        $task->parent_task = null;
                        $task->Save();
                        $task->Go();
                        $tasks[] = $task->id;
                    }
                    else
                        break;
                }
                usleep($delay * 1000);

                // Check if the currently monitored tasks are still present in DB (so: running)
                $present = WdfTaskModel::Make()->in('id', $tasks)->enumerate('id');
                $tasks = array_intersect($tasks, $present);
            }
        }
    }
}
