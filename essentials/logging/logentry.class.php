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
 */
namespace ScavixWDF\Logging;
use \stdClass;

/**
 * Represents a logfile entry.
 * 
 * We use this class to collect information before logging them.
 * It allows to create murch more detailed logs as the PHP standart allows.
 */
class LogEntry
{
    public $datetime;
    public $categories;
    public $severity;
    public $trace;
    public $message;
    
    function __construct($severity,$categories,$trace,$message,$max_trace_depth)
    {
        $this->datetime = time();
        $this->categories = $categories;
        $this->severity = $severity;
        $this->trace = $trace?$this->cleanupTrace($trace,$max_trace_depth):false;
        $this->message = substr($message,0,1024*50);
    }
    
	private function cleanupTrace($stacktrace,$max_trace_depth)
	{
		$args = [];
		$info = [];
		$stack = [];
		$stcnt = count($stacktrace);
		foreach($stacktrace as $i=>$t0)
		{
			if( isset($t0['object']) )
            {
                $id = ($t0['object'] instanceof \ScavixWDF\Base\Renderable)
                    ?$t0['object']->_storage_id
                    :false;
				unset($t0['object']);
            }
            else $id = false;
            
            if( isset($t0['class']) && $id )
                $t0['class'] .= "($id)";
            
			if( isset($t0['file']) )
			{
				if( ends_with($t0['file'],"/essentials/logging/logger.class.php") ||
					ends_with($t0['file'],"/essentials/logging/tracelogger.class.php") ||
					ends_with($t0['file'],"/essentials/logging/logentry.php") ||
					ends_with($t0['file'],"/essentials/logging.php") )
					continue;
				$t0['location'] = $t0['file'].":".$t0['line'];
			}
			else
				$t0['location'] = "*UNKNOWN*";
            
			if( $t0['function'] == 'system_render_object_tree' ||
				$t0['function'] == 'global_error_handler' ||
				$t0['function'] == 'WdfRender' ||
				$t0['function'] == 'WdfRenderAsRoot' )
				$t0['args'] = array("*TRUNCATED*");
            
			$stack[] = $t0;
			if( count($stack) == $max_trace_depth )
				break;
		}
		
		if( $stack[count($stack)-1]['function'] == 'system_execute' )
			array_pop($stack);
		if( $stack[count($stack)-1]['function'] == 'system_exit' )
			array_pop($stack);
		if( $stack[count($stack)-1]['function'] == 'system_invoke_request' )
			array_pop($stack);
		if( $stack[count($stack)-1]['function'] == 'call_user_func_array' )
			array_pop($stack);
		
		return $stack;
	}
    
    private function parseTrace($stacktrace)
	{
		$stack = [];
		
		foreach( $stacktrace as $t0 )
		{
            if( isset($t0['class']) && isset($t0['type']) )
            {
                $id = (isset($t0['object']) && ($t0['object'] instanceof \ScavixWDF\Base\Renderable))
                    ?"({$t0['object']->_storage_id})"
                    :'';
				$function = $t0['class'].$id.$t0['type'].$t0['function'];
            }
			else
				$function = $t0['function'];
			
			if( isset($t0['location']))
				$stack[] = sprintf("+ %s(...) [in %s]",$function,$t0['location']);
			else
				$stack[] = sprintf("+ %s(...)",$function);
		}
		return implode("\n",$stack);
	}
    
	/**
	 * @internal Creates a human readable representation of this <LogEntry>
	 */
    public function toReadable()
    {
        $content = date("[Y-m-d H:i:s.m]",$this->datetime);
		$content .= " [{$this->severity}]";
		$content .= " (".implode(",",$this->categories).")";
		$content .= "\t{$this->message}";
		if( $this->trace )
			$content .= "\n".$this->parseTrace($this->trace);
        return $content;
    }
	
	/**
	 * @internal Creates a machine readable representation of this <LogEntry>
	 */
	function serialize()
	{
        if( !function_exists('utf8_encode') )
            return "missing php-xml";
        
        if( class_exists("\\ScavixWDF\\Base\\Renderable") )
            $mss = \ScavixWDF\Base\Renderable::StartSlimSerialize();
        else
            $mss = false;
        
		$res = new stdClass();
		$res->dt = date("c",$this->datetime);
		$res->cat = [];
		foreach( array_values($this->categories) as $v )
			if( $v )
                $res->cat[] = utf8_encode("$v");
		$res->sev = utf8_encode($this->severity);
		$res->msg = utf8_encode($this->message);
        if( logging_mem_ok() )
            $res->trace = $this->trace;
        else
            $res->trace = "*OUTOFMEM*";
		$out = json_encode($res);
		if( !$out )
		{
			$res->trace = [];
			foreach( $this->trace as $i=>$t )
			{
				if( !json_encode($t) )
                {
                    $t['args'] = json_decode(json_encode($t['args'],JSON_PARTIAL_OUTPUT_ON_ERROR),true);
                    $t['args'] = $t['args']?[$t['args']]:['**UNRENDERABLE**'];
                }
				$res->trace[$i] = $t;
			}	
			$out = json_encode($res);
		}
        
        if( $mss )
            \ScavixWDF\Base\Renderable::StopSlimSerialize();
		return $out;
	}
}