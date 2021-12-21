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
 * Represents some work to be done.
 * 
 * Processes defined in Task class can be run from different SAPI.
 * You may create and run the directly:
 * `MyTask::Make()->DoMyWork()`
 * You may delay them using <WdfTaskModel> and the WDF cli backend:
 * `MyTask::Async('DoMyWork')->Go()`
 * You may execute them from command line:
 * `php index.php mytask-domywork`
 */
abstract class Task
{
    var $model;
    var $ds;
    
    function __construct(WdfTaskModel $model=null)
    {
        $this->model = $model;
        $this->ds = \ScavixWDF\Model\DataSource::Get();
    }
    
    /**
     * Generic static construction method that can be called on subclasses.
     * 
     * Sample: `MyTask::Make()->DoWork();`
     * 
     * @return \ScavixWDF\Tasks\Task
     */
    public static function Make() : Task
    {
        $name = get_called_class();
        return new $name();
    }
    
    /**
     * Creates a WdfTaskModel for async processing.
     * 
     * @param string $method Optional method name to be started.
     * @return \ScavixWDF\Tasks\WdfTaskModel
     */
    public static function Async($method='run') : WdfTaskModel
    {
        $name = get_called_class()."-$method";
        return WdfTaskModel::Create($name);
    }
    
    /**
     * Creates a WdfTaskModel for async processing if it not already exists.
     * 
     * @param string $method Optional method name to be started.
     * @param bool $return_original If true and there's already another task present, return that one, else return a dummy if there's another one
     * @return \ScavixWDF\Tasks\WdfTaskModel The new task or a dummy if already present or the one already present
     */
    public static function AsyncOnce($method='run', $return_original=false) : WdfTaskModel
    {
        $name = get_called_class()."-$method";
        return WdfTaskModel::CreateOnce($name, $return_original);
    }
    
    public static function IsRunning($method='run')
    {
        $name = get_called_class()."-$method";
        return !!WdfTaskModel::Make()->eq('name',$name)->scalar('id');
    }
    
    /**
     * Runs this task in another (CLI) process.
     * 
     * @see <cli_run_script>
     * @param array $args Arguments
     * @param string $method Optional method name
     * @param bool $return_cmdline If true, process will not be started, but it's commandline is returned.
     * @return void|string
     */
    public function Fork($args=[],$method='run',$return_cmdline=false)
    {
        if( !function_exists("cli_run_taskprocessor") )
            system_load_module('modules/cli.php');
        
        array_unshift($args,get_called_class()."-{$method}");
        return cli_run_script(CLI_SELF,$args,$_SERVER,$return_cmdline);
    }

    /**
     * Subclasses may implement this to react on arguments before actual run.
     * @param array $args Array with arguments
     * @return array New array with arguments
     */
    function PreprocessArguments(array $args): array
    {
        return $args;
    }
    
    /**
     * Central processing method. Subclasses must implement this.
     * @param array $args Array with arguments
     * @return void
     */
    abstract function Run($args);
    
    /**
     * Called once the Task finished processing.
     * 
     * @param string $method The method processed
     * @param int $runtime The total time from creation/start till not in ms
     * @param int $exectime The time in ms needed for actual execution (Run method)
     * @return void
     */
    public function Finished($method, $runtime, $exectime)
    {
    }
    
    protected function mapCliArgs(&$args,$exact,$names)
    {
        $ca = count($args); 
        $cn = is_array($names)?count($names):$names;
        if( $ca<$cn || ($exact && $ca!=$cn) )
            return array_fill(0,$cn,false);
        if( !is_array($names) ) 
            $names = range(0,$cn-1);
        $res = [];
        foreach( $names as $n )
            $res[$n] = array_shift($args);
        //log_debug($res);
        return $res;
    }
    
    protected function getArg($args, ...$aliases)
    {
        foreach( $aliases as $a )
            if( isset($args[$a]) )
                return $args[$a];
        return null;
    }
    
    protected function hasFlag($args, ...$names)
    {
        foreach( $names as $n )
            if( in_array($n, $args) )
                return true;
        return false;
    }
}
