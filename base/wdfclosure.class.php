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

namespace ScavixWDF\Base;

/**
 * Implements persistable closures.
 * 
 * @see http://www.htmlist.com/development/extending-php-5-3-closures-with-serialization-and-reflection/
 * @todo Check if this is still needed in times of PHP8.1
 */
class WdfClosure
{
	protected $closure = NULL;
	protected $reflection = NULL;
	public $code = NULL;
	public $used_variables = [];

	public function __construct($function=null)
    {
        if ( !$function instanceOf \Closure )
			throw new \InvalidArgumentException();

		$this->closure = $function;
		$this->reflection = new \ReflectionFunction($function);
		$this->code = $this->_fetchCode();
		$this->used_variables = $this->_fetchUsedVariables();
    }
    
	public function __invoke(...$args)
	{
		return $this->reflection->invokeArgs($args);
	}

	/**
	 * @internal 
	 */
	public function getClosure()
	{
		return $this->closure;
	}

	protected function _fetchCode()
	{
		// Open file and seek to the first line of the closure
		$file = new \SplFileObject($this->reflection->getFileName());
		$file->seek($this->reflection->getStartLine()-1);

		// Retrieve all of the lines that contain code for the closure
		$code = '';
		while ($file->key() < $this->reflection->getEndLine())
		{
			$code .= $file->current();
			$file->next();
		}

		// Only keep the code defining that closure
		$begin = strpos($code, 'function');
		$end = strrpos($code, '}');
		$code = substr($code, $begin, $end - $begin + 1);

		return $code;
	}

	/**
	 * @internal
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 *  @internal
	 */
	public function getParameters()
	{
		return $this->reflection->getParameters();
	}

	protected function _fetchUsedVariables()
	{
		// Make sure the use construct is actually used
		$use_index = stripos($this->code, 'use');
		if ( ! $use_index)
			return [];

        if( !preg_match('/function\([^\)]*\)\s*use\s*\(([^\)]+)\)/U',$this->code,$m) )
            return;
        
        $vars = explode(',',$m[1]);
        
		// Get the names of the variables inside the use statement
//		$begin = strpos($this->code, '(', $use_index) + 1;
//		$end = strpos($this->code, ')', $begin);
//		$vars = explode(',', substr($this->code, $begin, $end - $begin));

		// Get the static variables of the function via reflection
		$static_vars = $this->reflection->getStaticVariables();
	
		// Only keep the variables that appeared in both sets
		$used_vars = [];
		foreach ($vars as $var)
		{
			$var = trim($var,' $');
			$used_vars[$var] = $static_vars[$var];
		}

		return $used_vars;
	}

	/**
	 * @internal
	 */
	public function getUsedVariables()
	{
		return $this->used_variables;
	}

	public function __sleep()
	{
		return array('code', 'used_variables');
	}

	public function __wakeup()
	{
		extract(ifavail($this,'used_variables')?:[]);

		eval('$_function = '.$this->code.';');
		if (isset($_function) AND $_function instanceOf \Closure)
		{
			$this->closure = $_function;
			$this->reflection = new \ReflectionFunction($_function);
		}
		else
			throw new \Exception();
	}
}